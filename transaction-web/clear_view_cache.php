<?php
// Script to clear Laravel view cache
$viewCacheDir = __DIR__ . '/storage/framework/views/';

if (is_dir($viewCacheDir)) {
    $files = glob($viewCacheDir . '*.php');
    
    foreach ($files as $file) {
        if (is_file($file) && basename($file) !== '.gitignore') {
            unlink($file);
            echo "Deleted: " . basename($file) . "\n";
        }
    }
    
    echo "View cache cleared successfully!\n";
    echo "Total files deleted: " . count($files) . "\n";
} else {
    echo "View cache directory not found.\n";
}
?>
