<?php
// Izinkan sesi jika diperlukan (misalnya untuk keranjang di masa depan)
// session_start(); 

// Sertakan konfigurasi database
// Sesuaikan path ini jika Anda menempatkan folder 'api' di lokasi lain
require_once __DIR__ . '/../../includes/db_config.php';
require_once __DIR__ . '/../../includes/functions.php'; // Mungkin tidak terlalu dibutuhkan untuk API sederhana ini

// --- Header untuk API ---
// Memberitahu browser bahwa respons adalah JSON
header('Content-Type: application/json');
// Mengizinkan permintaan dari origin mana pun (penting untuk pengembangan lintas domain)
// Di lingkungan produksi, Anda harus mengubah '*' ke domain frontend spesifik Anda
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS'); // Metode yang diizinkan
header('Access-Control-Allow-Headers: Content-Type, Authorization'); // Header yang diizinkan

// Tangani permintaan OPTIONS (untuk preflight CORS request)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
// --- Akhir Header untuk API ---


try {
    // Ambil data produk dari database
    // Filter atau parameter lain bisa ditambahkan di sini
    $sql = "SELECT id, name, description, price, stock, image_url FROM products ORDER BY price DESC";
    $stmt = $conn->query($sql);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mengembalikan data sebagai JSON dengan status sukses
    echo json_encode(['success' => true, 'data' => $products]);

} catch (PDOException $e) {
    // Jika ada error database, kembalikan respons error JSON
    http_response_code(500); // Set status HTTP ke 500 (Internal Server Error)
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    // Tangani error umum lainnya
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred: ' . $e->getMessage()]);
}

// Tidak perlu $conn->close() untuk PDO, koneksi akan ditutup otomatis saat skrip selesai
?>