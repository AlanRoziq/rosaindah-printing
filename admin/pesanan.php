<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/order-tracking.php';
require_once __DIR__ . '/../includes/loyalty.php';

$pdo = db();
$allowedStatuses = ['pending','confirmed','processing','printed','completed','cancelled'];
$paymentStatuses = ['unpaid','waiting','partial','paid','rejected'];
$paymentLabels = ['unpaid'=>'Belum Bayar','waiting'=>'Menunggu Verifikasi','partial'=>'DP / Sebagian','paid'=>'Lunas','rejected'=>'Ditolak'];
$paymentMethods = ['transfer'=>'Transfer Bank','qris'=>'QRIS','ewallet'=>'E-Wallet','cash'=>'Tunai','other'=>'Lainnya'];
$statusLabels = [
    'pending' => 'Baru',
    'confirmed' => 'Dikonfirmasi',
    'processing' => 'Diproses',
    'printed' => 'Dicetak',
    'completed' => 'Selesai',
    'cancelled' => 'Dibatalkan',
];
$error = '';

if (empty($_SESSION['admin_order_csrf'])) {
    $_SESSION['admin_order_csrf'] = bin2hex(random_bytes(32));
}
$orderCsrf = $_SESSION['admin_order_csrf'];

if ($pdo) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS order_attachments (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id BIGINT UNSIGNED NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            stored_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            INDEX idx_order_attachments_order (order_id)
        ) ENGINE=InnoDB");
    } catch (Throwable $e) {
        // Fitur lampiran akan disembunyikan jika tabel belum dapat dibuat.
    }
    // Migrasi ringan Tahap 15: pembayaran tanpa perlu import database ulang.
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('payment_status', $cols, true)) $pdo->exec("ALTER TABLE orders ADD payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid' AFTER status");
        if (!in_array('payment_method', $cols, true)) $pdo->exec("ALTER TABLE orders ADD payment_method VARCHAR(30) NULL AFTER payment_status");
        if (!in_array('paid_amount', $cols, true)) $pdo->exec("ALTER TABLE orders ADD paid_amount DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER payment_method");
        if (!in_array('payment_note', $cols, true)) $pdo->exec("ALTER TABLE orders ADD payment_note TEXT NULL AFTER paid_amount");
        if (!in_array('paid_at', $cols, true)) $pdo->exec("ALTER TABLE orders ADD paid_at DATETIME NULL AFTER payment_note");
        $pdo->exec("CREATE TABLE IF NOT EXISTS order_payments (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id BIGINT UNSIGNED NOT NULL,
            method VARCHAR(30) NOT NULL,
            amount DECIMAL(14,2) NOT NULL DEFAULT 0,
            proof_path VARCHAR(500) NULL,
            proof_name VARCHAR(255) NULL,
            note TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'waiting',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            INDEX idx_order_payments_order (order_id)
        ) ENGINE=InnoDB");
    } catch (Throwable $e) {
        // Jika DB lama tidak mengizinkan migrasi otomatis, halaman tetap berjalan.
    }
    try { order_tracking_ensure_tables($pdo); } catch (Throwable $e) {}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $csrf = $_POST['csrf'] ?? '';
    $orderId = (int)($_POST['order_id'] ?? 0);
    $mode = $_POST['mode'] ?? 'status';
    if (!hash_equals($orderCsrf, $csrf)) {
        $error = 'Permintaan tidak valid. Silakan muat ulang halaman.';
    } elseif ($orderId > 0 && $mode === 'status' && in_array($_POST['status'] ?? '', $allowedStatuses, true)) {
        try {
            $newStatus = $_POST['status'];
            $oldStmt = $pdo->prepare('SELECT status, order_code FROM orders WHERE id=? LIMIT 1');
            $oldStmt->execute([$orderId]);
            $oldOrder = $oldStmt->fetch();
            $stmt = $pdo->prepare('UPDATE orders SET status=? WHERE id=?');
            $stmt->execute([$newStatus, $orderId]);
            if ($oldOrder && ($oldOrder['status'] ?? '') !== $newStatus) {
                try { order_record_status($pdo, $orderId, $newStatus); } catch (Throwable $e) {}
                if ($newStatus === 'completed') {
                    try { loyalty_earn_for_completed_order($pdo, $orderId); } catch (Throwable $e) {}
                }
            }
            header('Location: ' . page_url('admin/pesanan.php'));
            exit;
        } catch (Throwable $e) { $error = 'Status pesanan gagal diperbarui.'; }
    } elseif ($orderId > 0 && $mode === 'payment' && in_array($_POST['payment_status'] ?? '', $paymentStatuses, true)) {
        try {
            $ps = $_POST['payment_status'];
            $method = $_POST['payment_method'] ?? null;
            if ($method !== null && !isset($paymentMethods[$method])) $method = null;
            $amount = max(0, (float)($_POST['paid_amount'] ?? 0));
            $note = trim((string)($_POST['payment_note'] ?? ''));
            $paidAt = $ps === 'paid' ? date('Y-m-d H:i:s') : null;
            $oldPaymentStmt = $pdo->prepare('SELECT payment_status FROM orders WHERE id=? LIMIT 1');
            $oldPaymentStmt->execute([$orderId]);
            $oldPayment = (string)$oldPaymentStmt->fetchColumn();
            $stmt = $pdo->prepare('UPDATE orders SET payment_status=?, payment_method=?, paid_amount=?, payment_note=?, paid_at=? WHERE id=?');
            $stmt->execute([$ps, $method, $amount, $note !== '' ? $note : null, $paidAt, $orderId]);
            if ($oldPayment !== $ps) {
                try { order_record_payment($pdo, $orderId, $ps); } catch (Throwable $e) {}
            }
            admin_flash('success', 'Status pembayaran berhasil diperbarui.');
            header('Location: ' . page_url('admin/pesanan.php'));
            exit;
        } catch (Throwable $e) { $error = 'Status pembayaran gagal diperbarui.'; }
    }
}

$pageTitle = 'Pesanan';
require_once __DIR__ . '/_header.php';
?>
<div class="admin-title admin-orders-heading">
    <div>
        <h1>Pesanan</h1>
        <p style="color:#777;margin:7px 0 0">Pantau, konfirmasi, dan kelola seluruh pesanan dari website Rosa Indah Printing.</p>
    </div>
</div>
<?php if ($error): ?><div class="admin-alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<?php if (!$pdo): ?>
<div class="admin-card"><p style="color:#a33b3b">Database belum terhubung.</p></div>
<?php else: ?>
<?php
$search = trim($_GET['q'] ?? '');
$filterStatus = $_GET['status'] ?? '';
$filterPayment = $_GET['payment'] ?? '';
$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(order_code LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like);
}
if (in_array($filterStatus, $allowedStatuses, true)) {
    $where[] = 'status = ?';
    $params[] = $filterStatus;
}
if (in_array($filterPayment, $paymentStatuses, true)) {
    $where[] = 'payment_status = ?';
    $params[] = $filterPayment;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders {$whereSql}");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM orders {$whereSql} ORDER BY created_at DESC LIMIT 100");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$counts = array_fill_keys($allowedStatuses, 0);
$countRows = $pdo->query('SELECT status, COUNT(*) total FROM orders GROUP BY status')->fetchAll();
foreach ($countRows as $cr) if (isset($counts[$cr['status']])) $counts[$cr['status']] = (int)$cr['total'];
$countsPayment = array_fill_keys($paymentStatuses, 0);
$paymentCountRows = $pdo->query('SELECT payment_status, COUNT(*) total FROM orders GROUP BY payment_status')->fetchAll();
foreach ($paymentCountRows as $cr) if (isset($countsPayment[$cr['payment_status']])) $countsPayment[$cr['payment_status']] = (int)$cr['total'];
?>

<div class="admin-order-stats">
    <a class="admin-order-stat <?php echo $filterStatus==='' ? 'active':''; ?>" href="<?php echo page_url('admin/pesanan.php'); ?>"><span>Semua Pesanan</span><strong><?php echo array_sum($counts); ?></strong></a>
    <a class="admin-order-stat pending" href="?status=pending"><span>Baru</span><strong><?php echo $counts['pending']; ?></strong></a>
    <a class="admin-order-stat confirmed" href="?status=confirmed"><span>Dikonfirmasi</span><strong><?php echo $counts['confirmed']; ?></strong></a>
    <a class="admin-order-stat processing" href="?status=processing"><span>Diproses</span><strong><?php echo $counts['processing']; ?></strong></a>
    <a class="admin-order-stat printed" href="?status=printed"><span>Dicetak</span><strong><?php echo $counts['printed']; ?></strong></a>
    <a class="admin-order-stat completed" href="?status=completed"><span>Selesai</span><strong><?php echo $counts['completed']; ?></strong></a>
</div>
<div class="admin-order-stats payment-stats">
    <a class="admin-order-stat payment-unpaid" href="?payment=unpaid"><span>Belum Bayar</span><strong><?php echo $countsPayment['unpaid'] ?? 0; ?></strong></a>
    <a class="admin-order-stat payment-waiting" href="?payment=waiting"><span>Menunggu Verifikasi</span><strong><?php echo $countsPayment['waiting'] ?? 0; ?></strong></a>
    <a class="admin-order-stat payment-partial" href="?payment=partial"><span>DP / Sebagian</span><strong><?php echo $countsPayment['partial'] ?? 0; ?></strong></a>
    <a class="admin-order-stat payment-paid" href="?payment=paid"><span>Lunas</span><strong><?php echo $countsPayment['paid'] ?? 0; ?></strong></a>
</div>

<div class="admin-card admin-orders-card">
    <form class="admin-order-toolbar" method="get">
        <div class="admin-order-search"><i class="bi bi-search"></i><input type="search" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari nomor pesanan, nama, atau WhatsApp..."></div>
        <select name="status" class="admin-order-filter">
            <option value="">Semua status</option>
            <?php foreach ($allowedStatuses as $s): ?><option value="<?php echo $s; ?>" <?php echo $filterStatus===$s?'selected':''; ?>><?php echo htmlspecialchars($statusLabels[$s]); ?></option><?php endforeach; ?>
        </select>
        <button class="admin-btn" type="submit"><i class="bi bi-funnel"></i> Filter</button>
        <?php if ($search!=='' || $filterStatus!=='' || $filterPayment!==''): ?><a class="admin-btn secondary" href="<?php echo page_url('admin/pesanan.php'); ?>">Reset</a><?php endif; ?>
    </form>
    <div class="admin-order-result"><span><?php echo $totalRows; ?> pesanan ditemukan</span><span>Menampilkan maksimal 100 pesanan terbaru</span></div>

    <?php if (!$rows): ?>
        <div style="padding:55px 20px;text-align:center;color:#968c93"><i class="bi bi-cart3" style="font-size:46px"></i><h3>Belum ada pesanan</h3><p>Pesanan dari checkout website akan tampil di sini.</p></div>
    <?php else: ?>
    <div class="admin-order-table-wrap">
    <table class="admin-table admin-orders-table">
        <thead><tr><th>Pesanan</th><th>Pemesan</th><th>Total Estimasi</th><th>Status</th><th>Pembayaran</th><th>Tanggal</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $order):
            $orderId = (int)$order['id'];
            $itemStmt=$pdo->prepare('SELECT * FROM order_items WHERE order_id=? ORDER BY id ASC');
            $itemStmt->execute([$orderId]);
            $orderItems=$itemStmt->fetchAll();
            $attachmentRows = [];
            try {
                $attachmentStmt = $pdo->prepare('SELECT id, original_name, mime_type, file_size, created_at FROM order_attachments WHERE order_id=? ORDER BY id ASC');
                $attachmentStmt->execute([$orderId]);
                $attachmentRows = $attachmentStmt->fetchAll();
            } catch (Throwable $e) {
                $attachmentRows = [];
            }
            $latestPayment = null;
            try { $lp = $pdo->prepare('SELECT id, method, amount, proof_name, status, created_at FROM order_payments WHERE order_id=? ORDER BY id DESC LIMIT 1'); $lp->execute([$orderId]); $latestPayment = $lp->fetch() ?: null; } catch (Throwable $e) { $latestPayment = null; }
            $waPhone = preg_replace('/\D+/', '', (string)$order['customer_phone']);
            if (str_starts_with($waPhone, '0')) $waPhone = '62' . substr($waPhone, 1);
            $waText = "Halo {$order['customer_name']}, kami dari Rosa Indah Printing. Kami ingin mengonfirmasi pesanan {$order['order_code']}.";
        ?>
            <tr>
                <td><strong class="order-code"><?php echo htmlspecialchars($order['order_code']); ?></strong><small>#<?php echo $orderId; ?></small></td>
                <td><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><small><?php echo htmlspecialchars($order['customer_phone']); ?></small></td>
                <td><strong>Rp<?php echo number_format($order['total_estimate'],0,',','.'); ?></strong></td>
                <td>
                    <form method="post" class="admin-order-status-form">
                        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($orderCsrf); ?>">
                        <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                        <select name="status" onchange="this.form.submit()" class="admin-status-select status-<?php echo htmlspecialchars($order['status']); ?>">
                            <?php foreach ($allowedStatuses as $status): ?><option value="<?php echo $status; ?>" <?php echo $order['status']===$status?'selected':''; ?>><?php echo htmlspecialchars($statusLabels[$status]); ?></option><?php endforeach; ?>
                        </select>
                    </form>
                </td>
                <td>
                    <div class="payment-summary">
                        <span class="payment-pill payment-<?php echo htmlspecialchars($order['payment_status'] ?? 'unpaid'); ?>"><?php echo htmlspecialchars($paymentLabels[$order['payment_status'] ?? 'unpaid'] ?? 'Belum Bayar'); ?></span>
                        <small>Rp<?php echo number_format((float)($order['paid_amount'] ?? 0),0,',','.'); ?></small>
                    </div>
                </td>
                <td><strong><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></strong><small><?php echo date('H:i', strtotime($order['created_at'])); ?> WIB</small></td>
                <td><div class="admin-order-actions"><button type="button" class="admin-small-btn" data-order-detail="<?php echo $orderId; ?>"><i class="bi bi-eye"></i> Detail</button><a class="admin-small-btn" target="_blank" rel="noopener" href="<?php echo page_url('invoice.php?code=' . urlencode($order['order_code'])); ?>"><i class="bi bi-receipt"></i> Nota</a><?php if ($waPhone): ?><a class="admin-small-btn wa" target="_blank" rel="noopener" href="https://wa.me/<?php echo htmlspecialchars($waPhone); ?>?text=<?php echo rawurlencode($waText); ?>"><i class="bi bi-whatsapp"></i> Hubungi</a><?php endif; ?></div></td>
            </tr>
            <tr class="admin-order-detail-row" id="order-detail-<?php echo $orderId; ?>" hidden>
                <td colspan="7">
                    <div class="admin-order-detail-box">
                        <div class="order-detail-head"><div><span class="detail-mini-label">NO. PESANAN</span><h3><?php echo htmlspecialchars($order['order_code']); ?></h3></div><div class="detail-status-stack"><div class="order-detail-status status-pill-<?php echo htmlspecialchars($order['status']); ?>"><?php echo htmlspecialchars($statusLabels[$order['status']]); ?></div><span class="payment-pill payment-<?php echo htmlspecialchars($order['payment_status'] ?? 'unpaid'); ?>"><?php echo htmlspecialchars($paymentLabels[$order['payment_status'] ?? 'unpaid']); ?></span></div></div>
                        <div class="order-detail-grid">
                            <div><span class="detail-mini-label">DATA PEMESAN</span><p><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br><?php echo htmlspecialchars($order['customer_phone']); ?></p></div>
                            <div><span class="detail-mini-label">TANGGAL ORDER</span><p><?php echo date('d F Y, H:i', strtotime($order['created_at'])); ?> WIB</p></div>
                            <div class="order-detail-full"><span class="detail-mini-label">ITEM PESANAN</span>
                                <div class="order-items-admin-list">
                                <?php foreach($orderItems as $oi):
                                    $variant=[]; if (!empty($oi['variant_data'])) { $decoded=json_decode($oi['variant_data'], true); if(is_array($decoded)) $variant=$decoded; }
                                ?>
                                    <div class="order-item-admin"><div><strong><?php echo htmlspecialchars($oi['product_name']); ?></strong><span><?php echo (int)$oi['quantity']; ?> <?php echo htmlspecialchars($oi['unit']); ?> × Rp<?php echo number_format($oi['unit_price'],0,',','.'); ?></span><?php if($variant): ?><small><?php echo htmlspecialchars(implode(' • ', array_filter([$variant['ukuran']??'', $variant['bahan']??'', $variant['finishing']??'', $variant['desain']??'']))); ?></small><?php endif; ?></div><b>Rp<?php echo number_format($oi['subtotal'],0,',','.'); ?></b></div>
                                <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="order-detail-full"><span class="detail-mini-label">CATATAN / ALAMAT / SPESIFIKASI</span><p class="order-note"><?php echo htmlspecialchars($order['customer_note'] ?: '-'); ?></p></div>
                            <div class="order-detail-full"><span class="detail-mini-label">FILE / DESAIN PEMESAN</span>
                                <?php if ($attachmentRows): ?>
                                    <div class="order-attachments-list">
                                    <?php foreach ($attachmentRows as $att): $sizeMb = max(0.01, ((int)$att['file_size']) / 1048576); ?>
                                        <a class="order-attachment" target="_blank" rel="noopener" href="<?php echo page_url('admin/download-attachment.php?id=' . (int)$att['id']); ?>">
                                            <i class="bi <?php echo $att['mime_type']==='application/pdf' ? 'bi-file-earmark-pdf' : 'bi-file-earmark-image'; ?>"></i>
                                            <span><strong><?php echo htmlspecialchars($att['original_name']); ?></strong><small><?php echo number_format($sizeMb, 2, ',', '.'); ?> MB • <?php echo htmlspecialchars($att['mime_type']); ?></small></span>
                                            <i class="bi bi-download"></i>
                                        </a>
                                    <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="order-note">Tidak ada file/desain yang dilampirkan.</p>
                                <?php endif; ?>
                            </div>
                            <div class="order-detail-full payment-management">
    <span class="detail-mini-label">PEMBAYARAN</span>
    <form method="post" class="payment-admin-form">
        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($orderCsrf); ?>">
        <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
        <input type="hidden" name="mode" value="payment">
        <div><label>Status</label><select name="payment_status"><?php foreach($paymentStatuses as $ps): ?><option value="<?php echo $ps; ?>" <?php echo (($order['payment_status'] ?? 'unpaid')===$ps?'selected':''); ?>><?php echo htmlspecialchars($paymentLabels[$ps]); ?></option><?php endforeach; ?></select></div>
        <div><label>Metode</label><select name="payment_method"><option value="">Pilih</option><?php foreach($paymentMethods as $pm=>$label): ?><option value="<?php echo $pm; ?>" <?php echo (($order['payment_method'] ?? '')===$pm?'selected':''); ?>><?php echo htmlspecialchars($label); ?></option><?php endforeach; ?></select></div>
        <div><label>Nominal Dibayar</label><input type="number" min="0" step="100" name="paid_amount" value="<?php echo htmlspecialchars((string)($order['paid_amount'] ?? 0)); ?>"></div>
        <div class="payment-note-field"><label>Catatan</label><input type="text" name="payment_note" value="<?php echo htmlspecialchars((string)($order['payment_note'] ?? '')); ?>" placeholder="Contoh: DP 50%, transfer BCA, dll."></div>
        <button class="admin-small-btn payment-save" type="submit"><i class="bi bi-check2-circle"></i> Simpan Pembayaran</button>
    </form>
    <?php if ($latestPayment && !empty($latestPayment['proof_name'])): ?><div class="payment-proof"><i class="bi bi-file-earmark-check"></i><span><strong>Bukti pembayaran:</strong> <?php echo htmlspecialchars($latestPayment['proof_name']); ?><small><?php echo date('d/m/Y H:i', strtotime($latestPayment['created_at'])); ?> WIB • Rp<?php echo number_format((float)$latestPayment['amount'],0,',','.'); ?></small></span><a class="admin-small-btn" target="_blank" rel="noopener" href="<?php echo page_url('admin/download-payment-proof.php?id='.(int)$latestPayment['id']); ?>"><i class="bi bi-eye"></i> Lihat</a></div><?php endif; ?>
    <?php if (!empty($order['paid_at'])): ?><small class="payment-updated">Lunas pada <?php echo date('d/m/Y H:i', strtotime($order['paid_at'])); ?> WIB</small><?php endif; ?>
</div>
<div class="order-total-box"><div><span>Subtotal</span><strong>Rp<?php echo number_format((float)($order['subtotal_before_discount'] ?? $order['total_estimate']),0,',','.'); ?></strong></div><?php if((float)($order['discount_amount'] ?? 0)>0): ?><div><span><?php echo !empty($order['voucher_code']) ? 'Voucher '.htmlspecialchars($order['voucher_code']) : 'Promo Member'; ?></span><strong style="color:#2f8a58">-Rp<?php echo number_format((float)$order['discount_amount'],0,',','.'); ?></strong></div><?php endif; ?><div><span>Total Estimasi</span><strong>Rp<?php echo number_format($order['total_estimate'],0,',','.'); ?></strong></div></div>
                        </div>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
<script>
document.querySelectorAll('[data-order-detail]').forEach(btn => btn.addEventListener('click', () => {
 const row=document.getElementById('order-detail-'+btn.dataset.orderDetail); if(!row)return; row.hidden=!row.hidden; btn.innerHTML=row.hidden ? '<i class="bi bi-eye"></i> Detail' : '<i class="bi bi-eye-slash"></i> Tutup';
}));
</script>
<?php endif; ?>
<?php require __DIR__ . '/_footer.php'; ?>
