<?php


namespace YesWiki\Lms\Service;

// use YesWiki\Wiki;
use YesWiki\Lms\Activity;
use YesWiki\Lms\Course;
use YesWiki\Lms\Module;

class ActivityConditionsManager
{
    public const STATUS_LABEL = 'conditions_passed';
    public const URL_LABEL = 'url';
    public const MESSAGE_LABEL = 'message';

    // protected $wiki;

    /**
     * LearnerManager constructor
     *
     * @param Wiki $wiki the injected wiki instance
     */
    public function __construct(
        // Wiki $wiki,
    ) {
        // $this->wiki = $wiki;
    }

    /**
     * checkActivityConditions
     *
     * @param Course $course the concerned course
     * @param Module $module the concerned module
     * @param Activity $activity the concerned activity
     * @param mixed|null $value the value of conditions for the activity (if available)
     * @return [self::STATUS_LABEL => true|false,self::URL_LABEL => "https://...",self::MESSAGE_LABEL => <html for meesage>]
     */
    public function checkActivityConditions(
        ?Course $course,
        ?Module $module,
        ?Activity $activity,
        $value = null
    ): array {
        return [
                self::STATUS_LABEL => false,
                self::URL_LABEL => "",
                self::MESSAGE_LABEL => '<div>No Message</div>',
        ];
    }
}
