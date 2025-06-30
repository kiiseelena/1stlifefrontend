// public/js/app.js

document.addEventListener('DOMContentLoaded', () => {
    const productsContainer = document.getElementById('products-container');
    const apiBaseUrl = 'http://localhost:8000/api'; // URL dasar untuk API PHP Anda

    async function fetchProducts() {
        try {
            const response = await fetch(`${apiBaseUrl}/products.php`);
            const data = await response.json();

            if (data.success) {
                renderProducts(data.data);
            } else {
                productsContainer.innerHTML = `<p class="alert alert-danger">Gagal memuat produk: ${data.message}</p>`;
            }
        } catch (error) {
            productsContainer.innerHTML = `<p class="alert alert-danger">Terjadi kesalahan jaringan: ${error.message}</p>`;
            console.error('Error fetching products:', error);
        }
    }

    function renderProducts(products) {
        if (products.length === 0) {
            productsContainer.innerHTML = '<p class="alert alert-info">Belum ada produk.</p>';
            return;
        }

        productsContainer.innerHTML = ''; // Kosongkan container

        products.forEach(product => {
            const productItem = document.createElement('div');
            productItem.className = 'product-item'; 

            const productLink = document.createElement('a');
            // productLink.href = `product.html?id=${product.id}`; // Jika Anda punya product.html terpisah
            productLink.href = `#product-detail/${product.id}`; // Contoh untuk SPA, atau bisa ke product.php jika masih PHP
            
            const img = document.createElement('img');
            img.src = product.image_url || 'uploads/default.jpg';
            img.alt = product.name;

            const title = document.createElement('h3');
            title.textContent = product.name;

            const price = document.createElement('p');
            price.className = 'price';
            price.textContent = `Rp ${new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(product.price)}`;

            const form = document.createElement('form');
            form.className = 'add-to-cart-form'; // Tambahkan class untuk identifikasi form

            const productIdInput = document.createElement('input');
            productIdInput.type = 'hidden';
            productIdInput.name = 'product_id';
            productIdInput.value = product.id;

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'add';

            const button = document.createElement('button');
            button.type = 'submit'; // Tetap submit agar bisa pakai form.addEventListener
            button.className = 'add-to-cart-btn';
            button.textContent = 'Tambah ke Keranjang';

            // Membangun struktur DOM
            productLink.appendChild(img);
            productLink.appendChild(title);
            productLink.appendChild(price);
            
            form.appendChild(productIdInput);
            form.appendChild(actionInput);
            form.appendChild(button);
            
            productItem.appendChild(productLink);
            productItem.appendChild(form);
            
            productsContainer.appendChild(productItem);
        });

        // Panggil fungsi untuk menambahkan event listener ke semua form setelah produk dirender
        addCartButtonListeners();
    }

    // --- FUNGSI BARU UNTUK MENAMBAH ITEM KE KERANJANG VIA API ---
    function addCartButtonListeners() {
        document.querySelectorAll('.add-to-cart-form').forEach(form => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault(); // Mencegah submit form default (page reload)

                const productId = form.querySelector('input[name="product_id"]').value;
                const action = form.querySelector('input[name="action"]').value;
                const quantity = 1; // Untuk tombol 'Tambah ke Keranjang', default quantity 1

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
                        alert(data.message); // Tampilkan notifikasi sukses
                        // Anda bisa update icon keranjang di sini jika ada
                        console.log('Cart updated:', data.cart);
                    } else {
                        alert('Gagal menambah produk ke keranjang: ' + data.message);
                    }
                } catch (error) {
                    alert('Terjadi kesalahan jaringan saat menambah produk ke keranjang.');
                    console.error('Error adding to cart:', error);
                }
            });
        });
    }
    // --- AKHIR FUNGSI BARU ---

    // Panggil fungsi untuk mengambil produk saat halaman dimuat
    fetchProducts();
});