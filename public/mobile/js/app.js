/**
 * AR Service - Mobile POS Application Logic
 */

(function () {
  'use strict';

  // --- Configuration & State ---
  const DEFAULT_SERVER_URL = window.location.protocol.startsWith('http') && window.location.hostname.includes('arcell.store')
    ? window.location.origin
    : 'https://www.arcell.store';

  const DEFAULT_API_KEY = 'pos-service-mobile-dev-key';

  let savedServerUrl = localStorage.getItem('ar_pos_server_url');
  if (!savedServerUrl || savedServerUrl.includes('192.168.') || savedServerUrl.includes('localhost') || savedServerUrl.includes('10.0.2.2')) {
    savedServerUrl = DEFAULT_SERVER_URL;
    localStorage.setItem('ar_pos_server_url', savedServerUrl);
  }

  const DEFAULT_RECEIPT_TEMPLATE = {
    shopName: 'AR Cell',
    slogan: 'Service HP , Kuota & Aksesoris',
    address: 'Jl. Perwira no.8 Kapangsari - Petarukan',
    phone: '082322872201',
    footer: 'Jazakumullah, Terima Kasih Sudah Belanja ditempat Kami.\nSemoga Rezekinya Lancar, Aamiin...',
    paperSize: '58'
  };

  let savedReceiptTemplate = null;
  try {
    savedReceiptTemplate = JSON.parse(localStorage.getItem('ar_pos_receipt_template') || 'null');
  } catch (e) {}

  if (!savedReceiptTemplate || savedReceiptTemplate.shopName === 'AR CELL & SERVICE' || savedReceiptTemplate.address?.includes('Jl. Raya Utama') || !savedReceiptTemplate.slogan) {
    savedReceiptTemplate = { ...DEFAULT_RECEIPT_TEMPLATE, ...(savedReceiptTemplate || {}) };
    savedReceiptTemplate.shopName = 'AR Cell';
    savedReceiptTemplate.slogan = 'Service HP , Kuota & Aksesoris';
    savedReceiptTemplate.address = 'Jl. Perwira no.8 Kapangsari - Petarukan';
    savedReceiptTemplate.phone = '082322872201';
    savedReceiptTemplate.footer = 'Jazakumullah, Terima Kasih Sudah Belanja ditempat Kami.\nSemoga Rezekinya Lancar, Aamiin...';
    localStorage.setItem('ar_pos_receipt_template', JSON.stringify(savedReceiptTemplate));
  }

  const state = {
    serverUrl: savedServerUrl,
    apiKey: localStorage.getItem('ar_pos_api_key') || DEFAULT_API_KEY,
    token: localStorage.getItem('ar_pos_token') || null,
    user: JSON.parse(localStorage.getItem('ar_pos_user') || 'null'),
    categories: [],
    products: [],
    filteredProducts: [],
    activeCategory: '',
    searchQuery: '',
    cart: [],
    selectedCustomer: { id: null, name: 'Pelanggan Umum', phone: '' },
    paymentMethod: 'cash',
    cashReceived: 0,
    bankAccounts: [],
    selectedBankId: null,
    activeTab: 'view-pos',
    lastTransaction: null,
    lowStockFilter: false,
    stockOpname: {
      canAdjust: false,
      pending: null,
      recent: [],
      items: {},
      filterSearch: '',
      filterDiffOnly: false
    },
    theme: localStorage.getItem('ar_pos_theme') || 'dark-gold',
    receiptTemplate: savedReceiptTemplate,
    bluetoothPrinter: {
      device: null,
      characteristic: null,
      connected: false
    },
    financialPeriod: 'today',
    historyPeriod: 'all'
  };

  // --- Utility Functions ---
  const formatRupiah = (number) => {
    return 'Rp ' + Number(number || 0).toLocaleString('id-ID');
  };

  const showToast = (message, duration = 2500) => {
    const toast = document.getElementById('toast-box');
    const toastText = document.getElementById('toast-text');
    if (!toast || !toastText) return;
    toastText.textContent = message;
    toast.classList.add('show');
    if (window.AndroidBridge && window.AndroidBridge.showToast) {
      window.AndroidBridge.showToast(message);
    }
    setTimeout(() => toast.classList.remove('show'), duration);
  };

  const vibrateTouch = (ms = 25) => {
    if (window.AndroidBridge && window.AndroidBridge.vibrate) {
      window.AndroidBridge.vibrate(ms);
    } else if (navigator.vibrate) {
      navigator.vibrate(ms);
    }
  };

  function showServerModal() {
    const modal = document.getElementById('server-modal');
    if (modal) {
      const input = document.getElementById('modal-server-url');
      if (input) input.value = state.serverUrl || DEFAULT_SERVER_URL;
      modal.classList.add('open');
    }
  }

  function hideServerModal() {
    const modal = document.getElementById('server-modal');
    if (modal) modal.classList.remove('open');
  }

  // --- API Client ---
  function getApiBaseUrl() {
    let base = (state.serverUrl || '').trim().replace(/\/+$/, '');
    if (!base) {
      base = DEFAULT_SERVER_URL;
    }
    if (!base.endsWith('/api')) {
      base += '/api';
    }
    return base;
  }

  async function apiRequest(endpoint, options = {}) {
    const cleanEndpoint = endpoint.startsWith('/') ? endpoint : '/' + endpoint;
    const url = getApiBaseUrl() + cleanEndpoint;
    const headers = {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-API-Key': state.apiKey,
      ...(options.headers || {})
    };

    if (state.token) {
      headers['Authorization'] = `Bearer ${state.token}`;
    }

    try {
      const response = await fetch(url, { ...options, headers });
      const data = await response.json().catch(() => null);

      if (response.status === 401 && !endpoint.includes('/auth/login')) {
        // Token expired or invalid
        logout();
        throw new Error('Sesi telah berakhir, silakan login kembali.');
      }

      if (!response.ok) {
        const errorMsg = data?.message || data?.error || `Error ${response.status}: ${response.statusText}`;
        throw new Error(errorMsg);
      }

      return data;
    } catch (err) {
      console.error('API Request Error:', url, err);
      if (err.message && (err.message.includes('Failed to fetch') || err.message.includes('NetworkError'))) {
        updateServerStatus(false);
        showServerModal();
      }
      throw err;
    }
  }

  // --- Authentication ---
  function checkAuth() {
    const loginModal = document.getElementById('login-modal');
    if (!state.token || !state.user) {
      loginModal.classList.add('open');
    } else {
      loginModal.classList.remove('open');
      updateUserInfo();
      loadInitialData();
    }
  }

  async function login(email, password) {
    showToast('Menghubungkan ke server...');
    try {
      const res = await apiRequest('/auth/login', {
        method: 'POST',
        body: JSON.stringify({ email, password, device_name: 'AR-POS-Mobile' })
      });

      if (res && res.data && res.data.token) {
        state.token = res.data.token;
        state.user = res.data.user;
        localStorage.setItem('ar_pos_token', state.token);
        localStorage.setItem('ar_pos_user', JSON.stringify(state.user));
        document.getElementById('login-modal').classList.remove('open');
        showToast(`Selamat datang, ${state.user.name}!`);
        updateUserInfo();
        loadInitialData();
      } else {
        throw new Error('Respon login tidak valid.');
      }
    } catch (err) {
      alert('Login Gagal: ' + err.message);
    }
  }

  function logout() {
    if (state.token) {
      apiRequest('/auth/logout', { method: 'POST' }).catch(() => {});
    }
    state.token = null;
    state.user = null;
    state.cart = [];
    localStorage.removeItem('ar_pos_token');
    localStorage.removeItem('ar_pos_user');
    updateCartUI();
    document.getElementById('login-modal').classList.add('open');
    showToast('Telah keluar.');
  }

  function updateUserInfo() {
    if (state.user) {
      const name = state.user.name || 'Kasir';
      const email = state.user.email || '-';
      const roles = state.user.roles ? state.user.roles.map(r => r.name).join(', ') : 'Kasir';

      const cashierNameEl = document.getElementById('cashier-name');
      if (cashierNameEl) cashierNameEl.textContent = name;

      const profileNameEl = document.getElementById('profile-user-name');
      if (profileNameEl) profileNameEl.textContent = name;

      const profileEmailEl = document.getElementById('profile-user-email');
      if (profileEmailEl) profileEmailEl.textContent = email;

      const profileRoleEl = document.getElementById('profile-user-role');
      if (profileRoleEl) profileRoleEl.textContent = roles;

      // Sidebar info
      const sbName = document.getElementById('sidebar-user-name');
      if (sbName) sbName.textContent = name;

      const sbEmail = document.getElementById('sidebar-user-email');
      if (sbEmail) sbEmail.textContent = email;

      const sbRole = document.getElementById('sidebar-user-role');
      if (sbRole) sbRole.textContent = roles;
    }
  }

  // --- Data Loading ---
  async function loadInitialData() {
    updateServerStatus(true);
    await Promise.allSettled([
      fetchCategories(),
      fetchProducts(),
      fetchTxOptions()
    ]);
  }

  function updateServerStatus(online) {
    const dot = document.getElementById('server-status-dot');
    const txt = document.getElementById('server-status-text');
    if (online) {
      dot.className = 'status-dot';
      txt.textContent = 'Online';
    } else {
      dot.className = 'status-dot offline';
      txt.textContent = 'Offline';
    }
  }

  async function fetchCategories() {
    try {
      const res = await apiRequest('/item-categories');
      if (res && res.data && res.data.categories) {
        state.categories = res.data.categories;
        renderCategoryBar();
      }
    } catch (err) {
      console.warn('Gagal memuat kategori:', err);
    }
  }

  async function fetchProducts() {
    const grid = document.getElementById('product-grid');
    grid.innerHTML = '<div style="grid-column: 1/-1; text-align:center; padding: 40px; color: var(--text-muted);">Memuat produk...</div>';

    try {
      const res = await apiRequest('/items?is_active=1&per_page=100');
      if (res && res.data && res.data.items) {
        state.products = res.data.items;
        applyProductFilter();
        renderInventoryList();
        updateServerStatus(true);
      }
    } catch (err) {
      updateServerStatus(false);
      grid.innerHTML = `<div style="grid-column: 1/-1; text-align:center; padding: 30px; color: var(--danger);">
        Gagal memuat produk.<br><small>${err.message}</small>
      </div>`;
    }
  }

  async function fetchTxOptions() {
    try {
      const res = await apiRequest('/transactions/options');
      if (res && res.data && res.data.bank_accounts) {
        state.bankAccounts = res.data.bank_accounts;
        renderBankSelect();
      }
    } catch (err) {
      console.warn('Gagal memuat opsi transaksi:', err);
    }
  }

  async function fetchHistory(period = state.historyPeriod) {
    state.historyPeriod = period;
    const container = document.getElementById('history-list');
    if (!container) return;
    container.innerHTML = '<div style="text-align:center; padding:30px; color:var(--text-muted);">Memuat riwayat...</div>';

    // Update filter pills UI
    document.querySelectorAll('.btn-history-filter').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.period === period);
    });

    let queryParams = '?per_page=50';
    const today = new Date().toISOString().split('T')[0];
    if (period === 'today') {
      queryParams += `&from=${today}&to=${today}`;
    } else if (period === 'this_month') {
      const now = new Date();
      const monthStart = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-01`;
      queryParams += `&from=${monthStart}&to=${today}`;
    }

    try {
      const timeoutPromise = new Promise((_, reject) => 
        setTimeout(() => reject(new Error('Koneksi timeout, server lambat merespon.')), 9000)
      );
      const res = await Promise.race([
        apiRequest(`/transactions${queryParams}`),
        timeoutPromise
      ]);

      const list = res?.data?.items || res?.data?.transactions || [];
      state.historyList = list;
      renderHistoryList(list);
    } catch (err) {
      console.warn('Gagal memuat riwayat:', err);
      container.innerHTML = `
        <div style="text-align:center; color:var(--danger); padding:24px;">
          <div style="margin-bottom:8px;">Gagal memuat riwayat: ${err.message}</div>
          <button class="btn-primary-action" id="btn-retry-history" style="width:auto; margin:0 auto; padding:8px 18px; font-size:0.82rem;">
            Coba Lagi
          </button>
        </div>
      `;
      const retryBtn = document.getElementById('btn-retry-history');
      if (retryBtn) retryBtn.addEventListener('click', () => fetchHistory(period));
    }
  }

  // --- Rendering UI ---
  function renderCategoryBar() {
    const bar = document.getElementById('category-bar');
    bar.innerHTML = '<button class="category-pill active" data-id="">Semua</button>';
    state.categories.forEach(cat => {
      const btn = document.createElement('button');
      btn.className = 'category-pill';
      btn.dataset.id = cat.id;
      btn.textContent = cat.name;
      bar.appendChild(btn);
    });
  }

  function applyProductFilter() {
    let list = [...state.products];

    if (state.activeCategory) {
      list = list.filter(p => String(p.category_id) === String(state.activeCategory));
    }

    if (state.searchQuery.trim()) {
      const q = state.searchQuery.toLowerCase();
      list = list.filter(p => 
        (p.name && p.name.toLowerCase().includes(q)) || 
        (p.code && p.code.toLowerCase().includes(q))
      );
    }

    state.filteredProducts = list;
    renderProductGrid();
  }

  function renderProductGrid() {
    const grid = document.getElementById('product-grid');
    if (state.filteredProducts.length === 0) {
      grid.innerHTML = '<div style="text-align:center; padding: 40px; color: var(--text-muted);">Tidak ada barang yang ditemukan.</div>';
      return;
    }

    grid.innerHTML = '';
    state.filteredProducts.forEach(item => {
      const row = document.createElement('div');
      row.className = 'product-row-item';
      
      const stock = Number(item.stock || 0);
      let stockClass = 'in-stock';
      let stockText = `Stok ${stock}`;
      if (stock <= 0) {
        stockClass = 'out-stock';
        stockText = 'Habis';
      } else if (stock < 5) {
        stockClass = 'low-stock';
        stockText = `Sisa ${stock}`;
      }

      const cartItem = state.cart.find(c => c.id === item.id);
      const inCartQty = cartItem ? cartItem.qty : 0;
      const inCartClass = inCartQty > 0 ? 'in-cart' : '';
      const badgeVisibleClass = inCartQty > 0 ? 'visible' : '';

      row.className = `product-row-item ${inCartClass}`;
      row.dataset.id = item.id;

      const imgHtml = item.photo_url 
        ? `<img src="${item.photo_url}" alt="${item.name}" loading="lazy" onerror="this.src='icon.png'">`
        : `<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="rgba(212,160,23,0.6)" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>`;

      row.innerHTML = `
        <div class="product-row-img-box">
          ${imgHtml}
        </div>
        <div class="product-row-info">
          <div class="product-row-name">${item.name}</div>
          <div class="product-row-meta">
            <span class="product-row-code">${item.code || '-'}</span>
            <span class="product-stock-tag ${stockClass}">${stockText}</span>
          </div>
        </div>
        <div class="product-row-qty-badge ${badgeVisibleClass}" id="product-qty-badge-${item.id}">
          <span class="product-qty-num">${inCartQty > 0 ? inCartQty : ''}</span>
        </div>
        <div class="product-row-action">
          <div class="product-row-price">${formatRupiah(item.selling_price || item.price)}</div>
          <button class="btn-add-row" title="Tambah ke keranjang">
            <span>+ Tambah</span>
          </button>
        </div>
      `;

      row.addEventListener('click', () => {
        addToCart(item);
      });

      grid.appendChild(row);
    });
  }

  function renderBankSelect() {
    const select = document.getElementById('checkout-bank-id');
    select.innerHTML = '<option value="">-- Pilih Rekening --</option>';
    state.bankAccounts.forEach(b => {
      const opt = document.createElement('option');
      opt.value = b.id;
      opt.textContent = `${b.bank_name} - ${b.account_number} (a.n ${b.account_name})`;
      select.appendChild(opt);
    });
  }

  // --- Cart Operations ---
  function addToCart(product) {
    vibrateTouch(30);

    const existingIndex = state.cart.findIndex(i => i.id === product.id);
    if (existingIndex > -1) {
      state.cart[existingIndex].qty += 1;
    } else {
      state.cart.push({
        id: product.id,
        name: product.name,
        code: product.code,
        price: Number(product.selling_price || product.price || 0),
        qty: 1,
        stock: Number(product.stock || 999)
      });
    }

    updateCartUI(product.id);
    showToast(`Ditambah: ${product.name}`);
  }

  function changeCartQty(index, delta) {
    vibrateTouch(20);
    const item = state.cart[index];
    const itemId = item ? item.id : null;
    state.cart[index].qty += delta;
    if (state.cart[index].qty <= 0) {
      state.cart.splice(index, 1);
    }
    updateCartUI(itemId);
  }

  function removeCartItem(index) {
    vibrateTouch(25);
    const item = state.cart[index];
    const itemId = item ? item.id : null;
    state.cart.splice(index, 1);
    updateCartUI(itemId);
  }

  function getCartTotal() {
    return state.cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
  }

  function getCartCount() {
    return state.cart.reduce((sum, item) => sum + item.qty, 0);
  }

  function updateProductRowQuantities(popProductId = null) {
    const rows = document.querySelectorAll('.product-row-item');
    rows.forEach(row => {
      const id = Number(row.dataset.id);
      if (!id) return;

      const cartItem = state.cart.find(c => c.id === id);
      const qty = cartItem ? cartItem.qty : 0;
      const badge = document.getElementById(`product-qty-badge-${id}`);

      if (qty > 0) {
        row.classList.add('in-cart');
        if (badge) {
          badge.classList.add('visible');
          badge.innerHTML = `<span class="product-qty-num">${qty}</span>`;
          if (popProductId === id) {
            badge.classList.remove('pop');
            void badge.offsetWidth;
            badge.classList.add('pop');
          }
        }
      } else {
        row.classList.remove('in-cart');
        if (badge) {
          badge.classList.remove('visible');
          badge.innerHTML = '';
        }
      }
    });
  }

  function updateCartUI(popProductId = null) {
    const count = getCartCount();
    const total = getCartTotal();

    // Update individual product row badges in Kasir grid
    updateProductRowQuantities(popProductId);

    // Floating Cart Bar
    const floatingBar = document.getElementById('floating-cart-bar');
    const cartCountBadge = document.getElementById('cart-count');
    const cartTotalBar = document.getElementById('cart-total-bar');

    cartCountBadge.textContent = count;
    cartTotalBar.textContent = formatRupiah(total);

    if (count > 0 && state.activeTab === 'view-pos') {
      floatingBar.classList.add('visible');
    } else {
      floatingBar.classList.remove('visible');
    }

    // Cart Modal List
    const container = document.getElementById('cart-items-container');
    const subtotalDisplay = document.getElementById('bill-subtotal');
    const totalDisplay = document.getElementById('bill-total');

    subtotalDisplay.textContent = formatRupiah(total);
    totalDisplay.textContent = formatRupiah(total);

    if (state.cart.length === 0) {
      container.innerHTML = '<div style="text-align:center; padding:30px; color:var(--text-muted);">Keranjang masih kosong.</div>';
      return;
    }

    container.innerHTML = '';
    state.cart.forEach((item, idx) => {
      const row = document.createElement('div');
      row.className = 'cart-item-row';
      row.innerHTML = `
        <div class="cart-item-info">
          <div class="cart-item-title">${item.name}</div>
          <div class="cart-item-price">${formatRupiah(item.price)} × ${item.qty} = ${formatRupiah(item.price * item.qty)}</div>
        </div>
        <div class="stepper">
          <button class="stepper-btn btn-minus" data-idx="${idx}">−</button>
          <span class="stepper-val">${item.qty}</span>
          <button class="stepper-btn btn-plus" data-idx="${idx}">+</button>
        </div>
        <button class="icon-btn btn-del" data-idx="${idx}" style="color:var(--danger); width:32px; height:32px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
        </button>
      `;
      container.appendChild(row);
    });

    container.querySelectorAll('.btn-minus').forEach(b => {
      b.addEventListener('click', () => changeCartQty(Number(b.dataset.idx), -1));
    });
    container.querySelectorAll('.btn-plus').forEach(b => {
      b.addEventListener('click', () => changeCartQty(Number(b.dataset.idx), 1));
    });
    container.querySelectorAll('.btn-del').forEach(b => {
      b.addEventListener('click', () => removeCartItem(Number(b.dataset.idx)));
    });
  }

  // --- Checkout & Payment ---
  function openCheckoutModal() {
    if (state.cart.length === 0) {
      showToast('Keranjang masih kosong!');
      return;
    }
    const total = getCartTotal();
    document.getElementById('checkout-total-display').textContent = formatRupiah(total);
    state.cashReceived = total;
    document.getElementById('cash-received-input').value = total;
    updateChangeDisplay();

    document.getElementById('cart-sheet-modal').classList.remove('open');
    document.getElementById('checkout-modal').classList.add('open');
  }

  function updateChangeDisplay() {
    const total = getCartTotal();
    const received = Number(document.getElementById('cash-received-input').value) || 0;
    const change = Math.max(0, received - total);
    document.getElementById('cash-change-display').textContent = formatRupiah(change);
  }

  async function processCheckout() {
    const total = getCartTotal();
    const received = Number(document.getElementById('cash-received-input').value) || 0;

    if (state.paymentMethod === 'cash' && received < total) {
      showToast('Nominal uang diterima kurang dari total belanja!');
      return;
    }

    const customerMode = (state.selectedCustomer && state.selectedCustomer.id) ? 'existing' : 'umum';
    const amountPaid = state.paymentMethod === 'cash' ? received : total;

    const payload = {
      customer_mode: customerMode,
      customer_id: customerMode === 'existing' ? state.selectedCustomer.id : null,
      payment_method: state.paymentMethod,
      type: 'sale',
      notes: 'Penjualan via AR POS Mobile',
      amount_paid: amountPaid,
      paid_amount: amountPaid,
      bank_account_id: state.paymentMethod === 'transfer' ? document.getElementById('checkout-bank-id').value : null,
      items: state.cart.map(item => ({
        item_id: item.id,
        quantity: item.qty,
        unit_price: item.price
      }))
    };

    const submitBtn = document.getElementById('btn-submit-order');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Memproses...';

    try {
      const res = await apiRequest('/transactions', {
        method: 'POST',
        body: JSON.stringify(payload)
      });

      const tx = res?.data?.transaction || {
        invoice_no: 'TRX-' + Date.now(),
        total_amount: total,
        paid_amount: amountPaid,
        payment_method: state.paymentMethod,
        created_at: new Date().toISOString()
      };

      state.lastTransaction = {
        ...tx,
        items: [...state.cart],
        change: Math.max(0, received - total),
        cashier: state.user?.name || 'Kasir',
        customer: state.selectedCustomer?.name || 'Pelanggan Umum'
      };

      document.getElementById('checkout-modal').classList.remove('open');
      openReceiptModal(state.lastTransaction);

      // Reset cart & customer to default Pelanggan Umum
      state.cart = [];
      state.selectedCustomer = { id: null, name: 'Pelanggan Umum', phone: '' };
      const custLabel = document.getElementById('selected-customer-label');
      if (custLabel) custLabel.textContent = 'Pelanggan Umum';
      updateCartUI();
      fetchProducts(); // Refresh stock
    } catch (err) {
      alert('Gagal menyelesaikan transaksi: ' + err.message);
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = 'SELESAIKAN TRANSAKSI';
    }
  }

  // --- Receipt Modal & Printing ---
  function openReceiptModal(tx) {
    const tpl = state.receiptTemplate || {};
    const headerTitleEl = document.getElementById('rec-header-title');
    const headerSloganEl = document.getElementById('rec-header-slogan');
    const headerAddressEl = document.getElementById('rec-header-address');
    const headerPhoneEl = document.getElementById('rec-header-phone');
    const footerTextEl = document.getElementById('rec-footer-text');
    const barcodeCodeEl = document.getElementById('rec-barcode-code');

    if (headerTitleEl) headerTitleEl.textContent = tpl.shopName || 'AR Cell';
    if (headerSloganEl) {
      headerSloganEl.textContent = tpl.slogan || 'Service HP , Kuota & Aksesoris';
      headerSloganEl.style.display = tpl.slogan ? 'block' : 'none';
    }
    if (headerAddressEl) headerAddressEl.textContent = tpl.address || '';
    if (headerPhoneEl) headerPhoneEl.textContent = tpl.phone ? `Telp / WA: ${tpl.phone}` : '';
    if (footerTextEl) footerTextEl.textContent = tpl.footer || 'Jazakumullah, Terima Kasih Sudah Belanja ditempat Kami.\nSemoga Rezekinya Lancar, Aamiin...';

    const invoiceNo = tx.invoice_no || tx.code || tx.transaction_no || ('TRX-' + Date.now());
    document.getElementById('rec-invoice-no').textContent = invoiceNo;
    if (barcodeCodeEl) barcodeCodeEl.textContent = invoiceNo;

    document.getElementById('rec-date').textContent = new Date().toLocaleString('id-ID');
    document.getElementById('rec-cashier').textContent = tx.cashier || state.user?.name || 'Kasir';
    document.getElementById('rec-customer').textContent = tx.customer || 'Pelanggan Umum';
    document.getElementById('rec-total').textContent = formatRupiah(tx.total_amount || getCartTotal());
    document.getElementById('rec-paid').textContent = formatRupiah(tx.paid_amount);
    document.getElementById('rec-change').textContent = formatRupiah(tx.change || 0);
    document.getElementById('rec-method').textContent = tx.payment_method?.toUpperCase() || 'TUNAI';

    const itemsContainer = document.getElementById('rec-items-list');
    itemsContainer.innerHTML = '';
    const items = tx.items || [];
    items.forEach(it => {
      const row = document.createElement('div');
      row.className = 'receipt-item-row';
      const name = it.name || it.item?.name || 'Barang';
      const qty = it.qty || it.quantity || 1;
      const price = it.price || it.unit_price || 0;
      const subtotal = price * qty;

      row.innerHTML = `
        <div class="receipt-item-top">
          <span>${name}</span>
          <span>${formatRupiah(subtotal)}</span>
        </div>
        <div class="receipt-item-sub">
          <span>${qty} x ${formatRupiah(price)}</span>
        </div>
      `;
      itemsContainer.appendChild(row);
    });

    document.getElementById('receipt-modal').classList.add('open');
  }

  async function printReceipt() {
    if (state.bluetoothPrinter.connected && state.bluetoothPrinter.characteristic && state.lastTransaction) {
      await printThermalReceipt(state.lastTransaction);
      return;
    }

    if (window.AndroidBridge && window.AndroidBridge.printHtml) {
      const html = document.getElementById('printable-receipt').outerHTML;
      window.AndroidBridge.printHtml(html);
    } else {
      window.print();
    }
  }

  function shareReceiptWhatsApp() {
    if (!state.lastTransaction) return;
    const tx = state.lastTransaction;
    const tpl = state.receiptTemplate || {};
    let text = `*${tpl.shopName || 'AR Cell'}*\n`;
    if (tpl.slogan) text += `_${tpl.slogan}_\n`;
    if (tpl.address) text += `${tpl.address}\n`;
    if (tpl.phone) text += `Telp/WA: ${tpl.phone}\n`;
    text += `================================\n`;
    text += `No. Faktur : ${tx.invoice_no || tx.code || '-'}\n`;
    text += `Tanggal    : ${new Date().toLocaleString('id-ID')}\n`;
    text += `Kasir      : ${tx.cashier || state.user?.name || 'Kasir'}\n`;
    text += `Pelanggan  : ${tx.customer || 'Pelanggan Umum'}\n`;
    text += `--------------------------------\n`;
    (tx.items || []).forEach(it => {
      const name = it.name || it.item?.name || 'Barang';
      const qty = it.qty || it.quantity || 1;
      const price = it.price || it.unit_price || 0;
      text += `*${name}*\n`;
      text += `  ${qty} x ${formatRupiah(price)} = ${formatRupiah(qty * price)}\n`;
    });
    text += `--------------------------------\n`;
    text += `*TOTAL     : ${formatRupiah(tx.total_amount)}*\n`;
    text += `Bayar (${(tx.payment_method || 'TUNAI').toUpperCase()}) : ${formatRupiah(tx.paid_amount)}\n`;
    text += `Kembali    : ${formatRupiah(tx.change || 0)}\n`;
    text += `================================\n`;
    text += `${tpl.footer || 'Jazakumullah, Terima Kasih Sudah Belanja ditempat Kami.\nSemoga Rezekinya Lancar, Aamiin...'}\n`;

    const url = `https://wa.me/?text=${encodeURIComponent(text)}`;
    window.open(url, '_blank');
  }

  // --- History Rendering ---
  function renderHistoryList(transactions) {
    const container = document.getElementById('history-list');
    if (!transactions || transactions.length === 0) {
      container.innerHTML = '<div style="text-align:center; padding:30px; color:var(--text-muted);">Belum ada riwayat transaksi.</div>';
      return;
    }

    container.innerHTML = '';
    transactions.forEach(t => {
      const card = document.createElement('div');
      card.className = 'ticket-card';
      const invoiceNo = t.transaction_no || t.invoice_no || t.code || 'TRX';
      const custName = t.customer?.name || t.customer_name || 'Umum';
      const totalAmount = t.total ?? t.total_amount ?? 0;
      const paymentMethod = t.payment_label || t.payment_method || 'Tunai';
      const isCancelled = t.status === 'cancelled';
      const statusText = t.status_label || (isCancelled ? 'Batal' : 'Selesai');
      const statusStyle = isCancelled 
        ? 'background:var(--danger-bg); color:var(--danger);' 
        : 'background:var(--success-bg); color:var(--success);';

      const itemsList = t.items || [];
      const itemsCount = itemsList.length > 0 ? itemsList.length : (t.items_count || 1);

      card.innerHTML = `
        <div class="ticket-header">
          <span class="ticket-no">${invoiceNo}</span>
          <span class="ticket-status" style="${statusStyle}">${statusText}</span>
        </div>
        <div class="ticket-device">${custName}</div>
        <div class="ticket-complaint">${itemsCount} item barang • ${paymentMethod}</div>
        <div class="ticket-footer">
          <span>${t.created_at ? new Date(t.created_at).toLocaleString('id-ID') : '-'}</span>
          <strong style="color:var(--primary-light);">${formatRupiah(totalAmount)}</strong>
        </div>
      `;
      card.addEventListener('click', () => {
        const mappedItems = itemsList.map(it => ({
          name: it.item_name || it.name || 'Barang',
          qty: it.quantity || it.qty || 1,
          price: it.unit_price || it.price || 0
        }));

        state.lastTransaction = {
          invoice_no: invoiceNo,
          cashier: t.user?.name || 'Kasir',
          customer: custName,
          total_amount: totalAmount,
          paid_amount: t.cash_received || t.paid_amount || totalAmount,
          change: t.cash_change || 0,
          payment_method: paymentMethod,
          items: mappedItems
        };
        openReceiptModal(state.lastTransaction);
      });
      container.appendChild(card);
    });
  }

  function renderInventoryList() {
    const container = document.getElementById('inventory-list');
    let items = [...state.products];
    if (state.lowStockFilter) {
      items = items.filter(i => Number(i.stock || 0) < 5);
    }

    if (items.length === 0) {
      container.innerHTML = '<div style="text-align:center; padding:30px; color:var(--text-muted);">Tidak ada barang yang cocok.</div>';
      return;
    }

    container.innerHTML = '';
    items.forEach(it => {
      const row = document.createElement('div');
      row.className = 'cart-item-row';
      const stock = Number(it.stock || 0);
      const stockColor = stock <= 0 ? 'var(--danger)' : (stock < 5 ? 'var(--warning)' : 'var(--success)');

      row.innerHTML = `
        <div class="cart-item-info">
          <div class="cart-item-title">${it.name}</div>
          <div style="font-size:0.75rem; color:var(--text-muted);">${it.code || ''} • ${it.category?.name || 'Umum'}</div>
        </div>
        <div style="text-align:right;">
          <div style="font-size:0.85rem; font-weight:700; color:var(--primary-light);">${formatRupiah(it.selling_price || it.price)}</div>
          <div style="font-size:0.75rem; font-weight:600; color:${stockColor};">Stok: ${stock}</div>
        </div>
      `;
      container.appendChild(row);
    });
  }

  // --- Stock Opname ---
  async function fetchStockOpname() {
    const itemsListContainer = document.getElementById('opname-items-list');
    if (itemsListContainer) {
      itemsListContainer.innerHTML = '<div style="text-align:center; padding:30px; color:var(--text-muted);">Memuat data stok opname...</div>';
    }

    try {
      const res = await apiRequest('/stock-opnames');
      if (res && res.data) {
        state.stockOpname.canAdjust = !!res.data.can_adjust;
        state.stockOpname.pending = res.data.pending_opname;
        state.stockOpname.recent = res.data.recent_opnames || [];

        // Pre-fill state.stockOpname.items from pending draft if available
        if (state.stockOpname.pending && Array.isArray(state.stockOpname.pending.items)) {
          state.stockOpname.pending.items.forEach(it => {
            state.stockOpname.items[it.id] = {
              physicalStock: it.physical_stock,
              notes: it.notes || ''
            };
          });
        }

        renderOpnamePendingCard();
        renderOpnameItemsList();
        renderOpnameRecentHistory();
        updateOpnameCountedBadge();
      }
    } catch (err) {
      console.error('Gagal mengambil stock opname:', err);
      if (itemsListContainer) {
        itemsListContainer.innerHTML = `<div style="text-align:center; padding:30px; color:var(--danger);">Gagal memuat data: ${err.message}</div>`;
      }
    }
  }

  function updateOpnameCountedBadge() {
    const badge = document.getElementById('opname-counted-count');
    if (!badge) return;
    const count = Object.keys(state.stockOpname.items).length;
    badge.textContent = `${count} diisi`;
  }

  function renderOpnamePendingCard() {
    const container = document.getElementById('opname-pending-container');
    if (!container) return;

    const pending = state.stockOpname.pending;
    if (!pending) {
      container.style.display = 'none';
      container.innerHTML = '';
      return;
    }

    container.style.display = 'block';

    const canAdjust = state.stockOpname.canAdjust;
    const lossUnits = pending.total_loss_units || 0;
    const lossCost = pending.total_loss_cost || 0;
    const gainUnits = pending.total_gain_units || 0;
    const gainCost = pending.total_gain_cost || 0;
    const totalItems = pending.total_items || (pending.items ? pending.items.length : 0);
    const dateFormatted = pending.created_at ? new Date(pending.created_at).toLocaleString('id-ID') : '-';

    let diffItemsHtml = '';
    if (pending.items && pending.items.length > 0) {
      const diffList = pending.items.filter(it => it.difference !== 0);
      if (diffList.length > 0) {
        diffItemsHtml = `
          <div style="margin-top:10px; font-size:0.75rem; background:rgba(0,0,0,0.25); border-radius:var(--radius-sm); padding:8px 10px;">
            <div style="font-weight:700; color:var(--text-secondary); margin-bottom:6px;">Daftar Barang Selisih (${diffList.length}):</div>
            ${diffList.slice(0, 10).map(it => {
              const diffText = it.difference > 0 ? `+${it.difference}` : `${it.difference}`;
              const diffColor = it.difference > 0 ? 'var(--success)' : 'var(--danger)';
              return `
                <div style="display:flex; justify-content:space-between; padding:3px 0; border-bottom:1px solid rgba(255,255,255,0.05);">
                  <span style="color:var(--text-main);">${it.name}</span>
                  <span style="font-weight:700; color:${diffColor};">Fisik: ${it.physical_stock} (${diffText})</span>
                </div>
              `;
            }).join('')}
            ${diffList.length > 10 ? `<div style="text-align:center; color:var(--text-muted); padding-top:4px;">...dan ${diffList.length - 10} barang lainnya</div>` : ''}
          </div>
        `;
      }
    }

    let ownerActionHtml = '';
    if (canAdjust) {
      ownerActionHtml = `
        <div style="margin-top:14px; padding-top:12px; border-top:1px solid var(--border-subtle); display:flex; flex-direction:column; gap:8px;">
          <div style="font-size:0.82rem; font-weight:700; color:var(--primary-light); display:flex; align-items:center; gap:6px;">
            👑 Aksi Persetujuan Owner
          </div>
          <p style="font-size:0.72rem; color:var(--text-muted); margin-bottom:4px;">
            Sebagai Owner / Super Admin, Anda dapat menyetujui penyesuaian stok ini untuk langsung memperbarui stok di sistem.
          </p>
          <div style="display:flex; gap:8px;">
            <button class="btn-primary-action" id="btn-owner-approve-opname" style="flex:1.5; margin-top:0; background:#10B981; color:#fff; font-weight:700;">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
              Setujui Stok
            </button>
            <button class="btn-primary-action" id="btn-owner-reject-opname" style="flex:1; margin-top:0; background:transparent; border:1px solid var(--danger); color:var(--danger); font-weight:700;">
              ✕ Tolak
            </button>
          </div>
        </div>
      `;
    } else {
      ownerActionHtml = `
        <div style="margin-top:12px; padding:10px; background:rgba(245,158,11,0.1); border:1px dashed var(--warning); border-radius:var(--radius-sm); font-size:0.75rem; color:#fcd34d;">
          ⏳ <strong>Menunggu Persetujuan Owner:</strong> Hasil stok opname telah diajukan ke Owner. Menunggu disetujui untuk penyesuaian stok.
        </div>
      `;
    }

    container.innerHTML = `
      <div class="opname-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
          <span class="opname-status-badge pending">⏳ MENUNGGU PERSETUJUAN OWNER</span>
          <span style="font-size:0.72rem; color:var(--text-muted);">${pending.opname_number}</span>
        </div>
        <div style="font-size:0.82rem; color:var(--text-secondary); margin-bottom:6px;">
          Dibuat oleh: <strong style="color:var(--primary-light);">${pending.creator}</strong> • ${dateFormatted}
        </div>
        ${pending.notes ? `<div style="font-size:0.75rem; color:var(--text-muted); font-style:italic;">Catatan: "${pending.notes}"</div>` : ''}

        <div class="opname-metrics-grid">
          <div class="opname-metric-box">
            <div class="label">Barang Dicek</div>
            <div class="val">${totalItems}</div>
          </div>
          <div class="opname-metric-box">
            <div class="label">Kurang/Hilang</div>
            <div class="val loss">-${lossUnits}</div>
            <div style="font-size:0.62rem; color:var(--danger);">${formatRupiah(lossCost)}</div>
          </div>
          <div class="opname-metric-box">
            <div class="label">Lebih/Temuan</div>
            <div class="val gain">+${gainUnits}</div>
            <div style="font-size:0.62rem; color:var(--success);">${formatRupiah(gainCost)}</div>
          </div>
        </div>

        ${diffItemsHtml}
        ${ownerActionHtml}
      </div>
    `;

    // Bind owner actions
    if (canAdjust) {
      const btnApprove = document.getElementById('btn-owner-approve-opname');
      const btnReject = document.getElementById('btn-owner-reject-opname');
      if (btnApprove) {
        btnApprove.addEventListener('click', () => {
          approveStockOpname();
        });
      }
      if (btnReject) {
        btnReject.addEventListener('click', () => {
          rejectStockOpname(pending.id);
        });
      }
    }
  }

  function renderOpnameItemsList() {
    const container = document.getElementById('opname-items-list');
    if (!container) return;

    let items = [...state.products];
    const search = (state.stockOpname.filterSearch || '').toLowerCase().trim();
    if (search) {
      items = items.filter(i => 
        (i.name && i.name.toLowerCase().includes(search)) ||
        (i.code && i.code.toLowerCase().includes(search))
      );
    }

    if (state.stockOpname.filterDiffOnly) {
      items = items.filter(i => {
        const entry = state.stockOpname.items[i.id];
        if (!entry || entry.physicalStock === undefined || entry.physicalStock === '') return false;
        return Number(entry.physicalStock) !== Number(i.stock || 0);
      });
    }

    if (items.length === 0) {
      container.innerHTML = '<div style="text-align:center; padding:30px; color:var(--text-muted);">Tidak ada barang yang cocok.</div>';
      return;
    }

    container.innerHTML = '';
    items.forEach(it => {
      const row = document.createElement('div');
      const sysStock = Number(it.stock || 0);
      const savedEntry = state.stockOpname.items[it.id];
      const hasCount = savedEntry && savedEntry.physicalStock !== undefined && savedEntry.physicalStock !== '';
      const physStock = hasCount ? Number(savedEntry.physicalStock) : '';
      const diff = hasCount ? (physStock - sysStock) : 0;
      const notes = savedEntry ? (savedEntry.notes || '') : '';

      const hasDiff = hasCount && diff !== 0;
      row.className = `opname-item-row ${hasDiff ? 'has-diff' : ''}`;

      let diffPillClass = 'zero';
      let diffPillText = 'Sama';
      if (hasCount) {
        if (diff < 0) {
          diffPillClass = 'loss';
          diffPillText = `${diff}`;
        } else if (diff > 0) {
          diffPillClass = 'gain';
          diffPillText = `+${diff}`;
        }
      } else {
        diffPillText = '-';
      }

      row.innerHTML = `
        <div class="opname-item-header">
          <div>
            <div class="opname-item-name">${it.name}</div>
            <div class="opname-item-code">${it.code || '-'} • HPP: ${formatRupiah(it.purchase_price || 0)}</div>
          </div>
          <button type="button" class="btn-sync-stock btn-sync-single" data-id="${it.id}" data-sys="${sysStock}" title="Samakan fisik dengan stok sistem">
            = Sistem (${sysStock})
          </button>
        </div>

        <div class="opname-inputs-row">
          <div class="opname-stock-pill">
            Sistem: <strong>${sysStock}</strong>
          </div>
          <div class="opname-phys-input-wrap">
            <input type="number" min="0" step="1" class="opname-phys-input opname-input-field" 
              data-id="${it.id}" 
              data-sys="${sysStock}" 
              placeholder="Fisik..." 
              value="${physStock !== '' ? physStock : ''}">
          </div>
          <div class="opname-diff-pill ${diffPillClass}" id="diff-pill-${it.id}">
            ${diffPillText}
          </div>
        </div>

        <input type="text" class="opname-note-input opname-notes-field" 
          data-id="${it.id}" 
          placeholder="Catatan jika ada selisih (misal: barang rusak, terselip)..." 
          value="${notes}">
      `;

      container.appendChild(row);
    });

    // Attach row events
    container.querySelectorAll('.opname-input-field').forEach(inp => {
      inp.addEventListener('input', (e) => {
        const id = e.target.dataset.id;
        const sys = Number(e.target.dataset.sys || 0);
        const val = e.target.value.trim();
        const diffPill = document.getElementById(`diff-pill-${id}`);
        const parentRow = e.target.closest('.opname-item-row');

        if (val === '') {
          delete state.stockOpname.items[id];
          if (diffPill) {
            diffPill.className = 'opname-diff-pill zero';
            diffPill.textContent = '-';
          }
          if (parentRow) parentRow.classList.remove('has-diff');
        } else {
          const phys = Number(val);
          const diff = phys - sys;
          if (!state.stockOpname.items[id]) {
            state.stockOpname.items[id] = { physicalStock: phys, notes: '' };
          } else {
            state.stockOpname.items[id].physicalStock = phys;
          }

          if (diffPill) {
            if (diff < 0) {
              diffPill.className = 'opname-diff-pill loss';
              diffPill.textContent = `${diff}`;
            } else if (diff > 0) {
              diffPill.className = 'opname-diff-pill gain';
              diffPill.textContent = `+${diff}`;
            } else {
              diffPill.className = 'opname-diff-pill zero';
              diffPill.textContent = 'Sama';
            }
          }

          if (parentRow) {
            parentRow.classList.toggle('has-diff', diff !== 0);
          }
        }
        updateOpnameCountedBadge();
      });
    });

    container.querySelectorAll('.opname-notes-field').forEach(inp => {
      inp.addEventListener('change', (e) => {
        const id = e.target.dataset.id;
        const notes = e.target.value.trim();
        if (state.stockOpname.items[id]) {
          state.stockOpname.items[id].notes = notes;
        }
      });
    });

    container.querySelectorAll('.btn-sync-single').forEach(btn => {
      btn.addEventListener('click', () => {
        vibrateTouch(10);
        const id = btn.dataset.id;
        const sys = Number(btn.dataset.sys || 0);
        const inputField = container.querySelector(`.opname-input-field[data-id="${id}"]`);
        if (inputField) {
          inputField.value = sys;
          inputField.dispatchEvent(new Event('input', { bubbles: true }));
        }
      });
    });
  }

  function renderOpnameRecentHistory() {
    const container = document.getElementById('opname-recent-list');
    if (!container) return;

    const recent = state.stockOpname.recent || [];
    if (recent.length === 0) {
      container.innerHTML = '<div style="text-align:center; padding:16px; color:var(--text-muted); font-size:0.75rem;">Belum ada riwayat opname yang selesai.</div>';
      return;
    }

    container.innerHTML = '';
    recent.forEach(op => {
      const card = document.createElement('div');
      card.className = 'cart-item-row';
      const isApproved = op.status === 'approved';
      const statusBadge = isApproved 
        ? '<span class="opname-status-badge approved">Disetujui Owner</span>' 
        : '<span class="opname-status-badge rejected">Ditolak</span>';
      
      const dateStr = op.approved_at 
        ? new Date(op.approved_at).toLocaleDateString('id-ID') 
        : (op.created_at ? new Date(op.created_at).toLocaleDateString('id-ID') : '-');

      card.innerHTML = `
        <div class="cart-item-info">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
            <strong style="font-size:0.85rem; color:var(--text-main);">${op.opname_number}</strong>
            ${statusBadge}
          </div>
          <div style="font-size:0.75rem; color:var(--text-muted);">
            Oleh: ${op.creator} • Owner: ${op.approver || '-'} • ${dateStr}
          </div>
          <div style="font-size:0.75rem; color:var(--text-secondary); margin-top:2px;">
            ${op.total_items} barang • Kurang: -${op.total_loss_units} • Lebih: +${op.total_gain_units}
          </div>
        </div>
      `;
      container.appendChild(card);
    });
  }

  async function saveStockOpnameDraft() {
    const countedEntries = Object.entries(state.stockOpname.items);
    if (countedEntries.length === 0) {
      showToast('Silakan isi kolom fisik minimal 1 barang terlebih dahulu.');
      return;
    }

    const payloadItems = countedEntries.map(([id, entry]) => ({
      id: parseInt(id),
      physical_stock: parseInt(entry.physicalStock),
      notes: entry.notes || null
    }));

    showToast('Menyimpan dan mengajukan ke Owner...');
    try {
      const res = await apiRequest('/stock-opnames/draft', {
        method: 'POST',
        body: JSON.stringify({
          items: payloadItems,
          general_notes: 'Stok Opname dari Mobile POS'
        })
      });

      showToast(res.message || 'Stok opname berhasil diajukan ke Owner!');
      await fetchStockOpname();
    } catch (err) {
      showToast('Gagal menyimpan opname: ' + err.message);
    }
  }

  async function approveStockOpname() {
    if (!state.stockOpname.pending) {
      showToast('Tidak ada pengajuan opname yang aktif.');
      return;
    }

    const confirmApprove = confirm('Konfirmasi persetujuan Owner:\n\nSetujui hasil stok opname ini? Stok di database toko akan langsung disesuaikan secara otomatis.');
    if (!confirmApprove) return;

    showToast('Menyetujui & menyesuaikan stok toko...');
    try {
      const pendingItems = state.stockOpname.pending.items || [];
      const payloadItems = pendingItems.map(it => ({
        id: it.id,
        physical_stock: it.physical_stock,
        notes: it.notes || null
      }));

      const res = await apiRequest('/stock-opnames/approve', {
        method: 'POST',
        body: JSON.stringify({
          stock_opname_id: state.stockOpname.pending.id,
          items: payloadItems
        })
      });

      showToast(res.message || 'Stok opname disetujui! Stok toko berhasil diperbarui.');
      state.stockOpname.items = {};
      await Promise.allSettled([
        fetchProducts(),
        fetchStockOpname()
      ]);
    } catch (err) {
      showToast('Gagal menyetujui opname: ' + err.message);
    }
  }

  async function rejectStockOpname(id) {
    const confirmReject = confirm('Konfirmasi penolakan:\n\nTolak pengajuan stok opname ini?');
    if (!confirmReject) return;

    showToast('Menolak pengajuan...');
    try {
      const res = await apiRequest(`/stock-opnames/${id}/reject`, {
        method: 'POST'
      });
      showToast(res.message || 'Pengajuan stok opname telah ditolak.');
      await fetchStockOpname();
    } catch (err) {
      showToast('Gagal menolak opname: ' + err.message);
    }
  }

  // --- Bluetooth Thermal Printer & ESC/POS ---
  function updatePrinterStatus(connected, deviceName = '') {
    const badge = document.getElementById('printer-status-badge');
    const nameEl = document.getElementById('printer-device-name');
    if (!badge || !nameEl) return;

    if (connected) {
      badge.className = 'printer-badge connected';
      badge.textContent = 'Terhubung';
      nameEl.textContent = deviceName || 'Printer Bluetooth';
    } else {
      badge.className = 'printer-badge disconnected';
      badge.textContent = 'Terputus';
      nameEl.textContent = 'Belum Terhubung';
    }
  }

  async function connectBluetoothPrinter() {
    if (!navigator.bluetooth) {
      const isHttps = window.location.protocol === 'https:' || window.location.hostname === 'localhost';
      const msg = 'Browser ini belum mendukung Web Bluetooth secara langsung.\n\n' +
        'Penyebab umum & solusi:\n' +
        '1. Jika membuka dari WhatsApp/chat: Ketuk titik tiga (⋮) di pojok kanan atas > pilih "Buka di Chrome".\n' +
        '2. Pastikan menggunakan aplikasi Google Chrome resmi di Android.\n' +
        '3. Pastikan Bluetooth dan Lokasi (GPS) HP sudah AKTIF.\n' +
        (!isHttps ? '4. Pastikan alamat diawali https:// (keamanan browser mewajibkan HTTPS).\n\n' : '\n') +
        'Tips: Anda tetap bisa mencetak struk langsung menggunakan tombol "Cetak Struk" pada nota transaksi tanpa perlu menyambungkan Bluetooth di sini.';
      alert(msg);
      return;
    }
    showToast('Mencari printer Bluetooth...');
    try {
      const device = await navigator.bluetooth.requestDevice({
        acceptAllDevices: true,
        optionalServices: [
          '000018f0-0000-1000-8000-00805f9b34fb',
          'e7810a71-73ae-499d-8c15-faa9aef0c3f2',
          '0000ffe0-0000-1000-8000-00805f9b34fb',
          '49535343-fe7d-4ae5-8fa9-9fafd205e455'
        ]
      });

      const server = await device.gatt.connect();
      state.bluetoothPrinter.device = device;
      state.bluetoothPrinter.connected = true;

      device.addEventListener('gattserverdisconnected', () => {
        state.bluetoothPrinter.connected = false;
        state.bluetoothPrinter.characteristic = null;
        updatePrinterStatus(false);
        showToast('Koneksi printer terputus.');
      });

      try {
        const services = await server.getPrimaryServices();
        for (const s of services) {
          const chars = await s.getCharacteristics();
          for (const c of chars) {
            if (c.properties.write || c.properties.writeWithoutResponse) {
              state.bluetoothPrinter.characteristic = c;
              break;
            }
          }
          if (state.bluetoothPrinter.characteristic) break;
        }
      } catch (err) {
        console.warn('Gagal membaca karakteristik GATT:', err);
      }

      updatePrinterStatus(true, device.name || 'Printer Bluetooth');
      showToast(`Printer terhubung: ${device.name || 'Bluetooth'}`);
    } catch (err) {
      console.warn('Bluetooth connect cancelled:', err);
      if (!err.message?.includes('User cancelled')) {
        alert('Gagal menyambung printer: ' + err.message);
      }
    }
  }

  function generateEscPosBytes(tx) {
    const encoder = new TextEncoder();
    const tpl = state.receiptTemplate || {};
    const is80mm = tpl.paperSize === '80';
    const lineWidth = is80mm ? 48 : 32;

    const ESC = 0x1B;
    const GS = 0x1D;

    const buffer = [];
    const push = (...bytes) => buffer.push(...bytes);
    const pushText = (str) => {
      const b = encoder.encode(str + '\n');
      b.forEach(x => buffer.push(x));
    };

    // Initialize printer
    push(ESC, 0x40);

    // Center header
    push(ESC, 0x61, 1);
    push(ESC, 0x45, 1); // bold on
    pushText(tpl.shopName || 'AR Cell');
    push(ESC, 0x45, 0); // bold off
    if (tpl.slogan) pushText(tpl.slogan);
    if (tpl.address) pushText(tpl.address);
    if (tpl.phone) pushText('Telp/WA: ' + tpl.phone);
    pushText('='.repeat(lineWidth));

    // Left align meta
    push(ESC, 0x61, 0);
    pushText(`No.Nota: ${tx.invoice_no || tx.code || '-'}`);
    pushText(`Waktu  : ${new Date().toLocaleString('id-ID')}`);
    pushText(`Kasir  : ${tx.cashier || state.user?.name || 'Kasir'}`);
    pushText(`Cust   : ${tx.customer || 'Pelanggan Umum'}`);
    pushText('-'.repeat(lineWidth));

    // Items
    (tx.items || []).forEach(it => {
      const name = (it.name || it.item?.name || 'Barang').substring(0, lineWidth);
      pushText(name);
      const qtyPrice = ` ${it.qty || it.quantity} x ${formatRupiah(it.price || it.unit_price)}`;
      const subtotal = formatRupiah((it.price || it.unit_price) * (it.qty || it.quantity));
      const spaces = Math.max(1, lineWidth - qtyPrice.length - subtotal.length);
      pushText(qtyPrice + ' '.repeat(spaces) + subtotal);
    });

    pushText('='.repeat(lineWidth));

    // Totals
    push(ESC, 0x45, 1);
    const totalLabel = 'TOTAL';
    const totalVal = formatRupiah(tx.total_amount);
    const totalSpaces = Math.max(1, lineWidth - totalLabel.length - totalVal.length);
    pushText(totalLabel + ' '.repeat(totalSpaces) + totalVal);
    push(ESC, 0x45, 0);

    const payLabel = `BAYAR (${(tx.payment_method || 'TUNAI').toUpperCase()})`;
    const payVal = formatRupiah(tx.paid_amount);
    const paySpaces = Math.max(1, lineWidth - payLabel.length - payVal.length);
    pushText(payLabel + ' '.repeat(paySpaces) + payVal);

    const changeLabel = 'KEMBALI';
    const changeVal = formatRupiah(tx.change || 0);
    const changeSpaces = Math.max(1, lineWidth - changeLabel.length - changeVal.length);
    pushText(changeLabel + ' '.repeat(changeSpaces) + changeVal);
    pushText('='.repeat(lineWidth));

    // Footer
    push(ESC, 0x61, 1);
    const footerLines = (tpl.footer || 'Jazakumullah, Terima Kasih Sudah Belanja ditempat Kami.\nSemoga Rezekinya Lancar, Aamiin...').split('\n');
    footerLines.forEach(l => pushText(l));
    push(ESC, 0x61, 0);

    // Feed lines & Cut
    push(ESC, 0x64, 4);
    push(GS, 0x56, 66, 0);

    return new Uint8Array(buffer);
  }

  async function printThermalReceipt(tx) {
    if (state.bluetoothPrinter.connected && state.bluetoothPrinter.characteristic) {
      try {
        showToast('Mengirim struk ke printer Bluetooth...');
        const bytes = generateEscPosBytes(tx);
        for (let i = 0; i < bytes.length; i += 512) {
          const chunk = bytes.slice(i, i + 512);
          await state.bluetoothPrinter.characteristic.writeValue(chunk);
        }
        showToast('Struk berhasil dicetak!');
        return;
      } catch (err) {
        console.warn('Bluetooth direct print error:', err);
      }
    }
    window.print();
  }

  // --- Theme Management ---
  function applyTheme(themeName) {
    state.theme = themeName;
    localStorage.setItem('ar_pos_theme', themeName);
    if (themeName === 'dark-gold') {
      document.documentElement.removeAttribute('data-theme');
    } else {
      document.documentElement.setAttribute('data-theme', themeName);
    }
    document.querySelectorAll('.theme-pill-card').forEach(card => {
      card.classList.toggle('active', card.dataset.theme === themeName);
    });
  }

  // --- Financial Report ---
  async function fetchFinancialReport(period = state.financialPeriod) {
    state.financialPeriod = period;
    const container = document.getElementById('financial-report-content');
    if (!container) return;

    container.innerHTML = '<div style="text-align:center; padding:30px; color:var(--text-muted);">Memuat laporan keuangan...</div>';

    document.querySelectorAll('.btn-fin-period').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.period === period);
    });

    const now = new Date();
    const today = now.toISOString().split('T')[0];
    let query = '';

    if (period === 'today') {
      query = `?from=${today}&to=${today}`;
    } else if (period === '7days') {
      const past7 = new Date(Date.now() - 6 * 24 * 60 * 60 * 1000);
      const fromStr = past7.toISOString().split('T')[0];
      query = `?from=${fromStr}&to=${today}`;
    } else if (period === 'this_month') {
      const monthStart = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-01`;
      query = `?from=${monthStart}&to=${today}`;
    } else if (period === 'last_month') {
      const lastMonthStart = new Date(now.getFullYear(), now.getMonth() - 1, 1);
      const lastMonthEnd = new Date(now.getFullYear(), now.getMonth(), 0);
      const fromStr = `${lastMonthStart.getFullYear()}-${String(lastMonthStart.getMonth() + 1).padStart(2, '0')}-01`;
      const toStr = `${lastMonthEnd.getFullYear()}-${String(lastMonthEnd.getMonth() + 1).padStart(2, '0')}-${String(lastMonthEnd.getDate()).padStart(2, '0')}`;
      query = `?from=${fromStr}&to=${toStr}`;
    }

    try {
      const timeoutPromise = new Promise((_, reject) => 
        setTimeout(() => reject(new Error('Koneksi timeout, server lambat merespon.')), 9000)
      );
      const res = await Promise.race([
        apiRequest(`/financial-reports${query}`),
        timeoutPromise
      ]);

      if (res && res.data) {
        renderFinancialReport(res.data);
      } else {
        container.innerHTML = '<div style="text-align:center; padding:30px; color:var(--text-muted);">Data tidak tersedia.</div>';
      }
    } catch (err) {
      console.error('Gagal mengambil laporan keuangan:', err);
      container.innerHTML = `
        <div style="text-align:center; padding:24px; color:var(--danger);">
          <div style="margin-bottom:8px;">Gagal memuat laporan: ${err.message}</div>
          <button class="btn-primary-action" id="btn-retry-finance" style="width:auto; margin:0 auto; padding:8px 18px; font-size:0.82rem;">Coba Lagi</button>
        </div>
      `;
      const retryBtn = document.getElementById('btn-retry-finance');
      if (retryBtn) retryBtn.addEventListener('click', () => fetchFinancialReport(period));
    }
  }

  function renderFinancialReport(data) {
    const container = document.getElementById('financial-report-content');
    if (!container) return;

    const netProfit = data.profit?.owner_net_estimate || 0;
    const cashFlowEstimate = data.profit?.cash_flow_estimate ?? 0;
    const totalInflow = data.totals?.inflow || 0;
    const operatingOutflow = data.totals?.operating_outflow || 0;
    const manualIncome = data.manual_income?.amount || 0;
    const manualExpense = data.manual_expense?.amount || 0;
    const purchasesExpense = data.purchases?.expense || 0;
    const totalOutflow = operatingOutflow + manualExpense + purchasesExpense;

    const sales = data.sales || {};
    const itemsRevenue = sales.items_revenue || 0;
    const itemsCost = sales.items_cost || 0;
    const itemsMargin = sales.items_margin || 0;

    container.innerHTML = `
      <!-- LABA BERSIH & SISA KAS GRID -->
      <div class="finance-hero-grid">
        <div class="finance-hero-card">
          <div class="finance-hero-label">LABA BERSIH</div>
          <div class="finance-hero-amount" style="color:var(--primary-light);">${formatRupiah(netProfit)}</div>
        </div>
        <div class="finance-hero-card">
          <div class="finance-hero-label">SISA KAS</div>
          <div class="finance-hero-amount" style="color:var(--success);">${formatRupiah(cashFlowEstimate)}</div>
        </div>
      </div>
      <div style="font-size:0.75rem; color:var(--text-muted); text-align:center; margin-top:-6px; margin-bottom:12px;">
        Periode: ${data.period?.from || '-'} s/d ${data.period?.to || '-'}
      </div>

      <!-- RINGKASAN ARUS KAS -->
      <div class="finance-breakdown-card">
        <div class="header-title">
          <span>💰 Ringkasan Arus Kas</span>
        </div>
        <div class="finance-line-item">
          <span class="label">Total Pemasukan</span>
          <span class="amount" style="color:var(--success); font-weight:700;">+${formatRupiah(totalInflow)}</span>
        </div>
        <div class="finance-line-item">
          <span class="label">Total Pengeluaran</span>
          <span class="amount" style="color:var(--danger); font-weight:700;">-${formatRupiah(totalOutflow)}</span>
        </div>
        <div class="finance-line-item" style="border-top:1px dashed var(--border-gold); padding-top:10px; margin-top:4px;">
          <span class="label" style="font-weight:700; color:var(--text-main);">Estimasi Sisa Kas</span>
          <span class="amount" style="color:var(--primary-light); font-weight:900; font-size:0.95rem;">${formatRupiah(cashFlowEstimate)}</span>
        </div>
      </div>

      <!-- PENJUALAN -->
      <div class="finance-breakdown-card">
        <div class="header-title">
          <span>📦 Penjualan</span>
          <span style="font-size:0.75rem; color:var(--text-muted);">${sales.transaction_count || 0} Transaksi</span>
        </div>
        <div class="finance-line-item">
          <span class="label">Total Omset Barang</span>
          <span class="amount">${formatRupiah(itemsRevenue)}</span>
        </div>
        <div class="finance-line-item">
          <span class="label">Total Modal Barang (HPP)</span>
          <span class="amount" style="color:var(--text-muted);">${formatRupiah(itemsCost)}</span>
        </div>
        <div class="finance-line-item">
          <span class="label">Laba Kotor Barang (Margin)</span>
          <span class="amount" style="color:var(--success); font-weight:800;">+${formatRupiah(itemsMargin)}</span>
        </div>
      </div>

      <!-- KAS OPERASIONAL -->
      <div class="finance-breakdown-card">
        <div class="header-title">
          <span>💵 Kas Operasional</span>
        </div>
        <div class="finance-line-item">
          <span class="label">Pemasukan Manual</span>
          <span class="amount" style="color:var(--success);">${formatRupiah(manualIncome)}</span>
        </div>
        <div class="finance-line-item">
          <span class="label">Pengeluaran Manual</span>
          <span class="amount" style="color:var(--danger);">${formatRupiah(manualExpense)}</span>
        </div>
      </div>
    `;
  }

  // --- Navigation & Event Listeners ---
  function setupEvents() {
    function switchTab(target) {
      state.activeTab = target;

      // Update bottom nav
      document.querySelectorAll('.nav-item').forEach(i => {
        i.classList.toggle('active', i.dataset.target === target);
      });

      // Update sidebar nav
      document.querySelectorAll('.sidebar-nav-item').forEach(i => {
        i.classList.toggle('active', i.dataset.target === target);
      });

      // Show view
      document.querySelectorAll('.app-view').forEach(v => v.style.display = 'none');
      const targetEl = document.getElementById(target);
      if (targetEl) targetEl.style.display = 'block';

      if (target === 'view-pos') {
        if (getCartCount() > 0) document.getElementById('floating-cart-bar').classList.add('visible');
      } else {
        document.getElementById('floating-cart-bar').classList.remove('visible');
      }

      if (target === 'view-history') fetchHistory(state.historyPeriod);
      if (target === 'view-inventory') renderInventoryList();
      if (target === 'view-stock-opname') fetchStockOpname();
      if (target === 'view-financial-report') fetchFinancialReport(state.financialPeriod);
    }

    // Nav Items (Bottom)
    document.querySelectorAll('.nav-item').forEach(item => {
      item.addEventListener('click', () => {
        vibrateTouch(15);
        switchTab(item.dataset.target);
      });
    });

    // Sidebar triggers & events
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    const btnToggleSidebar = document.getElementById('btn-toggle-sidebar');
    const btnCloseSidebar = document.getElementById('btn-close-sidebar');

    function openSidebar() {
      vibrateTouch(15);
      if (sidebarOverlay) sidebarOverlay.classList.add('open');
    }

    function closeSidebar() {
      if (sidebarOverlay) sidebarOverlay.classList.remove('open');
    }

    window.openSidebar = openSidebar;
    window.closeSidebar = closeSidebar;

    if (btnToggleSidebar) btnToggleSidebar.addEventListener('click', openSidebar);
    if (btnCloseSidebar) btnCloseSidebar.addEventListener('click', closeSidebar);
    if (sidebarOverlay) {
      sidebarOverlay.addEventListener('click', (e) => {
        if (e.target === sidebarOverlay) closeSidebar();
      });
    }

    // Sidebar nav items
    document.querySelectorAll('.sidebar-nav-item').forEach(item => {
      item.addEventListener('click', () => {
        vibrateTouch(15);
        closeSidebar();
        if (item.id === 'sidebar-item-server') {
          showServerModal();
        } else if (item.dataset.target) {
          switchTab(item.dataset.target);
        }
      });
    });

    const sidebarBtnLogout = document.getElementById('sidebar-btn-logout');
    if (sidebarBtnLogout) {
      sidebarBtnLogout.addEventListener('click', () => {
        closeSidebar();
        logout();
      });
    }

    // Category click
    document.getElementById('category-bar').addEventListener('click', (e) => {
      if (e.target.classList.contains('category-pill')) {
        vibrateTouch(15);
        document.querySelectorAll('.category-pill').forEach(p => p.classList.remove('active'));
        e.target.classList.add('active');
        state.activeCategory = e.target.dataset.id;
        applyProductFilter();
      }
    });

    // Search input
    const searchInput = document.getElementById('pos-search');
    const clearBtn = document.getElementById('btn-clear-search');
    searchInput.addEventListener('input', (e) => {
      state.searchQuery = e.target.value;
      clearBtn.style.display = state.searchQuery ? 'flex' : 'none';
      applyProductFilter();
    });

    clearBtn.addEventListener('click', () => {
      searchInput.value = '';
      state.searchQuery = '';
      clearBtn.style.display = 'none';
      applyProductFilter();
    });

    // History search input
    const historySearch = document.getElementById('history-search');
    if (historySearch) {
      historySearch.addEventListener('input', (e) => {
        const q = e.target.value.toLowerCase().trim();
        if (!state.historyList) return;
        const filtered = state.historyList.filter(t => {
          const no = (t.transaction_no || t.invoice_no || '').toLowerCase();
          const cust = (t.customer?.name || t.customer_name || '').toLowerCase();
          return no.includes(q) || cust.includes(q);
        });
        renderHistoryList(filtered);
      });
    }

    // Refresh button
    document.getElementById('btn-refresh').addEventListener('click', () => {
      vibrateTouch(20);
      showToast('Memuat ulang data...');
      loadInitialData();
    });

    // Cart Open/Close
    document.getElementById('btn-open-cart').addEventListener('click', () => {
      vibrateTouch(20);
      document.getElementById('cart-sheet-modal').classList.add('open');
    });
    document.getElementById('btn-close-cart').addEventListener('click', () => {
      document.getElementById('cart-sheet-modal').classList.remove('open');
    });

    // Checkout Open/Close
    document.getElementById('btn-goto-checkout').addEventListener('click', openCheckoutModal);
    document.getElementById('btn-close-checkout').addEventListener('click', () => {
      document.getElementById('checkout-modal').classList.remove('open');
    });

    // Quick cash buttons
    document.querySelectorAll('.btn-quick-cash').forEach(b => {
      b.addEventListener('click', () => {
        vibrateTouch(15);
        if (b.id === 'btn-exact-cash') {
          document.getElementById('cash-received-input').value = getCartTotal();
        } else {
          document.getElementById('cash-received-input').value = b.dataset.cash;
        }
        updateChangeDisplay();
      });
    });

    document.getElementById('cash-received-input').addEventListener('input', updateChangeDisplay);

    // Payment method selector
    document.querySelectorAll('.method-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        vibrateTouch(15);
        document.querySelectorAll('.method-btn').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        state.paymentMethod = btn.dataset.method;
        document.getElementById('bank-select-wrapper').style.display = 
          state.paymentMethod === 'transfer' ? 'block' : 'none';
      });
    });

    // Submit Order
    document.getElementById('btn-submit-order').addEventListener('click', processCheckout);

    // Receipt Close & Actions
    document.getElementById('btn-close-receipt').addEventListener('click', () => {
      document.getElementById('receipt-modal').classList.remove('open');
    });
    document.getElementById('btn-new-order').addEventListener('click', () => {
      document.getElementById('receipt-modal').classList.remove('open');
    });
    document.getElementById('btn-print-receipt').addEventListener('click', printReceipt);
    document.getElementById('btn-whatsapp-receipt').addEventListener('click', shareReceiptWhatsApp);

    // Login submit
    document.getElementById('form-login').addEventListener('submit', (e) => {
      e.preventDefault();
      const email = document.getElementById('login-email').value;
      const pass = document.getElementById('login-password').value;
      login(email, pass);
    });

    // Logout
    document.getElementById('btn-logout').addEventListener('click', logout);

    // Theme selector
    document.querySelectorAll('.theme-pill-card').forEach(card => {
      card.addEventListener('click', () => {
        vibrateTouch(15);
        applyTheme(card.dataset.theme);
        showToast(`Tema diterapkan: ${card.querySelector('.theme-pill-label')?.textContent || card.dataset.theme}`);
      });
    });
    applyTheme(state.theme);

    // Bluetooth Printer
    const btnConnectBt = document.getElementById('btn-connect-bluetooth');
    if (btnConnectBt) {
      btnConnectBt.addEventListener('click', () => {
        vibrateTouch(15);
        connectBluetoothPrinter();
      });
    }

    const btnTestPrint = document.getElementById('btn-test-print');
    if (btnTestPrint) {
      btnTestPrint.addEventListener('click', async () => {
        vibrateTouch(15);
        const sampleTx = {
          invoice_no: 'TES-STRUK',
          cashier: state.user?.name || 'Kasir AR POS',
          customer: 'Pelanggan Tes',
          payment_method: 'TUNAI',
          total_amount: 50000,
          paid_amount: 50000,
          change: 0,
          items: [
            { name: 'Tes Cetak Printer Bluetooth', qty: 1, price: 50000 }
          ]
        };
        await printThermalReceipt(sampleTx);
      });
    }

    const paperSizeSel = document.getElementById('setting-paper-size');
    if (paperSizeSel) {
      paperSizeSel.value = state.receiptTemplate.paperSize || '58';
      paperSizeSel.addEventListener('change', (e) => {
        state.receiptTemplate.paperSize = e.target.value;
        localStorage.setItem('ar_pos_receipt_template', JSON.stringify(state.receiptTemplate));
        showToast(`Lebar kertas: ${e.target.value}mm`);
      });
    }

    // Receipt Template
    const shopNameInp = document.getElementById('setting-receipt-shop-name');
    const shopSloganInp = document.getElementById('setting-receipt-slogan');
    const shopAddressInp = document.getElementById('setting-receipt-address');
    const shopPhoneInp = document.getElementById('setting-receipt-phone');
    const shopFooterInp = document.getElementById('setting-receipt-footer');
    const btnSaveReceipt = document.getElementById('btn-save-receipt-template');

    if (shopNameInp) shopNameInp.value = state.receiptTemplate.shopName || '';
    if (shopSloganInp) shopSloganInp.value = state.receiptTemplate.slogan || '';
    if (shopAddressInp) shopAddressInp.value = state.receiptTemplate.address || '';
    if (shopPhoneInp) shopPhoneInp.value = state.receiptTemplate.phone || '';
    if (shopFooterInp) shopFooterInp.value = state.receiptTemplate.footer || '';

    if (btnSaveReceipt) {
      btnSaveReceipt.addEventListener('click', () => {
        vibrateTouch(20);
        state.receiptTemplate.shopName = shopNameInp?.value.trim() || 'AR Cell';
        state.receiptTemplate.slogan = shopSloganInp?.value.trim() || 'Service HP , Kuota & Aksesoris';
        state.receiptTemplate.address = shopAddressInp?.value.trim() || '';
        state.receiptTemplate.phone = shopPhoneInp?.value.trim() || '';
        state.receiptTemplate.footer = shopFooterInp?.value.trim() || 'Jazakumullah, Terima Kasih Sudah Belanja ditempat Kami.\nSemoga Rezekinya Lancar, Aamiin...';
        localStorage.setItem('ar_pos_receipt_template', JSON.stringify(state.receiptTemplate));
        showToast('Template struk berhasil disimpan!');
      });
    }

    // Financial Report Controls
    document.querySelectorAll('.btn-fin-period').forEach(btn => {
      btn.addEventListener('click', () => {
        vibrateTouch(15);
        fetchFinancialReport(btn.dataset.period);
      });
    });

    const btnRefreshFin = document.getElementById('btn-refresh-financial');
    if (btnRefreshFin) {
      btnRefreshFin.addEventListener('click', () => {
        vibrateTouch(15);
        showToast('Memuat ulang laporan keuangan...');
        fetchFinancialReport(state.financialPeriod);
      });
    }

    // History Filters & Refresh
    document.querySelectorAll('.btn-history-filter').forEach(btn => {
      btn.addEventListener('click', () => {
        vibrateTouch(15);
        fetchHistory(btn.dataset.period);
      });
    });

    const btnRefreshHist = document.getElementById('btn-refresh-history');
    if (btnRefreshHist) {
      btnRefreshHist.addEventListener('click', () => {
        vibrateTouch(15);
        showToast('Memuat ulang riwayat...');
        fetchHistory(state.historyPeriod);
      });
    }

    // Optional Settings if present
    const settingServerUrl = document.getElementById('setting-server-url');
    const settingApiKey = document.getElementById('setting-api-key');
    const btnSaveSettings = document.getElementById('btn-save-settings');
    if (settingServerUrl) settingServerUrl.value = state.serverUrl;
    if (settingApiKey) settingApiKey.value = state.apiKey;
    if (btnSaveSettings) {
      btnSaveSettings.addEventListener('click', async () => {
        const url = settingServerUrl.value.trim();
        const key = settingApiKey.value.trim();
        state.serverUrl = url;
        state.apiKey = key;
        localStorage.setItem('ar_pos_server_url', url);
        localStorage.setItem('ar_pos_api_key', key);
        showToast('Menguji koneksi server...');
        try {
          await apiRequest('/settings');
          updateServerStatus(true);
          showToast('Koneksi server sukses & setelan tersimpan!');
        } catch (err) {
          updateServerStatus(false);
          alert('Gagal terhubung ke server: ' + err.message);
        }
      });
    }

    // Modal server config triggers
    document.getElementById('link-config-server').addEventListener('click', (e) => {
      e.preventDefault();
      showServerModal();
    });

    document.getElementById('btn-close-server-modal').addEventListener('click', hideServerModal);

    document.querySelectorAll('.btn-preset-ip').forEach(btn => {
      btn.addEventListener('click', () => {
        document.getElementById('modal-server-url').value = btn.dataset.url;
      });
    });

    document.getElementById('btn-save-server-modal').addEventListener('click', async () => {
      const url = document.getElementById('modal-server-url').value.trim();
      if (!url) return;
      state.serverUrl = url;
      localStorage.setItem('ar_pos_server_url', url);
      document.getElementById('setting-server-url').value = url;
      showToast('Menguji koneksi server...');
      try {
        await apiRequest('/auth/login', { method: 'POST', body: JSON.stringify({}) }).catch(e => {
          // 422 validation error means server is REACHABLE and API is responding!
          if (e.message && (e.message.includes('422') || e.message.includes('email'))) {
            return { reachable: true };
          }
          throw e;
        });
        updateServerStatus(true);
        hideServerModal();
        showToast('Koneksi server BERHASIL!');
        if (state.token) loadInitialData();
      } catch (err) {
        updateServerStatus(false);
        alert('Gagal terhubung ke server (' + url + '):\n' + err.message + '\n\nPastikan port dan alamat IP benar.');
      }
    });

    // Low stock filter
    document.getElementById('filter-low-stock').addEventListener('change', (e) => {
      state.lowStockFilter = e.target.checked;
      renderInventoryList();
    });

    // Stock Opname Controls
    const btnRefreshOpname = document.getElementById('btn-refresh-opname');
    if (btnRefreshOpname) {
      btnRefreshOpname.addEventListener('click', () => {
        vibrateTouch(15);
        showToast('Memuat ulang data opname...');
        fetchStockOpname();
      });
    }

    const opnameSearch = document.getElementById('opname-search');
    if (opnameSearch) {
      opnameSearch.addEventListener('input', (e) => {
        state.stockOpname.filterSearch = e.target.value;
        renderOpnameItemsList();
      });
    }

    const filterOpnameDiff = document.getElementById('filter-opname-diff');
    if (filterOpnameDiff) {
      filterOpnameDiff.addEventListener('change', (e) => {
        state.stockOpname.filterDiffOnly = e.target.checked;
        renderOpnameItemsList();
      });
    }

    const btnCopyAllSys = document.getElementById('btn-copy-all-system-stock');
    if (btnCopyAllSys) {
      btnCopyAllSys.addEventListener('click', () => {
        vibrateTouch(20);
        state.products.forEach(p => {
          const sys = Number(p.stock || 0);
          state.stockOpname.items[p.id] = {
            physicalStock: sys,
            notes: state.stockOpname.items[p.id]?.notes || ''
          };
        });
        renderOpnameItemsList();
        updateOpnameCountedBadge();
        showToast('Semua fisik barang disamakan dengan sistem.');
      });
    }

    const btnSaveOpnameDraft = document.getElementById('btn-save-opname-draft');
    if (btnSaveOpnameDraft) {
      btnSaveOpnameDraft.addEventListener('click', () => {
        vibrateTouch(20);
        saveStockOpnameDraft();
      });
    }
  }

  // --- Initialize Service Worker (PWA) ---
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('./sw.js').catch((err) => {
        console.warn('SW registration failed:', err);
      });
    });
  }

  // --- App Startup ---
  window.addEventListener('DOMContentLoaded', () => {
    setupEvents();
    checkAuth();
  });

})();
