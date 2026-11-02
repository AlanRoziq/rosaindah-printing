<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/products-data.php';
require_once __DIR__ . '/includes/cart.php';
require_once __DIR__ . '/includes/order-tracking.php';
require_once __DIR__ . '/config/customer-auth.php';
require_once __DIR__ . '/includes/promotions.php';
require_once __DIR__ . '/includes/loyalty.php';

$pdo = db();
if ($pdo) {
    try {
        loyalty_ensure_tables($pdo);
        customer_auth_init($pdo);
        ensure_promotions_tables($pdo);
    } catch (Throwable $e) {
    }
}

cart_init();

if (!function_exists('format_rupiah')) {
    function format_rupiah($amount): string {
        return 'Rp' . number_format((float)$amount, 0, ',', '.');
    }
}

$loggedCustomer = customer_logged_in() ? customer_user() : null;
$error = '';
$buyId = trim($_GET['buy'] ?? ($_POST['buy'] ?? ''));
$buyQty = max(1, (int)($_GET['qty'] ?? ($_POST['qty'] ?? 1)));
$buyProduct = null;
if ($buyId !== '') {
    foreach ($products as $item) {
        if ($item['id'] === $buyId) {
            $buyProduct = $item;
            break;
        }
    }
}

$items = [];
if ($buyProduct) {
    $unitPrice = product_unit_price($buyProduct, $buyQty);
    $items[] = [
        'product'   => $buyProduct,
        'quantity'  => $buyQty,
        'unitPrice' => $unitPrice,
        'subtotal'  => $unitPrice * $buyQty,
    ];
} else {
    foreach (cart_items() as $productId => $row) {
        foreach ($products as $item) {
            if ($item['id'] !== $productId) continue;
            $qty = max(1, (int)$row['quantity']);
            $unitPrice = product_unit_price($item, $qty);
            $items[] = [
                'product'   => $item,
                'quantity'  => $qty,
                'unitPrice' => $unitPrice,
                'subtotal'  => $unitPrice * $qty,
            ];
            break;
        }
    }
}

if (empty($items) && ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: ' . page_url('cart.php'));
    exit;
}

$designTiers = [
    'easy'   => ['code' => 'easy',   'name' => 'Easy',   'label' => 'Easy (Desain Sederhana)',          'price' => 5000,  'desc' => 'Ganti teks/nama, tambah kontak/WA, atau tata letak simpel 1 sisi.'],
    'medium' => ['code' => 'medium', 'name' => 'Medium', 'label' => 'Medium (Desain Menengah)',         'price' => 7000,  'desc' => 'Kombinasi beberapa foto, elemen grafis promosi, stiker produk, atau spanduk acara.'],
    'hard'   => ['code' => 'hard',   'name' => 'Hard',   'label' => 'Hard (Desain Kompleks / Custom)',  'price' => 10000, 'desc' => 'Desain dari nol penuh detail, brosur/undangan lipat, banyak komponen & tata letak khusus.'],
];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $csrfToken = $_POST['csrf'] ?? '';
    if ($csrfToken !== '' && !cart_verify_csrf($csrfToken)) {
        $error = 'Sesi checkout tidak valid. Silakan muat ulang halaman.';
    } elseif (empty($items)) {
        $error = 'Keranjang belanja masih kosong.';
    } else {
        $name = trim($_POST['customer_name'] ?? ($loggedCustomer['name'] ?? ''));
        $phone = trim($_POST['customer_phone'] ?? ($loggedCustomer['phone'] ?? ''));
        $address = trim($_POST['customer_address'] ?? ($loggedCustomer['address'] ?? ''));
        $customerNoteInput = trim($_POST['customer_note'] ?? ($_POST['notes'] ?? ''));
        $paymentMethod = trim($_POST['payment_method'] ?? 'transfer');
        if (!in_array($paymentMethod, ['transfer', 'qris', 'cash'], true)) {
            $paymentMethod = 'transfer';
        }
        $voucherCode = strtoupper(trim($_POST['voucher_code'] ?? ''));

        $designOption = trim($_POST['design_option'] ?? 'upload');
        if (!in_array($designOption, ['upload', 'seller_design'], true)) {
            $designOption = 'upload';
        }
        $designTierKey = trim($_POST['design_tier'] ?? 'easy');
        if (!isset($designTiers[$designTierKey])) {
            $designTierKey = 'easy';
        }
        $designBrief = trim($_POST['design_brief'] ?? '');

        // Validasi & proses file desain jika pilih Kirim File Sendiri
        $pendingUploads = [];
        $maxFileSize = 10 * 1024 * 1024; // 10 MB
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'cdr', 'ai', 'psd', 'eps', 'svg', 'zip', 'rar', '7z', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'];

        if ($designOption === 'upload' && !empty($_FILES['design_files'])) {
            $filesArr = $_FILES['design_files'];
            $fileCount = is_array($filesArr['name']) ? count($filesArr['name']) : 1;
            for ($i = 0; $i < $fileCount; $i++) {
                $fName = is_array($filesArr['name']) ? ($filesArr['name'][$i] ?? '') : ($filesArr['name'] ?? '');
                $fTmp  = is_array($filesArr['tmp_name']) ? ($filesArr['tmp_name'][$i] ?? '') : ($filesArr['tmp_name'] ?? '');
                $fErr  = is_array($filesArr['error']) ? (int)($filesArr['error'][$i] ?? UPLOAD_ERR_NO_FILE) : (int)($filesArr['error'] ?? UPLOAD_ERR_NO_FILE);
                $fSize = is_array($filesArr['size']) ? (int)($filesArr['size'][$i] ?? 0) : (int)($filesArr['size'] ?? 0);
                $fType = is_array($filesArr['type']) ? ($filesArr['type'][$i] ?? 'application/octet-stream') : ($filesArr['type'] ?? 'application/octet-stream');

                if ($fErr === UPLOAD_ERR_NO_FILE || trim((string)$fName) === '') {
                    continue;
                }
                if ($fErr === UPLOAD_ERR_INI_SIZE || $fErr === UPLOAD_ERR_FORM_SIZE || $fSize > $maxFileSize) {
                    $error = 'Ukuran file desain "' . htmlspecialchars((string)$fName) . '" melebihi batas maksimal 10 MB.';
                    break;
                }
                if ($fErr !== UPLOAD_ERR_OK) {
                    $error = 'Gagal mengunggah file desain "' . htmlspecialchars((string)$fName) . '". Silakan coba lagi.';
                    break;
                }
                $ext = strtolower(pathinfo((string)$fName, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExts, true)) {
                    $error = 'Format file "' . htmlspecialchars((string)$fName) . '" tidak didukung. Gunakan format gambar (JPG/PNG/WEBP), PDF, CDR, AI, PSD, ZIP/RAR, atau dokumen Office.';
                    break;
                }
                $pendingUploads[] = [
                    'original_name' => basename((string)$fName),
                    'tmp_name'      => $fTmp,
                    'size'          => $fSize,
                    'mime_type'     => $fType ?: 'application/octet-stream',
                    'ext'           => $ext,
                ];
            }
        }

        if ($error === '') {
            if ($name === '' || $phone === '') {
                $error = 'Nama lengkap dan nomor WhatsApp wajib diisi.';
            } else {
                $productSubtotal = 0;
                foreach ($items as $row) {
                    $productSubtotal += $row['subtotal'];
                }

                $designFee = 0.0;
                $selectedTierObj = null;
                if ($designOption === 'seller_design') {
                    $selectedTierObj = $designTiers[$designTierKey];
                    $designFee = (float)$selectedTierObj['price'];
                }

                $subtotal = $productSubtotal + $designFee;

                $isMember = customer_logged_in();
                $appliedRule = null;
                $discount = 0.0;

                if ($pdo) {
                    if ($voucherCode !== '') {
                        [$voucherOk, $voucherRule, $voucherMessage] = promo_apply_voucher(
                            $pdo,
                            $voucherCode,
                            $items,
                            $isMember,
                            $subtotal,
                            $loggedCustomer ? (int)$loggedCustomer['id'] : null
                        );
                        if ($voucherOk && $voucherRule) {
                            $appliedRule = $voucherRule;
                            $discount = (float)$appliedRule['discount_amount'];
                        }
                    } else {
                        $appliedRule = promo_get_best($pdo, $items, $isMember, $subtotal);
                        if ($appliedRule) {
                            $discount = (float)$appliedRule['discount_amount'];
                        }
                    }
                }

                if ($discount <= 0 && $voucherCode !== '') {
                    if ($voucherCode === 'ROSAHEMAT10' && $subtotal >= 100000) {
                        $discount = min(round($subtotal * 0.10), 50000);
                    } elseif ($voucherCode === 'MEMBERBARU' && $subtotal >= 75000) {
                        $discount = 15000;
                    }
                }

                $finalTotal = max(0.0, $subtotal - $discount);
                $orderCode = 'RIP-' . date('Ymd') . '-' . rand(1000, 9999);

                // Simpan file desain yang diunggah ke folder uploads/order-designs
                $savedAttachments = [];
                if ($designOption === 'upload' && !empty($pendingUploads)) {
                    $designDir = __DIR__ . '/uploads/order-designs';
                    if (!is_dir($designDir)) {
                        @mkdir($designDir, 0775, true);
                    }
                    foreach ($pendingUploads as $idx => $up) {
                        $safeCode = strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '', $orderCode));
                        $randStr = substr(bin2hex(random_bytes(4)), 0, 8);
                        $storedName = "desain-{$safeCode}-" . ($idx + 1) . "-{$randStr}." . $up['ext'];
                        $targetPath = $designDir . '/' . $storedName;
                        if (@move_uploaded_file($up['tmp_name'], $targetPath)) {
                            $savedAttachments[] = [
                                'original_name' => $up['original_name'],
                                'stored_name'   => $storedName,
                                'file_path'     => 'uploads/order-designs/' . $storedName,
                                'mime_type'     => $up['mime_type'],
                                'file_size'     => $up['size'],
                            ];
                        }
                    }
                }

                $combinedNote = '';
                if ($address !== '') {
                    $combinedNote .= "Alamat: {$address}\n";
                } else {
                    $combinedNote .= "Pengambilan: Ambil sendiri di Workshop Rosa Indah Printing\n";
                }
                if ($designOption === 'seller_design' && $selectedTierObj) {
                    $combinedNote .= "Pilihan Desain: Jasa Buatkan Desain Oleh Seller - {$selectedTierObj['name']} (" . format_rupiah($designFee) . ")\n";
                    if ($designBrief !== '') {
                        $combinedNote .= "Konsep Desain: {$designBrief}\n";
                    }
                } else {
                    if (!empty($savedAttachments)) {
                        $fileNames = implode(', ', array_column($savedAttachments, 'original_name'));
                        $combinedNote .= "Pilihan Desain: Kirim File Sendiri ({$fileNames})\n";
                    } else {
                        $combinedNote .= "Pilihan Desain: Kirim File Sendiri\n";
                    }
                }
                if ($customerNoteInput !== '') {
                    $combinedNote .= "Catatan: {$customerNoteInput}\n";
                }
                if ($voucherCode !== '' && $discount > 0) {
                    $combinedNote .= "Voucher: {$voucherCode} (-" . format_rupiah($discount) . ")";
                }
                $combinedNote = trim($combinedNote);

                if ($pdo) {
                    try {
                        try {
                            $cols = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
                            if (!in_array('payment_status', $cols, true)) {
                                $pdo->exec("ALTER TABLE orders ADD payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid' AFTER status");
                            }
                            if (!in_array('payment_method', $cols, true)) {
                                $pdo->exec("ALTER TABLE orders ADD payment_method VARCHAR(30) NULL AFTER payment_status");
                            }
                            $pdo->exec("CREATE TABLE IF NOT EXISTS order_attachments (
                                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                order_id BIGINT UNSIGNED NOT NULL,
                                original_name VARCHAR(255) NOT NULL,
                                stored_name VARCHAR(255) NOT NULL,
                                file_path VARCHAR(500) NOT NULL,
                                mime_type VARCHAR(100) NOT NULL,
                                file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
                                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                INDEX idx_order_attachments_order (order_id)
                            ) ENGINE=InnoDB");
                        } catch (Throwable $e) {}

                        $pdo->beginTransaction();
                        $stmt = $pdo->prepare('INSERT INTO orders (customer_id, order_code, customer_name, customer_phone, customer_note, subtotal_before_discount, discount_amount, voucher_code, promotion_id, total_estimate, status, payment_status, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                        $stmt->execute([
                            $loggedCustomer['id'] ?? null,
                            $orderCode,
                            $name,
                            $phone,
                            $combinedNote,
                            $subtotal,
                            $discount,
                            $voucherCode !== '' ? $voucherCode : null,
                            $appliedRule && ($appliedRule['source'] ?? '') === 'promotion' ? (int)$appliedRule['id'] : null,
                            $finalTotal,
                            'pending',
                            'unpaid',
                            $paymentMethod,
                        ]);
                        $orderId = (int)$pdo->lastInsertId();

                        $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, quantity, unit, variant_data, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                        foreach ($items as $row) {
                            $p = $row['product'];
                            $qty = (int)$row['quantity'];
                            $price = (float)$row['unitPrice'];
                            $variantInfo = [
                                'desain'  => $designOption === 'seller_design' && $selectedTierObj ? ('Jasa Desain ' . $selectedTierObj['name']) : 'File Desain Sendiri',
                                'catatan' => $customerNoteInput,
                            ];
                            $variantData = json_encode($variantInfo, JSON_UNESCAPED_UNICODE);
                            $itemStmt->execute([$orderId, $p['id'], $p['name'], $qty, $p['unit'], $variantData, $price, $price * $qty]);
                        }

                        if ($designOption === 'seller_design' && $selectedTierObj && $designFee > 0) {
                            $designVariant = json_encode([
                                'desain'  => $selectedTierObj['label'],
                                'catatan' => $designBrief !== '' ? $designBrief : 'Jasa pembuatan desain oleh tim Rosa Indah Printing',
                            ], JSON_UNESCAPED_UNICODE);
                            $itemStmt->execute([
                                $orderId,
                                'jasa-desain-' . $selectedTierObj['code'],
                                'Jasa Buatkan Desain Oleh Seller (' . $selectedTierObj['name'] . ')',
                                1,
                                'desain',
                                $designVariant,
                                $designFee,
                                $designFee,
                            ]);
                        }

                        if (!empty($savedAttachments)) {
                            $attStmt = $pdo->prepare('INSERT INTO order_attachments (order_id, original_name, stored_name, file_path, mime_type, file_size) VALUES (?, ?, ?, ?, ?, ?)');
                            foreach ($savedAttachments as $att) {
                                $attStmt->execute([
                                    $orderId,
                                    $att['original_name'],
                                    $att['stored_name'],
                                    $att['file_path'],
                                    $att['mime_type'],
                                    $att['file_size'],
                                ]);
                            }
                        }

                        $pdo->commit();
                        try {
                            order_record_status($pdo, $orderId, 'pending');
                        } catch (Throwable $e) {}
                    } catch (Throwable $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                    }
                }

                // Siapkan item list untuk sesi fallback
                $sessionItems = array_map(function ($it) {
                    return [
                        'product_id'   => $it['product']['id'],
                        'product_name' => $it['product']['name'],
                        'quantity'     => $it['quantity'],
                        'unit'         => $it['product']['unit'],
                        'unit_price'   => $it['unitPrice'],
                        'subtotal'     => $it['subtotal'],
                    ];
                }, $items);

                if ($designOption === 'seller_design' && $selectedTierObj && $designFee > 0) {
                    $sessionItems[] = [
                        'product_id'   => 'jasa-desain-' . $selectedTierObj['code'],
                        'product_name' => 'Jasa Buatkan Desain Oleh Seller (' . $selectedTierObj['name'] . ')',
                        'quantity'     => 1,
                        'unit'         => 'desain',
                        'unit_price'   => $designFee,
                        'subtotal'     => $designFee,
                    ];
                }

                // Simpan ke sesi sebagai fallback agar langsung dapat dilacak
                if (session_status() === PHP_SESSION_NONE) {
                    @session_start();
                }
                $_SESSION['session_orders'][$orderCode] = [
                    'id'               => time(),
                    'order_code'       => $orderCode,
                    'customer_name'    => $name,
                    'customer_phone'   => $phone,
                    'customer_address' => $address,
                    'customer_note'    => $combinedNote,
                    'design_option'    => $designOption,
                    'design_tier'      => $designOption === 'seller_design' ? $designTierKey : null,
                    'design_fee'       => $designFee,
                    'attachments'      => $savedAttachments,
                    'subtotal'         => $subtotal,
                    'discount_amount'  => $discount,
                    'voucher_code'     => $voucherCode,
                    'total_estimate'   => $finalTotal,
                    'status'           => 'pending',
                    'payment_status'   => 'unpaid',
                    'payment_method'   => $paymentMethod,
                    'paid_amount'      => 0,
                    'payment_proof'    => null,
                    'created_at'       => date('c'),
                    'items'            => $sessionItems,
                    'status_history'   => [
                        [
                            'status'     => 'pending',
                            'note'       => 'Pesanan berhasil dibuat oleh pelanggan.',
                            'created_at' => date('c'),
                        ],
                    ],
                ];

                if (!$buyProduct) {
                    cart_clear();
                }

                if (in_array($paymentMethod, ['transfer', 'qris'], true)) {
                    header('Location: ' . page_url('pembayaran.php?code=' . urlencode($orderCode) . '&phone=' . urlencode($phone) . '&new=1'));
                } else {
                    header('Location: ' . page_url('lacak-pesanan.php?code=' . urlencode($orderCode) . '&phone=' . urlencode($phone) . '&new=1'));
                }
                exit;
            }
        }
    }
}

$total = 0;
foreach ($items as $row) {
    $total += $row['subtotal'];
}

$pageTitle = 'Checkout Pesanan';
include __DIR__ . '/includes/header.php';
?>

<style>
/* Sinkronisasi Desain Checkout Pesanan */
.checkout-page {
    background: #fbf8fa !important;
    min-height: 75vh;
}
.checkout-page-title {
    font-size: 26px;
    font-weight: 800;
    color: #18243a;
    letter-spacing: -0.02em;
    margin-bottom: 6px;
}
.checkout-page-subtitle {
    font-size: 13.5px;
    color: #6e656d;
    margin-bottom: 0;
}
.checkout-card,
.checkout-summary-card {
    border: 1px solid #f2dce7 !important;
    border-radius: 18px !important;
}
.checkout-icon-badge {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    background: #fff0f6;
    color: #e11d64;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
}
.checkout-label {
    font-size: 13px;
    color: #374151;
    margin-bottom: 5px;
}
.checkout-input {
    border: 1px solid #ebdbe4;
    border-radius: 10px;
    padding: 9px 13px;
    font-size: 13.5px;
    color: #1f2937;
    transition: all 0.15s ease;
}
.checkout-input:focus {
    border-color: #e11d64;
    box-shadow: 0 0 0 3px rgba(225, 29, 100, 0.1);
}
.checkout-hint {
    font-size: 11px;
    color: #7a7079;
}
.payment-card-option {
    border: 1.5px solid #ebdbe4;
    border-radius: 13px;
    padding: 12px 14px;
    cursor: pointer;
    background: #ffffff;
    transition: all 0.18s ease;
    display: block;
    user-select: none;
}
.payment-card-option:hover {
    border-color: #f472b6;
    background: #fffbfe;
}
.payment-card-option.selected {
    border-color: #e11d64;
    background: #fff5f8;
    box-shadow: 0 2px 8px rgba(225, 29, 100, 0.08);
}
.payment-radio:checked {
    background-color: #e11d64;
    border-color: #e11d64;
}
.payment-title {
    font-size: 13px;
    color: #18243a;
}
.payment-chip {
    font-size: 10px;
    padding: 3px 8px;
    border-radius: 6px;
}
.payment-desc {
    font-size: 11px;
    line-height: 1.4;
}
.checkout-item-name {
    font-size: 13px;
}
.checkout-item-qty {
    font-size: 11.5px;
}
.checkout-item-subtotal {
    font-size: 13.5px;
}
.checkout-voucher-box {
    background: #fff8fb;
    border: 1px solid #f2dce7;
}
.checkout-voucher-input {
    font-size: 12.5px;
    font-weight: 700;
    letter-spacing: 0.05em;
}
.btn-apply-voucher {
    font-size: 11.5px;
    font-weight: 700;
}
.btn-voucher-chip {
    border: 1px dashed #e11d64;
    background: #fff0f6;
    color: #e11d64;
    border-radius: 6px;
    padding: 2px 8px;
    font-size: 10.5px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.15s ease;
}
.btn-voucher-chip:hover {
    background: #e11d64;
    color: #ffffff;
}
.checkout-total-val {
    font-size: 20px;
    color: #e11d64;
}
.btn-submit-checkout {
    background: linear-gradient(90deg, #e11d64 0%, #f43f7e 50%, #fb71a0 100%) !important;
    color: #ffffff !important;
    border: 0 !important;
    border-radius: 12px !important;
    font-size: 14.5px;
    box-shadow: 0 4px 15px rgba(225, 29, 100, 0.28) !important;
    transition: all 0.2s ease;
}
.btn-submit-checkout:hover {
    background: linear-gradient(90deg, #be124f 0%, #e11d64 50%, #f43f7e 100%) !important;
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(225, 29, 100, 0.35) !important;
    color: #ffffff !important;
}
.checkout-terms {
    font-size: 11px;
}
@media (max-width: 767.98px) {
    .checkout-page {
        padding-top: 14px !important;
        padding-bottom: 85px !important;
    }
    .checkout-page-title {
        font-size: 18px !important;
        line-height: 1.25 !important;
        margin-bottom: 4px !important;
    }
    .checkout-page-subtitle {
        font-size: 11px !important;
        line-height: 1.4 !important;
        margin-bottom: 0 !important;
    }
    .checkout-header {
        margin-bottom: 14px !important;
    }
    .checkout-card,
    .checkout-summary-card {
        padding: 14px !important;
        border-radius: 14px !important;
        border-color: #f2dce7 !important;
    }
    .checkout-card-title {
        font-size: 14px !important;
    }
    .checkout-total-val {
        font-size: 17px !important;
    }
    .btn-submit-checkout {
        font-size: 13px !important;
        padding: 10px 14px !important;
        border-radius: 10px !important;
    }
    .checkout-card-header {
        margin-bottom: 12px !important;
        padding-bottom: 8px !important;
    }
    .checkout-icon-badge {
        width: 28px !important;
        height: 28px !important;
        font-size: 13px !important;
    }
    .checkout-label {
        font-size: 11.5px !important;
        margin-bottom: 4px !important;
    }
    .checkout-input {
        font-size: 12.5px !important;
        padding: 8px 11px !important;
        border-radius: 9px !important;
    }
    .checkout-hint {
        font-size: 10px !important;
        line-height: 1.35 !important;
    }
    .payment-card-option {
        padding: 10px 11px !important;
        border-radius: 11px !important;
    }
    .payment-title {
        font-size: 12px !important;
    }
    .payment-chip {
        font-size: 9px !important;
        padding: 2px 6px !important;
    }
    .payment-desc {
        font-size: 10px !important;
    }
    .checkout-item-name {
        font-size: 12px !important;
    }
    .checkout-item-qty {
        font-size: 10px !important;
    }
    .checkout-item-subtotal {
        font-size: 12px !important;
    }
    .btn-voucher-chip {
        font-size: 9.5px !important;
        padding: 2px 6px !important;
    }
}
@media (max-width: 575.98px) {
    .checkout-page-title {
        font-size: 17.5px !important;
    }
    .checkout-summary-card {
        position: static !important;
        top: auto !important;
    }
}
</style>

<header class="main-header">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
</header>

<main class="checkout-page py-3 py-md-4">
    <div class="container">
        <!-- Breadcrumb -->
        <div class="catalog-breadcrumb mb-3 mb-md-4">
            <a href="<?php echo page_url('index.php'); ?>">Home</a>
            <i class="bi bi-chevron-right"></i>
            <a href="<?php echo page_url('cart.php'); ?>">Keranjang</a>
            <i class="bi bi-chevron-right"></i>
            <span>Checkout</span>
        </div>

        <div class="checkout-header mb-3 mb-md-4">
            <h1 class="checkout-page-title">Konfirmasi & Checkout Pesanan</h1>
            <p class="checkout-page-subtitle">Isi data pemesan dan alamat pengiriman dengan lengkap untuk memproses pesanan cetak Anda.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show checkout-alert mb-3 mb-md-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="<?php echo page_url('checkout.php' . ($buyProduct ? '?buy=' . urlencode($buyProduct['id']) . '&qty=' . $buyQty : '')); ?>" method="POST" enctype="multipart/form-data" id="checkoutForm">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(cart_csrf()); ?>">
            <?php if ($buyProduct): ?>
                <input type="hidden" name="buy" value="<?php echo htmlspecialchars($buyProduct['id']); ?>">
                <input type="hidden" name="qty" value="<?php echo (int)$buyQty; ?>">
            <?php endif; ?>
            <div class="row g-3 g-md-4">
                <!-- Customer Details, Design Options & Payment Options (Left Column) -->
                <div class="col-lg-7">
                    <!-- Data Pemesan Card -->
                    <div class="checkout-card bg-white p-3 p-md-4 rounded-4 shadow-sm mb-3 mb-md-4">
                        <div class="checkout-card-header d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                            <span class="checkout-icon-badge">
                                <i class="bi bi-person-lines-fill"></i>
                            </span>
                            <h2 class="checkout-card-title h5 fw-bold mb-0">Data Pemesan</h2>
                        </div>

                        <div class="mb-3">
                            <label for="customer_name" class="checkout-label form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control checkout-input" id="customer_name" name="customer_name" required placeholder="Contoh: Budi Santoso" value="<?php echo htmlspecialchars($loggedCustomer['name'] ?? ($_POST['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="customer_phone" class="checkout-label form-label fw-semibold">Nomor WhatsApp Aktif <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control checkout-input" id="customer_phone" name="customer_phone" required placeholder="Contoh: 082331035371" value="<?php echo htmlspecialchars($loggedCustomer['phone'] ?? ($_POST['customer_phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="form-text checkout-hint mt-1">
                                <i class="bi bi-info-circle me-1"></i> Status proses cetak & konfirmasi acc file desain akan dikirim ke WhatsApp ini.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="customer_address" class="checkout-label form-label fw-semibold">Alamat Lengkap Pengiriman</label>
                            <textarea class="form-control checkout-input" id="customer_address" name="customer_address" rows="3" placeholder="Nama Jalan, RT/RW, Dusun/Kelurahan, Kecamatan, Kota/Kabupaten"><?php echo htmlspecialchars($loggedCustomer['address'] ?? ($_POST['customer_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                            <div class="form-text checkout-hint mt-1">
                                Biarkan kosong bila Anda akan mengambil pesanan langsung di workshop Rosa Indah Printing.
                            </div>
                        </div>

                        <div class="mb-1">
                            <label for="customer_note" class="checkout-label form-label fw-semibold">Catatan Pesanan / Spesifikasi Tambahan</label>
                            <textarea class="form-control checkout-input" id="customer_note" name="customer_note" rows="2" placeholder="Contoh: Finishing mata ayam 4 sudut, ukuran pasti, atau catatan pengiriman."><?php echo htmlspecialchars($_POST['customer_note'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                    </div>

                    <!-- Pilihan File / Desain Cetak Card -->
                    <?php
                    $selectedDesignOption = $_POST['design_option'] ?? 'upload';
                    if (!in_array($selectedDesignOption, ['upload', 'seller_design'], true)) {
                        $selectedDesignOption = 'upload';
                    }
                    $selectedDesignTier = $_POST['design_tier'] ?? 'easy';
                    if (!isset($designTiers[$selectedDesignTier])) {
                        $selectedDesignTier = 'easy';
                    }
                    ?>
                    <div class="checkout-card bg-white p-3 p-md-4 rounded-4 shadow-sm mb-3 mb-md-4">
                        <div class="checkout-card-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-2 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <span class="checkout-icon-badge">
                                    <i class="bi bi-palette2"></i>
                                </span>
                                <h2 class="checkout-card-title h5 fw-bold mb-0">Pilihan File / Desain Cetak</h2>
                            </div>
                            <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" style="font-size: 11px;">Wajib Pilih Salah Satu</span>
                        </div>

                        <div class="d-flex flex-column gap-3">
                            <!-- OPSI 1: Kirim File Sendiri -->
                            <label class="payment-card-option design-card-option <?php echo $selectedDesignOption === 'upload' ? 'selected' : ''; ?>" for="designOptUpload">
                                <div class="d-flex align-items-start gap-3">
                                    <input class="form-check-input payment-radio design-option-radio mt-1" type="radio" name="design_option" id="designOptUpload" value="upload" <?php echo $selectedDesignOption === 'upload' ? 'checked' : ''; ?>>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                                            <span class="payment-title fw-bold"><i class="bi bi-cloud-arrow-up-fill text-danger me-1"></i> Kirim File Sendiri (Sudah Punya Desain)</span>
                                            <span class="badge bg-success-subtle text-success border payment-chip">Gratis • Rp0</span>
                                        </div>
                                        <p class="payment-desc text-muted mb-0 mt-1">
                                            Gunakan fitur ini jika Anda sudah mempunyai file desain siap cetak sendiri (Maksimal ukuran file <strong>10 MB</strong>).
                                        </p>
                                    </div>
                                </div>

                                <!-- Box Upload File Desain (Muncul saat Opsi 1 dipilih) -->
                                <div id="designUploadPanel" class="mt-3 pt-3 border-top <?php echo $selectedDesignOption === 'upload' ? '' : 'd-none'; ?>" onclick="event.stopPropagation();">
                                    <div class="p-3 rounded-3" style="background: #fff9fc; border: 1.5px dashed #e11d64;">
                                        <label for="designFilesInput" class="form-label fw-bold small text-dark mb-1 d-flex align-items-center gap-1">
                                            <i class="bi bi-file-earmark-arrow-up text-danger fs-6"></i> Unggah File Desain Siap Cetak (Maks. 10 MB)
                                        </label>
                                        <input class="form-control form-control-sm checkout-input bg-white" type="file" id="designFilesInput" name="design_files[]" multiple accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.cdr,.ai,.psd,.eps,.svg,.zip,.rar,.7z,.doc,.docx,.ppt,.pptx,.xls,.xlsx">
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-1 mt-2">
                                            <small class="text-muted" style="font-size: 11px;">
                                                <i class="bi bi-check2-circle text-success me-1"></i>Format: <strong>JPG, PNG, PDF, CDR, AI, PSD, RAR/ZIP, DOCX</strong> (Maks. <strong>10 MB</strong>/file)
                                            </small>
                                        </div>
                                        <div id="designFileAlert" class="alert alert-danger py-2 px-3 mt-2 mb-0 small d-none"></div>
                                        <div id="designFilePreviewList" class="mt-2 d-flex flex-column gap-1 d-none"></div>
                                    </div>
                                </div>
                            </label>

                            <!-- OPSI 2: Jasa Buatkan Desain Oleh Seller -->
                            <label class="payment-card-option design-card-option <?php echo $selectedDesignOption === 'seller_design' ? 'selected' : ''; ?>" for="designOptSeller">
                                <div class="d-flex align-items-start gap-3">
                                    <input class="form-check-input payment-radio design-option-radio mt-1" type="radio" name="design_option" id="designOptSeller" value="seller_design" <?php echo $selectedDesignOption === 'seller_design' ? 'checked' : ''; ?>>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                                            <span class="payment-title fw-bold"><i class="bi bi-brush-fill text-primary me-1"></i> Jasa Buatkan Desain Oleh Seller (Belum Punya Desain)</span>
                                            <span class="badge bg-primary-subtle text-primary border payment-chip">Easy 5k • Medium 7k • Hard 10k</span>
                                        </div>
                                        <p class="payment-desc text-muted mb-0 mt-1">
                                            Belum punya desain? Tim desainer Rosa Indah Printing siap membuatkan desain sesuai tingkat kerumitan yang Anda pilih.
                                        </p>
                                    </div>
                                </div>

                                <!-- Box Pilihan Paket Jasa Desain Easy / Medium / Hard (Muncul saat Opsi 2 dipilih) -->
                                <div id="sellerDesignPanel" class="mt-3 pt-3 border-top <?php echo $selectedDesignOption === 'seller_design' ? '' : 'd-none'; ?>" onclick="event.stopPropagation();">
                                    <div class="mb-2 fw-bold small text-dark">
                                        Pilih Tingkat Kerumitan Jasa Desain:
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <?php foreach ($designTiers as $tierKey => $tier): ?>
                                            <div class="col-md-4">
                                                <label class="tier-option-box d-block p-2 px-3 rounded-3 border h-100 <?php echo $selectedDesignTier === $tierKey ? 'active' : ''; ?>" for="tier_<?php echo $tierKey; ?>" style="cursor: pointer; transition: all 0.15s ease; background: <?php echo $selectedDesignTier === $tierKey ? '#fff0f6' : '#ffffff'; ?>; border-color: <?php echo $selectedDesignTier === $tierKey ? '#e11d64' : '#e5d4df'; ?> !important;">
                                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                                        <div class="form-check mb-0">
                                                            <input class="form-check-input design-tier-radio" type="radio" name="design_tier" id="tier_<?php echo $tierKey; ?>" value="<?php echo $tierKey; ?>" data-price="<?php echo (int)$tier['price']; ?>" data-name="<?php echo htmlspecialchars($tier['name']); ?>" <?php echo $selectedDesignTier === $tierKey ? 'checked' : ''; ?>>
                                                            <span class="fw-bold small text-dark"><?php echo htmlspecialchars($tier['name']); ?></span>
                                                        </div>
                                                        <span class="badge bg-danger text-white" style="font-size: 11px;">+<?php echo format_rupiah($tier['price']); ?></span>
                                                    </div>
                                                    <div class="text-muted" style="font-size: 10.5px; line-height: 1.35;">
                                                        <?php echo htmlspecialchars($tier['desc']); ?>
                                                    </div>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <div>
                                        <label for="design_brief" class="form-label small fw-semibold text-dark mb-1">
                                            Arahan / Konsep Desain yang Diinginkan <span class="text-muted fw-normal">(Opsional)</span>
                                        </label>
                                        <textarea class="form-control form-control-sm checkout-input bg-white" id="design_brief" name="design_brief" rows="2" placeholder="Contoh: Judul tulisan, pilihan warna dominan, tema acara, atau keterangan konsep desain..."><?php echo htmlspecialchars($_POST['design_brief'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Metode Pembayaran Card -->
                    <?php
                    // Daftar Rekening Bank Resmi Rosa Indah Printing
                    // Catatan: Untuk menampilkan Bank BRI atau BCA ke depannya, cukup ubah 'active' => true dan isi 'account_number'.
                    $bankAccounts = [
                        [
                            'bank_code'      => 'MANDIRI',
                            'bank_name'      => 'Bank Mandiri',
                            'account_number' => '1440020363831',
                            'account_holder' => 'Muhammad Alan Roziqi',
                            'active'         => true,
                        ],
                        [
                            'bank_code'      => 'BRI',
                            'bank_name'      => 'Bank BRI',
                            'account_number' => '', // Isi nomor rekening BRI saat sudah tersedia
                            'account_holder' => 'Muhammad Alan Roziqi',
                            'active'         => false,
                        ],
                        [
                            'bank_code'      => 'BCA',
                            'bank_name'      => 'Bank BCA',
                            'account_number' => '', // Isi nomor rekening BCA saat sudah tersedia
                            'account_holder' => 'Muhammad Alan Roziqi',
                            'active'         => false,
                        ],
                    ];
                    $activeBanks = array_values(array_filter($bankAccounts, function ($b) {
                        return !empty($b['active']) && !empty($b['account_number']);
                    }));
                    $selectedPayment = $_POST['payment_method'] ?? 'transfer';
                    ?>
                    <div class="checkout-card bg-white p-3 p-md-4 rounded-4 shadow-sm">
                        <div class="checkout-card-header d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                            <span class="checkout-icon-badge">
                                <i class="bi bi-wallet2"></i>
                            </span>
                            <h2 class="checkout-card-title h5 fw-bold mb-0">Metode Pembayaran</h2>
                        </div>

                        <div class="payment-options-grid d-flex flex-column gap-2">
                            <label class="payment-card-option payment-method-card <?php echo $selectedPayment === 'transfer' ? 'selected' : ''; ?>" for="payTransfer">
                                <div class="d-flex align-items-start gap-3">
                                    <input class="form-check-input payment-radio method-radio mt-1" type="radio" name="payment_method" id="payTransfer" value="transfer" <?php echo $selectedPayment === 'transfer' ? 'checked' : ''; ?>>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                                            <span class="payment-title fw-bold">Transfer Bank (<?php echo implode(' / ', array_column($activeBanks, 'bank_name')); ?>)</span>
                                            <span class="badge bg-primary-subtle text-primary border payment-chip">Transfer Bank</span>
                                        </div>
                                        <p class="payment-desc text-muted mb-2 mt-1">Lakukan transfer ke rekening resmi kami dan unggah bukti transfer di halaman pembayaran setelah klik Buat Pesanan.</p>
                                        <div class="p-2 px-3 rounded-3 bg-white border d-flex flex-column gap-1 mt-1">
                                            <?php foreach ($activeBanks as $bank): ?>
                                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 small">
                                                    <div>
                                                        <span class="badge bg-warning text-dark fw-bold me-1"><?php echo htmlspecialchars($bank['bank_code']); ?></span>
                                                        <strong class="text-dark"><?php echo htmlspecialchars($bank['bank_name']); ?></strong>:
                                                        <code class="fw-bold text-primary fs-6 ms-1"><?php echo htmlspecialchars($bank['account_number']); ?></code>
                                                    </div>
                                                    <span class="text-secondary fw-semibold">a/n <?php echo htmlspecialchars($bank['account_holder']); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </label>

                            <label class="payment-card-option payment-method-card <?php echo $selectedPayment === 'qris' ? 'selected' : ''; ?>" for="payQris">
                                <div class="d-flex align-items-start gap-3">
                                    <input class="form-check-input payment-radio method-radio mt-1" type="radio" name="payment_method" id="payQris" value="qris" <?php echo $selectedPayment === 'qris' ? 'checked' : ''; ?>>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                                            <span class="payment-title fw-bold">QRIS Resmi Rosa Indah Printing (Semua E-Wallet & M-Banking)</span>
                                            <span class="badge bg-success-subtle text-success border payment-chip">Instan QRIS</span>
                                        </div>
                                        <p class="payment-desc text-muted mb-2 mt-1">Scan barcode QRIS resmi (GoPay, DANA, OVO, ShopeePay, LinkAja, atau Mobile Banking) dan upload bukti bayar di langkah berikutnya.</p>
                                        <div class="p-2 px-3 rounded-3 bg-white border d-flex align-items-center justify-content-between flex-wrap gap-2 small">
                                            <span><i class="bi bi-qr-code-scan text-danger me-1"></i> <strong>ROSA INDAH PRINTING</strong></span>
                                            <span class="text-muted">NMID: <code>ID1026627457595</code></span>
                                        </div>
                                    </div>
                                </div>
                            </label>

                            <label class="payment-card-option payment-method-card <?php echo $selectedPayment === 'cash' ? 'selected' : ''; ?>" for="payCash">
                                <div class="d-flex align-items-start gap-3">
                                    <input class="form-check-input payment-radio method-radio mt-1" type="radio" name="payment_method" id="payCash" value="cash" <?php echo $selectedPayment === 'cash' ? 'checked' : ''; ?>>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                                            <span class="payment-title fw-bold">Bayar di Workshop (Cash / Tunai)</span>
                                            <span class="badge bg-light text-secondary border payment-chip">Ambil Sendiri</span>
                                        </div>
                                        <p class="payment-desc text-muted mb-0 mt-1">Bayar langsung secara tunai kepada kasir saat mengambil hasil cetak di workshop Rosa Indah Printing.</p>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Order Review & Summary (Right Column) -->
                <div class="col-lg-5">
                    <div class="checkout-summary-card bg-white p-3 p-md-4 rounded-4 shadow-sm position-sticky" style="top: 85px;">
                        <div class="checkout-card-header d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                            <span class="checkout-icon-badge">
                                <i class="bi bi-receipt"></i>
                            </span>
                            <h2 class="checkout-card-title h5 fw-bold mb-0">Rincian Pesanan</h2>
                        </div>

                        <!-- Order Items List -->
                        <div class="checkout-items-list mb-3">
                            <?php foreach ($items as $item): ?>
                                <div class="checkout-item-row d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <div class="checkout-item-main pe-2">
                                        <span class="checkout-item-name fw-semibold text-dark d-block"><?php echo htmlspecialchars($item['product']['name']); ?></span>
                                        <span class="checkout-item-qty text-muted small">
                                            <?php echo (int)$item['quantity']; ?> <?php echo htmlspecialchars($item['product']['unit']); ?> × <?php echo format_rupiah($item['unitPrice']); ?>
                                        </span>
                                    </div>
                                    <div class="checkout-item-subtotal fw-bold text-dark tabular-nums text-nowrap">
                                        <?php echo format_rupiah($item['subtotal']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Voucher Code Form Field -->
                        <div class="checkout-voucher-box p-3 rounded-3 mb-3">
                            <label for="voucher_code" class="form-label checkout-label fw-bold small text-secondary mb-1">
                                <i class="bi bi-tag-fill text-primary me-1"></i> Kode Promo / Voucher Member:
                            </label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control text-uppercase checkout-voucher-input" id="voucher_code" name="voucher_code" placeholder="Misal: ROSAHEMAT10" value="<?php echo htmlspecialchars($_POST['voucher_code'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="button" class="btn btn-outline-primary btn-apply-voucher" id="btnApplyVoucher">Terapkan</button>
                            </div>

                            <!-- Quick Voucher Suggestion Chips -->
                            <div class="voucher-chips d-flex align-items-center gap-2 mt-2 flex-wrap">
                                <span class="small text-muted">Promo:</span>
                                <button type="button" class="btn-voucher-chip" data-code="ROSAHEMAT10">ROSAHEMAT10</button>
                                <button type="button" class="btn-voucher-chip" data-code="MEMBERBARU">MEMBERBARU</button>
                            </div>
                        </div>

                        <!-- Summary Breakdown -->
                        <div class="checkout-breakdown mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Subtotal Produk</span>
                                <span class="fw-semibold tabular-nums small" id="summaryProductSubtotal" data-base="<?php echo (float)$total; ?>"><?php echo format_rupiah($total); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small" id="summaryDesignLabel">File / Jasa Desain</span>
                                <span class="fw-semibold tabular-nums small text-success" id="summaryDesignValue">Kirim File Sendiri (Rp0)</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Biaya Pengiriman</span>
                                <span class="text-success fw-semibold small">Dikonfirmasi via WA</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1" id="voucherDiscountRow" style="display: none !important;">
                                <span class="text-success small">Potongan Voucher</span>
                                <span class="text-success fw-bold tabular-nums small" id="voucherDiscountVal">-</span>
                            </div>
                        </div>

                        <hr class="my-3">

                        <div class="checkout-total-row d-flex justify-content-between align-items-baseline mb-4">
                            <span class="fw-bold">Total Pembayaran</span>
                            <span class="checkout-total-val fw-bold tabular-nums" id="summaryGrandTotal"><?php echo format_rupiah($total); ?></span>
                        </div>

                        <button type="submit" class="btn btn-primary btn-submit-checkout w-100 py-2 py-md-3 fw-bold">
                            <i class="bi bi-check2-circle me-1"></i> Buat Pesanan Sekarang
                        </button>

                        <div class="text-center mt-3">
                            <small class="text-muted checkout-terms">
                                <i class="bi bi-lock-fill me-1"></i> Data Anda terlindungi. Invoice resmi akan langsung diterbitkan setelah order dibuat.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const baseProductTotal = <?php echo (float)$total; ?>;
        const maxFileBytes = 10 * 1024 * 1024; // 10 MB

        function formatRupiahJs(num) {
            return 'Rp' + Math.round(num).toLocaleString('id-ID');
        }

        // 1. Highlight active payment method card
        const methodCards = document.querySelectorAll('.payment-method-card');
        const methodRadios = document.querySelectorAll('.method-radio');

        methodRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                methodCards.forEach(card => card.classList.remove('selected'));
                if (this.checked) {
                    const parentCard = this.closest('.payment-method-card');
                    if (parentCard) parentCard.classList.add('selected');
                }
            });
        });

        // 2. Design Option & Tier Calculation
        const designCards = document.querySelectorAll('.design-card-option');
        const designOptionRadios = document.querySelectorAll('.design-option-radio');
        const designTierRadios = document.querySelectorAll('.design-tier-radio');
        const designUploadPanel = document.getElementById('designUploadPanel');
        const sellerDesignPanel = document.getElementById('sellerDesignPanel');
        const summaryDesignLabel = document.getElementById('summaryDesignLabel');
        const summaryDesignValue = document.getElementById('summaryDesignValue');
        const summaryGrandTotal = document.getElementById('summaryGrandTotal');

        function recalculateCheckoutTotal() {
            const selectedOpt = document.querySelector('.design-option-radio:checked');
            const optVal = selectedOpt ? selectedOpt.value : 'upload';
            let designFee = 0;

            if (optVal === 'seller_design') {
                if (designUploadPanel) designUploadPanel.classList.add('d-none');
                if (sellerDesignPanel) sellerDesignPanel.classList.remove('d-none');

                const selectedTier = document.querySelector('.design-tier-radio:checked');
                const tierPrice = selectedTier ? parseInt(selectedTier.getAttribute('data-price') || '5000', 10) : 5000;
                const tierName = selectedTier ? (selectedTier.getAttribute('data-name') || 'Easy') : 'Easy';
                designFee = tierPrice;

                if (summaryDesignLabel) summaryDesignLabel.textContent = 'Jasa Desain (' + tierName + ')';
                if (summaryDesignValue) {
                    summaryDesignValue.textContent = '+' + formatRupiahJs(designFee);
                    summaryDesignValue.classList.remove('text-success');
                    summaryDesignValue.classList.add('text-danger');
                }
            } else {
                if (designUploadPanel) designUploadPanel.classList.remove('d-none');
                if (sellerDesignPanel) sellerDesignPanel.classList.add('d-none');

                if (summaryDesignLabel) summaryDesignLabel.textContent = 'File / Jasa Desain';
                if (summaryDesignValue) {
                    summaryDesignValue.textContent = 'Kirim File Sendiri (Rp0)';
                    summaryDesignValue.classList.remove('text-danger');
                    summaryDesignValue.classList.add('text-success');
                }
            }

            // Update tier card borders
            document.querySelectorAll('.tier-option-box').forEach(box => {
                const rad = box.querySelector('.design-tier-radio');
                if (rad && rad.checked) {
                    box.style.background = '#fff0f6';
                    box.style.borderColor = '#e11d64';
                } else {
                    box.style.background = '#ffffff';
                    box.style.borderColor = '#e5d4df';
                }
            });

            const grandTotal = Math.max(0, baseProductTotal + designFee);
            if (summaryGrandTotal) {
                summaryGrandTotal.textContent = formatRupiahJs(grandTotal);
            }
        }

        designOptionRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                designCards.forEach(card => card.classList.remove('selected'));
                if (this.checked) {
                    const parentCard = this.closest('.design-card-option');
                    if (parentCard) parentCard.classList.add('selected');
                }
                recalculateCheckoutTotal();
            });
        });

        designTierRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                const sellerRadio = document.getElementById('designOptSeller');
                if (sellerRadio && !sellerRadio.checked) {
                    sellerRadio.checked = true;
                    designCards.forEach(card => card.classList.remove('selected'));
                    const parentCard = sellerRadio.closest('.design-card-option');
                    if (parentCard) parentCard.classList.add('selected');
                }
                recalculateCheckoutTotal();
            });
        });

        // 3. Validate File Upload max 10 MB & show file list preview
        const designFilesInput = document.getElementById('designFilesInput');
        const designFileAlert = document.getElementById('designFileAlert');
        const designFilePreviewList = document.getElementById('designFilePreviewList');

        if (designFilesInput) {
            designFilesInput.addEventListener('change', function() {
                if (designFileAlert) {
                    designFileAlert.classList.add('d-none');
                    designFileAlert.textContent = '';
                }
                if (designFilePreviewList) {
                    designFilePreviewList.innerHTML = '';
                    designFilePreviewList.classList.add('d-none');
                }

                const files = Array.from(this.files || []);
                if (files.length === 0) return;

                let hasOversize = false;
                let oversizeName = '';
                files.forEach(f => {
                    if (f.size > maxFileBytes) {
                        hasOversize = true;
                        oversizeName = f.name;
                    }
                });

                if (hasOversize) {
                    if (designFileAlert) {
                        designFileAlert.innerHTML = '<i class="bi bi-exclamation-octagon-fill me-1"></i> File <strong>' + oversizeName + '</strong> melebihi batas maksimal <strong>10 MB</strong>. Silakan pilih file dengan ukuran maksimal 10 MB.';
                        designFileAlert.classList.remove('d-none');
                    }
                    this.value = '';
                    return;
                }

                if (designFilePreviewList) {
                    files.forEach(f => {
                        const sizeMb = (f.size / (1024 * 1024)).toFixed(2);
                        const itemEl = document.createElement('div');
                        itemEl.className = 'd-flex align-items-center justify-content-between bg-white border rounded-2 px-2 py-1 small';
                        itemEl.innerHTML = '<span class="text-truncate me-2"><i class="bi bi-file-earmark-check-fill text-success me-1"></i><strong>' + f.name + '</strong></span><span class="badge bg-light text-secondary border">' + sizeMb + ' MB</span>';
                        designFilePreviewList.appendChild(itemEl);
                    });
                    designFilePreviewList.classList.remove('d-none');
                }
            });
        }

        // Quick Voucher Chips click handler
        const voucherInput = document.getElementById('voucher_code');
        document.querySelectorAll('.btn-voucher-chip').forEach(chip => {
            chip.addEventListener('click', function() {
                const code = this.getAttribute('data-code');
                if (voucherInput && code) {
                    voucherInput.value = code;
                    voucherInput.focus();
                }
            });
        });

        recalculateCheckoutTotal();
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>