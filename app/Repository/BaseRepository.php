<?php

declare(strict_types=1);

namespace App\Repository;

use Nette\Database\Explorer;

/**
 * Abstract class providing common database operations for derived repository classes.
 */
abstract class BaseRepository
{
    public function __construct(
        protected Explorer $database,
    ) {
    }

    /**
     * Get the name of the table associated with the repository.
     *
     * @return string Table name
     */
    abstract public static function getTableName(): string;

    /**
     * Inserts data into the table associated with the repository.
     *
     * @param array $data Data to insert.
     */
    public function insertData(array $data): void
    {
        $this->database
            ->table($this->getTableName())
            ->insert($data);
    }

    /**
     * Clears all data from the table associated with the repository.
     */
    public function clearAllData(): void
    {
        $this->database
            ->table($this->getTableName())
            ->delete();
    }
}
