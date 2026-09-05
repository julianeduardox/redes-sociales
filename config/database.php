<?php
/**
 * Database Connection & Multi-Tenant Schema Initializer
 * Supports:
 * - SQLite (with WAL Journal Mode, Busy Timeout & Concurrency Optimizations for Local Dev)
 * - PostgreSQL (for High-Concurrency Production +1,000 users)
 * - MySQL / MariaDB (for High-Concurrency Production +1,000 users)
 * 
 * Auto-detects driver via DB_DRIVER / DB_CONNECTION / DATABASE_URL in .env or environment variables.
 */
class Database {
    private static ?PDO $pdo = null;
    private static ?string $driver = null;
    private static string $dbFile = __DIR__ . '/../data/social_agent.sqlite';

    /**
     * Load environment variables from .env if present
     */
    public static function loadEnv(): void {
        static $loaded = false;
        if ($loaded) return;
        $loaded = true;

        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || str_starts_with($line, '#')) continue;
                if (str_contains($line, '=')) {
                    [$name, $value] = explode('=', $line, 2);
                    $name = trim($name);
                    $value = trim($value, " \t\n\r\0\x0B\"'");
                    if (getenv($name) === false && !isset($_ENV[$name])) {
                        putenv("{$name}={$value}");
                        $_ENV[$name] = $value;
                        $_SERVER[$name] = $value;
                    }
                }
            }
        }
    }

    /**
     * Get active database driver name ('sqlite', 'pgsql', or 'mysql')
     */
    public static function getDriver(): string {
        if (self::$driver === null) {
            self::loadEnv();
            
            $dbUrl = getenv('DATABASE_URL') ?: ($_ENV['DATABASE_URL'] ?? '');
            if (!empty($dbUrl)) {
                $parsed = parse_url($dbUrl);
                $scheme = strtolower($parsed['scheme'] ?? '');
                if ($scheme === 'postgres' || $scheme === 'postgresql') {
                    self::$driver = 'pgsql';
                    return self::$driver;
                } elseif ($scheme === 'mysql') {
                    self::$driver = 'mysql';
                    return self::$driver;
                }
            }

            $driver = getenv('DB_DRIVER') ?: ($_ENV['DB_DRIVER'] ?? (getenv('DB_CONNECTION') ?: ($_ENV['DB_CONNECTION'] ?? 'sqlite')));
            $driver = strtolower(trim($driver));

            if ($driver === 'postgres' || $driver === 'postgresql') {
                $driver = 'pgsql';
            } elseif ($driver === 'mariadb') {
                $driver = 'mysql';
            }

            self::$driver = in_array($driver, ['sqlite', 'pgsql', 'mysql'], true) ? $driver : 'sqlite';
        }
        return self::$driver;
    }

    /**
     * Get Singleton PDO connection
     */
    public static function getConnection(): PDO {
        if (self::$pdo === null) {
            self::loadEnv();
            $driver = self::getDriver();

            $dbUrl = getenv('DATABASE_URL') ?: ($_ENV['DATABASE_URL'] ?? '');
            
            if (!empty($dbUrl) && $driver !== 'sqlite') {
                $parsed = parse_url($dbUrl);
                $host = $parsed['host'] ?? '127.0.0.1';
                $dbName = ltrim($parsed['path'] ?? '', '/');
                $user = $parsed['user'] ?? '';
                $pass = $parsed['pass'] ?? '';
                parse_str($parsed['query'] ?? '', $query);

                if ($driver === 'pgsql') {
                    $port = $parsed['port'] ?? 5432;
                    $sslMode = $query['sslmode'] ?? 'prefer';
                    $dsn = "pgsql:host={$host};port={$port};dbname={$dbName};sslmode={$sslMode}";
                } else {
                    $port = $parsed['port'] ?? 3306;
                    $charset = $query['charset'] ?? 'utf8mb4';
                    $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";
                }

                self::$pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);

            } elseif ($driver === 'pgsql') {
                // PostgreSQL connection
                $host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? '127.0.0.1');
                $port = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '5432');
                $dbName = getenv('DB_DATABASE') ?: ($_ENV['DB_DATABASE'] ?? 'social_agent_prod');
                $user = getenv('DB_USERNAME') ?: ($_ENV['DB_USERNAME'] ?? 'postgres');
                $pass = getenv('DB_PASSWORD') ?: ($_ENV['DB_PASSWORD'] ?? '');
                $sslMode = getenv('DB_SSLMODE') ?: ($_ENV['DB_SSLMODE'] ?? 'prefer');

                $dsn = "pgsql:host={$host};port={$port};dbname={$dbName};sslmode={$sslMode}";
                self::$pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);

            } elseif ($driver === 'mysql') {
                // MySQL / MariaDB connection
                $host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? '127.0.0.1');
                $port = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '3306');
                $dbName = getenv('DB_DATABASE') ?: ($_ENV['DB_DATABASE'] ?? 'social_agent_prod');
                $user = getenv('DB_USERNAME') ?: ($_ENV['DB_USERNAME'] ?? 'root');
                $pass = getenv('DB_PASSWORD') ?: ($_ENV['DB_PASSWORD'] ?? '');
                $charset = getenv('DB_CHARSET') ?: ($_ENV['DB_CHARSET'] ?? 'utf8mb4');

                $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";
                self::$pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}"
                ]);

            } else {
                // Default: SQLite with WAL mode & concurrency tuning
                $dataDir = dirname(self::$dbFile);
                if (!is_dir($dataDir)) {
                    mkdir($dataDir, 0750, true);
                }

                $isNew = !file_exists(self::$dbFile);

                self::$pdo = new PDO('sqlite:' . self::$dbFile, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);

                // 🚀 Concurrency & Performance Tuning for SQLite:
                // 1. Enable Write-Ahead Logging (WAL) for non-blocking concurrent reads & writes
                self::$pdo->exec("PRAGMA journal_mode = WAL;");
                // 2. Synchronous normal provides safety with high write throughput
                self::$pdo->exec("PRAGMA synchronous = NORMAL;");
                // 3. Busy timeout: wait up to 5000ms if database is momentarily locked
                self::$pdo->exec("PRAGMA busy_timeout = 5000;");
                // 4. Foreign key constraint enforcement
                self::$pdo->exec("PRAGMA foreign_keys = ON;");

                if ($isNew || filesize(self::$dbFile) === 0) {
                    if (file_exists(self::$dbFile)) {
                        @chmod(self::$dbFile, 0640);
                    }
                }
            }

            // Ensure baseline tables exist
            self::initializeSchema(self::$pdo);

            // Auto-migrate tables for multi-tenant and Meta Insights
            self::migrateMultiTenantSchema(self::$pdo);

            // Ensure baseline seed data exists
            self::ensureBaselineData(self::$pdo);
        }
        return self::$pdo;
    }

    /**
     * Get table column names agnostically across SQLite, PostgreSQL and MySQL
     */
    public static function getTableColumns(PDO $pdo, string $table): array {
        $driver = self::getDriver();
        $cols = [];
        try {
            if ($driver === 'sqlite') {
                $stmt = $pdo->query("PRAGMA table_info({$table})");
                while ($r = $stmt->fetch()) {
                    $cols[] = strtolower($r['name']);
                }
            } elseif ($driver === 'pgsql') {
                $stmt = $pdo->prepare("
                    SELECT column_name 
                    FROM information_schema.columns 
                    WHERE table_name = :tbl AND table_schema = current_schema()
                ");
                $stmt->execute([':tbl' => strtolower($table)]);
                while ($r = $stmt->fetch()) {
                    $cols[] = strtolower($r['column_name']);
                }
            } elseif ($driver === 'mysql') {
                $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
                while ($r = $stmt->fetch()) {
                    $cols[] = strtolower($r['Field']);
                }
            }
        } catch (Throwable $e) {
            // Table might not exist yet
        }
        return $cols;
    }

    /**
     * Upsert a setting key-value pair agnostically across SQLite, PostgreSQL and MySQL
     */
    public static function upsertSetting(PDO $pdo, int $userId, string $key, string $value): void {
        $driver = self::getDriver();
        if ($driver === 'pgsql') {
            $stmt = $pdo->prepare("
                INSERT INTO settings (user_id, key, value) 
                VALUES (:uid, :key, :value)
                ON CONFLICT (user_id, key) DO UPDATE SET value = EXCLUDED.value
            ");
        } elseif ($driver === 'mysql') {
            $stmt = $pdo->prepare("
                INSERT INTO settings (user_id, `key`, value) 
                VALUES (:uid, :key, :value)
                ON DUPLICATE KEY UPDATE value = VALUES(value)
            ");
        } else {
            // SQLite 3.24+ standard UPSERT / INSERT OR REPLACE
            $stmt = $pdo->prepare("INSERT OR REPLACE INTO settings (user_id, key, value) VALUES (:uid, :key, :value)");
        }
        $stmt->execute([':uid' => $userId, ':key' => $key, ':value' => $value]);
    }

    /**
     * Initialize standard relational schema across SQLite, PostgreSQL and MySQL
     */
    private static function initializeSchema(PDO $pdo): void {
        $driver = self::getDriver();

        if ($driver === 'pgsql') {
            // PostgreSQL Schema
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS rate_limits (
                    rate_key VARCHAR(255) PRIMARY KEY,
                    count INTEGER DEFAULT 1,
                    reset_at BIGINT NOT NULL,
                    expires_at BIGINT NOT NULL
                );
                CREATE INDEX IF NOT EXISTS idx_rate_expires ON rate_limits(expires_at);

                CREATE TABLE IF NOT EXISTS users (
                    id SERIAL PRIMARY KEY,
                    tenant_id VARCHAR(100) UNIQUE,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    password_hash VARCHAR(255) NOT NULL,
                    role VARCHAR(50) DEFAULT 'user',
                    avatar_url TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    last_login_at TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS brand_voices (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER NOT NULL DEFAULT 1,
                    brand_name VARCHAR(255) NOT NULL,
                    persona_name VARCHAR(255) NOT NULL DEFAULT 'Copiloto de Marca',
                    industry VARCHAR(255) NOT NULL DEFAULT 'Comercio & Creadores',
                    tone_level VARCHAR(100) NOT NULL DEFAULT 'friendly_engaging',
                    language VARCHAR(10) NOT NULL DEFAULT 'es',
                    system_prompt TEXT NOT NULL,
                    warmth_level INTEGER DEFAULT 85,
                    depth_level INTEGER DEFAULT 75,
                    energy_level INTEGER DEFAULT 80,
                    closing_question_rule VARCHAR(50) DEFAULT 'always',
                    emoji_style VARCHAR(50) DEFAULT 'moderate',
                    key_phrases TEXT,
                    forbidden_phrases TEXT,
                    few_shot_examples TEXT,
                    is_default INTEGER DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                );
                CREATE INDEX IF NOT EXISTS idx_brand_user ON brand_voices(user_id);

                CREATE TABLE IF NOT EXISTS accounts (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER NOT NULL DEFAULT 1,
                    brand_voice_id INTEGER DEFAULT 1,
                    platform VARCHAR(50) NOT NULL,
                    account_name VARCHAR(255) NOT NULL,
                    account_handle VARCHAR(255) NOT NULL,
                    page_id VARCHAR(255),
                    avatar_url TEXT,
                    access_token TEXT,
                    is_active INTEGER DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (brand_voice_id) REFERENCES brand_voices(id) ON DELETE SET NULL
                );

                CREATE TABLE IF NOT EXISTS posts (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER NOT NULL DEFAULT 1,
                    account_id INTEGER NOT NULL,
                    brand_voice_id INTEGER DEFAULT 1,
                    platform VARCHAR(50) NOT NULL,
                    external_post_id VARCHAR(255),
                    caption TEXT NOT NULL,
                    media_url TEXT,
                    media_type VARCHAR(50) DEFAULT 'image',
                    permalink TEXT,
                    total_likes INTEGER DEFAULT 0,
                    total_comments INTEGER DEFAULT 0,
                    total_shares INTEGER DEFAULT 0,
                    impressions INTEGER DEFAULT 0,
                    reach INTEGER DEFAULT 0,
                    saved_count INTEGER DEFAULT 0,
                    engagement_rate REAL DEFAULT 0.0,
                    last_synced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    posted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
                );

                CREATE TABLE IF NOT EXISTS comments (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER NOT NULL DEFAULT 1,
                    post_id INTEGER NOT NULL,
                    platform VARCHAR(50) NOT NULL,
                    external_comment_id VARCHAR(255),
                    author_name VARCHAR(255) NOT NULL,
                    author_handle VARCHAR(255),
                    author_avatar TEXT,
                    comment_text TEXT NOT NULL,
                    sentiment VARCHAR(50) DEFAULT 'neutral',
                    intent VARCHAR(50) DEFAULT 'general',
                    highlight_score INTEGER DEFAULT 50,
                    is_highlighted INTEGER DEFAULT 0,
                    highlight_reason TEXT,
                    likes_count INTEGER DEFAULT 0,
                    status VARCHAR(50) DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
                );

                CREATE TABLE IF NOT EXISTS replies (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER NOT NULL DEFAULT 1,
                    comment_id INTEGER NOT NULL,
                    reply_text TEXT NOT NULL,
                    reply_type VARCHAR(50) DEFAULT 'copilot',
                    tone_used VARCHAR(100),
                    variant_type VARCHAR(50),
                    is_posted_to_platform INTEGER DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE
                );

                CREATE TABLE IF NOT EXISTS settings (
                    user_id INTEGER NOT NULL DEFAULT 1,
                    key VARCHAR(100) NOT NULL,
                    value TEXT,
                    PRIMARY KEY (user_id, key),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                );

                CREATE TABLE IF NOT EXISTS automation_rules (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER NOT NULL DEFAULT 1,
                    rule_name VARCHAR(255) NOT NULL,
                    trigger_intent VARCHAR(100) NOT NULL,
                    action_type VARCHAR(50) DEFAULT 'auto_reply',
                    custom_prompt_addon TEXT,
                    is_enabled INTEGER DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                );

                CREATE TABLE IF NOT EXISTS password_resets (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER NOT NULL,
                    token_hash VARCHAR(255) NOT NULL,
                    expires_at TIMESTAMP NOT NULL,
                    used INTEGER DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                );
                CREATE INDEX IF NOT EXISTS idx_reset_token ON password_resets(token_hash);
                CREATE INDEX IF NOT EXISTS idx_reset_expires ON password_resets(expires_at);

                CREATE TABLE IF NOT EXISTS webhook_queue (
                    id SERIAL PRIMARY KEY,
                    event_source VARCHAR(50) NOT NULL DEFAULT 'meta',
                    payload TEXT NOT NULL,
                    signature VARCHAR(255),
                    status VARCHAR(50) NOT NULL DEFAULT 'pending',
                    attempts INTEGER DEFAULT 0,
                    error_message TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    processed_at TIMESTAMP
                );
                CREATE INDEX IF NOT EXISTS idx_webhook_status_created ON webhook_queue(status, created_at);
            ");

        } elseif ($driver === 'mysql') {
            // MySQL Schema
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS rate_limits (
                    rate_key VARCHAR(255) PRIMARY KEY,
                    count INT DEFAULT 1,
                    reset_at BIGINT NOT NULL,
                    expires_at BIGINT NOT NULL,
                    INDEX idx_rate_expires (expires_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    tenant_id VARCHAR(100) UNIQUE,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    password_hash VARCHAR(255) NOT NULL,
                    role VARCHAR(50) DEFAULT 'user',
                    avatar_url TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    last_login_at DATETIME
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS brand_voices (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL DEFAULT 1,
                    brand_name VARCHAR(255) NOT NULL,
                    persona_name VARCHAR(255) NOT NULL DEFAULT 'Copiloto de Marca',
                    industry VARCHAR(255) NOT NULL DEFAULT 'Comercio & Creadores',
                    tone_level VARCHAR(100) NOT NULL DEFAULT 'friendly_engaging',
                    language VARCHAR(10) NOT NULL DEFAULT 'es',
                    system_prompt TEXT NOT NULL,
                    warmth_level INT DEFAULT 85,
                    depth_level INT DEFAULT 75,
                    energy_level INT DEFAULT 80,
                    closing_question_rule VARCHAR(50) DEFAULT 'always',
                    emoji_style VARCHAR(50) DEFAULT 'moderate',
                    key_phrases TEXT,
                    forbidden_phrases TEXT,
                    few_shot_examples TEXT,
                    is_default INT DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_brand_user (user_id),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS accounts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL DEFAULT 1,
                    brand_voice_id INT DEFAULT 1,
                    platform VARCHAR(50) NOT NULL,
                    account_name VARCHAR(255) NOT NULL,
                    account_handle VARCHAR(255) NOT NULL,
                    page_id VARCHAR(255),
                    avatar_url TEXT,
                    access_token TEXT,
                    is_active INT DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (brand_voice_id) REFERENCES brand_voices(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS posts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL DEFAULT 1,
                    account_id INT NOT NULL,
                    brand_voice_id INT DEFAULT 1,
                    platform VARCHAR(50) NOT NULL,
                    external_post_id VARCHAR(255),
                    caption TEXT NOT NULL,
                    media_url TEXT,
                    media_type VARCHAR(50) DEFAULT 'image',
                    permalink TEXT,
                    total_likes INT DEFAULT 0,
                    total_comments INT DEFAULT 0,
                    total_shares INT DEFAULT 0,
                    impressions INT DEFAULT 0,
                    reach INT DEFAULT 0,
                    saved_count INT DEFAULT 0,
                    engagement_rate FLOAT DEFAULT 0.0,
                    last_synced_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    posted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS comments (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL DEFAULT 1,
                    post_id INT NOT NULL,
                    platform VARCHAR(50) NOT NULL,
                    external_comment_id VARCHAR(255),
                    author_name VARCHAR(255) NOT NULL,
                    author_handle VARCHAR(255),
                    author_avatar TEXT,
                    comment_text TEXT NOT NULL,
                    sentiment VARCHAR(50) DEFAULT 'neutral',
                    intent VARCHAR(50) DEFAULT 'general',
                    highlight_score INT DEFAULT 50,
                    is_highlighted INT DEFAULT 0,
                    highlight_reason TEXT,
                    likes_count INT DEFAULT 0,
                    status VARCHAR(50) DEFAULT 'pending',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS replies (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL DEFAULT 1,
                    comment_id INT NOT NULL,
                    reply_text TEXT NOT NULL,
                    reply_type VARCHAR(50) DEFAULT 'copilot',
                    tone_used VARCHAR(100),
                    variant_type VARCHAR(50),
                    is_posted_to_platform INT DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS settings (
                    user_id INT NOT NULL DEFAULT 1,
                    `key` VARCHAR(100) NOT NULL,
                    value TEXT,
                    PRIMARY KEY (user_id, `key`),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS automation_rules (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL DEFAULT 1,
                    rule_name VARCHAR(255) NOT NULL,
                    trigger_intent VARCHAR(100) NOT NULL,
                    action_type VARCHAR(50) DEFAULT 'auto_reply',
                    custom_prompt_addon TEXT,
                    is_enabled INT DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS password_resets (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    token_hash VARCHAR(255) NOT NULL,
                    expires_at DATETIME NOT NULL,
                    used INT DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_reset_token (token_hash),
                    INDEX idx_reset_expires (expires_at),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS webhook_queue (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    event_source VARCHAR(50) NOT NULL DEFAULT 'meta',
                    payload LONGTEXT NOT NULL,
                    signature VARCHAR(255),
                    status VARCHAR(50) NOT NULL DEFAULT 'pending',
                    attempts INT DEFAULT 0,
                    error_message TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    processed_at DATETIME,
                    INDEX idx_webhook_status_created (status, created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

        } else {
            // SQLite Schema
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS rate_limits (
                    rate_key TEXT PRIMARY KEY,
                    count INTEGER DEFAULT 1,
                    reset_at INTEGER NOT NULL,
                    expires_at INTEGER NOT NULL
                );
                CREATE INDEX IF NOT EXISTS idx_rate_expires ON rate_limits(expires_at);

                CREATE TABLE IF NOT EXISTS users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    tenant_id TEXT UNIQUE,
                    name TEXT NOT NULL,
                    email TEXT UNIQUE NOT NULL,
                    password_hash TEXT NOT NULL,
                    role TEXT DEFAULT 'user',
                    avatar_url TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    last_login_at DATETIME
                );

                CREATE TABLE IF NOT EXISTS brand_voices (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL DEFAULT 1,
                    brand_name TEXT NOT NULL,
                    persona_name TEXT NOT NULL DEFAULT 'Copiloto de Marca',
                    industry TEXT NOT NULL DEFAULT 'Comercio & Creadores',
                    tone_level TEXT NOT NULL DEFAULT 'friendly_engaging',
                    language TEXT NOT NULL DEFAULT 'es',
                    system_prompt TEXT NOT NULL,
                    warmth_level INTEGER DEFAULT 85,
                    depth_level INTEGER DEFAULT 75,
                    energy_level INTEGER DEFAULT 80,
                    closing_question_rule TEXT DEFAULT 'always',
                    emoji_style TEXT DEFAULT 'moderate',
                    key_phrases TEXT,
                    forbidden_phrases TEXT,
                    few_shot_examples TEXT,
                    is_default INTEGER DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                );
                CREATE INDEX IF NOT EXISTS idx_brand_user ON brand_voices(user_id);

                CREATE TABLE IF NOT EXISTS accounts (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL DEFAULT 1,
                    brand_voice_id INTEGER DEFAULT 1,
                    platform TEXT NOT NULL,
                    account_name TEXT NOT NULL,
                    account_handle TEXT NOT NULL,
                    page_id TEXT,
                    avatar_url TEXT,
                    access_token TEXT,
                    is_active INTEGER DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (brand_voice_id) REFERENCES brand_voices(id) ON DELETE SET NULL
                );

                CREATE TABLE IF NOT EXISTS posts (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL DEFAULT 1,
                    account_id INTEGER NOT NULL,
                    brand_voice_id INTEGER DEFAULT 1,
                    platform TEXT NOT NULL,
                    external_post_id TEXT,
                    caption TEXT NOT NULL,
                    media_url TEXT,
                    media_type TEXT DEFAULT 'image',
                    permalink TEXT,
                    total_likes INTEGER DEFAULT 0,
                    total_comments INTEGER DEFAULT 0,
                    total_shares INTEGER DEFAULT 0,
                    impressions INTEGER DEFAULT 0,
                    reach INTEGER DEFAULT 0,
                    saved_count INTEGER DEFAULT 0,
                    engagement_rate REAL DEFAULT 0.0,
                    last_synced_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    posted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
                );

                CREATE TABLE IF NOT EXISTS comments (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL DEFAULT 1,
                    post_id INTEGER NOT NULL,
                    platform TEXT NOT NULL,
                    external_comment_id TEXT,
                    author_name TEXT NOT NULL,
                    author_handle TEXT,
                    author_avatar TEXT,
                    comment_text TEXT NOT NULL,
                    sentiment TEXT DEFAULT 'neutral',
                    intent TEXT DEFAULT 'general',
                    highlight_score INTEGER DEFAULT 50,
                    is_highlighted INTEGER DEFAULT 0,
                    highlight_reason TEXT,
                    likes_count INTEGER DEFAULT 0,
                    status TEXT DEFAULT 'pending',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
                );

                CREATE TABLE IF NOT EXISTS replies (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL DEFAULT 1,
                    comment_id INTEGER NOT NULL,
                    reply_text TEXT NOT NULL,
                    reply_type TEXT DEFAULT 'copilot',
                    tone_used TEXT,
                    variant_type TEXT,
                    is_posted_to_platform INTEGER DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE
                );

                CREATE TABLE IF NOT EXISTS settings (
                    user_id INTEGER NOT NULL DEFAULT 1,
                    key TEXT NOT NULL,
                    value TEXT,
                    PRIMARY KEY (user_id, key),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                );

                CREATE TABLE IF NOT EXISTS automation_rules (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL DEFAULT 1,
                    rule_name TEXT NOT NULL,
                    trigger_intent TEXT NOT NULL,
                    action_type TEXT DEFAULT 'auto_reply',
                    custom_prompt_addon TEXT,
                    is_enabled INTEGER DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                );

                CREATE TABLE IF NOT EXISTS password_resets (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    token_hash TEXT NOT NULL,
                    expires_at DATETIME NOT NULL,
                    used INTEGER DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                );
                CREATE INDEX IF NOT EXISTS idx_reset_token ON password_resets(token_hash);
                CREATE INDEX IF NOT EXISTS idx_reset_expires ON password_resets(expires_at);

                CREATE TABLE IF NOT EXISTS webhook_queue (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    event_source TEXT NOT NULL DEFAULT 'meta',
                    payload TEXT NOT NULL,
                    signature TEXT,
                    status TEXT NOT NULL DEFAULT 'pending',
                    attempts INTEGER DEFAULT 0,
                    error_message TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    processed_at DATETIME
                );
                CREATE INDEX IF NOT EXISTS idx_webhook_status_created ON webhook_queue(status, created_at);
            ");
        }
    }

    /**
     * Migrate tables agnostically across drivers
     */
    private static function migrateMultiTenantSchema(PDO $pdo): void {
        try {
            $driver = self::getDriver();

            // 0. Ensure tenant_id in users
            $uCols = self::getTableColumns($pdo, 'users');
            if (!empty($uCols) && !in_array('tenant_id', $uCols, true)) {
                $type = ($driver === 'sqlite') ? 'TEXT' : 'VARCHAR(100)';
                $pdo->exec("ALTER TABLE users ADD COLUMN tenant_id {$type}");
            }

            // Fill missing tenant_id
            $usersWithoutTenant = $pdo->query("SELECT id, created_at FROM users WHERE tenant_id IS NULL OR tenant_id = ''")->fetchAll();
            if (!empty($usersWithoutTenant)) {
                $upTenant = $pdo->prepare("UPDATE users SET tenant_id = :tenant_id WHERE id = :id");
                foreach ($usersWithoutTenant as $u) {
                    $tenantKey = 'tnt_' . substr(md5($u['id'] . '_' . ($u['created_at'] ?? time())), 0, 12);
                    $upTenant->execute([':tenant_id' => $tenantKey, ':id' => $u['id']]);
                }
            }

            // 1. Ensure columns in posts
            $postCols = self::getTableColumns($pdo, 'posts');
            if (!empty($postCols)) {
                $colsToAdd = [
                    'impressions' => ($driver === 'sqlite' ? 'INTEGER DEFAULT 0' : 'INT DEFAULT 0'),
                    'reach' => ($driver === 'sqlite' ? 'INTEGER DEFAULT 0' : 'INT DEFAULT 0'),
                    'saved_count' => ($driver === 'sqlite' ? 'INTEGER DEFAULT 0' : 'INT DEFAULT 0'),
                    'engagement_rate' => ($driver === 'sqlite' ? 'REAL DEFAULT 0.0' : 'FLOAT DEFAULT 0.0'),
                    'last_synced_at' => ($driver === 'sqlite' ? 'DATETIME' : ($driver === 'pgsql' ? 'TIMESTAMP' : 'DATETIME')),
                    'user_id' => ($driver === 'sqlite' ? 'INTEGER DEFAULT 1' : 'INT DEFAULT 1'),
                    'brand_voice_id' => ($driver === 'sqlite' ? 'INTEGER DEFAULT 1' : 'INT DEFAULT 1')
                ];

                foreach ($colsToAdd as $col => $colType) {
                    if (!in_array($col, $postCols, true)) {
                        $pdo->exec("ALTER TABLE posts ADD COLUMN {$col} {$colType}");
                    }
                }
            }

            // 2. Ensure user_id in accounts, comments, replies
            $tables = ['accounts', 'comments', 'replies'];
            foreach ($tables as $t) {
                $tCols = self::getTableColumns($pdo, $t);
                if (!empty($tCols) && !in_array('user_id', $tCols, true)) {
                    $tType = ($driver === 'sqlite' ? 'INTEGER DEFAULT 1' : 'INT DEFAULT 1');
                    $pdo->exec("ALTER TABLE {$t} ADD COLUMN user_id {$tType}");
                }
            }

            // 3. Ensure brand_voice_id in accounts
            $accCols = self::getTableColumns($pdo, 'accounts');
            if (!empty($accCols) && !in_array('brand_voice_id', $accCols, true)) {
                $tType = ($driver === 'sqlite' ? 'INTEGER DEFAULT 1' : 'INT DEFAULT 1');
                $pdo->exec("ALTER TABLE accounts ADD COLUMN brand_voice_id {$tType}");
            }
            try {
                $pdo->exec("UPDATE accounts SET brand_voice_id = 1 WHERE brand_voice_id IS NULL OR brand_voice_id = 0");
                $pdo->exec("UPDATE posts SET brand_voice_id = 1 WHERE brand_voice_id IS NULL OR brand_voice_id = 0");
            } catch (Throwable) {}

            // 4. Ensure admin user
            $julianStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $julianStmt->execute([':email' => 'julianeduardox@gmail.com']);
            $julianUser = $julianStmt->fetch();

            if ($julianUser) {
                $julianId = (int)$julianUser['id'];
                $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = :id")->execute([':id' => $julianId]);
            } else {
                $firstUserStmt = $pdo->query("SELECT id FROM users ORDER BY id ASC LIMIT 1");
                $firstUser = $firstUserStmt ? $firstUserStmt->fetch() : null;
                if ($firstUser) {
                    $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = :id")->execute([':id' => $firstUser['id']]);
                }
            }

            // 5. Ensure tester user
            $testerStmt = $pdo->prepare("SELECT id FROM users WHERE email = 'tester@xindro.app' LIMIT 1");
            $testerStmt->execute();
            $testerUser = $testerStmt->fetch();

            if (!$testerUser) {
                $testerHash = password_hash('TesterPassword2026!', PASSWORD_BCRYPT, ['cost' => 12]);
                $stmtInsTester = $pdo->prepare("
                    INSERT INTO users (tenant_id, name, email, password_hash, role, avatar_url, last_login_at)
                    VALUES ('tnt_tester_api_01', 'Usuario de Prueba API', 'tester@xindro.app', :hash, 'tester', 'https://ui-avatars.com/api/?name=Tester+API&background=7c3aed&color=fff&size=96', CURRENT_TIMESTAMP)
                ");
                $stmtInsTester->execute([':hash' => $testerHash]);
                $testerId = (int)$pdo->lastInsertId();
                self::seedInitialData($pdo, $testerId);
            }

        } catch (Throwable $e) {
            error_log("Schema migration notice: " . $e->getMessage());
        }
    }

    /**
     * Ensure baseline demo/starter data exists
     */
    private static function ensureBaselineData(PDO $pdo): void {
        try {
            $userCount = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            if ($userCount === 0) {
                $adminHash = password_hash('Admin2026!Secure', PASSWORD_BCRYPT, ['cost' => 12]);
                $pdo->prepare("
                    INSERT INTO users (tenant_id, name, email, password_hash, role, avatar_url, last_login_at)
                    VALUES ('tnt_admin_01', 'Julian Eduardo', 'julianeduardox@gmail.com', :hash, 'admin', 'https://ui-avatars.com/api/?name=Julian+Eduardo&background=6366f1&color=fff&size=96', CURRENT_TIMESTAMP)
                ")->execute([':hash' => $adminHash]);
                $adminId = (int)$pdo->lastInsertId();
                self::seedInitialData($pdo, $adminId);
            }
        } catch (Throwable $e) {
            // Ignore if already seeded
        }
    }

    public static function resetDatabase(): void {
        self::$pdo = null;
        if (self::getDriver() === 'sqlite' && file_exists(self::$dbFile)) {
            unlink(self::$dbFile);
        }
        self::getConnection();
    }

    public static function getDefaultFewShots(): array {
        return [
            [
                'tag' => 'precio_leads',
                'comment' => '¿Cuál es el precio del curso o programa y qué incluye?',
                'reply' => '¡Hola {nombre}! Con gusto te comparto los detalles. El programa incluye acceso completo a las clases grabadas, módulos prácticos y soporte continuo. Puedes revisar los detalles e inscribirte directamente en el enlace de nuestra biografía, o enviarnos un DM si deseas asesoría personalizada. ¿Qué objetivo principal buscas alcanzar?'
            ],
            [
                'tag' => 'concepto_filosofico',
                'comment' => '¿Cómo aplico la dicotomía del control en mi día a día cuando siento estrés?',
                'reply' => '¡Hola {nombre}! La clave es separar lo que depende al 100% de ti (tu actitud, tus decisiones y tu esfuerzo) de lo externo (el tráfico, las opiniones ajenas). Enfoca toda tu energía en tu propia respuesta y suelta lo incontrolable. ¿Qué obstáculo puntual estás enfrentando hoy?'
            ],
            [
                'tag' => 'objecion_garantia',
                'comment' => '¿Qué garantía tienen y cómo sé si funcionará para mí?',
                'reply' => 'Excelente pregunta, {nombre}. Respaldamos todo nuestro trabajo con garantía de satisfacción y atención personalizada 1 a 1. Además, puedes revisar testimonios de nuestra comunidad en el enlace de la bio. ¿Te gustaría agendar una llamada rápida para evaluar tu caso?'
            ],
            [
                'tag' => 'soporte_ayuda',
                'comment' => 'Tengo un inconveniente con el acceso a mi cuenta en la plataforma.',
                'reply' => '¡Hola {nombre}! Por supuesto, queremos que accedas sin inconvenientes. Por favor envíanos un mensaje privado (DM) con tu correo registrado para que nuestro equipo técnico lo verifique y resuelva de inmediato. ¡Cuenta con nosotros!'
            ],
            [
                'tag' => 'felicitacion_agradecimiento',
                'comment' => '¡Excelente contenido y qué gran valor aportan! Me ayudó muchísimo su recomendación.',
                'reply' => '¡Muchísimas gracias por tus palabras, {nombre}! Nos alegra enorme saber que te ha sido de gran valor. ¿De qué tema te gustaría que profundicemos en la siguiente publicación?'
            ]
        ];
    }

    public static function ensureDefaultBrandVoice(PDO $pdo, int $userId, string $brandName = 'Mi Marca'): int {
        $bvCheck = $pdo->prepare("SELECT id FROM brand_voices WHERE user_id = :uid LIMIT 1");
        $bvCheck->execute([':uid' => $userId]);
        $bvRow = $bvCheck->fetch();

        if ($bvRow) {
            return (int)$bvRow['id'];
        }

        $defaultFewShots = self::getDefaultFewShots();

        $stmtBv = $pdo->prepare("
            INSERT INTO brand_voices (
                user_id, brand_name, persona_name, industry, tone_level, language, system_prompt,
                warmth_level, depth_level, energy_level, closing_question_rule, emoji_style,
                key_phrases, forbidden_phrases, few_shot_examples, is_default
            ) VALUES (
                :uid, :bname, :pname, :industry, :tone, :lang, :prompt,
                :warmth, :depth, :energy, :crule, :emojis,
                :kphrases, :fphrases, :fewshots, 1
            )
        ");
        $stmtBv->execute([
            ':uid' => $userId,
            ':bname' => $brandName . ' Oficial',
            ':pname' => 'Alex — Asistente de Marca',
            ':industry' => 'Comercio Electrónico, Servicios & Creadores',
            ':tone' => 'friendly_engaging',
            ':lang' => 'es',
            ':prompt' => 'Eres el estratega oficial de comunicación de la marca. Responde siempre con carisma, empatía y claridad, captando leads y orientando a los usuarios con soluciones útiles y profesionales sin sonar como un robot.',
            ':warmth' => 85,
            ':depth' => 75,
            ':energy' => 80,
            ':crule' => 'always',
            ':emojis' => 'moderate',
            ':kphrases' => json_encode(['Calidad garantizada', 'Atención personalizada', 'Envíos a todo el país', 'Comunidad oficial', 'Asesoría directa'], JSON_UNESCAPED_UNICODE),
            ':fphrases' => json_encode(['Estimado cliente', 'Compra ya', 'Oferta engañosa', 'Somos un bot', 'Haz clic aquí'], JSON_UNESCAPED_UNICODE),
            ':fewshots' => json_encode($defaultFewShots, JSON_UNESCAPED_UNICODE)
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function seedInitialData(PDO $pdo, int $userId = 1): void {
        // Fetch user name
        $uStmt = $pdo->prepare("SELECT name FROM users WHERE id = :uid LIMIT 1");
        $uStmt->execute([':uid' => $userId]);
        $uRow = $uStmt->fetch();
        $uName = $uRow['name'] ?? 'Xindro Studio';

        // 1. Seed or update Brand Voice record
        $brandVoiceId = self::ensureDefaultBrandVoice($pdo, $userId, $uName);

        // 2. Default System & AI Settings for User
        $defaultFewShots = self::getDefaultFewShots();
        $defaultSettings = [
            'brand_name' => $uName . ' Oficial',
            'brand_industry' => 'Comercio Electrónico, Servicios & Creadores',
            'brand_tone' => 'friendly_engaging',
            'brand_description' => 'Marca líder dedicada a ofrecer soluciones innovadoras, productos de alta calidad y atención personalizada a nuestra comunidad.',
            'brand_warmth_level' => '85',
            'brand_depth_level' => '75',
            'brand_energy_level' => '80',
            'brand_closing_question_rule' => 'always',
            'brand_emoji_style' => 'moderate',
            'brand_key_phrases' => json_encode(['Calidad garantizada', 'Atención personalizada', 'Envíos a todo el país', 'Comunidad oficial', 'Asesoría directa'], JSON_UNESCAPED_UNICODE),
            'brand_forbidden_phrases' => json_encode(['Estimado cliente', 'Compra ya', 'Oferta engañosa', 'Somos un bot', 'Haz clic aquí'], JSON_UNESCAPED_UNICODE),
            'brand_few_shot_examples' => json_encode($defaultFewShots, JSON_UNESCAPED_UNICODE),
            'ai_provider' => 'openrouter',
            'openrouter_api_key' => '',
            'openrouter_model' => 'anthropic/claude-3.5-sonnet',
            'autopilot_enabled' => '0',
            'autopilot_min_score' => '75',
            'meta_app_id' => '',
            'meta_app_secret' => '',
            'meta_page_access_token' => '',
            'meta_instagram_account_id' => '',
            'webhook_verify_token' => 'social_boost_secure_token_2026'
        ];

        foreach ($defaultSettings as $k => $v) {
            self::upsertSetting($pdo, $userId, $k, (string)$v);
        }
    }
}

