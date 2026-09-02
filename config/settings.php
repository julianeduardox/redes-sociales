<?php
/**
 * Settings Helper Service (Multi-Tenant & Per-User Isolated)
 */
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

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

    public static function get(string $key, $default = null, ?int $userId = null) {
        $uid = self::resolveUserId($userId);
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE user_id = :uid AND key = :key LIMIT 1");
        $stmt->execute([':uid' => $uid, ':key' => $key]);
        $row = $stmt->fetch();
        return $row !== false ? $row['value'] : $default;
    }

    public static function getAll(?int $userId = null): array {
        $uid = self::resolveUserId($userId);
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT key, value FROM settings WHERE user_id = :uid");
        $stmt->execute([':uid' => $uid]);
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['key']] = $row['value'];
        }
        return $settings;
    }

    public static function set(string $key, string $value, ?int $userId = null): void {
        $uid = self::resolveUserId($userId);
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT OR REPLACE INTO settings (user_id, key, value) VALUES (:uid, :key, :value)");
        $stmt->execute([':uid' => $uid, ':key' => $key, ':value' => $value]);
    }

    public static function setMultiple(array $data, ?int $userId = null): void {
        $uid = self::resolveUserId($userId);
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT OR REPLACE INTO settings (user_id, key, value) VALUES (:uid, :key, :value)");
        foreach ($data as $k => $v) {
            $stmt->execute([':uid' => $uid, ':key' => $k, ':value' => (string)$v]);
        }
    }
}
