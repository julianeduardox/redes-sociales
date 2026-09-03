<?php
/**
 * Database Connection & Multi-Tenant Schema Initializer (SQLite)
 * Hardened with File Permissions, Auto-Migration, Rate Limits & User Isolation
 */
class Database {
    private static ?PDO $pdo = null;
    private static string $dbFile = __DIR__ . '/../data/social_agent.sqlite';

    public static function getConnection(): PDO {
        if (self::$pdo === null) {
            $dataDir = dirname(self::$dbFile);
            if (!is_dir($dataDir)) {
                mkdir($dataDir, 0750, true);
            }

            $isNew = !file_exists(self::$dbFile);

            self::$pdo = new PDO('sqlite:' . self::$dbFile);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Ensure rate_limits table always exists
            self::$pdo->exec("
                CREATE TABLE IF NOT EXISTS rate_limits (
                    rate_key TEXT PRIMARY KEY,
                    count INTEGER DEFAULT 1,
                    reset_at INTEGER NOT NULL,
                    expires_at INTEGER NOT NULL
                );
                CREATE INDEX IF NOT EXISTS idx_rate_expires ON rate_limits(expires_at);
            ");

            // Ensure users table exists with tenant_id support
            self::$pdo->exec("
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
            ");

            // Auto-migrate tables for multi-tenant and Meta Insights
            self::migrateMultiTenantSchema(self::$pdo);

            if ($isNew || filesize(self::$dbFile) === 0) {
                if (file_exists(self::$dbFile)) {
                    @chmod(self::$dbFile, 0640);
                }
                self::initializeSchema(self::$pdo);
                self::seedInitialData(self::$pdo, 1);
            }
        }
        return self::$pdo;
    }

    private static function migrateMultiTenantSchema(PDO $pdo): void {
        try {
            // 0. Ensure tenant_id column in users table
            $uColsStmt = $pdo->query("PRAGMA table_info(users)");
            $uCols = [];
            while ($uc = $uColsStmt->fetch()) {
                $uCols[] = $uc['name'];
            }
            if (!empty($uCols) && !in_array('tenant_id', $uCols, true)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN tenant_id TEXT");
            }

            // Fill missing tenant_id for existing users
            $usersWithoutTenant = $pdo->query("SELECT id, created_at FROM users WHERE tenant_id IS NULL OR tenant_id = ''")->fetchAll();
            $upTenant = $pdo->prepare("UPDATE users SET tenant_id = :tenant_id WHERE id = :id");
            foreach ($usersWithoutTenant as $u) {
                $tenantKey = 'tnt_' . substr(md5($u['id'] . '_' . ($u['created_at'] ?? time())), 0, 12);
                $upTenant->execute([':tenant_id' => $tenantKey, ':id' => $u['id']]);
            }

            // 1. Migrate posts table for Meta Insights
            $colsStmt = $pdo->query("PRAGMA table_info(posts)");
            $existingCols = [];
            while ($c = $colsStmt->fetch()) {
                $existingCols[] = $c['name'];
            }

            if (!empty($existingCols)) {
                $newColumns = [
                    'impressions' => 'INTEGER DEFAULT 0',
                    'reach' => 'INTEGER DEFAULT 0',
                    'saved_count' => 'INTEGER DEFAULT 0',
                    'engagement_rate' => 'REAL DEFAULT 0.0',
                    'last_synced_at' => 'DATETIME',
                    'user_id' => 'INTEGER DEFAULT 1'
                ];

                foreach ($newColumns as $col => $type) {
                    if (!in_array($col, $existingCols, true)) {
                        $pdo->exec("ALTER TABLE posts ADD COLUMN $col $type");
                    }
                }
            }

            // 2. Add user_id to accounts, comments, replies if missing
            $tablesToMigrate = ['accounts', 'comments', 'replies'];
            foreach ($tablesToMigrate as $table) {
                $tStmt = $pdo->query("PRAGMA table_info($table)");
                $tCols = [];
                while ($tc = $tStmt->fetch()) {
                    $tCols[] = $tc['name'];
                }
                if (!empty($tCols) && !in_array('user_id', $tCols, true)) {
                    $pdo->exec("ALTER TABLE $table ADD COLUMN user_id INTEGER DEFAULT 1");
                }
            }

            // 3. Migrate settings table to composite key (user_id, key)
            $sStmt = $pdo->query("PRAGMA table_info(settings)");
            $sCols = [];
            while ($sc = $sStmt->fetch()) {
                $sCols[] = $sc['name'];
            }

            if (!empty($sCols) && !in_array('user_id', $sCols, true)) {
                // Recreate settings table with multi-tenant structure
                $oldRows = $pdo->query("SELECT key, value FROM settings")->fetchAll();
                $pdo->exec("DROP TABLE settings");
                $pdo->exec("
                    CREATE TABLE settings (
                        user_id INTEGER NOT NULL DEFAULT 1,
                        key TEXT NOT NULL,
                        value TEXT,
                        PRIMARY KEY (user_id, key)
                    );
                ");
                $ins = $pdo->prepare("INSERT OR REPLACE INTO settings (user_id, key, value) VALUES (1, :key, :value)");
                foreach ($oldRows as $row) {
                    $ins->execute([':key' => $row['key'], ':value' => $row['value']]);
                }
            }

            // 4. Ensure julianeduardox@gmail.com is official administrator & delete demo user
            $julianStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $julianStmt->execute([':email' => 'julianeduardox@gmail.com']);
            $julianUser = $julianStmt->fetch();

            if ($julianUser) {
                $julianId = (int)$julianUser['id'];
                
                // Set Julian as Admin
                $upAdmin = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = :id");
                $upAdmin->execute([':id' => $julianId]);

                // Reassign any orphaned accounts, posts, comments from old demo user to Julian
                $demoStmt = $pdo->prepare("SELECT id FROM users WHERE email = 'admin@menteestoica.com' LIMIT 1");
                $demoStmt->execute();
                $demoUser = $demoStmt->fetch();

                if ($demoUser) {
                    $demoId = (int)$demoUser['id'];
                    if ($demoId !== $julianId) {
                        $pdo->prepare("UPDATE OR IGNORE accounts SET user_id = :new_uid WHERE user_id = :old_uid")->execute([':new_uid' => $julianId, ':old_uid' => $demoId]);
                        $pdo->prepare("UPDATE OR IGNORE posts SET user_id = :new_uid WHERE user_id = :old_uid")->execute([':new_uid' => $julianId, ':old_uid' => $demoId]);
                        $pdo->prepare("UPDATE OR IGNORE comments SET user_id = :new_uid WHERE user_id = :old_uid")->execute([':new_uid' => $julianId, ':old_uid' => $demoId]);
                        $pdo->prepare("UPDATE OR IGNORE replies SET user_id = :new_uid WHERE user_id = :old_uid")->execute([':new_uid' => $julianId, ':old_uid' => $demoId]);
                        $pdo->prepare("UPDATE OR IGNORE settings SET user_id = :new_uid WHERE user_id = :old_uid")->execute([':new_uid' => $julianId, ':old_uid' => $demoId]);
                        
                        // Delete demo user
                        $pdo->prepare("DELETE FROM users WHERE id = :old_uid")->execute([':old_uid' => $demoId]);
                    }
                }
            } else {
                // If Julian has not registered locally, promote first registered user to admin
                $firstUserStmt = $pdo->query("SELECT id FROM users ORDER BY id ASC LIMIT 1");
                $firstUser = $firstUserStmt->fetch();
                if ($firstUser) {
                    $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = :id")->execute([':id' => $firstUser['id']]);
                }
            }

            // 5. Ensure password_resets table exists with indexes
            $pdo->exec("
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
            ");

            // 6. Ensure dedicated tester user exists for API & Integration Testing
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
            error_log("Multi-tenant schema migration error: " . $e->getMessage());
        }
    }

    public static function resetDatabase(): void {
        self::$pdo = null;
        if (file_exists(self::$dbFile)) {
            unlink(self::$dbFile);
        }
        self::getConnection();
    }

    private static function initializeSchema(PDO $pdo): void {
        $schema = "
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            role TEXT DEFAULT 'user',
            avatar_url TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_login_at DATETIME
        );

        CREATE TABLE IF NOT EXISTS accounts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL DEFAULT 1,
            platform TEXT NOT NULL,
            account_name TEXT NOT NULL,
            account_handle TEXT NOT NULL,
            page_id TEXT,
            avatar_url TEXT,
            access_token TEXT,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL DEFAULT 1,
            account_id INTEGER NOT NULL,
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
        ";

        $pdo->exec($schema);
    }

    public static function seedInitialData(PDO $pdo, int $userId = 1): void {
        // Default Stoic & Motivational Creator Settings for User
        $defaultSettings = [
            'brand_name' => 'Mente Estoica Oficial',
            'brand_industry' => 'Estoicismo, Filosofía Práctica y Disciplina',
            'brand_tone' => 'stoic_mentor',
            'brand_description' => 'Espacio dedicado a compartir reflexiones de Marco Aurelio, Séneca y Epicteto, desarrollo del carácter, superación de la adversidad, disciplina diaria y paz mental.',
            'brand_warmth_level' => '85',
            'brand_depth_level' => '80',
            'brand_energy_level' => '75',
            'brand_closing_question_rule' => 'always',
            'brand_emoji_style' => 'moderate',
            'brand_key_phrases' => json_encode(['Dicotomía del control', 'Amor Fati', 'Memento Mori', 'Autodominio', 'Fortaleza mental', 'Disciplina diaria'], JSON_UNESCAPED_UNICODE),
            'brand_forbidden_phrases' => json_encode(['Estimado cliente', 'Compra ya', 'Oferta imperdible', 'Somos un bot', 'Haz clic aquí'], JSON_UNESCAPED_UNICODE),
            'ai_provider' => 'gemini',
            'gemini_api_key' => '',
            'openai_api_key' => '',
            'autopilot_enabled' => '0',
            'autopilot_min_score' => '75',
            'meta_app_id' => '',
            'meta_app_secret' => '',
            'meta_page_access_token' => '',
            'meta_instagram_account_id' => '',
            'webhook_verify_token' => 'social_boost_secure_token_2026'
        ];

        $stmt = $pdo->prepare("INSERT OR REPLACE INTO settings (user_id, key, value) VALUES (:uid, :key, :value)");
        foreach ($defaultSettings as $k => $v) {
            $stmt->execute([':uid' => $userId, ':key' => $k, ':value' => $v]);
        }

        // Ensure initial starter account and workspace data exist for this user tenant
        $accCheck = $pdo->prepare("SELECT COUNT(*) FROM accounts WHERE user_id = :uid");
        $accCheck->execute([':uid' => $userId]);
        if ((int)$accCheck->fetchColumn() === 0) {
            $uStmt = $pdo->prepare("SELECT name FROM users WHERE id = :uid LIMIT 1");
            $uStmt->execute([':uid' => $userId]);
            $uRow = $uStmt->fetch();
            $uName = $uRow['name'] ?? 'Mente Estoica Oficial';
            $cleanHandle = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '', $uName)));
            $uHandle = '@' . (!empty($cleanHandle) ? $cleanHandle : 'usuario_' . $userId);

            $stmtAcc = $pdo->prepare("
                INSERT INTO accounts (user_id, platform, account_name, account_handle, page_id, avatar_url) 
                VALUES (:uid, 'instagram', :name, :handle, :page_id, :avatar)
            ");
            
            $stmtAcc->execute([
                ':uid' => $userId,
                ':name' => $uName,
                ':handle' => $uHandle,
                ':page_id' => 'page_user_' . $userId . '_ig',
                ':avatar' => 'https://images.unsplash.com/photo-1544717305-2782549b5136?w=96&h=96&fit=crop&crop=faces&auto=format&q=75'
            ]);
            $accId = (int)$pdo->lastInsertId();

            // Insert Initial Starter Post for this user
            $stmtPost = $pdo->prepare("
                INSERT INTO posts (user_id, account_id, platform, external_post_id, caption, media_url, media_type, permalink, total_likes, total_comments, total_shares, impressions, reach, saved_count, engagement_rate) 
                VALUES (:uid, :acc, 'instagram', :ext_id, :caption, :media, :mtype, :permalink, :likes, :comments, :shares, :impressions, :reach, :saved, :eng_rate)
            ");

            $stmtPost->execute([
                ':uid' => $userId,
                ':acc' => $accId,
                ':ext_id' => 'ig_post_user_' . $userId,
                ':caption' => '🏛️ "Tienes poder sobre tu mente, no sobre los acontecimientos externos. Comprende esto y encontrarás la fuerza." — Marco Aurelio. ⚔️ Cuando la vida se pone difícil, recuerda la Dicotomía del Control: no sufras por lo que no puedes cambiar, domina cómo reaccionas. ¿Qué situación hoy te está retando a aplicar esto? 👇 #Estoicismo #MarcoAurelio #Disciplina #Motivacion #PazMental',
                ':media' => 'https://images.unsplash.com/photo-1544717305-2782549b5136?w=480&h=320&fit=crop&auto=format&q=75',
                ':mtype' => 'image',
                ':permalink' => 'https://instagram.com/p/C_welcome_' . $userId,
                ':likes' => 3840,
                ':comments' => 48,
                ':shares' => 820,
                ':impressions' => 48200,
                ':reach' => 31400,
                ':saved' => 1240,
                ':eng_rate' => 8.42
            ]);
            $postId = (int)$pdo->lastInsertId();

            // Insert Initial Starter Comment for this user
            $stmtComm = $pdo->prepare("
                INSERT INTO comments (user_id, post_id, platform, external_comment_id, author_name, author_handle, author_avatar, comment_text, sentiment, intent, highlight_score, is_highlighted, highlight_reason, likes_count, status)
                VALUES (:uid, :post_id, 'instagram', :ext_id, :author, :handle, :avatar, :text, :sentiment, :intent, :score, :is_high, :reason, :likes, :status)
            ");

            $stmtComm->execute([
                ':uid' => $userId,
                ':post_id' => $postId,
                ':ext_id' => 'cmt_user_' . $userId . '_1',
                ':author' => 'Alejandro Morales',
                ':handle' => '@alejandro_m',
                ':avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=96&h=96&fit=crop&crop=faces&auto=format&q=75',
                ':text' => 'Me cuesta mucho aceptar las cosas que no puedo controlar, especialmente cuando tengo problemas en el trabajo y siento que todo se derrumba. ¿Cómo puedo empezar a practicar la dicotomía del control en mi día a día?',
                ':sentiment' => 'question',
                ':intent' => 'lead_info',
                ':score' => 96,
                ':is_high' => 1,
                ':reason' => '🧠 Pregunta Filosófica de Alto Valor: Oportunidad de oro para profundizar y fidelizar con sabiduría estoica',
                ':likes' => 24,
                ':status' => 'pending'
            ]);
        }
    }
}
