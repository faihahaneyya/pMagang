/**
 * Inventory Sewa Beli — App JS
 * PT Banyan Koalindo Lestari
 */

// === DELETE CONFIRMATION MODAL ===
let pendingDeleteForm = null;

function confirmDelete(event, itemName) {
    event.preventDefault();
    pendingDeleteForm = event.target.closest('form');
    const modal = document.getElementById('deleteModal');
    const itemNameEl = document.getElementById('deleteItemName');
    if (itemNameEl) itemNameEl.textContent = itemName || 'item ini';
    if (modal) modal.classList.add('open');
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    if (modal) modal.classList.remove('open');
    pendingDeleteForm = null;
}

function executeDelete() {
    if (pendingDeleteForm) {
        pendingDeleteForm.submit();
    }
}

// Close modal on overlay click
document.addEventListener('click', function (e) {
    const modal = document.getElementById('deleteModal');
    if (modal && e.target === modal) {
        closeDeleteModal();
    }
});

// === CONTRACT TOGGLE ===
function toggleContractFields() {
    const radio = document.querySelector('input[name="memiliki_kontrak"]:checked');
    const contractSection = document.getElementById('contractSection');
    const selectExisting = document.getElementById('selectExistingSection');
    const createNew = document.getElementById('createNewSection');

    if (!contractSection) return;

    if (radio && radio.value === 'ya') {
        contractSection.style.display = 'block';
        contractSection.style.opacity = '0';
        contractSection.style.transform = 'translateY(8px)';
        setTimeout(() => {
            contractSection.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
            contractSection.style.opacity = '1';
            contractSection.style.transform = 'translateY(0)';
        }, 10);
    } else {
        contractSection.style.opacity = '0';
        contractSection.style.transform = 'translateY(8px)';
        setTimeout(() => {
            contractSection.style.display = 'none';
        }, 200);
    }
}

// Contract source toggle (existing vs new)
function toggleContractSource() {
    const source = document.querySelector('input[name="kontrak_source"]:checked');
    const existingSection = document.getElementById('existingKontrakSection');
    const newSection = document.getElementById('newKontrakSection');

    if (!existingSection || !newSection) return;

    if (source && source.value === 'existing') {
        existingSection.style.display = 'block';
        newSection.style.display = 'none';
    } else {
        existingSection.style.display = 'none';
        newSection.style.display = 'block';
    }
}

// Auto compute duration from dates
function computeDuration() {
    const start = document.getElementById('tanggal_mulai');
    const end = document.getElementById('tanggal_berakhir');
    const duration = document.getElementById('durasi_kontrak');

    if (!start || !end || !duration) return;

    if (start.value && end.value) {
        const s = new Date(start.value);
        const e = new Date(end.value);
        if (e > s) {
            const diffMs = e - s;
            const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
            const months = Math.floor(diffDays / 30);
            const days = diffDays % 30;

            let text = '';
            if (months > 0) text += `${months} Bulan`;
            if (days > 0) text += (text ? ' ' : '') + `${days} Hari`;
            duration.value = text || '0 Hari';
        }
    }
}

// Initialize contract toggles on DOM ready
document.addEventListener('DOMContentLoaded', function () {
    // Init contract toggle
    toggleContractFields();

    // Date change listeners
    const startDate = document.getElementById('tanggal_mulai');
    const endDate = document.getElementById('tanggal_berakhir');
    if (startDate) startDate.addEventListener('change', computeDuration);
    if (endDate) endDate.addEventListener('change', computeDuration);

    // Init contract source toggle
    const sourceRadios = document.querySelectorAll('input[name="kontrak_source"]');
    sourceRadios.forEach(r => r.addEventListener('change', toggleContractSource));
    toggleContractSource();
});

// === DASHBOARD CARD FILTER ===
function filterDashboard(filter, cardEl) {
    // Update active card
    document.querySelectorAll('.stat-card').forEach(c => c.classList.remove('card-active'));
    if (cardEl) cardEl.classList.add('card-active');

    // Update URL without reload
    const url = new URL(window.location);
    url.searchParams.set('filter', filter);
    history.pushState({}, '', url);

    // Fetch filtered list
    const listWrapper = document.getElementById('dashboardList');
    if (!listWrapper) return;

    listWrapper.style.opacity = '0';
    listWrapper.style.transform = 'translateY(8px)';

    fetch(`/?filter=${filter}&ajax=1`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(r => r.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newList = doc.getElementById('dashboardList');
            if (newList) {
                listWrapper.innerHTML = newList.innerHTML;
                listWrapper.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
                listWrapper.style.opacity = '1';
                listWrapper.style.transform = 'translateY(0)';
            }
        })
        .catch(() => {
            // Fallback: reload page with filter
            window.location.href = `/?filter=${filter}`;
        });
}

// === BARCODE PRINT ===
function printBarcode(url) {
    const win = window.open(url, '_blank', 'width=500,height=600');
    if (win) {
        win.addEventListener('load', () => {
            win.print();
        });
    }
}

// === SELECT ALL BARCODES ===
function toggleSelectAll(checkbox) {
    document.querySelectorAll('.barcode-checkbox').forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateSelectedCount();
}

function updateSelectedCount() {
    const selected = document.querySelectorAll('.barcode-checkbox:checked').length;
    const countEl = document.getElementById('selectedCount');
    const printAllBtn = document.getElementById('printAllBtn');
    if (countEl) countEl.textContent = selected;
    if (printAllBtn) {
        printAllBtn.disabled = selected === 0;
        printAllBtn.style.opacity = selected === 0 ? '0.5' : '1';
    }
}

function printSelectedBarcodes() {
    const kodes = Array.from(document.querySelectorAll('.barcode-checkbox:checked'))
        .map(cb => cb.value);
    if (kodes.length === 0) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/admin/barcode/print-all';
    form.target = '_blank';

    const csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = '_token';
        input.value = csrf.getAttribute('content');
        form.appendChild(input);
    }

    kodes.forEach(kode => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'kodes[]';
        input.value = kode;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}
