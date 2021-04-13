<?php


namespace YesWiki\Lms\Service;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonInterval;
use Exception;
use YesWiki\Wiki;

class DateManager
{
    public const TIME_FORMAT_WITH_COLONS = '%H:%I:%S';
    public const TIME_FORMAT_WITH_COLONS_FOR_IMPORT = 'H:i:s';
    public const DATETIME_FORMAT = 'Y-m-d H:i:s';
    public const LONG_DATE_ISOFORMAT = 'LL';
    public const LONG_DATETIME_ISOFORMAT = 'LLLL';
    protected $config;

    /**
     * DateManager constructor
     * @param Wiki $wiki the injected Wiki instance
     */
    public function __construct(Wiki $wiki)
    {
        $this->config = $wiki->config;
    }

    public function createIntervalFromMinutes(int $minutes): CarbonInterval
    {
        return CarbonInterval::minutes($minutes)->cascade();
    }

    public function createIntervalFromString(string $durationString): ?CarbonInterval
    {
        try {
            return CarbonInterval::createFromFormat(
                self::TIME_FORMAT_WITH_COLONS_FOR_IMPORT,
                $durationString
            )->cascade();
        } catch (Exception $e) {
            //error_log("Error by parsing the interval. The format '00:00:00' is expected but '$durationString' is given");
            return null;
        }
    }

    public function createDatetimeFromString(string $dateStr): ?Carbon
    {
        $date = Carbon::createFromFormat(self::DATETIME_FORMAT, $dateStr);
        // TODO manage the timezone
        if (!$date) {
            //error_log("Error by parsing the date. The format 'Y-m-d H:i:s' is expected but '$dateStr' is given");
            return null;
        }
        $date->locale($GLOBALS['prefered_language']);
        return $date;
    }

    public function formatTimeWithColons(CarbonInterval $duration): string
    {
        // create new CarbonInterval to set total hours without cascade
        // difference between 2021-01-01 00:00:00 and 2021-01-02 01:05:00 should give 25:05:00
        // whereas $duration->format(self::TIME_FORMAT_WITH_COLONS) gives 01:05:00
        return CarbonInterval::hours($duration->totalHours)
            ->minutes($duration->minutes)
            ->seconds($duration->seconds)
            ->format(self::TIME_FORMAT_WITH_COLONS);
    }

    public function formatDatetime(Carbon $date): string
    {
        return $date->locale($GLOBALS['prefered_language'])->format(self::DATETIME_FORMAT);
    }

    public function formatLongDate(Carbon $date): string
    {
        return $date->locale($GLOBALS['prefered_language'])->isoFormat(self::LONG_DATE_ISOFORMAT);
    }

    public function formatLongDatetime(Carbon $date): string
    {
        return $date->locale($GLOBALS['prefered_language'])->isoFormat(self::LONG_DATETIME_ISOFORMAT);
    }

    public function diffToNowInReadableFormat(Carbon $date): string
    {
        return $this->diffDatesInReadableFormat($date, Carbon::now());
    }

    public function diffDatesInReadableFormat(Carbon $fromDate, Carbon $toDate): string
    {
        return $toDate->locale($GLOBALS['prefered_language'])->DiffForHumans(
            $fromDate,
            CarbonInterface::DIFF_ABSOLUTE
        );
    }
}
