<?php
// public/checkout.php
session_start();
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/functions.php';

include __DIR__ . '/../includes/header.php';

$cart_items = $_SESSION['cart'] ?? [];
$total_checkout = 0;
foreach ($cart_items as $id => $item) {
    $total_checkout += $item['price'] * $item['quantity'];
}

// Tidak ada lagi POST handling langsung di sini, itu akan di API

?>
<section class="checkout-form">
    <h2>Checkout Pesanan</h2>
    <?php if (empty($cart_items)): ?>
        <p>Keranjang Anda kosong. Silakan <a href="index.html">mulai belanja</a> terlebih dahulu.</p>
    <?php else: ?>
        <h3>Detail Pesanan Anda:</h3>
        <ul>
            <?php foreach ($cart_items as $item): ?>
                <li><?php echo htmlspecialchars($item['name']); ?> (<?php echo $item['quantity']; ?>x) - Rp <?php echo number_format($item['price'], 2, ',', '.'); ?></li>
            <?php endforeach; ?>
        </ul>
        <h3>Total Pembayaran: <strong>Rp <?php echo number_format($total_checkout, 2, ',', '.'); ?></strong></h3>

        <form id="checkout-form" method="post"> <div class="form-group">
                <label for="name">Nama Lengkap:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="address">Alamat Pengiriman:</label>
                <textarea id="address" name="address" rows="4" required></textarea>
            </div>
            <div class="form-group">
                <label for="phone">Nomor Telepon:</label>
                <input type="tel" id="phone" name="phone" required>
            </div>

            <button type="submit" class="button primary">Konfirmasi Pesanan</button>
        </form>
    <?php endif; ?>
</section>

<?php
include __DIR__ . '/../includes/footer.php';
?>
<script>
    // --- JavaScript untuk pengajuan Checkout ---
    document.addEventListener('DOMContentLoaded', () => {
        const checkoutForm = document.getElementById('checkout-form');
        const apiBaseUrl = 'http://localhost:8000/api';

        if (checkoutForm) {
            checkoutForm.addEventListener('submit', async (event) => {
                event.preventDefault(); // Mencegah submit form default

                const formData = new FormData(checkoutForm);
                const orderData = {
                    name: formData.get('name'),
                    address: formData.get('address'),
                    phone: formData.get('phone'),
                    cart_items: <?php echo json_encode($cart_items); ?>, // Ambil item keranjang dari PHP
                    total_amount: <?php echo json_encode($total_checkout); ?>
                };

                try {
                    const response = await fetch(`${apiBaseUrl}/checkout.php`, { // Panggil API checkout
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(orderData)
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert(data.message);
                        window.location.href = 'index.html'; // Redirect ke halaman utama setelah sukses
                    } else {
                        alert('Gagal mengajukan pesanan: ' + data.message);
                    }
                } catch (error) {
                    alert('Terjadi kesalahan jaringan saat mengajukan pesanan.');
                    console.error('Error submitting checkout:', error);
                }
            });
        }
    });
</script>