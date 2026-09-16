<?php
/**
 * System Logs & Query Logs Viewer (Super Admin)
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../includes/functions.php';

// Enforce admin login
checkAccess('admin');

$db = getDBConnection();

$pageTitle = 'System & Query Logs';
$pageHeading = 'Server Diagnostics, Error Logs & Query Traces';
$activePage = 'logs';

// Handle Log Actions (Clear & Download)
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    // Download Error Log
    if ($action === 'download_error_log') {
        if (file_exists(ERROR_LOG_FILE)) {
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="error_' . date('Y-m-d_His') . '.log"');
            header('Content-Length: ' . filesize(ERROR_LOG_FILE));
            readfile(ERROR_LOG_FILE);
            exit;
        } else {
            $_SESSION['alert_error'] = 'Error log file does not exist.';
            redirect('logs.php');
        }
    }
    
    // Download Query Log
    if ($action === 'download_query_log') {
        if (file_exists(QUERY_LOG_FILE)) {
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="query_' . date('Y-m-d_His') . '.log"');
            header('Content-Length: ' . filesize(QUERY_LOG_FILE));
            readfile(QUERY_LOG_FILE);
            exit;
        } else {
            $_SESSION['alert_error'] = 'Query log file does not exist.';
            redirect('logs.php');
        }
    }
    
    // Clear Error Log
    if ($action === 'clear_error_log') {
        @file_put_contents(ERROR_LOG_FILE, '');
        try {
            $db->exec("TRUNCATE TABLE system_error_logs");
        } catch (Throwable $e) {}
        logActivity('Clear Error Logs', 'Super Admin cleared system error logs.');
        $_SESSION['alert_success'] = 'System Error Logs cleared successfully!';
        redirect('logs.php?tab=errors');
    }
    
    // Clear Query Log
    if ($action === 'clear_query_log') {
        @file_put_contents(QUERY_LOG_FILE, '');
        try {
            $db->exec("TRUNCATE TABLE system_query_logs");
        } catch (Throwable $e) {}
        logActivity('Clear Query Logs', 'Super Admin cleared SQL query logs.');
        $_SESSION['alert_success'] = 'System Query Logs cleared successfully!';
        redirect('logs.php?tab=queries');
    }
}

// Active Tab
$currentTab = $_GET['tab'] ?? 'errors';

// 1. Fetch Error Logs (from File & DB)
$errorLogs = [];
if (file_exists(ERROR_LOG_FILE) && filesize(ERROR_LOG_FILE) > 0) {
    $lines = file(ERROR_LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!empty($lines)) {
        $lines = array_reverse($lines); // Latest first
        $lines = array_slice($lines, 0, 150); // Show last 150 entries
        foreach ($lines as $line) {
            $errorLogs[] = $line;
        }
    }
}

// 2. Fetch Query Logs
$queryLogs = [];
if (file_exists(QUERY_LOG_FILE) && filesize(QUERY_LOG_FILE) > 0) {
    $qLines = file(QUERY_LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!empty($qLines)) {
        $qLines = array_reverse($qLines);
        $qLines = array_slice($qLines, 0, 150);
        foreach ($qLines as $line) {
            $queryLogs[] = $line;
        }
    }
}

// 3. System Diagnostics Data
$phpVersion = phpversion();
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$os = PHP_OS . ' (' . php_uname('s') . ' ' . php_uname('r') . ')';
$maxExecutionTime = ini_get('max_execution_time') . 's';
$memoryLimit = ini_get('memory_limit');
$currentMemoryUsage = round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB';
$dbVersion = 'Unknown';
try {
    $dbVersion = $db->getAttribute(PDO::ATTR_SERVER_VERSION);
} catch (Throwable $e) {}

$extensions = [
    'pdo' => extension_loaded('pdo'),
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'curl' => extension_loaded('curl'),
    'mbstring' => extension_loaded('mbstring'),
    'openssl' => extension_loaded('openssl'),
    'json' => extension_loaded('json'),
    'fileinfo' => extension_loaded('fileinfo'),
    'gd' => extension_loaded('gd')
];

$writablePaths = [
    'logs/' => is_writable(LOGS_DIR),
    'logs/error.log' => is_writable(ERROR_LOG_FILE) || is_writable(LOGS_DIR),
    'logs/query.log' => is_writable(QUERY_LOG_FILE) || is_writable(LOGS_DIR),
    'assets/images/' => is_writable(__DIR__ . '/../assets/images')
];

$errorLogSize = file_exists(ERROR_LOG_FILE) ? round(filesize(ERROR_LOG_FILE) / 1024, 2) . ' KB' : '0 KB';
$queryLogSize = file_exists(QUERY_LOG_FILE) ? round(filesize(QUERY_LOG_FILE) / 1024, 2) . ' KB' : '0 KB';

include_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        
        <!-- Top Stats Row -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card stat-card shadow-xs border-0">
                    <div class="stat-icon-wrapper bg-danger bg-opacity-10 text-danger">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <p class="stat-title">Error Log File</p>
                    <h4 class="stat-value text-danger mb-0"><?php echo count($errorLogs); ?> <span class="fs-6 text-muted font-weight-normal">(<?php echo $errorLogSize; ?>)</span></h4>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card stat-card shadow-xs border-0">
                    <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
                        <i class="fa-solid fa-database"></i>
                    </div>
                    <p class="stat-title">Query Log File</p>
                    <h4 class="stat-value text-primary mb-0"><?php echo count($queryLogs); ?> <span class="fs-6 text-muted font-weight-normal">(<?php echo $queryLogSize; ?>)</span></h4>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card stat-card shadow-xs border-0">
                    <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
                        <i class="fa-solid fa-server"></i>
                    </div>
                    <p class="stat-title">PHP Environment</p>
                    <h4 class="stat-value text-success mb-0">PHP <?php echo explode('-', $phpVersion)[0]; ?></h4>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card stat-card shadow-xs border-0">
                    <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
                        <i class="fa-solid fa-hard-drive"></i>
                    </div>
                    <p class="stat-title">MySQL Database</p>
                    <h4 class="stat-value text-info mb-0"><?php echo explode('-', $dbVersion)[0]; ?></h4>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <!-- Navigation Tabs -->
                    <ul class="nav nav-pills card-header-pills" id="logTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a href="?tab=errors" class="nav-link py-2 px-3 font-weight-600 <?php echo $currentTab === 'errors' ? 'active' : ''; ?>">
                                <i class="fa-solid fa-circle-exclamation me-1 text-danger"></i> Error Logs (<?php echo count($errorLogs); ?>)
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="?tab=queries" class="nav-link py-2 px-3 font-weight-600 <?php echo $currentTab === 'queries' ? 'active' : ''; ?>">
                                <i class="fa-solid fa-database me-1 text-primary"></i> Query Traces (<?php echo count($queryLogs); ?>)
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="?tab=diagnostics" class="nav-link py-2 px-3 font-weight-600 <?php echo $currentTab === 'diagnostics' ? 'active' : ''; ?>">
                                <i class="fa-solid fa-heart-pulse me-1 text-success"></i> Server Health & Diagnostics
                            </a>
                        </li>
                    </ul>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2">
                        <?php if ($currentTab === 'errors'): ?>
                            <a href="?action=download_error_log" class="btn btn-sm btn-outline-secondary font-weight-600">
                                <i class="fa-solid fa-download me-1"></i> Download Log
                            </a>
                            <a href="?action=clear_error_log" class="btn btn-sm btn-outline-danger font-weight-600" onclick="return confirm('Are you sure you want to clear all error logs?');">
                                <i class="fa-solid fa-trash-can me-1"></i> Clear Log
                            </a>
                        <?php elseif ($currentTab === 'queries'): ?>
                            <a href="?action=download_query_log" class="btn btn-sm btn-outline-secondary font-weight-600">
                                <i class="fa-solid fa-download me-1"></i> Download Log
                            </a>
                            <a href="?action=clear_query_log" class="btn btn-sm btn-outline-danger font-weight-600" onclick="return confirm('Are you sure you want to clear all query logs?');">
                                <i class="fa-solid fa-trash-can me-1"></i> Clear Log
                            </a>
                        <?php endif; ?>
                        <a href="?tab=<?php echo urlencode($currentTab); ?>" class="btn btn-sm btn-light border font-weight-600">
                            <i class="fa-solid fa-rotate me-1"></i> Refresh
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="card-body p-0">

                <?php if ($currentTab === 'errors'): ?>
                    <!-- ERROR LOGS TAB -->
                    <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="small text-muted">
                            Showing latest <strong><?php echo count($errorLogs); ?></strong> error entries from <code>logs/error.log</code>
                        </div>
                        <input type="text" id="errorSearchInput" class="form-control form-control-sm" placeholder="Filter errors by keyword..." style="max-width: 280px;" onkeyup="filterLogs('errorSearchInput', 'errorLogList')">
                    </div>

                    <?php if (empty($errorLogs)): ?>
                        <div class="text-center py-5">
                            <i class="fa-solid fa-circle-check text-success fs-1 mb-2"></i>
                            <h6 class="font-weight-700 text-dark">No Error Logs Recorded</h6>
                            <p class="text-muted small mb-0">System is running cleanly with zero logged errors.</p>
                        </div>
                    <?php else: ?>
                        <div class="p-3 bg-dark" style="max-height: 560px; overflow-y: auto; font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.82rem;" id="errorLogList">
                            <?php foreach ($errorLogs as $index => $log): 
                                $isFatal = (strpos($log, '[FATAL') !== false || strpos($log, '[DATABASE_CONNECTION_ERROR') !== false || strpos($log, '[UNCAUGHT_EXCEPTION') !== false);
                                $isWarning = (strpos($log, '[WARNING') !== false);
                                $isNotice = (strpos($log, '[NOTICE') !== false);
                                
                                $badgeClass = 'bg-danger';
                                if ($isWarning) $badgeClass = 'bg-warning text-dark';
                                elseif ($isNotice) $badgeClass = 'bg-info text-dark';
                            ?>
                                <div class="log-item p-2 mb-2 rounded bg-black bg-opacity-50 text-light border-start border-3 <?php echo $isFatal ? 'border-danger' : ($isWarning ? 'border-warning' : 'border-info'); ?>">
                                    <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-1">
                                        <span class="badge <?php echo $badgeClass; ?> font-weight-600 px-2 py-1">
                                            <?php 
                                            if (preg_match('/\[([A-Z0-9_]+)\]/i', $log, $m)) {
                                                echo htmlspecialchars($m[1]);
                                            } else {
                                                echo 'ERROR';
                                            }
                                            ?>
                                        </span>
                                        <span class="text-muted small">#<?php echo $index + 1; ?></span>
                                    </div>
                                    <div class="text-break text-light" style="line-height: 1.45;">
                                        <?php echo htmlspecialchars($log); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php elseif ($currentTab === 'queries'): ?>
                    <!-- QUERY LOGS TAB -->
                    <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="small text-muted">
                            Showing latest <strong><?php echo count($queryLogs); ?></strong> executed database queries from <code>logs/query.log</code>
                        </div>
                        <input type="text" id="querySearchInput" class="form-control form-control-sm" placeholder="Search SQL queries..." style="max-width: 280px;" onkeyup="filterLogs('querySearchInput', 'queryLogList')">
                    </div>

                    <?php if (empty($queryLogs)): ?>
                        <div class="text-center py-5">
                            <i class="fa-solid fa-database text-muted fs-1 mb-2"></i>
                            <h6 class="font-weight-700 text-dark">No Query Logs Found</h6>
                            <p class="text-muted small mb-0">Execute application actions to see live query execution traces.</p>
                        </div>
                    <?php else: ?>
                        <div class="p-3 bg-dark" style="max-height: 560px; overflow-y: auto; font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.82rem;" id="queryLogList">
                            <?php foreach ($queryLogs as $index => $qlog): 
                                $isError = (strpos($qlog, 'ERROR') !== false);
                                $isSlow = (strpos($qlog, '[Time:') !== false && (float)filter_var($qlog, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) > 100);
                            ?>
                                <div class="log-item p-2 mb-2 rounded bg-black bg-opacity-50 text-light border-start border-3 <?php echo $isError ? 'border-danger' : ($isSlow ? 'border-warning' : 'border-success'); ?>">
                                    <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-1">
                                        <span class="badge <?php echo $isError ? 'bg-danger' : ($isSlow ? 'bg-warning text-dark' : 'bg-success'); ?> font-weight-600 px-2 py-1">
                                            <?php echo $isError ? 'SQL ERROR' : ($isSlow ? 'SLOW QUERY' : 'SQL QUERY'); ?>
                                        </span>
                                        <span class="text-muted small">#<?php echo $index + 1; ?></span>
                                    </div>
                                    <div class="text-break text-light" style="line-height: 1.45;">
                                        <?php echo htmlspecialchars($qlog); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php elseif ($currentTab === 'diagnostics'): ?>
                    <!-- SERVER HEALTH & DIAGNOSTICS TAB -->
                    <div class="p-4">
                        <div class="row g-4">
                            
                            <!-- Environment Info -->
                            <div class="col-12 col-md-6">
                                <h6 class="font-weight-700 text-dark border-bottom pb-2 mb-3">
                                    <i class="fa-solid fa-microchip text-primary me-2"></i> Server Environment
                                </h6>
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td class="text-muted" style="width: 45%;">PHP Version:</td>
                                        <td class="font-weight-600 text-dark"><?php echo htmlspecialchars($phpVersion); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Web Server:</td>
                                        <td class="font-weight-600 text-dark"><?php echo htmlspecialchars($serverSoftware); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Operating System:</td>
                                        <td class="font-weight-600 text-dark"><?php echo htmlspecialchars($os); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">MySQL Server Version:</td>
                                        <td class="font-weight-600 text-dark"><?php echo htmlspecialchars($dbVersion); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Active Memory Usage:</td>
                                        <td class="font-weight-600 text-dark"><?php echo htmlspecialchars($currentMemoryUsage); ?> / <?php echo htmlspecialchars($memoryLimit); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Max Execution Time:</td>
                                        <td class="font-weight-600 text-dark"><?php echo htmlspecialchars($maxExecutionTime); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Server Time:</td>
                                        <td class="font-weight-600 text-dark"><?php echo date('Y-m-d H:i:s T'); ?></td>
                                    </tr>
                                </table>
                            </div>

                            <!-- PHP Extensions Check -->
                            <div class="col-12 col-md-6">
                                <h6 class="font-weight-700 text-dark border-bottom pb-2 mb-3">
                                    <i class="fa-solid fa-puzzle-piece text-success me-2"></i> Required PHP Extensions
                                </h6>
                                <div class="row g-2">
                                    <?php foreach ($extensions as $ext => $loaded): ?>
                                        <div class="col-6">
                                            <div class="p-2 rounded bg-light border d-flex align-items-center justify-content-between">
                                                <span class="small font-weight-600 font-monospace"><?php echo htmlspecialchars($ext); ?></span>
                                                <?php if ($loaded): ?>
                                                    <span class="badge bg-success bg-opacity-10 text-success"><i class="fa-solid fa-check"></i> Loaded</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger bg-opacity-10 text-danger"><i class="fa-solid fa-xmark"></i> Missing</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <h6 class="font-weight-700 text-dark border-bottom pb-2 mb-3 mt-4">
                                    <i class="fa-solid fa-folder-open text-warning me-2"></i> Directory Permissions
                                </h6>
                                <div class="row g-2">
                                    <?php foreach ($writablePaths as $path => $isWritable): ?>
                                        <div class="col-6">
                                            <div class="p-2 rounded bg-light border d-flex align-items-center justify-content-between">
                                                <span class="small font-weight-600 font-monospace text-truncate" style="max-width: 130px;"><?php echo htmlspecialchars($path); ?></span>
                                                <?php if ($isWritable): ?>
                                                    <span class="badge bg-success bg-opacity-10 text-success"><i class="fa-solid fa-pen"></i> Writable</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger bg-opacity-10 text-danger"><i class="fa-solid fa-lock"></i> Read-only</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>

    </div>
</div>

<script>
function filterLogs(inputId, containerId) {
    var filter = document.getElementById(inputId).value.toLowerCase();
    var container = document.getElementById(containerId);
    var items = container.getElementsByClassName('log-item');
    
    for (var i = 0; i < items.length; i++) {
        var text = items[i].textContent || items[i].innerText;
        if (text.toLowerCase().indexOf(filter) > -1) {
            items[i].style.display = "";
        } else {
            items[i].style.display = "none";
        }
    }
}
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
