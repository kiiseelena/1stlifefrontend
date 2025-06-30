// public/js/checkout.js

document.addEventListener('DOMContentLoaded', () => {
    const checkoutSummaryDiv = document.getElementById('checkout-summary');
    const checkoutForm = document.getElementById('checkout-form-data');
    const apiBaseUrl = 'http://localhost:8000/api';

    async function fetchCartForCheckout() {
        try {
            const response = await fetch(`${apiBaseUrl}/cart.php?action=get_cart`);
            const data = await response.json();

            if (data.success) {
                renderCheckoutSummary(data.cart);
            } else {
                checkoutSummaryDiv.innerHTML = `<p class="alert alert-danger">Gagal memuat ringkasan keranjang: ${data.message}</p>`;
                checkoutForm.style.display = 'none'; // Sembunyikan form jika gagal
            }
        } catch (error) {
            checkoutSummaryDiv.innerHTML = `<p class="alert alert-danger">Terjadi kesalahan jaringan: ${error.message}</p>`;
            checkoutForm.style.display = 'none';
            console.error('Error fetching cart for checkout:', error);
        }
    }

    function renderCheckoutSummary(cart) {
        if (Object.keys(cart).length === 0) {
            checkoutSummaryDiv.innerHTML = '<p>Keranjang Anda kosong. Silakan <a href="index.html">mulai belanja</a> terlebih dahulu.</p>';
            checkoutForm.style.display = 'none';
            return;
        }

        let summaryHtml = '<h3>Detail Pesanan Anda:</h3><ul>';
        let totalAmount = 0;

        for (const id in cart) {
            const item = cart[id];
            const subtotal = item.price * item.quantity;
            totalAmount += subtotal;
            summaryHtml += `<li>${htmlspecialchars(item.name)} (${item.quantity}x) - Rp ${new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(item.price)}</li>`;
        }
        summaryHtml += '</ul>';
        summaryHtml += `<h3>Total Pembayaran: <strong>Rp ${new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(totalAmount)}</strong></h3>`;
        
        checkoutSummaryDiv.innerHTML = summaryHtml;
        checkoutForm.style.display = 'block'; // Pastikan form terlihat
    }

    // Fungsi helper untuk htmlspecialchars di JavaScript (sama seperti di cart.js)
    function htmlspecialchars(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // Tambahkan event listener untuk submit form checkout
    checkoutForm.addEventListener('submit', async (event) => {
        event.preventDefault(); // Mencegah submit form default

        const formData = new FormData(checkoutForm);
        const customerName = formData.get('name');
        const address = formData.get('address');
        const phone = formData.get('phone');

        // Ambil data keranjang dari server lagi untuk memastikan konsistensi
        const cartResponse = await fetch(`${apiBaseUrl}/cart.php?action=get_cart`);
        const cartData = await cartResponse.json();

        if (!cartData.success || Object.keys(cartData.cart).length === 0) {
            alert('Keranjang Anda kosong atau tidak valid. Silakan kembali ke keranjang.');
            return;
        }
        const cartItems = cartData.cart;
        let totalAmount = 0;
        for (const id in cartItems) {
            totalAmount += cartItems[id].price * cartItems[id].quantity;
        }

        const orderData = {
            name: customerName,
            address: address,
            phone: phone,
            cart_items: cartItems, // Kirim item keranjang yang valid dari server
            total_amount: totalAmount
        };

        try {
            const response = await fetch(`${apiBaseUrl}/checkout.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(orderData)
            });

            const result = await response.json();

            if (result.success) {
                alert(result.message);
                // Redirect ke halaman utama setelah sukses
                window.location.href = 'index.html'; 
            } else {
                alert('Gagal mengajukan pesanan: ' + result.message);
            }
        } catch (error) {
            alert('Terjadi kesalahan jaringan saat mengajukan pesanan.');
            console.error('Error submitting checkout:', error);
        }
    });

    // Panggil fungsi untuk mengambil keranjang saat halaman dimuat
    fetchCartForCheckout();
});