<?php
require_once __DIR__ . '/../config/auth.php'; require_admin();
require_once __DIR__ . '/../includes/notifications.php';
$pdo=db(); $pageTitle='Notifikasi Member'; $error='';
if(!$pdo){$error='Database belum terhubung.';} else {notifications_ensure_tables($pdo);}
if(empty($_SESSION['admin_notif_csrf'])) $_SESSION['admin_notif_csrf']=bin2hex(random_bytes(32)); $csrf=$_SESSION['admin_notif_csrf'];
if($_SERVER['REQUEST_METHOD']==='POST' && $pdo){
  try{
    if(!hash_equals($csrf,$_POST['csrf']??'')) throw new RuntimeException('Permintaan keamanan tidak valid.');
    $title=trim($_POST['title']??''); $message=trim($_POST['message']??''); $link=trim($_POST['link']??''); $target=(int)($_POST['customer_id']??0);
    if($title===''||$message==='') throw new RuntimeException('Judul dan isi notifikasi wajib diisi.');
    if($target>0){member_notification_add($pdo,$target,'general',$title,$message,$link!==''?$link:null);$m='Notifikasi berhasil dikirim ke Member.';} else {$n=member_notification_broadcast($pdo,'general',$title,$message,$link!==''?$link:null);$m='Notifikasi berhasil dikirim ke '.$n.' Member.';}
    admin_flash('success',$m); header('Location: '.page_url('admin/notifikasi.php')); exit;
  }catch(Throwable $e){$error=$e->getMessage();}
}
$members=$pdo?$pdo->query('SELECT id,name,phone FROM customers WHERE is_verified=1 ORDER BY name')->fetchAll():[];
$rows=$pdo?$pdo->query("SELECT n.*,c.name,c.phone FROM member_notifications n JOIN customers c ON c.id=n.customer_id ORDER BY n.created_at DESC,n.id DESC LIMIT 100")->fetchAll():[];
function an_e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
require_once __DIR__ . '/_header.php';
?><div class="admin-title"><div><h1>Notifikasi Member</h1><p style="color:#777;margin:7px 0 0">Kirim informasi langsung ke pusat notifikasi akun Member.</p></div></div><?php if($error):?><div class="admin-alert error"><?php echo an_e($error);?></div><?php endif;?><section class="admin-card"><h2>Kirim Notifikasi</h2><p style="color:#777;font-size:13px">Pilih satu Member atau biarkan Semua Member. Promo, reward, dan bonus poin juga membuat notifikasi otomatis.</p><form method="post" class="admin-form"><input type="hidden" name="csrf" value="<?php echo an_e($csrf);?>"><div class="admin-grid-2"><label class="admin-field">Tujuan<select name="customer_id"><option value="0">Semua Member</option><?php foreach($members as $m):?><option value="<?php echo (int)$m['id'];?>"><?php echo an_e($m['name']);?> — <?php echo an_e($m['phone']);?></option><?php endforeach;?></select></label><label class="admin-field">Judul<input name="title" required maxlength="160" placeholder="Contoh: Promo Spesial Member"></label></div><label class="admin-field">Isi Notifikasi<textarea name="message" rows="4" required placeholder="Tulis informasi promo, voucher, reward, atau pengumuman..."></textarea></label><label class="admin-field">Link (opsional)<input name="link" placeholder="Contoh: promo.php atau poin-member.php"></label><button class="admin-btn" type="submit"><i class="bi bi-send"></i> Kirim Notifikasi</button></form></section><section class="admin-card" style="margin-top:18px;overflow:auto"><h2>Riwayat Notifikasi Terbaru</h2><table class="admin-table" style="min-width:850px"><thead><tr><th>Waktu</th><th>Member</th><th>Judul</th><th>Jenis</th><th>Status</th></tr></thead><tbody><?php if(!$rows):?><tr><td colspan="5">Belum ada notifikasi.</td></tr><?php else:foreach($rows as $r):?><tr><td><?php echo an_e(date('d/m/Y H:i',strtotime($r['created_at'])));?></td><td><strong><?php echo an_e($r['name']);?></strong><small style="display:block;color:#888"><?php echo an_e($r['phone']);?></small></td><td><?php echo an_e($r['title']);?></td><td><?php echo an_e($r['type']);?></td><td><?php echo (int)$r['is_read']?'Sudah dibaca':'Belum dibaca';?></td></tr><?php endforeach;endif;?></tbody></table></section><?php require __DIR__ . '/_footer.php';?>
