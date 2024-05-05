<?php

declare(strict_types=1);

namespace App\Repository;

/**
 * Repository for managing the 'opening_hours' table.
 */
final class OpeningHoursRepository extends BaseRepository
{
    /**
     * Get the name of the table associated with the repository.
     *
     * @return string Table name
     */
    public static function getTableName(): string
    {
        return 'opening_hours';
    }
}
