<?php
// Temporary cache clearing script for production
// Upload this to your public_html/ directory and visit it in browser
// DELETE THIS FILE after use for security!

// Change to your Laravel root directory
chdir(__DIR__ . '/../');

// Clear view cache
if (is_dir('storage/framework/views')) {
    $files = glob('storage/framework/views/*');
    foreach($files as $file) {
        if(is_file($file)) {
            unlink($file);
        }
    }
    echo "✅ View cache cleared<br>";
} else {
    echo "❌ Views directory not found<br>";
}

// Clear config cache
if (file_exists('bootstrap/cache/config.php')) {
    unlink('bootstrap/cache/config.php');
    echo "✅ Config cache cleared<br>";
} else {
    echo "ℹ️ No config cache found<br>";
}

// Clear route cache
if (file_exists('bootstrap/cache/routes-v7.php')) {
    unlink('bootstrap/cache/routes-v7.php');
    echo "✅ Route cache cleared<br>";
} else {
    echo "ℹ️ No route cache found<br>";
}

// Clear compiled services
if (file_exists('bootstrap/cache/services.php')) {
    unlink('bootstrap/cache/services.php');
    echo "✅ Services cache cleared<br>";
} else {
    echo "ℹ️ No services cache found<br>";
}

echo "<br>🎉 Cache clearing completed!<br>";
echo "<br>⚠️ <strong>IMPORTANT: Delete this file now for security!</strong>";
?>
