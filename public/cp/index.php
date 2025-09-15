<?php
// Control Panel for SiApp Application Manager

// Load required dependencies
require_once dirname(__DIR__) . '/../config/AppManager.php';
require_once dirname(__DIR__) . '/../config/AuthManager.php';

// Ensure user is authenticated (should already be checked by router, but double-check)
AuthManager::requireAuth();

// Initialize AppManager
try {
    $appManager = new AppManager();
    $appData = $appManager->getAllApps();
    $stats = $appManager->getStats();
} catch (Exception $e) {
    error_log('Control Panel Error: ' . $e->getMessage());
    $appData = [];
    $stats = [
        'total_apps' => 0,
        'total_slugs' => 0,
        'active_templates' => 1,
        'last_updated' => null
    ];
}

// Helper function for compatibility
function getAllSlugs($data = null) {
    global $appManager;
    try {
        return $appManager->getAllSlugs();
    } catch (Exception $e) {
        return [];
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - SiApp Manager</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            line-height: 1.6;
            background: #f5f5f5;
        }
        .header {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header h1 {
            color: #333;
            margin: 0 0 10px 0;
        }
        .nav-link {
            display: inline-block;
            padding: 8px 16px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 10px;
        }
        .nav-link:hover {
            background: #5a6268;
        }
        .app-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: box-shadow 0.2s;
        }
        .app-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .app-name {
            font-size: 1.5em;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .app-slug {
            color: #666;
            font-family: monospace;
            background: #e9ecef;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.9em;
        }
        .app-actions {
            margin-top: 15px;
        }
        .btn {
            display: inline-block;
            margin: 5px 5px 5px 0;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #1e7e34;
        }
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        .btn-info:hover {
            background: #138496;
        }
        .routes-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .route-list {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }
        .route-item {
            margin: 10px 0;
            padding: 10px;
            background: white;
            border-radius: 4px;
            border-left: 4px solid #007bff;
        }
        .route-url {
            font-family: monospace;
            color: #495057;
            font-size: 0.9em;
        }
        .stats {
            display: flex;
            gap: 20px;
            margin: 20px 0;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            flex: 1;
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #007bff;
        }
        .stat-label {
            color: #6c757d;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <div>
                <h1 style="margin: 0;">🚀 Panel Admin SiApp</h1>
                <p style="margin: 5px 0 0 0;">Dashboard Manajemen Aplikasi Apps Script</p>
            </div>
            <div style="text-align: right;">
                <span style="color: #666; font-size: 0.9em;">Welcome, <?= htmlspecialchars(AuthManager::getUsername()) ?>!</span>
                <br>
                <a href="logout.php" style="color: #dc3545; text-decoration: none; font-size: 0.9em;">Logout</a>
            </div>
        </div>
        <div style="margin-top: 15px;">
            <a href="../" class="nav-link">← Kembali ke Beranda</a>
            <a href="manage.php" class="nav-link">➕ Kelola Aplikasi</a>
            <a href="../docs-apps-script.html" class="nav-link">Panduan Apps Script</a>
        </div>
    </div>

    <div class="stats">
        <div class="stat-card">
            <div class="stat-number"><?= $stats['total_apps'] ?></div>
            <div class="stat-label">Aplikasi Aktif</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $stats['total_slugs'] ?></div>
            <div class="stat-label">Rute Tersedia</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $stats['active_templates'] ?></div>
            <div class="stat-label">Template Aktif</div>
        </div>
    </div>

    <?php if ($appData): ?>
        <h2>📱 Aplikasi Terdaftar</h2>
        <?php foreach ($appData as $app): ?>
            <div class="app-card">
                <div class="app-name"><?= htmlspecialchars($app['APP_NAME']) ?></div>
                <p><strong>Short Name:</strong> <?= htmlspecialchars($app['APP_SHORT_NAME']) ?></p>
                <p><strong>Slug:</strong> <span class="app-slug"><?= htmlspecialchars($app['APP_SLUG']) ?></span></p>
                <?php if (isset($app['APP_URL'])): ?>
                    <p><strong>Source URL:</strong> <a href="<?= htmlspecialchars($app['APP_URL']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($app['APP_URL']) ?></a></p>
                <?php endif; ?>

                <div class="app-actions">
                    <a href="../<?= htmlspecialchars($app['APP_SLUG']) ?>" class="btn btn-primary" target="_blank">Lihat App</a>
                    <a href="../<?= htmlspecialchars($app['APP_SLUG']) ?>?format=json" class="btn btn-success" target="_blank">Data JSON</a>
                    <a href="../<?= htmlspecialchars($app['APP_SLUG']) ?>/manifest.json" class="btn btn-info" target="_blank">Manifest</a>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="routes-section">
            <h3>🗺️ Rute & Endpoint Tersedia</h3>
            <div class="route-list">
                <?php foreach (getAllSlugs($appData) as $slug): ?>
                    <div class="route-item">
                        <strong>Endpoint Aplikasi <?= htmlspecialchars($slug) ?>:</strong>
                        <div style="margin-top: 8px;">
                            <div class="route-url">📱 <strong>Aplikasi:</strong> <?= $_SERVER['HTTP_HOST'] ?>/<?= htmlspecialchars($slug) ?></div>
                            <div class="route-url">📄 <strong>Data JSON:</strong> <?= $_SERVER['HTTP_HOST'] ?>/<?= htmlspecialchars($slug) ?>?format=json</div>
                            <div class="route-url">⚙️ <strong>Service Worker:</strong> <?= $_SERVER['HTTP_HOST'] ?>/<?= htmlspecialchars($slug) ?>/sw.js</div>
                            <div class="route-url">📋 <strong>Manifest:</strong> <?= $_SERVER['HTTP_HOST'] ?>/<?= htmlspecialchars($slug) ?>/manifest.json</div>
                            <div class="route-url">🎨 <strong>Ikon:</strong> <?= $_SERVER['HTTP_HOST'] ?>/<?= htmlspecialchars($slug) ?>/icons/icon.svg</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="app-card">
            <div class="app-name">⚠️ Tidak Ada Aplikasi Ditemukan</div>
            <p>Belum ada aplikasi yang terdaftar saat ini. Silakan periksa file data.json atau tambahkan aplikasi baru.</p>
            <div class="app-actions">
                <a href="../data.json" class="btn btn-info" target="_blank">Lihat data.json</a>
            </div>
        </div>
    <?php endif; ?>

    <div class="routes-section">
        <h3>ℹ️ Informasi Sistem</h3>
        <p><strong>Sumber Data:</strong> data.json</p>
        <p><strong>Direktori Template:</strong> template/</p>
        <p><strong>Panel Admin:</strong> /cp/</p>
        <p><strong>Versi PHP:</strong> <?= PHP_VERSION ?></p>
    </div>
</body>
</html>
