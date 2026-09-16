<?php
/**
 * Global Error Logging and Query Logging Engine
 * Vehicle Details & Insurance Renewal Management System
 */

// Define Log Paths
define('LOGS_DIR', __DIR__ . '/../logs');
define('ERROR_LOG_FILE', LOGS_DIR . '/error.log');
define('QUERY_LOG_FILE', LOGS_DIR . '/query.log');

// Ensure Logs Directory and Protection Exists
if (!is_dir(LOGS_DIR)) {
    @mkdir(LOGS_DIR, 0755, true);
}

// Protect logs directory from direct browser access via .htaccess
$htaccessPath = LOGS_DIR . '/.htaccess';
if (!file_exists($htaccessPath)) {
    $htaccessContent = "# Deny direct web access to log files\n<IfModule authz_core_module>\n    Require all denied\n</IfModule>\n<IfModule !authz_core_module>\n    Deny from all\n</IfModule>\n";
    @file_put_contents($htaccessPath, $htaccessContent);
}
$indexHtmlPath = LOGS_DIR . '/index.html';
if (!file_exists($indexHtmlPath)) {
    @file_put_contents($indexHtmlPath, '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>Directory access is forbidden.</h1></body></html>');
}

/**
 * Write a formatted entry to a log file safely
 * @param string $filePath
 * @param string $entry
 */
function writeLogEntry($filePath, $entry) {
    try {
        @file_put_contents($filePath, $entry . PHP_EOL, FILE_APPEND | LOCK_EX);
    } catch (Throwable $e) {
        // Fallback to error_log
        error_log($entry);
    }
}

/**
 * Log a PHP Error, Warning, or Custom Exception
 * @param string $level (FATAL, ERROR, WARNING, NOTICE, EXCEPTION)
 * @param string $message
 * @param string $file
 * @param int $line
 * @param array|string|null $trace
 * @return void
 */
function logSystemError($level, $message, $file = '', $line = 0, $trace = null) {
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
    $uri = $_SERVER['REQUEST_URI'] ?? (php_sapi_name() === 'cli' ? 'CLI Script' : 'Unknown');
    $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    $role = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';
    
    $cleanMessage = str_replace(["\r", "\n"], ' ', $message);
    $traceStr = '';
    if (!empty($trace)) {
        if (is_array($trace)) {
            $traceStr = json_encode($trace, JSON_UNESCAPED_SLASHES);
        } else {
            $traceStr = str_replace(["\r\n", "\r", "\n"], ' | ', (string)$trace);
        }
    }

    $logLine = sprintf(
        "[%s] [%s] [IP: %s] [User: #%d (%s)] [URI: %s %s] %s in %s on line %d%s",
        $timestamp,
        strtoupper($level),
        $ip,
        $userId,
        $role,
        $method,
        $uri,
        $cleanMessage,
        $file,
        $line,
        !empty($traceStr) ? " | Stack: " . $traceStr : ""
    );

    writeLogEntry(ERROR_LOG_FILE, $logLine);

    // Also attempt to log to database if database connection is available
    try {
        if (function_exists('getDBConnection')) {
            $db = @getDBConnection();
            if ($db instanceof PDO) {
                $stmt = $db->prepare("
                    INSERT INTO system_error_logs (level, message, file, line, request_uri, ip_address, user_id, stack_trace, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    substr(strtoupper($level), 0, 20),
                    $message,
                    $file,
                    $line,
                    substr($uri, 0, 255),
                    substr($ip, 0, 45),
                    $userId > 0 ? $userId : null,
                    !empty($traceStr) ? $traceStr : null
                ]);
            }
        }
    } catch (Throwable $dbErr) {
        // Silently ignore DB logging failures to prevent infinite loops
    }
}

/**
 * Log a Database Query with execution time and error status
 * @param string $sql
 * @param array $params
 * @param float $executionTimeMs
 * @param string|null $errorMessage
 * @return void
 */
function logDatabaseQuery($sql, $params = [], $executionTimeMs = 0, $errorMessage = null) {
    // Check if query logging is globally enabled in settings
    static $loggingEnabled = null;
    if ($loggingEnabled === null) {
        if (defined('ENABLE_QUERY_LOG') && ENABLE_QUERY_LOG === false) {
            $loggingEnabled = false;
        } else {
            $loggingEnabled = true;
        }
    }

    if (!$loggingEnabled && empty($errorMessage)) {
        return; // Skip successful query logging if disabled
    }

    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
    $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    
    $cleanSql = trim(preg_replace('/\s+/', ' ', $sql));
    $paramsStr = !empty($params) ? json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '[]';
    $status = empty($errorMessage) ? 'SUCCESS' : 'ERROR: ' . str_replace(["\r", "\n"], ' ', $errorMessage);

    $logLine = sprintf(
        "[%s] [%s] [Time: %.2fms] [User: #%d] [IP: %s] Query: %s | Params: %s | Status: %s",
        $timestamp,
        empty($errorMessage) ? 'QUERY' : 'QUERY_ERROR',
        $executionTimeMs,
        $userId,
        $ip,
        $cleanSql,
        $paramsStr,
        $status
    );

    writeLogEntry(QUERY_LOG_FILE, $logLine);

    // Also record query errors or slow queries to DB table if available
    try {
        if (!empty($errorMessage) || $executionTimeMs > 500) { // Log errors or queries taking >500ms
            if (function_exists('getDBConnection')) {
                $db = @getDBConnection();
                if ($db instanceof PDO) {
                    $stmt = $db->prepare("
                        INSERT INTO system_query_logs (query_sql, params_json, execution_time_ms, status, error_message, user_id, ip_address, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $cleanSql,
                        $paramsStr,
                        round($executionTimeMs, 2),
                        empty($errorMessage) ? 'slow' : 'error',
                        $errorMessage,
                        $userId > 0 ? $userId : null,
                        substr($ip, 0, 45)
                    ]);
                }
            }
        }
    } catch (Throwable $e) {
        // Silently ignore
    }
}

/**
 * Custom PHP Error Handler
 */
function customPhpErrorHandler($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }

    $level = 'ERROR';
    switch ($errno) {
        case E_ERROR:
        case E_USER_ERROR:
            $level = 'FATAL';
            break;
        case E_WARNING:
        case E_USER_WARNING:
        case E_CORE_WARNING:
        case E_COMPILE_WARNING:
            $level = 'WARNING';
            break;
        case E_NOTICE:
        case E_USER_NOTICE:
        case E_DEPRECATED:
        case E_USER_DEPRECATED:
            $level = 'NOTICE';
            break;
        default:
            $level = 'UNKNOWN (' . $errno . ')';
            break;
    }

    logSystemError($level, $errstr, $errfile, $errline);

    // Don't execute standard PHP error handler for handled non-fatals
    return true;
}

/**
 * Custom Exception Handler
 */
function customExceptionHandler(Throwable $exception) {
    logSystemError(
        'UNCAUGHT_EXCEPTION',
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    );

    // If headers not sent, render a clean error view instead of generic 500
    if (!headers_sent()) {
        http_response_code(500);
    }

    $isCli = (php_sapi_name() === 'cli');
    if ($isCli) {
        echo "\n[CRITICAL ERROR] " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine() . "\n";
        exit(1);
    }

    // Render user-friendly production error page if not already in debug mode
    renderFriendlyErrorPage($exception->getMessage(), $exception->getFile(), $exception->getLine());
    exit;
}

/**
 * Shutdown Function to Catch Fatal PHP Errors (E_PARSE, E_ERROR, etc.)
 */
function customShutdownHandler() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        logSystemError('FATAL_SHUTDOWN', $error['message'], $error['file'], $error['line']);
        
        if (!headers_sent()) {
            http_response_code(500);
        }

        if (php_sapi_name() !== 'cli') {
            renderFriendlyErrorPage($error['message'], $error['file'], $error['line']);
        }
    }
}

/**
 * Render a beautiful, informative error fallback screen
 */
function renderFriendlyErrorPage($message, $file, $line) {
    // Only display full details to logged in Super Admin or localhost
    $isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']);
    $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    $showDetails = $isLocal || $isAdmin || (defined('DEBUG_MODE') && DEBUG_MODE === true);

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Application Error | System Recovery</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
        <style>
            body { background-color: #0b1329; color: #f8fafc; font-family: system-ui, -apple-system, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 1.5rem; }
            .error-card { background: #111e38; border: 1px solid rgba(255,255,255,0.12); border-radius: 18px; max-width: 680px; width: 100%; padding: 2.5rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
            .error-icon { width: 64px; height: 64px; border-radius: 50%; background: rgba(239, 68, 68, 0.15); color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-bottom: 1.5rem; }
            .code-box { background: #060b18; border: 1px solid #1e293b; border-radius: 10px; padding: 1rem; font-family: monospace; font-size: 0.85rem; color: #f87171; word-break: break-word; }
            .btn-action { background: linear-gradient(135deg, #059669 0%, #10b981 100%); border: none; color: #fff; font-weight: 600; padding: 0.65rem 1.5rem; border-radius: 10px; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
            .btn-action:hover { background: #059669; color: #fff; }
        </style>
    </head>
    <body>
        <div class="error-card">
            <div class="error-icon">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h3 class="font-weight-700 text-white mb-2">System Notice / Service Recovery</h3>
            <p class="text-secondary mb-4">
                The application encountered an unexpected issue while processing your request. The event has been logged to the system error log for immediate inspection.
            </p>

            <?php if ($showDetails): ?>
                <div class="mb-4">
                    <h6 class="text-warning small text-uppercase font-weight-700 mb-2"><i class="fa-solid fa-bug me-1"></i> Technical Diagnostic Details:</h6>
                    <div class="code-box mb-2">
                        <strong>Error:</strong> <?php echo htmlspecialchars($message); ?><br>
                        <strong>Location:</strong> <?php echo htmlspecialchars($file); ?> on line <?php echo (int)$line; ?>
                    </div>
                    <div class="text-secondary small">
                        Log recorded in: <code>logs/error.log</code>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-dark bg-opacity-50 border-0 text-secondary small mb-4">
                    <i class="fa-solid fa-circle-info text-info me-1"></i> If you are the system administrator, please check <code>logs/error.log</code> or verify your database credentials in <code>config/database.php</code>.
                </div>
            <?php endif; ?>

            <div class="d-flex gap-2 flex-wrap">
                <a href="index.php" class="btn-action">
                    <i class="fa-solid fa-house"></i> Return to Home
                </a>
                <a href="javascript:location.reload();" class="btn btn-outline-secondary text-light">
                    <i class="fa-solid fa-rotate-right me-1"></i> Try Again
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
}

// Register Global Error Handlers
set_error_handler('customPhpErrorHandler');
set_exception_handler('customExceptionHandler');
register_shutdown_function('customShutdownHandler');
