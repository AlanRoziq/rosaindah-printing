<?php
require_once __DIR__ . '/../config/auth.php';

$pdo = db();
$pageTitle = 'Member';
require_once __DIR__ . '/_header.php';

$search = trim($_GET['q'] ?? '');
$selectedPhone = trim($_GET['phone'] ?? '');
$customers = [];
$customerCount = 0;
$totalOrders = 0;
$totalValue = 0;

if ($pdo) {
    $where = 'WHERE 1=1';
    $params = [];
    if ($search !== '') {
        $where .= ' AND (m.phone LIKE ? OR m.name LIKE ?)';
        $like = '%' . $search . '%';
        $params = [$like, $like];
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers m {$where}");
    $stmt->execute($params);
    $customerCount = (int)$stmt->fetchColumn();

    $summary = $pdo->query("SELECT COUNT(*) total_orders, COALESCE(SUM(CASE WHEN status <> 'cancelled' THEN total_estimate ELSE 0 END),0) total_value FROM orders")->fetch();
    $totalOrders = (int)($summary['total_orders'] ?? 0);
    $totalValue = (float)($summary['total_value'] ?? 0);

    $sql = "SELECT
                m.id,
                m.name AS customer_name,
                m.phone AS customer_phone,
                m.email,
                m.is_verified,
                m.created_at AS member_created,
                COALESCE(o.total_orders,0) total_orders,
                COALESCE(o.total_value,0) total_value,
                COALESCE(o.completed_value,0) completed_value,
                o.last_order,
                COALESCE(o.pending_orders,0) pending_orders,
                COALESCE(o.active_orders,0) active_orders,
                COALESCE(o.completed_orders,0) completed_orders,
                COALESCE(o.cancelled_orders,0) cancelled_orders
            FROM customers m
            LEFT JOIN (
                SELECT customer_phone,
                       COUNT(*) total_orders,
                       COALESCE(SUM(CASE WHEN status <> 'cancelled' THEN total_estimate ELSE 0 END),0) total_value,
                       COALESCE(SUM(CASE WHEN status='completed' THEN total_estimate ELSE 0 END),0) completed_value,
                       MAX(created_at) last_order,
                       SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) pending_orders,
                       SUM(CASE WHEN status IN ('confirmed','processing','printed') THEN 1 ELSE 0 END) active_orders,
                       SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) completed_orders,
                       SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) cancelled_orders
                FROM orders
                GROUP BY customer_phone
            ) o ON o.customer_phone = m.phone
            {$where}
            ORDER BY COALESCE(o.last_order, m.created_at) DESC
            LIMIT 100";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll();
}?>

<div class="admin-title admin-customers-heading">
    <div>
        <h1>Member</h1>
        <p style="color:#777;margin:7px 0 0">Kelola data Member dan lihat seluruh riwayat pesanan mereka.</p>
    </div>
</div>

<?php if (!$pdo): ?>
    <div class="admin-card"><p style="color:#a33b3b">Database belum terhubung.</p></div>
<?php else: ?>

<div class="admin-customer-stats">
    <div class="admin-customer-stat"><i class="bi bi-people"></i><span>Total Member</span><strong><?php echo $customerCount; ?></strong></div>
    <div class="admin-customer-stat"><i class="bi bi-receipt"></i><span>Total Pesanan</span><strong><?php echo $totalOrders; ?></strong></div>
    <div class="admin-customer-stat"><i class="bi bi-wallet2"></i><span>Nilai Pesanan</span><strong>Rp<?php echo number_format($totalValue,0,',','.'); ?></strong></div>
</div>

<div class="admin-card admin-customers-card">
    <form method="get" class="admin-customer-toolbar">
        <div class="admin-order-search">
            <i class="bi bi-search"></i>
            <input type="search" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari nama Member atau nomor WhatsApp...">
        </div>
        <button class="admin-btn" type="submit"><i class="bi bi-search"></i> Cari</button>
        <?php if ($search !== ''): ?><a class="admin-btn secondary" href="<?php echo page_url('admin/pelanggan.php'); ?>">Reset</a><?php endif; ?>
    </form>

    <div class="admin-order-result">
        <span><?php echo $customerCount; ?> Member ditemukan</span>
        <span>Maksimal 100 Member terbaru</span>
    </div>

    <?php if (!$customers): ?>
        <div class="admin-customer-empty">
            <i class="bi bi-people"></i>
            <h3>Belum ada Member</h3>
            <p>Member terdaftar akan tampil di sini bersama riwayat pesanan mereka.</p>
        </div>
    <?php else: ?>
        <div class="admin-customer-table-wrap">
            <table class="admin-table admin-customers-table">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>WhatsApp</th>
                        <th>Pesanan</th>
                        <th>Total Belanja</th>
                        <th>Pesanan Terakhir</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($customers as $customer):
                    $phoneRaw = (string)$customer['customer_phone'];
                    $waPhone = preg_replace('/\D+/', '', $phoneRaw);
                    if (str_starts_with($waPhone, '0')) $waPhone = '62' . substr($waPhone, 1);
                    $detailKey = 'customer-' . md5($phoneRaw);
                    $waText = 'Halo ' . $customer['customer_name'] . ', kami dari Rosa Indah Printing. Terima kasih sudah melakukan pemesanan.';
                    $orders = [];
                    $customerOrdersTotal = 0;
                    if ($pdo) {
                        $os = $pdo->prepare('SELECT id, order_code, total_estimate, status, payment_status, paid_amount, created_at, customer_note FROM orders WHERE customer_phone = ? ORDER BY created_at DESC LIMIT 100');
                        $os->execute([$phoneRaw]);
                        $orders = $os->fetchAll();
                    }
                ?>
                    <tr>
                        <td>
                            <strong class="customer-name"><?php echo htmlspecialchars($customer['customer_name']); ?></strong>
                            <small class="customer-meta"><?php echo (int)$customer['completed_orders']; ?> selesai · <?php echo (int)$customer['active_orders']; ?> aktif</small><?php if ((int)$customer['is_verified'] === 1): ?><span class="customer-meta" style="color:#287a54;font-weight:800"><i class="bi bi-patch-check-fill"></i> Member terverifikasi</span><?php else: ?><span class="customer-meta" style="color:#a36a1d;font-weight:800"><i class="bi bi-hourglass-split"></i> Menunggu verifikasi</span><?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($phoneRaw); ?></td>
                        <td><strong><?php echo (int)$customer['total_orders']; ?></strong><small class="customer-meta"><?php echo (int)$customer['pending_orders']; ?> baru</small></td>
                        <td><strong>Rp<?php echo number_format($customer['total_value'],0,',','.'); ?></strong></td>
                        <td><strong><?php echo !empty($customer['last_order']) ? date('d/m/Y', strtotime($customer['last_order'])) : '-'; ?></strong><small class="customer-meta"><?php echo !empty($customer['last_order']) ? date('H:i', strtotime($customer['last_order'])) . ' WIB' : 'Belum ada pesanan'; ?></small></td>
                        <td>
                            <div class="admin-customer-actions">
                                <button type="button" class="admin-small-btn" data-customer-detail="<?php echo htmlspecialchars($detailKey); ?>"><i class="bi bi-clock-history"></i> Riwayat</button>
                                <?php if ($waPhone): ?><a class="admin-small-btn wa" target="_blank" rel="noopener" href="https://wa.me/<?php echo htmlspecialchars($waPhone); ?>?text=<?php echo rawurlencode($waText); ?>"><i class="bi bi-whatsapp"></i> Hubungi</a><?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <tr class="admin-customer-detail-row" id="<?php echo htmlspecialchars($detailKey); ?>" hidden>
                        <td colspan="6">
                            <div class="admin-customer-detail-box">
                                <div class="customer-detail-head">
                                    <div>
                                        <span class="detail-mini-label">PROFIL MEMBER</span>
                                        <h3><?php echo htmlspecialchars($customer['customer_name']); ?></h3>
                                        <p><?php echo htmlspecialchars($phoneRaw); ?></p>
                                    </div>
                                    <div class="customer-detail-total"><span>Total non-cancelled</span><strong>Rp<?php echo number_format($customer['total_value'],0,',','.'); ?></strong></div>
                                </div>
                                <div class="customer-history-list">
                                    <?php foreach ($orders as $order): ?>
                                        <div class="customer-history-item">
                                            <div>
                                                <strong><?php echo htmlspecialchars($order['order_code']); ?></strong>
                                                <span><?php echo date('d F Y, H:i', strtotime($order['created_at'])); ?> WIB</span>
                                                <?php if (!empty($order['customer_note'])): ?><small><?php echo htmlspecialchars($order['customer_note']); ?></small><?php endif; ?>
                                            </div>
                                            <div class="customer-history-right">
                                                <span class="order-detail-status status-pill-<?php echo htmlspecialchars($order['status']); ?>"><?php echo htmlspecialchars([
                                                    'pending'=>'Baru','confirmed'=>'Dikonfirmasi','processing'=>'Diproses','printed'=>'Dicetak','completed'=>'Selesai','cancelled'=>'Dibatalkan'
                                                ][$order['status']] ?? $order['status']); ?></span>
                                                <span class="payment-pill payment-<?php echo htmlspecialchars($order['payment_status'] ?? 'unpaid'); ?>"><?php echo htmlspecialchars(['unpaid'=>'Belum Bayar','waiting'=>'Menunggu Verifikasi','partial'=>'DP / Sebagian','paid'=>'Lunas','rejected'=>'Ditolak'][$order['payment_status'] ?? 'unpaid'] ?? 'Belum Bayar'); ?></span><strong>Rp<?php echo number_format($order['total_estimate'],0,',','.'); ?></strong>
                                                <a href="<?php echo page_url('admin/pesanan.php?q=' . urlencode($order['order_code'])); ?>" class="admin-small-btn">Lihat Pesanan</a><a href="<?php echo page_url('invoice.php?code=' . urlencode($order['order_code'])); ?>" target="_blank" rel="noopener" class="admin-small-btn"><i class="bi bi-receipt"></i> Nota</a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="customer-detail-footer">
                                    <span><b><?php echo (int)$customer['total_orders']; ?></b> total pesanan</span>
                                    <span><b><?php echo (int)$customer['completed_orders']; ?></b> selesai</span>
                                    <span><b><?php echo (int)$customer['cancelled_orders']; ?></b> dibatalkan</span>
                                    <span><b>Rp<?php echo number_format($customer['completed_value'],0,',','.'); ?></b> transaksi selesai</span>
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
<?php endif; ?>

<script>
document.querySelectorAll('[data-customer-detail]').forEach(function(button){
    button.addEventListener('click', function(){
        var id = button.getAttribute('data-customer-detail');
        var row = document.getElementById(id);
        if (!row) return;
        var isHidden = row.hasAttribute('hidden');
        if (isHidden) {
            row.removeAttribute('hidden');
            button.innerHTML = '<i class="bi bi-chevron-up"></i> Tutup Riwayat';
        } else {
            row.setAttribute('hidden','hidden');
            button.innerHTML = '<i class="bi bi-clock-history"></i> Riwayat';
        }
    });
});
</script>

<?php require __DIR__ . '/_footer.php'; ?>
