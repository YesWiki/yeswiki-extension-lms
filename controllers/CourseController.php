<?php

namespace YesWiki\Lms\Controller;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\YesWikiController;
use YesWiki\Lms\Activity;
use YesWiki\Lms\Course;
use YesWiki\Lms\Module;
use YesWiki\Lms\ModuleStatus;
use YesWiki\Lms\Service\CourseManager;
use YesWiki\Lms\Service\DateManager;
use YesWiki\Lms\Service\LearnerManager;

class CourseController extends YesWikiController
{
    protected $entryManager;
    protected $courseManager;
    protected $learnerManager;
    protected $dateManager;
    protected $config;

    /**
     * CourseController constructor
     * @param EntryManager $entryManager the injected EntryManager instance
     * @param CourseManager $courseManager the injected CourseManager instance
     * @param LearnerManager $learnerManager the injected LearnerManager instance
     * @param DateManager $dateManager the injected DateManager instance
     * @param ParameterBagInterface $config the injected Wiki instance
     */
    public function __construct(
        EntryManager $entryManager,
        CourseManager $courseManager,
        LearnerManager $learnerManager,
        DateManager $dateManager,
        ParameterBagInterface $config
    ) {
        $this->entryManager = $entryManager;
        $this->courseManager = $courseManager;
        $this->learnerManager = $learnerManager;
        $this->dateManager = $dateManager;
        $this->config = $config->all();
    }

    /**
     * Get the contextual course according to the Get parameter 'course' and the existing course. By order :
     *
     *   - if the Get parameter 'course' refers to a tag associated to a course entry, return it
     *   - else if there is at least one course in the database, return the first created one
     *   - if not, return null
     *
     * @return Course|null the course entry or null if not found
     */
    public function getContextualCourse(): ?Course
    {
        $courseTag = empty($_GET['course']) ? '' : $_GET['course'];
        if (!empty($courseTag)) {
            if ($course = $this->courseManager->getCourse($courseTag)) {
                return $course;
            }
        }
        $courses = $this->courseManager->getAllCourses();
        return !empty($courses) ?
            $courses[array_key_first($courses)]
            : null;
    }

    /**
     * Get the contextual module according to the given course entry, its modules, the Get parameter 'module' and the current page.
     *
     * By order :
     *   - if the Get parameter 'module' refer to a tag associated to a module entry of the given course, and if its activities
     *   contains the current page, return this module
     *   - if not, return null
     *   - if the current page refers to a module which is contained by the given course, return it
     *   - or if the current page refers to an activity and there is at least one module in the given course which contains the
     *   current page, return it
     *   - if not, return null
     *
     * @param $course|null Course the given course entry
     * @return Module|null the module entry or null if not found
     */
    public function getContextualModule(?Course $course): ?Module
    {
        // if an handler is after the page tag in the wiki parameter variable, get only the tag
        $currentPageTag = isset($_GET['wiki']) ?
            strpos($_GET['wiki'], '/') ?
                substr($_GET['wiki'], 0, strpos($_GET['wiki'], '/'))
                : $_GET['wiki']
            : null;

        if (!empty($currentPageTag) && $course) {

            // the activity is not loaded from the manager because we don't want to requests the fields (it's an exception)
            $activity = new Activity($this->config, $this->entryManager, $this->dateManager, $currentPageTag);

            // if nav tabs are configurated and if the current activity is a tab activity, we refer now to the parent tab activity
            $activity = $this->getParentTabActivity($activity);

            $moduleTag = isset($_GET['module']) ? $_GET['module'] : null;

            if ($moduleTag) {
                // if the module is specified in the GET parameter, return it if the tag corresponds
                $module = $this->courseManager->getModule($moduleTag);

                return ($module && $course->hasModule($module->getTag()) && $module->hasActivity($activity->getTag())) ?
                    $module
                    : null;
            } else {
                // if the current page refers to a module of the course, return it
                $currentModule = $this->courseManager->getModule($activity->getTag());
                if ($currentModule && $course->hasModule($activity->getTag())) {
                    return $currentModule;
                }

                // find in the course modules, the first module which contains the activity
                if ($course) {
                    foreach ($course->getModules() as $currentModule) {
                        if ($currentModule->hasActivity($activity->getTag())) {
                            return $currentModule;
                        }
                    }
                }
            }
        }
        return null;
    }

    /**
     * Get the parent tab activity of the given activity if it's an activity tab
     *
     * Indeed, if the Lms module is configurated for nav tabs, the activity tabs have the tags 'MyTagX' with X >= 2 and
     * it's always 'MyTag' which are referenced by the Lms modules.
     *
     * @param Activity $activity the given activity
     * @return Activity if the tabs are configurated and there is a parent tab activity return it, otherwise return the
     * same activity
     */
    public function getParentTabActivity(Activity $activity): Activity
    {
        if ($this->config['lms_config']['use_tabs']) {
            $parentActivityTag = preg_replace('/[0-9]*$/', '', $activity->getTag());
            if ($parentActivityTag != $activity->getTag()) {
                return $this->courseManager->getActivity($parentActivityTag);
            } else {
                return $activity;
            }
        } else {
            return $activity;
        }
    }

    /**
     * Display the given module as an information card
     * @param Course $course the course to wich the module belongs
     * @param Module $module the module to display
     * @return string the generated output
     */
    public function renderModuleCard(Course $course, Module $module): string
    {
        $imageSize = is_int($this->config['lms_config']['module_image_size_in_course']) ?
            $this->config['lms_config']['module_image_size_in_course']
            : 400;
        $image = empty($module->getField('imagebf_image')) || !is_file('files/' . $module->getField('imagebf_image')) ?
            null :
            // the resizing keep the orginal ratio with maximum width and height defined at $imageSize
            redimensionner_image(
                'files/' . $module->getField('imagebf_image'),
                'cache/' . $imageSize . 'x' . $imageSize . $module->getField('imagebf_image'),
                $imageSize,
                $imageSize,
                'fit'
            );

        // TODO duplicate code (function navigationmodule in bazarlms.fonc.inc.php) : when passing to twig, mutualize it

        $learner = $this->learnerManager->getLearner();
        $disabledLink = !$module->isAccessibleBy($learner, $course) || $module->getStatus($course) == ModuleStatus::UNKNOWN;

        // TODO implement getNextActivity for a learner, for the moment choose the first activity of the module
        if (!$disabledLink) {
            $activityLink = $this->wiki->href(
                '',
                $module->getFirstActivityTag(),
                ['course' => $course->getTag(), 'module' => $module->getTag()],
                false
            );
        }
        $labelStart = $learner && $learner->isAdmin() && $module->getStatus($course) != ModuleStatus::OPEN ?
            _t('LMS_BEGIN_ONLY_ADMIN')
            : _t('LMS_BEGIN');
        $statusMsg = $this->calculateModuleStatusMessage($course, $module);

        // End of duplicate code

        return $this->render('@lms/module-card.twig', [
            'course' => $course,
            'module' => $module,
            'image' => $image,
            'activityLink' => $activityLink ?? null,
            'labelStart' => $labelStart,
            'statusMsg' => $statusMsg,
            'disabledLink' => $disabledLink,
            'learner' => $learner,
            // TODO replace it by a Twig Macro
            'formatter' => $this->getTwigFormatter()
        ]);
    }

    /**
     * Calculate the message to display to the user the module status
     * @param Course $course the course to wich the module belongs
     * @param Module $module the module concerned
     * @return string this presentation string
     */
    public function calculateModuleStatusMessage(Course $course, Module $module): string
    {
        $status = $module->getStatus($course);
        $date = empty($module->getField('bf_date_ouverture')) ? '' : Carbon::parse($module->getField('bf_date_ouverture'));
        switch ($status) {
            case ModuleStatus::UNKNOWN:
                return _t('LMS_UNKNOWN_STATUS_MODULE');
                break;
            case ModuleStatus::CLOSED:
                return _t('LMS_CLOSED_MODULE');
                break;
            case ModuleStatus::TO_BE_OPEN:
                return _t('LMS_MODULE_WILL_OPEN') . ' '
                    . Carbon::now()->locale($GLOBALS['prefered_language'])->DiffForHumans($date,
                        CarbonInterface::DIFF_ABSOLUTE)
                    . ' (' . str_replace(' 00:00', '',
                        $date->locale($GLOBALS['prefered_language'])->isoFormat('LLLL')) . ')';
                break;
            case ModuleStatus::OPEN:
                $msg = _t('LMS_OPEN_MODULE');
                if (!empty($date)) {
                    $msg .= ' ' . _t('LMS_SINCE')
                        . ' ' . Carbon::now()->locale($GLOBALS['prefered_language'])->DiffForHumans(
                            $date,
                            CarbonInterface::DIFF_ABSOLUTE
                        )
                        . ' (' . str_replace(
                            ' 00:00',
                            '',
                            $date->locale($GLOBALS['prefered_language'])->isoFormat('LLLL')
                        ) . ')';
                }
                return $msg;
                break;
            case ModuleStatus::NOT_ACCESSIBLE:
                return _t('LMS_MODULE_NOT_ACCESSIBLE');
                break;
        }
    }
}
