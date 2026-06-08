<?php
// Test file to check if web server can access this directory
header('Content-Type: text/plain');
echo "Test successful!\n";
echo "Current directory: " . __DIR__ . "\n";

// Check if APCu is enabled
echo "APCu enabled: " . (extension_loaded('apcu') ? 'Yes' : 'No') . "\n";

// Check if we can write to the directory
$testFile = __DIR__ . '/test_write.txt';
$testContent = 'test';
$writeResult = file_put_contents($testFile, $testContent);
$writeStatus = ($writeResult !== false) ? 'Yes' : 'No';
@unlink($testFile);

echo "Can write to directory: $writeStatus\n";

// Show PHP info
// phpinfo(); // Uncomment if needed for debugging


