<?php
/**
 * Settings Helper Service (Multi-Tenant & Per-User Isolated with In-Memory Cache)
 */
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../services/CacheService.php';

class Settings {
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
     * Get a setting value (Served from L1/L2/L3 in-memory cache)
     */
    public static function get(string $key, $default = null, ?int $userId = null) {
        $uid = self::resolveUserId($userId);
        $settings = CacheService::getUserSettings($uid);
        return array_key_exists($key, $settings) ? $settings[$key] : $default;
    }

    /**
     * Get all settings dictionary for a user (Served from in-memory cache)
     */
    public static function getAll(?int $userId = null): array {
        $uid = self::resolveUserId($userId);
        $settings = CacheService::getUserSettings($uid);
        unset($settings['_cache_key']);
        return $settings;
    }

    /**
     * Persist setting and invalidate cache
     */
    public static function set(string $key, string $value, ?int $userId = null): void {
        $uid = self::resolveUserId($userId);
        $pdo = Database::getConnection();
        Database::upsertSetting($pdo, $uid, $key, $value);
        CacheService::invalidateUserSettings($uid);
    }

    /**
     * Persist multiple settings and invalidate cache
     */
    public static function setMultiple(array $data, ?int $userId = null): void {
        $uid = self::resolveUserId($userId);
        $pdo = Database::getConnection();
        foreach ($data as $k => $v) {
            Database::upsertSetting($pdo, $uid, $k, (string)$v);
        }
        CacheService::invalidateUserSettings($uid);
    }
}
