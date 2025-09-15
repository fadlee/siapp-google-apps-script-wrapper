<?php
/**
 * Public App Submission Page for SiApp
 * Users can submit their Google Apps Script applications here
 */

require_once dirname(__DIR__) . '/../config/AppManager.php';

$appManager = new AppManager();
$message = '';
$messageType = '';
$submittedApp = null;

/**
 * Generate a random unique slug
 */
function generateRandomSlug($length = 6) {
    global $appManager;

    $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $maxAttempts = 100;

    for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
        $slug = '';
        for ($i = 0; $i < $length; $i++) {
            $slug .= $characters[rand(0, strlen($characters) - 1)];
        }

        // Check if slug already exists
        if (!$appManager->getAppBySlug($slug)) {
            return $slug;
        }

        // If we've tried many times, increase length
        if ($attempt > 50) {
            $length++;
        }
    }

    // Fallback: use timestamp-based slug
    return 'app' . time() . rand(100, 999);
}

/**
 * Validate Google Apps Script URL
 */
function validateAppsScriptUrl($url) {
    if (empty($url)) {
        return false;
    }

    // Check if it's a valid URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }

    // Check if it's a Google Apps Script URL
    return (
        strpos($url, 'script.google.com/macros/s/') !== false &&
        strpos($url, '/exec') !== false
    );
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appName = trim($_POST['app_name'] ?? '');
    $appUrl = trim($_POST['app_url'] ?? '');

    // Validation
    if (empty($appName)) {
        $message = 'Nama aplikasi harus diisi!';
        $messageType = 'error';
    } elseif (strlen($appName) < 3) {
        $message = 'Nama aplikasi minimal 3 karakter!';
        $messageType = 'error';
    } elseif (strlen($appName) > 100) {
        $message = 'Nama aplikasi maksimal 100 karakter!';
        $messageType = 'error';
    } elseif (empty($appUrl)) {
        $message = 'URL Google Apps Script harus diisi!';
        $messageType = 'error';
    } elseif (!validateAppsScriptUrl($appUrl)) {
        $message = 'URL harus berupa Google Apps Script yang valid (berakhiran /exec)!';
        $messageType = 'error';
    } else {
        // Generate slug and short name
        $slug = generateRandomSlug();
        $shortName = strlen($appName) > 20 ? substr($appName, 0, 17) . '...' : $appName;

        $appData = [
            'APP_NAME' => $appName,
            'APP_SLUG' => $slug,
            'APP_SHORT_NAME' => $shortName,
            'APP_URL' => $appUrl
        ];

        $result = $appManager->createApp($appData);

        if ($result) {
            $message = 'Aplikasi berhasil didaftarkan! URL pendek Anda sudah siap digunakan.';
            $messageType = 'success';
            $submittedApp = $appData;
        } else {
            $message = 'Maaf, terjadi kesalahan saat mendaftarkan aplikasi. Silakan coba lagi.';
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftarkan Aplikasi - SiApp</title>
    <meta name="description" content="Daftarkan Google Apps Script Anda dan dapatkan URL pendek yang mudah diingat.">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .form-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #22c55e;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
        }

        .form-help {
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 0.5rem;
        }

        .submit-button {
            width: 100%;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
            border: none;
            padding: 15px 20px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .submit-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 25px -5px rgba(34, 197, 94, 0.4);
        }

        .submit-button:active {
            transform: translateY(0);
        }

        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }

        .message.success {
            background: #f0fdf4;
            color: #16a34a;
            border-left: 4px solid #22c55e;
        }

        .message.error {
            background: #fef2f2;
            color: #dc2626;
            border-left: 4px solid #dc2626;
        }

        .success-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            text-align: center;
        }

        .success-card h3 {
            color: #22c55e;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .app-url {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 15px;
            margin: 1rem 0;
            font-family: monospace;
            font-size: 1.1rem;
            word-break: break-all;
        }

        .app-details {
            background: #f9fafb;
            border-radius: 8px;
            padding: 15px;
            margin: 1rem 0;
            text-align: left;
        }

        .app-details strong {
            color: #374151;
        }

        .footer-links {
            text-align: center;
            margin-top: 2rem;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 25px;
            transition: background 0.3s;
        }

        .footer-links a:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .info-box {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 2rem;
            color: white;
        }

        .info-box h3 {
            margin-bottom: 1rem;
            color: white;
        }

        .info-box ul {
            list-style: none;
            padding: 0;
        }

        .info-box li {
            margin: 0.5rem 0;
            padding-left: 1.5rem;
            position: relative;
        }

        .info-box li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #dcfce7;
            font-weight: bold;
        }

        @media (max-width: 600px) {
            .container {
                padding: 15px;
            }

            .header h1 {
                font-size: 2rem;
            }

            .form-container {
                padding: 1.5rem;
            }

            .footer-links a {
                display: block;
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 SiApp</h1>
            <p>Daftarkan Google Apps Script dan Dapatkan URL Pendek</p>
        </div>

        <?php if ($submittedApp): ?>
            <div class="success-card">
                <h3>🎉 Aplikasi Berhasil Didaftarkan!</h3>
                <p>Aplikasi Anda sudah dapat diakses melalui URL pendek berikut:</p>

                <div class="app-url">
                    <strong><?= $_SERVER['HTTP_HOST'] ?>/<?= htmlspecialchars($submittedApp['APP_SLUG']) ?></strong>
                </div>

                <div class="app-details">
                    <p><strong>Nama Aplikasi:</strong> <?= htmlspecialchars($submittedApp['APP_NAME']) ?></p>
                    <p><strong>URL Slug:</strong> <?= htmlspecialchars($submittedApp['APP_SLUG']) ?></p>
                    <p><strong>URL Apps Script:</strong> <a href="<?= htmlspecialchars($submittedApp['APP_URL']) ?>" target="_blank">Lihat Asli</a></p>
                </div>

                <p style="margin-top: 1rem; color: #6b7280; font-size: 0.9rem;">
                    Bookmark URL ini atau bagikan kepada pengguna Anda!
                </p>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (!$submittedApp): ?>
            <div class="form-container">
                <h2 style="margin-bottom: 1.5rem; color: #374151;">📝 Daftarkan Aplikasi Anda</h2>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="app_name">Nama Aplikasi</label>
                        <input
                            type="text"
                            id="app_name"
                            name="app_name"
                            value="<?= htmlspecialchars($_POST['app_name'] ?? '') ?>"
                            placeholder="Contoh: Sistem Manajemen Bimbel"
                            required
                            maxlength="100"
                        >
                        <div class="form-help">Masukkan nama yang mudah diingat untuk aplikasi Anda</div>
                    </div>

                    <div class="form-group">
                        <label for="app_url">URL Google Apps Script</label>
                        <input
                            type="url"
                            id="app_url"
                            name="app_url"
                            value="<?= htmlspecialchars($_POST['app_url'] ?? '') ?>"
                            placeholder="https://script.google.com/macros/s/YOUR_SCRIPT_ID/exec"
                            required
                        >
                        <div class="form-help">Paste URL lengkap web app dari Google Apps Script (harus berakhiran /exec)</div>
                    </div>

                    <button type="submit" class="submit-button">
                        🚀 Daftarkan Aplikasi
                    </button>
                </form>

                <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb; text-align: center; color: #6b7280; font-size: 0.9rem;">
                    <p>⚡ <strong>Gratis dan Instan!</strong> URL pendek akan dibuat otomatis setelah submit</p>
                </div>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <h3>ℹ️ Yang Perlu Anda Ketahui</h3>
            <ul>
                <li>URL pendek akan dibuat secara otomatis dengan slug acak</li>
                <li>Pastikan Google Apps Script sudah di-deploy sebagai web app</li>
                <li>Apps Script harus dikonfigurasi untuk iframe (lihat panduan)</li>
                <li>Aplikasi akan langsung dapat diakses setelah didaftarkan</li>
                <li>URL pendek akan tersedia selama aplikasi aktif</li>
            </ul>
        </div>

        <div class="footer-links">
            <a href="../">← Beranda</a>
            <a href="../docs-apps-script.html">📖 Panduan Apps Script</a>
        </div>
    </div>
</body>
</html>
