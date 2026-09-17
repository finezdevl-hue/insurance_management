<?php
/**
 * Live Session & Server Health Diagnostics with In-Page Log Inspector
 */
require_once __DIR__ . '/config/database.php';

$savePath = session_save_path() ?: sys_get_temp_dir();
$isWritable = is_writable($savePath);

// Check previous counter value before modifying
$counterBefore = $_SESSION['test_counter'] ?? '(not set)';

// Increment counter
if (!isset($_SESSION['test_counter'])) {
    $_SESSION['test_counter'] = 1;
} else {
    $_SESSION['test_counter']++;
}
$counterAfter = $_SESSION['test_counter'];

$sessionId = session_id();
$cookieParams = session_get_cookie_params();
$serverTime = date('Y-m-d H:i:s');
$microtime = microtime(true);

// Action: Simulate Login
$actionMessage = '';
if (isset($_POST['action']) && $_POST['action'] === 'simulate_login') {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['role'] = 'admin';
    $_SESSION['email'] = 'admin@finez.in';
    $actionMessage = "Simulated Super Admin Login set in session! Now try opening the Admin Dashboard.";
}

if (isset($_POST['action']) && $_POST['action'] === 'clear_session') {
    $_SESSION = [];
    $actionMessage = "Session data cleared.";
}

// Write to debug log file
$debugLogFile = __DIR__ . '/logs/session_debug.log';
$logEntry = sprintf(
    "[%s] IP: %s | Method: %s | SessionID: %s | CookieReceived: %s | CounterBefore: %s | CounterAfter: %s | UserID: %s\n",
    $serverTime,
    $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    $sessionId,
    $_COOKIE['PHPSESSID'] ?? '(NONE)',
    $counterBefore,
    $counterAfter,
    $_SESSION['user_id'] ?? '(Guest)'
);
@file_put_contents($debugLogFile, $logEntry, FILE_APPEND | LOCK_EX);

// Read last 15 log lines
$recentLogs = [];
if (file_exists($debugLogFile)) {
    $lines = file($debugLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $recentLogs = array_slice($lines, -15);
}

// Session file inspection on disk
$sessionFile = rtrim($savePath, '/\\') . '/sess_' . $sessionId;
$fileExists = file_exists($sessionFile);
$fileSize = $fileExists ? filesize($sessionFile) : 0;
$fileContent = $fileExists ? @file_get_contents($sessionFile) : 'File not on disk';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Live Session Diagnostics</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 25px; background: #f8fafc; color: #1e293b; line-height: 1.5; }
        .card { max-width: 780px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; margin-bottom: 20px; }
        h2, h3 { margin-top: 0; color: #0f172a; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 13px; }
        .badge-success { background: #dcfce7; color: #15803d; }
        .badge-danger { background: #fee2e2; color: #b91c1c; }
        .badge-info { background: #e0f2fe; color: #0369a1; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        th { color: #64748b; font-weight: 600; width: 35%; }
        .btn { display: inline-block; background: #059669; color: #fff; padding: 9px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; border: none; cursor: pointer; }
        .btn:hover { opacity: 0.9; }
        .btn-blue { background: #2563eb; }
        .btn-purple { background: #7c3aed; }
        .btn-gray { background: #475569; }
        .btn-red { background: #dc2626; }
        .btn-group { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 15px; }
        .log-box { background: #0f172a; color: #38bdf8; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 12px; overflow-x: auto; max-height: 250px; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 15px; font-weight: 500; font-size: 14px; }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Live Session Diagnostics</h2>
        <p style="color: #64748b; font-size: 14px; margin-top: -5px;">Page Generated: <strong><?php echo $serverTime; ?></strong> (Microtime: <code><?php echo $microtime; ?></code>)</p>

        <?php if (!empty($actionMessage)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($actionMessage); ?></div>
        <?php endif; ?>

        <table>
            <tr>
                <th>Session Counter</th>
                <td><strong style="font-size: 24px; color: #059669;"><?php echo $counterAfter; ?></strong></td>
            </tr>
            <tr>
                <th>Active Session ID</th>
                <td><code><?php echo htmlspecialchars($sessionId); ?></code></td>
            </tr>
            <tr>
                <th>Received PHPSESSID Cookie</th>
                <td>
                    <?php if (isset($_COOKIE['PHPSESSID'])): ?>
                        <span class="badge badge-success">RECEIVED: <?php echo htmlspecialchars($_COOKIE['PHPSESSID']); ?></span>
                    <?php else: ?>
                        <span class="badge badge-danger">NONE (Browser is not sending cookie)</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Logged In User</th>
                <td>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span class="badge badge-success">LOGGED IN: User #<?php echo htmlspecialchars((string)$_SESSION['user_id']); ?> (Role: <?php echo htmlspecialchars($_SESSION['role'] ?? ''); ?>)</span>
                    <?php else: ?>
                        <span class="badge badge-danger">GUEST (Not logged in)</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Session Save Path</th>
                <td><code><?php echo htmlspecialchars($savePath); ?></code></td>
            </tr>
            <tr>
                <th>Save Path Writable?</th>
                <td>
                    <?php if ($isWritable): ?>
                        <span class="badge badge-success">YES (Writable)</span>
                    <?php else: ?>
                        <span class="badge badge-danger">NO (Permission Denied)</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Cookie Config</th>
                <td>Path: <code><?php echo htmlspecialchars($cookieParams['path']); ?></code> | Secure: <?php echo $cookieParams['secure'] ? 'TRUE' : 'FALSE'; ?> | SameSite: <?php echo htmlspecialchars($cookieParams['samesite'] ?? 'None'); ?></td>
            </tr>
        </table>

        <div class="btn-group">
            <a href="test_session.php?rand=<?php echo microtime(true); ?>" class="btn">Test via GET (+1 Counter)</a>
            <form action="test_session.php" method="POST" style="margin:0;">
                <button type="submit" class="btn btn-blue">Test via POST (Never Cached)</button>
            </form>
            <form action="test_session.php" method="POST" style="margin:0;">
                <input type="hidden" name="action" value="simulate_login">
                <button type="submit" class="btn btn-purple">Simulate Admin Login</button>
            </form>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="admin/index.php" class="btn btn-gray" target="_blank">Open Admin Dashboard &rarr;</a>
                <a href="admin/agents.php" class="btn btn-gray" target="_blank">Open Agents Page &rarr;</a>
            <?php endif; ?>
            <form action="test_session.php" method="POST" style="margin:0;">
                <input type="hidden" name="action" value="clear_session">
                <button type="submit" class="btn btn-red">Clear Session</button>
            </form>
        </div>
    </div>

    <div class="card">
        <h3>Server-Side Session Trace Log (Live from Disk)</h3>
        <p style="color: #64748b; font-size: 13px;">This shows every request recorded directly on the server disk.</p>
        <div class="log-box">
            <?php if (!empty($recentLogs)): ?>
                <?php foreach ($recentLogs as $log): ?>
                    <div><?php echo htmlspecialchars($log); ?></div>
                <?php endforeach; ?>
            <?php else: ?>
                <div>No log entries yet. Refresh the page to record first entry.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
