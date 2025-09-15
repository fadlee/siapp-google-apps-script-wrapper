<?php
// Dynamic router for siapp - matches any slug from JSON data

// Get the request URI and remove query parameters
$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove leading slash and get the route
$route = trim($request, '/');

// Load data from JSON file
function loadAppData() {
    $jsonFile = __DIR__ . '/data.json';
    if (file_exists($jsonFile)) {
        $jsonContent = file_get_contents($jsonFile);
        return json_decode($jsonContent, true);
    }
    return null;
}

// Load and process HTML template
function loadTemplate($templatePath, $data) {
    if (!file_exists($templatePath)) {
        return null;
    }
    
    $template = file_get_contents($templatePath);
    
    // Replace placeholders with data
    foreach ($data as $key => $value) {
        $placeholder = '{{' . $key . '}}';
        $template = str_replace($placeholder, htmlspecialchars($value), $template);
    }
    
    return $template;
}

// Find app data by slug
function findAppBySlug($slug, $data) {
    if (!$data || empty($slug)) return null;
    
    foreach ($data as $app) {
        if (isset($app['APP_SLUG']) && $app['APP_SLUG'] === $slug) {
            return $app;
        }
    }
    return null;
}

// Get all available slugs
function getAllSlugs($data) {
    if (!$data) return [];
    
    $slugs = [];
    foreach ($data as $app) {
        if (isset($app['APP_SLUG'])) {
            $slugs[] = $app['APP_SLUG'];
        }
    }
    return $slugs;
}

// Load app data
$appData = loadAppData();

// Check for static asset requests (manifest, icons, etc.)
if (preg_match('/^([a-zA-Z0-9-_]+)\/(manifest\.json|icons\/.*|sw\.js)$/', $route, $matches)) {
    $slug = $matches[1];
    $assetPath = $matches[2];
    $app = findAppBySlug($slug, $appData);
    
    if ($app) {
        // Handle different asset types
        if ($assetPath === 'sw.js') {
            // Service worker
            $templatePath = __DIR__ . '/template/sw.js';
            $rendered = loadTemplate($templatePath, $app);
            
            if ($rendered) {
                header('Content-Type: application/javascript; charset=utf-8');
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');
                echo $rendered;
                exit;
            }
        } elseif ($assetPath === 'manifest.json') {
            // Manifest file
            $templatePath = __DIR__ . '/template/manifest.json';
            if (file_exists($templatePath)) {
                $rendered = loadTemplate($templatePath, $app);
                header('Content-Type: application/json; charset=utf-8');
                echo $rendered;
                exit;
            } else {
                // Generate basic manifest if template doesn't exist
                $manifest = [
                    'name' => $app['APP_NAME'],
                    'short_name' => $app['APP_SHORT_NAME'],
                    'start_url' => '/' . $app['APP_SLUG'] . '/',
                    'display' => 'standalone',
                    'theme_color' => '#ffffff',
                    'background_color' => '#ffffff',
                    'scope' => '/' . $app['APP_SLUG'] . '/'
                ];
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($manifest, JSON_PRETTY_PRINT);
                exit;
            }
        } elseif (preg_match('/^icons\/(.+)$/', $assetPath, $iconMatches)) {
            // Icon files
            $iconFile = $iconMatches[1];
            $iconPath = __DIR__ . '/template/icons/' . $iconFile;
            
            if (file_exists($iconPath)) {
                $ext = pathinfo($iconFile, PATHINFO_EXTENSION);
                $contentType = 'application/octet-stream';
                
                switch (strtolower($ext)) {
                    case 'svg':
                        $contentType = 'image/svg+xml';
                        // Process SVG as template for placeholders
                        $rendered = loadTemplate($iconPath, $app);
                        if ($rendered) {
                            header('Content-Type: ' . $contentType);
                            header('Cache-Control: public, max-age=3600'); // Cache for 1 hour (shorter for dynamic content)
                            echo $rendered;
                            exit;
                        }
                        break;
                    case 'png':
                        $contentType = 'image/png';
                        break;
                    case 'jpg':
                    case 'jpeg':
                        $contentType = 'image/jpeg';
                        break;
                    case 'ico':
                        $contentType = 'image/x-icon';
                        break;
                }
                
                // For non-SVG files, serve directly
                if (strtolower($ext) !== 'svg') {
                    header('Content-Type: ' . $contentType);
                    header('Cache-Control: public, max-age=31536000'); // Cache for 1 year
                    readfile($iconPath);
                    exit;
                }
            }
        }
    }
    
    // Asset not found
    http_response_code(404);
    echo '// Asset not found: ' . htmlspecialchars($route);
    exit;
}


// Handle routing
if (empty($route) || $route === 'index.php') {
    // Home page - show available apps
    ?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiApp - Application Manager</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            line-height: 1.6;
        }
        .app-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin: 10px 0;
            background: #f9f9f9;
            transition: box-shadow 0.2s;
        }
        .app-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
            background: #e0e0e0;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .json-link {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 16px;
            background: #007cba;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .json-link:hover {
            background: #005a87;
        }
        .available-slugs {
            background: #f0f8ff;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <h1>🚀 SiApp - Application Manager</h1>
    <p>Welcome to SiApp! Here are the available applications:</p>
    
    <?php if ($appData): ?>
        <?php foreach ($appData as $app): ?>
            <div class="app-card">
                <div class="app-name"><?= htmlspecialchars($app['APP_NAME']) ?></div>
                <p><strong>Short Name:</strong> <?= htmlspecialchars($app['APP_SHORT_NAME']) ?></p>
                <p><strong>Slug:</strong> <span class="app-slug"><?= htmlspecialchars($app['APP_SLUG']) ?></span></p>
                <a href="<?= htmlspecialchars($app['APP_SLUG']) ?>" class="json-link">View App</a>
                <a href="<?= htmlspecialchars($app['APP_SLUG']) ?>?format=json" class="json-link" style="background: #28a745;">View JSON</a>
            </div>
        <?php endforeach; ?>
        
        <div class="available-slugs">
            <h3>📋 Available Routes:</h3>
            <ul>
                <?php foreach (getAllSlugs($appData) as $slug): ?>
                    <li>
                        <strong><?= htmlspecialchars($slug) ?> App:</strong>
                        <br>
                        <code>siapp.test/<?= htmlspecialchars($slug) ?></code> - HTML App
                        <br>
                        <code>siapp.test/<?= htmlspecialchars($slug) ?>?format=json</code> - JSON Data
                        <br>
                        <code>siapp.test/<?= htmlspecialchars($slug) ?>/sw.js</code> - Service Worker
                        <br>
                        <code>siapp.test/<?= htmlspecialchars($slug) ?>/manifest.json</code> - Web App Manifest
                        <br>
                        <code>siapp.test/<?= htmlspecialchars($slug) ?>/icons/icon.svg</code> - App Icon
                        <br><br>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php else: ?>
        <div class="app-card">
            <p>⚠️ No applications found. Please check your data.json file.</p>
        </div>
    <?php endif; ?>
</body>
</html>
    <?php
} else {
    // Try to find app by slug dynamically
    $app = findAppBySlug($route, $appData);
    
    if ($app) {
        // Check if request wants JSON (via Accept header or ?format=json)
        $wantsJson = (isset($_GET['format']) && $_GET['format'] === 'json') ||
                     (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
        
        if ($wantsJson) {
            // Return JSON data
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($app, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            // Serve HTML template with replaced placeholders
            $templatePath = __DIR__ . '/template/index.html';
            
            // Prepare template data with additional fields
            $templateData = $app;
            
            // Add APP_URL if not present (you can customize this based on your needs)
            if (!isset($templateData['APP_URL'])) {
                // You can set a default URL or generate one based on the app
                $templateData['APP_URL'] = 'https://script.google.com/your-app-url-here';
            }
            
            $renderedTemplate = loadTemplate($templatePath, $templateData);
            
            if ($renderedTemplate) {
                header('Content-Type: text/html; charset=utf-8');
                echo $renderedTemplate;
            } else {
                // Template not found, fallback to JSON
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'error' => 'Template not found',
                    'app_data' => $app,
                    'message' => 'HTML template could not be loaded, showing JSON data instead'
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
        }
    } else {
        // No matching slug found - show 404
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        
        $availableSlugs = getAllSlugs($appData);
        echo json_encode([
            'error' => 'App not found',
            'requested_slug' => $route,
            'available_slugs' => $availableSlugs,
            'message' => 'The requested app slug "' . $route . '" does not exist in data.json'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
?>