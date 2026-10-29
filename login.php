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

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $phoneNorm = customer_normalize_phone($phone);

    if ($phoneNorm === '') {
        $error = 'Nomor WhatsApp wajib diisi.';
    } elseif ($pdo) {
        try {
            $stmt = $pdo->prepare('SELECT * FROM customers WHERE phone = ? LIMIT 1');
            $stmt->execute([$phoneNorm]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($customer && password_verify($password, $customer['password'] ?? '')) {
                if ((int)($customer['is_verified'] ?? 1) !== 1 && function_exists('customer_pending_set')) {
                    customer_pending_set((int)$customer['id'], (string)$customer['phone']);
                    header('Location: ' . page_url('verifikasi-member.php'));
                    exit;
                } else {
                    customer_login($customer);
                    header('Location: ' . page_url('akun.php'));
                    exit;
                }
            } else {
                $error = 'Nomor WhatsApp atau password salah.';
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
            'phone'       => $phoneNorm,
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

<header class="main-header">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
</header>

<main class="py-5">
    <div class="container">
        <div class="card p-4 p-md-5 rounded-4 shadow-sm border-0 max-w-500 mx-auto bg-white">
            <div class="text-center mb-4">
                <div class="rounded-circle bg-light text-primary mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px; font-size: 28px;">
                    <i class="bi bi-person-lock"></i>
                </div>
                <h2 class="fw-bold">Login Member</h2>
                <p class="text-muted small">Masuk untuk melihat riwayat pesanan, voucher khusus, dan mengumpulkan poin loyalty.</p>
            </div>

            <?php if (!empty($notice)): ?>
                <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form action="<?php echo page_url('login.php'); ?>" method="POST">
                <div class="mb-3">
                    <label for="login_phone" class="form-label fw-semibold">Nomor WhatsApp Terdaftar</label>
                    <input type="tel" class="form-control" id="login_phone" name="phone" value="<?php echo htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'); ?>" required placeholder="082331035371">
                </div>
                <div class="mb-3">
                    <label for="login_password" class="form-label fw-semibold">Password</label>
                    <input type="password" class="form-control" id="login_password" name="password" placeholder="Masukkan password Anda">
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold mb-3">Masuk ke Akun</button>
            </form>

            <div class="text-center small text-muted">
                Belum punya akun member? <a href="<?php echo page_url('daftar.php'); ?>" class="text-primary fw-semibold">Daftar sekarang</a>
            </div>
        </div>
    </div>
</main>

<style>
.max-w-500 { max-width: 500px; }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
