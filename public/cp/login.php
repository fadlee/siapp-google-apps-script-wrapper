<?php
require_once dirname(__DIR__) . '/../config/AuthManager.php';

// Handle form submission
$error = '';
$success = '';
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '/cp/';

// Handle logout message
if (isset($_GET['message']) && $_GET['message'] === 'logged_out') {
    $success = 'Anda telah berhasil logout.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $remember = isset($_POST['remember']);

    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi!';
    } elseif (AuthManager::login($username, $password, $remember)) {
        // Login successful, redirect
        header('Location: ' . $redirect);
        exit();
    } else {
        $error = 'Username atau password salah!';
    }
}

// If already logged in, redirect to dashboard
if (AuthManager::isAuthenticated()) {
    header('Location: ' . $redirect);
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Panel Admin SiApp</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }

        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            width: 100%;
            max-width: 400px;
            margin: 20px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h1 {
            color: #22c55e;
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
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

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin: 0;
        }

        .checkbox-group label {
            margin: 0;
            font-weight: normal;
            font-size: 0.9rem;
            color: #6b7280;
        }

        .login-button {
            width: 100%;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
            border: none;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .login-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 25px -5px rgba(34, 197, 94, 0.4);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .error-message {
            background: #fef2f2;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #dc2626;
            font-size: 0.9rem;
        }

        .success-message {
            background: #f0fdf4;
            color: #16a34a;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #22c55e;
            font-size: 0.9rem;
        }

        .footer-links {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
        }

        .footer-links a {
            color: #22c55e;
            text-decoration: none;
            font-size: 0.9rem;
            margin: 0 10px;
        }

        .footer-links a:hover {
            text-decoration: underline;
        }

        .default-credentials {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
        }

        .default-credentials h4 {
            color: #16a34a;
            margin-bottom: 0.5rem;
        }

        .default-credentials code {
            background: #dcfce7;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 10px;
                padding: 1.5rem;
            }

            .login-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🚀 SiApp</h1>
            <p>Panel Admin - Login untuk melanjutkan</p>
        </div>

        <?php
            $host = $_SERVER['HTTP_HOST'] ?? '';
            // Remove port if present, e.g., localhost:8000 -> localhost
            $host = preg_replace('/:\d+$/', '', $host);
            $isLocalEnv = ($host === 'localhost') || preg_match('/\.test$/i', $host);
            if ($isLocalEnv):
        ?>
        <div class="default-credentials">
            <h4>Default Login:</h4>
            <p><strong>Username:</strong> <code>admin</code></p>
            <p><strong>Password:</strong> <code>siapp123</code></p>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    placeholder="Masukkan username Anda"
                    required
                    autofocus
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Masukkan password Anda"
                    required
                >
            </div>

            <div class="checkbox-group">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Ingat saya selama 30 hari</label>
            </div>

            <button type="submit" class="login-button">
                Masuk ke Panel Admin
            </button>
        </form>

        <div class="footer-links">
            <a href="../">← Kembali ke Beranda</a>
            <a href="../docs-apps-script.html">Bantuan</a>
        </div>
    </div>

    <script>
        // Auto-focus on password field if username is already filled
        document.addEventListener('DOMContentLoaded', function() {
            const usernameField = document.getElementById('username');
            const passwordField = document.getElementById('password');

            if (usernameField.value.trim() !== '') {
                passwordField.focus();
            }
        });

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>
