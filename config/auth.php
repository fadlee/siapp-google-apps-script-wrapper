<?php
/**
 * Authentication Configuration for SiApp Control Panel
 * 
 * This file contains the hardcoded credentials for accessing the control panel.
 * For production use, consider moving to environment variables or a more secure method.
 */

return [
    'username' => 'admin',
    'password' => 'siapp123',
    'session_timeout' => 3600, // 1 hour in seconds
    'remember_duration' => 86400 * 30, // 30 days in seconds
];