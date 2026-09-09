<?php
/** Tahap 24 — Notifikasi Member */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

function notifications_ensure_tables(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS member_notifications (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        customer_id BIGINT UNSIGNED NOT NULL,
        type VARCHAR(40) NOT NULL DEFAULT 'general',
        title VARCHAR(160) NOT NULL,
        message TEXT NOT NULL,
        link VARCHAR(500) NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        read_at DATETIME NULL,
        INDEX idx_member_notifications_customer (customer_id, is_read, created_at),
        INDEX idx_member_notifications_type (type, created_at),
        CONSTRAINT fk_member_notifications_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
}

function member_notification_add(PDO $pdo, int $customerId, string $type, string $title, string $message, ?string $link = null): bool {
    if ($customerId <= 0) return false;
    notifications_ensure_tables($pdo);
    $s = $pdo->prepare('INSERT INTO member_notifications(customer_id,type,title,message,link) VALUES(?,?,?,?,?)');
    $s->execute([$customerId, $type, $title, $message, $link]);
    return true;
}

function member_notification_broadcast(PDO $pdo, string $type, string $title, string $message, ?string $link = null): int {
    notifications_ensure_tables($pdo);
    $s = $pdo->prepare("INSERT INTO member_notifications(customer_id,type,title,message,link) SELECT id,?,?,?,?,? FROM customers WHERE is_verified=1");
    $s->execute([$type, $title, $message, $link]);
    return $s->rowCount();
}

function member_notification_unread_count(PDO $pdo, int $customerId): int {
    if ($customerId <= 0) return 0;
    notifications_ensure_tables($pdo);
    $s = $pdo->prepare('SELECT COUNT(*) FROM member_notifications WHERE customer_id=? AND is_read=0');
    $s->execute([$customerId]);
    return (int)$s->fetchColumn();
}

function member_notifications(PDO $pdo, int $customerId, int $limit = 50): array {
    if ($customerId <= 0) return [];
    notifications_ensure_tables($pdo);
    $limit = max(1, min(100, $limit));
    $s = $pdo->prepare("SELECT * FROM member_notifications WHERE customer_id=? ORDER BY created_at DESC,id DESC LIMIT {$limit}");
    $s->execute([$customerId]);
    return $s->fetchAll();
}

function member_notification_mark_read(PDO $pdo, int $customerId, ?int $notificationId = null): void {
    if ($customerId <= 0) return;
    notifications_ensure_tables($pdo);
    if ($notificationId !== null && $notificationId > 0) {
        $s = $pdo->prepare('UPDATE member_notifications SET is_read=1,read_at=NOW() WHERE id=? AND customer_id=?');
        $s->execute([$notificationId, $customerId]);
    } else {
        $s = $pdo->prepare('UPDATE member_notifications SET is_read=1,read_at=NOW() WHERE customer_id=? AND is_read=0');
        $s->execute([$customerId]);
    }
}
