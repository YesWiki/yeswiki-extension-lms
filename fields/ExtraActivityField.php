<?php

namespace YesWiki\Lms\Field;

use Psr\Container\ContainerInterface;
use YesWiki\Core\Service\UserManager;
use YesWiki\Bazar\Field\BazarField;
use YesWiki\Lms\Course;
use YesWiki\Lms\Module;
use YesWiki\Lms\Controller\CourseController;
use YesWiki\Lms\Service\CourseManager;
use YesWiki\Lms\Service\LearnerManager;
use YesWiki\Lms\Service\ExtraActivityManager;

/**
 * @Field({"extraactivity"})
 */
class ExtraActivityField extends BazarField
{
    protected $courseManager ;
    protected $extraActivityManager ;
    protected $userManager ;
    protected $courseController ;

    public function __construct(array $values, ContainerInterface $services)
    {
        parent::__construct($values, $services);

        
        $this->label = null;
        $this->default = [];

        //  load managers
        $this->courseManager = $services->get(CourseManager::class);
        $this->learnerManager = $services->get(LearnerManager::class);
        $this->extraActivityManager = $services->get(ExtraActivityManager::class);
        $this->userManager = $services->get(UserManager::class);
        $this->courseController = $services->get(CourseController::class);
    }

    // Render the show view of the field
    protected function renderStatic($entry)
    {
        return null ;// $this->render("@lms/fields/extra-activity.twig", []);
    }

    protected function renderInput($entry)
    {
        $courseTag = $_GET['course'] ?? null;

        $value = $this->getValue($entry);
        if (!empty($value)) {
            if ($extraActivityLog = $this->extraActivityManager->getExtraActivityLog($value)) {
                $courseTag = $extraActivityLog->getCourse()->getTag();
                $module = $extraActivityLog->getModule() ;
            }
        }

        $learners = [];
        if (!empty($courseTag)) {
            if ($course = $this->courseController->getContextualCourse()) {
                // the progresses we are going to process
                $progresses = $this->learnerManager->getProgressesForAllLearners($course);
                // the learners for this course, we count all users which have already a progress
                foreach ($progresses->getAllUsernames() as $user) {
                    if ($learner = $this->learnerManager->getLearner($user)) {
                        $learners[$learner->getUsername()] = $learner->getFullname();
                    }
                }
                $modules = [];
                foreach ($course->getModules() as $moduleFromCourse) {
                    $modules[$moduleFromCourse->getTag()] = $moduleFromCourse->getTitle();
                }
            }
        } else {
            $courses = array_map(
                function ($course) {
                    return $course->getTitle();
                },
                $this->courseManager->getAllCourses()
            );
            foreach ($this->userManager->getAll() as $user) {
                if ($learner = $this->learnerManager->getLearner($user['name'])) {
                    $learners[$learner->getUsername()] = $learner->getFullname();
                }
            }
        }
        return $this->render("@lms/inputs/extra-activity.twig", [
            'courses' => $courses ?? null,
            'course' => $course ?? null,
            'modules' => $modules ?? null,
            'module' => isset($module) ? $module->getTag() : null,
            'learners' => $learners,
            'extraActivityLog' => $extraActivityLog ?? null,
        ]);
    }

    // Format input values before save
    public function formatValuesBeforeSave($entry)
    {
        if ($this->canEdit($entry)) {
            $id = $this->getPropertyName();
            // extract data
            $data = [];
            $data['title'] = $entry['bf_titre'] ?? null;
            $data['relatedLink'] = $entry['id_fiche'] ?? null;
            foreach (['bf_date_debut_evenement','bf_date_debut_evenement_allday','bf_date_debut_evenement_hour','bf_date_debut_evenement_minutes',
                'bf_date_fin_evenement','bf_date_fin_evenement_allday','bf_date_fin_evenement_hour','bf_date_fin_evenement_minutes',
                'course','module','registeredLearnerNames','tag'] as $key) {
                $data[$key] =  $entry[$id.'_'.$key]  ?? null;
            }
            
            if ($this->extraActivityManager->saveExtraActivity($data)) {
                $value = $this->getExtraActivityTagFromrelatedLink($data['course'], $data['module'], $data['relatedLink']);
            }
        } else {
            $value = $this->getValue($entry);
        }
        return ((isset($value)) ? [$this->getPropertyName() => $value]:[])
            + ['fields-to-remove' => [$this->getPropertyName()]];
    }

    /**
     * get tag from related link
     * @param string $courseTag
     * @param string|null $moduleTag
     * @param string $tag of the related entry, saved in the relatedLink
     * @return string|null tag of the extra-activity
     */
    private function getExtraActivityTagFromrelatedLink(string $courseTag, string $moduleTag = null, string $tag): ?string
    {
        if ($course = $this->courseManager->getCourse($courseTag)) {
            $module = ($moduleTag) ? $course->getModule($moduleTag) : null;
            foreach ($this->extraActivityManager->getExtraActivityLogs($course, $module) as $extraActivityLog) {
                if ($extraActivityLog->getRelatedLink() == $tag) {
                    $extraActivityTag = $extraActivityLog->getTag();
                }
            }
        }
        
        return $extraActivityTag ?? null;
    }
}
