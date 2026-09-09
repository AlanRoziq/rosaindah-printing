<?php
/**
 * Session cart helper — Rosa Indah Printing
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function cart_init(): void {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

function cart_items(): array {
    cart_init();
    return $_SESSION['cart'];
}

function cart_count(): int {
    cart_init();
    $count = 0;
    foreach ($_SESSION['cart'] as $item) $count += max(1, (int)($item['quantity'] ?? 1));
    return $count;
}

function cart_add(string $productId, int $quantity = 1): void {
    cart_init();
    $quantity = max(1, min(9999, $quantity));
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]['quantity'] = min(9999, (int)$_SESSION['cart'][$productId]['quantity'] + $quantity);
    } else {
        $_SESSION['cart'][$productId] = ['quantity' => $quantity];
    }
}

function cart_set(string $productId, int $quantity): void {
    cart_init();
    if (!isset($_SESSION['cart'][$productId])) return;
    if ($quantity <= 0) unset($_SESSION['cart'][$productId]);
    else $_SESSION['cart'][$productId]['quantity'] = min(9999, $quantity);
}

function cart_remove(string $productId): void {
    cart_init();
    unset($_SESSION['cart'][$productId]);
}

function cart_clear(): void {
    $_SESSION['cart'] = [];
}

function cart_csrf(): string {
    if (empty($_SESSION['cart_csrf'])) $_SESSION['cart_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['cart_csrf'];
}

function cart_verify_csrf(string $token): bool {
    return !empty($_SESSION['cart_csrf']) && hash_equals($_SESSION['cart_csrf'], $token);
}
