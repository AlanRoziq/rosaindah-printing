<?php
require_once __DIR__ . '/config/customer-auth.php';
$pdo=db();
if(!$pdo){die('Database belum terhubung.');}
try{customer_auth_init($pdo);}catch(Throwable $e){die('Sistem Member belum siap.');}
if(customer_logged_in()){header('Location: '.page_url('akun.php'));exit;}
$pendingId=customer_pending_id();
if(!$pendingId){header('Location: '.page_url('daftar.php'));exit;}
$stmt=$pdo->prepare('SELECT id,name,phone,is_verified FROM customers WHERE id=? LIMIT 1');$stmt->execute([$pendingId]);$member=$stmt->fetch();
if(!$member){customer_pending_clear();header('Location: '.page_url('daftar.php'));exit;}
if((int)$member['is_verified']===1){customer_pending_clear();header('Location: '.page_url('login.php?verified=1'));exit;}
$error='';$message='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!customer_verify_csrf($_POST['csrf']??'')){$error='Sesi tidak valid. Silakan muat ulang halaman.';}
  elseif(isset($_POST['action'])&&$_POST['action']==='resend'){
    [$ok,$msg]=customer_send_whatsapp_otp($pdo,$member,false);
    if($ok){$message=$msg; $stmt->execute([$pendingId]); $member=$stmt->fetch();} else $error=$msg;
  } else {
    [$ok,$verified,$msg]=customer_verify_otp($pdo,$pendingId,trim($_POST['code']??''));
    if($ok){customer_pending_clear(); customer_flash_set('success','Akun Member Anda sudah terdaftar dan terverifikasi. Silakan lanjut ke Login Member.'); header('Location: '.page_url('login.php?verified=1'));exit;}
    $error=$msg;
  }
}
$remaining=customer_otp_remaining($pdo,$pendingId);$csrf=customer_csrf();
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Verifikasi Akun Member — Rosa Indah Printing</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"><style>
:root{--pink:#d64a8a;--soft:#fff5fa;--ink:#18243a;--muted:#756d75;--line:#eadde4;--green:#287a54;--red:#a33b3b}*{box-sizing:border-box}body{margin:0;background:linear-gradient(135deg,#fff7fb,#fff);font-family:Arial,sans-serif;color:var(--ink)}.wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:22px}.card{width:min(520px,100%);background:#fff;border:1px solid var(--line);border-radius:24px;padding:32px;box-shadow:0 20px 60px rgba(214,74,138,.12);text-align:center}.brand img{width:78px;height:78px;object-fit:contain}.brand h1{font-size:20px;margin:4px 0}.brand h1 span{color:var(--pink)}.title{font-size:27px;margin:16px 0 8px}.sub{color:var(--muted);font-size:13px;line-height:1.7;margin:0 0 20px}.wa{display:inline-flex;align-items:center;gap:7px;background:var(--soft);border:1px solid #f2d1e0;color:var(--pink);padding:9px 12px;border-radius:999px;font-weight:800;font-size:12px}.code{width:100%;padding:15px;border:1px solid var(--line);border-radius:12px;text-align:center;font-size:27px;letter-spacing:10px;font-weight:900;color:var(--ink);outline:none}.code:focus{border-color:var(--pink);box-shadow:0 0 0 3px rgba(214,74,138,.1)}.btn{width:100%;border:0;background:var(--pink);color:#fff;padding:13px;border-radius:11px;font-weight:900;font-size:15px;cursor:pointer;margin-top:14px}.err,.msg{padding:11px;border-radius:11px;font-size:13px;margin-bottom:15px;text-align:left}.err{background:#fff0f0;color:var(--red);border:1px solid #f3caca}.msg{background:#eefaf4;color:var(--green);border:1px solid #cfe9db}.resend{margin-top:18px}.resend button{border:0;background:transparent;color:var(--pink);font-weight:900;cursor:pointer;font-size:13px}.resend button:disabled{color:#aaa;cursor:not-allowed}.back{display:block;margin-top:18px;color:var(--muted);font-size:12px;text-decoration:none}
</style></head><body><main class="wrap"><section class="card"><div class="brand"><img src="<?php echo asset_url('images/logo/logori.png'); ?>" alt="Rosa Indah Printing"><h1>ROSA INDAH <span>PRINTING</span></h1></div><h2 class="title">Verifikasi Akun Member</h2><p class="sub">Masukkan kode 4 digit yang dikirim ke WhatsApp yang Anda daftarkan.</p><div class="wa"><i class="bi bi-whatsapp"></i> <?php echo customer_e($member['phone']); ?></div><?php if($error): ?><div class="err" style="margin-top:18px"><i class="bi bi-exclamation-circle"></i> <?php echo customer_e($error); ?></div><?php endif; ?><?php if($message): ?><div class="msg" style="margin-top:18px"><i class="bi bi-check-circle"></i> <?php echo customer_e($message); ?></div><?php endif; ?><form method="post" autocomplete="off"><input type="hidden" name="csrf" value="<?php echo customer_e($csrf); ?>"><input class="code" name="code" inputmode="numeric" pattern="\d{4}" maxlength="4" minlength="4" placeholder="0000" aria-label="Kode verifikasi 4 digit" required><button class="btn" type="submit"><i class="bi bi-shield-check"></i> Verifikasi Akun Member</button></form><form method="post" class="resend"><input type="hidden" name="csrf" value="<?php echo customer_e($csrf); ?>"><input type="hidden" name="action" value="resend"><button type="submit" <?php echo $remaining>0?'disabled':''; ?>><i class="bi bi-arrow-repeat"></i> <?php echo $remaining>0?'Kirim ulang dalam '.$remaining.' detik':'Kirim Ulang Kode'; ?></button></form><a class="back" href="<?php echo page_url('login.php'); ?>">← Kembali ke Login Member</a></section></main></body></html>
