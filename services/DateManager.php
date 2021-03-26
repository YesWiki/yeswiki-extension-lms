<?php


namespace YesWiki\Lms\Service;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Exception;
use YesWiki\Wiki;

class DateManager
{
    protected const DATE_FORMAT = 'Y-m-d H:i:s';
    protected const TIME_FORMAT_WITH_COLONS = '%H:%I:%S';
    protected const TIME_FORMAT_WITH_COLONS_FOR_IMPORT = 'H:i:s';
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
        $date = Carbon::createFromFormat(self::DATE_FORMAT, $dateStr);
        // TODO manage the timezone
        if (!$date) {
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
            return CarbonInterval::createFromFormat(self::TIME_FORMAT_WITH_COLONS_FOR_IMPORT, $durationString)->cascade();
        } catch (Exception $e) {
            //error_log("Error by parsing the interval. The format '00:00:00' is expected but '$durationString' is given");
            return null;
        }
    }

    public function formatTimeWithColons(CarbonInterval $duration): string
    {
        return $duration->format(self::TIME_FORMAT_WITH_COLONS);
    }

    public function formatDateWithWrittenMonth(Carbon $date): string
    {
        return $date->locale($GLOBALS['prefered_language'])->isoFormat('LLLL');
    }

    public function formatDateToString(Carbon $date = null): string
    {
        if (is_null($date)) {
            $date = Carbon::now();
        }
        return $date->locale($GLOBALS['prefered_language'])->format(self::DATE_FORMAT);
    }
}
