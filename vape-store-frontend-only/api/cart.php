<?php
// api/cart.php
session_start(); // Pastikan sesi dimulai untuk mengelola keranjang di sesi PHP

require_once __DIR__ . '/../../includes/db_config.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Sesuaikan di produksi
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Pastikan keranjang di sesi sudah ada
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari JSON request body
    $input = json_decode(file_get_contents('php://input'), true);

    $action = sanitize_input($input['action'] ?? '');
    $product_id = sanitize_input($input['product_id'] ?? '');
    $quantity = sanitize_input($input['quantity'] ?? 1);

    if (!is_numeric($product_id) || $product_id <= 0) {
        $response['message'] = 'Invalid product ID.';
        echo json_encode($response);
        exit();
    }

    $product_id = (int) $product_id;
    $quantity = (int) $quantity;

    try {
        switch ($action) {
            case 'add':
                $stmt = $conn->prepare("SELECT id, name, price, stock FROM products WHERE id = ?");
                $stmt->bindParam(1, $product_id, PDO::PARAM_INT);
                $stmt->execute();
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($product) {
                    if (isset($_SESSION['cart'][$product_id])) {
                        $_SESSION['cart'][$product_id]['quantity'] += $quantity;
                    } else {
                        $_SESSION['cart'][$product_id] = [
                            'name' => $product['name'],
                            'price' => (float)$product['price'], // Pastikan price adalah float
                            'quantity' => $quantity
                        ];
                    }
                    // Batasi kuantitas agar tidak melebihi stok
                    if ($_SESSION['cart'][$product_id]['quantity'] > $product['stock']) {
                        $_SESSION['cart'][$product_id]['quantity'] = $product['stock'];
                    }
                    $response = ['success' => true, 'message' => 'Produk ditambahkan ke keranjang!', 'cart' => $_SESSION['cart']];
                } else {
                    $response['message'] = 'Produk tidak ditemukan.';
                }
                break;

            case 'update':
                if (isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id]['quantity'] = max(1, $quantity);
                    
                    // Cek stok lagi saat update
                    $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
                    $stmt->bindParam(1, $product_id, PDO::PARAM_INT);
                    $stmt->execute();
                    $db_stock = $stmt->fetch(PDO::FETCH_ASSOC)['stock'] ?? 0;

                    if ($_SESSION['cart'][$product_id]['quantity'] > $db_stock) {
                        $_SESSION['cart'][$product_id]['quantity'] = $db_stock;
                    }
                    $response = ['success' => true, 'message' => 'Keranjang diperbarui!', 'cart' => $_SESSION['cart']];
                } else {
                    $response['message'] = 'Item keranjang tidak ditemukan.';
                }
                break;

            case 'remove':
                if (isset($_SESSION['cart'][$product_id])) {
                    unset($_SESSION['cart'][$product_id]);
                    $response = ['success' => true, 'message' => 'Item dihapus dari keranjang!', 'cart' => $_SESSION['cart']];
                } else {
                    $response['message'] = 'Item keranjang tidak ditemukan.';
                }
                break;
            
            case 'get_cart': // Endpoint baru untuk mengambil status keranjang
                $response = ['success' => true, 'cart' => $_SESSION['cart']];
                break;

            default:
                $response['message'] = 'Aksi tidak valid.';
                break;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        $response = ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    } catch (Exception $e) {
        http_response_code(500);
        $response = ['success' => false, 'message' => 'An unexpected error occurred: ' . $e->getMessage()];
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_cart') {
    // Handle GET request for cart status (e.g., for initial page load of cart)
    $response = ['success' => true, 'cart' => $_SESSION['cart']];
}

echo json_encode($response);
?>