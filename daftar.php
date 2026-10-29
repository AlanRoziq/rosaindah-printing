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
$address = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = (string)($_POST['password'] ?? '123456');
    $phoneNorm = customer_normalize_phone($phone);

    if ($name === '' || $phoneNorm === '') {
        $error = 'Nama lengkap dan nomor WhatsApp wajib diisi.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif ($pdo) {
        try {
            $stmt = $pdo->prepare('SELECT id, is_verified FROM customers WHERE phone = ? LIMIT 1');
            $stmt->execute([$phoneNorm]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $error = 'Nomor WhatsApp sudah terdaftar sebagai Member. Silakan login.';
            } else {
                $emailVal = $email !== '' ? $email : null;
                $hash = password_hash($password !== '' ? $password : '123456', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO customers (name, phone, email, password, address, is_verified) VALUES (?, ?, ?, ?, ?, 1)');
                $stmt->execute([$name, $phoneNorm, $emailVal, $hash, $address]);
                $newId = (int)$pdo->lastInsertId();

                $stmtUser = $pdo->prepare('SELECT * FROM customers WHERE id = ? LIMIT 1');
                $stmtUser->execute([$newId]);
                $customer = $stmtUser->fetch(PDO::FETCH_ASSOC);
                if ($customer) {
                    customer_login($customer);
                }
                header('Location: ' . page_url('akun.php'));
                exit;
            }
        } catch (Throwable $e) {
            $error = 'Terjadi kesalahan saat mendaftarkan akun.';
        }
    } else {
        $newCustomer = [
            'id'          => time(),
            'name'        => $name,
            'username'    => strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name)) ?: 'member',
            'phone'       => $phoneNorm,
            'email'       => $email,
            'address'     => $address,
            'city'        => 'Pasuruan',
            'points'      => 100,
            'member_tier' => 'Member Baru',
        ];
        customer_login($newCustomer);
        header('Location: ' . page_url('akun.php'));
        exit;
    }
}

$pageTitle = 'Daftar Member';
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
                    <i class="bi bi-person-plus"></i>
                </div>
                <h2 class="fw-bold">Pendaftaran Member</h2>
                <p class="text-muted small">Dapatkan keuntungan promo eksklusif, cashback poin, dan tracking pesanan cepat.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form action="<?php echo page_url('daftar.php'); ?>" method="POST">
                <div class="mb-3">
                    <label for="name" class="form-label fw-semibold">Nama Lengkap</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" required placeholder="Contoh: Budi Santoso">
                </div>
                <div class="mb-3">
                    <label for="phone" class="form-label fw-semibold">Nomor WhatsApp</label>
                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'); ?>" required placeholder="082331035371">
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">Email (Opsional)</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" placeholder="email@contoh.com">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Minimal 6 karakter">
                </div>
                <div class="mb-3">
                    <label for="address" class="form-label fw-semibold">Alamat Lengkap</label>
                    <textarea class="form-control" id="address" name="address" rows="2" placeholder="Alamat domisili"><?php echo htmlspecialchars($address, ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold mb-3">Daftar Akun Member</button>
            </form>

            <div class="text-center small text-muted">
                Sudah punya akun? <a href="<?php echo page_url('login.php'); ?>" class="text-primary fw-semibold">Masuk di sini</a>
            </div>
        </div>
    </div>
</main>

<style>
.max-w-500 { max-width: 500px; }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
