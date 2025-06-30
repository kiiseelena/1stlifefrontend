// public/js/cart.js

document.addEventListener('DOMContentLoaded', () => {
    const cartItemsContainer = document.getElementById('cart-items-container');
    const cartSummaryDiv = document.getElementById('cart-summary');
    const apiBaseUrl = 'http://localhost:8000/api'; // URL dasar untuk API PHP Anda

    async function fetchCart() {
        try {
            const response = await fetch(`${apiBaseUrl}/cart.php?action=get_cart`); // Ambil keranjang dari API
            const data = await response.json();

            if (data.success) {
                renderCart(data.cart); // Tampilkan keranjang
            } else {
                cartItemsContainer.innerHTML = `<p class="alert alert-danger">Gagal memuat keranjang: ${data.message}</p>`;
            }
        } catch (error) {
            cartItemsContainer.innerHTML = `<p class="alert alert-danger">Terjadi kesalahan jaringan: ${error.message}</p>`;
            console.error('Error fetching cart:', error);
        }
    }

    async function updateCartItem(productId, action, quantity = 1) {
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
                fetchCart(); // Muat ulang keranjang setelah update/remove
            } else {
                alert('Gagal memperbarui keranjang: ' + data.message);
            }
        } catch (error) {
            alert('Terjadi kesalahan jaringan saat memperbarui keranjang.');
            console.error('Error updating cart:', error);
        }
    }

    function renderCart(cart) {
        let totalCart = 0;
        let cartHtml = '';

        if (Object.keys(cart).length === 0) {
            cartItemsContainer.innerHTML = '<p>Keranjang Anda kosong. Yuk, <a href="index.html">mulai belanja</a>!</p>';
            cartSummaryDiv.style.display = 'none';
            return;
        }

        // Mulai tabel HTML
        cartHtml += `
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
        `;

        for (const id in cart) {
            const item = cart[id];
            const subtotal = item.price * item.quantity;
            totalCart += subtotal;

            cartHtml += `
                <tr data-product-id="${id}">
                    <td>${htmlspecialchars(item.name)}</td>
                    <td>Rp ${new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(item.price)}</td>
                    <td>
                        <form class="update-cart-form" style="display:inline;">
                            <input type="hidden" name="product_id" value="${id}">
                            <input type="hidden" name="action" value="update">
                            <input type="number" name="quantity" value="${item.quantity}" min="1" style="width: 60px;">
                            <button type="submit">Update</button>
                        </form>
                    </td>
                    <td>Rp ${new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(subtotal)}</td>
                    <td>
                        <form class="remove-cart-form" style="display:inline;">
                            <input type="hidden" name="product_id" value="${id}">
                            <input type="hidden" name="action" value="remove">
                            <button type="submit" class="remove-btn">Hapus</button>
                        </form>
                    </td>
                </tr>
            `;
        }

        // Akhiri tabel
        cartHtml += `
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3">Total</td>
                        <td><strong>Rp ${new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(totalCart)}</strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        `;
        cartItemsContainer.innerHTML = cartHtml;

        // Render summary dan tombol
        cartSummaryDiv.innerHTML = `
            <a href="index.html" class="button">Lanjutkan Belanja</a>
            <a href="checkout.html" class="button primary">Lanjut ke Checkout</a> `;
        cartSummaryDiv.style.display = 'block';

        // Tambahkan event listener untuk form update/remove
        addFormListeners();
    }

    function addFormListeners() {
        document.querySelectorAll('.update-cart-form').forEach(form => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                const productId = form.querySelector('input[name="product_id"]').value;
                const quantity = form.querySelector('input[name="quantity"]').value;
                updateCartItem(productId, 'update', parseInt(quantity));
            });
        });

        document.querySelectorAll('.remove-cart-form').forEach(form => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                const productId = form.querySelector('input[name="product_id"]').value;
                updateCartItem(productId, 'remove');
            });
        });
    }

    // Fungsi helper untuk htmlspecialchars di JavaScript
    function htmlspecialchars(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // Panggil fungsi untuk mengambil keranjang saat halaman dimuat
    fetchCart();
});