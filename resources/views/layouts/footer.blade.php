        </main>
    </div>
</div>

<div class="sidebar-backdrop d-lg-none" id="sidebar-backdrop"></div>

<script src="{{ asset('js/jquery.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/v/bs5/dt-2.1.8/datatables.min.js"></script>
<script src="{{ asset('js/admin.js') }}"></script>
<script src="{{ asset('js/modal-crud.js') }}"></script>
<script src="{{ asset('js/searchable-select.js') }}?v={{ filemtime(public_path('js/searchable-select.js')) }}"></script>
<script>
    // Global jQuery AJAX Setup (CSRF & AJAX Header)
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'X-Requested-With': 'XMLHttpRequest'
        }
    });

    // Matikan popup browser alert bawaan DataTables (tn/7)
    $.fn.dataTable.ext.errMode = 'none';

    // Tangani error AJAX DataTables secara anggun (suppress alert, log ke console)
    $(document).on('error.dt', function (e, settings, techNote, message) {
        console.warn('DataTables error ditangani:', message, techNote);
    });

    // Tangani sesi login kedaluwarsa (401 / 419) tanpa crash popup
    $(document).ajaxError(function (event, jqXHR) {
        if (jqXHR.status === 401 || jqXHR.status === 419) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sesi Kedaluwarsa',
                    text: 'Sesi login Anda telah berakhir. Mengalihkan ke halaman login...',
                    timer: 2500,
                    showConfirmButton: false
                }).then(function () {
                    window.location.href = '{{ route('login') }}';
                });
            } else {
                window.location.href = '{{ route('login') }}';
            }
        }
    });

    $.extend(true, $.fn.dataTable.defaults, {
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Semua']],
        language: {
            processing: 'Memproses...',
            search: 'Cari:',
            lengthMenu: 'Tampilkan _MENU_ data',
            info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
            infoEmpty: 'Tidak ada data',
            zeroRecords: 'Data tidak ditemukan',
            paginate: { first: 'Awal', last: 'Akhir', next: '›', previous: '‹' }
        }
    });
</script>
@stack('js')
</body>
</html>
