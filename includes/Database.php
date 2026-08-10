<?php
/**
 * Swap Design - Database Class (PDO Singleton Wrapper)
 *
 * Provides a clean OOP interface for database operations with
 * automatic prepared statement handling.
 *
 * Usage:
 *   $db = Database::getInstance();
 *   $user = $db->fetch("SELECT * FROM users WHERE id = ?", [$id]);
 *   $pages = $db->fetchAll("SELECT * FROM pages WHERE status = ?", ['published']);
 *   $newId = $db->insert('users', ['email' => '...', 'name' => '...']);
 *   $affected = $db->update('pages', ['title' => 'New'], 'id = ?', [$id]);
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;
    private ?CacheManager $cache = null;
    private bool $queryCacheEnabled = true;

    /**
     * Private constructor: use getInstance() instead.
     */
    private function __construct()
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }

    /**
     * Prevent cloning of the singleton.
     */
    private function __clone() {}

    /**
     * Get the singleton Database instance.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the raw PDO connection.
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    /**
     * Execute a query and return the PDOStatement.
     *
     * @param string $sql    SQL query with ? placeholders
     * @param array  $params Bound parameters
     * @return PDOStatement
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch a single row as an associative array.
     *
     * @param string $sql    SQL query
     * @param array  $params Bound parameters
     * @return array|null
     */
    public function fetch(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * Fetch all rows as an array of associative arrays.
     *
     * @param string $sql    SQL query
     * @param array  $params Bound parameters
     * @return array
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Fetch a single scalar value (first column of first row).
     *
     * @param string $sql    SQL query
     * @param array  $params Bound parameters
     * @param mixed  $default Default if no rows
     * @return mixed
     */
    public function fetchColumn(string $sql, array $params = [], mixed $default = null): mixed
    {
        $result = $this->query($sql, $params)->fetchColumn();
        return $result !== false ? $result : $default;
    }

    /**
     * Insert a row and return the last insert ID.
     *
     * @param string $table Table name
     * @param array  $data  Associative array of column => value
     * @return string       Last insert ID
     */
    public function insert(string $table, array $data): string
    {
        $columns      = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));

        return $this->pdo->lastInsertId();
    }

    /**
     * Update rows matching a WHERE clause.
     *
     * @param string $table      Table name
     * @param array  $data       Associative array of column => value to SET
     * @param string $where      WHERE clause with ? placeholders
     * @param array  $whereParams Parameters for WHERE clause
     * @return int               Number of affected rows
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $setClauses = [];
        foreach (array_keys($data) as $column) {
            $setClauses[] = "{$column} = ?";
        }
        $setString = implode(', ', $setClauses);

        $sql    = "UPDATE {$table} SET {$setString} WHERE {$where}";
        $params = array_merge(array_values($data), $whereParams);

        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Delete rows matching a WHERE clause.
     *
     * @param string $table      Table name
     * @param string $where      WHERE clause with ? placeholders
     * @param array  $whereParams Parameters for WHERE clause
     * @return int               Number of affected rows
     */
    public function delete(string $table, string $where, array $whereParams = []): int
    {
        $sql  = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $whereParams);
        return $stmt->rowCount();
    }

    /**
     * Begin a transaction.
     */
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    /**
     * Commit the current transaction.
     */
    public function commit(): void
    {
        $this->pdo->commit();
    }

    /**
     * Roll back the current transaction.
     */
    public function rollback(): void
    {
        $this->pdo->rollBack();
    }

    /**
     * Count rows matching a condition.
     *
     * @param string $table  Table name
     * @param string $where  WHERE clause with ? placeholders
     * @param array  $params Parameters
     * @return int
     */
    public function count(string $table, string $where = '1=1', array $params = []): int
    {
        return (int) $this->fetchColumn(
            "SELECT COUNT(*) FROM {$table} WHERE {$where}",
            $params,
            0
        );
    }

    /**
     * Check if any rows match a condition.
     *
     * @param string $table  Table name
     * @param string $where  WHERE clause
     * @param array  $params Parameters
     * @return bool
     */
    public function exists(string $table, string $where = '1=1', array $params = []): bool
    {
        return $this->count($table, $where, $params) > 0;
    }

    /**
     * Fetch with query caching support.
     *
     * @param string $sql      SQL query
     * @param array  $params   Bound parameters
     * @param int    $ttl      Cache TTL in seconds (0 = no cache)
     * @return array|null
     */
    public function fetchCached(string $sql, array $params = [], int $ttl = 300): ?array
    {
        if ($ttl <= 0 || !$this->queryCacheEnabled) {
            return $this->fetch($sql, $params);
        }

        if ($this->cache === null && class_exists('CacheManager')) {
            $this->cache = CacheManager::getInstance();
        }

        if ($this->cache === null) {
            return $this->fetch($sql, $params);
        }

        $cacheKey = 'query:' . md5($sql . serialize($params));

        return $this->cache->remember(
            $cacheKey,
            fn() => $this->fetch($sql, $params),
            $ttl,
            'queries'
        );
    }

    /**
     * Fetch all with query caching support.
     *
     * @param string $sql      SQL query
     * @param array  $params   Bound parameters
     * @param int    $ttl      Cache TTL in seconds (0 = no cache)
     * @return array
     */
    public function fetchAllCached(string $sql, array $params = [], int $ttl = 300): array
    {
        if ($ttl <= 0 || !$this->queryCacheEnabled) {
            return $this->fetchAll($sql, $params);
        }

        if ($this->cache === null && class_exists('CacheManager')) {
            $this->cache = CacheManager::getInstance();
        }

        if ($this->cache === null) {
            return $this->fetchAll($sql, $params);
        }

        $cacheKey = 'query:' . md5($sql . serialize($params));

        return $this->cache->remember(
            $cacheKey,
            fn() => $this->fetchAll($sql, $params),
            $ttl,
            'queries'
        );
    }

    /**
     * Invalidate query cache for specific tables.
     *
     * @param array $tables Table names to invalidate
     */
    public function invalidateQueryCache(array $tables = []): void
    {
        if ($this->cache === null && class_exists('CacheManager')) {
            $this->cache = CacheManager::getInstance();
        }

        if ($this->cache !== null) {
            $this->cache->flush('queries');
        }
    }

    /**
     * Enable or disable query caching.
     *
     * @param bool $enabled
     */
    public function setQueryCacheEnabled(bool $enabled): void
    {
        $this->queryCacheEnabled = $enabled;
    }
}
