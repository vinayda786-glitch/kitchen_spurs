@extends('layouts.app')

@section('title', 'Kitchen - Artisanal Dining & Online Ordering')

@section('header_actions')
    <!-- Cart Trigger Button -->
    <button onclick="toggleCart()" class="relative p-2.5 text-stone-700 hover:text-orange-600 hover:bg-stone-100 rounded-xl transition-colors">
        <i class="fa-solid fa-basket-shopping text-xl"></i>
        <span id="cart-badge" class="absolute -top-1 -right-1 bg-orange-600 text-white text-[11px] font-bold w-5 h-5 rounded-full flex items-center justify-center scale-0 transition-transform">0</span>
    </button>
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Hero / Architecture Banner -->
    <div class="relative overflow-hidden rounded-3xl p-8 sm:p-12 mb-10 shadow-2xl border border-stone-800" 
         style="background: linear-gradient(135deg, #1c1917 0%, #171412 50%, #3d1405 100%); color: #ffffff;">
        <div class="relative z-10 max-w-2xl">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-orange-500/20 text-orange-400 border border-orange-500/30 mb-4">
                <i class="fa-solid fa-satellite-dish"></i> End-to-End Analytics Pipeline Active
            </span>
            <h1 class="text-3xl sm:text-5xl font-black tracking-tight text-white mb-4 leading-tight">
                Savor the Craft. <br/><span class="text-orange-400">Tracked in Real-Time.</span>
            </h1>
            <p class="text-stone-300 text-sm sm:text-base leading-relaxed mb-6 font-normal">
                Browse our seasonal kitchen selections. Every page visit, item view, and cart action is captured by the <strong class="text-white font-bold">Browser SDK</strong>, while final checkouts trigger our secure <strong class="text-white font-bold">Server SDK</strong>.
            </p>
            <div class="flex flex-wrap gap-3">
                <a href="#menu-grid" class="px-5 py-2.5 bg-orange-600 hover:bg-orange-500 text-white rounded-xl font-bold transition-all shadow-lg shadow-orange-600/30 flex items-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-utensils"></i> Explore Menu
                </a>
                <a href="{{ route('analytics.dashboard') }}" class="px-5 py-2.5 bg-stone-800/80 hover:bg-stone-700 text-white rounded-xl font-bold backdrop-blur transition-all border border-stone-600 flex items-center gap-2 cursor-pointer shadow-md">
                    <i class="fa-solid fa-chart-pie text-orange-400"></i> Open Live Dashboard
                </a>
            </div>
        </div>
        <!-- Decorative Background Element -->
        <div class="absolute right-0 top-0 bottom-0 w-1/3 opacity-20 pointer-events-none hidden md:flex items-center justify-center">
            <i class="fa-solid fa-pizza-slice text-9xl text-orange-400"></i>
        </div>
    </div>

    <!-- Category Filter Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto pb-4 mb-8 no-scrollbar" id="menu-grid">
        <button onclick="filterCategory('All')" class="category-pill active px-4 py-2 rounded-xl text-sm font-semibold whitespace-nowrap transition-all bg-orange-600 text-white shadow-sm">
            All Items ({{ $menuItems->count() }})
        </button>
        @foreach($categories as $category)
            <button onclick="filterCategory('{{ $category }}')" class="category-pill px-4 py-2 rounded-xl text-sm font-semibold whitespace-nowrap transition-all bg-white text-stone-600 hover:bg-stone-100 hover:text-stone-900 border border-stone-200">
                {{ $category }}
            </button>
        @endforeach
    </div>

    <!-- Menu Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @foreach($menuItems as $item)
            <div class="menu-item-card bg-white rounded-2xl border border-stone-200 overflow-hidden shadow-xs hover:shadow-xl transition-all duration-300 flex flex-col group"
                 data-category="{{ $item->category }}"
                 data-id="{{ $item->id }}"
                 data-name="{{ $item->name }}"
                 data-price="{{ $item->price }}">
                
                <!-- Card Image -->
                <div class="relative h-48 overflow-hidden bg-stone-100 cursor-pointer" onclick="viewItemModal({{ json_encode($item) }})">
                    <img src="{{ $item->image_url }}" alt="{{ $item->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    <span class="absolute top-3 left-3 px-2.5 py-1 bg-white/90 backdrop-blur rounded-full text-xs font-semibold text-stone-800 shadow-xs">
                        {{ $item->category }}
                    </span>
                    <span class="absolute bottom-3 right-3 px-3 py-1 bg-stone-900/80 backdrop-blur rounded-full text-sm font-bold text-white shadow-xs">
                        ${{ number_format($item->price, 2) }}
                    </span>
                </div>

                <!-- Card Body -->
                <div class="p-5 flex-grow flex flex-col justify-between">
                    <div>
                        <h3 class="text-base font-bold text-stone-900 group-hover:text-orange-600 transition-colors line-clamp-1 mb-1.5 cursor-pointer"
                            onclick="viewItemModal({{ json_encode($item) }})">
                            {{ $item->name }}
                        </h3>
                        <p class="text-xs text-stone-500 line-clamp-2 leading-relaxed mb-4">
                            {{ $item->description }}
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 pt-2 border-t border-stone-100">
                        <button onclick="viewItemModal({{ json_encode($item) }})" 
                                class="flex-1 py-2 px-3 text-xs font-semibold text-stone-600 bg-stone-100 hover:bg-stone-200 rounded-xl transition-colors text-center">
                            <i class="fa-regular fa-eye mr-1"></i> Details
                        </button>
                        <button onclick="addToCart({{ $item->id }}, '{{ addslashes($item->name) }}', {{ $item->price }}, '{{ $item->image_url }}')" 
                                class="py-2 px-4 text-xs font-semibold text-white bg-orange-600 hover:bg-orange-500 rounded-xl transition-all shadow-sm hover:shadow-orange-600/30 flex items-center gap-1.5">
                            <i class="fa-solid fa-plus"></i> Add
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- ============================================== -->
<!-- Item Details Modal (Triggers item_viewed event)-->
<!-- ============================================== -->
<div id="item-modal" style="z-index: 10000;" class="fixed inset-0 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs opacity-0 pointer-events-none transition-opacity duration-300">
    <div class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl transform scale-95 transition-transform duration-300">
        <div class="relative h-64 bg-stone-100">
            <img id="modal-img" src="" alt="" class="w-full h-full object-cover">
            <button onclick="closeItemModal()" class="absolute top-4 right-4 w-9 h-9 rounded-full bg-white/80 backdrop-blur text-stone-700 hover:bg-white flex items-center justify-center transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <span id="modal-category" class="absolute top-4 left-4 px-3 py-1 bg-white/90 backdrop-blur rounded-full text-xs font-bold text-stone-900"></span>
        </div>
        <div class="p-6">
            <div class="flex justify-between items-baseline mb-3">
                <h2 id="modal-title" class="text-2xl font-bold text-stone-900"></h2>
                <span id="modal-price" class="text-2xl font-black text-orange-600"></span>
            </div>
            <p id="modal-desc" class="text-sm text-stone-600 leading-relaxed mb-6"></p>
            
            <div class="flex gap-3">
                <button onclick="closeItemModal()" class="flex-1 py-3 bg-stone-100 hover:bg-stone-200 text-stone-700 rounded-xl font-semibold text-sm transition-colors">
                    Close
                </button>
                <button id="modal-add-btn" class="flex-1 py-3 bg-orange-600 hover:bg-orange-500 text-white rounded-xl font-semibold text-sm transition-colors shadow-lg shadow-orange-600/20 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-basket-shopping"></i> Add to Cart
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================== -->
<!-- Cart & Checkout Slide-over Drawer -->
<!-- ============================================== -->
<div id="cart-drawer-backdrop" onclick="toggleCart(false)" style="z-index: 9990;" class="fixed inset-0 bg-black/10 opacity-0 pointer-events-none transition-opacity duration-300"></div>
<div id="cart-drawer" onclick="event.stopPropagation()" style="z-index: 9999;" class="fixed top-0 right-0 bottom-0 w-full sm:max-w-md md:max-w-lg bg-white shadow-2xl transform translate-x-full transition-transform duration-300 flex flex-col h-screen overflow-hidden">
    <!-- Header -->
    <div class="p-5 border-b border-stone-200 flex items-center justify-between bg-stone-50 shrink-0">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-basket-shopping text-orange-600 text-lg"></i>
            <h2 class="text-lg font-bold text-stone-900">Your Order Cart</h2>
        </div>
        <button onclick="toggleCart(false)" class="w-8 h-8 rounded-lg hover:bg-stone-200 text-stone-500 flex items-center justify-center transition-colors">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <!-- Items List -->
    <div id="cart-items-container" class="flex-grow overflow-y-auto p-5 space-y-4 min-h-0">
        <!-- Empty Cart Placeholder (Persistent Node) -->
        <div id="empty-cart-state" class="text-center py-16 text-stone-400">
            <i class="fa-solid fa-utensils text-5xl mb-3 opacity-30"></i>
            <p class="text-sm font-medium">Your cart is empty.</p>
            <p class="text-xs text-stone-400">Select dishes from the menu to start ordering!</p>
        </div>
        <!-- Dynamic Cart Items Container -->
        <div id="cart-items-list" class="space-y-4 hidden"></div>
    </div>

    <!-- Checkout Form & Totals -->
    <div id="cart-footer" class="p-5 pb-8 border-t border-stone-200 bg-stone-50 space-y-4 shrink-0 overflow-y-auto max-h-[55vh] hidden shadow-inner">
        <!-- Order Summary -->
        <div class="space-y-1.5 text-xs text-stone-600">
            <div class="flex justify-between">
                <span>Subtotal</span>
                <span id="cart-subtotal" class="font-semibold text-stone-900">$0.00</span>
            </div>
            <div class="flex justify-between">
                <span>Estimated Tax (8%)</span>
                <span id="cart-tax" class="font-semibold text-stone-900">$0.00</span>
            </div>
            <div class="flex justify-between text-base font-bold text-stone-900 pt-2 border-t border-stone-200">
                <span>Total Amount</span>
                <span id="cart-total" class="text-orange-600 font-black">$0.00</span>
            </div>
        </div>

        <!-- Customer Identity Info (Feeds into Analytics.identify) -->
        <form id="checkout-form" onsubmit="handlePlaceOrder(event)" onclick="event.stopPropagation()" class="space-y-3 pt-2">
            <div>
                <label class="block text-xs font-semibold text-stone-700 mb-1">Your Full Name *</label>
                <input type="text" id="cust-name" required placeholder="e.g. Alex Morgan" onclick="event.stopPropagation()"
                       class="w-full text-xs px-3 py-2 rounded-lg border border-stone-300 focus:outline-hidden focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-stone-700 mb-1">Email Address *</label>
                <input type="email" id="cust-email" required placeholder="alex@example.com" onclick="event.stopPropagation()"
                       onblur="handleEmailIdentify(this.value)"
                       class="w-full text-xs px-3 py-2 rounded-lg border border-stone-300 focus:outline-hidden focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                <span class="text-[10px] text-stone-400 block mt-0.5"><i class="fa-solid fa-fingerprint text-orange-400"></i> Links anonymous visitor to user profile in analytics</span>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-semibold text-stone-700 mb-1">Table / Room</label>
                    <input type="text" id="cust-table" placeholder="Table 04" onclick="event.stopPropagation()"
                           class="w-full text-xs px-3 py-2 rounded-lg border border-stone-300 focus:outline-hidden focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-stone-700 mb-1">Phone</label>
                    <input type="tel" id="cust-phone" placeholder="+1 555-0192" onclick="event.stopPropagation()"
                           class="w-full text-xs px-3 py-2 rounded-lg border border-stone-300 focus:outline-hidden focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                </div>
            </div>

            <button type="submit" id="btn-place-order" 
                    class="w-full py-3.5 mb-4 bg-orange-600 hover:bg-orange-500 text-white font-bold text-sm rounded-xl transition-all shadow-lg shadow-orange-600/30 flex items-center justify-center gap-2 cursor-pointer">
                <i class="fa-solid fa-lock"></i>
                <span>Confirm & Place Order</span>
            </button>
        </form>
    </div>
</div>

<!-- ============================================== -->
<!-- Order Success Confirmation Modal -->
<!-- ============================================== -->
<div id="order-success-modal" style="z-index: 10000;" class="fixed inset-0 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs opacity-0 pointer-events-none transition-opacity duration-300">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 text-center shadow-2xl transform scale-95 transition-transform duration-300">
        <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 mx-auto flex items-center justify-center text-3xl mb-4">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <h3 class="text-2xl font-bold text-stone-900 mb-1">Order Confirmed!</h3>
        <p class="text-xs font-semibold text-orange-600 uppercase tracking-wider mb-3" id="success-order-num">ORD-20261014-9999</p>
        <p class="text-sm text-stone-600 mb-6 leading-relaxed">
            Thank you! Your meal is being prepared. <br/>
            <span class="inline-block bg-orange-50 text-orange-800 text-xs px-3 py-1.5 rounded-lg border border-orange-200 mt-2">
                <i class="fa-solid fa-server mr-1"></i> <strong>Server SDK</strong> securely tracked this purchase event with total $<span id="success-order-total"></span>!
            </span>
        </p>

        <div class="space-y-2">
            <a href="{{ route('analytics.dashboard') }}" class="w-full block py-3 bg-stone-900 hover:bg-stone-800 text-white rounded-xl font-bold text-sm transition-colors shadow-md">
                <i class="fa-solid fa-chart-line mr-1 text-orange-400"></i> View in Analytics Dashboard
            </a>
            <button onclick="closeSuccessModal()" class="w-full py-2.5 text-stone-500 hover:text-stone-800 text-sm font-medium transition-colors">
                Back to Menu
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // State
    let cart = [];

    // Filter Menu Categories
    function filterCategory(category) {
        document.querySelectorAll('.category-pill').forEach(btn => {
            btn.classList.remove('active', 'bg-orange-600', 'text-white');
            btn.classList.add('bg-white', 'text-stone-600');
        });
        event.currentTarget.classList.add('active', 'bg-orange-600', 'text-white');
        event.currentTarget.classList.remove('bg-white', 'text-stone-600');

        document.querySelectorAll('.menu-item-card').forEach(card => {
            if (category === 'All' || card.dataset.category === category) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });

        // Track Category Filter Event
        KitchenAnalytics.track('category_filtered', { category: category });
    }

    // Modal Details (Triggers item_viewed event)
    let currentModalItem = null;
    function viewItemModal(item) {
        currentModalItem = item;
        document.getElementById('modal-img').src = item.image_url;
        document.getElementById('modal-title').textContent = item.name;
        document.getElementById('modal-desc').textContent = item.description;
        document.getElementById('modal-price').textContent = '$' + parseFloat(item.price).toFixed(2);
        document.getElementById('modal-category').textContent = item.category;

        document.getElementById('modal-add-btn').onclick = function() {
            addToCart(item.id, item.name, item.price, item.image_url);
            closeItemModal();
        };

        const modal = document.getElementById('item-modal');
        modal.classList.remove('opacity-0', 'pointer-events-none');
        modal.firstElementChild.classList.remove('scale-95');

        // Track item_viewed event via Browser SDK
        KitchenAnalytics.track('item_viewed', {
            item_id: item.id,
            item_name: item.name,
            category: item.category,
            price: parseFloat(item.price),
        });
    }

    function closeItemModal() {
        const modal = document.getElementById('item-modal');
        modal.classList.add('opacity-0', 'pointer-events-none');
        modal.firstElementChild.classList.add('scale-95');
    }

    // Add to Cart Logic
    function addToCart(id, name, price, imageUrl) {
        const existing = cart.find(i => i.id === id);
        if (existing) {
            existing.quantity++;
        } else {
            cart.push({ id, name, price: parseFloat(price), image_url: imageUrl, quantity: 1 });
        }

        renderCart();
        showToast(`Added "${name}" to cart!`, 'success');

        // Track add_to_cart event via Browser SDK
        KitchenAnalytics.track('add_to_cart', {
            item_id: id,
            item_name: name,
            price: parseFloat(price),
            cart_count: cart.reduce((sum, i) => sum + i.quantity, 0)
        });
    }

    function updateQuantity(id, delta) {
        const item = cart.find(i => i.id === id);
        if (!item) return;

        item.quantity += delta;
        if (item.quantity <= 0) {
            cart = cart.filter(i => i.id !== id);
        }
        renderCart();
    }

    function renderCart() {
        const emptyState = document.getElementById('empty-cart-state');
        const itemsList = document.getElementById('cart-items-list');
        const footer = document.getElementById('cart-footer');
        const badge = document.getElementById('cart-badge');

        const totalItems = cart.reduce((sum, i) => sum + i.quantity, 0);

        if (totalItems > 0) {
            badge.textContent = totalItems;
            badge.classList.remove('scale-0');
            emptyState.classList.add('hidden');
            itemsList.classList.remove('hidden');
            footer.classList.remove('hidden');

            let subtotal = 0;
            let itemsHtml = '';

            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;
                subtotal += itemTotal;
                itemsHtml += `
                    <div class="flex items-center gap-3 p-3 bg-stone-50 rounded-xl border border-stone-200">
                        <img src="${item.image_url}" class="w-14 h-14 rounded-lg object-cover">
                        <div class="flex-grow">
                            <h4 class="text-xs font-bold text-stone-900">${item.name}</h4>
                            <p class="text-xs text-orange-600 font-semibold">$${item.price.toFixed(2)}</p>
                        </div>
                        <div class="flex items-center gap-2 bg-white rounded-lg border border-stone-200 p-1">
                            <button onclick="updateQuantity(${item.id}, -1)" class="w-5 h-5 text-stone-500 hover:text-stone-900 flex items-center justify-center text-xs">
                                <i class="fa-solid fa-minus"></i>
                            </button>
                            <span class="text-xs font-bold w-4 text-center">${item.quantity}</span>
                            <button onclick="updateQuantity(${item.id}, 1)" class="w-5 h-5 text-stone-500 hover:text-stone-900 flex items-center justify-center text-xs">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                    </div>
                `;
            });

            itemsList.innerHTML = itemsHtml;

            const tax = subtotal * 0.08;
            const total = subtotal + tax;

            document.getElementById('cart-subtotal').textContent = '$' + subtotal.toFixed(2);
            document.getElementById('cart-tax').textContent = '$' + tax.toFixed(2);
            document.getElementById('cart-total').textContent = '$' + total.toFixed(2);
        } else {
            badge.classList.add('scale-0');
            itemsList.innerHTML = '';
            itemsList.classList.add('hidden');
            emptyState.classList.remove('hidden');
            footer.classList.add('hidden');
        }
    }

    function toggleCart(forceState) {
        const drawer = document.getElementById('cart-drawer');
        const backdrop = document.getElementById('cart-drawer-backdrop');
        const evalBar = document.getElementById('evaluator-bar');
        const isCurrentlyOpen = !drawer.classList.contains('translate-x-full');
        
        const shouldOpen = (typeof forceState === 'boolean') ? forceState : !isCurrentlyOpen;

        if (!shouldOpen) {
            drawer.classList.add('translate-x-full');
            backdrop.classList.add('opacity-0', 'pointer-events-none');
            if (evalBar) {
                evalBar.style.display = 'flex';
                evalBar.classList.remove('opacity-0', 'pointer-events-none');
            }
        } else {
            drawer.classList.remove('translate-x-full');
            backdrop.classList.remove('opacity-0', 'pointer-events-none');
            if (evalBar) {
                evalBar.style.display = 'none';
                evalBar.classList.add('opacity-0', 'pointer-events-none');
            }

            if (cart.length > 0) {
                // Track checkout_started
                KitchenAnalytics.track('checkout_started', {
                    cart_count: cart.reduce((sum, i) => sum + i.quantity, 0),
                    subtotal: parseFloat(document.getElementById('cart-subtotal').textContent.replace('$', ''))
                });
            }
        }
    }

    // Customer Email Identification
    function handleEmailIdentify(email) {
        if (email && email.includes('@')) {
            const name = document.getElementById('cust-name').value;
            KitchenAnalytics.identify(email, {
                email: email,
                name: name
            });
        }
    }

    // Order Submission
    async function handlePlaceOrder(event) {
        event.preventDefault();
        const btn = document.getElementById('btn-place-order');
        const origText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin"></i> Processing Order...`;

        const payload = {
            customer_name: document.getElementById('cust-name').value,
            customer_email: document.getElementById('cust-email').value,
            customer_phone: document.getElementById('cust-phone').value,
            table_number: document.getElementById('cust-table').value,
            items: cart.map(i => ({ id: i.id, quantity: i.quantity })),
            anonymous_id: KitchenAnalytics.anonymousId,
            session_id: KitchenAnalytics.sessionId,
        };

        try {
            const res = await fetch('/orders', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Anonymous-Id': KitchenAnalytics.anonymousId,
                    'X-Session-Id': KitchenAnalytics.sessionId,
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            if (res.ok) {
                // Clear cart
                cart = [];
                renderCart();
                toggleCart();

                // Open success modal
                document.getElementById('success-order-num').textContent = data.order.order_number;
                document.getElementById('success-order-total').textContent = data.order.total_amount;
                
                const modal = document.getElementById('order-success-modal');
                modal.classList.remove('opacity-0', 'pointer-events-none');
                modal.firstElementChild.classList.remove('scale-95');
            } else {
                showToast('Failed to place order: ' + (data.message || 'Validation error'), 'error');
            }
        } catch (e) {
            showToast('Order failed: ' + e.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = origText;
        }
    }

    function closeSuccessModal() {
        const modal = document.getElementById('order-success-modal');
        modal.classList.add('opacity-0', 'pointer-events-none');
        modal.firstElementChild.classList.add('scale-95');
        const evalBar = document.getElementById('evaluator-bar');
        if (evalBar) {
            evalBar.style.display = 'flex';
            evalBar.classList.remove('opacity-0', 'pointer-events-none');
        }
    }
</script>
@endpush

