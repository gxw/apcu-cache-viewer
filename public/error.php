<?php
header('Content-Type: text/html; charset=utf-8');

$statusCode = http_response_code();
$statusText = '';
$message = $message ?? 'An error occurred';

switch ($statusCode) {
    case 404:
        $statusText = 'Not Found';
        $message = 'The requested page could not be found.';
        break;
    case 500:
    default:
        $statusCode = 500;
        $statusText = 'Internal Server Error';
        $message = 'An error occurred while processing your request.';
        break;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error <?= $statusCode ?> - APCu Cache Viewer</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        h1 {
            color: #dc3545;
            margin-top: 0;
        }
        .error-container {
            background: #f8f9fa;
            border-left: 4px solid #dc3545;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .error-details {
            margin-top: 1rem;
            padding: 1rem;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-family: monospace;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 1rem;
        }
        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>Error <?= $statusCode ?>: <?= htmlspecialchars($statusText) ?></h1>
        <p><?= nl2br(htmlspecialchars($message)) ?></p>
        
        <?php if (isset($error) && $error instanceof Throwable && ini_get('display_errors')): ?>
        <div class="error-details">
            <strong><?= get_class($error) ?>:</strong> <?= htmlspecialchars($error->getMessage()) ?>
            
            <div style="margin-top: 1rem;">
                <strong>Stack trace:</strong>
                <div style="margin-top: 0.5rem;">
                    <?= nl2br(htmlspecialchars($error->getTraceAsString())) ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <a href="/apcu/" class="btn">Go to Dashboard</a>
    </div>
</body>
</html>

