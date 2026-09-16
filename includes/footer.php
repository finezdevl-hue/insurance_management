</div> <!-- .container-fluid -->
        </div> <!-- #main-content -->
    </div> <!-- #wrapper -->

    <?php if (isLoggedIn()): 
        $userRole = $_SESSION['role'] ?? '';
        $activeNav = $activePage ?? 'dashboard';
    ?>
        <!-- Sticky Mobile Bottom Navigation Bar -->
        <nav id="mobile-bottom-nav" aria-label="Mobile Bottom Navigation">
            <?php if ($userRole === 'admin'): ?>
                <a href="../admin/index.php" class="mobile-nav-item <?php echo $activeNav === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span>Home</span>
                </a>
                <a href="../admin/agents.php" class="mobile-nav-item <?php echo $activeNav === 'agents' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-user-tie"></i>
                    <span>Agents</span>
                </a>
                <a href="../admin/vehicles.php" class="mobile-nav-item <?php echo $activeNav === 'vehicles' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-car-side"></i>
                    <span>Vehicles</span>
                </a>
                <a href="../admin/reports.php" class="mobile-nav-item <?php echo $activeNav === 'reports' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    <span>Reports</span>
                </a>
                <button type="button" class="mobile-nav-item" id="mobile-sidebar-toggle" aria-label="Toggle Menu">
                    <i class="fa-solid fa-bars"></i>
                    <span>Menu</span>
                </button>
            <?php else: ?>
                <a href="../agent/index.php" class="mobile-nav-item <?php echo $activeNav === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-gauge-high"></i>
                    <span>Home</span>
                </a>
                <a href="../agent/vehicles.php" class="mobile-nav-item <?php echo $activeNav === 'vehicles' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-car"></i>
                    <span>Vehicles</span>
                </a>
                <a href="../agent/health.php" class="mobile-nav-item <?php echo $activeNav === 'health' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-heart-pulse"></i>
                    <span>Health</span>
                </a>
                <a href="../agent/reminders.php" class="mobile-nav-item <?php echo $activeNav === 'reminders' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-bell"></i>
                    <span>Renewals</span>
                </a>
                <button type="button" class="mobile-nav-item" id="mobile-sidebar-toggle" aria-label="Toggle Menu">
                    <i class="fa-solid fa-bars"></i>
                    <span>Menu</span>
                </button>
            <?php endif; ?>
        </nav>
    <?php endif; ?>

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

        // Hide loader when page is restored from bfcache (back button)
        $(window).on('pageshow', function(event) {
            if (event.originalEvent.persisted) {
                $('#loader-wrapper').fadeOut(300);
            }
        });

        $(document).ready(function() {
            // Show loader on form submission
            $('form').on('submit', function() {
                // If form is valid (or if no validation API), show loader
                if (!this.checkValidity || this.checkValidity()) {
                    $('#loader-wrapper').fadeIn(200);
                }
            });
            // Sidebar Toggle Controls
            $('#sidebar-toggle, #mobile-sidebar-toggle').on('click', function(e) {
                e.preventDefault();
                $('#sidebar').toggleClass('show-sidebar');
            });

            // Close sidebar when clicking outside on mobile
            $(document).on('click', function(event) {
                if (!$(event.target).closest('#sidebar, #sidebar-toggle, #mobile-sidebar-toggle').length) {
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
