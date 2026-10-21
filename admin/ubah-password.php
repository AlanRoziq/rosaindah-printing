<?php
require_once __DIR__ . '/../config/auth.php';
require_admin();

$pageTitle = 'Ubah Password';
$pdo = db();
$error = '';
$success = '';
$csrf = app_csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $current = (string)($_POST['current_password'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if (!app_verify_csrf($token)) {
        $error = 'Sesi tidak valid. Silakan muat ulang halaman.';
    } elseif (!$pdo) {
        $error = 'Database belum terhubung.';
    } elseif (strlen($new) < 10) {
        $error = 'Password baru minimal 10 karakter.';
    } elseif ($new !== $confirm) {
        $error = 'Konfirmasi password baru tidak sama.';
    } else {
        $admin = admin_user();
        $stmt = $pdo->prepare('SELECT password FROM admins WHERE id=? LIMIT 1');
        $stmt->execute([(int)$admin['id']]);
        $hash = (string)$stmt->fetchColumn();

        if (!$hash || !password_verify($current, $hash)) {
            $error = 'Password saat ini salah.';
        } else {
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE admins SET password=? WHERE id=?');
            $stmt->execute([$newHash, (int)$admin['id']]);
            $success = 'Password berhasil diubah. Simpan password baru Anda di tempat yang aman.';
        }
    }
}

require_once __DIR__ . '/_header.php';
?>
<div class="admin-title">
    <div>
        <h1>Ubah Password Admin</h1>
        <p style="color:#777;margin:7px 0 0">Gunakan fitur ini sebelum website dipublikasikan ke hosting.</p>
    </div>
</div>

<?php if ($error): ?><div class="admin-alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="admin-alert"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<div class="admin-card" style="max-width:650px">
    <form method="post" class="admin-form" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="admin-field">
            <label>Password Saat Ini</label>
            <input type="password" name="current_password" autocomplete="current-password" required>
        </div>
        <div class="admin-field">
            <label>Password Baru</label>
            <input type="password" name="new_password" minlength="10" autocomplete="new-password" required>
            <small style="display:block;color:#8a7f86;margin-top:6px">Minimal 10 karakter. Gunakan kombinasi huruf besar, kecil, angka, dan simbol.</small>
        </div>
        <div class="admin-field">
            <label>Ulangi Password Baru</label>
            <input type="password" name="confirm_password" minlength="10" autocomplete="new-password" required>
        </div>
        <button class="admin-btn" type="submit"><i class="bi bi-shield-lock"></i> Simpan Password Baru</button>
    </form>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
