<?php
define('DB_PATH', __DIR__ . '/fashionDatabase.db');
define('SCHEMA_PATH', __DIR__ . '/schema.sql');

try {
    $dbExists = file_exists(DB_PATH);

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON;');

    if (!$dbExists && file_exists(SCHEMA_PATH)) {
        $sql = file_get_contents(SCHEMA_PATH);
        $pdo->exec($sql);
    }

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>