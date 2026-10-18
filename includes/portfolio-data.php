<?php
/**
 * Data dan helper Portofolio Rosa Indah Printing.
 * Fitur ini berdiri sendiri dan tidak mengubah sistem katalog produk.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

function ensure_portfolio_table(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS portfolios (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        image VARCHAR(255) NOT NULL,
        product_name VARCHAR(180) NOT NULL,
        price DECIMAL(14,2) NOT NULL DEFAULT 0,
        quantity INT UNSIGNED NOT NULL DEFAULT 1,
        customer_name VARCHAR(120) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_portfolios_created (created_at),
        INDEX idx_portfolios_name (product_name)
    ) ENGINE=InnoDB");
}

function portfolio_image_url(string $image): string {
    $image = ltrim(str_replace('\\', '/', trim($image)), '/');
    return str_starts_with($image, 'uploads/') ? page_url($image) : page_url($image);
}

function load_portfolios(PDO $pdo, ?int $limit = null, int $offset = 0): array {
    ensure_portfolio_table($pdo);
    $sql = 'SELECT id,image,product_name,price,quantity,customer_name,created_at FROM portfolios ORDER BY created_at DESC, id DESC';
    if ($limit !== null) {
        $sql .= ' LIMIT ' . max(1, (int)$limit) . ' OFFSET ' . max(0, (int)$offset);
    }
    return $pdo->query($sql)->fetchAll();
}

function portfolio_count(PDO $pdo): int {
    ensure_portfolio_table($pdo);
    return (int)$pdo->query('SELECT COUNT(*) FROM portfolios')->fetchColumn();
}
