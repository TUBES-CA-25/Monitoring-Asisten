let currentPage = 1;

function loadData(page = 1) {
    currentPage = page;
    const status = document.getElementById('statusFilter').value;
    const limit = 10;
    const offset = (page - 1) * limit;

    // Show loading state
    document.getElementById('izinTableBody').innerHTML = `
        <tr>
            <td colspan="6" class="p-6 text-center text-gray-400">
                <i class="fas fa-spinner fa-spin mr-2"></i> Loading...
            </td>
        </tr>
    `;

    // Create FormData for AJAX
    const formData = new FormData();
    formData.append('status', status);
    formData.append('page', page);
    formData.append('limit', limit);

    fetch(`${window.APP_CONFIG.BASE_URL}/admin/izin`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        // Parse the response to extract table data
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        const newTableBody = doc.getElementById('izinTableBody');
        const countIzin = doc.getElementById('countIzin');
        const totalIzin = doc.getElementById('totalIzin');
        const currentPageSpan = doc.getElementById('currentPage');
        const totalPagesSpan = doc.getElementById('totalPages');
        const paginationContainer = doc.getElementById('paginationContainer');

        if (newTableBody) {
            document.getElementById('izinTableBody').innerHTML = newTableBody.innerHTML;
            document.getElementById('countIzin').textContent = countIzin.textContent;
            document.getElementById('totalIzin').textContent = totalIzin.textContent;
            document.getElementById('currentPage').textContent = currentPageSpan.textContent;
            document.getElementById('totalPages').textContent = totalPagesSpan.textContent;
            document.getElementById('paginationContainer').innerHTML = paginationContainer.innerHTML;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('izinTableBody').innerHTML = `
            <tr>
                <td colspan="6" class="p-6 text-center text-red-500">
                    <i class="fas fa-exclamation-circle mr-2"></i> Gagal memuat data
                </td>
            </tr>
        `;
    });
}

function showModal(id, nama, tipe, deskripsi, status, fileBukti) {
    document.getElementById('modal_id_izin').value = id;

    // Build file preview HTML
    let filePreviewHTML = '';
    if (fileBukti) {
        const fileUrl = `${window.APP_CONFIG.BASE_URL}/uploads/leaves/` + fileBukti;
        const isImage = /\.(jpg|jpeg|png|gif|webp)$/i.test(fileBukti);
        const isPdf = /\.pdf$/i.test(fileBukti);

        if (isImage) {
            filePreviewHTML = `
                <div class="col-span-2">
                    <label class="text-xs font-bold text-gray-500 uppercase">File Bukti (Photo)</label>
                    <div class="mt-2 rounded-lg overflow-hidden border border-gray-200">
                        <img src="${fileUrl}" alt="Bukti" class="w-full h-auto max-h-96 object-cover">
                    </div>
                    <a href="${fileUrl}" target="_blank" class="inline-block mt-2 text-blue-600 hover:text-blue-800 text-sm font-bold">
                        <i class="fas fa-download mr-1"></i> Lihat File Original
                    </a>
                </div>
            `;
        } else if (isPdf) {
            filePreviewHTML = `
                <div class="col-span-2">
                    <label class="text-xs font-bold text-gray-500 uppercase">File Bukti (PDF)</label>
                    <div class="mt-2 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-file-pdf text-2xl text-red-600"></i>
                            <span class="text-sm font-bold text-gray-800">${fileBukti}</span>
                        </div>
                        <a href="${fileUrl}" target="_blank" class="px-3 py-2 bg-blue-600 text-white rounded-lg text-xs font-bold hover:bg-blue-700">
                            <i class="fas fa-external-link-alt mr-1"></i> Buka
                        </a>
                    </div>
                </div>
            `;
        } else {
            filePreviewHTML = `
                <div class="col-span-2">
                    <label class="text-xs font-bold text-gray-500 uppercase">File Bukti</label>
                    <div class="mt-2 p-3 bg-gray-50 border border-gray-200 rounded-lg">
                        <a href="${fileUrl}" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm font-bold break-all">
                            <i class="fas fa-download mr-1"></i> ${fileBukti}
                        </a>
                    </div>
                </div>
            `;
        }
    } else {
        filePreviewHTML = `
            <div class="col-span-2">
                <label class="text-xs font-bold text-gray-500 uppercase">File Bukti</label>
                <div class="mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-sm text-yellow-800">
                    <i class="fas fa-info-circle mr-2"></i> Belum ada file bukti yang diupload
                </div>
            </div>
        `;
    }

    document.getElementById('modalContent').innerHTML = `
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Asisten</label>
                <p class="text-lg font-bold text-gray-800">${nama}</p>
            </div>
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Tipe</label>
                <p class="text-lg font-bold text-blue-600">${tipe}</p>
            </div>
            <div class="col-span-2">
                <label class="text-xs font-bold text-gray-500 uppercase">Status</label>
                <p class="text-sm"><span class="px-3 py-1 rounded font-bold bg-${status === 'Pending' ? 'yellow-100 text-yellow-800' : (status === 'Approved' ? 'green-100 text-green-800' : 'red-100 text-red-800')}">${status}</span></p>
            </div>
            <div class="col-span-2">
                <label class="text-xs font-bold text-gray-500 uppercase">Deskripsi</label>
                <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded-lg">${deskripsi}</p>
            </div>
            ${filePreviewHTML}
        </div>
    `;

    // Show buttons only if Pending
    document.getElementById('approveBtn').style.display = status === 'Pending' ? 'block' : 'none';
    document.getElementById('rejectBtn').style.display = status === 'Pending' ? 'block' : 'none';

    document.getElementById('detailModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('detailModal').classList.add('hidden');
}

function submitForm(action) {
    if (!confirm(`Yakin ingin ${action === 'approve' ? 'approve' : 'reject'} izin ini?`)) return;

    document.getElementById('modal_action').value = action;
    document.getElementById('actionForm').submit();
}

document.addEventListener('DOMContentLoaded', () => {
    // Listen to status filter changes
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', () => {
            loadData(1);
        });
    }

    // Live clock
    setInterval(() => {
        const liveTime = document.getElementById('liveTime');
        if (liveTime) liveTime.textContent = new Date().toLocaleTimeString('id-ID');
    }, 1000);

    // Close modal on outside click
    const detailModal = document.getElementById('detailModal');
    if (detailModal) {
        detailModal.addEventListener('click', (e) => {
            if (e.target.id === 'detailModal') closeModal();
        });
    }
});
