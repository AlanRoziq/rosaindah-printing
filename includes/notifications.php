<?php
/** Tahap 24 — Notifikasi Member */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

function notifications_ensure_tables(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS member_notifications (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        customer_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        type VARCHAR(40) NOT NULL DEFAULT 'general',
        title VARCHAR(160) NOT NULL,
        message TEXT NOT NULL,
        link VARCHAR(500) NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        read_at DATETIME NULL,
        INDEX idx_member_notifications_customer (customer_id, is_read, created_at),
        INDEX idx_member_notifications_type (type, created_at)
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
    $s = $pdo->prepare("INSERT INTO member_notifications(customer_id,type,title,message,link) SELECT id,?,?,?,?,? FROM customers");
    $s->execute([$type, $title, $message, $link]);
    return $s->rowCount();
}

function member_notifications_sync(PDO $pdo, int $customerId): void {
    if ($customerId <= 0) return;
    static $syncedCustomers = [];
    if (!empty($syncedCustomers[$customerId])) return;
    $syncedCustomers[$customerId] = true;

    try {
        notifications_ensure_tables($pdo);

        // Ambil nomor telepon customer untuk mencocokkan pesanan
        $cStmt = $pdo->prepare('SELECT id, name, phone, points FROM customers WHERE id = ? LIMIT 1');
        $cStmt->execute([$customerId]);
        $cRow = $cStmt->fetch(PDO::FETCH_ASSOC);
        $rawPhone = trim((string)($cRow['phone'] ?? ''));
        $digits = preg_replace('/\D+/', '', $rawPhone);
        $phone62 = str_starts_with($digits, '0') ? ('62' . substr($digits, 1)) : $digits;
        $phone0 = str_starts_with($digits, '62') ? ('0' . substr($digits, 2)) : $digits;

        // 1. Pastikan ada notifikasi sambutan & info poin/voucher jika tabel masih kosong untuk member ini
        $cntStmt = $pdo->prepare('SELECT COUNT(*) FROM member_notifications WHERE customer_id = ?');
        $cntStmt->execute([$customerId]);
        $existingCount = (int)$cntStmt->fetchColumn();

        if ($existingCount === 0) {
            $pts = (int)($cRow['points'] ?? 100);
            member_notification_add(
                $pdo,
                $customerId,
                'reward',
                'Saldo ' . $pts . ' Poin Member Aktif ⭐',
                'Selamat datang di Rosa Indah Printing! Kumpulkan poin dari setiap pesanan selesai dan tukarkan menjadi voucher diskon di menu Poin & Voucher.',
                page_url('akun.php?tab=poin')
            );
            member_notification_add(
                $pdo,
                $customerId,
                'promo',
                'Voucher Diskon Member Siap Dipakai 🎟️',
                'Gunakan kode voucher ROSAHEMAT10, MEMBERBARU, atau CETAKUMKM20 saat Checkout untuk mendapatkan potongan harga langsung.',
                page_url('akun.php?tab=poin')
            );
        }

        // 2. Sinkronkan notifikasi otomatis dari pesanan milik customer yang belum tercatat di member_notifications
        $oStmt = $pdo->prepare('SELECT id, order_code, status, payment_status, total_estimate, created_at FROM orders WHERE customer_id = ? OR customer_phone IN (?, ?, ?) ORDER BY id DESC LIMIT 10');
        $oStmt->execute([$customerId, $rawPhone, $phone62, $phone0]);
        $custOrders = $oStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $statusLabels = [
            'pending'    => ['Menunggu Konfirmasi', 'sedang menunggu konfirmasi dari Admin Rosa Indah Printing.'],
            'confirmed'  => ['Dikonfirmasi', 'telah dikonfirmasi oleh Admin dan masuk antrean produksi.'],
            'processing' => ['Sedang Diproses', 'sedang dalam proses pengerjaan/desain oleh tim produksi kami.'],
            'printed'    => ['Sedang Dicetak', 'sedang dalam tahap pencetakan & finishing di workshop.'],
            'completed'  => ['Pesanan Selesai', 'telah selesai dikerjakan. Terima kasih telah mencetak di Rosa Indah Printing!'],
            'cancelled'  => ['Dibatalkan', 'telah dibatalkan. Hubungi Admin jika membutuhkan bantuan.'],
        ];

        foreach (array_reverse($custOrders) as $ord) {
            $code = trim((string)($ord['order_code'] ?? ''));
            if ($code === '') continue;

            $chkStmt = $pdo->prepare('SELECT id FROM member_notifications WHERE customer_id = ? AND (title LIKE ? OR message LIKE ?) LIMIT 1');
            $likeCode = '%' . $code . '%';
            $chkStmt->execute([$customerId, $likeCode, $likeCode]);
            if (!$chkStmt->fetchColumn()) {
                $stKey = strtolower((string)($ord['status'] ?? 'pending'));
                [$stTitle, $stDesc] = $statusLabels[$stKey] ?? ['Update Pesanan', 'sedang diproses oleh tim Rosa Indah Printing.'];
                $totalFmt = 'Rp ' . number_format((float)($ord['total_estimate'] ?? 0), 0, ',', '.');
                $trackLink = page_url('lacak-pesanan.php?code=' . urlencode($code) . '&phone=' . urlencode($rawPhone));
                member_notification_add(
                    $pdo,
                    $customerId,
                    'order',
                    'Status Pesanan ' . $code . ': ' . $stTitle,
                    'Pesanan Anda (' . $code . ' • ' . $totalFmt . ') ' . $stDesc,
                    $trackLink
                );
            }
        }
    } catch (Throwable $e) {
        // Jangan blokir halaman jika tabel belum siap
    }
}

function member_notification_unread_count(PDO $pdo, int $customerId): int {
    if ($customerId <= 0) return 0;
    notifications_ensure_tables($pdo);
    member_notifications_sync($pdo, $customerId);
    $s = $pdo->prepare('SELECT COUNT(*) FROM member_notifications WHERE (customer_id=? OR customer_id=0) AND is_read=0');
    $s->execute([$customerId]);
    return (int)$s->fetchColumn();
}

function member_notifications(PDO $pdo, int $customerId, int $limit = 50): array {
    if ($customerId <= 0) return [];
    notifications_ensure_tables($pdo);
    member_notifications_sync($pdo, $customerId);
    $limit = max(1, min(100, $limit));
    $s = $pdo->prepare("SELECT * FROM member_notifications WHERE (customer_id=? OR customer_id=0) ORDER BY created_at DESC,id DESC LIMIT {$limit}");
    $s->execute([$customerId]);
    return $s->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function member_notification_mark_read(PDO $pdo, int $customerId, ?int $notificationId = null): void {
    if ($customerId <= 0) return;
    notifications_ensure_tables($pdo);
    if ($notificationId !== null && $notificationId > 0) {
        $s = $pdo->prepare('UPDATE member_notifications SET is_read=1,read_at=NOW() WHERE id=? AND (customer_id=? OR customer_id=0)');
        $s->execute([$notificationId, $customerId]);
    } else {
        $s = $pdo->prepare('UPDATE member_notifications SET is_read=1,read_at=NOW() WHERE (customer_id=? OR customer_id=0) AND is_read=0');
        $s->execute([$customerId]);
    }
}
