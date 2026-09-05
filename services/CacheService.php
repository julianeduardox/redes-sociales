<?php
/**
 * High-Performance Multi-Tier Cache Service (L1 Static RAM / L2 APCu / L3 Atomic File)
 * Designed for Sub-Millisecond (< 0.1ms) In-Memory Lookup of:
 * - Brand Voices & AI Prompts
 * - User Settings & API Tokens (Meta, Gemini, OpenAI)
 * - Page ID to User Account Mappings
 */
require_once __DIR__ . '/../config/database.php';

class CacheService {
    /**
     * L1: In-Process Static Memory Cache (0 μs latency, lifespan: single request/worker)
     */
    private static array $l1Cache = [];

    /**
     * L3 Cache directory path
     */
    private static string $cacheDir = __DIR__ . '/../data/cache';

    private static bool $isApcuAvailable = false;
    private static bool $isApcuChecked = false;

    private static function hasApcu(): bool {
        if (!self::$isApcuChecked) {
            self::$isApcuAvailable = extension_loaded('apcu') && (
                ini_get('apc.enabled') && (php_sapi_name() !== 'cli' || ini_get('apc.enable_cli'))
            );
            self::$isApcuChecked = true;
        }
        return self::$isApcuAvailable;
    }

    private static function ensureCacheDir(): void {
        if (!is_dir(self::$cacheDir)) {
            @mkdir(self::$cacheDir, 0750, true);
        }
    }

    /**
     * Get an item from cache (checks L1 -> L2 APCu -> L3 File)
     */
    public static function get(string $key, $default = null) {
        // 1. Check L1 Memory
        if (array_key_exists($key, self::$l1Cache)) {
            $item = self::$l1Cache[$key];
            if ($item['expires_at'] === 0 || $item['expires_at'] >= time()) {
                return $item['value'];
            }
            unset(self::$l1Cache[$key]);
        }

        // 2. Check L2 APCu Shared Memory
        if (self::hasApcu()) {
            $success = false;
            $val = apcu_fetch('sb_cache_' . $key, $success);
            if ($success) {
                self::$l1Cache[$key] = ['value' => $val, 'expires_at' => 0];
                return $val;
            }
        }

        // 3. Check L3 Atomic File Cache
        $filePath = self::$cacheDir . '/' . md5($key) . '.cache';
        if (file_exists($filePath)) {
            $content = @file_get_contents($filePath);
            if ($content !== false) {
                $data = @unserialize($content);
                if (is_array($data) && isset($data['expires_at'], $data['value'])) {
                    if ($data['expires_at'] === 0 || $data['expires_at'] >= time()) {
                        // Populate L1 for subsequent reads in current process
                        self::$l1Cache[$key] = ['value' => $data['value'], 'expires_at' => $data['expires_at']];
                        return $data['value'];
                    }
                    @unlink($filePath);
                }
            }
        }

        return $default;
    }

    /**
     * Store an item in cache (writes L1, L2 APCu, and L3 File)
     * @param string $key Cache key
     * @param mixed $value Any serializable value
     * @param int $ttl Time to live in seconds (default 600s / 10 min, 0 = forever)
     */
    public static function set(string $key, $value, int $ttl = 600): bool {
        $expiresAt = $ttl > 0 ? time() + $ttl : 0;

        // 1. Store in L1 Memory
        self::$l1Cache[$key] = ['value' => $value, 'expires_at' => $expiresAt];

        // 2. Store in L2 APCu
        if (self::hasApcu()) {
            apcu_store('sb_cache_' . $key, $value, $ttl);
        }

        // 3. Store in L3 Atomic File
        self::ensureCacheDir();
        $filePath = self::$cacheDir . '/' . md5($key) . '.cache';
        $tmpFile = $filePath . '.' . bin2hex(random_bytes(6)) . '.tmp';
        $payload = serialize(['expires_at' => $expiresAt, 'value' => $value]);

        if (@file_put_contents($tmpFile, $payload, LOCK_EX) !== false) {
            @rename($tmpFile, $filePath);
            @chmod($filePath, 0640);
            return true;
        }

        return false;
    }

    /**
     * Get an item from cache, or execute callback and store result
     */
    public static function remember(string $key, int $ttl, callable $callback) {
        $cached = self::get($key, '__CACHE_MISS__');
        if ($cached !== '__CACHE_MISS__') {
            return $cached;
        }

        $fresh = $callback();
        self::set($key, $fresh, $ttl);
        return $fresh;
    }

    /**
     * Delete an item from all cache tiers
     */
    public static function delete(string $key): bool {
        unset(self::$l1Cache[$key]);

        if (self::hasApcu()) {
            apcu_delete('sb_cache_' . $key);
        }

        $filePath = self::$cacheDir . '/' . md5($key) . '.cache';
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        return true;
    }

    /**
     * Invalidate all keys matching a prefix
     */
    public static function deletePrefix(string $prefix): void {
        // Clear matching L1
        foreach (array_keys(self::$l1Cache) as $k) {
            if (str_starts_with($k, $prefix)) {
                unset(self::$l1Cache[$k]);
            }
        }

        // Clear matching L2 APCu
        if (self::hasApcu()) {
            $iterator = new APCUIterator('/^sb_cache_' . preg_quote($prefix, '/') . '/');
            foreach ($iterator as $item) {
                apcu_delete($item['key']);
            }
        }

        // Clear L3 file cache matching prefix
        if (is_dir(self::$cacheDir)) {
            $files = glob(self::$cacheDir . '/*.cache');
            if ($files) {
                foreach ($files as $f) {
                    $content = @file_get_contents($f);
                    if ($content !== false) {
                        $data = @unserialize($content);
                        if (is_array($data) && isset($data['value']) && is_array($data['value']) && isset($data['value']['_cache_key'])) {
                            if (str_starts_with($data['value']['_cache_key'], $prefix)) {
                                @unlink($f);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Flush entire cache
     */
    public static function flush(): void {
        self::$l1Cache = [];

        if (self::hasApcu()) {
            apcu_clear_cache();
        }

        if (is_dir(self::$cacheDir)) {
            $files = glob(self::$cacheDir . '/*.cache*');
            if ($files) {
                foreach ($files as $f) {
                    @unlink($f);
                }
            }
        }
    }

    // =========================================================================
    // DOMAIN-SPECIFIC HIGH SPEED ACCELERATORS (Brand Voices, Settings, Accounts)
    // =========================================================================

    /**
     * Get active brand voice for a user (Sub-millisecond resolution)
     */
    public static function getBrandVoice(int $userId, ?int $brandVoiceId = null, ?PDO $pdo = null): array {
        $cacheKey = "bv_u{$userId}_b" . ($brandVoiceId ?: 'default');
        
        return self::remember($cacheKey, 600, function() use ($userId, $brandVoiceId, $pdo) {
            $db = $pdo ?: Database::getConnection();

            if ($brandVoiceId && $brandVoiceId > 0) {
                $stmt = $db->prepare("SELECT * FROM brand_voices WHERE id = :id AND user_id = :uid LIMIT 1");
                $stmt->execute([':id' => $brandVoiceId, ':uid' => $userId]);
                $row = $stmt->fetch();
                if ($row) {
                    $row['_cache_key'] = "bv_u{$userId}";
                    return $row;
                }
            }

            // Fallback to default brand voice
            $stmtDef = $db->prepare("SELECT * FROM brand_voices WHERE user_id = :uid AND is_default = 1 LIMIT 1");
            $stmtDef->execute([':uid' => $userId]);
            $rowDef = $stmtDef->fetch();
            if ($rowDef) {
                $rowDef['_cache_key'] = "bv_u{$userId}";
                return $rowDef;
            }

            // Fallback to first brand voice
            $stmtFirst = $db->prepare("SELECT * FROM brand_voices WHERE user_id = :uid ORDER BY id ASC LIMIT 1");
            $stmtFirst->execute([':uid' => $userId]);
            $rowFirst = $stmtFirst->fetch();
            if ($rowFirst) {
                $rowFirst['_cache_key'] = "bv_u{$userId}";
                return $rowFirst;
            }

            return [
                '_cache_key' => "bv_u{$userId}",
                'brand_name' => 'Xindro Studio',
                'persona_name' => 'Alex — Asistente de Marca',
                'industry' => 'Comercio & Creadores',
                'tone_level' => 'friendly_engaging',
                'language' => 'es',
                'system_prompt' => 'Eres el asistente oficial de la marca. Responde con calidez y claridad profesional.',
                'warmth_level' => 85,
                'depth_level' => 75,
                'energy_level' => 80,
                'closing_question_rule' => 'always',
                'emoji_style' => 'moderate'
            ];
        });
    }

    /**
     * Invalidate all brand voices for a user
     */
    public static function invalidateBrandVoice(int $userId): void {
        self::delete("bv_u{$userId}_bdefault");
        self::deletePrefix("bv_u{$userId}_b");
    }

    /**
     * Get all settings dictionary for a user in 1 memory read
     */
    public static function getUserSettings(int $userId, ?PDO $pdo = null): array {
        $cacheKey = "settings_u{$userId}";

        return self::remember($cacheKey, 600, function() use ($userId, $pdo) {
            $db = $pdo ?: Database::getConnection();
            $stmt = $db->prepare("SELECT key, value FROM settings WHERE user_id = :uid");
            $stmt->execute([':uid' => $userId]);
            $map = [];
            while ($row = $stmt->fetch()) {
                $map[$row['key']] = $row['value'];
            }
            $map['_cache_key'] = "settings_u{$userId}";
            return $map;
        });
    }

    /**
     * Invalidate settings cache for a user
     */
    public static function invalidateUserSettings(int $userId): void {
        self::delete("settings_u{$userId}");
    }

    /**
     * Resolve user_id from Meta Page ID in microsecond cache
     */
    public static function getUserIdByPageId(string $pageId, ?PDO $pdo = null): int {
        if (empty($pageId)) {
            return 1;
        }
        $cacheKey = "page_user_" . md5($pageId);

        return (int)self::remember($cacheKey, 900, function() use ($pageId, $pdo) {
            $db = $pdo ?: Database::getConnection();
            $stmt = $db->prepare("SELECT user_id FROM accounts WHERE page_id = :pid LIMIT 1");
            $stmt->execute([':pid' => $pageId]);
            $row = $stmt->fetch();
            return $row ? (int)$row['user_id'] : 1;
        });
    }

    /**
     * Invalidate page_id mappings
     */
    public static function invalidateAccountMappings(): void {
        self::deletePrefix("page_user_");
    }
}
