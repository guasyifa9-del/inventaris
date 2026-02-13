<?php
if (!isset($_SESSION)) {
    session_start();
}

$page_title = $page_title ?? 'Inventaris Kantor';
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Inventaris Kantor - Kelola barang kantor dengan mudah">
    <meta name="author" content="Inventaris Kantor V3">
    <title><?= htmlspecialchars($page_title) ?> - Inventaris Kantor</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/inventaris/assets/images/favicon.png">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/inventaris/manifest.json">
    
    <!-- PWA Meta Tags -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Inventaris">
    <meta name="theme-color" content="#06b6d4">
    
    <!-- PWA Icons -->
    <link rel="apple-touch-icon" href="/inventaris/assets/images/icon-192.png">
    <link rel="apple-touch-icon" sizes="192x192" href="/inventaris/assets/images/icon-192.png">
    <link rel="apple-touch-icon" sizes="512x512" href="/inventaris/assets/images/icon-512.png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/inventaris/assets/css/style.css">
    
    <!-- Additional CSS -->
    <?php if (isset($additional_css)): ?>
        <?= $additional_css ?>
    <?php endif; ?>
    
    <style>
        /* Page-specific styles can be added here */
    </style>
</head>
<body>
