</div> <!-- .container-fluid -->
        </div> <!-- #main-content -->
    </div> <!-- #wrapper -->

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS & Plugins -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Global Client Scripts -->
    <script>
        $(window_load = function() {
            // Hide the global loading animation screen
            $('#loader-wrapper').fadeOut(300);
        });

        $(document).ready(function() {
            // Sidebar Toggle Control
            $('#sidebar-toggle').on('click', function(e) {
                e.preventDefault();
                $('#sidebar').toggleClass('show-sidebar');
            });

            // Close sidebar when clicking outside on mobile
            $(document).on('click', function(event) {
                if (!$(event.target).closest('#sidebar, #sidebar-toggle').length) {
                    if ($('#sidebar').hasClass('show-sidebar')) {
                        $('#sidebar').removeClass('show-sidebar');
                    }
                }
            });

            // Initialize DataTables automatically on any table with class .datatable
            if ($('.datatable').length > 0) {
                $('.datatable').DataTable({
                    responsive: true,
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Search here...",
                        paginate: {
                            previous: "<i class='fa-solid fa-chevron-left'></i>",
                            next: "<i class='fa-solid fa-chevron-right'></i>"
                        }
                    }
                });
            }

            // Automated Alert Messages using SweetAlert2 based on PHP Sessions
            <?php if (isset($_SESSION['alert_success'])): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: <?php echo json_encode($_SESSION['alert_success']); unset($_SESSION['alert_success']); ?>,
                    timer: 3500,
                    timerProgressBar: true,
                    confirmButtonColor: '#3b82f6'
                });
            <?php endif; ?>

            <?php if (isset($_SESSION['alert_error'])): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: <?php echo json_encode($_SESSION['alert_error']); unset($_SESSION['alert_error']); ?>,
                    confirmButtonColor: '#ef4444'
                });
            <?php endif; ?>

            <?php if (isset($_SESSION['alert_info'])): ?>
                Swal.fire({
                    icon: 'info',
                    title: 'Notice',
                    text: <?php echo json_encode($_SESSION['alert_info']); unset($_SESSION['alert_info']); ?>,
                    confirmButtonColor: '#3b82f6'
                });
            <?php endif; ?>
        });
    </script>
</body>
</html>
