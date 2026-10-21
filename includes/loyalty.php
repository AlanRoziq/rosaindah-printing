<?php
/** Tahap 23 — Member Points / Loyalty & Reward */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/promotions.php';
require_once __DIR__ . '/notifications.php';

function loyalty_ensure_tables(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS loyalty_settings (
        id TINYINT UNSIGNED PRIMARY KEY,
        points_per_amount DECIMAL(14,2) NOT NULL DEFAULT 10000,
        points_per_order_min DECIMAL(14,2) NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    $pdo->exec("INSERT IGNORE INTO loyalty_settings (id, points_per_amount, points_per_order_min) VALUES (1,10000,0)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS loyalty_transactions (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        customer_id BIGINT UNSIGNED NOT NULL,
        order_id BIGINT UNSIGNED NULL,
        type ENUM('earn','redeem','adjust') NOT NULL,
        points INT NOT NULL,
        balance_after INT NOT NULL DEFAULT 0,
        description VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_loyalty_order_earn (order_id, type),
        INDEX idx_loyalty_customer (customer_id, created_at),
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
    ) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS loyalty_rewards (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(160) NOT NULL,
        points_cost INT UNSIGNED NOT NULL,
        discount_type ENUM('percent','fixed') NOT NULL DEFAULT 'fixed',
        discount_value DECIMAL(14,2) NOT NULL DEFAULT 0,
        min_order DECIMAL(14,2) NOT NULL DEFAULT 0,
        max_discount DECIMAL(14,2) NULL,
        validity_days INT UNSIGNED NOT NULL DEFAULT 30,
        quota INT UNSIGNED NULL,
        redeemed_count INT UNSIGNED NOT NULL DEFAULT 0,
        status ENUM('active','inactive') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS loyalty_redemptions (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        reward_id BIGINT UNSIGNED NOT NULL,
        customer_id BIGINT UNSIGNED NOT NULL,
        points_spent INT UNSIGNED NOT NULL,
        voucher_id BIGINT UNSIGNED NULL,
        voucher_code VARCHAR(60) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_loyalty_red_customer (customer_id, created_at),
        FOREIGN KEY (reward_id) REFERENCES loyalty_rewards(id) ON DELETE CASCADE,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    try {
        $cols = $pdo->query('SHOW COLUMNS FROM vouchers')->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('customer_id', $cols, true)) {
            $pdo->exec("ALTER TABLE vouchers ADD customer_id BIGINT UNSIGNED NULL AFTER member_only");
            try { $pdo->exec("ALTER TABLE vouchers ADD INDEX idx_vouchers_customer (customer_id)"); } catch(Throwable $e) {}
        }
    } catch(Throwable $e) {}
}

function loyalty_settings(PDO $pdo): array {
    loyalty_ensure_tables($pdo);
    $r = $pdo->query('SELECT * FROM loyalty_settings WHERE id=1')->fetch();
    return $r ?: ['points_per_amount'=>10000,'points_per_order_min'=>0];
}
function loyalty_balance(PDO $pdo, int $customerId): int {
    loyalty_ensure_tables($pdo);
    $s=$pdo->prepare('SELECT COALESCE(SUM(points),0) FROM loyalty_transactions WHERE customer_id=?'); $s->execute([$customerId]); return max(0,(int)$s->fetchColumn());
}
function loyalty_earn_for_completed_order(PDO $pdo, int $orderId): array {
    loyalty_ensure_tables($pdo);
    $pdo->beginTransaction();
    try {
        $s=$pdo->prepare('SELECT id,customer_id,total_estimate,status FROM orders WHERE id=? FOR UPDATE'); $s->execute([$orderId]); $o=$s->fetch();
        if(!$o || empty($o['customer_id']) || $o['status']!=='completed'){ $pdo->commit(); return [false,0,'Pesanan bukan pesanan Member yang selesai.']; }
        $check=$pdo->prepare("SELECT id FROM loyalty_transactions WHERE order_id=? AND type='earn' LIMIT 1"); $check->execute([$orderId]);
        if($check->fetchColumn()){ $pdo->commit(); return [true,0,'Poin pesanan sudah pernah diberikan.']; }
        $set=loyalty_settings($pdo); $base=max(0,(float)$o['total_estimate']); $per=max(1,(float)$set['points_per_amount']);
        if($base < (float)$set['points_per_order_min']){ $pdo->commit(); return [true,0,'Belanja belum mencapai minimum perolehan poin.']; }
        $points=(int)floor($base/$per); if($points<=0){$pdo->commit();return [true,0,'Pesanan belum menghasilkan poin.'];}
        $bal=loyalty_balance($pdo,(int)$o['customer_id']); $new=$bal+$points;
        $i=$pdo->prepare("INSERT INTO loyalty_transactions(customer_id,order_id,type,points,balance_after,description) VALUES(?,?,?,?,?,?)");
        $i->execute([(int)$o['customer_id'],$orderId,'earn',$points,$new,'Poin dari pesanan '.$o['id']]);
        try {
            member_notification_add($pdo, (int)$o['customer_id'], 'points', 'Poin berhasil bertambah ⭐', 'Pesanan #'.$o['id'].' sudah selesai. Anda mendapatkan '.$points.' poin loyalty.', page_url('poin-member.php'));
        } catch (Throwable $e) {}
        $pdo->commit(); return [true,$points,'Poin berhasil ditambahkan.'];
    } catch(Throwable $e){ if($pdo->inTransaction())$pdo->rollBack(); return [false,0,'Poin belum dapat diproses.']; }
}
function loyalty_issue_reward(PDO $pdo, int $customerId, int $rewardId): array {
    loyalty_ensure_tables($pdo); ensure_promotions_tables($pdo);
    if($customerId<=0)return [false,'Akun Member tidak valid.',null];
    $pdo->beginTransaction();
    try {
        $s=$pdo->prepare('SELECT * FROM loyalty_rewards WHERE id=? FOR UPDATE');$s->execute([$rewardId]);$r=$s->fetch();
        if(!$r || $r['status']!=='active')throw new RuntimeException('Reward tidak tersedia.');
        if($r['quota']!==null && (int)$r['redeemed_count'] >= (int)$r['quota'])throw new RuntimeException('Kuota reward sudah habis.');
        $bal=loyalty_balance($pdo,$customerId);$cost=(int)$r['points_cost'];if($bal<$cost)throw new RuntimeException('Poin Anda belum mencukupi.');
        $code='RWD'.strtoupper(substr(bin2hex(random_bytes(5)),0,10));
        $ends=date('Y-m-d H:i:s',time()+max(1,(int)$r['validity_days'])*86400);
        $i=$pdo->prepare("INSERT INTO vouchers(code,name,description,discount_type,discount_value,min_order,max_discount,scope_type,scope_value,member_only,customer_id,quota,usage_count,starts_at,ends_at,status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $i->execute([$code,'Reward Member: '.$r['name'],'Voucher hasil penukaran poin Member.',''.$r['discount_type'],(float)$r['discount_value'],(float)$r['min_order'],$r['max_discount']!==null?(float)$r['max_discount']:null,'all',null,1,$customerId,1,0,date('Y-m-d H:i:s'),$ends,'active']);
        $voucherId=(int)$pdo->lastInsertId();
        $new=$bal-$cost;
        $t=$pdo->prepare("INSERT INTO loyalty_transactions(customer_id,type,points,balance_after,description) VALUES(?,?,?,?,?)");$t->execute([$customerId,'redeem',-$cost,$new,'Tukar reward: '.$r['name']]);
        $u=$pdo->prepare('UPDATE loyalty_rewards SET redeemed_count=redeemed_count+1 WHERE id=?');$u->execute([$rewardId]);
        $rd=$pdo->prepare('INSERT INTO loyalty_redemptions(reward_id,customer_id,points_spent,voucher_id,voucher_code) VALUES(?,?,?,?,?)');$rd->execute([$rewardId,$customerId,$cost,$voucherId,$code]);
        try { member_notification_add($pdo, $customerId, 'reward', 'Reward berhasil ditukar 🎁', 'Reward "'.$r['name'].'" berhasil ditukar. Kode voucher Anda: '.$code, page_url('poin-member.php')); } catch (Throwable $e) {}
        $pdo->commit(); return [true,'Reward berhasil ditukar. Kode voucher Anda: '.$code,$code];
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();return [false,$e->getMessage(),null];}
}
function loyalty_format_points(int $n): string{return number_format($n,0,',','.').' poin';}
