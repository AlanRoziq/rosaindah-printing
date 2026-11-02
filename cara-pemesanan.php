<?php
require_once __DIR__ . '/config/config.php';
$waNumber = defined('WHATSAPP_NUMBER') ? WHATSAPP_NUMBER : '6282331035371';
$pageTitle = 'Panduan Cara Pemesanan';
include __DIR__ . '/includes/header.php';
?>

<header class="main-header">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
</header>

<main class="rip-info-page">
    <div class="container">
        <!-- Breadcrumb -->
        <div class="catalog-breadcrumb mb-3">
            <a href="<?php echo page_url('index.php'); ?>">Home</a>
            <i class="bi bi-chevron-right"></i>
            <span>Cara Pemesanan</span>
        </div>

        <div class="section-title text-center rip-section-head">
            <span>Panduan Praktis</span>
            <h2>Cara Pemesanan di Rosa Indah Printing</h2>
            <p class="text-muted">Ikuti 4 langkah mudah berikut untuk mencetak kebutuhan promosi, dokumen, dan souvenir Anda dengan cepat.</p>
        </div>

        <!-- 4 Langkah Pemesanan -->
        <div class="rip-steps-grid">
            <article class="rip-step-card">
                <div class="rip-step-top">
                    <div class="rip-step-num">01</div>
                    <div class="rip-step-icon"><i class="bi bi-grid-1x2-fill"></i></div>
                </div>
                <h3>Pilih Produk & Spesifikasi</h3>
                <p>Buka <a href="<?php echo page_url('produk.php'); ?>">Katalog Produk</a>, pilih jenis cetakan (banner, kartu nama, undangan, brosur, dll.), lalu tentukan ukuran dan jumlah pesanan.</p>
            </article>

            <article class="rip-step-card">
                <div class="rip-step-top">
                    <div class="rip-step-num">02</div>
                    <div class="rip-step-icon"><i class="bi bi-bag-check-fill"></i></div>
                </div>
                <h3>Checkout & Nomor Pesanan</h3>
                <p>Isi nama lengkap, nomor WhatsApp aktif, serta catatan/alamat. Sistem otomatis menerbitkan kode pesanan resmi (contoh: <code>RIP-2026...</code>).</p>
            </article>

            <article class="rip-step-card">
                <div class="rip-step-top">
                    <div class="rip-step-num">03</div>
                    <div class="rip-step-icon"><i class="bi bi-file-earmark-arrow-up-fill"></i></div>
                </div>
                <h3>Kirim File Desain & Pembayaran</h3>
                <p>Kirim file siap cetak melalui WhatsApp atau lampirkan saat checkout, lalu konfirmasi pembayaran/DP agar pesanan langsung masuk antrian cetak.</p>
            </article>

            <article class="rip-step-card">
                <div class="rip-step-top">
                    <div class="rip-step-num">04</div>
                    <div class="rip-step-icon"><i class="bi bi-box-seam-fill"></i></div>
                </div>
                <h3>Produksi & Pengambilan</h3>
                <p>Pesanan diproduksi dengan kontrol kualitas ketat. Anda dapat memantau progres di menu <a href="<?php echo page_url('lacak-pesanan.php'); ?>">Lacak Pesanan</a> hingga siap diambil/dikirim.</p>
            </article>
        </div>

        <!-- Info Ketentuan File & Layanan -->
        <div class="rip-guide-extras">
            <div class="rip-guide-box">
                <div class="rip-guide-box-icon"><i class="bi bi-filetype-pdf"></i></div>
                <div>
                    <h4>Format File Siap Cetak</h4>
                    <p>Gunakan format <strong>PDF, CDR, AI, PSD, atau JPG/PNG resolusi tinggi (min. 300 DPI)</strong> dengan mode warna CMYK agar hasil warna tajam & presisi.</p>
                </div>
            </div>
            <div class="rip-guide-box">
                <div class="rip-guide-box-icon"><i class="bi bi-palette2"></i></div>
                <div>
                    <h4>Belum Punya Desain?</h4>
                    <p>Tim desainer Rosa Indah Printing siap membantu pembuatan layout & desain dari awal. Cukup konsultasikan konsep Anda melalui WhatsApp.</p>
                </div>
            </div>
        </div>

        <!-- CTA Banner -->
        <div class="rip-cta-banner">
            <div class="rip-cta-text">
                <h3>Siap Mencetak Kebutuhan Promosi Anda?</h3>
                <p>Hubungi admin kami untuk konsultasi bahan & ukuran, atau langsung pilih produk dari katalog online.</p>
            </div>
            <div class="rip-cta-actions">
                <a href="https://wa.me/<?php echo rawurlencode($waNumber); ?>?text=<?php echo rawurlencode('Halo Admin Rosa Indah Printing, saya ingin bertanya cara pemesanan cetak.'); ?>" target="_blank" rel="noopener" class="rip-btn rip-btn-wa">
                    <i class="bi bi-whatsapp"></i> Chat WhatsApp
                </a>
                <a href="<?php echo page_url('produk.php'); ?>" class="rip-btn rip-btn-pink">
                    <i class="bi bi-cart-check"></i> Mulai Pesan
                </a>
            </div>
        </div>
    </div>
</main>

<style>
.rip-info-page {
    padding: 28px 0 70px;
}
.rip-section-head {
    max-width: 680px;
    margin: 0 auto 32px;
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
.rip-steps-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 26px;
}
.rip-step-card {
    background: #ffffff;
    border: 1px solid #f2dce7;
    border-radius: 18px;
    padding: 22px 20px;
    box-shadow: 0 8px 24px rgba(214, 74, 138, 0.05);
    display: flex;
    flex-direction: column;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.rip-step-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 28px rgba(214, 74, 138, 0.1);
}
.rip-step-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
}
.rip-step-num {
    font-size: 12px;
    font-weight: 800;
    color: #e11d64;
    background: #fff0f6;
    border: 1px solid #f5c6da;
    padding: 4px 10px;
    border-radius: 999px;
}
.rip-step-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #e11d64 0%, #f43f7e 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 19px;
    box-shadow: 0 6px 14px rgba(225, 29, 100, 0.22);
}
.rip-step-card h3 {
    font-size: 16px;
    font-weight: 700;
    color: #18243a;
    margin: 0 0 8px;
    line-height: 1.35;
}
.rip-step-card p {
    font-size: 13px;
    color: #5f5862;
    line-height: 1.6;
    margin: 0;
}
.rip-step-card a {
    color: #e11d64;
    font-weight: 600;
    text-decoration: none;
}
.rip-step-card code {
    color: #e11d64;
    background: #fff0f6;
    padding: 2px 6px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
}
.rip-guide-extras {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 28px;
}
.rip-guide-box {
    background: #fff9fc;
    border: 1px solid #f2dce7;
    border-radius: 16px;
    padding: 18px 20px;
    display: flex;
    align-items: flex-start;
    gap: 14px;
}
.rip-guide-box-icon {
    width: 42px;
    height: 42px;
    flex: 0 0 42px;
    border-radius: 11px;
    background: #fff0f6;
    color: #e11d64;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}
.rip-guide-box h4 {
    font-size: 14.5px;
    font-weight: 700;
    color: #18243a;
    margin: 0 0 4px;
}
.rip-guide-box p {
    font-size: 12.5px;
    color: #665e68;
    line-height: 1.55;
    margin: 0;
}
.rip-cta-banner {
    background: linear-gradient(135deg, #fff0f6 0%, #ffe4ef 100%);
    border: 1px solid #f3c4d8;
    border-radius: 20px;
    padding: 24px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
}
.rip-cta-text {
    flex: 1 1 320px;
}
.rip-cta-text h3 {
    font-size: 19px;
    font-weight: 800;
    color: #18243a;
    margin: 0 0 4px;
}
.rip-cta-text p {
    font-size: 13px;
    color: #635862;
    margin: 0;
}
.rip-cta-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.rip-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 11px 20px;
    border-radius: 999px;
    font-size: 13.5px;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.2s ease;
    white-space: nowrap;
}
.rip-btn-pink {
    background: linear-gradient(90deg, #e11d64 0%, #f43f7e 50%, #fb71a0 100%);
    color: #ffffff;
    box-shadow: 0 6px 18px rgba(225, 29, 100, 0.25);
}
.rip-btn-pink:hover {
    background: linear-gradient(90deg, #be124f 0%, #e11d64 50%, #f43f7e 100%);
    color: #ffffff;
    transform: translateY(-1px);
}
.rip-btn-wa {
    background: #16a34a;
    color: #ffffff;
    box-shadow: 0 6px 16px rgba(22, 163, 74, 0.22);
}
.rip-btn-wa:hover {
    background: #15803d;
    color: #ffffff;
    transform: translateY(-1px);
}

@media (max-width: 991.98px) {
    .rip-steps-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }
}

@media (max-width: 767.98px) {
    .rip-info-page {
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
    .rip-steps-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 16px;
    }
    .rip-step-card {
        padding: 13px 11px;
        border-radius: 14px;
    }
    .rip-step-top {
        margin-bottom: 9px;
    }
    .rip-step-num {
        font-size: 10px;
        padding: 2px 8px;
    }
    .rip-step-icon {
        width: 34px;
        height: 34px;
        border-radius: 9px;
        font-size: 15px;
    }
    .rip-step-card h3 {
        font-size: 12.5px;
        margin-bottom: 5px;
        line-height: 1.25;
    }
    .rip-step-card p {
        font-size: 10.5px;
        line-height: 1.45;
    }
    .rip-step-card code {
        font-size: 9.5px;
        padding: 1px 4px;
    }
    .rip-guide-extras {
        grid-template-columns: 1fr;
        gap: 10px;
        margin-bottom: 16px;
    }
    .rip-guide-box {
        padding: 13px 14px;
        border-radius: 13px;
        gap: 11px;
    }
    .rip-guide-box-icon {
        width: 36px;
        height: 36px;
        flex-basis: 36px;
        font-size: 17px;
    }
    .rip-guide-box h4 {
        font-size: 12.5px;
        margin-bottom: 2px;
    }
    .rip-guide-box p {
        font-size: 11px;
        line-height: 1.45;
    }
    .rip-cta-banner {
        padding: 16px 15px;
        border-radius: 15px;
        text-align: center;
        gap: 12px;
    }
    .rip-cta-text h3 {
        font-size: 15px;
    }
    .rip-cta-text p {
        font-size: 11px;
        line-height: 1.45;
    }
    .rip-cta-actions {
        width: 100%;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }
    .rip-btn {
        width: 100%;
        padding: 10px 10px;
        font-size: 11.5px;
        gap: 5px;
    }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
