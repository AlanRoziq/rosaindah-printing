<?php
require_once __DIR__ . '/../config/auth.php';
require_admin();
$pdo=db();$id=(int)($_GET['id']??0);if(!$pdo||$id<=0){http_response_code(404);exit('File tidak ditemukan.');}
try{$st=$pdo->prepare('SELECT proof_path,proof_name FROM order_payments WHERE id=? LIMIT 1');$st->execute([$id]);$row=$st->fetch();}catch(Throwable $e){$row=false;}
if(!$row||empty($row['proof_path'])){http_response_code(404);exit('Bukti pembayaran tidak ditemukan.');}
$base=realpath(__DIR__.'/../uploads/pembayaran');$path=realpath(__DIR__.'/../'.$row['proof_path']);if(!$base||!$path||($path!==$base&&!str_starts_with(str_replace('\\','/',$path),str_replace('\\','/',$base).'/'))||!is_file($path)){http_response_code(404);exit('File tidak ditemukan.');}
$mime=(new finfo(FILEINFO_MIME_TYPE))->file($path)?:'application/octet-stream';header('Content-Type: '.$mime);header('Content-Length: '.filesize($path));header('Content-Disposition: inline; filename="'.basename($row['proof_name']?:basename($path)).'"');readfile($path);exit;
