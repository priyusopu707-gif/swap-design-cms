<?php
/**
 * Swap Design - Dev Database Setup (Fixed)
 *
 * Properly parses schema.sql respecting string literals so that
 * semicolons inside HTML/comment strings don't break splitting.
 * Handles MariaDB compatibility for ALTER TABLE statements.
 */

define('SWAP_ROOT', true);

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'swap';
$pass = getenv('DB_PASS') ?: 'swap_dev_pass';
$name = getenv('DB_NAME') ?: 'swap_design';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    echo "Creating database '$name'...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$name`");

    echo "Reading schema.sql...\n";
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    if ($sql === false) {
        throw new Exception("Failed to read schema.sql");
    }

    /* Remove SQL comments (-- style) within ALTER TABLE blocks */
    $lines = explode("\n", $sql);
    $cleaned = [];
    $inAlter = false;
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (preg_match('/^ALTER\s+TABLE/i', $trimmed)) {
            $inAlter = true;
        }
        if ($inAlter && preg_match('/^\s*--/', $line)) {
            continue;
        }
        $cleaned[] = $line;
        if ($inAlter && preg_match('/;\s*$/', $trimmed)) {
            $inAlter = false;
        }
    }
    $sql = implode("\n", $cleaned);

    /* Split SQL into statements respecting string literals */
    $statements = [];
    $current = '';
    $len = strlen($sql);
    $inString = false;
    $stringChar = null;
    $escapeNext = false;

    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i];

        if ($escapeNext) {
            $current .= $ch;
            $escapeNext = false;
            continue;
        }

        if ($inString) {
            if ($ch === '\\') {
                $current .= $ch;
                $escapeNext = true;
                continue;
            }
            if ($ch === $stringChar) {
                /* Check for doubled quotes like '' or "" */
                if ($i + 1 < $len && $sql[$i + 1] === $stringChar) {
                    $current .= $ch . $sql[$i + 1];
                    $i++;
                    continue;
                }
                $current .= $ch;
                $inString = false;
                $stringChar = null;
                continue;
            }
            $current .= $ch;
            continue;
        }

        if ($ch === "'" || $ch === '"') {
            $current .= $ch;
            $inString = true;
            $stringChar = $ch;
            continue;
        }

        if ($ch === ';') {
            $trimmed = trim($current);
            if ($trimmed !== '') {
                $statements[] = $trimmed;
            }
            $current = '';
            continue;
        }

        $current .= $ch;
    }
    $trimmed = trim($current);
    if ($trimmed !== '') {
        $statements[] = $trimmed;
    }

    /* Execute each statement */
    $count = 0;
    $errors = 0;
    foreach ($statements as $stmt) {
        if (empty($stmt)) continue;
        try {
            $pdo->exec($stmt);
            $count++;
        } catch (PDOException $e) {
            $preview = substr(preg_replace('/\s+/', ' ', $stmt), 0, 120);
            echo "  WARNING: Statement failed: {$e->getMessage()}\n";
            echo "    SQL: {$preview}...\n";
            $errors++;
        }
    }

    echo "Schema imported: $count successful, $errors failed.\n";

    /* Check if admin user exists */
    $check = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ((int)$check === 0) {
        echo "Creating admin user...\n";
        $hash = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
        $pdo->prepare("INSERT INTO users (email, password_hash, name, role, status, created_at) VALUES (?, ?, ?, 'admin', 'active', NOW())")
            ->execute(['admin@swapdesign.com', $hash, 'Admin']);
        echo "  Email: admin@swapdesign.com\n";
        echo "  Password: admin123\n";
    } else {
        echo "Admin user already exists, skipping.\n";
    }

    /* Verify table count */
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Database ready: " . count($tables) . " tables.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
