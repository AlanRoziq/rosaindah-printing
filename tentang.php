<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Tentang Kami';
include __DIR__ . '/includes/header.php';
?>

<header class="main-header">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
</header>

<main class="py-5">
    <div class="container">
        <!-- Breadcrumb -->
        <div class="catalog-breadcrumb mb-4">
            <a href="<?php echo page_url('index.php'); ?>">Home</a>
            <i class="bi bi-chevron-right"></i>
            <span>Tentang Kami</span>
        </div>

        <div class="card p-4 p-md-5 rounded-4 shadow-sm border-0 mb-5">
            <div class="row g-5 align-items-center">
                <div class="col-lg-6">
                    <span class="badge bg-primary px-3 py-2 mb-2">Profil Perusahaan</span>
                    <h1 class="display-6 fw-bold mb-3">Tentang <span class="text-primary">Rosa Indah Printing</span></h1>
                    <p class="text-muted leading-relaxed">
                        Rosa Indah Printing adalah unit usaha percetakan digital dan offset profesional yang berlokasi di Regek, Sambirejo, Rejoso. Kami hadir untuk membantu para pelaku usaha, institusi pendidikan, organisasi, hingga perorangan dalam mewujudkan media promosi dan kebutuhan cetak berkualitas tinggi.
                    </p>
                    <p class="text-muted leading-relaxed">
                        Dengan moto <strong>"Cetak Ide Jadi Kenyataan"</strong>, kami berkomitmen menghadirkan ketepatan warna, ketajaman detail, bahan cetak pilihan, serta pelayanan yang bersahabat dan tepat waktu.
                    </p>

                    <div class="row g-3 mt-4">
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <h3 class="fw-bold text-primary mb-0">10+</h3>
                                <small class="text-muted">Tahun Berpengalaman</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <h3 class="fw-bold text-primary mb-0">25.000+</h3>
                                <small class="text-muted">Produk Dicetak</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 text-center">
                    <img src="<?php echo asset_url('images/hero/mockup-printing.png'); ?>" alt="Workshop Rosa Indah Printing" class="img-fluid rounded-4 shadow-sm" style="max-height: 400px;">
                </div>
            </div>
        </div>

        <!-- Workshop Info -->
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card p-4 rounded-4 shadow-sm border-0 h-100 text-center">
                    <div class="rounded-circle bg-light text-primary mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; font-size: 24px;">
                        <i class="bi bi-geo-alt-fill"></i>
                    </div>
                    <h5 class="fw-bold">Lokasi Workshop</h5>
                    <p class="text-muted small">Regek, Sambirejo, Rejoso, Pasuruan, Jawa Timur.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-4 rounded-4 shadow-sm border-0 h-100 text-center">
                    <div class="rounded-circle bg-light text-primary mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; font-size: 24px;">
                        <i class="bi bi-clock-fill"></i>
                    </div>
                    <h5 class="fw-bold">Jam Operasional</h5>
                    <p class="text-muted small">Senin - Sabtu: 08:00 - 21:00 WIB<br>Minggu: Konsultasi Online via WA</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-4 rounded-4 shadow-sm border-0 h-100 text-center">
                    <div class="rounded-circle bg-light text-primary mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; font-size: 24px;">
                        <i class="bi bi-headset"></i>
                    </div>
                    <h5 class="fw-bold">Layanan Pelanggan</h5>
                    <p class="text-muted small">WhatsApp: 0823-3103-5371<br>Email: rosaindahprinting@gmail.com</p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
