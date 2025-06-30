<?php
// api/checkout.php
session_start(); // Pastikan sesi dimulai

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

$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $name = sanitize_input($input['name'] ?? '');
    $address = sanitize_input($input['address'] ?? '');
    $phone = sanitize_input($input['phone'] ?? '');
    $cart_items = $input['cart_items'] ?? [];
    $total_amount = $input['total_amount'] ?? 0;

    if (empty($name) || empty($address) || empty($phone) || empty($cart_items)) {
        $response['message'] = 'Semua field dan item keranjang harus diisi.';
        echo json_encode($response);
        exit();
    }

    try {
        $conn->beginTransaction(); // Mulai transaksi database

        // Simpan pesanan ke tabel 'orders' (Anda perlu membuat tabel ini di DB)
        // Contoh: CREATE TABLE orders (id SERIAL PRIMARY KEY, customer_name VARCHAR(255), address TEXT, phone VARCHAR(20), total_amount NUMERIC(10,2), order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
        $stmt_order = $conn->prepare("INSERT INTO orders (customer_name, address, phone, total_amount) VALUES (?, ?, ?, ?)");
        $stmt_order->bindParam(1, $name, PDO::PARAM_STR);
        $stmt_order->bindParam(2, $address, PDO::PARAM_STR);
        $stmt_order->bindParam(3, $phone, PDO::PARAM_STR);
        $stmt_order->bindParam(4, $total_amount, PDO::PARAM_STR); // NUMERIC bisa di-bind sebagai STR
        $stmt_order->execute();
        $order_id = $conn->lastInsertId(); // Ambil ID pesanan yang baru dibuat

        // Simpan item pesanan ke tabel 'order_items' (Anda perlu membuat tabel ini di DB)
        // Contoh: CREATE TABLE order_items (id SERIAL PRIMARY KEY, order_id INT REFERENCES orders(id), product_id INT, product_name VARCHAR(255), price NUMERIC(10,2), quantity INT);
        $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity) VALUES (?, ?, ?, ?, ?)");
        foreach ($cart_items as $product_id => $item) {
            $stmt_item->bindParam(1, $order_id, PDO::PARAM_INT);
            $stmt_item->bindParam(2, $product_id, PDO::PARAM_INT);
            $stmt_item->bindParam(3, $item['name'], PDO::PARAM_STR);
            $stmt_item->bindParam(4, $item['price'], PDO::PARAM_STR);
            $stmt_item->bindParam(5, $item['quantity'], PDO::PARAM_INT);
            $stmt_item->execute();

            // Kurangi stok produk (Anda bisa menambahkan ini jika perlu)
            // $stmt_update_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            // $stmt_update_stock->bindParam(1, $item['quantity'], PDO::PARAM_INT);
            // $stmt_update_stock->bindParam(2, $product_id, PDO::PARAM_INT);
            // $stmt_update_stock->execute();
        }

        $conn->commit(); // Komit transaksi jika semua berhasil
        
        // Bersihkan keranjang di sesi setelah pesanan berhasil
        unset($_SESSION['cart']);

        $response = ['success' => true, 'message' => 'Pesanan berhasil ditempatkan!'];

    } catch (PDOException $e) {
        $conn->rollBack(); // Rollback transaksi jika ada error
        http_response_code(500);
        $response = ['success' => false, 'message' => 'Database error during checkout: ' . $e->getMessage()];
    } catch (Exception $e) {
        $conn->rollBack();
        http_response_code(500);
        $response = ['success' => false, 'message' => 'An unexpected error occurred during checkout: ' . $e->getMessage()];
    }
}

echo json_encode($response);
?>