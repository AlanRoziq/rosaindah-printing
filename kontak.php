<?php
require_once __DIR__ . '/config/config.php';

$waNumber = defined('WHATSAPP_NUMBER') ? WHATSAPP_NUMBER : '6282331035371';
$success = !empty($_GET['sent']);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    header('Location: ' . page_url('kontak.php?sent=1'));
    exit;
}

$pageTitle = 'Kontak Kami';
include __DIR__ . '/includes/header.php';
?>

<header class="main-header">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
</header>

<main class="rip-contact-page">
    <div class="container">
        <!-- Breadcrumb -->
        <div class="catalog-breadcrumb mb-3">
            <a href="<?php echo page_url('index.php'); ?>">Home</a>
            <i class="bi bi-chevron-right"></i>
            <span>Kontak Kami</span>
        </div>

        <div class="section-title text-center rip-section-head">
            <span>Hubungi Percetakan</span>
            <h2>Kontak & Informasi Layanan</h2>
            <p class="text-muted">Punya pertanyaan seputar ukuran, bahan kertas, finishing, atau butuh penawaran cetak partai besar? Hubungi kami sekarang.</p>
        </div>

        <?php if ($success): ?>
            <div class="rip-contact-alert">
                <i class="bi bi-check-circle-fill"></i>
                <span>Pesan Anda berhasil dikirim! Tim customer service kami akan segera merespon melalui WhatsApp atau email.</span>
            </div>
        <?php endif; ?>

        <div class="rip-contact-layout">
            <!-- Kolom Informasi Kontak -->
            <div class="rip-contact-info-col">
                <div class="rip-contact-card">
                    <div class="rip-contact-card-head">
                        <h3>Informasi Workshop & CS</h3>
                        <p>Klik salah satu kanal di bawah untuk terhubung langsung dengan admin Rosa Indah Printing.</p>
                    </div>

                    <div class="rip-contact-items">
                        <div class="rip-contact-item">
                            <div class="rip-contact-icon"><i class="bi bi-geo-alt-fill"></i></div>
                            <div class="rip-contact-detail">
                                <span class="rip-contact-label">Alamat Percetakan</span>
                                <strong>Regek, Sambirejo, Rejoso</strong>
                                <small>Pasuruan, Jawa Timur</small>
                            </div>
                        </div>

                        <a href="https://wa.me/<?php echo rawurlencode($waNumber); ?>" target="_blank" rel="noopener" class="rip-contact-item is-link">
                            <div class="rip-contact-icon wa"><i class="bi bi-whatsapp"></i></div>
                            <div class="rip-contact-detail">
                                <span class="rip-contact-label">WhatsApp / Telepon</span>
                                <strong>0823-3103-5371</strong>
                                <small>Respon cepat & kirim file desain</small>
                            </div>
                        </a>

                        <a href="mailto:rosaindahprinting@gmail.com" class="rip-contact-item is-link">
                            <div class="rip-contact-icon email"><i class="bi bi-envelope-fill"></i></div>
                            <div class="rip-contact-detail">
                                <span class="rip-contact-label">Email Resmi</span>
                                <strong>rosaindahprinting@gmail.com</strong>
                                <small>Pengiriman file ukuran besar</small>
                            </div>
                        </a>

                        <div class="rip-contact-item">
                            <div class="rip-contact-icon clock"><i class="bi bi-clock-fill"></i></div>
                            <div class="rip-contact-detail">
                                <span class="rip-contact-label">Waktu Pelayanan</span>
                                <strong>Senin - Sabtu: 08.00 - 21.00 WIB</strong>
                                <small>Minggu: Konsultasi Online via WA</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kolom Form Konsultasi -->
            <div class="rip-contact-form-col">
                <div class="rip-contact-card">
                    <div class="rip-contact-card-head">
                        <h3>Kirim Pesan Konsultasi</h3>
                        <p>Isi form berikut untuk berkonsultasi mengenai spesifikasi atau estimasi biaya cetak.</p>
                    </div>

                    <form action="<?php echo page_url('kontak.php'); ?>" method="POST" class="rip-contact-form" id="contactForm">
                        <div class="rip-form-row">
                            <div class="rip-form-group">
                                <label for="contact_name">Nama Lengkap *</label>
                                <input type="text" id="contact_name" name="name" required placeholder="Contoh: Rian Pratama">
                            </div>
                            <div class="rip-form-group">
                                <label for="contact_phone">Nomor WhatsApp *</label>
                                <input type="tel" id="contact_phone" name="phone" required placeholder="Contoh: 082331035371">
                            </div>
                        </div>

                        <div class="rip-form-group">
                            <label for="contact_msg">Pesan / Rencana Cetak *</label>
                            <textarea id="contact_msg" name="message" rows="4" required placeholder="Jelaskan kebutuhan cetak Anda, misalnya ukuran banner, jenis bahan, jumlah pesanan, dll."></textarea>
                        </div>

                        <div class="rip-contact-submit-row">
                            <button type="submit" class="rip-submit-btn">
                                <i class="bi bi-send-fill"></i> Kirim Pesan Sekarang
                            </button>
                            <a href="https://wa.me/<?php echo rawurlencode($waNumber); ?>" target="_blank" rel="noopener" class="rip-wa-direct-btn">
                                <i class="bi bi-whatsapp"></i> Chat Langsung via WA
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.rip-contact-page {
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
.rip-contact-alert {
    max-width: 960px;
    margin: 0 auto 20px;
    background: #eaf8ef;
    border: 1px solid #c7ebd3;
    color: #25764a;
    padding: 13px 16px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13.5px;
    font-weight: 600;
}
.rip-contact-layout {
    max-width: 980px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1.25fr;
    gap: 22px;
    align-items: stretch;
}
.rip-contact-card {
    background: #ffffff;
    border: 1px solid #f2dce7;
    border-radius: 20px;
    padding: 26px 24px;
    box-shadow: 0 10px 28px rgba(214, 74, 138, 0.05);
    height: 100%;
}
.rip-contact-card-head {
    margin-bottom: 20px;
    padding-bottom: 14px;
    border-bottom: 1px solid #f5e5ee;
}
.rip-contact-card-head h3 {
    font-size: 18px;
    font-weight: 800;
    color: #18243a;
    margin: 0 0 4px;
}
.rip-contact-card-head p {
    font-size: 12.5px;
    color: #746b73;
    margin: 0;
    line-height: 1.5;
}
.rip-contact-items {
    display: grid;
    grid-template-columns: 1fr;
    gap: 12px;
}
.rip-contact-item {
    display: flex;
    align-items: flex-start;
    gap: 13px;
    padding: 12px 14px;
    border-radius: 14px;
    background: #fff9fc;
    border: 1px solid #f3dfe9;
    text-decoration: none;
    color: inherit;
    transition: all 0.18s ease;
    min-width: 0;
}
.rip-contact-item.is-link:hover {
    background: #fff0f6;
    border-color: #e11d64;
    transform: translateY(-1px);
}
.rip-contact-icon {
    width: 42px;
    height: 42px;
    flex: 0 0 42px;
    border-radius: 12px;
    background: #fff0f6;
    color: #e11d64;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 19px;
}
.rip-contact-icon.wa {
    background: #eaf8ef;
    color: #16a34a;
}
.rip-contact-icon.email {
    background: #fff0f6;
    color: #e11d64;
}
.rip-contact-icon.clock {
    background: #fff6e5;
    color: #d97706;
}
.rip-contact-detail {
    min-width: 0;
    flex: 1 1 auto;
}
.rip-contact-label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    color: #8c828a;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}
.rip-contact-detail strong {
    display: block;
    font-size: 13.5px;
    font-weight: 700;
    color: #18243a;
    margin: 2px 0 1px;
    overflow-wrap: anywhere;
    word-break: break-word;
}
.rip-contact-detail small {
    display: block;
    font-size: 11.5px;
    color: #746b73;
}
.rip-contact-form {
    display: grid;
    gap: 15px;
}
.rip-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}
.rip-form-group label {
    display: block;
    font-size: 12.5px;
    font-weight: 700;
    color: #3d3740;
    margin-bottom: 6px;
}
.rip-form-group input,
.rip-form-group textarea {
    width: 100%;
    border: 1px solid #e9d7e0;
    border-radius: 12px;
    padding: 11px 14px;
    font-size: 13.5px;
    color: #18243a;
    background: #fffcfd;
    outline: none;
    transition: all 0.15s ease;
}
.rip-form-group input:focus,
.rip-form-group textarea:focus {
    border-color: #e11d64;
    background: #ffffff;
    box-shadow: 0 0 0 3px #ffe8f1;
}
.rip-contact-submit-row {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 4px;
}
.rip-submit-btn {
    flex: 1 1 200px;
    border: 0;
    border-radius: 12px;
    background: linear-gradient(90deg, #e11d64 0%, #f43f7e 50%, #fb71a0 100%);
    color: #ffffff;
    font-size: 13.5px;
    font-weight: 700;
    padding: 12px 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
    box-shadow: 0 6px 18px rgba(225, 29, 100, 0.24);
    transition: all 0.2s ease;
}
.rip-submit-btn:hover {
    background: linear-gradient(90deg, #be124f 0%, #e11d64 50%, #f43f7e 100%);
    transform: translateY(-1px);
}
.rip-wa-direct-btn {
    flex: 0 0 auto;
    border: 1px solid #c7ebd3;
    border-radius: 12px;
    background: #eaf8ef;
    color: #16a34a;
    font-size: 13px;
    font-weight: 700;
    padding: 12px 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    text-decoration: none;
    transition: all 0.2s ease;
}
.rip-wa-direct-btn:hover {
    background: #d6f3e0;
    color: #15803d;
}

@media (max-width: 991.98px) {
    .rip-contact-layout {
        grid-template-columns: 1fr;
        gap: 16px;
    }
}

@media (max-width: 767.98px) {
    .rip-contact-page {
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
    .rip-contact-card {
        padding: 16px 14px;
        border-radius: 16px;
    }
    .rip-contact-card-head {
        margin-bottom: 14px;
        padding-bottom: 10px;
    }
    .rip-contact-card-head h3 {
        font-size: 15px;
    }
    .rip-contact-card-head p {
        font-size: 11px;
    }
    .rip-contact-items {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 9px;
    }
    .rip-contact-item {
        flex-direction: column;
        gap: 8px;
        padding: 11px 10px;
        border-radius: 12px;
    }
    .rip-contact-icon {
        width: 34px;
        height: 34px;
        flex-basis: 34px;
        font-size: 16px;
        border-radius: 9px;
    }
    .rip-contact-label {
        font-size: 9.5px;
    }
    .rip-contact-detail strong {
        font-size: 11.5px;
        line-height: 1.3;
    }
    .rip-contact-detail small {
        font-size: 10px;
        line-height: 1.3;
    }
    .rip-form-row {
        grid-template-columns: 1fr;
        gap: 11px;
    }
    .rip-contact-form {
        gap: 11px;
    }
    .rip-form-group label {
        font-size: 11.5px;
        margin-bottom: 4px;
    }
    .rip-form-group input,
    .rip-form-group textarea {
        font-size: 12.5px;
        padding: 9px 12px;
        border-radius: 10px;
    }
    .rip-contact-submit-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 8px;
    }
    .rip-submit-btn,
    .rip-wa-direct-btn {
        width: 100%;
        padding: 11px 14px;
        font-size: 12px;
    }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
