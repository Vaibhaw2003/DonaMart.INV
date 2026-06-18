/**
 * SmartINV — Global Application JavaScript
 * Smart Inventory & Billing Management System
 */

'use strict';

// -------------------------------------------------------
// Dark Mode
// -------------------------------------------------------
const DarkMode = {
  key: 'smartinv_theme',
  init() {
    const saved = localStorage.getItem(this.key) || 'light';
    this.apply(saved);
    document.getElementById('darkModeToggle')?.addEventListener('click', () => this.toggle());
  },
  apply(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    document.documentElement.setAttribute('data-bs-theme', theme);
    const icon = document.getElementById('darkModeIcon');
    if (icon) {
      icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
    }
    localStorage.setItem(this.key, theme);
    // Set cookie for PHP synchronization (lasts 1 year)
    document.cookie = this.key + "=" + theme + ";path=/;max-age=31536000;SameSite=Strict";
  },
  toggle() {
    const current = document.documentElement.getAttribute('data-theme') || 'light';
    this.apply(current === 'dark' ? 'light' : 'dark');
  }
};

// -------------------------------------------------------
// Sidebar Toggle
// -------------------------------------------------------
const Sidebar = {
  init() {
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar   = document.getElementById('appSidebar');
    const overlay   = document.getElementById('sidebarOverlay');

    toggleBtn?.addEventListener('click', () => this.toggle());
    overlay?.addEventListener('click', () => this.close());
  },
  toggle() {
    const sidebar  = document.getElementById('appSidebar');
    const overlay  = document.getElementById('sidebarOverlay');
    sidebar?.classList.toggle('open');
    overlay?.classList.toggle('active');
  },
  close() {
    document.getElementById('appSidebar')?.classList.remove('open');
    document.getElementById('sidebarOverlay')?.classList.remove('active');
  }
};

// -------------------------------------------------------
// AJAX Helper
// -------------------------------------------------------
const Ajax = {
  post(url, data, onSuccess, onError) {
    const formData = (data instanceof FormData) ? data : (() => {
      const fd = new FormData();
      Object.entries(data).forEach(([k, v]) => fd.append(k, v));
      return fd;
    })();

    fetch(url, { method: 'POST', body: formData })
      .then(r => r.json())
      .then(json => {
        if (json.success) {
          onSuccess && onSuccess(json);
        } else {
          if (onError) onError(json);
          else SmartINV.toast(json.message || 'An error occurred.', 'error');
        }
      })
      .catch(err => {
        SmartINV.toast('Network error. Please try again.', 'error');
        console.error(err);
      });
  },
  get(url, onSuccess) {
    fetch(url)
      .then(r => r.json())
      .then(json => onSuccess && onSuccess(json))
      .catch(err => console.error(err));
  }
};

// -------------------------------------------------------
// SweetAlert2 Helpers
// -------------------------------------------------------
const SmartINV = {
  toast(msg, icon = 'success') {
    if (typeof Swal === 'undefined') { alert(msg); return; }
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon,
      title: msg,
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
    });
  },
  confirm(title, text, icon = 'warning') {
    return Swal.fire({
      title,
      text,
      icon,
      showCancelButton: true,
      confirmButtonColor: '#2563eb',
      cancelButtonColor:  '#6b7280',
      confirmButtonText: 'Yes, proceed!',
      cancelButtonText: 'Cancel',
    });
  },
  loading(title = 'Processing...') {
    Swal.fire({
      title,
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    });
  },
  close() { Swal.close(); }
};

// -------------------------------------------------------
// DataTables Default Init
// -------------------------------------------------------
function initDataTable(selector, extraOpts = {}) {
  const el = document.querySelector(selector);
  if (!el || !$.fn?.DataTable) return;
  return $(selector).DataTable(Object.assign({
    responsive: true,
    pageLength: 10,
    language: {
      search: '',
      searchPlaceholder: 'Search...',
      lengthMenu: 'Show _MENU_ entries',
      emptyTable: 'No data available',
      zeroRecords: 'No matching records found',
    },
    dom: "<'row align-items-center mb-3'<'col-sm-6'l><'col-sm-6 text-end'f>>rtip",
  }, extraOpts));
}

// -------------------------------------------------------
// Image Preview on file input
// -------------------------------------------------------
function initImagePreview(inputId, previewId) {
  const input   = document.getElementById(inputId);
  const preview = document.getElementById(previewId);
  if (!input || !preview) return;
  input.addEventListener('change', () => {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
    reader.readAsDataURL(file);
  });
}

// -------------------------------------------------------
// Sales Invoice Builder
// -------------------------------------------------------
const InvoiceBuilder = {
  rowIndex: 0,
  gstRate: 18,

  addRow(productId = '', productName = '', price = 0, qty = 1) {
    const idx = this.rowIndex++;
    const tbody = document.getElementById('invoiceItems');
    if (!tbody) return;

    const tr = document.createElement('tr');
    tr.id = `item-row-${idx}`;
    tr.innerHTML = `
      <td>
        <select class="form-select form-select-sm product-select" name="items[${idx}][product_id]" required onchange="InvoiceBuilder.onProductChange(this, ${idx})">
          <option value="">Select product...</option>
        </select>
      </td>
      <td><input type="number" class="form-control form-control-sm item-qty" name="items[${idx}][qty]" value="${qty}" min="1" onchange="InvoiceBuilder.recalcRow(${idx})" /></td>
      <td><input type="number" class="form-control form-control-sm item-price" name="items[${idx}][price]" value="${price}" step="0.01" onchange="InvoiceBuilder.recalcRow(${idx})" /></td>
      <td><input type="number" class="form-control form-control-sm item-discount" name="items[${idx}][discount]" value="0" step="0.01" onchange="InvoiceBuilder.recalcRow(${idx})" /></td>
      <td class="item-total fw-600">0.00</td>
      <td><button type="button" class="btn btn-icon btn-outline-danger btn-sm" onclick="InvoiceBuilder.removeRow(${idx})"><i class="bi bi-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
    this.loadProducts(idx, productId);
    this.recalcRow(idx);
  },

  loadProducts(idx, selectedId = '') {
    const sel = document.querySelector(`#item-row-${idx} .product-select`);
    if (!sel) return;
    Ajax.get('../ajax/get_products.php', data => {
      data.products?.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id;
        opt.textContent = `${p.name} (Stock: ${p.stock})`;
        opt.dataset.price = p.selling_price;
        opt.dataset.stock = p.stock;
        if (p.id == selectedId) opt.selected = true;
        sel.appendChild(opt);
      });
    });
  },

  onProductChange(sel, idx) {
    const opt = sel.options[sel.selectedIndex];
    const priceInput = document.querySelector(`#item-row-${idx} .item-price`);
    if (priceInput && opt.dataset.price) priceInput.value = parseFloat(opt.dataset.price).toFixed(2);
    this.recalcRow(idx);
  },

  recalcRow(idx) {
    const row  = document.getElementById(`item-row-${idx}`);
    if (!row) return;
    const qty  = parseFloat(row.querySelector('.item-qty')?.value  || 1);
    const price= parseFloat(row.querySelector('.item-price')?.value || 0);
    const disc = parseFloat(row.querySelector('.item-discount')?.value || 0);
    const total = Math.max(0, qty * price - disc);
    const cell = row.querySelector('.item-total');
    if (cell) cell.textContent = total.toFixed(2);
    this.recalcTotals();
  },

  removeRow(idx) {
    document.getElementById(`item-row-${idx}`)?.remove();
    this.recalcTotals();
  },

  recalcTotals() {
    let subtotal = 0;
    document.querySelectorAll('.item-total').forEach(el => {
      subtotal += parseFloat(el.textContent || 0);
    });
    const discountVal = parseFloat(document.getElementById('globalDiscount')?.value || 0);
    const gstRate  = parseFloat(document.getElementById('gstRate')?.value || this.gstRate);
    const afterDisc = Math.max(0, subtotal - discountVal);
    const gstAmt   = afterDisc * (gstRate / 100);
    const grand    = afterDisc + gstAmt;

    const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val.toFixed(2); };
    set('subtotalDisplay', subtotal);
    set('discountDisplay', discountVal);
    set('gstDisplay', gstAmt);
    set('grandTotalDisplay', grand);

    const gstIn = document.getElementById('gstAmount');
    const grIn  = document.getElementById('grandTotalInput');
    const subIn = document.getElementById('subtotalInput');
    if (gstIn) gstIn.value = gstAmt.toFixed(2);
    if (grIn)  grIn.value  = grand.toFixed(2);
    if (subIn) subIn.value = subtotal.toFixed(2);
  }
};

// -------------------------------------------------------
// Purchase Builder (similar pattern)
// -------------------------------------------------------
const PurchaseBuilder = {
  rowIndex: 0,

  addRow() {
    const idx   = this.rowIndex++;
    const tbody = document.getElementById('purchaseItems');
    if (!tbody) return;

    const tr = document.createElement('tr');
    tr.id = `pitem-row-${idx}`;
    tr.innerHTML = `
      <td>
        <select class="form-select form-select-sm" name="items[${idx}][product_id]" required>
          <option value="">Select product...</option>
        </select>
      </td>
      <td><input type="number" class="form-control form-control-sm" name="items[${idx}][qty]" value="1" min="1" onchange="PurchaseBuilder.recalc()" /></td>
      <td><input type="number" class="form-control form-control-sm" name="items[${idx}][price]" value="0" step="0.01" onchange="PurchaseBuilder.recalc()" /></td>
      <td class="pitem-total fw-600">0.00</td>
      <td><button type="button" class="btn btn-icon btn-outline-danger btn-sm" onclick="PurchaseBuilder.removeRow(${idx})"><i class="bi bi-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
    this.loadProducts(idx);
  },

  loadProducts(idx) {
    const sel = document.querySelector(`#pitem-row-${idx} select`);
    if (!sel) return;
    Ajax.get('../ajax/get_products.php', data => {
      data.products?.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id;
        opt.textContent = `${p.name} (Stock: ${p.stock})`;
        opt.dataset.price = p.purchase_price;
        sel.appendChild(opt);
      });
      sel.addEventListener('change', () => {
        const opt = sel.options[sel.selectedIndex];
        const priceInput = sel.closest('tr').querySelector('input[name$="[price]"]');
        if (priceInput && opt.dataset.price) priceInput.value = parseFloat(opt.dataset.price).toFixed(2);
        this.recalc();
      });
    });
  },

  removeRow(idx) {
    document.getElementById(`pitem-row-${idx}`)?.remove();
    this.recalc();
  },

  recalc() {
    let total = 0;
    document.querySelectorAll('#purchaseItems tr').forEach(row => {
      const qty   = parseFloat(row.querySelector('input[name$="[qty]"]')?.value  || 1);
      const price = parseFloat(row.querySelector('input[name$="[price]"]')?.value || 0);
      const t = qty * price;
      const cell = row.querySelector('.pitem-total');
      if (cell) cell.textContent = t.toFixed(2);
      total += t;
    });
    const el = document.getElementById('purchaseTotal');
    if (el) el.textContent = total.toFixed(2);
    const inp = document.getElementById('purchaseTotalInput');
    if (inp) inp.value = total.toFixed(2);
  }
};

// -------------------------------------------------------
// Charts helper (Chart.js)
// -------------------------------------------------------
const Charts = {
  colors: {
    primary: '#2563eb',
    success: '#22c55e',
    warning: '#f59e0b',
    danger:  '#ef4444',
    info:    '#06b6d4',
    purple:  '#8b5cf6',
  },

  lineGradient(ctx, color) {
    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, color + '55');
    gradient.addColorStop(1, color + '00');
    return gradient;
  },

  defaults() {
    if (typeof Chart === 'undefined') return;
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.font.size   = 12;
    Chart.defaults.color       = '#64748b';
  }
};

// -------------------------------------------------------
// Global Delete handler via data-attributes
// -------------------------------------------------------
document.addEventListener('click', e => {
  const btn = e.target.closest('[data-delete-url]');
  if (!btn) return;
  e.preventDefault();
  const url  = btn.dataset.deleteUrl;
  const name = btn.dataset.deleteName || 'this item';
  SmartINV.confirm('Delete ' + name + '?', 'This action cannot be undone.')
    .then(result => {
      if (!result.isConfirmed) return;
      Ajax.post(url, { action: 'delete' }, json => {
        SmartINV.toast(json.message || 'Deleted successfully!');
        btn.closest('tr')?.remove();
      });
    });
});

// -------------------------------------------------------
// Initialize on DOM ready
// -------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
  DarkMode.init();
  Sidebar.init();
  Charts.defaults();

  // Tooltip init
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el, { trigger: 'hover' });
  });

  // Auto-dismiss alerts
  setTimeout(() => {
    document.querySelectorAll('.alert-auto-dismiss').forEach(el => {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
      bsAlert?.close();
    });
  }, 4000);
});
