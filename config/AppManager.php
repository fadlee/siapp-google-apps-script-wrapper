<?php
/**
 * Application Manager for SiApp
 * Handles CRUD operations for applications using SleekDB
 */

require_once __DIR__ . '/database.php';

class AppManager {
    private $store;
    private $storeName = 'applications';
    
    public function __construct() {
        try {
            $this->store = DatabaseConfig::getStore($this->storeName);
        } catch (Exception $e) {
            throw new Exception('Failed to initialize AppManager: ' . $e->getMessage());
        }
    }
    
    /**
     * Get all applications
     * @return array
     */
    public function getAllApps() {
        try {
            // Use query builder instead of findAll for better compatibility
            $query = $this->store->createQueryBuilder();
            $apps = $query->getQuery()->fetch();
            
            // Convert to the expected format (without _id for compatibility)
            $result = [];
            foreach ($apps as $app) {
                if (is_array($app)) {
                    $cleanApp = $app;
                    unset($cleanApp['_id']); // Remove internal _id
                    $result[] = $cleanApp;
                }
            }
            
            return $result;
        } catch (Exception $e) {
            error_log('AppManager::getAllApps Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get application by slug
     * @param string $slug
     * @return array|null
     */
    public function getAppBySlug($slug) {
        try {
            $query = $this->store->createQueryBuilder();
            $apps = $query->where(['APP_SLUG', '=', $slug])->getQuery()->fetch();
            
            if (!empty($apps)) {
                $app = $apps[0];
                unset($app['_id']); // Remove internal _id
                return $app;
            }
            return null;
        } catch (Exception $e) {
            error_log('AppManager::getAppBySlug Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create new application
     * @param array $appData
     * @return array|false
     */
    public function createApp($appData) {
        try {
            // Validate required fields
            if (!$this->validateAppData($appData)) {
                return false;
            }
            
            // Check if slug already exists
            if ($this->getAppBySlug($appData['APP_SLUG'])) {
                throw new Exception('Application slug already exists: ' . $appData['APP_SLUG']);
            }
            
            // Add timestamps
            $appData['created_at'] = date('Y-m-d H:i:s');
            $appData['updated_at'] = date('Y-m-d H:i:s');
            
            // Insert to database
            $result = $this->store->insert($appData);
            
            if ($result) {
                unset($result['_id']); // Remove internal _id
                return $result;
            }
            
            return false;
        } catch (Exception $e) {
            error_log('AppManager::createApp Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update existing application
     * @param string $slug
     * @param array $appData
     * @return array|false
     */
    public function updateApp($slug, $appData) {
        try {
            // Validate required fields
            if (!$this->validateAppData($appData, false)) {
                return false;
            }
            
            // If slug is being changed, check for conflicts
            if (isset($appData['APP_SLUG']) && $appData['APP_SLUG'] !== $slug) {
                if ($this->getAppBySlug($appData['APP_SLUG'])) {
                    throw new Exception('New application slug already exists: ' . $appData['APP_SLUG']);
                }
            }
            
            // Add update timestamp
            $appData['updated_at'] = date('Y-m-d H:i:s');
            
            // Update in database using QueryBuilder
            $query = $this->store->createQueryBuilder();
            $result = $query->where(['APP_SLUG', '=', $slug])->getQuery()->update($appData);
            
            if ($result) {
                return $this->getAppBySlug($appData['APP_SLUG'] ?? $slug);
            }
            
            return false;
        } catch (Exception $e) {
            error_log('AppManager::updateApp Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete application by slug
     * @param string $slug
     * @return bool
     */
    public function deleteApp($slug) {
        try {
            $query = $this->store->createQueryBuilder();
            $result = $query->where(['APP_SLUG', '=', $slug])->getQuery()->delete();
            return $result > 0;
        } catch (Exception $e) {
            error_log('AppManager::deleteApp Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all application slugs
     * @return array
     */
    public function getAllSlugs() {
        try {
            $apps = $this->getAllApps();
            $slugs = [];
            
            foreach ($apps as $app) {
                if (isset($app['APP_SLUG'])) {
                    $slugs[] = $app['APP_SLUG'];
                }
            }
            
            return $slugs;
        } catch (Exception $e) {
            error_log('AppManager::getAllSlugs Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Search applications
     * @param string $query
     * @return array
     */
    public function searchApps($query) {
        try {
            $queryBuilder = $this->store->createQueryBuilder();
            $apps = $queryBuilder->getQuery()->fetch();
            $results = [];
            
            $query = strtolower($query);
            
            foreach ($apps as $app) {
                $searchText = strtolower($app['APP_NAME'] . ' ' . $app['APP_SLUG'] . ' ' . ($app['APP_SHORT_NAME'] ?? ''));
                
                if (strpos($searchText, $query) !== false) {
                    unset($app['_id']);
                    $results[] = $app;
                }
            }
            
            return $results;
        } catch (Exception $e) {
            error_log('AppManager::searchApps Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get application statistics
     * @return array
     */
    public function getStats() {
        try {
            $apps = $this->getAllApps();
            
            return [
                'total_apps' => count($apps),
                'total_slugs' => count($this->getAllSlugs()),
                'active_templates' => 1, // For now, we have 1 template
                'last_updated' => $this->getLastUpdateTime()
            ];
        } catch (Exception $e) {
            error_log('AppManager::getStats Error: ' . $e->getMessage());
            return [
                'total_apps' => 0,
                'total_slugs' => 0,
                'active_templates' => 1,
                'last_updated' => null
            ];
        }
    }
    
    /**
     * Validate application data
     * @param array $appData
     * @param bool $requireAll
     * @return bool
     */
    private function validateAppData($appData, $requireAll = true) {
        $requiredFields = ['APP_NAME', 'APP_SLUG', 'APP_SHORT_NAME', 'APP_URL'];
        
        if ($requireAll) {
            foreach ($requiredFields as $field) {
                if (!isset($appData[$field]) || empty(trim($appData[$field]))) {
                    error_log('AppManager: Missing required field: ' . $field);
                    return false;
                }
            }
        }
        
        // Validate slug format (alphanumeric, dashes, underscores only)
        if (isset($appData['APP_SLUG'])) {
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $appData['APP_SLUG'])) {
                error_log('AppManager: Invalid slug format: ' . $appData['APP_SLUG']);
                return false;
            }
        }
        
        // Validate URL format
        if (isset($appData['APP_URL'])) {
            if (!filter_var($appData['APP_URL'], FILTER_VALIDATE_URL)) {
                error_log('AppManager: Invalid URL format: ' . $appData['APP_URL']);
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get last update time
     * @return string|null
     */
    private function getLastUpdateTime() {
        try {
            $query = $this->store->createQueryBuilder();
            $apps = $query->getQuery()->fetch();
            $lastUpdate = null;
            
            foreach ($apps as $app) {
                if (isset($app['updated_at'])) {
                    if ($lastUpdate === null || strtotime($app['updated_at']) > strtotime($lastUpdate)) {
                        $lastUpdate = $app['updated_at'];
                    }
                }
            }
            
            return $lastUpdate;
        } catch (Exception $e) {
            error_log('AppManager::getLastUpdateTime Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Import data from JSON array
     * @param array $jsonData
     * @return bool
     */
    public function importFromJSON($jsonData) {
        try {
            if (!is_array($jsonData)) {
                return false;
            }
            
            $imported = 0;
            
            foreach ($jsonData as $appData) {
                // Skip if app already exists
                if ($this->getAppBySlug($appData['APP_SLUG'])) {
                    continue;
                }
                
                if ($this->createApp($appData)) {
                    $imported++;
                }
            }
            
            error_log("AppManager: Imported {$imported} applications from JSON");
            return $imported > 0;
        } catch (Exception $e) {
            error_log('AppManager::importFromJSON Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Export all data to JSON array
     * @return array
     */
    public function exportToJSON() {
        return $this->getAllApps();
    }
}