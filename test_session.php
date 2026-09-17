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
// Session file inspection
$sessionFile = rtrim($savePath, '/\\') . '/sess_' . $sessionId;
$fileExists = file_exists($sessionFile);
$fileSize = $fileExists ? filesize($sessionFile) : 0;
$fileContent = $fileExists ? @file_get_contents($sessionFile) : 'File not created yet';
$serverTime = date('Y-m-d H:i:s') . ' (Microtime: ' . microtime(true) . ')';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Persistence Test</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 30px; background: #f8fafc; color: #1e293b; }
        .card { max-width: 680px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
        h2 { margin-top: 0; color: #0f172a; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 13px; }
        .badge-success { background: #dcfce7; color: #15803d; }
        .badge-danger { background: #fee2e2; color: #b91c1c; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        th { color: #64748b; font-weight: 600; }
        .btn { display: inline-block; background: #059669; color: #fff; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; margin-top: 15px; border: none; cursor: pointer; }
        .btn:hover { background: #047857; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Live Session Diagnostics</h2>
        <p>Live Server Generated Time: <strong><?php echo $serverTime; ?></strong></p>

        <table>
            <tr>
                <th>Session Counter</th>
                <td><strong style="font-size: 24px; color: #059669;"><?php echo $_SESSION['test_counter']; ?></strong></td>
            </tr>
            <tr>
                <th>Active Session ID</th>
                <td><code><?php echo htmlspecialchars($sessionId); ?></code></td>
            </tr>
            <tr>
                <th>Received Cookie</th>
                <td>
                    <?php if (isset($_COOKIE['PHPSESSID'])): ?>
                        <span class="badge badge-success">RECEIVED: <?php echo htmlspecialchars($_COOKIE['PHPSESSID']); ?></span>
                    <?php else: ?>
                        <span class="badge badge-danger">NONE (Browser is not sending cookie)</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Session File on Disk</th>
                <td>
                    <?php if ($fileExists): ?>
                        <span class="badge badge-success">EXISTS (<?php echo $fileSize; ?> bytes)</span>
                        <div style="margin-top:5px;font-size:12px;color:#64748b;">Content: <code><?php echo htmlspecialchars($fileContent); ?></code></div>
                    <?php else: ?>
                        <span class="badge badge-danger">FILE DOES NOT EXIST ON DISK</span>
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
                <th>Cookie Parameters</th>
                <td>Path: <code><?php echo htmlspecialchars($cookieParams['path']); ?></code> | Secure: <?php echo $cookieParams['secure'] ? 'TRUE' : 'FALSE'; ?> | SameSite: <?php echo htmlspecialchars($cookieParams['samesite'] ?? 'None'); ?></td>
            </tr>
        </table>

        <div style="display:flex;gap:10px;margin-top:15px;flex-wrap:wrap;">
            <a href="test_session.php?rand=<?php echo microtime(true); ?>" class="btn">Test via GET (Bypass Cache)</a>
            <form action="test_session.php" method="POST" style="margin:0;">
                <button type="submit" class="btn" style="background:#2563eb;">Test via POST (Never Cached)</button>
            </form>
            <a href="index.php" class="btn" style="background:#475569;">Go to Login</a>
        </div>
    </div>
</body>
</html>
