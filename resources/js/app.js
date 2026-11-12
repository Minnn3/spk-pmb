import {
    Chart,
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    Tooltip,
    Legend,
} from 'chart.js';

Chart.register(
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    Tooltip,
    Legend
);

document.addEventListener('DOMContentLoaded', () => {
    initSidebarToggle();
    initToasts();
    initAutoSubmitSelects();
    initPrintButtons();
    initModals();
    initDataTables();
    initFileUploadPreview();
    initDashboardChart();
});

/**
 * Inisialisasi buka/tutup sidebar pada perangkat mobile (< 768px).
 */
function initSidebarToggle() {
    const hamburger = document.getElementById('sidebarToggleBtn');
    const sidebar = document.getElementById('appSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');

    if (!hamburger || !sidebar || !backdrop) {
        return;
    }

    const closeSidebar = () => {
        sidebar.classList.remove('open');
        backdrop.classList.remove('open');
        hamburger.setAttribute('aria-expanded', 'false');
    };

    hamburger.addEventListener('click', () => {
        const isOpen = sidebar.classList.toggle('open');
        backdrop.classList.toggle('open', isOpen);
        hamburger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    backdrop.addEventListener('click', closeSidebar);
}

/**
 * Inisialisasi penutupan otomatis dan tombol tutup pada notifikasi toast.
 */
function initToasts() {
    const toasts = document.querySelectorAll('[data-toast]');

    toasts.forEach((toast) => {
        const closeBtn = toast.querySelector('[data-toast-close]');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                toast.remove();
            });
        }

        window.setTimeout(() => {
            if (toast.isConnected) {
                toast.remove();
            }
        }, 5000);
    });
}

/**
 * Inisialisasi select tahun target atau filter yang langsung mengirim form GET saat berubah.
 */
function initAutoSubmitSelects() {
    const selects = document.querySelectorAll('[data-auto-submit="true"]');

    selects.forEach((select) => {
        select.addEventListener('change', () => {
            if (select.form) {
                select.form.submit();
            }
        });
    });
}

/**
 * Inisialisasi tombol cetak dokumen pada halaman Perhitungan.
 */
function initPrintButtons() {
    const printButtons = document.querySelectorAll('[data-action="print"]');

    printButtons.forEach((button) => {
        button.addEventListener('click', () => {
            window.print();
        });
    });
}

/**
 * Inisialisasi sistem modal dialog (Tambah/Edit/Hapus Data Historis & Reset Parameter).
 */
function initModals() {
    const openTriggers = document.querySelectorAll('[data-modal-open]');
    const closeTriggers = document.querySelectorAll('[data-modal-close]');

    openTriggers.forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const targetId = trigger.getAttribute('data-modal-open');
            if (!targetId) {
                return;
            }

            const modal = document.getElementById(targetId);
            if (!modal) {
                return;
            }

            // Jika tombol adalah pemicu Edit Data Historis, isi form di dalam modal
            if (targetId === 'modalEditData') {
                const actionUrl = trigger.getAttribute('data-action-url') || '';
                const tahun = trigger.getAttribute('data-tahun') || '';
                const bulan = trigger.getAttribute('data-bulan') || '';
                const jumlah = trigger.getAttribute('data-jumlah') || '';

                const form = modal.querySelector('#formEditData');
                const inputTahun = modal.querySelector('#edit_tahun');
                const inputBulan = modal.querySelector('#edit_bulan');
                const inputJumlah = modal.querySelector('#edit_jumlah_pendaftar');

                if (form) form.setAttribute('action', actionUrl);
                if (inputTahun) inputTahun.value = tahun;
                if (inputBulan) inputBulan.value = bulan;
                if (inputJumlah) inputJumlah.value = jumlah;
            }

            // Jika tombol adalah pemicu Hapus Data Historis
            if (targetId === 'modalHapusData') {
                const actionUrl = trigger.getAttribute('data-action-url') || '';
                const periodeLabel = trigger.getAttribute('data-periode') || '';

                const form = modal.querySelector('#formHapusData');
                const labelEl = modal.querySelector('#hapusPeriodeLabel');

                if (form) form.setAttribute('action', actionUrl);
                if (labelEl) labelEl.textContent = periodeLabel;
            }

            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
        });
    });

    closeTriggers.forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const overlay = trigger.closest('.modal-overlay');
            if (overlay) {
                overlay.classList.remove('open');
                overlay.setAttribute('aria-hidden', 'true');
            }
        });
    });

    document.querySelectorAll('.modal-overlay').forEach((overlay) => {
        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) {
                overlay.classList.remove('open');
                overlay.setAttribute('aria-hidden', 'true');
            }
        });
    });
}

/**
 * Inisialisasi tabel interaktif (pencarian, pengurutan kolom, filter tahun, dan paginasi)
 * yang di-skin mengikuti komponen .table-toolbar dan .table pada DESIGN.md.
 */
function initDataTables() {
    const wrappers = document.querySelectorAll('[data-datatable]');

    wrappers.forEach((wrapper) => {
        const table = wrapper.querySelector('table');
        const tbody = table ? table.querySelector('tbody') : null;
        if (!table || !tbody) {
            return;
        }

        const allRows = Array.from(tbody.querySelectorAll('tr[data-dt-row]'));
        if (allRows.length === 0) {
            return;
        }

        const pageSize = parseInt(wrapper.getAttribute('data-page-size') || '12', 10);
        const searchInput = wrapper.querySelector('[data-dt-search]');
        const yearFilter = wrapper.querySelector('[data-dt-year]');
        const infoEl = wrapper.querySelector('[data-dt-info]');
        const paginationEl = wrapper.querySelector('[data-dt-pagination]');
        const sortHeaders = Array.from(table.querySelectorAll('th[data-dt-sort]'));

        let currentPage = 1;
        let sortColumnIndex = -1;
        let sortDirection = 'asc';

        const getFilteredRows = () => {
            const keyword = searchInput ? searchInput.value.trim().toLowerCase() : '';
            const selectedYear = yearFilter ? yearFilter.value.trim() : '';

            return allRows.filter((row) => {
                if (selectedYear !== '' && row.getAttribute('data-year') !== selectedYear) {
                    return false;
                }

                if (keyword !== '') {
                    const rowText = (row.textContent || '').toLowerCase();
                    if (!rowText.includes(keyword)) {
                        return false;
                    }
                }

                return true;
            });
        };

        const getSortedRows = (rows) => {
            if (sortColumnIndex < 0) {
                return rows;
            }

            const sortType = sortHeaders[sortColumnIndex]?.getAttribute('data-dt-sort') || 'text';

            return [...rows].sort((rowA, rowB) => {
                const cellA = rowA.children[sortColumnIndex];
                const cellB = rowB.children[sortColumnIndex];

                const rawA = cellA?.getAttribute('data-sort-value') ?? cellA?.textContent?.trim() ?? '';
                const rawB = cellB?.getAttribute('data-sort-value') ?? cellB?.textContent?.trim() ?? '';

                let comparison = 0;
                if (sortType === 'number') {
                    const numA = parseFloat(String(rawA).replace(/[^\d.-]/g, '')) || 0;
                    const numB = parseFloat(String(rawB).replace(/[^\d.-]/g, '')) || 0;
                    comparison = numA - numB;
                } else {
                    comparison = String(rawA).localeCompare(String(rawB), 'id');
                }

                return sortDirection === 'asc' ? comparison : -comparison;
            });
        };

        const render = () => {
            const filtered = getFilteredRows();
            const sorted = getSortedRows(filtered);
            const totalItems = sorted.length;
            const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));

            if (currentPage > totalPages) {
                currentPage = totalPages;
            }

            const startIndex = (currentPage - 1) * pageSize;
            const endIndex = Math.min(startIndex + pageSize, totalItems);

            allRows.forEach((row) => {
                row.hidden = true;
            });

            sorted.forEach((row, index) => {
                tbody.appendChild(row);
                if (index >= startIndex && index < endIndex) {
                    row.hidden = false;
                }
            });

            if (infoEl) {
                if (totalItems === 0) {
                    infoEl.textContent = 'Menampilkan 0 dari 0 entri';
                } else {
                    infoEl.textContent = `Menampilkan ${startIndex + 1}–${endIndex} dari ${totalItems} entri`;
                }
            }

            if (paginationEl) {
                paginationEl.innerHTML = '';

                const prevBtn = document.createElement('button');
                prevBtn.type = 'button';
                prevBtn.className = 'page-btn';
                prevBtn.textContent = '←';
                prevBtn.disabled = currentPage <= 1;
                prevBtn.setAttribute('aria-label', 'Halaman sebelumnya');
                prevBtn.addEventListener('click', () => {
                    if (currentPage > 1) {
                        currentPage--;
                        render();
                    }
                });
                paginationEl.appendChild(prevBtn);

                for (let page = 1; page <= totalPages; page++) {
                    const pageBtn = document.createElement('button');
                    pageBtn.type = 'button';
                    pageBtn.className = 'page-btn' + (page === currentPage ? ' active' : '');
                    pageBtn.textContent = String(page);
                    pageBtn.setAttribute('aria-label', `Halaman ${page}`);
                    pageBtn.addEventListener('click', () => {
                        currentPage = page;
                        render();
                    });
                    paginationEl.appendChild(pageBtn);
                }

                const nextBtn = document.createElement('button');
                nextBtn.type = 'button';
                nextBtn.className = 'page-btn';
                nextBtn.textContent = '→';
                nextBtn.disabled = currentPage >= totalPages;
                nextBtn.setAttribute('aria-label', 'Halaman berikutnya');
                nextBtn.addEventListener('click', () => {
                    if (currentPage < totalPages) {
                        currentPage++;
                        render();
                    }
                });
                paginationEl.appendChild(nextBtn);
            }
        };

        if (searchInput) {
            searchInput.addEventListener('input', () => {
                currentPage = 1;
                render();
            });
        }

        if (yearFilter) {
            yearFilter.addEventListener('change', () => {
                currentPage = 1;
                render();
            });
        }

        sortHeaders.forEach((header, index) => {
            header.addEventListener('click', () => {
                if (sortColumnIndex === index) {
                    sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    sortColumnIndex = index;
                    sortDirection = 'asc';
                }

                render();
            });
        });

        render();
    });
}

/**
 * Tampilkan nama dan ukuran berkas .xlsx yang dipilih pada halaman Import Excel.
 */
function initFileUploadPreview() {
    const fileInput = document.getElementById('berkas_excel');
    const fileInfoEl = document.getElementById('selectedFileInfo');

    if (!fileInput || !fileInfoEl) {
        return;
    }

    fileInput.addEventListener('change', () => {
        const file = fileInput.files && fileInput.files[0];
        if (!file) {
            fileInfoEl.textContent = 'Belum ada berkas yang dipilih (format .xlsx, maksimal 2 MB).';
            return;
        }

        const sizeKb = (file.size / 1024).toFixed(1);
        fileInfoEl.textContent = `Berkas terpilih: ${file.name} (${sizeKb} KB)`;
    });
}

/**
 * Inisialisasi grafik garis aktual vs prediksi di halaman Dashboard
 * dengan membaca token warna langsung dari getComputedStyle(document.documentElement) (PRD 5.3 & 5.6).
 */
function initDashboardChart() {
    const canvas = document.getElementById('spkForecastChart');
    if (!canvas) {
        return;
    }

    const rawConfig = canvas.getAttribute('data-chart-payload');
    if (!rawConfig) {
        return;
    }

    let payload;
    try {
        payload = JSON.parse(rawConfig);
    } catch {
        return;
    }

    const rootStyles = window.getComputedStyle(document.documentElement);
    const colorTextPrimary = rootStyles.getPropertyValue('--text-primary').trim();
    const colorTextSecondary = rootStyles.getPropertyValue('--text-secondary').trim();
    const colorTextTertiary = rootStyles.getPropertyValue('--text-tertiary').trim();
    const colorAccent = rootStyles.getPropertyValue('--accent').trim();
    const colorBorder = rootStyles.getPropertyValue('--border').trim();
    const colorSurface = rootStyles.getPropertyValue('--bg-surface').trim();
    const fontSans = rootStyles.getPropertyValue('--font-sans').trim();
    const fontMono = rootStyles.getPropertyValue('--font-mono').trim();

    const labels = payload.labels || [];
    const aktualSeries = payload.aktual_tahun_terakhir || [];
    const prediksiSeries = payload.prediksi || [];
    const targetMinLine = labels.map(() => payload.target_minimum_pendaftar);
    const batasPeakLine = labels.map(() => payload.batas_peak_season);

    new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: payload.label_aktual || 'Aktual',
                    data: aktualSeries,
                    borderColor: colorTextPrimary,
                    backgroundColor: colorTextPrimary,
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    tension: 0.25,
                    fill: false,
                },
                {
                    label: payload.label_prediksi || 'Prediksi',
                    data: prediksiSeries,
                    borderColor: colorAccent,
                    backgroundColor: colorAccent,
                    borderWidth: 2.5,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.25,
                    fill: false,
                },
                {
                    label: `Batas Peak Season (${payload.batas_peak_season})`,
                    data: batasPeakLine,
                    borderColor: colorTextTertiary,
                    backgroundColor: colorTextTertiary,
                    borderWidth: 1.5,
                    borderDash: [6, 6],
                    pointRadius: 0,
                    pointHoverRadius: 0,
                    fill: false,
                },
                {
                    label: `Target Minimum (${payload.target_minimum_pendaftar})`,
                    data: targetMinLine,
                    borderColor: colorTextTertiary,
                    backgroundColor: colorTextTertiary,
                    borderWidth: 1.5,
                    borderDash: [4, 4],
                    pointRadius: 0,
                    pointHoverRadius: 0,
                    fill: false,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: {
                        color: colorTextSecondary,
                        usePointStyle: true,
                        boxWidth: 8,
                        padding: 16,
                        font: {
                            family: fontSans,
                            size: 12,
                            weight: '500',
                        },
                    },
                },
                tooltip: {
                    backgroundColor: colorSurface,
                    titleColor: colorTextPrimary,
                    bodyColor: colorTextSecondary,
                    borderColor: colorBorder,
                    borderWidth: 1,
                    padding: 12,
                    titleFont: {
                        family: fontSans,
                        size: 13,
                        weight: '600',
                    },
                    bodyFont: {
                        family: fontMono,
                        size: 12,
                    },
                },
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        color: colorTextSecondary,
                        font: {
                            family: fontSans,
                            size: 12,
                        },
                    },
                    border: {
                        color: colorBorder,
                    },
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: colorBorder,
                    },
                    ticks: {
                        color: colorTextSecondary,
                        font: {
                            family: fontMono,
                            size: 12,
                        },
                    },
                    border: {
                        display: false,
                    },
                },
            },
        },
    });
}
