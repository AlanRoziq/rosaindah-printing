<?php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/customer-auth.php';
if (file_exists(__DIR__ . '/includes/order-tracking.php')) {
    require_once __DIR__ . '/includes/order-tracking.php';
}
if (file_exists(__DIR__ . '/includes/notifications.php')) {
    require_once __DIR__ . '/includes/notifications.php';
}

if (!function_exists('format_rupiah')) {
    function format_rupiah($angka) {
        return 'Rp ' . number_format((float)$angka, 0, ',', '.');
    }
}
if (!function_exists('account_e')) {
    function account_e($v) {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

$pdo = db();
if ($pdo) {
    try {
        customer_auth_init($pdo);
        if (function_exists('order_tracking_ensure_tables')) {
            order_tracking_ensure_tables($pdo);
        }
    } catch (Throwable $e) {
        // Fallback aman jika tabel belum siap
    }
}

$isLoggedIn = customer_logged_in();
$user = $isLoggedIn ? customer_user() : null;
if ($isLoggedIn && $user && $pdo && !empty($user['id'])) {
    try {
        $stmtFresh = $pdo->prepare('SELECT * FROM customers WHERE id = ? LIMIT 1');
        $stmtFresh->execute([(int)$user['id']]);
        $dbCustomer = $stmtFresh->fetch(PDO::FETCH_ASSOC);
        if ($dbCustomer) {
            if (empty($dbCustomer['avatar']) && !empty($user['avatar'])) {
                try {
                    $pdo->prepare('UPDATE customers SET avatar = ? WHERE id = ?')->execute([$user['avatar'], (int)$user['id']]);
                    $dbCustomer['avatar'] = $user['avatar'];
                } catch (Throwable $eSync) {}
            }
            $user = array_merge($user, array_filter($dbCustomer, static fn($v) => $v !== null));
            $_SESSION['customer'] = $user;
            $_SESSION['customer_avatar'] = (string)($user['avatar'] ?? '');
        }
    } catch (Throwable $eFresh) {}
}

if (is_file(__DIR__ . '/includes/loyalty.php')) {
    require_once __DIR__ . '/includes/loyalty.php';
}
if (is_file(__DIR__ . '/includes/promotions.php')) {
    require_once __DIR__ . '/includes/promotions.php';
}
if (is_file(__DIR__ . '/includes/notifications.php')) {
    require_once __DIR__ . '/includes/notifications.php';
}

$tab = isset($_GET['tab']) ? trim((string)$_GET['tab']) : 'profil';
if (!in_array($tab, ['profil', 'pesanan', 'poin', 'notifikasi', 'alamat'], true)) {
    $tab = 'profil';
}

$notice = isset($_GET['notice']) ? trim((string)$_GET['notice']) : '';
$error = isset($_GET['error']) ? trim((string)$_GET['error']) : '';

if (isset($_GET['welcome'])) {
    $notice = 'Akun Member berhasil diverifikasi. Selamat datang di Rosa Indah Printing!';
}
if (isset($_GET['saved'])) {
    $notice = 'Biodata dan profil Anda berhasil diperbarui!';
}

// Helper untuk menyimpan file avatar secara tangguh (uploads/avatars + copy ke assets/uploads/avatars + fallback base64)
function account_save_uploaded_avatar(array $fileArr, int $userId, string &$errOut): ?string {
    $tmpFile = $fileArr['tmp_name'] ?? '';
    $fileSize = (int)($fileArr['size'] ?? 0);
    $ext = strtolower(pathinfo((string)($fileArr['name'] ?? ''), PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

    if ($fileSize > 4 * 1024 * 1024) {
        $errOut = 'Ukuran foto terlalu besar. Maksimal ukuran foto adalah 4 MB.';
        return null;
    }
    if (!in_array($ext, $allowedExts, true)) {
        $errOut = 'Format foto tidak didukung. Gunakan JPG, PNG, atau WEBP.';
        return null;
    }

    $uploadDir = __DIR__ . '/uploads/avatars';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0775, true);
    }
    $assetsUploadDir = __DIR__ . '/assets/uploads/avatars';
    if (!is_dir($assetsUploadDir)) {
        @mkdir($assetsUploadDir, 0775, true);
    }

    $newFileName = 'avatar-' . ($userId ?: time()) . '-' . time() . '.' . $ext;
    $destPath = $uploadDir . '/' . $newFileName;

    $rawBytes = @file_get_contents($tmpFile);
    if (@move_uploaded_file($tmpFile, $destPath) || ($rawBytes !== false && @file_put_contents($destPath, $rawBytes) !== false)) {
        @copy($destPath, $assetsUploadDir . '/' . $newFileName);
        return 'uploads/avatars/' . $newFileName;
    }

    if ($rawBytes !== false && strlen($rawBytes) > 0) {
        $mime = ($ext === 'png') ? 'image/png' : (($ext === 'webp') ? 'image/webp' : 'image/jpeg');
        return 'data:' . $mime . ';base64,' . base64_encode($rawBytes);
    }

    $errOut = 'Gagal menyimpan file foto profil. Silakan coba lagi.';
    return null;
}

// Handle POST update profil hanya jika sudah login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isAjaxAvatar = (($_POST['ajax_avatar'] ?? '0') === '1');

    if (!$isLoggedIn || !$user) {
        if ($isAjaxAvatar) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ok' => false, 'error' => 'Sesi login berakhir. Silakan login kembali.']);
            exit;
        }
        header('Location: ' . page_url('login.php'));
        exit;
    }

    $id = (int)($user['id'] ?? 0);
    $postAction = trim((string)($_POST['action'] ?? ''));

    // Aksi Tukar Poin dengan Voucher Reward
    if ($postAction === 'redeem_reward') {
        $rewardId = (int)($_POST['reward_id'] ?? 0);
        if ($pdo && $id > 0 && function_exists('loyalty_issue_reward')) {
            [$okRedeem, $msgRedeem, $codeRedeem] = loyalty_issue_reward($pdo, $id, $rewardId);
            if ($okRedeem) {
                $newBal = function_exists('loyalty_balance') ? loyalty_balance($pdo, $id) : max(0, (int)($user['points'] ?? 100));
                $user['points'] = $newBal;
                $_SESSION['customer']['points'] = $newBal;
                header('Location: ' . page_url('akun.php?tab=poin&notice=' . urlencode($msgRedeem)));
                exit;
            } else {
                header('Location: ' . page_url('akun.php?tab=poin&error=' . urlencode($msgRedeem)));
                exit;
            }
        } else {
            header('Location: ' . page_url('akun.php?tab=poin&error=' . urlencode('Layanan penukaran poin belum tersedia.')));
            exit;
        }
    }

    // Aksi Tandai Semua Notifikasi Sudah Dibaca
    if ($postAction === 'mark_notif_read') {
        if ($pdo && $id > 0 && function_exists('member_notification_mark_read')) {
            try {
                member_notification_mark_read($pdo, $id);
            } catch (Throwable $e) {}
        }
        header('Location: ' . page_url('akun.php?tab=notifikasi&notice=' . urlencode('Semua notifikasi telah ditandai sudah dibaca.')));
        exit;
    }

    $removeAvatar = ($_POST['remove_avatar'] ?? '0') === '1';

    // Mode Auto-Upload / Hapus Foto Profil Instan via AJAX
    if ($isAjaxAvatar) {
        header('Content-Type: application/json; charset=UTF-8');
        $ajaxError = '';
        $newAvatarPath = $user['avatar'] ?? '';

        if ($removeAvatar) {
            $newAvatarPath = '';
        } elseif (!empty($_FILES['avatar']['name']) && ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $savedPath = account_save_uploaded_avatar($_FILES['avatar'], $id, $ajaxError);
            if ($savedPath !== null) {
                $newAvatarPath = $savedPath;
            }
        } else {
            $ajaxError = 'File foto tidak ditemukan atau gagal diunggah.';
        }

        if ($ajaxError !== '') {
            echo json_encode(['ok' => false, 'error' => $ajaxError]);
            exit;
        }

        if ($pdo && $id > 0) {
            try {
                $pdo->exec("ALTER TABLE customers MODIFY avatar MEDIUMTEXT NULL");
            } catch (Throwable $eAlter) {}
            try {
                $updAv = $pdo->prepare('UPDATE customers SET avatar = ? WHERE id = ?');
                $updAv->execute([$newAvatarPath !== '' ? $newAvatarPath : null, $id]);
            } catch (Throwable $eUpd) {}
        }

        $updatedUser = array_merge($user, ['avatar' => $newAvatarPath]);
        $_SESSION['customer'] = $updatedUser;
        $_SESSION['customer_avatar'] = $newAvatarPath;
        if (function_exists('customer_login')) {
            customer_login($updatedUser);
        }
        $resolvedUrl = function_exists('customer_avatar_url')
            ? customer_avatar_url($newAvatarPath)
            : ($newAvatarPath !== '' ? page_url(ltrim($newAvatarPath, '/')) : '');

        echo json_encode([
            'ok' => true,
            'avatar' => $newAvatarPath,
            'avatar_url' => $resolvedUrl,
            'message' => $removeAvatar ? 'Foto profil berhasil dihapus.' : 'Foto profil berhasil dipasang & tersimpan otomatis!'
        ]);
        exit;
    }

    $name = trim($_POST['name'] ?? ($user['name'] ?? ''));
    $username = trim($_POST['username'] ?? ($user['username'] ?? ''));
    $phone = customer_normalize_phone(trim($_POST['phone'] ?? ($user['phone'] ?? '')));
    $email = trim($_POST['email'] ?? ($user['email'] ?? ''));
    $gender = in_array($_POST['gender'] ?? 'L', ['L', 'P'], true) ? $_POST['gender'] : 'L';
    $birthDate = trim($_POST['birth_date'] ?? '');
    $city = trim($_POST['city'] ?? 'Pasuruan');
    $addressNote = trim($_POST['address_note'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (mb_strlen($name) < 2 || $phone === '') {
        $error = 'Nama lengkap dan nomor WhatsApp aktif wajib diisi.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format alamat email tidak valid.';
    } else {
        $avatarPath = $user['avatar'] ?? '';
        if ($removeAvatar) {
            $avatarPath = '';
        }

        // Proses upload foto profil jika ada
        if (!empty($_FILES['avatar']['name']) && ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $savedPath = account_save_uploaded_avatar($_FILES['avatar'], $id, $error);
            if ($savedPath !== null) {
                $avatarPath = $savedPath;
            }
        }

        if ($error === '') {
            [$phoneNorm, $phoneLocal] = function_exists('customer_phone_candidates')
                ? customer_phone_candidates($phone)
                : [$phone, $phone];

            if ($pdo && $id > 0) {
                try {
                    $q = $pdo->prepare('SELECT id FROM customers WHERE (phone = ? OR phone = ?) AND id <> ? LIMIT 1');
                    $q->execute([$phoneNorm, $phoneLocal, $id]);
                    if ($q->fetch()) {
                        $error = 'Nomor WhatsApp sudah digunakan oleh akun Member lain.';
                    } else {
                        try {
                            $upd = $pdo->prepare('UPDATE customers SET name = ?, username = ?, phone = ?, email = ?, gender = ?, birth_date = ?, city = ?, address = ?, address_note = ?, avatar = ? WHERE id = ?');
                            $upd->execute([
                                $name,
                                $username !== '' ? $username : null,
                                $phoneNorm ?: $phone,
                                $email !== '' ? $email : null,
                                $gender,
                                $birthDate !== '' ? $birthDate : null,
                                $city !== '' ? $city : 'Pasuruan',
                                $address,
                                $addressNote !== '' ? $addressNote : null,
                                $avatarPath !== '' ? $avatarPath : null,
                                $id
                            ]);
                        } catch (Throwable $innerEx) {
                            $upd = $pdo->prepare('UPDATE customers SET name = ?, phone = ?, email = ?, address = ? WHERE id = ?');
                            $upd->execute([$name, $phoneNorm ?: $phone, $email !== '' ? $email : null, $address, $id]);
                            try {
                                $pdo->prepare('UPDATE customers SET avatar = ? WHERE id = ?')->execute([$avatarPath !== '' ? $avatarPath : null, $id]);
                            } catch (Throwable $avEx) {}
                        }
                    }
                } catch (Throwable $e) {
                    // Tetap lanjut update session
                }
            }

            if ($error === '') {
                $updatedUser = array_merge($user, [
                    'name'         => $name,
                    'username'     => $username !== '' ? $username : ($user['username'] ?? ''),
                    'phone'        => $phoneNorm ?: $phone,
                    'email'        => $email,
                    'gender'       => $gender,
                    'birth_date'   => $birthDate,
                    'city'         => $city,
                    'address_note' => $addressNote,
                    'address'      => $address,
                    'avatar'       => $avatarPath,
                ]);
                $_SESSION['customer'] = $updatedUser;
                $_SESSION['customer_avatar'] = $avatarPath;
                if (function_exists('customer_login')) {
                    customer_login($updatedUser);
                }
                $user = $updatedUser;
                $notice = 'Foto profil dan biodata diri Anda berhasil diperbarui!';
            }
        }
    }
}

$orders = [];
$stats = ['total' => 0, 'active' => 0, 'completed' => 0, 'spent' => 0];
$vouchers = [
    [
        'code' => 'ROSAHEMAT10',
        'name' => 'Diskon Spesial Member 10%',
        'min_order' => 50000,
        'discount_type' => 'percent',
        'discount_value' => 10
    ],
    [
        'code' => 'MEMBERBARU',
        'name' => 'Potongan Member Baru Rp5.000',
        'min_order' => 30000,
        'discount_type' => 'fixed',
        'discount_value' => 5000
    ],
    [
        'code' => 'CETAKUMKM20',
        'name' => 'Potongan Cetak Kemasan & Stiker 20%',
        'min_order' => 100000,
        'discount_type' => 'percent',
        'discount_value' => 20
    ],
    [
        'code' => 'UNDANGAN25RB',
        'name' => 'Potongan Langsung Cetak Undangan',
        'min_order' => 250000,
        'discount_type' => 'fixed',
        'discount_value' => 25000
    ]
];
$loyaltyRewards = [
    [
        'id' => 1,
        'name' => 'Voucher Potongan Rp10.000',
        'points_cost' => 100,
        'discount_type' => 'fixed',
        'discount_value' => 10000,
        'min_order' => 50000,
        'validity_days' => 30
    ],
    [
        'id' => 2,
        'name' => 'Voucher Diskon Cetak 10%',
        'points_cost' => 150,
        'discount_type' => 'percent',
        'discount_value' => 10,
        'min_order' => 100000,
        'validity_days' => 30
    ]
];
$loyaltyTransactions = [];
$memberNotifications = [];
$unreadNotifCount = 0;
$loyaltyRule = ['points_per_amount' => 10000, 'points_per_order_min' => 0];

if ($isLoggedIn && $user) {
    $id = (int)($user['id'] ?? 0);
    $phone = (string)($user['phone'] ?? '');
    [$phoneNorm, $phoneLocal] = function_exists('customer_phone_candidates')
        ? customer_phone_candidates($phone)
        : [$phone, $phone];

    if ($pdo) {
        // 1. Ambil Pesanan & Statistik
        try {
            $q = $pdo->prepare('SELECT * FROM orders WHERE customer_id = ? OR customer_phone = ? OR customer_phone = ? ORDER BY created_at DESC LIMIT 100');
            $q->execute([$id, $phoneNorm, $phoneLocal]);
            $orders = $q->fetchAll(PDO::FETCH_ASSOC) ?: [];

            foreach ($orders as &$o) {
                $stats['total']++;
                if (in_array($o['status'] ?? '', ['pending', 'confirmed', 'processing', 'printed'], true)) {
                    $stats['active']++;
                }
                if (($o['status'] ?? '') === 'completed') {
                    $stats['completed']++;
                }
                if (($o['status'] ?? '') !== 'cancelled') {
                    $stats['spent'] += (float)($o['total_estimate'] ?? 0);
                }

                $o['items'] = [];
                try {
                    $qi = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
                    $qi->execute([(int)$o['id']]);
                    $o['items'] = $qi->fetchAll(PDO::FETCH_ASSOC) ?: [];
                } catch (Throwable $e) {
                    $o['items'] = [];
                }
            }
            unset($o);
        } catch (Throwable $e) {
            $orders = [];
        }

        // 2. Sinkronkan Poin Loyalty, Daftar Reward, dan Riwayat Poin
        try {
            if (function_exists('loyalty_ensure_tables')) {
                loyalty_ensure_tables($pdo);
                $loyaltyRule = loyalty_settings($pdo);
                if ($id > 0) {
                    $livePoints = loyalty_balance($pdo, $id);
                    $user['points'] = $livePoints;
                    $_SESSION['customer']['points'] = $livePoints;

                    $trStmt = $pdo->prepare('SELECT * FROM loyalty_transactions WHERE customer_id = ? ORDER BY created_at DESC, id DESC LIMIT 20');
                    $trStmt->execute([$id]);
                    $loyaltyTransactions = $trStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                }
                $rwStmt = $pdo->query("SELECT * FROM loyalty_rewards WHERE status = 'active' ORDER BY points_cost ASC, id DESC");
                $dbRewards = $rwStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                if (!empty($dbRewards)) {
                    $loyaltyRewards = $dbRewards;
                }
            }
        } catch (Throwable $e) {}

        // 3. Sinkronkan Voucher Aktif (Voucher Umum + Voucher Reward Member)
        try {
            if (function_exists('ensure_promotions_tables')) {
                ensure_promotions_tables($pdo);
            }
            $vStmt = $pdo->prepare("SELECT * FROM vouchers WHERE status = 'active' AND ends_at >= NOW() AND (customer_id IS NULL OR customer_id = 0 OR customer_id = ?) ORDER BY customer_id DESC, id DESC");
            $vStmt->execute([$id]);
            $dbVouchers = $vStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            if (!empty($dbVouchers)) {
                $vouchers = $dbVouchers;
            }
        } catch (Throwable $e) {}

        // 4. Sinkronkan Notifikasi Member (Dari Seller & Dari Pesanan)
        try {
            if (function_exists('notifications_ensure_tables')) {
                notifications_ensure_tables($pdo);
                if ($id > 0) {
                    if ($tab === 'notifikasi') {
                        member_notification_mark_read($pdo, $id);
                    }
                    $memberNotifications = member_notifications($pdo, $id, 50);
                    $unreadNotifCount = member_notification_unread_count($pdo, $id);
                }
            }
        } catch (Throwable $e) {}
    }
}

$title = 'Profil & Akun Member — Rosa Indah Printing';
include __DIR__ . '/includes/header.php';
?>

<style>
/* =========================================================
   MARKETPLACE PROFILE STYLING (DESKTOP, TABLET & MOBILE)
   ========================================================= */
.profile-page {
    background: #fbf8fa;
    min-height: 85vh;
    padding-bottom: 70px;
}
.profile-hero-card {
    background: linear-gradient(135deg, #ffffff 0%, #fff4fa 100%);
    border: 1.5px solid #f2dce7;
    border-radius: 24px;
    padding: 24px 28px;
    box-shadow: 0 10px 30px rgba(225, 29, 100, 0.05);
    margin-bottom: 22px;
}
.profile-avatar-wrap {
    position: relative;
    width: 82px;
    height: 82px;
    flex-shrink: 0;
}
.profile-avatar-img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #ffffff;
    box-shadow: 0 8px 22px rgba(225, 29, 100, 0.28);
    display: block;
}
.profile-avatar {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: linear-gradient(135deg, #e11d64 0%, #f43f7e 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: 800;
    border: 3px solid #ffffff;
    box-shadow: 0 8px 22px rgba(225, 29, 100, 0.28);
    text-transform: uppercase;
}
.profile-avatar-badge {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 26px;
    height: 26px;
    background: #28a745;
    color: #ffffff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    border: 2px solid #ffffff;
}
.avatar-upload-card {
    background: #fff8fb;
    border: 1.5px dashed #f2bad0;
    border-radius: 18px;
    padding: 16px 18px;
    margin-bottom: 20px;
    transition: all 0.2s ease;
}
.avatar-upload-card:hover {
    border-color: #e11d64;
    background: #fff3f8;
}
.avatar-preview-wrap {
    width: 78px;
    height: 78px;
    position: relative;
    flex-shrink: 0;
}
.avatar-preview-circle {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #e11d64;
    box-shadow: 0 4px 14px rgba(225, 29, 100, 0.22);
}
.avatar-preview-placeholder {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: linear-gradient(135deg, #e11d64 0%, #f43f7e 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    font-weight: 800;
    border: 3px solid #ffffff;
    box-shadow: 0 4px 14px rgba(225, 29, 100, 0.22);
}
.btn-upload-avatar {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: linear-gradient(90deg, #e11d64 0%, #f43f7e 100%);
    color: #ffffff !important;
    border: 0;
    padding: 8px 16px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(225, 29, 100, 0.22);
    transition: all 0.2s ease;
}
.btn-upload-avatar:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(225, 29, 100, 0.32);
}
.btn-remove-avatar {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #ffffff;
    color: #dc3545;
    border: 1px solid #f5c2c7;
    padding: 8px 14px;
    border-radius: 10px;
    font-size: 12.5px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
}
.btn-remove-avatar:hover {
    background: #f8d7da;
    border-color: #dc3545;
}

/* Marketplace Tab Bar */
.marketplace-tabs-wrap {
    margin-bottom: 22px;
    border-bottom: 1.5px solid #f0dce6;
    background: #ffffff;
    border-radius: 18px;
    padding: 6px 10px;
    box-shadow: 0 4px 18px rgba(35, 15, 26, 0.03);
}
.marketplace-nav-tabs {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    margin: 0;
    padding: 4px 0;
}
.marketplace-nav-tabs::-webkit-scrollbar {
    display: none;
}
.marketplace-tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 18px;
    border-radius: 14px;
    border: 0;
    background: transparent;
    color: #635761;
    font-size: 13.5px;
    font-weight: 700;
    white-space: nowrap;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
}
.marketplace-tab-btn i {
    font-size: 16px;
    color: #9c8e98;
    transition: color 0.2s ease;
}
.marketplace-tab-btn:hover {
    color: #e11d64;
    background: #fff3f8;
}
.marketplace-tab-btn:hover i {
    color: #e11d64;
}
.marketplace-tab-btn.active {
    background: linear-gradient(90deg, #e11d64 0%, #f43f7e 50%, #fb71a0 100%);
    color: #ffffff;
    box-shadow: 0 6px 18px rgba(225, 29, 100, 0.28);
}
.marketplace-tab-btn.active i {
    color: #ffffff;
}
.marketplace-tab-btn .badge-pill {
    background: rgba(255, 255, 255, 0.25);
    padding: 2px 7px;
    border-radius: 999px;
    font-size: 11px;
}
.marketplace-tab-btn:not(.active) .badge-pill {
    background: #f4e3eb;
    color: #e11d64;
}

/* Profile Form Card */
.profile-card {
    background: #ffffff;
    border: 1.5px solid #f2dce7;
    border-radius: 22px;
    padding: 26px 28px;
    box-shadow: 0 8px 24px rgba(35, 15, 26, 0.03);
    margin-bottom: 22px;
}
.profile-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 14px;
    border-bottom: 1px solid #f6e8ef;
}
.profile-card-header h2 {
    margin: 0;
    font-size: 18px;
    font-weight: 800;
    color: #18243a;
    display: flex;
    align-items: center;
    gap: 8px;
}
.profile-card-header p {
    margin: 4px 0 0;
    font-size: 12.5px;
    color: #837781;
}

/* Modern Marketplace Form Inputs */
.form-field-group {
    margin-bottom: 18px;
}
.form-field-group label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: #3b333a;
    margin-bottom: 7px;
}
.form-field-group label span.req {
    color: #e11d64;
}
.form-input-box {
    position: relative;
    display: flex;
    align-items: center;
}
.form-input-box i.input-icon {
    position: absolute;
    left: 14px;
    font-size: 17px;
    color: #a4949f;
    pointer-events: none;
}
.form-input-box input,
.form-input-box select,
.form-field-group textarea {
    width: 100%;
    padding: 12px 14px 12px 42px;
    font-size: 14.5px;
    border: 1.5px solid #eddfe5;
    border-radius: 13px;
    background: #ffffff;
    color: #18243a;
    transition: all 0.2s ease;
    outline: none;
    font-family: inherit;
    box-sizing: border-box;
}
.form-field-group textarea {
    padding: 12px 14px;
    min-height: 85px;
    resize: vertical;
}
.form-input-box input:focus,
.form-input-box select:focus,
.form-field-group textarea:focus {
    border-color: #e11d64;
    box-shadow: 0 0 0 3px rgba(225, 29, 100, 0.12);
}
.form-hint {
    display: block;
    font-size: 11.5px;
    color: #8c7e87;
    margin-top: 5px;
    line-height: 1.45;
}

/* Gender Radio Pills */
.gender-radio-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
.gender-radio-pill {
    position: relative;
}
.gender-radio-pill input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}
.gender-radio-label {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 11px 16px;
    border: 1.5px solid #eddfe5;
    border-radius: 13px;
    background: #ffffff;
    font-size: 13.5px;
    font-weight: 700;
    color: #554a52;
    cursor: pointer;
    transition: all 0.2s ease;
}
.gender-radio-pill input:checked + .gender-radio-label {
    border-color: #e11d64;
    background: #fff2f8;
    color: #e11d64;
    box-shadow: 0 4px 12px rgba(225, 29, 100, 0.1);
}

/* Save Button */
.btn-save-profile {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: 0;
    background: linear-gradient(90deg, #e11d64 0%, #f43f7e 50%, #fb71a0 100%);
    color: #ffffff !important;
    padding: 14px 28px;
    border-radius: 999px;
    font-weight: 800;
    font-size: 14.5px;
    cursor: pointer;
    box-shadow: 0 8px 24px rgba(225, 29, 100, 0.32);
    transition: all 0.25s ease;
    text-decoration: none;
}
.btn-save-profile:hover {
    background: linear-gradient(90deg, #be124f 0%, #e11d64 50%, #f43f7e 100%);
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(225, 29, 100, 0.42);
}

/* Order Item Card (Marketplace Style) */
.profile-order-card {
    background: #ffffff;
    border: 1px solid #f0dce6;
    border-radius: 18px;
    padding: 18px;
    margin-bottom: 14px;
    transition: all 0.2s ease;
}
.profile-order-card:hover {
    box-shadow: 0 8px 22px rgba(225, 29, 100, 0.08);
}
.profile-order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f6eaf0;
    margin-bottom: 12px;
}

/* User Identity Meta & Hero Action Pills */
.profile-user-identity {
    min-width: 0;
    flex: 1;
}
.profile-user-meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px 14px;
    margin-top: 6px;
    font-size: 12.5px;
    color: #6f626b;
}
.profile-user-meta-item {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-weight: 600;
    white-space: nowrap;
}
.profile-hero-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.profile-action-pill {
    border-radius: 12px;
    padding: 9px 14px;
    text-decoration: none;
    font-weight: 800;
    font-size: 12.5px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    white-space: nowrap;
    transition: transform 0.18s ease, box-shadow 0.18s ease;
}
.profile-action-pill:hover {
    transform: translateY(-1px);
}
.profile-action-pill.pill-points {
    background: #fff9df;
    border: 1px solid #fae69e;
    color: #9c6c00;
}
.profile-action-pill.pill-vouchers {
    background: #fff0f6;
    border: 1px solid #f2c9de;
    color: #e11d64;
}
.profile-action-pill.pill-logout {
    background: #ffffff;
    border: 1px solid #f0dce6;
    color: #a43a4e;
    font-weight: 700;
}

/* Quick Stats Bar */
.profile-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    margin-top: 18px;
}
.profile-stat-box {
    background: #ffffff;
    border: 1px solid #f2dce7;
    border-radius: 15px;
    padding: 13px 14px;
    display: flex;
    align-items: center;
    gap: 11px;
    min-width: 0;
}
.profile-stat-icon {
    width: 38px;
    height: 38px;
    border-radius: 11px;
    background: #fff0f6;
    color: #e11d64;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    flex-shrink: 0;
}
.profile-stat-info {
    min-width: 0;
    flex: 1;
}
.profile-stat-info strong {
    display: block;
    font-size: 16px;
    font-weight: 800;
    color: #18243a;
    line-height: 1.25;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.profile-stat-info span {
    display: block;
    font-size: 11.5px;
    color: #7d727b;
    font-weight: 600;
    line-height: 1.3;
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Mobile Specific Optimizations */
@media (max-width: 768px) {
    .profile-hero-card {
        padding: 18px 16px;
        border-radius: 20px;
    }
    .profile-hero-flex {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 14px !important;
    }
    .profile-user-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 3px;
        margin-top: 5px;
    }
    .profile-hero-actions {
        display: grid !important;
        grid-template-columns: 1fr 1fr auto;
        gap: 8px !important;
        width: 100%;
    }
    .profile-action-pill {
        padding: 9px 10px;
        font-size: 12px;
    }
    .profile-action-pill.pill-logout {
        padding: 9px 12px;
    }
    .profile-stats-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 10px !important;
    }
    .profile-stat-box {
        padding: 11px 12px;
        gap: 9px;
    }
    .profile-stat-icon {
        width: 34px;
        height: 34px;
        font-size: 15px;
        border-radius: 10px;
    }
    .profile-stat-info strong {
        font-size: 14.5px;
    }
    .profile-stat-info span {
        font-size: 11px;
    }
    .profile-card {
        padding: 18px 16px;
        border-radius: 18px;
    }
    .form-grid-2 {
        grid-template-columns: 1fr !important;
        gap: 0 !important;
    }
    .profile-order-footer {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 12px !important;
    }
    .profile-order-actions {
        display: grid !important;
        grid-template-columns: 1fr 1fr;
        gap: 6px !important;
    }
    .btn-save-profile {
        width: 100% !important;
    }
}

/* Guest Login Required State Card */
.guest-profile-card {
    max-width: 560px;
    margin: 24px auto 40px;
    background: #ffffff;
    border: 1.5px solid #f2dce7;
    border-radius: 24px;
    padding: 36px 28px;
    text-align: center;
    box-shadow: 0 14px 40px rgba(225, 29, 100, 0.08);
}
.guest-profile-icon {
    width: 76px;
    height: 76px;
    border-radius: 50%;
    background: linear-gradient(135deg, #fff0f6 0%, #ffe4ed 100%);
    color: #e11d64;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 34px;
    margin: 0 auto 18px;
    border: 2px solid #fbcfe8;
    box-shadow: 0 8px 20px rgba(225, 29, 100, 0.14);
}
.guest-profile-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #fff0f6;
    color: #e11d64;
    border: 1px solid #fbcfe8;
    font-size: 11px;
    font-weight: 800;
    padding: 4px 12px;
    border-radius: 999px;
    letter-spacing: 0.5px;
    margin-bottom: 10px;
    text-transform: uppercase;
}
.guest-profile-title {
    font-size: 22px;
    font-weight: 800;
    color: #18243a;
    margin: 0 0 10px;
}
.guest-profile-desc {
    font-size: 13.5px;
    color: #6b7280;
    line-height: 1.65;
    margin: 0 auto 22px;
    max-width: 440px;
}
.guest-benefits-list {
    display: grid;
    grid-template-columns: 1fr;
    gap: 10px;
    text-align: left;
    background: #fff9fb;
    border: 1px solid #f5dce8;
    border-radius: 16px;
    padding: 16px 18px;
    margin-bottom: 24px;
}
.guest-benefit-item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 12.5px;
    color: #374151;
    font-weight: 600;
}
.guest-benefit-item i {
    color: #e11d64;
    font-size: 16px;
    flex-shrink: 0;
}
.guest-action-buttons {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.btn-guest-login {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 13px 24px;
    border-radius: 999px;
    background: linear-gradient(90deg, #e11d64 0%, #f43f7e 50%, #fb71a0 100%);
    color: #ffffff !important;
    font-size: 14.5px;
    font-weight: 800;
    text-decoration: none;
    box-shadow: 0 10px 26px -4px rgba(225, 29, 100, 0.4);
    transition: all 0.25s ease;
}
.btn-guest-login:hover {
    background: linear-gradient(90deg, #be124f 0%, #e11d64 50%, #f43f7e 100%);
    transform: translateY(-2px);
    color: #ffffff !important;
}
.btn-guest-register {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 11px 24px;
    border-radius: 999px;
    background: #ffffff;
    color: #e11d64 !important;
    border: 1.5px solid #f2bad0;
    font-size: 13.5px;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.2s ease;
}
.btn-guest-register:hover {
    background: #fff0f6;
    border-color: #e11d64;
}
@media (max-width: 575.98px) {
    .guest-profile-card {
        padding: 26px 18px;
        margin: 12px auto 30px;
        border-radius: 20px;
    }
    .guest-profile-title {
        font-size: 19px;
    }
    .guest-profile-desc {
        font-size: 12.5px;
    }
}
</style>

<header class="main-header">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
</header>

<main class="profile-page py-3 py-md-4">
    <div class="container">
        <!-- Breadcrumbs -->
        <div class="catalog-breadcrumb mb-3" style="font-size: 12.5px;">
            <a href="<?php echo page_url(''); ?>">Home</a>
            <i class="bi bi-chevron-right"></i>
            <span>Akun Saya</span>
        </div>

        <!-- Alert Notification Messages -->
        <?php if (!empty($notice)): ?>
            <div style="background: #eefbf3; border: 1.5px solid #b7eed0; color: #1e7e46; padding: 12px 18px; border-radius: 14px; margin-bottom: 18px; display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 13.5px;">
                <i class="bi bi-check-circle-fill" style="font-size: 18px;"></i>
                <span><?php echo account_e($notice); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div style="background: #fff2f5; border: 1.5px solid #f9ccd9; color: #c4234b; padding: 12px 18px; border-radius: 14px; margin-bottom: 18px; display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 13.5px;">
                <i class="bi bi-exclamation-triangle-fill" style="font-size: 18px;"></i>
                <span><?php echo account_e($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!$isLoggedIn || !$user): ?>
            <!-- =========================================================
                 TAMPILAN SAAT BELUM LOGIN MEMBER (INFORMASI HARUS LOGIN)
                 ========================================================= -->
            <section class="guest-profile-card">
                <div class="guest-profile-icon">
                    <i class="bi bi-person-lock"></i>
                </div>
                <span class="guest-profile-badge">
                    <i class="bi bi-shield-lock-fill"></i> Akses Akun Member
                </span>
                <h1 class="guest-profile-title">Anda Belum Login Member</h1>
                <p class="guest-profile-desc">
                    Silakan login terlebih dahulu menggunakan akun Member Anda untuk melihat dan mengelola biodata diri, memantau riwayat pesanan cetak, serta menukarkan poin &amp; voucher promo.
                </p>

                <div class="guest-benefits-list">
                    <div class="guest-benefit-item">
                        <i class="bi bi-person-vcard-fill"></i>
                        <span>Kelola biodata diri &amp; simpan alamat pengiriman tetap</span>
                    </div>
                    <div class="guest-benefit-item">
                        <i class="bi bi-receipt-cutoff"></i>
                        <span>Pantau status pesanan cetak &amp; unduh nota PDF kapan saja</span>
                    </div>
                    <div class="guest-benefit-item">
                        <i class="bi bi-stars"></i>
                        <span>Kumpulkan poin loyalitas &amp; klaim voucher diskon khusus Member</span>
                    </div>
                </div>

                <div class="guest-action-buttons">
                    <a href="<?php echo page_url('login.php'); ?>" class="btn-guest-login">
                        <i class="bi bi-box-arrow-in-right"></i> Login Member Sekarang
                    </a>
                    <a href="<?php echo page_url('daftar.php'); ?>" class="btn-guest-register">
                        <i class="bi bi-person-plus"></i> Belum Punya Akun? Daftar Member
                    </a>
                </div>
            </section>
        <?php else: ?>
            <?php
            $userAvatarUrl = function_exists('customer_avatar_url')
                ? customer_avatar_url($user['avatar'] ?? '')
                : (!empty($user['avatar']) ? page_url(ltrim((string)$user['avatar'], '/')) : '');
            $hasUserAvatar = ($userAvatarUrl !== '');
            ?>
            <!-- =========================================================
                 1. HEADER PROFILE CARD (MARKETPLACE USER HERO)
                 ========================================================= -->
            <section class="profile-hero-card">
                <div class="profile-hero-flex d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <!-- User Avatar Photo or Initial (Clickable to trigger upload) -->
                        <div class="profile-avatar-wrap" style="cursor: pointer;" title="Klik untuk mengganti foto profil" onclick="var inp = document.getElementById('avatarInput'); if (inp) { inp.click(); } else { window.location.href='<?php echo page_url('akun.php?tab=profil'); ?>'; }">
                            <img id="heroAvatarImg" src="<?php echo account_e($userAvatarUrl); ?>" alt="<?php echo account_e($user['name'] ?? 'Member'); ?>" class="profile-avatar-img" style="<?php echo $hasUserAvatar ? 'display: block;' : 'display: none;'; ?>" onerror="this.style.display='none'; var initEl = document.getElementById('heroAvatarInitial'); if(initEl) initEl.style.display='flex';">
                            <div id="heroAvatarInitial" class="profile-avatar" style="<?php echo $hasUserAvatar ? 'display: none;' : 'display: flex;'; ?>">
                                <?php echo account_e(mb_substr($user['name'] ?? 'M', 0, 1)); ?>
                            </div>
                            <div class="profile-avatar-badge" title="Akun Terverifikasi">
                                <i class="bi bi-check-lg"></i>
                            </div>
                        </div>

                        <!-- User Name & Badges -->
                        <div class="profile-user-identity">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <h1 style="font-size: 21px; font-weight: 800; color: #18243a; margin: 0; line-height: 1.2;">
                                    <?php echo account_e($user['name'] ?? 'Member'); ?>
                                </h1>
                                <span style="background: #e11d64; color: #fff; font-size: 10.5px; font-weight: 800; padding: 3px 9px; border-radius: 999px; display: inline-flex; align-items: center; gap: 4px; white-space: nowrap;">
                                    <i class="bi bi-award-fill"></i> <?php echo account_e($user['member_tier'] ?? 'Member Baru'); ?>
                                </span>
                            </div>

                            <div class="profile-user-meta">
                                <span class="profile-user-meta-item">
                                    <i class="bi bi-person-circle text-rosa"></i>
                                    <span>@<?php echo account_e(!empty($user['username']) ? $user['username'] : ($user['phone'] ?? 'member')); ?></span>
                                </span>
                                <?php if (!empty($user['phone'])): ?>
                                    <span class="profile-user-meta-item">
                                        <i class="bi bi-whatsapp text-success"></i>
                                        <span><?php echo account_e($user['phone']); ?></span>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($user['email'])): ?>
                                    <span class="profile-user-meta-item">
                                        <i class="bi bi-envelope text-secondary"></i>
                                        <span><?php echo account_e($user['email']); ?></span>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Right Action / Reward Badges -->
                    <div class="profile-hero-actions">
                        <a href="<?php echo page_url('akun.php?tab=poin'); ?>" class="profile-action-pill pill-points">
                            <i class="bi bi-star-fill text-warning"></i>
                            <span><?php echo (int)($user['points'] ?? 100); ?> Poin Member</span>
                        </a>

                        <a href="<?php echo page_url('akun.php?tab=poin'); ?>" class="profile-action-pill pill-vouchers">
                            <i class="bi bi-ticket-perforated-fill"></i>
                            <span><?php echo count($vouchers); ?> Voucher</span>
                        </a>

                        <a href="<?php echo page_url('logout.php'); ?>" class="profile-action-pill pill-logout" title="Keluar Akun">
                            <i class="bi bi-box-arrow-right"></i>
                            <span class="d-none d-sm-inline">Keluar</span>
                        </a>
                    </div>
                </div>

                <!-- Quick Stats Bar (Total, Aktif, Selesai, Total Belanja) -->
                <div class="profile-stats-grid">
                    <div class="profile-stat-box">
                        <div class="profile-stat-icon"><i class="bi bi-bag-check"></i></div>
                        <div class="profile-stat-info">
                            <strong><?php echo (int)$stats['total']; ?></strong>
                            <span>Total Pesanan</span>
                        </div>
                    </div>
                    <div class="profile-stat-box">
                        <div class="profile-stat-icon" style="background:#fff9df;color:#c98b00;"><i class="bi bi-hourglass-split"></i></div>
                        <div class="profile-stat-info">
                            <strong><?php echo (int)$stats['active']; ?></strong>
                            <span>Sedang Diproses</span>
                        </div>
                    </div>
                    <div class="profile-stat-box">
                        <div class="profile-stat-icon" style="background:#e8f8ef;color:#1e7e46;"><i class="bi bi-check2-circle"></i></div>
                        <div class="profile-stat-info">
                            <strong><?php echo (int)$stats['completed']; ?></strong>
                            <span>Pesanan Selesai</span>
                        </div>
                    </div>
                    <div class="profile-stat-box">
                        <div class="profile-stat-icon"><i class="bi bi-wallet2"></i></div>
                        <div class="profile-stat-info">
                            <strong><?php echo format_rupiah($stats['spent']); ?></strong>
                            <span>Total Belanja</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- =========================================================
                 2. SEGMENTED NAVIGATION TABS (MARKETPLACE PILLS)
                 ========================================================= -->
            <div class="marketplace-tabs-wrap">
                <nav class="marketplace-nav-tabs" role="tablist">
                    <a href="<?php echo page_url('akun.php?tab=profil'); ?>" class="marketplace-tab-btn <?php echo ($tab === 'profil') ? 'active' : ''; ?>">
                        <i class="bi bi-person-vcard"></i>
                        <span>Biodata Diri</span>
                    </a>
                    <a href="<?php echo page_url('akun.php?tab=pesanan'); ?>" class="marketplace-tab-btn <?php echo ($tab === 'pesanan') ? 'active' : ''; ?>">
                        <i class="bi bi-receipt"></i>
                        <span>Pesanan Saya</span>
                        <span class="badge-pill"><?php echo count($orders); ?></span>
                    </a>
                    <a href="<?php echo page_url('akun.php?tab=poin'); ?>" class="marketplace-tab-btn <?php echo ($tab === 'poin') ? 'active' : ''; ?>">
                        <i class="bi bi-stars"></i>
                        <span>Poin &amp; Voucher</span>
                    </a>
                    <a href="<?php echo page_url('akun.php?tab=notifikasi'); ?>" class="marketplace-tab-btn <?php echo ($tab === 'notifikasi') ? 'active' : ''; ?>">
                        <i class="bi bi-bell"></i>
                        <span>Notifikasi</span>
                        <?php if ($unreadNotifCount > 0): ?>
                            <span class="badge-pill"><?php echo $unreadNotifCount; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?php echo page_url('akun.php?tab=alamat'); ?>" class="marketplace-tab-btn <?php echo ($tab === 'alamat') ? 'active' : ''; ?>">
                        <i class="bi bi-geo-alt"></i>
                        <span>Alamat Pengiriman</span>
                    </a>
                </nav>
            </div>

            <!-- =========================================================
                 3. TAB CONTENT: BIODATA DIRI / EDIT PROFIL
                 ========================================================= -->
            <?php if ($tab === 'profil'): ?>
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="profile-card">
                            <div class="profile-card-header">
                                <div>
                                    <h2><i class="bi bi-pencil-square text-rosa"></i> Edit Biodata Diri</h2>
                                    <p>Kelola informasi identitas Anda untuk mempercepat proses pemesanan &amp; cetak nota.</p>
                                </div>
                                <span style="font-size: 11px; background: #eefbf3; color: #1e7e46; font-weight: 700; padding: 4px 10px; border-radius: 8px;">
                                    <i class="bi bi-shield-check"></i> Data Aman
                                </span>
                            </div>

                            <form action="<?php echo page_url('akun.php'); ?>" method="POST" enctype="multipart/form-data" id="editProfileForm">
                                <div class="avatar-upload-card">
                                    <label class="d-block fw-bold mb-2" style="font-size: 13.5px; color: #18243a;">
                                        <i class="bi bi-camera-fill text-rosa me-1"></i> Foto Profil Member
                                    </label>
                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                        <div class="avatar-preview-wrap" style="cursor: pointer;" title="Klik untuk pilih foto" onclick="var inp = document.getElementById('avatarInput'); if (inp) inp.click();">
                                            <img id="avatarPreviewImg" src="<?php echo account_e($userAvatarUrl); ?>" alt="Preview Foto"
                                                 class="avatar-preview-circle" style="<?php echo $hasUserAvatar ? 'display: block;' : 'display: none;'; ?>" onerror="this.style.display='none'; var ph = document.getElementById('avatarPlaceholder'); if(ph) ph.style.display='flex';">
                                            <div id="avatarPlaceholder" class="avatar-preview-placeholder" style="<?php echo $hasUserAvatar ? 'display: none;' : 'display: flex;'; ?>">
                                                <?php echo account_e(mb_substr($user['name'] ?? 'M', 0, 1)); ?>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                                <label for="avatarInput" class="btn-upload-avatar">
                                                    <i class="bi bi-cloud-arrow-up-fill"></i>
                                                    <span id="btnUploadLabel"><?php echo $hasUserAvatar ? 'Ganti Foto Profil' : 'Unggah Foto Baru'; ?></span>
                                                </label>
                                                <input type="file" name="avatar" id="avatarInput" accept="image/jpeg,image/png,image/jpg,image/webp" style="display: none;">

                                                <button type="button" id="btnRemoveAvatar" class="btn-remove-avatar" style="<?php echo $hasUserAvatar ? 'display: inline-flex;' : 'display: none;'; ?>" title="Hapus Foto Profil">
                                                    <i class="bi bi-trash3"></i> Hapus Foto
                                                </button>
                                                <input type="hidden" name="remove_avatar" id="removeAvatarInput" value="0">
                                            </div>
                                            <small class="text-muted d-block" style="font-size: 11.5px; line-height: 1.45;">
                                                Format: JPG, JPEG, PNG, WEBP (Maksimal 4 MB). Foto yang Anda pilih akan <b>otomatis langsung tampil dan tersimpan</b> di profil Anda.
                                            </small>
                                            <div id="avatarSelectedInfo" class="mt-1" style="display: none; font-size: 11.5px;">
                                                <span id="avatarStatusBadge" class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                    <i class="bi bi-check2-circle me-1"></i> <span id="avatarFileName">Foto siap disimpan</span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                    <div class="form-field-group">
                                        <label>Nama Lengkap <span class="req">*</span></label>
                                        <div class="form-input-box">
                                            <i class="bi bi-person input-icon"></i>
                                            <input type="text" name="name" value="<?php echo account_e($user['name'] ?? ''); ?>" required placeholder="Nama lengkap sesuai KTP/ID">
                                        </div>
                                        <small class="form-hint">Nama ini akan dicetak pada invoice / nota resmi percetakan.</small>
                                    </div>

                                    <div class="form-field-group">
                                        <label>Username / Panggilan</label>
                                        <div class="form-input-box">
                                            <i class="bi bi-at input-icon"></i>
                                            <input type="text" name="username" value="<?php echo account_e($user['username'] ?? ''); ?>" placeholder="Contoh: alanroziqin">
                                        </div>
                                        <small class="form-hint">Nama panggilan unik akun Member Anda.</small>
                                    </div>
                                </div>

                                <div class="form-grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                    <div class="form-field-group">
                                        <label>Nomor WhatsApp Aktif <span class="req">*</span></label>
                                        <div class="form-input-box">
                                            <i class="bi bi-whatsapp input-icon text-success"></i>
                                            <input type="tel" name="phone" value="<?php echo account_e($user['phone'] ?? ''); ?>" required placeholder="08xxxxxxxxxx">
                                        </div>
                                        <small class="form-hint">Digunakan untuk konfirmasi pesanan, proof desain &amp; kirim file.</small>
                                    </div>

                                    <div class="form-field-group">
                                        <label>Alamat Email</label>
                                        <div class="form-input-box">
                                            <i class="bi bi-envelope input-icon"></i>
                                            <input type="email" name="email" value="<?php echo account_e($user['email'] ?? ''); ?>" placeholder="emailanda@example.com">
                                        </div>
                                        <small class="form-hint">Salinan invoice &amp; update status akan dikirim ke email ini.</small>
                                    </div>
                                </div>

                                <div class="form-grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                    <div class="form-field-group">
                                        <label>Jenis Kelamin</label>
                                        <div class="gender-radio-group">
                                            <div class="gender-radio-pill">
                                                <input type="radio" name="gender" id="gender_l" value="L" <?php echo (empty($user['gender']) || $user['gender'] === 'L') ? 'checked' : ''; ?>>
                                                <label for="gender_l" class="gender-radio-label">
                                                    <i class="bi bi-gender-male"></i> Laki-laki
                                                </label>
                                            </div>
                                            <div class="gender-radio-pill">
                                                <input type="radio" name="gender" id="gender_p" value="P" <?php echo (($user['gender'] ?? '') === 'P') ? 'checked' : ''; ?>>
                                                <label for="gender_p" class="gender-radio-label">
                                                    <i class="bi bi-gender-female"></i> Perempuan
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-field-group">
                                        <label>Tanggal Lahir</label>
                                        <div class="form-input-box">
                                            <i class="bi bi-calendar-event input-icon"></i>
                                            <input type="date" name="birth_date" value="<?php echo account_e($user['birth_date'] ?? ''); ?>">
                                        </div>
                                        <small class="form-hint">Dapatkan promo &amp; voucher potongan di hari ulang tahun Anda.</small>
                                    </div>
                                </div>

                                <hr style="border: 0; border-top: 1px solid #f6e8ef; margin: 10px 0 20px;">

                                <div class="form-grid-2" style="display: grid; grid-template-columns: 1fr 2fr; gap: 16px;">
                                    <div class="form-field-group">
                                        <label>Kota / Wilayah Pengiriman</label>
                                        <div class="form-input-box">
                                            <i class="bi bi-buildings input-icon"></i>
                                            <input type="text" name="city" value="<?php echo account_e($user['city'] ?? 'Pasuruan'); ?>" placeholder="Contoh: Pasuruan">
                                        </div>
                                    </div>

                                    <div class="form-field-group">
                                        <label>Catatan / Patokan Alamat Pengantaran</label>
                                        <div class="form-input-box">
                                            <i class="bi bi-signpost-2 input-icon"></i>
                                            <input type="text" name="address_note" value="<?php echo account_e($user['address_note'] ?? ''); ?>" placeholder="Contoh: Seberang Masjid Jami, Pagar Hitam">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-field-group">
                                    <label>Alamat Lengkap Rumah / Kantor / Toko</label>
                                    <textarea name="address" rows="3" placeholder="Tuliskan nama jalan, nomor rumah, RT/RW, dusun, desa/kelurahan, dan kecamatan secara lengkap..."><?php echo account_e($user['address'] ?? ''); ?></textarea>
                                    <small class="form-hint">Memudahkan kurir mengantarkan spanduk, banner, undangan, dan paket cetak langsung ke lokasi Anda.</small>
                                </div>

                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mt-4 pt-2">
                                    <button type="submit" class="btn-save-profile">
                                        <i class="bi bi-check2-circle" style="font-size: 18px;"></i> Simpan Perubahan Profil
                                    </button>
                                    <a href="<?php echo page_url('produk.php'); ?>" style="color: #8c7f88; text-decoration: none; font-size: 13.5px; font-weight: 700;">
                                        Batal &amp; Lanjut Belanja
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="profile-card" style="background: linear-gradient(135deg, #18243a 0%, #2a1f28 100%); color: #ffffff; border: 0; box-shadow: 0 12px 35px rgba(24, 36, 58, 0.2);">
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <div>
                                    <small style="color: #f472b6; font-size: 11px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase;">KARTU MEMBER DIGITAL</small>
                                    <h3 style="color: #ffffff; font-size: 18px; font-weight: 800; margin: 3px 0 0;">Rosa Indah Printing</h3>
                                </div>
                                <i class="bi bi-qr-code" style="font-size: 28px; color: #fff;"></i>
                            </div>

                            <div style="margin: 25px 0;">
                                <div style="font-size: 16px; font-weight: 800; letter-spacing: 1.5px; font-family: monospace;">
                                    RIP-MEMBER-<?php echo str_pad((string)($user['id'] ?? 1), 5, '0', STR_PAD_LEFT); ?>
                                </div>
                                <div style="font-size: 14px; color: #e2d9df; margin-top: 4px;">
                                    <?php echo account_e($user['name'] ?? 'Member'); ?>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-end pt-3" style="border-top: 1px solid rgba(255,255,255,0.15);">
                                <div>
                                    <small style="color: #b5a9b2; font-size: 10.5px; display: block;">STATUS MEMBER</small>
                                    <strong style="color: #38ef7d; font-size: 13px;"><i class="bi bi-shield-fill-check"></i> Terverifikasi</strong>
                                </div>
                                <div class="text-end">
                                    <small style="color: #b5a9b2; font-size: 10.5px; display: block;">SALDO POIN</small>
                                    <strong style="color: #ffd700; font-size: 15px;"><i class="bi bi-star-fill"></i> <?php echo (int)($user['points'] ?? 150); ?> Poin</strong>
                                </div>
                            </div>
                        </div>

                        <div class="profile-card" style="background: #fff9fc; border-color: #f2d1e0;">
                            <h4 style="font-size: 14px; font-weight: 800; color: #18243a; margin-bottom: 10px; display: flex; align-items: center; gap: 7px;">
                                <i class="bi bi-lightbulb-fill text-warning"></i> Tips Akun Member:
                            </h4>
                            <ul style="padding-left: 18px; margin: 0; font-size: 12.5px; color: #6f626b; line-height: 1.7;">
                                <li>Pastikan <b>nomor WhatsApp</b> benar karena file revisi desain cetak dikirim via WA.</li>
                                <li>Kumpulkan <b>poin belanja</b> setiap pesanan cetak dan tukarkan dengan voucher potongan.</li>
                                <li>Gunakan patokan alamat yang jelas untuk mempermudah kurir saat kirim banner/undangan.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- =========================================================
                 4. TAB CONTENT: PESANAN SAYA
                 ========================================================= -->
            <?php if ($tab === 'pesanan'): ?>
                <div class="profile-card">
                    <div class="profile-card-header">
                        <div>
                            <h2><i class="bi bi-receipt text-rosa"></i> Riwayat &amp; Status Pesanan</h2>
                            <p>Pantau status produksi, cetak nota, dan cek tracking pesanan cetak Anda.</p>
                        </div>
                        <a href="<?php echo page_url('lacak-pesanan.php'); ?>" style="color: #e11d64; font-size: 12.5px; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                            <i class="bi bi-search"></i> Lacak Nomor Lain
                        </a>
                    </div>

                    <?php if (empty($orders)): ?>
                        <div style="text-align: center; padding: 50px 20px;">
                            <i class="bi bi-inbox" style="font-size: 48px; color: #d0c0c9;"></i>
                            <h4 style="margin: 14px 0 6px; font-size: 17px; font-weight: 800; color: #18243a;">Belum Ada Riwayat Pesanan</h4>
                            <p style="color: #8c7e87; font-size: 13px; max-width: 420px; margin: 0 auto 18px;">Pesanan yang Anda buat melalui website akan otomatis tercatat dan dapat dipantau di sini.</p>
                            <a href="<?php echo page_url('produk.php'); ?>" class="btn-save-profile" style="padding: 10px 22px; font-size: 13.5px;">
                                <i class="bi bi-bag"></i> Mulai Belanja Cetak
                            </a>
                        </div>
                    <?php else: ?>
                        <div>
                            <?php
                            $statusMap = [
                                'pending'    => ['label' => 'Pesanan Baru', 'bg' => '#fff0f6', 'color' => '#e11d64'],
                                'confirmed'  => ['label' => 'Dikonfirmasi', 'bg' => '#e8f4fd', 'color' => '#0d6efd'],
                                'processing' => ['label' => 'Sedang Didesain / Cetak', 'bg' => '#fff9df', 'color' => '#b27b00'],
                                'printed'    => ['label' => 'Selesai Cetak', 'bg' => '#e3f7fc', 'color' => '#0288d1'],
                                'completed'  => ['label' => 'Selesai Diambil / Kirim', 'bg' => '#e8f8ef', 'color' => '#1e7e46'],
                                'cancelled'  => ['label' => 'Dibatalkan', 'bg' => '#fbe9eb', 'color' => '#dc3545'],
                            ];
                            foreach ($orders as $o):
                                $stKey = $o['status'] ?? 'pending';
                                $st = $statusMap[$stKey] ?? ['label' => $stKey, 'bg' => '#f0f0f0', 'color' => '#555'];
                                $isPaid = (($o['payment_status'] ?? '') === 'paid');
                            ?>
                                <div class="profile-order-card">
                                    <div class="profile-order-header">
                                        <div>
                                            <strong style="color: #18243a; font-size: 14.5px;"><?php echo account_e($o['order_code'] ?? ''); ?></strong>
                                            <small style="color: #8c7e87; display: block; font-size: 11.5px; margin-top: 2px;">
                                                <i class="bi bi-clock"></i> <?php echo account_e($o['created_at'] ?? ''); ?> WIB
                                            </small>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span style="background: <?php echo $st['bg']; ?>; color: <?php echo $st['color']; ?>; font-size: 11.5px; font-weight: 800; padding: 4px 10px; border-radius: 999px;">
                                                <?php echo account_e($st['label']); ?>
                                            </span>
                                            <span style="background: <?php echo $isPaid ? '#e8f8ef' : '#fff0f0'; ?>; color: <?php echo $isPaid ? '#1e7e46' : '#d63031'; ?>; font-size: 11.5px; font-weight: 800; padding: 4px 10px; border-radius: 999px;">
                                                <?php echo $isPaid ? 'Lunas' : 'Belum Lunas'; ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div style="margin: 12px 0;">
                                        <?php if (!empty($o['items'])): ?>
                                            <?php foreach ($o['items'] as $item): ?>
                                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; font-size: 13px;">
                                                    <div style="color: #3b333a; font-weight: 700;">
                                                        <i class="bi bi-box-seam text-rosa me-1"></i> <?php echo account_e($item['product_name'] ?? 'Produk Cetak'); ?>
                                                        <span style="color: #8c7e87; font-weight: 500;">x <?php echo (int)($item['quantity'] ?? 1); ?> <?php echo account_e($item['unit'] ?? 'pcs'); ?></span>
                                                    </div>
                                                    <div style="color: #635761; font-variant-numeric: tabular-nums;">
                                                        <?php echo format_rupiah($item['subtotal'] ?? ((float)($item['unit_price'] ?? 0) * (int)($item['quantity'] ?? 1))); ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div style="font-size: 13px; color: #635761;">Pesanan Cetak Custom</div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="profile-order-footer d-flex justify-content-between align-items-center pt-3" style="border-top: 1px dashed #f0dce6;">
                                        <div>
                                            <small style="font-size: 11px; color: #8c7f88; display: block;">Total Pembayaran:</small>
                                            <strong style="font-size: 17px; font-weight: 800; color: #e11d64;">
                                                <?php echo format_rupiah($o['total_estimate'] ?? 0); ?>
                                            </strong>
                                        </div>

                                        <div class="profile-order-actions d-flex gap-2 flex-wrap">
                                            <a href="<?php echo page_url('lacak-pesanan.php?code=' . urlencode((string)($o['order_code'] ?? '')) . '&phone=' . urlencode((string)($user['phone'] ?? ''))); ?>" style="border: 1px solid #e11d64; color: #e11d64; background: #fff; padding: 7px 12px; border-radius: 9px; font-size: 12px; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                                                <i class="bi bi-geo-alt"></i> Lacak
                                            </a>

                                            <a href="<?php echo page_url('invoice.php?code=' . urlencode((string)($o['order_code'] ?? '')) . '&phone=' . urlencode((string)($user['phone'] ?? ''))); ?>" target="_blank" style="background: #18243a; color: #ffffff; padding: 7px 12px; border-radius: 9px; font-size: 12px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                                                <i class="bi bi-printer"></i> Nota PDF
                                            </a>

                                            <?php if (!$isPaid): ?>
                                                <a href="<?php echo page_url('lacak-pesanan.php?code=' . urlencode((string)($o['order_code'] ?? '')) . '&phone=' . urlencode((string)($user['phone'] ?? ''))); ?>" style="background: #25d366; color: #ffffff; padding: 7px 12px; border-radius: 9px; font-size: 12px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                                                    <i class="bi bi-cash-stack"></i> Bayar
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- =========================================================
                 5. TAB CONTENT: POIN & VOUCHER MEMBER
                 ========================================================= -->
            <?php if ($tab === 'poin'): ?>
                <div class="row g-4">
                    <div class="col-lg-5">
                        <div class="profile-card" style="background: linear-gradient(135deg, #fffcf5 0%, #fff7dc 100%); border-color: #fce8a6;">
                            <h2 style="font-size: 17px; font-weight: 800; color: #8f6500; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                                <i class="bi bi-stars" style="font-size: 22px;"></i> Saldo Poin Saya
                            </h2>
                            <div style="font-size: 38px; font-weight: 800; color: #d67a00; font-variant-numeric: tabular-nums;">
                                <?php echo (int)($user['points'] ?? 100); ?> <span style="font-size: 16px; font-weight: 700; color: #8f6500;">Poin</span>
                            </div>
                            <p style="font-size: 12.5px; color: #7f6629; margin: 10px 0 16px; line-height: 1.6;">
                                Kumpulkan poin setiap pemesanan cetak di Rosa Indah Printing. Setiap kelipatan belanja <b><?php echo format_rupiah($loyaltyRule['points_per_amount'] ?? 10000); ?></b> pada pesanan yang berstatus <b>Selesai</b> otomatis mendapatkan <b>1 Poin</b> reward.
                            </p>
                            <a href="<?php echo page_url('produk.php'); ?>" class="btn-save-profile" style="padding: 10px 20px; font-size: 13px;">
                                <i class="bi bi-cart-plus"></i> Belanja Tambah Poin
                            </a>
                        </div>

                        <!-- Tukar Poin Jadi Voucher Reward -->
                        <div class="profile-card">
                            <div class="profile-card-header">
                                <div>
                                    <h2><i class="bi bi-gift-fill text-rosa"></i> Tukar Poin Reward</h2>
                                    <p>Tukarkan poin Anda menjadi kode voucher potongan belanja.</p>
                                </div>
                            </div>

                            <div style="display: grid; gap: 12px;">
                                <?php foreach ($loyaltyRewards as $rw):
                                    $rwCost = (int)($rw['points_cost'] ?? 100);
                                    $canRedeem = ((int)($user['points'] ?? 100) >= $rwCost);
                                    $rwDiscLabel = (($rw['discount_type'] ?? 'fixed') === 'percent')
                                        ? ((int)$rw['discount_value'] . '%')
                                        : format_rupiah($rw['discount_value'] ?? 0);
                                ?>
                                    <div style="border: 1px solid #f2dce7; background: #fffbfd; border-radius: 14px; padding: 14px; display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap;">
                                        <div>
                                            <span style="display: inline-block; background: #fff9df; color: #9c6c00; border: 1px solid #fae69e; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 6px; margin-bottom: 4px;">
                                                <i class="bi bi-star-fill text-warning"></i> <?php echo $rwCost; ?> Poin
                                            </span>
                                            <strong style="display: block; color: #18243a; font-size: 13.5px;"><?php echo account_e($rw['name'] ?? 'Reward Member'); ?></strong>
                                            <small style="color: #796c76; font-size: 11.5px;">
                                                Diskon <?php echo $rwDiscLabel; ?> • Min. <?php echo format_rupiah($rw['min_order'] ?? 0); ?>
                                            </small>
                                        </div>
                                        <form method="POST" action="<?php echo page_url('akun.php?tab=poin'); ?>" style="margin: 0;">
                                            <input type="hidden" name="action" value="redeem_reward">
                                            <input type="hidden" name="reward_id" value="<?php echo (int)($rw['id'] ?? 0); ?>">
                                            <button type="submit" <?php echo $canRedeem ? '' : 'disabled'; ?> style="border: 0; background: <?php echo $canRedeem ? 'linear-gradient(90deg, #e11d64, #f43f7e)' : '#e9dde3'; ?>; color: <?php echo $canRedeem ? '#fff' : '#9c8e98'; ?>; padding: 7px 14px; border-radius: 8px; font-weight: 800; font-size: 12px; cursor: <?php echo $canRedeem ? 'pointer' : 'not-allowed'; ?>;">
                                                <?php echo $canRedeem ? 'Tukar Poin' : 'Poin Belum Cukup'; ?>
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <div class="profile-card">
                            <div class="profile-card-header">
                                <div>
                                    <h2><i class="bi bi-ticket-perforated text-rosa"></i> Kupon &amp; Voucher Siap Pakai</h2>
                                    <p>Salin dan masukkan kode voucher saat Checkout untuk potongan harga langsung.</p>
                                </div>
                            </div>

                            <div style="display: grid; gap: 12px;">
                                <?php foreach ($vouchers as $v):
                                    $isPersonalReward = !empty($v['customer_id']);
                                ?>
                                    <div style="border: 1.5px dashed <?php echo $isPersonalReward ? '#eab308' : '#f2bad0'; ?>; background: <?php echo $isPersonalReward ? '#fffdf5' : '#fffafc'; ?>; border-radius: 14px; padding: 14px 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <span style="display: inline-block; background: #e11d64; color: #fff; font-size: 11px; font-weight: 800; padding: 3px 8px; border-radius: 6px; letter-spacing: 0.5px;">
                                                    <?php echo account_e($v['code']); ?>
                                                </span>
                                                <?php if ($isPersonalReward): ?>
                                                    <span style="background: #fef08a; color: #854d0e; font-size: 10px; font-weight: 800; padding: 2px 7px; border-radius: 999px;">Hasil Tukar Poin</span>
                                                <?php endif; ?>
                                            </div>
                                            <strong style="display: block; color: #18243a; font-size: 14px;"><?php echo account_e($v['name']); ?></strong>
                                            <small style="color: #796c76; font-size: 11.5px;">
                                                Min. Order <?php echo format_rupiah($v['min_order'] ?? 0); ?> • Diskon <?php echo (($v['discount_type'] ?? 'percent') === 'percent') ? (float)$v['discount_value'] . '%' : format_rupiah($v['discount_value'] ?? 0); ?>
                                            </small>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <button type="button" onclick="navigator.clipboard.writeText('<?php echo account_e($v['code']); ?>'); this.innerText='Tersalin!'; setTimeout(() => this.innerText='Salin Kode', 2000);" style="border: 1px solid #e11d64; background: #fff; color: #e11d64; padding: 7px 14px; border-radius: 8px; font-weight: 800; font-size: 12px; cursor: pointer;">
                                                Salin Kode
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <?php if (!empty($loyaltyTransactions)): ?>
                            <div class="profile-card">
                                <div class="profile-card-header">
                                    <div>
                                        <h2><i class="bi bi-clock-history text-rosa"></i> Riwayat Poin Member</h2>
                                        <p>Catatan perolehan poin belanja, bonus seller, dan penukaran reward.</p>
                                    </div>
                                </div>
                                <div style="display: grid; gap: 10px;">
                                    <?php foreach ($loyaltyTransactions as $lt):
                                        $pts = (int)($lt['points'] ?? 0);
                                        $isPlus = ($pts >= 0);
                                    ?>
                                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; border: 1px solid #f4e6ee; border-radius: 12px; background: #fff;">
                                            <div>
                                                <strong style="font-size: 13px; color: #18243a; display: block;"><?php echo account_e($lt['description'] ?? 'Transaksi Poin'); ?></strong>
                                                <small style="font-size: 11px; color: #8c7e87;"><?php echo account_e($lt['created_at'] ?? ''); ?></small>
                                            </div>
                                            <span style="font-size: 13.5px; font-weight: 800; color: <?php echo $isPlus ? '#1e7e46' : '#dc3545'; ?>;">
                                                <?php echo ($isPlus ? '+' : '') . $pts; ?> Poin
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- =========================================================
                 5B. TAB CONTENT: PUSAT NOTIFIKASI MEMBER
                 ========================================================= -->
            <?php if ($tab === 'notifikasi'): ?>
                <div class="profile-card">
                    <div class="profile-card-header flex-wrap gap-2">
                        <div>
                            <h2><i class="bi bi-bell-fill text-rosa"></i> Pusat Notifikasi Member</h2>
                            <p>Semua informasi promo/pengumuman dari Seller serta update otomatis status pesanan &amp; pembayaran Anda.</p>
                        </div>
                        <?php if (!empty($memberNotifications)): ?>
                            <form method="POST" action="<?php echo page_url('akun.php?tab=notifikasi'); ?>" style="margin: 0;">
                                <input type="hidden" name="action" value="mark_notif_read">
                                <button type="submit" style="border: 1px solid #f2c9de; background: #fff0f6; color: #e11d64; padding: 7px 14px; border-radius: 10px; font-size: 12px; font-weight: 800; cursor: pointer;">
                                    <i class="bi bi-check2-all"></i> Tandai Semua Dibaca
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($memberNotifications)): ?>
                        <div style="text-align: center; padding: 40px 20px;">
                            <i class="bi bi-bell-slash" style="font-size: 42px; color: #d0c0c9;"></i>
                            <h4 style="margin: 12px 0 6px; font-size: 16px; font-weight: 800; color: #18243a;">Belum Ada Notifikasi</h4>
                            <p style="color: #8c7e87; font-size: 13px; max-width: 420px; margin: 0 auto;">Notifikasi update pesanan, verifikasi pembayaran, poin reward, dan info promo dari Seller akan tampil di sini.</p>
                        </div>
                    <?php else: ?>
                        <div style="display: grid; gap: 12px;">
                            <?php foreach ($memberNotifications as $n):
                                $nType = (string)($n['type'] ?? 'general');
                                $badgeLabel = 'Info Seller';
                                $badgeBg = '#fff0f6';
                                $badgeColor = '#e11d64';
                                $iconClass = 'bi-megaphone-fill';
                                if (in_array($nType, ['order', 'status'], true)) {
                                    $badgeLabel = 'Status Pesanan';
                                    $badgeBg = '#e8f4fd';
                                    $badgeColor = '#0d6efd';
                                    $iconClass = 'bi-box-seam-fill';
                                } elseif ($nType === 'payment') {
                                    $badgeLabel = 'Info Pembayaran';
                                    $badgeBg = '#e8f8ef';
                                    $badgeColor = '#1e7e46';
                                    $iconClass = 'bi-credit-card-2-front-fill';
                                } elseif (in_array($nType, ['points', 'bonus', 'reward'], true)) {
                                    $badgeLabel = 'Poin & Reward';
                                    $badgeBg = '#fff9df';
                                    $badgeColor = '#b27b00';
                                    $iconClass = 'bi-star-fill';
                                } elseif (in_array($nType, ['promo', 'voucher'], true)) {
                                    $badgeLabel = 'Promo & Voucher';
                                    $badgeBg = '#fff0f6';
                                    $badgeColor = '#e11d64';
                                    $iconClass = 'bi-ticket-perforated-fill';
                                }
                            ?>
                                <div style="border: 1px solid #f0dce6; background: <?php echo empty($n['is_read']) ? '#fff9fc' : '#ffffff'; ?>; border-radius: 16px; padding: 15px 18px; display: flex; justify-content: space-between; align-items: flex-start; gap: 14px; flex-wrap: wrap;">
                                    <div style="display: flex; gap: 12px; align-items: flex-start; flex: 1; min-width: 220px;">
                                        <div style="width: 40px; height: 40px; border-radius: 12px; background: <?php echo $badgeBg; ?>; color: <?php echo $badgeColor; ?>; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0;">
                                            <i class="bi <?php echo $iconClass; ?>"></i>
                                        </div>
                                        <div>
                                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                                <span style="background: <?php echo $badgeBg; ?>; color: <?php echo $badgeColor; ?>; font-size: 10.5px; font-weight: 800; padding: 2px 8px; border-radius: 999px;">
                                                    <?php echo account_e($badgeLabel); ?>
                                                </span>
                                                <small style="color: #8c7e87; font-size: 11.5px;">
                                                    <i class="bi bi-clock"></i> <?php echo account_e($n['created_at'] ?? ''); ?>
                                                </small>
                                            </div>
                                            <strong style="display: block; color: #18243a; font-size: 14px; margin-bottom: 3px;"><?php echo account_e($n['title'] ?? ''); ?></strong>
                                            <p style="margin: 0; font-size: 13px; color: #5b5058; line-height: 1.5;"><?php echo account_e($n['message'] ?? ''); ?></p>
                                        </div>
                                    </div>
                                    <?php if (!empty($n['link'])): ?>
                                        <a href="<?php echo account_e($n['link']); ?>" style="border: 1px solid #f2c9de; background: #fff; color: #e11d64; padding: 7px 13px; border-radius: 9px; font-size: 12px; font-weight: 800; text-decoration: none; white-space: nowrap;">
                                            Buka Detail &rarr;
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- =========================================================
                 6. TAB CONTENT: ALAMAT PENGIRIMAN
                 ========================================================= -->
            <?php if ($tab === 'alamat'): ?>
                <div class="profile-card">
                    <div class="profile-card-header">
                        <div>
                            <h2><i class="bi bi-geo-alt text-rosa"></i> Alamat Pengiriman Utama</h2>
                            <p>Alamat tujuan untuk kurir dan pengiriman hasil pesanan percetakan Anda.</p>
                        </div>
                        <a href="<?php echo page_url('akun.php?tab=profil'); ?>" class="btn-save-profile" style="padding: 8px 16px; font-size: 12.5px;">
                            <i class="bi bi-pencil"></i> Ubah Alamat
                        </a>
                    </div>

                    <div style="border: 1.5px solid #f2dce7; background: #fffcfd; border-radius: 16px; padding: 20px;">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span style="background: #e11d64; color: #fff; font-size: 10.5px; font-weight: 800; padding: 2px 8px; border-radius: 6px;">ALAMAT UTAMA</span>
                            <strong style="color: #18243a; font-size: 15px;"><?php echo account_e($user['name'] ?? 'Member'); ?></strong>
                            <span style="color: #8c7e87; font-size: 13px;">(<?php echo account_e($user['phone'] ?? ''); ?>)</span>
                        </div>

                        <p style="color: #4b4149; font-size: 14px; line-height: 1.6; margin: 8px 0 10px;">
                            <?php echo !empty($user['address']) ? account_e($user['address']) : 'Alamat lengkap belum diisi. Silakan lengkapi pada tab Biodata Diri.'; ?>
                        </p>

                        <?php if (!empty($user['city'])): ?>
                            <div style="font-size: 13px; color: #6d616a; margin-bottom: 6px;">
                                <i class="bi bi-buildings text-rosa me-1"></i> Kota / Kecamatan: <b><?php echo account_e($user['city']); ?></b>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($user['address_note'])): ?>
                            <div style="background: #fff0f6; border-left: 3px solid #e11d64; padding: 8px 12px; border-radius: 4px; font-size: 12.5px; color: #87254e;">
                                <i class="bi bi-info-circle-fill me-1"></i> Patokan Kurir: <?php echo account_e($user['address_note']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const avatarInput = document.getElementById('avatarInput');
        const avatarPreviewImg = document.getElementById('avatarPreviewImg');
        const avatarPlaceholder = document.getElementById('avatarPlaceholder');
        const heroAvatarImg = document.getElementById('heroAvatarImg');
        const heroAvatarInitial = document.getElementById('heroAvatarInitial');
        const navAvatarImg = document.getElementById('navAvatarImg');
        const navAvatarIcon = document.getElementById('navAvatarIcon');
        const avatarSelectedInfo = document.getElementById('avatarSelectedInfo');
        const avatarFileName = document.getElementById('avatarFileName');
        const btnUploadLabel = document.getElementById('btnUploadLabel');
        const btnRemoveAvatar = document.getElementById('btnRemoveAvatar');
        const removeAvatarInput = document.getElementById('removeAvatarInput');

        function applyAvatarToAllIcons(srcUrl) {
            if (srcUrl) {
                if (avatarPreviewImg) {
                    avatarPreviewImg.src = srcUrl;
                    avatarPreviewImg.style.display = 'block';
                }
                if (avatarPlaceholder) {
                    avatarPlaceholder.style.display = 'none';
                }
                if (heroAvatarImg) {
                    heroAvatarImg.src = srcUrl;
                    heroAvatarImg.style.display = 'block';
                }
                if (heroAvatarInitial) {
                    heroAvatarInitial.style.display = 'none';
                }
                if (navAvatarImg) {
                    navAvatarImg.src = srcUrl;
                    navAvatarImg.style.display = 'inline-block';
                }
                if (navAvatarIcon) {
                    navAvatarIcon.style.display = 'none';
                }
            } else {
                if (avatarPreviewImg) {
                    avatarPreviewImg.src = '';
                    avatarPreviewImg.style.display = 'none';
                }
                if (avatarPlaceholder) {
                    avatarPlaceholder.style.display = 'flex';
                }
                if (heroAvatarImg) {
                    heroAvatarImg.src = '';
                    heroAvatarImg.style.display = 'none';
                }
                if (heroAvatarInitial) {
                    heroAvatarInitial.style.display = 'flex';
                }
                if (navAvatarImg) {
                    navAvatarImg.src = '';
                    navAvatarImg.style.display = 'none';
                }
                if (navAvatarIcon) {
                    navAvatarIcon.style.display = 'inline-block';
                }
            }
        }

        if (avatarInput) {
            avatarInput.addEventListener('change', function() {
                const file = this.files && this.files[0];
                if (file) {
                    if (file.size > 4 * 1024 * 1024) {
                        alert('Ukuran foto terlalu besar. Maksimal ukuran foto adalah 4 MB.');
                        this.value = '';
                        return;
                    }
                    const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
                    if (!validTypes.includes(file.type.toLowerCase())) {
                        alert('Format file tidak didukung. Harap pilih gambar dengan format JPG, PNG, atau WEBP.');
                        this.value = '';
                        return;
                    }

                    // 1. Tampilkan preview langsung di SEMUA icon foto profil (Hero atas, Form, dan Navbar)
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        applyAvatarToAllIcons(e.target.result);
                        if (avatarFileName) {
                            avatarFileName.textContent = 'Menyimpan foto profil otomatis...';
                        }
                        if (avatarSelectedInfo) {
                            avatarSelectedInfo.style.display = 'block';
                        }
                        if (btnUploadLabel) {
                            btnUploadLabel.textContent = 'Ganti Foto Lain';
                        }
                        if (btnRemoveAvatar) {
                            btnRemoveAvatar.style.display = 'inline-flex';
                        }
                        if (removeAvatarInput) {
                            removeAvatarInput.value = '0';
                        }
                    };
                    reader.readAsDataURL(file);

                    // 2. Upload & simpan foto secara otomatis ke server tanpa harus klik tombol Simpan di bawah
                    const formData = new FormData();
                    formData.append('ajax_avatar', '1');
                    formData.append('remove_avatar', '0');
                    formData.append('avatar', file);

                    fetch('<?php echo page_url('akun.php'); ?>', {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (data && data.ok) {
                            if (data.avatar_url) {
                                const bustUrl = data.avatar_url.indexOf('data:') === 0
                                    ? data.avatar_url
                                    : data.avatar_url + (data.avatar_url.indexOf('?') > -1 ? '&' : '?') + 't=' + Date.now();
                                applyAvatarToAllIcons(bustUrl);
                            }
                            if (avatarFileName) {
                                avatarFileName.textContent = data.message || 'Foto profil berhasil dipasang & tersimpan otomatis!';
                            }
                            avatarInput.value = '';
                        } else if (data && data.error) {
                            if (avatarFileName) {
                                avatarFileName.textContent = data.error;
                            }
                        }
                    })
                    .catch(function() {
                        if (avatarFileName) {
                            avatarFileName.textContent = file.name + ' (' + Math.round(file.size / 1024) + ' KB) siap disimpan';
                        }
                    });
                }
            });
        }

        if (btnRemoveAvatar) {
            btnRemoveAvatar.addEventListener('click', function(e) {
                e.preventDefault();
                if (avatarInput) avatarInput.value = '';
                applyAvatarToAllIcons('');
                if (removeAvatarInput) removeAvatarInput.value = '1';
                if (btnUploadLabel) btnUploadLabel.textContent = 'Unggah Foto Baru';
                btnRemoveAvatar.style.display = 'none';
                if (avatarSelectedInfo) {
                    avatarSelectedInfo.style.display = 'block';
                    if (avatarFileName) {
                        avatarFileName.textContent = 'Menghapus foto profil...';
                    }
                }

                const formData = new FormData();
                formData.append('ajax_avatar', '1');
                formData.append('remove_avatar', '1');

                fetch('<?php echo page_url('akun.php'); ?>', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data && data.ok && avatarFileName) {
                        avatarFileName.textContent = data.message || 'Foto profil telah dihapus.';
                    }
                })
                .catch(function() {
                    if (avatarFileName) {
                        avatarFileName.textContent = 'Foto profil akan dihapus saat Anda klik Simpan';
                    }
                });
            });
        }
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
