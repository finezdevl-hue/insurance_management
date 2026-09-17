    </div> <!-- .container-fluid -->

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
                <button type="button" class="mobile-nav-item" id="open-mobile-drawer" aria-label="Open Full Menu">
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
                <button type="button" class="mobile-nav-item" id="open-mobile-drawer" aria-label="Open Full Menu">
                    <i class="fa-solid fa-bars"></i>
                    <span>Menu</span>
                </button>
            <?php endif; ?>
        </nav>

        <!-- Slide-Out Navigation Drawer -->
        <div id="mobile-drawer-overlay"></div>
        <div id="mobile-drawer">
            <div class="p-3 border-bottom d-flex align-items-center justify-content-between bg-light">
                <div class="fw-bold fs-6 text-dark d-flex align-items-center gap-2">
                    <i class="fa-solid fa-compass text-primary"></i> Mobile Menu
                </div>
                <button type="button" class="btn-close" id="close-mobile-drawer" aria-label="Close Menu"></button>
            </div>
            
            <div class="p-2 flex-grow-1 overflow-auto">
                <div class="list-group list-group-flush">
                    <?php if ($userRole === 'admin'): ?>
                        <a href="../admin/index.php" class="list-group-item list-group-item-action py-3 border-0 rounded-3 mb-1 text-dark fw-500">
                            <i class="fa-solid fa-chart-pie me-3 text-primary"></i> Admin Dashboard
                        </a>
                        <a href="../admin/agents.php" class="list-group-item list-group-item-action py-3 border-0 rounded-3 mb-1 text-dark fw-500">
                            <i class="fa-solid fa-user-tie me-3 text-primary"></i> Agents Management
                        </a>
                        <a href="../admin/shops.php" class="list-group-item list-group-item-action py-3 border-0 rounded-3 mb-1 text-dark fw-500">
                            <i class="fa-solid fa-store me-3 text-primary"></i> Sub-Shops & Outlets
                        </a>
                        <a href="../admin/vehicles.php" class="list-group-item list-group-item-action py-3 border-0 rounded-3 mb-1 text-dark fw-500">
                            <i class="fa-solid fa-car-side me-3 text-primary"></i> Vehicle Records
                        </a>
                        <a href="../admin/health.php" class="list-group-item list-group-item-action py-3 border-0 rounded-3 mb-1 text-dark fw-500">
                            <i class="fa-solid fa-heart-pulse me-3 text-primary"></i> Health Insurance
                        </a>
                        <a href="../admin/subscription_plans.php" class="list-group-item list-group-item-action py-3 border-0 rounded-3 mb-1 text-dark fw-500">
                            <i class="fa-solid fa-crown me-3 text-primary"></i> Subscription Plans
                        </a>
                        <a href="../admin/subscriptions.php" class="list-group-item list-group-item-action py-3 border-0 rounded-3 mb-1 text-dark fw-500">
                            <i class="fa-solid fa-file-invoice-dollar me-3 text-primary"></i> Agent Subscriptions
                        </a>
                        <a href="../admin/companies.php" class="list-group-item list-group-item-action py-3 border-0 rounded-3 mb-1 text-dark fw-500">
                            <i class="fa-solid fa-building-shield me-3 text-primary"></i> Insurance Companies
                        </a>
                        <a href="../admin/types.php" class="list-group-item list-group-item-action py-3 border-0 rounded-3 mb-1 text-dark fw-500">
                            <i class="fa-solid fa-truck-pickup me-3 text-primary"></i> Vehicle Types
                        </a>
                        <a href="../admin/reports.php" class="list-group-item list-group-item-action py-3 border-0 rounded-3 mb-1 text-dark fw-500">
                            <i class="fa-solid fa-chart-line me-3 text-primary"></i> Expiry Reports
                        </a>
                        <a href="../admin/reminders.php" class="list-group-item list-group-item-action py-3 border-0 rounded-3 mb-1 text-dark fw-500">
                            <i class="fa-solid fa-clock-rotate-left me-3 text-primary"></i> Reminder History
                        </a>
                        <a href="../admin/recharge_history.php" class="list-group-item list-group-item-action py-3 border-0 rounded-3 mb-1 text-dark fw-500">
                            <i class="fa-solid fa-money-bill-transfer me-3 text-primary"></i> Message Recharges
                        </a>
                        <a href="../admin/activity.php" class="list-group-item list-group-item-action py-3 border-0 rounded-3 mb-1 text-dark fw-500">
                            <i class="fa-solid fa-list-check me-3 text-primary"></i> Activity Logs
                        </a>
                        <a href="../admin/settings.php" class="list-group-item list-group-item-action py-3 border-0 rounded-3 mb-1 text-dark fw-500">
                            <i class="fa-solid fa-gears me-3 text-primary"></i> System Settings
                        </a>
                        <a href="../admin/logs.php" class="list-group-item list-group-item-action py-3 border-0 rounded-3 mb-1 text-dark fw-500">
                            <i class="fa-solid fa-bug me-3 text-primary"></i> System & Query Logs
                        </a>
                    <?php else: ?>
                        <a href="../agent/index.php" class="list-group-item list-group-item-action py-3 border-0 rounded-3 mb-1 text-dark fw-500">
                            <i class="fa-solid fa-gauge-high me-3 text-success"></i> Agency Dashboard
                        </a>
                        <?php if ($userRole === 'agent'): ?>
                            <a href="../agent/subscriptions.php" class="list-group-item list-group-item-action py-3 border-0 rounded-3 mb-1 text-dark fw-500">
                                <i class="fa-solid fa-crown me-3 text-warning"></i> My Subscription & Plans
                            </a>
                            <a href="../agent/shops.php" class="list-group-item list-group-item-action py-3 border-0 rounded-3 mb-1 text-dark fw-500">
                                <i class="fa-solid fa-store me-3 text-success"></i> Sub-Shops & Outlets
                            </a>
                        <?php endif; ?>
                        <a href="../agent/vehicles.php" class="list-group-item list-group-item-action py-3 border-0 rounded-3 mb-1 text-dark fw-500">
                            <i class="fa-solid fa-car me-3 text-success"></i> Vehicle Records
                        </a>
                        <a href="../agent/health.php" class="list-group-item list-group-item-action py-3 border-0 rounded-3 mb-1 text-dark fw-500">
                            <i class="fa-solid fa-heart-pulse me-3 text-success"></i> Health Insurance
                        </a>
                        <a href="../agent/reminders.php" class="list-group-item list-group-item-action py-3 border-0 rounded-3 mb-1 text-dark fw-500">
                            <i class="fa-solid fa-bell me-3 text-success"></i> Expiry & Renewals
                        </a>
                        <a href="../agent/customers.php" class="list-group-item list-group-item-action py-3 border-0 rounded-3 mb-1 text-dark fw-500">
                            <i class="fa-solid fa-users me-3 text-success"></i> Customer Directory
                        </a>
                        <a href="../agent/recharge_history.php" class="list-group-item list-group-item-action py-3 border-0 rounded-3 mb-1 text-dark fw-500">
                            <i class="fa-solid fa-wallet me-3 text-success"></i> Recharge History
                        </a>
                        <a href="../agent/sent_history.php" class="list-group-item list-group-item-action py-3 border-0 rounded-3 mb-1 text-dark fw-500">
                            <i class="fa-solid fa-paper-plane me-3 text-success"></i> Sent Messages
                        </a>
                        <a href="../agent/profile.php" class="list-group-item list-group-item-action py-3 border-0 rounded-3 mb-1 text-dark fw-500">
                            <i class="fa-solid fa-store me-3 text-success"></i> Outlet Profile
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="p-3 border-top bg-light">
                <a href="<?php echo $userRole === 'admin' ? '../../admin/logout.php' : '../../agent/logout.php'; ?>" class="btn btn-danger w-100 touch-action-btn">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Sign Out
                </a>
            </div>
        </div>
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
            $('#loader-wrapper').fadeOut(250);
        });

        $(window).on('pageshow', function(event) {
            if (event.originalEvent.persisted) {
                $('#loader-wrapper').fadeOut(250);
            }
        });

        $(document).ready(function() {
            // Form Submit Loader
            $('form').on('submit', function() {
                if (!this.checkValidity || this.checkValidity()) {
                    $('#loader-wrapper').fadeIn(200);
                }
            });

            // Mobile Drawer Controls
            $('#open-mobile-drawer').on('click', function(e) {
                e.preventDefault();
                $('#mobile-drawer').addClass('open');
                $('#mobile-drawer-overlay').addClass('show');
            });

            $('#close-mobile-drawer, #mobile-drawer-overlay').on('click', function() {
                $('#mobile-drawer').removeClass('open');
                $('#mobile-drawer-overlay').removeClass('show');
            });

            // DataTables Mobile Optimization
            if ($('.datatable').length > 0) {
                $('.datatable').DataTable({
                    responsive: true,
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Search records...",
                        paginate: {
                            previous: "<i class='fa-solid fa-chevron-left'></i>",
                            next: "<i class='fa-solid fa-chevron-right'></i>"
                        }
                    }
                });
            }

            // Session Alerts
            <?php if (isset($_SESSION['alert_success'])): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: <?php echo json_encode($_SESSION['alert_success']); unset($_SESSION['alert_success']); ?>,
                    timer: 3500,
                    timerProgressBar: true,
                    confirmButtonColor: '#10b981'
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
