<?php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/customer-auth.php';

$pdo = db();
if ($pdo) {
    try {
        customer_auth_init($pdo);
    } catch (Throwable $e) {
    }
}

if (customer_logged_in()) {
    header('Location: ' . page_url('akun.php'));
    exit;
}

$error = '';
$name = '';
$phone = '';
$email = '';
$city = 'Pasuruan';
$address = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $city = trim($_POST['city'] ?? 'Pasuruan') ?: 'Pasuruan';
    $address = trim($_POST['address'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    [$phoneNorm, $phoneLocal] = customer_phone_candidates($phone);

    if ($name === '' || $phoneNorm === '') {
        $error = 'Nama lengkap dan nomor WhatsApp wajib diisi.';
    } elseif (mb_strlen($password) < 6) {
        $error = 'Password minimal terdiri dari 6 karakter.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format alamat email tidak valid.';
    } elseif ($pdo) {
        try {
            $stmt = $pdo->prepare('SELECT id FROM customers WHERE phone = ? OR phone = ? LIMIT 1');
            $stmt->execute([$phoneNorm, $phoneLocal]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $error = 'Nomor WhatsApp sudah terdaftar sebagai Member. Silakan login menggunakan nomor tersebut.';
            } else {
                $emailVal = $email !== '' ? $email : null;
                $usernameVal = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode(' ', $name)[0] ?? 'member')) ?: 'member';
                $hash = password_hash($password, PASSWORD_DEFAULT);

                try {
                    $stmt = $pdo->prepare('INSERT INTO customers (name, username, phone, email, password, city, address, points, member_tier, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, 100, ?, 1)');
                    $stmt->execute([$name, $usernameVal, $phoneNorm, $emailVal, $hash, $city, $address, 'Member Baru']);
                } catch (Throwable $innerEx) {
                    // Fallback jika tabel menggunakan kolom standar awal
                    $stmt = $pdo->prepare('INSERT INTO customers (name, phone, email, password, address, is_verified) VALUES (?, ?, ?, ?, ?, 1)');
                    $stmt->execute([$name, $phoneNorm, $emailVal, $hash, $address]);
                }

                $newId = (int)$pdo->lastInsertId();
                $stmtUser = $pdo->prepare('SELECT * FROM customers WHERE id = ? LIMIT 1');
                $stmtUser->execute([$newId]);
                $customer = $stmtUser->fetch(PDO::FETCH_ASSOC);
                if ($customer) {
                    customer_login($customer);
                } else {
                    customer_login([
                        'id'          => $newId ?: time(),
                        'name'        => $name,
                        'username'    => $usernameVal,
                        'phone'       => $phoneNorm,
                        'email'       => $email,
                        'city'        => $city,
                        'address'     => $address,
                        'points'      => 100,
                        'member_tier' => 'Member Baru',
                        'is_verified' => 1,
                    ]);
                }
                header('Location: ' . page_url('akun.php?welcome=1'));
                exit;
            }
        } catch (Throwable $e) {
            $error = 'Terjadi kesalahan saat mendaftarkan akun. Silakan coba kembali.';
        }
    } else {
        $newCustomer = [
            'id'          => time(),
            'name'        => $name,
            'username'    => strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode(' ', $name)[0] ?? 'member')) ?: 'member',
            'phone'       => $phoneNorm,
            'email'       => $email,
            'address'     => $address,
            'city'        => $city,
            'points'      => 100,
            'member_tier' => 'Member Baru',
            'is_verified' => 1,
        ];
        customer_login($newCustomer);
        header('Location: ' . page_url('akun.php?welcome=1'));
        exit;
    }
}

$pageTitle = 'Daftar Member — Rosa Indah Printing';
include __DIR__ . '/includes/header.php';
?>

<style>
.auth-member-section {
    background: linear-gradient(180deg, #fff6fa 0%, #fbf8fa 100%);
    min-height: 84vh;
    padding: 36px 0 70px;
}
.auth-member-card {
    max-width: 520px;
    margin: 0 auto;
    background: #ffffff;
    border: 1.5px solid #f2dce7;
    border-radius: 24px;
    padding: 34px 30px;
    box-shadow: 0 14px 40px rgba(225, 29, 100, 0.08);
}
.auth-icon-circle {
    width: 68px;
    height: 68px;
    border-radius: 50%;
    background: linear-gradient(135deg, #fff0f6 0%, #ffe4ed 100%);
    color: #e11d64;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
    margin: 0 auto 14px;
    border: 2px solid #fbcfe8;
    box-shadow: 0 8px 20px rgba(225, 29, 100, 0.14);
}
.auth-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #fff0f6;
    color: #e11d64;
    border: 1px solid #fbcfe8;
    font-size: 11px;
    font-weight: 800;
    padding: 4px 12px;
    border-radius: 999px;
    letter-spacing: 0.4px;
    text-transform: uppercase;
    margin-bottom: 8px;
}
.auth-form-label {
    font-size: 13px;
    font-weight: 700;
    color: #18243a;
    margin-bottom: 6px;
    display: block;
}
.auth-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}
.auth-input-wrap i.field-icon {
    position: absolute;
    left: 14px;
    color: #9ca3af;
    font-size: 17px;
    pointer-events: none;
}
.auth-input-wrap input,
.auth-textarea {
    width: 100%;
    padding: 12px 14px 12px 42px;
    border-radius: 13px;
    border: 1.5px solid #ead8e1;
    font-size: 14px;
    color: #18243a;
    background: #ffffff;
    transition: all 0.2s ease;
    outline: none;
}
.auth-textarea {
    padding: 12px 14px;
}
.auth-input-wrap input:focus,
.auth-textarea:focus {
    border-color: #e11d64;
    box-shadow: 0 0 0 3px rgba(225, 29, 100, 0.12);
}
.btn-toggle-pass {
    position: absolute;
    right: 10px;
    background: transparent;
    border: 0;
    color: #9ca3af;
    padding: 4px 6px;
    cursor: pointer;
    font-size: 17px;
}
.btn-auth-submit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 13px 22px;
    border: 0;
    border-radius: 999px;
    background: linear-gradient(90deg, #e11d64 0%, #f43f7e 50%, #fb71a0 100%);
    color: #ffffff !important;
    font-size: 14.5px;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 10px 26px -4px rgba(225, 29, 100, 0.4);
    transition: all 0.25s ease;
}
.btn-auth-submit:hover {
    background: linear-gradient(90deg, #be124f 0%, #e11d64 50%, #f43f7e 100%);
    transform: translateY(-2px);
}
@media (max-width: 575.98px) {
    .auth-member-section {
        padding: 20px 0 60px;
    }
    .auth-member-card {
        padding: 26px 20px;
        border-radius: 20px;
    }
}
</style>

<header class="main-header">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
</header>

<main class="auth-member-section">
    <div class="container">
        <div class="auth-member-card">
            <div class="text-center mb-4">
                <div class="auth-icon-circle">
                    <i class="bi bi-person-plus"></i>
                </div>
                <span class="auth-badge"><i class="bi bi-stars"></i> Gratis &amp; Bonus 100 Poin</span>
                <h2 class="fw-bold mb-1" style="color: #18243a; font-size: 23px;">Pendaftaran Member</h2>
                <p class="text-muted small mb-0">Dapatkan keuntungan promo eksklusif, poin loyalitas, dan pantau pesanan cetak lebih cepat.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger rounded-3 small fw-semibold" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo page_url('daftar.php'); ?>" method="POST">
                <div class="mb-3">
                    <label for="name" class="auth-form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <div class="auth-input-wrap">
                        <i class="bi bi-person field-icon"></i>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" required placeholder="Contoh: Budi Santoso">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="phone" class="auth-form-label">Nomor WhatsApp Aktif <span class="text-danger">*</span></label>
                    <div class="auth-input-wrap">
                        <i class="bi bi-whatsapp field-icon text-success"></i>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'); ?>" required placeholder="Contoh: 082331035371">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="email" class="auth-form-label">Alamat Email (Opsional)</label>
                    <div class="auth-input-wrap">
                        <i class="bi bi-envelope field-icon"></i>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" placeholder="email@contoh.com">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="password" class="auth-form-label">Buat Password <span class="text-danger">*</span></label>
                    <div class="auth-input-wrap">
                        <i class="bi bi-key field-icon"></i>
                        <input type="password" id="password" name="password" required minlength="6" placeholder="Minimal 6 karakter" style="padding-right: 42px;">
                        <button type="button" class="btn-toggle-pass" onclick="const p=document.getElementById('password'); const i=this.querySelector('i'); if(p.type==='password'){p.type='text';i.className='bi bi-eye-slash';}else{p.type='password';i.className='bi bi-eye';}" aria-label="Tampilkan password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="address" class="auth-form-label">Alamat Lengkap Pengiriman</label>
                    <textarea class="auth-textarea" id="address" name="address" rows="2" placeholder="Nama jalan, nomor rumah, RT/RW, desa/kelurahan, kecamatan"><?php echo htmlspecialchars($address, ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <button type="submit" class="btn-auth-submit mb-3">
                    <i class="bi bi-check2-circle"></i> Daftar Akun Member Sekarang
                </button>
            </form>

            <div class="text-center pt-3 border-top" style="font-size: 13.5px; color: #6b7280;">
                Sudah punya akun member?
                <a href="<?php echo page_url('login.php'); ?>" style="color: #e11d64; font-weight: 800; text-decoration: none;">Masuk di sini</a>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
