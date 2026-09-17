<?php
/**
 * Live Session & Server Health Diagnostics
 */
require_once __DIR__ . '/config/database.php';

$savePath = session_save_path() ?: sys_get_temp_dir();
$isWritable = is_writable($savePath);

// Counter increment
if (!isset($_SESSION['test_counter'])) {
    $_SESSION['test_counter'] = 1;
} else {
    $_SESSION['test_counter']++;
}

$sessionId = session_id();
$cookieParams = session_get_cookie_params();
$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Persistence Test</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 30px; background: #f8fafc; color: #1e293b; }
        .card { max-width: 650px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
        h2 { margin-top: 0; color: #0f172a; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 13px; }
        .badge-success { background: #dcfce7; color: #15803d; }
        .badge-danger { background: #fee2e2; color: #b91c1c; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        th { color: #64748b; font-weight: 600; }
        .btn { display: inline-block; background: #059669; color: #fff; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; margin-top: 15px; }
        .btn:hover { background: #047857; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Live Session Test</h2>
        <p>Click the button below. If the counter increases on every click, sessions are working.</p>

        <table>
            <tr>
                <th>Session Counter</th>
                <td><strong style="font-size: 18px; color: #059669;"><?php echo $_SESSION['test_counter']; ?></strong> (Refreshes: <?php echo $_SESSION['test_counter']; ?>)</td>
            </tr>
            <tr>
                <th>Session ID</th>
                <td><code><?php echo htmlspecialchars($sessionId); ?></code></td>
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
                        <span class="badge badge-danger">NO (Permission Denied / Read Only!)</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Cookie Lifetime</th>
                <td><?php echo $cookieParams['lifetime']; ?> seconds</td>
            </tr>
            <tr>
                <th>Cookie Path</th>
                <td><code><?php echo htmlspecialchars($cookieParams['path']); ?></code></td>
            </tr>
            <tr>
                <th>Cookie Secure Flag</th>
                <td><?php echo $cookieParams['secure'] ? 'TRUE (HTTPS Only)' : 'FALSE'; ?></td>
            </tr>
            <tr>
                <th>Cookie SameSite</th>
                <td><?php echo htmlspecialchars($cookieParams['samesite'] ?? 'None'); ?></td>
            </tr>
            <tr>
                <th>Logged In User ID</th>
                <td><?php echo isset($_SESSION['user_id']) ? "User #" . htmlspecialchars((string)$_SESSION['user_id']) . " (" . htmlspecialchars($_SESSION['role'] ?? '') . ")" : "<span style='color:#ef4444;'>Not Logged In in this session</span>"; ?></td>
            </tr>
        </table>

        <a href="test_session.php" class="btn">Click to Test Session Counter (+1)</a>
        <a href="index.php" class="btn" style="background:#475569; margin-left: 10px;">Go to Login Page</a>
    </div>
</body>
</html>
