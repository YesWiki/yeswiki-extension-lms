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
    public function getUserEntry(): ?array
    {
        // lazy loading
        if (is_null($this->userEntry)) {
            $userEntries = $this->entryManager->search(['formsIds' =>
                [$this->wiki->config['lms_config']['learner_form_id']], 'user' => $this->username]);
            if (!empty($userEntries)) {
                // get first element of userEntries (a user can have several associated entries)
                $this->userEntry = reset($userEntries);
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
     * Get the email of the learner if it's defined in the user entry
     * @return string|null the learner email if it's defined in the user entry, otherwise return null
     */
    public function getEmail(): ?string
    {
        return !empty($this->getUserEntry())
                && !empty($this->getUserEntry()[$this->wiki->config['lms_config']['learner_mail_field']]) ?
            $this->getUserEntry()[$this->wiki->config['lms_config']['learner_mail_field']]
            : null;
    }

    /**
     * Get the tag of the learner user entry
     * @return string|null if the learner has a user entry return its tag, otherwise return null
     */
    public function getUserEntryTag(): ?string
    {
        return !empty($this->getUserEntry()) && !empty($this->getUserEntry()['id_fiche']) ?
            // the user entry tag is always the username
            $this->getUserEntry()['id_fiche']
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

        return $this->isAdmin() ||
            (
                $module->getStatus($course) == ModuleStatus::OPEN
                &&
                (
                    !$course->isModuleScripted() //no constraint
                    || !$previousModule // or scripted but no previous module
                    || $this->hasFinishedModule($course, $previousModule)  // previous module should be finished
                )
            );
    }

    /**
     * check canAccessActivity
     * @param Course $course
     * @param Module $module
     * @param Activity $activity
     * @param bool $checkConditions parameters used to prevent loop with ConditionChecker
     * @return bool
     */
    public function canAccessActivity(
        Course $course,
        Module $module,
        Activity $activity,
        bool $checkConditions = true
    ): bool {
        $checkConditions = (!$this->conditionsChecker->isConditionsEnabled()) ? false : $checkConditions;
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
                        !$course->isActivityScripted() // no constraint
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
    public function hasOpened(Course $course, Module $module, Activity $activity = null): bool
    {
        $progress = $this->getProgresses()->getProgressForActivityOrModuleForLearner($this, $course, $module,
            $activity);
        return !empty($progress);
    }

    /**
     * check if an activity has been finished by the learner
     * @param Course $course
     * @param Module $module
     * @param Activity $activity
     * @return bool
     */
    public function hasFinishedActivity(Course $course, Module $module, Activity $activity): bool
    {
        $userNames = $this->getProgresses()->getUsernamesForFinishedActivity($course, $module, $activity);
        return in_array($this->getUsername(), $userNames);
    }


    /**
     * check if a module has been finished by the learner
     * @param Course $course
     * @param Module $module
     * @param bool $checkConditions parameters used to prevent loop with ConditionChecker
     * @return bool
     */
    public function hasFinishedModule(Course $course, Module $module, bool $checkConditions = true): bool
    {
        $checkConditions = (!$this->conditionsChecker->isConditionsEnabled()) ? false : $checkConditions;
        if (!$this->hasOpened($course, $module)) {
            return false;
        }
        $lastActivityTag = $module->getLastActivityTag();
        foreach ($module->getActivities() as $activity) {
            if ($activity->getTag() != $lastActivityTag) {
                if (!$this->hasFinishedActivity($course, $module, $activity)) {
                    return false;
                }
            } elseif ($checkConditions) {
                // the last activity should not be finihed because the next module has not been opened
                // so check conditions
                return $this->conditionsChecker
                    ->passActivityNavigationConditions($course, $module, $activity);
            }
        }
        return true;
    }
}
