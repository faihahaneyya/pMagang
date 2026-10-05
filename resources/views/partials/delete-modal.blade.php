{{-- Delete Confirmation Modal --}}
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <div class="modal-icon">
            <i class="fas fa-trash"></i>
        </div>
        <div class="modal-title">Konfirmasi Hapus</div>
        <div class="modal-desc">
            Apakah Anda yakin ingin menghapus <strong id="deleteItemName">item ini</strong>?<br>
            Data yang dihapus tidak dapat dikembalikan.
        </div>
        <div class="modal-actions">
            <button onclick="closeDeleteModal()" class="btn btn-secondary">
                <i class="fas fa-xmark"></i> Batal
            </button>
            <button onclick="executeDelete()" class="btn btn-danger">
                <i class="fas fa-trash"></i> Hapus
            </button>
        </div>
    </div>
</div>
