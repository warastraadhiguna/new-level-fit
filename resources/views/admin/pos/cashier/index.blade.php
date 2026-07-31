<div class="col-xl-12">
    <form action="{{ route('pos.checkout') }}" method="POST" id="posForm">
        @csrf
        <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
        <div class="row">
            <div class="col-xl-7">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Products</h4></div>
                    <div class="card-body">
                        <input type="search" id="productSearch" class="form-control mb-3" placeholder="Scan barcode atau cari nama / SKU..." autofocus>
                        <div class="row" id="productGrid"></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Cart</h4></div>
                    <div class="card-body">
                        <div id="emptyCart" class="text-center text-muted py-4">Belum ada produk.</div>
                        <div class="table-responsive"><table class="table"><tbody id="cartBody"></tbody></table></div>
                        <div class="mb-3"><label class="form-label">Customer Name (optional)</label><input class="form-control" name="customer_name"></div>
                        <div class="mb-3"><label class="form-label">Discount</label><input type="number" min="0" step="0.01" value="0" class="form-control" name="discount_amount" id="discountAmount"></div>
                        <div class="d-flex justify-content-between"><span>Subtotal</span><strong id="subtotalText">Rp. 0</strong></div>
                        <div class="d-flex justify-content-between fs-4 mt-2"><span>Total</span><strong id="grandTotalText">Rp. 0</strong></div>
                        <hr>
                        <div class="mb-3"><label class="form-label">Payment Method</label><select class="form-control" name="method_payment_id" required><option value="">-- Choose --</option>@foreach ($paymentMethods as $method)<option value="{{ $method->id }}">{{ $method->name }}</option>@endforeach</select></div>
                        <div class="mb-3"><label class="form-label">Paid Amount</label><input type="number" min="0" step="0.01" class="form-control" name="paid_amount" id="paidAmount" required></div>
                        <div class="mb-3"><label class="form-label">Reference Number (optional)</label><input class="form-control" name="reference_number"></div>
                        <div class="d-flex justify-content-between mb-3"><span>Change</span><strong id="changeText">Rp. 0</strong></div>
                        <button type="submit" class="btn btn-primary w-100" id="checkoutButton">Complete Payment</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const products = @json($productPayload);
    const cart = {};
    const grid = document.querySelector('#productGrid');
    const cartBody = document.querySelector('#cartBody');
    const search = document.querySelector('#productSearch');
    const money = value => 'Rp. ' + Math.round(Number(value || 0)).toLocaleString('id-ID');

    function renderProducts(term = '') {
        term = term.toLowerCase().trim();
        const filtered = products.filter(p => !term || `${p.name} ${p.sku} ${p.barcode || ''}`.toLowerCase().includes(term));
        grid.innerHTML = filtered.map(p => `<div class="col-md-6 mb-3"><button type="button" class="pos-product-card w-100 h-100 text-start product-button" data-id="${p.id}"><div class="p-3"><strong class="pos-product-name">${p.name}</strong><div class="text-muted pos-product-code">${p.sku}${p.barcode ? ' · '+p.barcode : ''}</div><div class="d-flex justify-content-between align-items-center gap-2 mt-3"><span class="pos-product-price">${money(p.price)}</span><span class="pos-product-stock">Stock: ${p.stock} ${p.unit}</span></div></div></button></div>`).join('');
    }
    function totals() {
        const subtotal = Object.values(cart).reduce((sum, row) => sum + row.quantity * row.price, 0);
        const discount = Math.max(0, Number(document.querySelector('#discountAmount').value || 0));
        const total = Math.max(0, subtotal - discount);
        const paid = Number(document.querySelector('#paidAmount').value || 0);
        document.querySelector('#subtotalText').textContent = money(subtotal);
        document.querySelector('#grandTotalText').textContent = money(total);
        document.querySelector('#changeText').textContent = money(Math.max(0, paid - total));
        return total;
    }
    function renderCart() {
        const rows = Object.values(cart);
        document.querySelector('#emptyCart').style.display = rows.length ? 'none' : 'block';
        cartBody.innerHTML = rows.map((row, index) => `<tr><td><strong>${row.name}</strong><br><small>${money(row.price)}</small><input type="hidden" name="items[${index}][product_id]" value="${row.id}"></td><td class="cart-quantity-cell"><div class="cart-quantity-control"><button type="button" class="btn btn-outline-secondary cart-minus" data-id="${row.id}">−</button><input type="number" class="form-control text-center cart-qty" data-id="${row.id}" name="items[${index}][quantity]" min="0.001" max="${row.stock}" step="0.001" value="${row.quantity}"><button type="button" class="btn btn-outline-secondary cart-plus" data-id="${row.id}">+</button></div></td><td class="text-end">${money(row.quantity * row.price)}<br><button type="button" class="btn btn-link text-danger p-0 cart-remove" data-id="${row.id}">remove</button></td></tr>`).join('');
        totals();
    }
    function add(id) {
        const product = products.find(p => p.id === Number(id));
        if (!product) return;
        if (!cart[id]) cart[id] = {...product, quantity: 0};
        if (cart[id].quantity + 1 > product.stock) return;
        cart[id].quantity += 1;
        renderCart();
    }
    grid.addEventListener('click', e => { const button = e.target.closest('.product-button'); if (button) add(button.dataset.id); });
    search.addEventListener('input', () => renderProducts(search.value));
    search.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const exact = products.find(p => p.barcode && p.barcode === search.value.trim());
            if (exact) { add(exact.id); search.value = ''; renderProducts(); }
        }
    });
    cartBody.addEventListener('click', e => {
        const id = e.target.dataset.id;
        if (!id || !cart[id]) return;
        if (e.target.classList.contains('cart-plus') && cart[id].quantity + 1 <= cart[id].stock) cart[id].quantity++;
        if (e.target.classList.contains('cart-minus')) cart[id].quantity--;
        if (e.target.classList.contains('cart-remove') || cart[id].quantity <= 0) delete cart[id];
        renderCart();
    });
    cartBody.addEventListener('change', e => {
        if (e.target.classList.contains('cart-qty')) {
            const row = cart[e.target.dataset.id];
            row.quantity = Math.min(row.stock, Math.max(0.001, Number(e.target.value || 0.001)));
            renderCart();
        }
    });
    document.querySelector('#discountAmount').addEventListener('input', totals);
    document.querySelector('#paidAmount').addEventListener('input', totals);
    document.querySelector('#posForm').addEventListener('submit', function (e) {
        if (!Object.keys(cart).length) { e.preventDefault(); alert('Keranjang masih kosong.'); return; }
        const button = document.querySelector('#checkoutButton'); button.disabled = true; button.textContent = 'Processing...';
    });
    renderProducts();
});
</script>
<style>
    .pos-product-card {
        display: block;
        overflow: hidden;
        color: inherit;
        background: #fff;
        border: 1px solid #d8dbe5;
        border-radius: 10px;
        box-shadow: 0 2px 7px rgba(33, 40, 66, 0.06);
        cursor: pointer;
        transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
    }

    .pos-product-card:hover,
    .pos-product-card:focus {
        color: inherit;
        background: #fff;
        border-color: #5649c9;
        box-shadow: 0 5px 14px rgba(86, 73, 201, 0.16);
        outline: none;
        transform: translateY(-2px);
    }

    .pos-product-name {
        display: block;
        color: #20243a;
        font-size: 15px;
    }

    .pos-product-code {
        min-height: 21px;
        margin-top: 3px;
        font-size: 13px;
    }

    .pos-product-price {
        color: #20243a;
        font-weight: 600;
    }

    .pos-product-stock {
        color: #62677a;
        font-size: 13px;
        white-space: nowrap;
    }

    .cart-quantity-cell {
        width: 132px;
        min-width: 132px;
    }

    .cart-quantity-control {
        display: flex;
        flex-wrap: nowrap;
        align-items: stretch;
        width: 126px;
    }

    .cart-quantity-control .btn {
        flex: 0 0 38px;
        width: 38px;
        min-width: 38px;
        height: 38px;
        padding: 0;
        border-radius: 0;
    }

    .cart-quantity-control .btn:first-child {
        border-radius: 0.375rem 0 0 0.375rem;
    }

    .cart-quantity-control .btn:last-child {
        border-radius: 0 0.375rem 0.375rem 0;
    }

    .cart-quantity-control .cart-qty {
        flex: 1 1 50px;
        width: 50px;
        min-width: 0;
        height: 38px;
        padding: 0 2px;
        border-right: 0;
        border-left: 0;
        border-radius: 0;
        appearance: textfield;
        -moz-appearance: textfield;
    }

    .cart-quantity-control .cart-qty::-webkit-inner-spin-button,
    .cart-quantity-control .cart-qty::-webkit-outer-spin-button {
        margin: 0;
        -webkit-appearance: none;
    }
</style>
