<?php
require_once __DIR__ . '/config/customer-auth.php';
$pdo = db();
if ($pdo) { try { customer_auth_init($pdo); } catch (Throwable $e) {} }
if (customer_logged_in()) { header('Location: ' . page_url('akun.php')); exit; }
$error=''; $name=''; $phone=''; $email=''; $address='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name=trim($_POST['name']??''); $phone=trim($_POST['phone']??''); $email=trim($_POST['email']??''); $address=trim($_POST['address']??''); $password=(string)($_POST['password']??''); $confirm=(string)($_POST['password_confirm']??'');
    if (!$pdo) $error='Database belum terhubung.';
    elseif (!customer_verify_csrf($_POST['csrf']??'')) $error='Sesi tidak valid. Silakan muat ulang halaman.';
    elseif (mb_strlen($name)<3) $error='Nama minimal 3 karakter.';
    elseif (customer_normalize_phone($phone)==='') $error='Nomor WhatsApp tidak valid.';
    elseif ($email!=='' && !filter_var($email,FILTER_VALIDATE_EMAIL)) $error='Format email tidak valid.';
    elseif (strlen($password)<6) $error='Password minimal 6 karakter.';
    elseif ($password!==$confirm) $error='Konfirmasi password tidak sama.';
    else {
        $phoneNorm=customer_normalize_phone($phone); $emailVal=$email!==''?$email:null;
        $stmt=$pdo->prepare('SELECT id, is_verified FROM customers WHERE phone=? LIMIT 1'); $stmt->execute([$phoneNorm]); $existing=$stmt->fetch();
        if ($existing) {
            $error=((int)$existing['is_verified']===1) ? 'Nomor WhatsApp sudah terdaftar sebagai Member. Silakan login.' : 'Nomor WhatsApp sudah digunakan untuk pendaftaran Member yang belum diverifikasi. Silakan lanjutkan verifikasi.';
            if ((int)$existing['is_verified']===0) { customer_pending_set((int)$existing['id'],$phoneNorm); header('Location: '.page_url('verifikasi-member.php')); exit; }
        } else {
            $stmt=$pdo->prepare('INSERT INTO customers (name,phone,email,password,address,is_verified) VALUES (?,?,?,?,?,0)');
            $stmt->execute([$name,$phoneNorm,$emailVal,password_hash($password,PASSWORD_DEFAULT),$address]);
            $id=(int)$pdo->lastInsertId();
            $customer=$pdo->prepare('SELECT * FROM customers WHERE id=?'); $customer->execute([$id]); $customer=$customer->fetch();
            customer_pending_set($id,$phoneNorm);
            [$sent,$sendMsg]=customer_send_whatsapp_otp($pdo,$customer,true);
            if (!$sent) { $error=$sendMsg; }
            else { header('Location: '.page_url('verifikasi-member.php')); exit; }
        }
    }
}
$csrf=customer_csrf();
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Daftar Akun Member — Rosa Indah Printing</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"><style>
:root{--pink:#d64a8a;--soft:#fff5fa;--ink:#18243a;--muted:#756d75;--line:#eadde4}*{box-sizing:border-box}body{margin:0;background:#fff8fb;color:var(--ink);font-family:Arial,sans-serif}.wrap{max-width:760px;margin:0 auto;padding:30px 16px 60px}.top{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}.top a{color:var(--pink);text-decoration:none;font-weight:800}.card{background:#fff;border:1px solid var(--line);border-radius:22px;padding:28px;box-shadow:0 18px 50px rgba(30,20,30,.07)}.head{text-align:center;margin-bottom:24px}.head img{width:72px;height:72px;object-fit:contain}.head h1{font-size:28px;margin:7px 0}.head h1 span{color:var(--pink)}.head p{color:var(--muted);margin:0}.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.field{margin-bottom:14px}.full{grid-column:1/-1}.field label{display:block;font-size:13px;font-weight:800;margin-bottom:7px}.field input,.field textarea{width:100%;padding:12px;border:1px solid var(--line);border-radius:10px;font:inherit}.field small{display:block;color:#938990;font-size:11px;margin-top:5px}.btn{width:100%;border:0;background:var(--pink);color:#fff;padding:13px;border-radius:11px;font-weight:900;font-size:15px;cursor:pointer}.err{background:#fff0f0;color:#a33b3b;border:1px solid #f3caca;padding:11px;border-radius:11px;margin-bottom:16px;font-size:13px}.note{background:var(--soft);border:1px solid #f2d1e0;padding:12px;border-radius:11px;color:#6f626b;font-size:12px;line-height:1.6;margin:16px 0}.login{text-align:center;margin-top:16px;font-size:13px}.login a{color:var(--pink);font-weight:800;text-decoration:none}@media(max-width:600px){.grid{grid-template-columns:1fr}.full{grid-column:auto}.card{padding:20px}}
</style></head><body><main class="wrap"><div class="top"><a href="<?php echo page_url('index.php'); ?>">← Website</a><a href="<?php echo page_url('login.php'); ?>">Sudah punya akun? Login Member</a></div><section class="card"><div class="head"><img src="<?php echo asset_url('images/logo/logori.png'); ?>" alt="Rosa Indah Printing"><h1>Daftar <span>Akun Member</span></h1><p>Daftar untuk mendapatkan informasi promo, voucher, dan diskon Member dari Rosa Indah Printing.</p></div><?php if($error): ?><div class="err"><i class="bi bi-exclamation-circle"></i> <?php echo customer_e($error); ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?php echo customer_e($csrf); ?>"><div class="grid"><div class="field"><label>Nama Lengkap *</label><input name="name" value="<?php echo customer_e($name); ?>" required autocomplete="name"></div><div class="field"><label>No. WhatsApp *</label><input name="phone" type="tel" value="<?php echo customer_e($phone); ?>" placeholder="08xxxxxxxxxx" required autocomplete="tel"><small>Nomor WhatsApp harus unik karena digunakan untuk login.</small></div><div class="field"><label>Email (opsional)</label><input name="email" type="email" value="<?php echo customer_e($email); ?>" placeholder="nama@email.com" autocomplete="email"></div><div class="field"><label>Password *</label><input name="password" type="password" minlength="6" required autocomplete="new-password"></div><div class="field"><label>Konfirmasi Password *</label><input name="password_confirm" type="password" minlength="6" required autocomplete="new-password"></div><div class="field full"><label>Alamat / Lokasi (opsional)</label><textarea name="address" rows="3" placeholder="Alamat yang sering digunakan untuk pesanan"><?php echo customer_e($address); ?></textarea></div></div><div class="note"><i class="bi bi-shield-check"></i> Password disimpan menggunakan hash. Setelah formulir berhasil dibuat, kode verifikasi 4 digit akan dikirim ke WhatsApp yang didaftarkan.</div><button class="btn" type="submit"><i class="bi bi-person-plus"></i> Buat Akun Member</button></form><div class="login">Anda tetap bisa <a href="<?php echo page_url('lacak-pesanan.php'); ?>">Lacak Pesanan</a> dan checkout tanpa menjadi Member.</div></section></main></body></html>
