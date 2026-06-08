<?php

/**
 * Laravel router for PHP built-in server (Railway deployment).
 * Serves static files directly from public/, routes everything else through index.php
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Map URI to actual file inside public/
$staticFile = __DIR__ . '/public' . $uri;

if ($uri !== '/' && file_exists($staticFile) && !is_dir($staticFile)) {
    $ext = strtolower(pathinfo($staticFile, PATHINFO_EXTENSION));

    $mimeTypes = [
        'css'   => 'text/css',
        'js'    => 'application/javascript',
        'mjs'   => 'application/javascript',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'gif'   => 'image/gif',
        'svg'   => 'image/svg+xml',
        'ico'   => 'image/x-icon',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
        'otf'   => 'font/otf',
        'eot'   => 'application/vnd.ms-fontobject',
        'webp'  => 'image/webp',
        'pdf'   => 'application/pdf',
        'map'   => 'application/json',
        'json'  => 'application/json',
        'txt'   => 'text/plain',
        'xml'   => 'application/xml',
    ];

    if (isset($mimeTypes[$ext])) {
        header('Content-Type: ' . $mimeTypes[$ext]);
    }

    // Read and output the file directly (NOT return false — that uses wrong root)
    readfile($staticFile);
    exit;
}

// All other requests → Laravel
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/public/index.php';
$_SERVER['DOCUMENT_ROOT']   = __DIR__ . '/public';
require __DIR__ . '/public/index.php';
