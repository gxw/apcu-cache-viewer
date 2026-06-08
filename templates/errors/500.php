<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 Internal Server Error</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .error-template { padding: 40px 15px; text-align: center; }
        .error-actions { margin-top: 15px; margin-bottom: 15px; }
        .error-actions .btn { margin-right: 10px; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <div class="error-template">
                    <h1><i class="fas fa-exclamation-triangle text-danger"></i> Oops!</h1>
                    <h2>500 Internal Server Error</h2>
                    <div class="error-details alert alert-danger mt-4">
                        <?php 
                        $isDev = (getenv('APP_ENV') === 'development' || 
                                (isset($_SERVER['APP_ENV']) && $_SERVER['APP_ENV'] === 'development'));
                        
                        if ($isDev && isset($error)): 
                        ?>
                            <h4>Error Details:</h4>
                            <pre class="text-start"><?= htmlspecialchars(is_string($error) ? $error : print_r($error, true)) ?></pre>
                            <?php if (isset($trace)): ?>
                                <h5 class="mt-3">Stack Trace:</h5>
                                <pre class="text-start"><?= htmlspecialchars($trace) ?></pre>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="mb-0">Sorry, an unexpected error occurred. Our team has been notified.</p>
                            <p class="mb-0">Please try again later or contact support if the problem persists.</p>
                        <?php endif; ?>
                    </div>
                    <div class="error-actions">
                        <a href="/" class="btn btn-primary">
                            <i class="fas fa-home"></i> Take Me Home
                        </a>
                        <a href="javascript:history.back()" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Go Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
