<?php
// Temporary deploy helper - REMOVE AFTER USE
// Secret: rk_deploy_helper_2026

$secret = $_GET['secret'] ?? $_SERVER['HTTP_X_SECRET'] ?? '';
if ($secret !== 'rk_deploy_helper_2026') {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Forbidden']));
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'ping';

if ($action === 'ping') {
    echo json_encode(['status' => 'ok', 'time' => date('Y-m-d H:i:s'), 'base_path' => dirname(__DIR__)]);
    exit;
}

if ($action === 'upload') {
    $relativePath = $_POST['path'] ?? '';
    $content = $_POST['content'] ?? ''; // base64 encoded
    
    if (!$relativePath || !$content) {
        echo json_encode(['status' => 'error', 'message' => 'path and content required']);
        exit;
    }
    
    $basePath = dirname(__DIR__);
    $fullPath = $basePath . '/' . ltrim($relativePath, '/');
    $dir = dirname($fullPath);
    
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $decoded = base64_decode($content);
    $result = file_put_contents($fullPath, $decoded);
    
    if ($result === false) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to write file', 'path' => $fullPath]);
    } else {
        echo json_encode(['status' => 'success', 'path' => $relativePath, 'bytes' => $result]);
    }
    exit;
}

if ($action === 'clear_cache') {
    $basePath = dirname(__DIR__);
    $dirs = [
        $basePath . '/storage/framework/cache/data',
        $basePath . '/storage/framework/views',
        $basePath . '/bootstrap/cache',
    ];
    $cleared = [];
    foreach ($dirs as $dir) {
        if (is_dir($dir)) {
            $files = glob($dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                    $cleared[] = $file;
                }
            }
        }
    }
    
    // Also delete compiled route and config files
    $cacheFiles = [
        $basePath . '/bootstrap/cache/routes-v7.php',
        $basePath . '/bootstrap/cache/config.php',
        $basePath . '/bootstrap/cache/packages.php',
        $basePath . '/bootstrap/cache/services.php',
    ];
    foreach ($cacheFiles as $f) {
        if (file_exists($f)) {
            unlink($f);
            $cleared[] = $f;
        }
    }
    
    echo json_encode(['status' => 'success', 'cleared' => count($cleared), 'files' => $cleared]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Unknown action: ' . $action]);
