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
use YesWiki\Wiki;

class CourseController extends YesWikiController
{
    protected $entryManager;
    protected $courseManager;
    protected $config;
    protected $wiki;

    /**
     * CourseController constructor
     * @param EntryManager $entryManager the injected EntryManager instance
     * @param CourseManager $courseManager the injected CourseManager instancz
     * @param Wiki $wiki the injected Wiki instance
     */
    public function __construct(EntryManager $entryManager, CourseManager $courseManager, ParameterBagInterface $config, Wiki $wiki)
    {
        $this->entryManager = $entryManager;
        $this->courseManager = $courseManager;
        $this->config = $config;
        $this->wiki = $wiki;
    }

    /**
     * Get the contextual course according to the Get parameter 'parcours' and the existing course. By order :
     *
     *   - if the Get parameter 'parcours' refers to a tag associated to a parcours entry, return it
     *   - if not, return null
     *   - if there is at least one course in the database, return the first created one
     *   - if not, return null
     *
     * @return Course|null the course entry or null if not found
     */
    public function getContextualCourse(): ?Course
    {
        $courseTag = empty($_GET['parcours']) ? '' : $_GET['parcours'];
        if (!empty($courseTag)) {
            return $this->courseManager->getCourse($courseTag);
        } else {
            $courses = $this->courseManager->getAllCourses();
            return !empty($courses) ?
                $courses[array_key_first($courses)]
                : null;
        }
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
     * @param $course Course the given course entry
     * @return Module|null the module entry or null if not found
     */
    public function getContextualModule(?Course $course): ?Module
    {
        // if an handler is after the page tag in the wiki parameter variable, get only the tag
        $currentPageTag =  isset($_GET['wiki']) ?
            strpos($_GET['wiki'], '/') ?
                substr($_GET['wiki'], 0, strpos($_GET['wiki'], '/'))
                : $_GET['wiki']
            : null;

        if (!empty($currentPageTag)) {
            if ($this->wiki->config['lms_config']['use_tabs']) {
                // if a number is at the end of the page tag, it means that it's a tab page corresponding to the page without the number
                // to associate this tab page to its parent one, we remove the number from the page tag
                $currentPageTag = preg_replace('/[0-9]*$/', '', $currentPageTag);
            }
            $moduleTag = isset($_GET['module']) ? $_GET['module'] : null;

            if ($moduleTag) {
                // if the module is specified in the GET parameter, return it if the tag corresponds
                $module = $this->courseManager->getModule($moduleTag);

                return ($module && $module->hasActivity($currentPageTag)) ?
                    $module
                    : null;
            } else {
                // if the current page refers to a module of the course, return it
                $currentModule = $this->courseManager->getModule($currentPageTag);
                if ($currentModule && $course->hasModule($currentPageTag)) {
                    return $currentModule;
                }

                // find in the course modules, the first module which contains the activity
                if ($course) {
                    foreach ($course->getModules() as $currentModule) {
                        if ($currentModule->hasActivity($currentPageTag)) {
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
    public function getParentTabActivity(Activity $activity) : Activity
    {
        if ($this->config->get('lms_config')['use_tabs']){
            $parentActivityTag = preg_replace('/[0-9]*$/', '', $activity->getTag());
            $parentActivity = $this->courseManager->getActivity($parentActivityTag);
            return $parentActivity ? $parentActivity : $activity;
        } else {
            return $activity;
        }
    }

    /**
     * Display the given module as an information card
     *
     * @param Module $module the module to display
     * @return string the generated output
     */
    public function renderModuleCard(Module $module): string
    {
        $title = $module->getField('bf_titre');
        $escapedTitle = htmlspecialchars($title);
        $description = empty($module->getField('bf_description')) ?
            '' :
            '<div class="description-module">' . $this->wiki->format($module->getField('bf_description')) . '</div>';
        $largeur = '400';
        // TODO $hauteur must fit the image ratio + vertical centered when the text is higher
        $hauteur = '300';
        $image = empty($module->getField('imagebf_image')) || !is_file('files/' . $module->getField('imagebf_image')) ?
            '' :
            redimensionner_image(
                'files/' . $module->getField('imagebf_image'),
                'cache/' . $largeur . 'x' . $hauteur . '-' . $module->getField('imagebf_image'),
                $largeur,
                $hauteur,
                'crop'
            );

        // the consulted course entry
        $course = $this->getContextualCourse();
        // TODO implement getNextActivity for a learner, for the moment choose the first activity of the module
        $activityTag = $module->getFirstActivityTag();
        $link = $this->wiki->href(
            '',
            $activityTag,
            ['parcours' => $course->getTag(), 'module' => $module->getTag()]
        );
        $status = $module->getStatus($course);
        $date = empty($module->getField('bf_date_ouverture')) ? '' : Carbon::parse($module->getField('bf_date_ouverture'));
        switch ($status) {
            case ModuleStatus::UNKNOWN:
                $active = _t('LMS_UNKNOWN_STATUS_MODULE');
                break;
            case ModuleStatus::CLOSED:
                $active = _t('LMS_CLOSED_MODULE');
                break;
            case ModuleStatus::TO_BE_OPEN:
                $active = _t('LMS_MODULE_WILL_OPEN') . ' '
                    . Carbon::now()->locale($GLOBALS['prefered_language'])->DiffForHumans($date,
                        CarbonInterface::DIFF_ABSOLUTE)
                    . ' (' . str_replace(' 00:00', '',
                        $date->locale($GLOBALS['prefered_language'])->isoFormat('LLLL')) . ')';
                break;
            case ModuleStatus::OPEN:
                $active = _t('LMS_OPEN_MODULE');
                if (!empty($date)) {
                    $active .= ' ' . _t('LMS_SINCE')
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
                break;
            case ModuleStatus::NOT_ACCESSIBLE:
                $active = _t('LMS_MODULE_NOT_ACCESSIBLE');
                break;
        }
        $nbActivities = count($module->getActivities());
        $duration = $module->getDuration();
        $adminLinks = $this->wiki->UserIsAdmin() ?
            '<a href="' . $this->wiki->href(
                'edit',
                $module->getTag()
            ) . '" class="btn btn-default btn-xs"><i class="fa fa-pencil-alt"></i> ' . _t('BAZ_MODIFIER') . '</a>
           <a href="' . $this->wiki->href(
                    'deletepage',
                    $module->getTag()
                ) . '" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i></a>' :
            '';
        $labelActivity = _t('LMS_ACTIVITY') . (($nbActivities > 1) ? 's' : '');
        $labelTime = _t('LMS_TIME');
        $classLink = !$this->wiki->UserIsAdmin() && in_array(
            $status,
            ['closed', 'to_be_open', 'not_accessible', 'unknown']
        ) ? 'disabled' : ' ';
        $labelStart = _t('LMS_BEGIN');
        // TODO use twig template
        return "<div class=\"module-card status-$status\">
    <div class=\"module-image\">
      <a class=\"launch-module $classLink\" href=\"$link\">
        " . (!empty($image) ? "<img src=\"$image\" alt=\"Image du module $escapedTitle\" />" : '') . "
      </a>
    </div>
    <div class=\"module-content\">
      <h3 class=\"module-title\"><a class=\"launch-module $classLink\" href=\"$link\">$title</a></h3>
      $description
      <small class=\"module-date\"><em>$active.</em></small>
    </div>
    <div class=\"module-activities\">
      <div class=\"activities-infos\">
          <div class=\"activities-numbers\"><strong><i class=\"fas fa-chalkboard-teacher fa-fw\"></i> $labelActivity</strong> : $nbActivities</div>
          <div class=\"activities-duration\"><strong><i class=\"fas fa-hourglass-half fa-fw\"></i> $labelTime</strong> : {$duration}</div>
      </div>
      <div class=\"activities-action\">
        <a href=\"$link\" class=\"btn btn-primary btn-block launch-module $classLink\"><i class=\"fas fa-play fa-fw\"></i> $labelStart</a>
        $adminLinks
      </div>
    </div>
  </div>
";
    }
}
