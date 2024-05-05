<?php

declare(strict_types=1);

namespace App\Repository;

use InvalidArgumentException;
use Nette\Database\Explorer;
use Nette\Database\Connection;

/**
 * Repository for managing the 'points_of_sale' table.
 */
final class PointOfSaleRepository extends BaseRepository
{
    public function __construct(
        private Connection $connection,
        Explorer $database,
    ) {
        parent::__construct($database);
    }

    /**
     * Get the name of the table associated with the repository.
     *
     * @return string Table name
     */
    public static function getTableName(): string
    {
        return 'points_of_sale';
    }

    /**
     * Verifies that the specified day and time are correct
     *
     * @param int|null $day Specified day
     * @param string|null $time Specified time
     */
    private function verifyDayAndTime(?int $day, ?string $time): void
    {
        if ($day !== null && ($day < 0 || $day > 6)) {
            throw new InvalidArgumentException("Invalid day of the week: $day");
        }

        if ($time !== null && !preg_match('/^(2[0-3]|[01]?[0-9]):([0-5][0-9])$/', $time)) {
            throw new InvalidArgumentException("Invalid time format: $time");
        }
    }

    /**
     * Retrieves data for further processing.
     *
     * @param int|null $day The day of the week as an integer (0 = Sunday, 6 = Saturday)
     * @param string|null $time Time in 'HH:mm' format
     * @return array An array of Points of Sale and Opening Hours rows
     */
    public function getRawData(?int $day = null, ?string $time = null): array
    {
        $this->verifyDayAndTime($day, $time);

        if ($day === null) {
            $day = date('w');
        }

        if ($time === null) {
            $time = date('H:i');
        }

        return $this->connection
            ->query(
                'SELECT pos.*, oh.day_from, oh.day_to, oh.open_time, oh.close_time FROM ? pos JOIN ? oh ON pos.id = oh.point_of_sale_id WHERE oh.day_from <= ? AND oh.day_to >= ? AND ? BETWEEN oh.open_time AND oh.close_time',
                Connection::literal(PointOfSaleRepository::getTableName()),
                Connection::literal(OpeningHoursRepository::getTableName()),
                $day,
                $day,
                $time
            )
            ->fetchAll();
    }

    /**
     * Retrieves all Points of Sale formatted along with their Opening Hours for the specified day and time.
     * Defaults to the current day and time if not specified.
     *
     * @param int|null $day The day of the week as an integer (0 = Sunday, 6 = Saturday)
     * @param string|null $time Time in 'HH:mm' format
     * @return array An array of Points of Sale with their detailed info and Opening Hours
     */
    public function getAllFormatted(?int $day = null, ?string $time = null): array
    {
        $results = [];
        foreach ($this->getRawData($day, $time) as $row) {
            // Add to results only if entry doesn't already exist
            if (!isset($results[$row['id']])) {
                $results[$row['id']] = [
                    'id' => $row['id'],
                    'type' => $row['type'],
                    'name' => $row['name'],
                    'address' => $row['address'],
                    'lat' => $row['lat'],
                    'lon' => $row['lon'],
                    'services' => $row['services'],
                    'payMethods' => $row['pay_methods'],
                    'link' => $row['link'],
                    'openingHours' => [],  // OpeningHours are added separately
                ];
            }

            // Add each set of opening hours to results
            $results[$row['id']]['openingHours'][] = [
                'from' => $row['day_from'],
                'to' => $row['day_to'],
                'hours' => $row['open_time']->format('%H:%I') . '-' . $row['close_time']->format('%H:%I'), // %H:%I is not a typo, we are calling DateInterval::format()
            ];
        }

        return $results;
    }
}
