<?php
require_once __DIR__ . '/config/customer-auth.php';
$pdo = db();
if ($pdo) { try { customer_auth_init($pdo); } catch (Throwable $e) {} }
if (customer_logged_in()) { header('Location: ' . page_url('akun.php')); exit; }
$error = '';
$phone = '';
$flash = customer_flash_get();
$notice = $flash ? (string)($flash[1] ?? '') : '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    if (!$pdo) $error = 'Database belum terhubung.';
    elseif (!customer_verify_csrf($_POST['csrf'] ?? '')) $error = 'Sesi tidak valid. Silakan muat ulang halaman.';
    else {
        $phoneNorm = customer_normalize_phone($phone);
        $stmt = $pdo->prepare('SELECT * FROM customers WHERE phone=? LIMIT 1');
        $stmt->execute([$phoneNorm]); $customer = $stmt->fetch();
        if ($customer && password_verify($password, $customer['password'])) {
            if ((int)($customer['is_verified'] ?? 1) !== 1) {
                customer_pending_set((int)$customer['id'], (string)$customer['phone']);
                header('Location: ' . page_url('verifikasi-member.php')); exit;
            } else {
                customer_login($customer);
                header('Location: ' . page_url('akun.php')); exit;
            }
        } else {
            $error = 'Nomor WhatsApp atau password salah.';
        }
    }
}
$csrf = customer_csrf();
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login Member — Rosa Indah Printing</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"><style>
:root{--pink:#d64a8a;--soft:#fff5fa;--ink:#18243a;--muted:#756d75;--line:#eadde4}*{box-sizing:border-box}body{margin:0;background:linear-gradient(135deg,#fff7fb,#fff);font-family:Arial,sans-serif;color:var(--ink)}.wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:22px}.card{width:min(440px,100%);background:#fff;border:1px solid var(--line);border-radius:24px;padding:32px;box-shadow:0 20px 60px rgba(214,74,138,.12)}.brand{text-align:center}.brand img{width:88px;height:88px;object-fit:contain}.brand h1{font-size:21px;margin:4px 0}.brand h1 span{color:var(--pink)}.brand p{margin:5px 0 25px;color:var(--muted);font-size:13px}.title{font-size:27px;margin:0 0 7px}.sub{color:var(--muted);font-size:13px;line-height:1.6;margin:0 0 20px}.err{background:#fff0f0;color:#a33b3b;border:1px solid #f3caca;padding:11px;border-radius:11px;font-size:13px;margin-bottom:15px}.notice{background:#eefaf4;color:#287a54;border:1px solid #cfe9db;padding:11px;border-radius:11px;font-size:13px;margin-bottom:15px}.field{margin:14px 0}.field label{display:block;font-weight:800;font-size:13px;margin-bottom:7px}.field input{width:100%;padding:13px;border:1px solid var(--line);border-radius:11px;font:inherit}.btn{width:100%;border:0;background:var(--pink);color:#fff;padding:13px;border-radius:11px;font-weight:900;font-size:15px;cursor:pointer}.links{display:flex;justify-content:space-between;gap:10px;margin-top:18px;font-size:13px}.links a{color:var(--pink);text-decoration:none;font-weight:800}.back{display:block;text-align:center;margin-top:20px;color:var(--muted);font-size:12px;text-decoration:none}
</style></head><body><main class="wrap"><section class="card"><div class="brand"><img src="<?php echo asset_url('images/logo/logori.png'); ?>" alt="Rosa Indah Printing"><h1>ROSA INDAH <span>PRINTING</span></h1><p>Akun Member</p></div><h2 class="title">Selamat datang kembali 👋</h2><p class="sub">Login untuk melihat riwayat pesanan, tracking, pembayaran, dan informasi Member Anda.</p><?php if($notice): ?><div class="notice"><i class="bi bi-check-circle"></i> <?php echo customer_e($notice); ?></div><?php endif; ?><?php if($error): ?><div class="err"><i class="bi bi-exclamation-circle"></i> <?php echo customer_e($error); ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?php echo customer_e($csrf); ?>"><div class="field"><label>No. WhatsApp</label><input name="phone" type="tel" value="<?php echo customer_e($phone); ?>" placeholder="08xxxxxxxxxx" required autocomplete="tel"></div><div class="field"><label>Password</label><input name="password" type="password" placeholder="Masukkan password" required autocomplete="current-password"></div><button class="btn"><i class="bi bi-box-arrow-in-right"></i> Masuk ke Akun Member</button></form><div class="links"><a href="<?php echo page_url('daftar.php'); ?>">Belum punya Akun Member? Daftar</a><a href="<?php echo page_url('lacak-pesanan.php'); ?>">Lacak tanpa login</a></div><a class="back" href="<?php echo page_url('index.php'); ?>">← Kembali ke website</a></section></main></body></html>
