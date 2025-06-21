<?php
/**
 * Docker Test File
 * This file helps verify that the Docker setup is working correctly
 */

// Test PHP version and extensions
echo "<h1>🐳 Blood Donation App - Docker Test</h1>";
echo "<h2>PHP Information</h2>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";

// Test required extensions
$required_extensions = [
    'curl',
    'json',
    'mbstring',
    'pdo',
    'pdo_mysql',
    'gd',
    'zip'
];

echo "<h3>Required Extensions:</h3>";
echo "<ul>";
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<li>✅ $ext - <span style='color: green;'>Loaded</span></li>";
    } else {
        echo "<li>❌ $ext - <span style='color: red;'>Not Loaded</span></li>";
    }
}
echo "</ul>";

// Test database connection
echo "<h2>Database Connection Test</h2>";
if (file_exists('mobile-app/config/database.php')) {
    require_once 'mobile-app/config/database.php';
    
    // Test Supabase connection
    $test_url = SUPABASE_URL . '/rest/v1/';
    $ch = curl_init($test_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . SUPABASE_API_KEY,
        'Authorization: Bearer ' . SUPABASE_API_KEY
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        echo "<p>✅ <strong>Supabase Connection:</strong> <span style='color: green;'>Success</span></p>";
    } else {
        echo "<p>❌ <strong>Supabase Connection:</strong> <span style='color: red;'>Failed (HTTP $http_code)</span></p>";
    }
} else {
    echo "<p>❌ <strong>Database Config:</strong> <span style='color: red;'>File not found</span></p>";
}

// Test file permissions
echo "<h2>File Permissions Test</h2>";
$test_dirs = [
    'mobile-app/templates/forms',
    'mobile-app/config',
    'images'
];

foreach ($test_dirs as $dir) {
    if (is_dir($dir)) {
        if (is_readable($dir)) {
            echo "<p>✅ <strong>$dir:</strong> <span style='color: green;'>Readable</span></p>";
        } else {
            echo "<p>❌ <strong>$dir:</strong> <span style='color: red;'>Not Readable</span></p>";
        }
        
        if (is_writable($dir)) {
            echo "<p>✅ <strong>$dir:</strong> <span style='color: green;'>Writable</span></p>";
        } else {
            echo "<p>❌ <strong>$dir:</strong> <span style='color: red;'>Not Writable</span></p>";
        }
    } else {
        echo "<p>❌ <strong>$dir:</strong> <span style='color: red;'>Directory not found</span></p>";
    }
}

// Test Apache modules
echo "<h2>Apache Modules Test</h2>";
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    $required_modules = ['mod_rewrite', 'mod_headers'];
    
    foreach ($required_modules as $module) {
        if (in_array($module, $modules)) {
            echo "<p>✅ <strong>$module:</strong> <span style='color: green;'>Loaded</span></p>";
        } else {
            echo "<p>❌ <strong>$module:</strong> <span style='color: red;'>Not Loaded</span></p>";
        }
    }
} else {
    echo "<p>⚠️ <strong>Apache Modules:</strong> <span style='color: orange;'>Cannot check (function not available)</span></p>";
}

// Test PWA files
echo "<h2>PWA Files Test</h2>";
$pwa_files = [
    'manifest.json',
    'service-worker.js',
    'offline.html'
];

foreach ($pwa_files as $file) {
    if (file_exists($file)) {
        echo "<p>✅ <strong>$file:</strong> <span style='color: green;'>Found</span></p>";
    } else {
        echo "<p>❌ <strong>$file:</strong> <span style='color: red;'>Not Found</span></p>";
    }
}

// Test mobile app files
echo "<h2>Mobile App Files Test</h2>";
$app_files = [
    'mobile-app/index.php',
    'mobile-app/config/database.php',
    'mobile-app/templates/forms/donor-form-modal.php',
    'mobile-app/templates/forms/medical-history-modal.php'
];

foreach ($app_files as $file) {
    if (file_exists($file)) {
        echo "<p>✅ <strong>$file:</strong> <span style='color: green;'>Found</span></p>";
    } else {
        echo "<p>❌ <strong>$file:</strong> <span style='color: red;'>Not Found</span></p>";
    }
}

// Environment information
echo "<h2>Environment Information</h2>";
echo "<p><strong>Server Software:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";
echo "<p><strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</p>";
echo "<p><strong>Current Directory:</strong> " . getcwd() . "</p>";

// Docker information
echo "<h2>Docker Information</h2>";
if (file_exists('/.dockerenv')) {
    echo "<p>✅ <strong>Running in Docker:</strong> <span style='color: green;'>Yes</span></p>";
} else {
    echo "<p>⚠️ <strong>Running in Docker:</strong> <span style='color: orange;'>No (or /.dockerenv not accessible)</span></p>";
}

echo "<hr>";
echo "<p><strong>Test completed at:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><a href='mobile-app/'>📱 Go to Mobile App</a></p>";
echo "<p><a href='mobile-app/templates/forms/donor-form-modal.php'>🩸 Go to Donor Form</a></p>";
?> 