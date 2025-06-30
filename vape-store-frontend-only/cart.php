<?php
// public/cart.php
session_start();
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/functions.php';

// Tidak ada lagi POST handling langsung di sini, itu sudah di API

include __DIR__ . '/../includes/header.php';

// Ambil data keranjang dari sesi untuk ditampilkan
$cart_items = $_SESSION['cart'] ?? [];
?>

<section class="cart-content">
    <h2>Keranjang Belanja Anda</h2>
    <?php if (empty($cart_items)): ?>
        <p>Keranjang Anda kosong. Yuk, <a href="index.html">mulai belanja</a>!</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Harga</th>
                    <th>Jumlah</th>
                    <th>Subtotal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total_cart = 0;
                foreach ($cart_items as $id => $item):
                    $subtotal = $item['price'] * $item['quantity'];
                    $total_cart += $subtotal;
                ?>
                    <tr data-product-id="<?php echo $id; ?>">
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td>Rp <?php echo number_format($item['price'], 2, ',', '.'); ?></td>
                        <td>
                            <form class="update-cart-form" style="display:inline;">
                                <input type="hidden" name="product_id" value="<?php echo $id; ?>">
                                <input type="hidden" name="action" value="update">
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" style="width: 60px;">
                                <button type="submit">Update</button>
                            </form>
                        </td>
                        <td>Rp <?php echo number_format($subtotal, 2, ',', '.'); ?></td>
                        <td>
                            <form class="remove-cart-form" style="display:inline;">
                                <input type="hidden" name="product_id" value="<?php echo $id; ?>">
                                <input type="hidden" name="action" value="remove">
                                <button type="submit" class="remove-btn">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">Total</td>
                    <td><strong>Rp <?php echo number_format($total_cart, 2, ',', '.'); ?></strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        <div class="cart-actions">
            <a href="index.html" class="button">Lanjutkan Belanja</a>
            <a href="checkout.php" class="button primary">Lanjut ke Checkout</a>
        </div>
    <?php endif; ?>
</section>

<?php
include __DIR__ . '/../includes/footer.php';
// Tidak perlu $conn->close() untuk PDO
?>
<script>
    // --- JavaScript untuk update/remove di halaman keranjang ---
    document.addEventListener('DOMContentLoaded', () => {
        const apiBaseUrl = 'http://localhost:8000/api'; // URL dasar untuk API PHP Anda

        async function updateCartViaAPI(productId, action, quantity = 1) {
            try {
                const response = await fetch(`${apiBaseUrl}/cart.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: action,
                        product_id: productId,
                        quantity: quantity
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert(data.message);
                    location.reload(); // Muat ulang halaman untuk menampilkan perubahan keranjang
                } else {
                    alert('Gagal memperbarui keranjang: ' + data.message);
                }
            } catch (error) {
                alert('Terjadi kesalahan jaringan saat memperbarui keranjang.');
                console.error('Error updating cart:', error);
            }
        }

        document.querySelectorAll('.update-cart-form').forEach(form => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                const productId = form.querySelector('input[name="product_id"]').value;
                const quantity = form.querySelector('input[name="quantity"]').value;
                updateCartViaAPI(productId, 'update', parseInt(quantity));
            });
        });

        document.querySelectorAll('.remove-cart-form').forEach(form => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                const productId = form.querySelector('input[name="product_id"]').value;
                updateCartViaAPI(productId, 'remove');
            });
        });
    });
</script>