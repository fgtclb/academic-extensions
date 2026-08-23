<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsDevSite\Tests\Functional\Support;

/**
 * Reads the rows of a committed `sqlite-databases/core-NN.sqlite` snapshot.
 *
 * Through PDO and not through the TYPO3 `ConnectionPool`: the snapshot is not
 * the database of the running installation, it is a file that happens to be
 * next to it, and registering a second connection in a functional test instance
 * to read it would put the test instance one configuration change away from
 * writing into the artifact it is checking.
 *
 * It is opened read only. The snapshot is a committed file and a check has no
 * business changing one - not even by leaving a `-wal` sidecar next to it.
 */
final class SqliteFileRowReader extends SeedRowReader
{
    private \PDO $connection;

    /** @var array<int, string>|null */
    private ?array $files = null;

    public function __construct(string $file)
    {
        if (!is_file($file)) {
            throw new \RuntimeException(
                sprintf('The snapshot "%s" does not exist.', $file),
                1787300201,
            );
        }

        $this->connection = new \PDO('sqlite:file:' . $file . '?mode=ro', null, null, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::SQLITE_ATTR_OPEN_FLAGS => \PDO::SQLITE_OPEN_READONLY,
        ]);
    }

    public function columnsOf(string $table): array
    {
        $statement = $this->connection->prepare('PRAGMA table_info(' . $this->quoteIdentifier($table) . ')');
        $statement->execute();

        $columns = [];
        foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $column) {
            $columns[] = strtolower((string)$column['name']);
        }

        return $columns;
    }

    public function rows(string $table, array $columns, ?array $uids): array
    {
        $select = array_values(array_filter(
            $columns,
            static fn(string $column): bool => $column !== SeedDefinition::REFERENCED_FILE,
        ));
        if ($table === 'sys_file_reference') {
            $select[] = 'uid_local';
        }

        $sql = sprintf(
            'SELECT %s FROM %s',
            implode(', ', array_map($this->quoteIdentifier(...), $select)),
            $this->quoteIdentifier($table),
        );
        if ($uids !== null) {
            // Written out rather than bound: the uid list comes from the seed
            // definition, is cast to int here, and SQLite has a bind parameter
            // limit a 242 element IN list would be uncomfortably close to.
            $sql .= ' WHERE uid IN (' . implode(',', array_map(intval(...), $uids)) . ')';
        }

        $statement = $this->connection->prepare($sql);
        $statement->execute();

        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

        return $rows;
    }

    public function fileIdentifiers(): array
    {
        if ($this->files !== null) {
            return $this->files;
        }

        $statement = $this->connection->prepare('SELECT uid, identifier FROM sys_file');
        $statement->execute();

        $files = [];
        foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $files[(int)$row['uid']] = (string)$row['identifier'];
        }

        return $this->files = $files;
    }

    /**
     * A table or column name of the manifest, quoted for SQLite.
     *
     * The names come from the seed definition and from the schema, so nothing
     * here is user input - but a `tt_content` column called `index` exists and
     * would be a syntax error unquoted.
     */
    private function quoteIdentifier(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }
}
