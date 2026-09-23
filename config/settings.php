<?php
/**
 * Settings Helper Service (Multi-Tenant & Per-User Isolated with In-Memory Cache)
 * Enhanced with Transparent AES-256-GCM Encryption for API Tokens & Secrets
 */
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../services/CacheService.php';

class Settings {
    /**
     * Settings keys that must be encrypted at rest in the database
     */
    private static array $sensitiveKeys = [
        'openrouter_api_key',
        'meta_page_access_token',
        'meta_user_access_token',
        'meta_instagram_token',
        'meta_app_secret',
        'cron_secret_key',
        'webhook_verify_token'
    ];

    private static function resolveUserId(?int $userId = null): int {
        if ($userId !== null && $userId > 0) {
            return $userId;
        }
        if (class_exists('Auth') && Auth::check()) {
            return Auth::id();
        }
        return 1;
    }

    /**
     * Get a setting value (Served from cache with transparent decryption)
     */
    public static function get(string $key, $default = null, ?int $userId = null) {
        $uid = self::resolveUserId($userId);
        $settings = CacheService::getUserSettings($uid);
        $val = array_key_exists($key, $settings) ? $settings[$key] : $default;
        if (is_string($val) && str_starts_with($val, 'enc:v1:')) {
            return Security::decrypt($val);
        }
        return $val;
    }

    /**
     * Get a setting value specifically for a user ID
     */
    public static function getForUser(int $userId, string $key, $default = null) {
        return self::get($key, $default, $userId);
    }

    /**
     * Get all settings dictionary for a user (with transparent decryption)
     */
    public static function getAll(?int $userId = null): array {
        $uid = self::resolveUserId($userId);
        $settings = CacheService::getUserSettings($uid);
        unset($settings['_cache_key']);
        foreach ($settings as $k => $v) {
            if (is_string($v) && str_starts_with($v, 'enc:v1:')) {
                $settings[$k] = Security::decrypt($v);
            }
        }
        return $settings;
    }

    /**
     * Persist setting with automatic encryption for sensitive keys and invalidate cache
     */
    public static function set(string $key, string $value, ?int $userId = null): void {
        $uid = self::resolveUserId($userId);
        $storedValue = $value;
        if (in_array($key, self::$sensitiveKeys, true) && !empty($value)) {
            $storedValue = Security::encrypt($value);
        }
        $pdo = Database::getConnection();
        Database::upsertSetting($pdo, $uid, $key, $storedValue);
        CacheService::invalidateUserSettings($uid);
    }

    /**
     * Persist multiple settings with automatic encryption for sensitive keys and invalidate cache
     */
    public static function setMultiple(array $data, ?int $userId = null): void {
        $uid = self::resolveUserId($userId);
        $pdo = Database::getConnection();
        foreach ($data as $k => $v) {
            $storedValue = (string)$v;
            if (in_array($k, self::$sensitiveKeys, true) && !empty($storedValue)) {
                $storedValue = Security::encrypt($storedValue);
            }
            Database::upsertSetting($pdo, $uid, $k, $storedValue);
        }
        CacheService::invalidateUserSettings($uid);
    }
}
