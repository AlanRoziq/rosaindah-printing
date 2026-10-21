<?php
/**
 * Stage 16 — Notifikasi & Tracking Pesanan Pelanggan
 */

function order_tracking_ensure_tables(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_status_history (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        order_id BIGINT UNSIGNED NOT NULL,
        status VARCHAR(30) NOT NULL,
        title VARCHAR(180) NOT NULL,
        message TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        INDEX idx_order_status_history_order (order_id, created_at)
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS order_notifications (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        order_id BIGINT UNSIGNED NOT NULL,
        type VARCHAR(30) NOT NULL DEFAULT 'status',
        title VARCHAR(180) NOT NULL,
        message TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        INDEX idx_order_notifications_order (order_id, created_at)
    ) ENGINE=InnoDB");
}

function order_status_label(string $status): string {
    return [
        'pending' => 'Pesanan Baru',
        'confirmed' => 'Dikonfirmasi',
        'processing' => 'Sedang Diproses',
        'printed' => 'Sudah Dicetak',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
    ][$status] ?? $status;
}

function order_status_message(string $status): array {
    return [
        'pending' => ['Pesanan diterima', 'Pesanan Anda sudah masuk ke sistem Rosa Indah Printing dan sedang menunggu pemeriksaan Admin.'],
        'confirmed' => ['Pesanan dikonfirmasi', 'Pesanan Anda sudah dikonfirmasi dan siap masuk ke proses produksi.'],
        'processing' => ['Pesanan sedang diproses', 'Pesanan Anda sedang dikerjakan oleh tim produksi Rosa Indah Printing.'],
        'printed' => ['Pesanan sudah dicetak', 'Proses pencetakan pesanan Anda sudah selesai dan sedang menunggu tahap akhir/pengambilan/pengiriman.'],
        'completed' => ['Pesanan selesai', 'Pesanan Anda telah selesai. Terima kasih sudah mempercayakan kebutuhan printing kepada kami.'],
        'cancelled' => ['Pesanan dibatalkan', 'Pesanan Anda telah dibatalkan. Silakan hubungi Admin jika membutuhkan informasi lebih lanjut.'],
    ][$status] ?? ['Status diperbarui', 'Status pesanan Anda telah diperbarui oleh Admin.'];
}

function order_payment_label(string $status): string {
    return [
        'unpaid' => 'Belum Bayar',
        'waiting' => 'Menunggu Verifikasi',
        'partial' => 'DP / Sebagian',
        'paid' => 'Lunas',
        'rejected' => 'Ditolak',
    ][$status] ?? $status;
}

function order_payment_message(string $status): array {
    return [
        'unpaid' => ['Pembayaran belum diterima', 'Silakan lakukan pembayaran sesuai instruksi yang tersedia pada halaman pembayaran.'],
        'waiting' => ['Pembayaran sedang diverifikasi', 'Konfirmasi pembayaran Anda sudah diterima dan sedang diperiksa oleh Admin.'],
        'partial' => ['Pembayaran sebagian diterima', 'Pembayaran sebagian/DP sudah tercatat. Admin akan menginformasikan sisa pembayaran jika diperlukan.'],
        'paid' => ['Pembayaran lunas', 'Pembayaran pesanan Anda sudah dinyatakan lunas oleh Admin.'],
        'rejected' => ['Pembayaran perlu diperiksa kembali', 'Konfirmasi pembayaran belum dapat diterima. Silakan hubungi Admin untuk pengecekan.'],
    ][$status] ?? ['Status pembayaran diperbarui', 'Status pembayaran pesanan Anda telah diperbarui oleh Admin.'];
}

function order_add_notification(PDO $pdo, int $orderId, string $type, string $title, string $message, ?string $status = null): void {
    order_tracking_ensure_tables($pdo);

    $stmt = $pdo->prepare('INSERT INTO order_notifications (order_id, type, title, message) VALUES (?, ?, ?, ?)');
    $stmt->execute([$orderId, $type, $title, $message]);

    if ($status !== null) {
        $history = $pdo->prepare('INSERT INTO order_status_history (order_id, status, title, message) VALUES (?, ?, ?, ?)');
        $history->execute([$orderId, $status, $title, $message]);
    }
}

function order_record_status(PDO $pdo, int $orderId, string $status): void {
    [$title, $message] = order_status_message($status);
    order_add_notification($pdo, $orderId, 'status', $title, $message, $status);
}

function order_record_payment(PDO $pdo, int $orderId, string $status): void {
    [$title, $message] = order_payment_message($status);
    order_add_notification($pdo, $orderId, 'payment', $title, $message, null);
}

function order_normalize_phone(string $value): string {
    $digits = preg_replace('/\D+/', '', $value) ?? '';
    if ($digits === '') return '';
    if (str_starts_with($digits, '0')) return '62' . substr($digits, 1);
    if (str_starts_with($digits, '8')) return '62' . $digits;
    return $digits;
}
