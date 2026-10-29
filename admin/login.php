<?php
require_once __DIR__ . '/../config/auth.php';

if (admin_logged_in()) {
    header('Location: ' . page_url('admin/dashboard.php'));
    exit;
}

$error = '';
$loginCsrf = app_csrf_token();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $csrf = (string)($_POST['csrf_token'] ?? '');

    if (!app_verify_csrf($csrf)) {
        $error = 'Sesi login tidak valid. Silakan muat ulang halaman.';
    } elseif (admin_login_is_throttled()) {
        $error = 'Terlalu banyak percobaan login. Silakan tunggu sekitar 10 menit lalu coba lagi.';
    } else {
        $pdo = db();

        if ($pdo) {
            try {
                $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
                $stmt->execute([$username]);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($admin && password_verify($password, $admin['password'] ?? '')) {
                    session_regenerate_id(true);
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_name'] = $admin['name'] ?? 'Administrator Rosa Indah';
                    $_SESSION['admin_username'] = $admin['username'];
                    admin_login_clear_throttle();
                    header('Location: ' . page_url('admin/dashboard.php'));
                    exit;
                } else {
                    admin_login_record_failure();
                    usleep(350000);
                    $error = 'Username atau password salah. Akses khusus Administrator Rosa Indah Printing.';
                }
            } catch (Throwable $e) {
                $error = 'Gagal memproses login ke database.';
            }
        } else {
            // Fallback lokal apabila database belum terhubung
            if ($username === 'admin' && ($password === 'admin' || $password === 'admin123')) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = 1;
                $_SESSION['admin_name'] = 'Administrator Rosa Indah';
                $_SESSION['admin_username'] = 'admin';
                admin_login_clear_throttle();
                header('Location: ' . page_url('admin/dashboard.php'));
                exit;
            } else {
                admin_login_record_failure();
                usleep(350000);
                $error = 'Username atau password salah. Akses khusus Administrator Rosa Indah Printing.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrator - Rosa Indah Printing</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #fff1f7 0%, #ffffff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
        }
        .login-card {
            background: #ffffff;
            width: 100%;
            max-width: 420px;
            border-radius: 24px;
            padding: 38px 34px;
            box-shadow: 0 20px 60px rgba(214, 74, 138, 0.12);
            border: 1px solid #f2dce7;
        }
        .login-brand {
            font-size: 19px;
            font-weight: 800;
            color: #18243a;
            margin: 0 0 4px;
        }
        .login-brand span {
            color: #e11d64;
        }
        .form-control:focus {
            border-color: #e11d64;
            box-shadow: 0 0 0 3px #ffe8f1;
        }
        .admin-login-btn {
            background: linear-gradient(90deg, #e11d64 0%, #f43f7e 50%, #fb71a0 100%);
            color: #ffffff;
            border: 0;
            border-radius: 12px;
            padding: 12px;
            font-weight: 700;
            font-size: 14px;
            width: 100%;
            transition: all 0.2s ease;
            box-shadow: 0 6px 18px rgba(225, 29, 100, 0.22);
        }
        .admin-login-btn:hover {
            background: linear-gradient(90deg, #be124f 0%, #e11d64 50%, #f43f7e 100%);
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 8px 22px rgba(225, 29, 100, 0.3);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <div class="mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; border-radius: 16px; background: #fff0f6; color: #e11d64; font-size: 26px;">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <h1 class="login-brand">ROSA INDAH <span>PRINTING</span></h1>
            <p class="text-muted small mb-0">Panel Kontrol Operasional Administrator</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 small mb-3" style="border-radius: 10px; background: #fff0f0; border: 1px solid #f7d2d2; color: #a33b3b;" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo page_url('admin/login.php'); ?>" method="POST" autocomplete="on">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($loginCsrf, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="mb-3">
                <label for="username" class="form-label fw-bold small text-secondary">Username Administrator</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted border-end-0" style="border-radius: 11px 0 0 11px;"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" id="username" name="username" required placeholder="Masukkan username admin" autocomplete="username" style="border-radius: 0 11px 11px 0;">
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label fw-bold small text-secondary">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted border-end-0" style="border-radius: 11px 0 0 11px;"><i class="bi bi-key"></i></span>
                    <input type="password" class="form-control" id="password" name="password" required placeholder="Masukkan password admin" autocomplete="current-password" style="border-radius: 0 11px 11px 0;">
                </div>
            </div>

            <button type="submit" class="admin-login-btn">
                Masuk ke Dashboard Admin <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </form>

        <div class="text-center mt-4 pt-3 border-top small">
            <a href="<?php echo page_url('index.php'); ?>" class="text-decoration-none fw-semibold" style="color: #8b8087;">
                <i class="bi bi-arrow-left"></i> Kembali ke Website Publik
            </a>
        </div>
    </div>
</body>
</html>