<?php


namespace YesWiki\Lms\Service;

use Carbon\Carbon;
use YesWiki\Core\Service\TripleStore;
use YesWiki\Wiki;
use YesWiki\Lms\ExtraActivityLog ;
use YesWiki\Lms\ExtraActivityLogs ;
use YesWiki\Lms\Learner ;
use YesWiki\Lms\Module ;
use YesWiki\Lms\Course ;
use YesWiki\Lms\Service\CourseManager;
use YesWiki\Lms\Service\DateManager;

class ExtraActivityManager
{
    protected const LMS_TRIPLE_PROPERTY_NAME_EXTRA_ACTIVITY =  'https://yeswiki.net/vocabulary/lms-extra-activity' ;

    protected $tripleStore;
    protected $wiki;
    protected $courseManager ;
    protected $dateManager ;

    /**
     * LearnerManager constructor
     *
     * @param TripleStore $tripleStore the injected TripleStore instance
     * @param CourseManager $courseManager the injected CourseManager instance
     * @param DateManager $dateManager the injected CourseManager instance
     * @param Wiki $wiki
     */
    public function __construct(
        TripleStore $tripleStore,
        CourseManager $courseManager,
        DateManager $dateManager,
        Wiki $wiki
    ) {
        $this->tripleStore = $tripleStore;
        $this->courseManager = $courseManager;
        $this->dateManager = $dateManager;
        $this->wiki = $wiki;
    }

    // TODO : cut in smaller functions
    /**
     * Save a Extra-activity
     * @param array $data $_POST data to save
     * @return bool false if error
     */
    public function saveExtraActivity(array $data): bool
    {
        // === START validate data ====
        if (empty($data['title'])
                || empty($data['bf_date_debut_evenement'])
                || empty($data['bf_date_fin_evenement'])
                || empty($data['course'])
                || empty($data['registeredLearnerNames'])) {
            $output = 'Errors in '. get_class($this) . ' :<br>' ;
            $output .= (empty($data['title'])) ? 'empty($data[\'title\'])<br>' : '' ;
            $output .= (empty($data['bf_date_debut_evenement'])) ? 'empty($data[\'bf_date_debut_evenement\'])<br>' : '' ;
            $output .= (empty($data['bf_date_fin_evenement'])) ? 'empty($data[\'bf_date_fin_evenement\'])<br>' : '' ;
            $output .= (empty($data['course'])) ? 'empty($data[\'course\'])<br>' : '' ;
            $output .= (!isset($data['registeredLearnerNames'])) ? 'not isset($data[\'registeredLearnerNames\'])<br>' : '' ;
            $output .= (isset($data['registeredLearnerNames']) && count($data['registeredLearnerNames']) == 0) ? 'count($data[\'registeredLearnerNames\']) == 0<br>' : '' ;
            throw new \Exception($output);
        }
        // === END validate data ====

        // === START check tag ====
        if (!empty($data['tag'])) {
            $extraActivityLogs = $this->getExtraActivityLogsFromLike('%"tag":"' . $data['tag'] . '"%');
            if (!$extraActivityLogs->has($data['tag'])) {
                throw new \Exception('Errors in '. get_class($this) . ' : $data[\'tag\'] defined but not existing in triples' .'<br>');
            }
        } else {
            // define new Tag
            $tmptag = genere_nom_wiki($data['title'], 1);
        
            // check if tag is a pageName or already exists for this course
            // get all extra-activities
            $extraActivityLogs = $this->getExtraActivityLogsFromLike('');

            $i = 1 ;
            do {
                $tag = genere_nom_wiki($tmptag, $i);
                ++$i;
            } while ($i < 1000 && $extraActivityLogs->has($tag)) ;
            if ($extraActivityLogs->has($tag)) {
                throw new \Exception('Errors in '. get_class($this) . ' : genere_nom_wiki does not work' .'<br>');
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
            $endDate->add(1, 'days') ;
        }
        $elapsedTime = $date->diffAsCarbonInterval($endDate, false) ;
        $elapsedTime->invert = false; // to have only positive without warning
        $extraActivityLog = new ExtraActivityLog(
            $this->dateManager,
            $data['tag'],
            $data['title'],
            $data['relatedLink'] ?? '',
            $date,
            $elapsedTime,
            $course,
            $module
        );
        // === END format data ====

        // === START remove previous data if existing ====
        if ($this->getExtraActivityLog($data['tag']) && !$this->deleteExtraActivity($data['tag'])) {
            throw new \Exception('Errors in '. get_class($this) . ' when deleting '.$data['tag'].'<br>');
        }
        // === END remove previous data ====
    
        // === START save new data ====
        $errorMessage = '' ;
        foreach ($data['registeredLearnerNames'] as $learnerName => $value) {
            if ($value == 1 && (($this->tripleStore->create(
                $learnerName,
                self::LMS_TRIPLE_PROPERTY_NAME_EXTRA_ACTIVITY,
                json_encode($extraActivityLog),
                '',
                ''
            ) > 0))) {// create
                $errorMessage .= 'Errors in '. get_class($this) . ' when creating '.$data['tag'].' for '.$learnerName .'<br>';
            }
        }
        if (!empty($errorMessage)) {
            throw new \Exception($errorMessage);
        }
        
        // === END save new data ====
        return true;
    }

    private function getDateFromData(string $prefix, array $data): ?Carbon
    {
        $date = $data[$prefix];
        if (isset($data[$prefix.'_allday']) && $data[$prefix.'_allday'] == 0) {
            $date .= ' ' . ($data[$prefix.'_hour'] ?? '00'). ':' ;
            $date .=  ($data[$prefix.'_minutes'] ?? '00'). ':00' ;
        }
        return new Carbon($date) ;
    }

    /**
     * Get the Extra-activities of a courseStructure
     * @param Course @course
     * @param Module @module (optional)
     * @param Learner @learner (optional)
     * @return ExtraActivityLogs the courseStructure's extraActivityLogs
     */
    public function getExtraActivityLogs(Course $course, Module $module = null, Learner $learner = null): ExtraActivityLogs
    {
        $like = '%"course":"' . $course->getTag() . '"';
        if (!is_null($module)) {
            $like .= '%"module":"' . $module->getTag() . '"%';
        } else {
            $like .= '}%';
        }
        return $this->getExtraActivityLogsFromLike($like, ($learner)?$learner->getUsername():'') ;
    }

    /**
     * Get a Extra-activity from tag
     * @param string $tag tag of the ExtraActivityLog
     * @return ExtraActivityLog|null return null if empty tag
     */
    public function getExtraActivityLog(string $tag): ?ExtraActivityLog
    {
        if (empty($tag)) {
            return null;
        }
        $like = '%"tag":"' . $tag . '"%';
        $extraActivityLogs = $this->getExtraActivityLogsFromLike($like) ;
        return $extraActivityLogs->get($tag) ;
    }

    /**
     * Get extra-activitylogs from like query
     * @param string $like query to send to TripleStore->getMatching
     * @param string $learnerName
     * @return ExtraActivityLogs the courseStructure's extraActivityLogs
     */
    private function getExtraActivityLogsFromLike(string $like, string $learnerName = ''):ExtraActivityLogs
    {
        $results = $this->tripleStore->getMatching(
            (empty($learnerName) ? null : $learnerName),
            self::LMS_TRIPLE_PROPERTY_NAME_EXTRA_ACTIVITY,
            $like,
            (empty($learnerName) ? 'LIKE' : '='),
            '=',
            'LIKE'
        );

        $extraActivityLogs = new ExtraActivityLogs() ;
        if ($results) {
            foreach ($results as $result) {
                $extraActivityLog = ExtraActivityLog::createFromJSON(
                    $result['value'],
                    $this->courseManager,
                    $this->dateManager
                );
                if ($extraActivityLog) {
                    if (!$extraActivityLogs->add($extraActivityLog)) {
                        // already present
                        $extraActivityLog = $extraActivityLogs->get($extraActivityLog->getTag());
                    };
                    $extraActivityLog->addLearnerName($result['resource']) ;
                }
            }
        }
        return $extraActivityLogs ;
    }

    
    /**
     * delete the Extra-activities of a courseStructure
     * @param string $tag tag of the ExtraActivityLog
     * @param string $learnerName (optional), if not present, delete for all learners
     * @return bool false in cas of error
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
            throw new \Exception('Errors in '. get_class($this) . ' : not possible to delete tag : "'.$tag.'" because not existing <br>');
        }

        $errorMessage = '';
        foreach ($results as $result) {
            if ($this->tripleStore->delete(
                $result['resource'],
                self::LMS_TRIPLE_PROPERTY_NAME_EXTRA_ACTIVITY,
                $result['value'],
                '',
                ''
            ) != 0) {
                // error but continue deleting others before throwing error
                $errorMessage .= 'Errors in '. get_class($this) . ' : error when deleting tag : "'.$tag.'" in TripleStore<br>'  ;
            };
        }
        if (!empty($errorMessage)) {
            throw new \Exception($errorMessage);
        }
        return true ;
    }
}
