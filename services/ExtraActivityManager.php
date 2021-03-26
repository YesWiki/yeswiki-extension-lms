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
        $debug = ($this->wiki->GetConfigValue('debug')=='yes');
        // === START validate data ====
        $error = (empty($data['title'])
            || empty($data['bf_date_debut_evenement'])
            || empty($data['bf_date_fin_evenement'])
            || empty($data['course'])
            || !isset($data['registeredLearnerNames'])
            || count($data['registeredLearnerNames']) == 0) ;
        if ($error) {
            if ($debug) {
                $output = 'Errors in '. get_class($this) . ' :<br>' ;
                $output .= (empty($data['title'])) ? 'empty($data[\'title\'])<br>' : '' ;
                $output .= (empty($data['bf_date_debut_evenement'])) ? 'empty($data[\'bf_date_debut_evenement\'])<br>' : '' ;
                $output .= (empty($data['bf_date_fin_evenement'])) ? 'empty($data[\'bf_date_fin_evenement\'])<br>' : '' ;
                $output .= (empty($data['course'])) ? 'empty($data[\'course\'])<br>' : '' ;
                $output .= (!isset($data['registeredLearnerNames'])) ? 'not isset($data[\'registeredLearnerNames\'])<br>' : '' ;
                $output .= (isset($data['registeredLearnerNames']) && count($data['registeredLearnerNames']) == 0) ? 'count($data[\'registeredLearnerNames\']) == 0<br>' : '' ;
                echo $output;
            }
            return false ;
        }
        // === END validate data ====

        // === START check tag ====
        if (!empty($data['tag'])) {
            $extraActivities = $this->getExtraActivityLogsFromLike('%"tag":"' . $data['tag'] . '"%');
            if (!$extraActivities->has($data['tag'])) {
                if ($debug) {
                    echo 'Errors in '. get_class($this) . ' : $data[\'tag\'] defined but not existing in triples' .'<br>' ;
                }
                return false;
            } else {
                $oldExtraActivity = $extraActivities->get($data['tag']);
            }
        } else {
            // define new Tag
            $tmptag = genere_nom_wiki($data['title'], 1);
        
            // check if tag is a pageName or already exists for this course
            // get all extra-activities
            $extraActivities = $this->getExtraActivityLogsFromLike('');

            $i = 1 ;
            do {
                $tag = genere_nom_wiki($tmptag, $i);
                ++$i;
            } while ($i < 1000 && $extraActivities->has($tag)) ;
            if ($extraActivities->has($tag)) {
                if ($debug) {
                    echo 'Errors in '. get_class($this) . ' : genere_nom_wiki does not work' .'<br>' ;
                }
                return false;
            } else {
                $data['tag'] = $tag ;
            }
        }
        // === END check tag ====

        // === START format data ====
        $course = $this->courseManager->getCourse($data['course']);
        $module = !empty($data['module']) ? $this->courseManager->getModule($data['module']) : null;

        // Date
        $date=$this->getDateFromData('bf_date_debut_evenement', $data);
        $endDate=$this->getDateFromData('bf_date_fin_evenement', $data);
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
        // === END format data ====
    
        foreach ($data['registeredLearnerNames'] as $learnerName => $value) {
            if ($value == 1) {
                $previousTripples = $this->getTripplesForLearner($learnerName, $data['tag']);
                if (!empty($previousTripples)) {
                    $first = true;
                    foreach ($previousTripples as $result) {
                        if ($first) {
                            $first = false;
                            if (!in_array($this->tripleStore->update(
                                $learnerName,
                                self::LMS_TRIPLE_PROPERTY_NAME_EXTRA_ACTIVITY,
                                $result['value'],
                                json_encode($extraActivity),
                                '',
                                ''
                            ), [0,3])) {// update
                                if ($debug) {
                                    echo 'Errors in '. get_class($this) . ' when updating '.$data['tag'].' for '.$learnerName .'<br>' ;
                                }
                                $error = true;
                            }
                        } else {
                            // clean data
                            $this->tripleStore->delete(
                                $learnerName,
                                self::LMS_TRIPLE_PROPERTY_NAME_EXTRA_ACTIVITY,
                                $result['value'],
                                '',
                                ''
                            );
                        }
                    }
                } elseif ($this->tripleStore->create(
                    $learnerName,
                    self::LMS_TRIPLE_PROPERTY_NAME_EXTRA_ACTIVITY,
                    json_encode($extraActivity),
                    '',
                    ''
                ) > 0) {// create
                    if ($debug) {
                        echo 'Errors in '. get_class($this) . ' when creating '.$data['tag'].' for '.$learnerName .'<br>';
                    }
                    $error = true;
                }
                if (isset($oldExtraActivity)) {
                    $oldExtraActivity->removeLearnerName($learnerName);
                }
            }
        }
        // remove old
        if (isset($oldExtraActivity)) {
            foreach ($oldExtraActivity->getRegisteredLearnerNames() as $learnerName) {
                if (!$this->deleteExtraActivity($data['tag'], $learnerName)) {
                    if ($debug) {
                        echo 'Errors in '. get_class($this) . ' when deleting '.$data['tag'].' for '.$learnerName .'<br>';
                    }
                    $error = true;
                }
            }
        }
        return !$error;
    }

    private function getDateFromData(string $prefix, array $data): ?\DateTime
    {
        $date = $data[$prefix];
        if (isset($data[$prefix.'_allday']) && $data[$prefix.'_allday'] == 0) {
            $date .= ' ' . ($data[$prefix.'_hour'] ?? '00'). ':' ;
            $date .=  ($data[$prefix.'_minutes'] ?? '00'). ':00' ;
        }
        return new \DateTime($date) ;
    }

    private function getTripplesForLearner(string $learnerName, string $tag)
    {
        $like = '%"tag":"' . $tag . '"%';
        return $this->tripleStore->getMatching(
            $learnerName,
            self::LMS_TRIPLE_PROPERTY_NAME_EXTRA_ACTIVITY,
            $like,
            '=',
            '=',
            'LIKE'
        );
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
    public function deleteExtraActivity(string $tag, string $learnerName = ''): bool
    {
        $like = '%"tag":"' . $tag . '"%';
        $results = $this->tripleStore->getMatching(
            (empty($learnerName) ? null : $learnerName),
            self::LMS_TRIPLE_PROPERTY_NAME_EXTRA_ACTIVITY,
            $like,
            (empty($learnerName) ? 'LIKE' : '='),
            '=',
            'LIKE'
        );

        if (!$results) {
            if ($debug) {
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
                if ($debug) {
                    echo 'Errors in '. get_class($this) . ' : error when deleting tag : "'.$tag.'" in TripleStroe'  ;
                }
            };
        }
        return $log ;
    }
}
