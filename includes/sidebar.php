<?php
/**
 * Global Sidebar Template
 * Vehicle Details & Insurance Renewal Management System
 */

$role = $_SESSION['role'] ?? '';
$activePage = $activePage ?? 'dashboard';

// Fetch current user details for the footer widget if not loaded
if (isLoggedIn() && !isset($current_user)) {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch();
}
?>
<div id="sidebar">
    <!-- Brand Header -->
    <div class="sidebar-header">
        <a href="#" class="sidebar-brand">
            <i class="fa-solid fa-shield-halved text-primary"></i>
            <span class="text-truncate">Vehicle Care</span>
        </a>
    </div>

    <!-- Navigation Menu Items -->
    <div class="sidebar-menu">
        
        <?php if ($role === 'admin'): ?>
            <!-- SUPER ADMIN SIDEBAR -->
            <div class="menu-label">Main</div>
            
            <a href="../admin/index.php" class="menu-item <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">
                <i class="fa-solid fa-chart-pie"></i> Dashboard
            </a>
            
            <div class="menu-label">Management</div>
            
            <a href="../admin/agents.php" class="menu-item <?php echo $activePage === 'agents' ? 'active' : ''; ?>">
                <i class="fa-solid fa-user-tie"></i> Agents Management
            </a>
            
            <a href="../admin/customers.php" class="menu-item <?php echo $activePage === 'customers' ? 'active' : ''; ?>">
                <i class="fa-solid fa-users"></i> Customers List
            </a>
            
            <a href="../admin/vehicles.php" class="menu-item <?php echo $activePage === 'vehicles' ? 'active' : ''; ?>">
                <i class="fa-solid fa-car-side"></i> Vehicles List
            </a>
            
            <a href="../admin/health.php" class="menu-item <?php echo $activePage === 'health' ? 'active' : ''; ?>">
                <i class="fa-solid fa-heart-pulse"></i> Health Insurance List
            </a>
            
            <div class="menu-label">Settings & Master</div>
            
            <a href="../admin/companies.php" class="menu-item <?php echo $activePage === 'companies' ? 'active' : ''; ?>">
                <i class="fa-solid fa-building-shield"></i> Insurance Companies
            </a>
            
            <a href="../admin/types.php" class="menu-item <?php echo $activePage === 'types' ? 'active' : ''; ?>">
                <i class="fa-solid fa-truck-pickup"></i> Vehicle Types
            </a>
            
            <a href="../admin/settings.php" class="menu-item <?php echo $activePage === 'settings' ? 'active' : ''; ?>">
                <i class="fa-solid fa-gears"></i> System Settings
            </a>
            
            <div class="menu-label">Reports & Logs</div>
            
            <a href="../admin/reports.php" class="menu-item <?php echo $activePage === 'reports' ? 'active' : ''; ?>">
                <i class="fa-solid fa-file-invoice-dollar"></i> Expiry Reports
            </a>
            
            <a href="../admin/reminders.php" class="menu-item <?php echo $activePage === 'reminders' ? 'active' : ''; ?>">
                <i class="fa-solid fa-clock-rotate-left"></i> Reminder History
            </a>
            
            <a href="../admin/activity.php" class="menu-item <?php echo $activePage === 'activity' ? 'active' : ''; ?>">
                <i class="fa-solid fa-list-check"></i> Activity Logs
            </a>

        <?php elseif ($role === 'agent'): ?>
            <!-- AGENT SIDEBAR -->
            <div class="menu-label">Main</div>
            
            <a href="../agent/index.php" class="menu-item <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
            
            <a href="../agent/profile.php" class="menu-item <?php echo $activePage === 'profile' ? 'active' : ''; ?>">
                <i class="fa-solid fa-store"></i> Shop Profile
            </a>
            
            <div class="menu-label">Operations</div>
            
            <a href="../agent/customers.php" class="menu-item <?php echo $activePage === 'customers' ? 'active' : ''; ?>">
                <i class="fa-solid fa-user-group"></i> Manage Customers
            </a>
            
            <?php if (hasAgentAccess('health')): ?>
                <a href="../agent/health.php" class="menu-item <?php echo $activePage === 'health' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-heart-pulse"></i> Health Insurance
                </a>
            <?php endif; ?>
            
            <?php if (hasAgentAccess('vehicle') || hasAgentAccess('pollution')): ?>
                <a href="../agent/vehicles.php" class="menu-item <?php echo $activePage === 'vehicles' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-car"></i> Vehicle Records
                </a>
            <?php endif; ?>
            
            <a href="../agent/reminders.php" class="menu-item <?php echo $activePage === 'reminders' ? 'active' : ''; ?>">
                <i class="fa-solid fa-bell"></i> Expiry & Renewals
            </a>
        <?php endif; ?>
        
    </div>

    <!-- User Profile Widget Footer -->
    <?php if (isLoggedIn()): ?>
    <div class="sidebar-footer">
        <div class="user-profile-widget">
            <div class="user-avatar text-uppercase">
                <?php 
                    if ($current_user && !empty($current_user['shop_name']) && $role === 'agent') {
                        echo substr(sanitize($current_user['shop_name']), 0, 2);
                    } else {
                        echo substr(sanitize($current_user['username']), 0, 2);
                    }
                ?>
            </div>
            <div class="user-details">
                <p class="user-name text-truncate">
                    <?php echo sanitize($current_user['shop_owner_name'] ?? $current_user['username']); ?>
                </p>
                <span class="user-role"><?php echo sanitize($role); ?></span>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
