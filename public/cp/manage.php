<?php
/**
 * Application Management UI for SiApp Control Panel
 */

require_once dirname(__DIR__) . '/../config/AppManager.php';
require_once dirname(__DIR__) . '/../config/AuthManager.php';

// Ensure user is authenticated
AuthManager::requireAuth();

// Initialize AppManager
$appManager = new AppManager();
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create':
            $appData = [
                'APP_NAME' => trim($_POST['app_name'] ?? ''),
                'APP_SLUG' => trim($_POST['app_slug'] ?? ''),
                'APP_SHORT_NAME' => trim($_POST['app_short_name'] ?? ''),
                'APP_URL' => trim($_POST['app_url'] ?? '')
            ];

            $result = $appManager->createApp($appData);
            if ($result) {
                $message = 'Aplikasi berhasil ditambahkan!';
                $messageType = 'success';
            } else {
                $message = 'Gagal menambahkan aplikasi. Periksa data dan coba lagi.';
                $messageType = 'error';
            }
            break;

        case 'update':
            $originalSlug = $_POST['original_slug'] ?? '';
            $appData = [
                'APP_NAME' => trim($_POST['app_name'] ?? ''),
                'APP_SLUG' => trim($_POST['app_slug'] ?? ''),
                'APP_SHORT_NAME' => trim($_POST['app_short_name'] ?? ''),
                'APP_URL' => trim($_POST['app_url'] ?? '')
            ];

            $result = $appManager->updateApp($originalSlug, $appData);
            if ($result) {
                $message = 'Aplikasi berhasil diperbarui!';
                $messageType = 'success';
            } else {
                $message = 'Gagal memperbarui aplikasi. Periksa data dan coba lagi.';
                $messageType = 'error';
            }
            break;

        case 'delete':
            $slug = $_POST['slug'] ?? '';
            $result = $appManager->deleteApp($slug);
            if ($result) {
                $message = 'Aplikasi berhasil dihapus!';
                $messageType = 'success';
            } else {
                $message = 'Gagal menghapus aplikasi.';
                $messageType = 'error';
            }
            break;
    }
}

// Get current applications
$apps = $appManager->getAllApps();
$stats = $appManager->getStats();

// Handle edit mode
$editApp = null;
if (isset($_GET['edit'])) {
    $editApp = $appManager->getAppBySlug($_GET['edit']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Aplikasi - SiApp Panel Admin</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            line-height: 1.6;
            background: #f5f5f5;
        }
        .header {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
        }
        .nav-link {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            margin: 5px;
        }
        .nav-link:hover {
            background: rgba(255,255,255,0.3);
        }
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: #22c55e;
            box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.1);
        }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #22c55e;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            margin-right: 0.5rem;
        }
        .btn:hover {
            background: #16a34a;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .app-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .app-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .app-table th, .app-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .app-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .app-table tr:hover {
            background: #f8f9fa;
        }
        .message {
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 4px;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
            color: #22c55e;
        }
        .stat-label {
            color: #6c757d;
            margin-top: 5px;
        }
        .form-help {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        @media (max-width: 768px) {
            .stats {
                flex-direction: column;
            }
            .app-table {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div style="position: absolute; top: 1rem; right: 2rem; text-align: right;">
            <span style="color: rgba(255,255,255,0.9); font-size: 0.9em;">Welcome, <?= htmlspecialchars(AuthManager::getUsername()) ?>!</span><br>
            <a href="logout.php" style="color: rgba(255,255,255,0.8); text-decoration: none; font-size: 0.9em;">Logout</a>
        </div>
        <h1>🚀 Kelola Aplikasi</h1>
        <p>Tambah, edit, dan hapus aplikasi Google Apps Script</p>
        <a href="../" class="nav-link">← Homepage</a>
        <a href="./" class="nav-link">Dashboard</a>
        <a href="../docs-apps-script.html" class="nav-link">Panduan Apps Script</a>
    </div>

    <?php if ($message): ?>
        <div class="message <?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="stats">
        <div class="stat-card">
            <div class="stat-number"><?= $stats['total_apps'] ?></div>
            <div class="stat-label">Total Aplikasi</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $stats['total_slugs'] ?></div>
            <div class="stat-label">URL Aktif</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $stats['last_updated'] ? date('d/m/Y', strtotime($stats['last_updated'])) : 'Belum ada' ?></div>
            <div class="stat-label">Terakhir Update</div>
        </div>
    </div>

    <div class="form-container">
        <h2><?= $editApp ? '✏️ Edit Aplikasi' : '➕ Tambah Aplikasi Baru' ?></h2>
        <form method="POST">
            <input type="hidden" name="action" value="<?= $editApp ? 'update' : 'create' ?>">
            <?php if ($editApp): ?>
                <input type="hidden" name="original_slug" value="<?= htmlspecialchars($editApp['APP_SLUG']) ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="app_name">Nama Aplikasi</label>
                <input type="text" id="app_name" name="app_name"
                       value="<?= $editApp ? htmlspecialchars($editApp['APP_NAME']) : '' ?>"
                       required maxlength="100">
                <div class="form-help">Nama lengkap aplikasi (contoh: "Sistem Manajemen Bimbel")</div>
            </div>

            <div class="form-group">
                <label for="app_short_name">Nama Pendek</label>
                <input type="text" id="app_short_name" name="app_short_name"
                       value="<?= $editApp ? htmlspecialchars($editApp['APP_SHORT_NAME']) : '' ?>"
                       required maxlength="20">
                <div class="form-help">Nama pendek untuk PWA (contoh: "Bimbel")</div>
            </div>

            <div class="form-group">
                <label for="app_slug">URL Slug</label>
                <input type="text" id="app_slug" name="app_slug"
                       value="<?= $editApp ? htmlspecialchars($editApp['APP_SLUG']) : '' ?>"
                       required pattern="[a-zA-Z0-9_-]+" maxlength="50">
                <div class="form-help">URL pendek (contoh: "bimbel" → siapp.test/bimbel). Hanya huruf, angka, dash (-) dan underscore (_)</div>
            </div>

            <div class="form-group">
                <label for="app_url">URL Google Apps Script</label>
                <input type="url" id="app_url" name="app_url"
                       value="<?= $editApp ? htmlspecialchars($editApp['APP_URL']) : '' ?>"
                       required>
                <div class="form-help">URL lengkap web app dari Google Apps Script (yang berakhiran /exec)</div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-success">
                    <?= $editApp ? '💾 Update Aplikasi' : '➕ Tambah Aplikasi' ?>
                </button>
                <?php if ($editApp): ?>
                    <a href="manage.php" class="btn btn-secondary">❌ Batal</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="app-table">
        <table>
            <thead>
                <tr>
                    <th>Nama Aplikasi</th>
                    <th>Slug</th>
                    <th>URL</th>
                    <th>Dibuat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($apps)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 2rem; color: #6c757d;">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">📱</div>
                            Belum ada aplikasi. Tambahkan aplikasi Google Apps Script Anda di atas!
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($apps as $app): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($app['APP_NAME']) ?></strong><br>
                                <small><?= htmlspecialchars($app['APP_SHORT_NAME']) ?></small>
                            </td>
                            <td>
                                <code><?= htmlspecialchars($app['APP_SLUG']) ?></code><br>
                                <small><a href="../<?= htmlspecialchars($app['APP_SLUG']) ?>" target="_blank">🔗 Lihat App</a></small>
                            </td>
                            <td>
                                <a href="<?= htmlspecialchars($app['APP_URL']) ?>" target="_blank" style="word-break: break-all; font-size: 0.875rem;">
                                    <?= htmlspecialchars(substr($app['APP_URL'], 0, 50)) ?><?= strlen($app['APP_URL']) > 50 ? '...' : '' ?>
                                </a>
                            </td>
                            <td>
                                <?php if (isset($app['created_at'])): ?>
                                    <?= date('d/m/Y H:i', strtotime($app['created_at'])) ?>
                                <?php else: ?>
                                    <em>-</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="manage.php?edit=<?= urlencode($app['APP_SLUG']) ?>" class="btn" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                    ✏️ Edit
                                </a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus aplikasi <?= htmlspecialchars($app['APP_NAME']) ?>?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="slug" value="<?= htmlspecialchars($app['APP_SLUG']) ?>">
                                    <button type="submit" class="btn btn-danger" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                        🗑️ Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div style="background: white; padding: 20px; border-radius: 8px; margin-top: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h3>ℹ️ Informasi</h3>
        <ul>
            <li><strong>URL Slug</strong> akan menjadi alamat pendek aplikasi Anda</li>
            <li><strong>URL Apps Script</strong> harus dari Google Apps Script yang sudah di-deploy sebagai web app</li>
            <li>Pastikan Apps Script sudah dikonfigurasi untuk iframe (lihat <a href="../docs-apps-script.html">Panduan Apps Script</a>)</li>
            <li>Setelah ditambahkan, aplikasi dapat diakses di <code>siapp.test/slug-anda</code></li>
        </ul>
    </div>
</body>
</html>
