<?php


namespace YesWiki\Lms\Service;

use YesWiki\Core\Service\TripleStore;
use YesWiki\Wiki;
use YesWiki\Lms\ExtraActivityLog ;
use YesWiki\Lms\ExtraActivityLogs ;
use YesWiki\Lms\Module ;
use YesWiki\Lms\Course ;
use YesWiki\Lms\Service\CourseManager;

class ExtraActivityManager
{
    protected const LMS_TRIPLE_PROPERTY_NAME_EXTRA_ACTIVITY =  'https://yeswiki.net/vocabulary/lms-extra-activity' ;

    protected $tripleStore;
    protected $wiki;
    protected $courseManager ;

    /**
     * LearnerManager constructor
     *
     * @param TripleStore $tripleStore the injected TripleStore instance
     * @param CourseManager $courseManager the injected CourseManager instance
     * @param Wiki $wiki
     */
    public function __construct(
        TripleStore $tripleStore,
        CourseManager $courseManager,
        Wiki $wiki
    ) {
        $this->tripleStore = $tripleStore;
        $this->courseManager = $courseManager;
        $this->wiki = $wiki;
    }

    /**
     * Save a Extra-activity
     *
     * @return bool
     */
    public function saveExtraActivity(array $data): bool
    {
        if (!empty($data['title'])
                && !empty($data['bf_date_debut_evenement'])
                && !empty($data['bf_date_fin_evenement'])
                && !empty($data['course'])
                && isset($data['registeredLearnerNames'])
                && count($data['registeredLearnerNames']) > 0) {
            $course = $this->courseManager->getCourse($data['course']);
            $module = !empty($data['module']) ? $this->courseManager->getModule($data['module']) : null;
            // get all extra-activities
            $extraActivities = $this->getExtraActivityLogsFromLike('%"tag"%');
            if (empty($data['tag'])) {
                $i = 1 ;
                // check if tag is a pageName or already exists for this course
                $tmptag = genere_nom_wiki($data['title'], 1);
                do {
                    $tag = genere_nom_wiki($tmptag, $i);
                    ++$i;
                } while ($i < 1000 && $extraActivities->has($tag)) ;
                if ($i > (1000-1)) {
                    if ($this->wiki->GetConfigValue('debug')=='yes') {
                        echo 'Errors in '. get_class($this) . ' : genere_nom_wiki does not work' ;
                    }
                    return false;
                } else {
                    $data['tag'] = $tag ;
                }
            } elseif (!$extraActivities->has($data['tag'])) {
                if ($this->wiki->GetConfigValue('debug')=='yes') {
                    echo 'Errors in '. get_class($this) . ' : $data[\'tag\'] defined but not existing in triples' ;
                }
                return false;
            } elseif (!$this->deleteExtraActivity($extraActivities->get($data['tag']))) {
                if ($this->wiki->GetConfigValue('debug')=='yes') {
                    echo 'Errors in '. get_class($this) . ' : not possible to delete $data[\'tag\'] before update'  ;
                }
                return false;
            }
            $dateStr = $data['bf_date_debut_evenement'];
            if (isset($data['bf_date_debut_evenement_allday']) && $data['bf_date_debut_evenement_allday'] == 0) {
                $dateStr .= ' ' . ($data['bf_date_debut_evenement_hour'] ?? '00'). '-' ;
                $dateStr .= ' ' . ($data['bf_date_debut_evenement_minutes'] ?? '00'). '-00' ;
            }
            $date = new \DateTime($dateStr) ;
            $dateStr = $data['bf_date_fin_evenement'];
            if (isset($data['bf_date_fin_evenement_allday']) && $data['bf_date_fin_evenement_allday'] == 0) {
                $dateStr .= ' ' . ($data['bf_date_fin_evenement_hour'] ?? '00'). '-' ;
                $dateStr .= ' ' . ($data['bf_date_fin_evenement_minutes'] ?? '00'). '-00' ;
            }
            $endDate = new \DateTime($dateStr) ;
            if (isset($data['bf_date_fin_evenement_allday']) && $data['bf_date_fin_evenement_allday'] == 1) {
                $endDate->add(new \DateInterval('P1D')) ;
            }
            $elapsedTime = $date->diff($endDate) ;
            $extraActivity = new ExtraActivityLog(
                $data['tag'],
                $data['title'],
                $data['relatedLink'] ?? '',
                $date,
                $elapsedTime,
                $course,
                $module
            );
            foreach ($data['registeredLearnerNames'] as $learnerName => $value) {
                if ($value == 1) {
                    if (!$this->saveExtraActivityForLearner($learnerName, $extraActivity)) {
                        if ($this->wiki->GetConfigValue('debug')=='yes') {
                            echo 'Errors in '. get_class($this) . ' : $this->saveExtraActivityForLearner() does not work' ;
                        }
                        return false;
                    } ;
                }
            }
            return true;
        } elseif ($this->wiki->GetConfigValue('debug')=='yes') {
            $output = 'Errors in '. get_class($this) . ' :<br>' ;
            $output .= (empty($data['title'])) ? 'empty($data[\'title\'])<br>' : '' ;
            $output .= (empty($data['bf_date_debut_evenement'])) ? 'empty($data[\'bf_date_debut_evenement\'])<br>' : '' ;
            $output .= (empty($data['bf_date_fin_evenement'])) ? 'empty($data[\'bf_date_fin_evenement\'])<br>' : '' ;
            $output .= (empty($data['course'])) ? 'empty($data[\'course\'])<br>' : '' ;
            $output .= (!isset($data['registeredLearnerNames'])) ? 'not isset($data[\'registeredLearnerNames\'])<br>' : '' ;
            $output .= (isset($data['registeredLearnerNames']) && count($data['registeredLearnerNames']) == 0) ? 'count($data[\'registeredLearnerNames\']) == 0<br>' : '' ;
            echo $output;
        }
        return false;
    }

    private function saveExtraActivityForLearner(string $learnerName, ExtraActivityLog $extraActivityLog):bool
    {
        return ($this->tripleStore->create(
            $learnerName,
            self::LMS_TRIPLE_PROPERTY_NAME_EXTRA_ACTIVITY,
            json_encode($extraActivityLog),
            '',
            ''
        ) == 0);
    }

    /**
     * Get the Extra-activities of a courseStructure
     *
     * @return ExtraActivityLogs the courseStructure's extraActivities
     */
    public function getExtraActivities(Course $course, Module $module = null): ExtraActivityLogs
    {
        $like = '%"course":"' . $course->getTag() . '"';
        if (!is_null($module)) {
            $like .= '%"module":"' . $module->getTag() . '"%';
        } else {
            $like .= '}%';
        }
        return $this->getExtraActivityLogsFromLike($like) ;
    }

    /**
     * Get a Extra-activity from tag
     *
     * @return ExtraActivityLog
     */
    public function getExtraActivity(string $tag): ?ExtraActivityLog
    {
        if (empty($tag)) {
            return null;
        }
        $like = '%"tag":"' . $tag . '"%';
        $extraActivities = $this->getExtraActivityLogsFromLike($like) ;
        return $extraActivities->get($tag) ;
    }

    private function getExtraActivityLogsFromLike(string $like):ExtraActivityLogs
    {
        $results = $this->tripleStore->getMatching(
            null,
            self::LMS_TRIPLE_PROPERTY_NAME_EXTRA_ACTIVITY,
            $like,
            'LIKE',
            '=',
            'LIKE'
        );

        $extraActivities = new ExtraActivityLogs() ;
        if ($results) {
            foreach ($results as $result) {
                $extraActivity = ExtraActivityLog::createFromJSON(
                    $result['value'],
                    $this->courseManager
                );
                if ($extraActivity) {
                    if (!$extraActivities->add($extraActivity)) {
                        // already present
                        $extraActivity = $extraActivities->get($extraActivity->getTag());
                    };
                    $extraActivity->addLearnerName($result['resource']) ;
                }
            }
        }
        return $extraActivities ;
    }

    
    /**
     * delete the Extra-activities of a courseStructure
     *
     * @return bool
     */
    public function deleteExtraActivity(string $tag): bool
    {
        $like = '%"tag":"' . $tag . '"%';
        $results = $this->tripleStore->getMatching(
            null,
            self::LMS_TRIPLE_PROPERTY_NAME_EXTRA_ACTIVITY,
            $like,
            'LIKE',
            '=',
            'LIKE'
        );

        if (!$results) {
            if ($this->wiki->GetConfigValue('debug')=='yes') {
                echo 'Errors in '. get_class($this) . ' : not possible to delete tag : "'.$tag.'" because not existing'  ;
            }
            return false ;
        }

        $log = true ;
        foreach ($results as $result) {
            if ($this->tripleStore->delete(
                $result['resource'],
                self::LMS_TRIPLE_PROPERTY_NAME_EXTRA_ACTIVITY,
                $result['value'],
                '',
                ''
            ) != 0) {
                $log = false ;
                if ($this->wiki->GetConfigValue('debug')=='yes') {
                    echo 'Errors in '. get_class($this) . ' : error when deleting tag : "'.$tag.'" in TripleStroe'  ;
                }
            };
        }
        return $log ;
    }
}
