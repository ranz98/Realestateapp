<?php
/**
 * Performance migration: add indexes to apartments to make filter queries
 * scale to 10k+ rows. Idempotent — checks for existing indexes before adding.
 *
 * Run once: php migrations/add_perf_indexes.php  (or hit it in the browser)
 */
require_once __DIR__ . '/../db.php';

header('Content-Type: text/plain; charset=utf-8');

function indexExists(PDO $pdo, string $table, string $name): bool {
    $stmt = $pdo->prepare("SHOW INDEX FROM `$table` WHERE Key_name = ?");
    $stmt->execute([$name]);
    return (bool) $stmt->fetch();
}

function addIndex(PDO $pdo, string $sql, string $table, string $name): void {
    if (indexExists($pdo, $table, $name)) {
        echo "[skip] $name already exists\n";
        return;
    }
    try {
        $pdo->exec($sql);
        echo "[ok]   added $name\n";
    } catch (Throwable $e) {
        echo "[err]  $name: " . $e->getMessage() . "\n";
    }
}

echo "Adding performance indexes to `apartments`...\n\n";

addIndex($pdo,
    "ALTER TABLE apartments ADD INDEX idx_status_mode_price (status, listing_mode, price)",
    'apartments', 'idx_status_mode_price');

addIndex($pdo,
    "ALTER TABLE apartments ADD INDEX idx_status_created (status, created_at)",
    'apartments', 'idx_status_created');

addIndex($pdo,
    "ALTER TABLE apartments ADD INDEX idx_status_views (status, view_count)",
    'apartments', 'idx_status_views');

addIndex($pdo,
    "ALTER TABLE apartments ADD INDEX idx_type (type)",
    'apartments', 'idx_type');

addIndex($pdo,
    "ALTER TABLE apartments ADD INDEX idx_bedrooms (bedrooms)",
    'apartments', 'idx_bedrooms');

addIndex($pdo,
    "ALTER TABLE apartments ADD FULLTEXT INDEX ft_search (title, address, description)",
    'apartments', 'ft_search');

// Composite index for the draw-shape bbox filter.
addIndex($pdo,
    "ALTER TABLE apartments ADD INDEX idx_lat_lng (lat, lng)",
    'apartments', 'idx_lat_lng');

echo "\nDone.\n";
