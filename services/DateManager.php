<?php


namespace YesWiki\Lms\Service;


use Carbon\Carbon;
use Carbon\CarbonInterval;
use Carbon\Exceptions\ParseErrorException;
use YesWiki\Wiki;
use \Exception;

class DateManager
{
    protected $config;

    /**
     * DateManager constructor
     * @param Wiki $wiki the injected Wiki instance
     */
    public function __construct(Wiki $wiki)
    {
        $this->config = $wiki->config;
    }

    public function createDateFromString(string $dateStr): ?Carbon
    {
        $date = Carbon::createFromFormat('Y-m-d H:i:s', $dateStr);
        // TODO manage the timezone
        if (!$date){
            //error_log("Error by parsing the date. The format 'Y-m-d H:i:s' is expected but '$dateStr' is given");
            return null;
        }
        $date->locale($GLOBALS['prefered_language']);
        return $date;
    }

    public function createIntervalFromMinutes(int $minutes): CarbonInterval
    {
        return CarbonInterval::minutes($minutes)->cascade();
    }

    public function createIntervalFromString(string $durationString): ?CarbonInterval
    {
        try {
            return CarbonInterval::createFromFormat('H:i:s', $durationString);
        } catch (Exception $e) {
            //error_log("Error by parsing the interval. The format '00:00:00' is expected but '$durationString' is given");
            return null;
        }
    }

    public function formatDateWithColons(CarbonInterval $duration): string
    {
        return $duration->format('%H:%I:%S');
    }
}