<?php
require_once __DIR__ . '/config/config.php';

if (!function_exists('format_rupiah')) {
    function format_rupiah($amount): string {
        return 'Rp' . number_format((float)$amount, 0, ',', '.');
    }
}

$vouchers = [];
$pdo = function_exists('db') ? db() : null;
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM vouchers WHERE is_active = 1 ORDER BY id DESC");
        $dbVouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($dbVouchers)) {
            $vouchers = $dbVouchers;
        }
    } catch (Throwable $e) {
        $vouchers = [];
    }
}

if (empty($vouchers)) {
    $vouchers = [
        [
            'id' => 1,
            'code' => 'ROSAHEMAT10',
            'name' => 'Diskon Spesial Cetak 10%',
            'discount_type' => 'percent',
            'discount_value' => 10,
            'min_order' => 100000,
            'max_discount' => 50000,
            'is_active' => 1,
        ],
        [
            'id' => 2,
            'code' => 'MEMBERBARU',
            'name' => 'Potongan Member Baru Rp15.000',
            'discount_type' => 'fixed',
            'discount_value' => 15000,
            'min_order' => 75000,
            'max_discount' => 15000,
            'is_active' => 1,
        ],
    ];
}

$pageTitle = 'Promo & Diskon Member';
include __DIR__ . '/includes/header.php';
?>

<header class="main-header">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
</header>

<main class="rip-promo-page">
    <div class="container">
        <!-- Breadcrumb -->
        <div class="catalog-breadcrumb mb-3">
            <a href="<?php echo page_url('index.php'); ?>">Home</a>
            <i class="bi bi-chevron-right"></i>
            <span>Promo Member</span>
        </div>

        <div class="section-title text-center rip-section-head">
            <span>Penawaran Khusus</span>
            <h2>Voucher & Promo Percetakan</h2>
            <p class="text-muted">Salin kode voucher di bawah ini dan gunakan pada halaman Checkout untuk menikmati potongan harga spesial!</p>
        </div>

        <div class="rip-voucher-grid">
            <?php foreach ($vouchers as $v):
                $vType = (string)($v['discount_type'] ?? 'percent');
                $vVal = (float)($v['discount_value'] ?? 0);
                $vMin = (float)($v['min_order'] ?? 0);
                $vMax = (float)($v['max_discount'] ?? 0);
                $vCode = (string)($v['code'] ?? '');
                $vName = (string)($v['name'] ?? 'Promo Spesial');
                $badgeText = $vType === 'percent' ? 'Diskon ' . (int)$vVal . '%' : 'Potongan ' . format_rupiah($vVal);
            ?>
                <article class="rip-voucher-card">
                    <div class="rip-voucher-top">
                        <span class="rip-voucher-badge">
                            <i class="bi bi-ticket-perforated-fill"></i> <?php echo htmlspecialchars($badgeText, ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                        <span class="rip-voucher-status">Promo Aktif</span>
                    </div>

                    <h3 class="rip-voucher-title"><?php echo htmlspecialchars($vName, ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p class="rip-voucher-desc">
                        Minimal belanja <strong><?php echo format_rupiah($vMin); ?></strong><?php echo $vMax > 0 ? ' • Maks. potongan <strong>' . format_rupiah($vMax) . '</strong>' : ''; ?>.
                    </p>

                    <div class="rip-voucher-code-box">
                        <div>
                            <small>Kode Voucher</small>
                            <code class="rip-voucher-code"><?php echo htmlspecialchars($vCode, ENT_QUOTES, 'UTF-8'); ?></code>
                        </div>
                        <button type="button" class="rip-copy-btn" data-code="<?php echo htmlspecialchars($vCode, ENT_QUOTES, 'UTF-8'); ?>" onclick="copyVoucherCode(this)">
                            <i class="bi bi-clipboard"></i> <span>Salin Kode</span>
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="rip-promo-cta">
            <a href="<?php echo page_url('produk.php'); ?>" class="rip-promo-shop-btn">
                <i class="bi bi-cart-check-fill"></i> Mulai Belanja & Gunakan Promo
            </a>
        </div>
    </div>
</main>

<style>
.rip-promo-page {
    padding: 28px 0 70px;
}
.rip-section-head {
    max-width: 680px;
    margin: 0 auto 30px;
}
.rip-section-head span {
    display: inline-block;
    background: #fff0f6;
    color: #e11d64;
    font-size: 11.5px;
    font-weight: 700;
    padding: 5px 14px;
    border-radius: 999px;
    border: 1px solid #f5c6da;
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 0.6px;
}
.rip-section-head h2 {
    font-size: clamp(22px, 3vw, 32px);
    font-weight: 800;
    color: #18243a;
    margin-bottom: 8px;
}
.rip-section-head p {
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 0;
}
.rip-voucher-grid {
    max-width: 920px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
}
.rip-voucher-card {
    background: #ffffff;
    border: 2px dashed #f2b8d0;
    border-radius: 20px;
    padding: 22px;
    box-shadow: 0 10px 26px rgba(214, 74, 138, 0.06);
    display: flex;
    flex-direction: column;
    transition: transform 0.2s ease, border-color 0.2s ease;
}
.rip-voucher-card:hover {
    transform: translateY(-2px);
    border-color: #e11d64;
}
.rip-voucher-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 14px;
}
.rip-voucher-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: linear-gradient(90deg, #e11d64 0%, #f43f7e 100%);
    color: #ffffff;
    font-size: 12.5px;
    font-weight: 700;
    padding: 6px 13px;
    border-radius: 999px;
    box-shadow: 0 4px 12px rgba(225, 29, 100, 0.2);
}
.rip-voucher-status {
    font-size: 11px;
    font-weight: 700;
    color: #25764a;
    background: #eaf8ef;
    border: 1px solid #c7ebd3;
    padding: 4px 10px;
    border-radius: 999px;
}
.rip-voucher-title {
    font-size: 18px;
    font-weight: 800;
    color: #18243a;
    margin: 0 0 6px;
    line-height: 1.3;
}
.rip-voucher-desc {
    font-size: 13px;
    color: #665e68;
    line-height: 1.55;
    margin: 0 0 18px;
}
.rip-voucher-code-box {
    margin-top: auto;
    background: #fff6fa;
    border: 1px solid #f4d3e2;
    border-radius: 14px;
    padding: 11px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.rip-voucher-code-box small {
    display: block;
    font-size: 10.5px;
    color: #887d85;
    font-weight: 600;
}
.rip-voucher-code {
    font-size: 16px;
    font-weight: 800;
    color: #e11d64;
    letter-spacing: 0.5px;
}
.rip-copy-btn {
    border: 1px solid #f0bfd4;
    background: #ffffff;
    color: #e11d64;
    font-size: 12px;
    font-weight: 700;
    padding: 8px 13px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
}
.rip-copy-btn:hover,
.rip-copy-btn.copied {
    background: #e11d64;
    border-color: #e11d64;
    color: #ffffff;
}
.rip-promo-cta {
    text-align: center;
    margin-top: 30px;
}
.rip-promo-shop-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: linear-gradient(90deg, #e11d64 0%, #f43f7e 50%, #fb71a0 100%);
    color: #ffffff;
    font-size: 14px;
    font-weight: 700;
    padding: 13px 26px;
    border-radius: 999px;
    text-decoration: none;
    box-shadow: 0 8px 22px rgba(225, 29, 100, 0.25);
    transition: all 0.2s ease;
}
.rip-promo-shop-btn:hover {
    background: linear-gradient(90deg, #be124f 0%, #e11d64 50%, #f43f7e 100%);
    color: #ffffff;
    transform: translateY(-1px);
}

@media (max-width: 767.98px) {
    .rip-promo-page {
        padding: 14px 0 88px;
    }
    .rip-section-head {
        margin-bottom: 18px;
    }
    .rip-section-head span {
        font-size: 9.5px;
        padding: 4px 10px;
        margin-bottom: 6px;
    }
    .rip-section-head h2 {
        font-size: 19px;
        line-height: 1.25;
        margin-bottom: 6px;
    }
    .rip-section-head p {
        font-size: 11.5px;
        line-height: 1.5;
    }
    .rip-voucher-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    .rip-voucher-card {
        padding: 16px 14px;
        border-radius: 16px;
    }
    .rip-voucher-top {
        margin-bottom: 10px;
    }
    .rip-voucher-badge {
        font-size: 11px;
        padding: 5px 11px;
    }
    .rip-voucher-status {
        font-size: 10px;
        padding: 3px 8px;
    }
    .rip-voucher-title {
        font-size: 15px;
        margin-bottom: 4px;
    }
    .rip-voucher-desc {
        font-size: 11.5px;
        margin-bottom: 12px;
    }
    .rip-voucher-code-box {
        padding: 9px 11px;
        border-radius: 11px;
    }
    .rip-voucher-code {
        font-size: 14px;
    }
    .rip-copy-btn {
        font-size: 11px;
        padding: 7px 11px;
    }
    .rip-promo-cta {
        margin-top: 20px;
    }
    .rip-promo-shop-btn {
        width: 100%;
        padding: 12px 18px;
        font-size: 12.5px;
    }
}
</style>

<script>
function copyVoucherCode(btn) {
    var code = btn.getAttribute('data-code') || '';
    if (!code) return;
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(code);
    } else {
        var temp = document.createElement('input');
        temp.value = code;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
    }
    var span = btn.querySelector('span');
    var icon = btn.querySelector('i');
    btn.classList.add('copied');
    if (span) span.textContent = 'Tersalin!';
    if (icon) icon.className = 'bi bi-check2';
    setTimeout(function() {
        btn.classList.remove('copied');
        if (span) span.textContent = 'Salin Kode';
        if (icon) icon.className = 'bi bi-clipboard';
    }, 1800);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
