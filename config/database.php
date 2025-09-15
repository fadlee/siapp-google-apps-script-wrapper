<?php
/**
 * SleekDB Database Configuration for SiApp
 */

// Create config directory if not exists
if (!is_dir(__DIR__)) {
    mkdir(__DIR__, 0755, true);
}

// Require SleekDB files manually (since we're not using composer)
require_once __DIR__ . '/../lib/SleekDB/src/Store.php';
require_once __DIR__ . '/../lib/SleekDB/src/QueryBuilder.php';
require_once __DIR__ . '/../lib/SleekDB/src/Query.php';
require_once __DIR__ . '/../lib/SleekDB/src/Cache.php';

// Load all exception classes
foreach (glob(__DIR__ . '/../lib/SleekDB/src/Exceptions/*.php') as $exception) {
    require_once $exception;
}

// Load all helper classes  
foreach (glob(__DIR__ . '/../lib/SleekDB/src/Classes/*.php') as $class) {
    require_once $class;
}

// Database configuration
class DatabaseConfig {
    private static $dataDir = null;
    private static $stores = [];
    
    /**
     * Get database directory path
     */
    public static function getDataDir() {
        if (self::$dataDir === null) {
            self::$dataDir = __DIR__ . '/../database';
            
            // Create database directory if not exists
            if (!is_dir(self::$dataDir)) {
                if (!mkdir(self::$dataDir, 0755, true)) {
                    throw new Exception('Failed to create database directory: ' . self::$dataDir);
                }
            }
        }
        return self::$dataDir;
    }
    
    /**
     * Get SleekDB store instance
     */
    public static function getStore($storeName) {
        if (!isset(self::$stores[$storeName])) {
            try {
                self::$stores[$storeName] = new \SleekDB\Store($storeName, self::getDataDir(), [
                    'auto_cache' => false, // Disable cache for now to avoid issues
                    'cache_lifetime' => null,
                    'timeout' => false, // Disable timeout to avoid deprecation warning
                    'primary_key' => '_id'
                ]);
            } catch (Exception $e) {
                error_log('SleekDB Store Error: ' . $e->getMessage());
                throw new Exception('Database connection failed: ' . $e->getMessage());
            }
        }
        return self::$stores[$storeName];
    }
    
    /**
     * Test database connection
     */
    public static function testConnection() {
        try {
            $store = self::getStore('test');
            $testDoc = $store->insert(['test' => true, 'created_at' => date('Y-m-d H:i:s')]);
            $store->deleteBy(['_id', '=', $testDoc['_id']]);
            return true;
        } catch (Exception $e) {
            error_log('Database test failed: ' . $e->getMessage());
            return false;
        }
    }
}

// Initialize and test connection on load
try {
    if (!DatabaseConfig::testConnection()) {
        error_log('Warning: Database connection test failed');
    }
} catch (Exception $e) {
    error_log('Database initialization error: ' . $e->getMessage());
}