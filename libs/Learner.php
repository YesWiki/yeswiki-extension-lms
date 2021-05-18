<?php

namespace YesWiki\lms;

use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Lms\Service\ConditionsChecker;
use YesWiki\Lms\Service\LearnerManager;
use YesWiki\Wiki;

class Learner
{
    // username of the Learner (the same than the user's one)
    protected $username;
    // fullname of the Learner
    protected $fullname;
    // the associated user entry if exists
    protected $userEntry;
    // progresses (lazy loading)
    protected $progresses;

    // the conditionsChecker to check conditions
    protected $conditionsChecker;
    // the entryManager to get the user entry
    protected $entryManager;
    // the learnerManager to get the learner's progresses
    protected $learnerManager;
    // the Wiki service
    protected $wiki;

    /**
     * Learner constructor
     * A learner always corresponds to a user
     * @param string $username the name of the learner
     * @param ConditionsChecker $conditionsChecker the ConditionsChecker service
     * @param EntryManager $entryManager the EntryManager service
     * @param LearnerManager $learnerManager the LearnerManager service
     * @param Wiki $wiki the Wiki service
     */
    public function __construct(
        string $username,
        ConditionsChecker $conditionsChecker,
        EntryManager $entryManager,
        LearnerManager $learnerManager,
        Wiki $wiki
    ) {
        $this->username = $username;
        $this->conditionsChecker = $conditionsChecker;
        $this->entryManager = $entryManager;
        $this->learnerManager = $learnerManager;
        $this->wiki = $wiki;
        $this->progresses = null;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Get the associated user entry of the learner
     * @return array the user entry or an empty array if the user doesn't have
     */
    public function getUserEntry(): array
    {
        // lazy loading
        if (is_null($this->userEntry)) {
            $this->userEntry = $this->entryManager->getOne($this->username);
            if (!$this->userEntry) {
                // if no associated user entry, we assign an empty array to avoid other loadings
                $this->userEntry = [];
            }
        }
        return $this->userEntry;
    }

    /**
     * Get the full name of the learner
     * His full name is normally the 'bf_title' field of its user entry, but if he has any, the username will be
     * returned
     * @return string if the learner has a user entry return the 'bf_title', otherwise its username
     */
    public function getFullName(): string
    {
        return !empty($this->getUserEntry()) && !empty($this->getUserEntry()['bf_titre']) ?
            $this->getUserEntry()['bf_titre']
            : $this->getUsername();
    }

    /**
     * Get the tag of the learner user entry
     * @return string|null if the learner has a user entry return its tag, otherwise return null
     */
    public function getUserEntryTag(): ?string
    {
        return !empty($this->getUserEntry()) ?
            // the user entry tag is always the username
            $this->getUsername()
            : null;
    }

    /**
     * Does the learner is a wiki admin ?
     * (TODO if needed, it can evolved in isInstructor but for the moment instructors are wiki admins)
     * @return bool the answer
     */
    public function isAdmin(): bool
    {
        return $this->wiki->userIsAdmin($this->username);
    }

    /**
     * get Progresses for current learner
     * @return Progresses
     */
    public function getProgresses(): Progresses
    {
        // Lazy Loading
        if (is_null($this->progresses)) {
            $this->progresses = $this->learnerManager->getAllProgressesForLearner($this);
        }
        return $this->progresses;
    }

    /**
     * check canAccessModule
     * @param Course $course
     * @param Module $module
     * @return bool
     */
    public function canAccessModule(Course $course, Module $module): bool
    {
        $previousModule = $course->getPreviousModule($module->getTag());
        $previousActivity = (isset($previousModule)) ? $previousModule->getLastActivity() : null;
        
        return $this->isAdmin() ||
            (
                $module->getStatus($course) == ModuleStatus::OPEN
                &&
                (
                    !$course->isModuleScripted() //no constraint
                    || !$previousModule // or scripted but no previous module
                    ||
                    (
                        $this->hasOpened($course, $previousModule) // previous module should be opened
                        && (
                            !$previousActivity // scripted with empty but opened previous module
                            || $this->hasOpened($course, $previousModule, $previousActivity)
                                // or scripted and has started the last Activity of the previous module
                        )
                    )
                )
            );
    }

    /**
     * check canAccessActivity
     * @param Course $course
     * @param Module $module
     * @param Activity $activity
     * @return bool
     */
    public function canAccessActivity(Course $course, Module $module, Activity $activity): bool
    {
        $checkConditions = $this->conditionsChecker->isConditionsEnabled() ;// TODO avoid loop? false : $checkConditions;
        $previousActivity = $module->getPreviousActivity($activity->getTag());
        return $this->isAdmin() ||
            (
                $module->getStatus($course) == ModuleStatus::OPEN
                &&
                (
                    (
                        !$course->isModuleScripted() //no constraint
                        || $this->canAccessModule($course, $module) // module accessible if scripted
                    )
                    &&
                    (
                        !$course->isActivityScripted() //no constraint
                        || !$previousActivity // or scripted but no previous activity
                        ||
                        (
                            $this->hasOpened($course, $module, $previousActivity) // previous activity should be opened
                            &&
                            (
                                !$checkConditions
                                || $this->conditionsChecker
                                    ->passActivityNavigationConditions($course, $module, $previousActivity, $activity)
                            )
                        )
                    )
                )
            );
    }

    /**
     * check if an activity or a module has been opened by the learner
     * @param Course $course
     * @param Module $module
     * @param null|Activity $activity
     * @return bool
     */
    public function hasOpened(Course $course, Module $module, Activity $activity = null):bool
    {
        $progress = $this->getProgresses()->getProgressForActivityOrModuleForLearner($this, $course, $module, $activity);
        return !empty($progress);
    }
}
