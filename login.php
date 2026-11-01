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
        // Fallback jika tabel belum siap
    }
}

if (customer_logged_in()) {
    header('Location: ' . page_url('akun.php'));
    exit;
}

$error = '';
$phone = '';
$flash = function_exists('customer_flash_get') ? customer_flash_get() : null;
$notice = $flash ? (string)($flash[1] ?? '') : '';
if (isset($_GET['registered'])) {
    $notice = 'Pendaftaran akun Member berhasil! Silakan masuk menggunakan nomor WhatsApp dan password Anda.';
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    [$phoneNorm, $phoneLocal] = customer_phone_candidates($phone);

    if ($phoneNorm === '') {
        $error = 'Nomor WhatsApp wajib diisi.';
    } elseif ($password === '') {
        $error = 'Password wajib diisi.';
    } elseif ($pdo) {
        try {
            $stmt = $pdo->prepare('SELECT * FROM customers WHERE phone = ? OR phone = ? LIMIT 1');
            $stmt->execute([$phoneNorm, $phoneLocal]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($customer) {
                $storedPass = (string)($customer['password'] ?? '');
                $isValidPassword = false;

                if ($storedPass !== '' && password_verify($password, $storedPass)) {
                    $isValidPassword = true;
                } elseif ($storedPass !== '' && hash_equals($storedPass, $password)) {
                    // Fallback jika password dimasukkan manual lewat phpMyAdmin (belum di-hash)
                    $isValidPassword = true;
                    try {
                        $newHash = password_hash($password, PASSWORD_DEFAULT);
                        $pdo->prepare('UPDATE customers SET password = ? WHERE id = ?')->execute([$newHash, (int)$customer['id']]);
                    } catch (Throwable $e) {}
                }

                if ($isValidPassword) {
                    if ((int)($customer['is_verified'] ?? 1) !== 1) {
                        try {
                            $pdo->prepare('UPDATE customers SET is_verified = 1 WHERE id = ?')->execute([(int)$customer['id']]);
                            $customer['is_verified'] = 1;
                        } catch (Throwable $e) {}
                    }
                    customer_login($customer);
                    header('Location: ' . page_url('akun.php'));
                    exit;
                } else {
                    $error = 'Password yang Anda masukkan salah.';
                }
            } else {
                $error = 'Nomor WhatsApp belum terdaftar sebagai Member. Silakan daftar terlebih dahulu.';
            }
        } catch (Throwable $e) {
            $error = 'Terjadi kesalahan saat memverifikasi akun.';
        }
    } else {
        // Fallback demo jika DB belum terhubung
        $demoCustomer = [
            'id'          => 1,
            'name'        => 'Member Rosa Indah',
            'username'    => 'memberrosa',
            'phone'       => $phoneLocal ?: $phoneNorm,
            'email'       => '',
            'address'     => 'Pasuruan, Jawa Timur',
            'city'        => 'Pasuruan',
            'points'      => 150,
            'member_tier' => 'Member Prioritas'
        ];
        customer_login($demoCustomer);
        header('Location: ' . page_url('akun.php'));
        exit;
    }
}

$title = 'Login Member — Rosa Indah Printing';
include __DIR__ . '/includes/header.php';
?>

<style>
.auth-member-section {
    background: linear-gradient(180deg, #fff6fa 0%, #fbf8fa 100%);
    min-height: 82vh;
    padding: 40px 0 70px;
}
.auth-member-card {
    max-width: 480px;
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
.auth-input-wrap input {
    width: 100%;
    padding: 12px 42px 12px 42px;
    border-radius: 13px;
    border: 1.5px solid #ead8e1;
    font-size: 14px;
    color: #18243a;
    background: #ffffff;
    transition: all 0.2s ease;
    outline: none;
}
.auth-input-wrap input:focus {
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
                    <i class="bi bi-person-lock"></i>
                </div>
                <span class="auth-badge"><i class="bi bi-shield-check"></i> Area Member Pelanggan</span>
                <h2 class="fw-bold mb-1" style="color: #18243a; font-size: 23px;">Login Member</h2>
                <p class="text-muted small mb-0">Masuk untuk melihat riwayat pesanan, klaim voucher khusus, dan mengumpulkan poin loyalty.</p>
            </div>

            <?php if (!empty($notice)): ?>
                <div class="alert alert-success rounded-3 small fw-semibold" role="alert">
                    <i class="bi bi-check-circle-fill me-1"></i> <?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger rounded-3 small fw-semibold" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo page_url('login.php'); ?>" method="POST">
                <div class="mb-3">
                    <label for="login_phone" class="auth-form-label">Nomor WhatsApp Terdaftar <span class="text-danger">*</span></label>
                    <div class="auth-input-wrap">
                        <i class="bi bi-whatsapp field-icon text-success"></i>
                        <input type="tel" id="login_phone" name="phone" value="<?php echo htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'); ?>" required placeholder="Contoh: 082331035371" autocomplete="tel">
                    </div>
                </div>
                <div class="mb-4">
                    <label for="login_password" class="auth-form-label">Password <span class="text-danger">*</span></label>
                    <div class="auth-input-wrap">
                        <i class="bi bi-key field-icon"></i>
                        <input type="password" id="login_password" name="password" required placeholder="Masukkan password akun Anda" autocomplete="current-password">
                        <button type="button" class="btn-toggle-pass" onclick="const p=document.getElementById('login_password'); const i=this.querySelector('i'); if(p.type==='password'){p.type='text';i.className='bi bi-eye-slash';}else{p.type='password';i.className='bi bi-eye';}" aria-label="Tampilkan password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-auth-submit mb-3">
                    <i class="bi bi-box-arrow-in-right"></i> Masuk ke Akun Member
                </button>
            </form>

            <div class="text-center pt-3 border-top" style="font-size: 13.5px; color: #6b7280;">
                Belum punya akun member?
                <a href="<?php echo page_url('daftar.php'); ?>" style="color: #e11d64; font-weight: 800; text-decoration: none;">Daftar Member Baru</a>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
