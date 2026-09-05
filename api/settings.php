<?php
/**
 * REST API: Settings & Multi-Brand Voice Controller
 * Supports Agency Multi-Brand Voice Management, CSRF & Rate Limiting
 */
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/CacheService.php';
require_once __DIR__ . '/../services/MetaApiService.php';
require_once __DIR__ . '/../services/AiAgentService.php';

Security::applySecurityHeaders(true);
Auth::requireAuth(true);

$userId = Auth::id();
$pdo = Database::getConnection();
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

try {
    if ($method === 'GET') {
        $action = trim($_GET['action'] ?? 'get_all');

        // 1. Action: List all brand voices for the user
        if ($action === 'list_brands') {
            $stmt = $pdo->prepare("SELECT * FROM brand_voices WHERE user_id = :uid ORDER BY is_default DESC, id ASC");
            $stmt->execute([':uid' => $userId]);
            $brands = $stmt->fetchAll();

            // If no brand voices exist yet, seed initial
            if (empty($brands)) {
                Database::seedInitialData($pdo, $userId);
                $stmt->execute([':uid' => $userId]);
                $brands = $stmt->fetchAll();
            }

            $activeBrandId = $_SESSION['active_brand_id'] ?? null;
            if (!$activeBrandId && !empty($brands)) {
                $activeBrandId = (int)$brands[0]['id'];
                $_SESSION['active_brand_id'] = $activeBrandId;
            }

            echo json_encode([
                'success' => true,
                'active_brand_id' => $activeBrandId,
                'brands' => $brands
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 1.1 Action: List all connected accounts with assigned brand voice
        if ($action === 'list_accounts') {
            $stmtAcc = $pdo->prepare("
                SELECT 
                    a.id,
                    a.user_id,
                    a.platform,
                    a.account_name,
                    a.account_handle,
                    a.page_id,
                    a.avatar_url,
                    a.is_active,
                    COALESCE(a.brand_voice_id, 1) as brand_voice_id,
                    COALESCE(bv.brand_name, 'Voz Predeterminada') as brand_voice_name,
                    COALESCE(bv.tone_level, 'friendly_engaging') as brand_voice_tone,
                    (SELECT COUNT(*) FROM posts WHERE account_id = a.id AND user_id = :uid) as posts_count,
                    (SELECT COUNT(*) FROM comments c JOIN posts p ON c.post_id = p.id WHERE p.account_id = a.id AND c.user_id = :uid) as comments_count
                FROM accounts a
                LEFT JOIN brand_voices bv ON a.brand_voice_id = bv.id
                WHERE a.user_id = :uid
                ORDER BY a.platform ASC, a.id ASC
            ");
            $stmtAcc->execute([':uid' => $userId]);
            $accounts = $stmtAcc->fetchAll();

            $stmtBrands = $pdo->prepare("SELECT id, brand_name, persona_name, tone_level, is_default FROM brand_voices WHERE user_id = :uid ORDER BY is_default DESC, id ASC");
            $stmtBrands->execute([':uid' => $userId]);
            $brands = $stmtBrands->fetchAll();

            echo json_encode([
                'success' => true,
                'accounts' => $accounts,
                'brands' => $brands
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 2. Action: Get specific brand voice
        if ($action === 'get_brand') {
            $brandId = (int)($_GET['id'] ?? ($_SESSION['active_brand_id'] ?? 0));
            $stmt = $pdo->prepare("SELECT * FROM brand_voices WHERE id = :id AND user_id = :uid LIMIT 1");
            $stmt->execute([':id' => $brandId, ':uid' => $userId]);
            $brand = $stmt->fetch();

            if (!$brand) {
                // Fallback to active brand
                $brand = AiAgentService::resolveActiveBrandVoice($pdo);
            }

            // Decode JSON fields
            $brand['key_phrases'] = !empty($brand['key_phrases']) ? json_decode($brand['key_phrases'], true) : [];
            $brand['forbidden_phrases'] = !empty($brand['forbidden_phrases']) ? json_decode($brand['forbidden_phrases'], true) : [];
            $brand['few_shot_examples'] = !empty($brand['few_shot_examples']) ? json_decode($brand['few_shot_examples'], true) : AiAgentService::getDefaultFewShotExamples();

            echo json_encode([
                'success' => true,
                'brand' => $brand
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 3. Default: Get all global settings & active brand voice merged
        $settings = Settings::getAll();
        $activeBrand = AiAgentService::resolveActiveBrandVoice($pdo);

        // Mask keys partially for safe display
        $maskedGemini = !empty($settings['gemini_api_key']) ? substr($settings['gemini_api_key'], 0, 4) . '...' . substr($settings['gemini_api_key'], -4) : '';
        $maskedOpenAi = !empty($settings['openai_api_key']) ? substr($settings['openai_api_key'], 0, 4) . '...' . substr($settings['openai_api_key'], -4) : '';
        $maskedMetaToken = !empty($settings['meta_page_access_token']) ? substr($settings['meta_page_access_token'], 0, 6) . '...' . substr($settings['meta_page_access_token'], -4) : '';
        $maskedMetaSecret = !empty($settings['meta_app_secret']) ? substr($settings['meta_app_secret'], 0, 4) . '...' . substr($settings['meta_app_secret'], -4) : '';

        $keyPhrases = !empty($activeBrand['key_phrases']) ? json_decode($activeBrand['key_phrases'], true) : [
            'Calidad garantizada', 'Atención personalizada', 'Envíos a todo el país', 'Comunidad oficial', 'Asesoría directa'
        ];

        $forbiddenPhrases = !empty($activeBrand['forbidden_phrases']) ? json_decode($activeBrand['forbidden_phrases'], true) : [
            'Estimado cliente', 'Compra ya', 'Oferta engañosa', 'Somos un bot', 'Haz clic aquí'
        ];

        $fewShotExamples = !empty($activeBrand['few_shot_examples']) ? json_decode($activeBrand['few_shot_examples'], true) : AiAgentService::getDefaultFewShotExamples();

        echo json_encode([
            'success' => true,
            'data' => [
                'active_brand_id' => (int)($activeBrand['id'] ?? 1),
                'brand_name' => htmlspecialchars($activeBrand['brand_name'] ?? 'Xindro Studio', ENT_QUOTES, 'UTF-8'),
                'persona_name' => htmlspecialchars($activeBrand['persona_name'] ?? 'Alex — Asistente de Marca', ENT_QUOTES, 'UTF-8'),
                'brand_industry' => htmlspecialchars($activeBrand['industry'] ?? 'Comercio Electrónico & Creadores', ENT_QUOTES, 'UTF-8'),
                'brand_tone' => $activeBrand['tone_level'] ?? 'friendly_engaging',
                'language' => $activeBrand['language'] ?? 'es',
                'brand_description' => htmlspecialchars($activeBrand['system_prompt'] ?? 'Marca dedicada a ofrecer soluciones innovadoras y atención personalizada a la comunidad.', ENT_QUOTES, 'UTF-8'),
                
                // Calibration Sliders & Identity Rules
                'brand_warmth_level' => (int)($activeBrand['warmth_level'] ?? 85),
                'brand_depth_level' => (int)($activeBrand['depth_level'] ?? 75),
                'brand_energy_level' => (int)($activeBrand['energy_level'] ?? 80),
                'brand_closing_question_rule' => $activeBrand['closing_question_rule'] ?? 'always',
                'brand_emoji_style' => $activeBrand['emoji_style'] ?? 'moderate',
                'brand_key_phrases' => is_array($keyPhrases) ? $keyPhrases : [],
                'brand_forbidden_phrases' => is_array($forbiddenPhrases) ? $forbiddenPhrases : [],
                'brand_few_shot_examples' => is_array($fewShotExamples) ? $fewShotExamples : [],

                // Engine & Keys
                'ai_provider' => $settings['ai_provider'] ?? 'gemini',
                'has_gemini_key' => !empty($settings['gemini_api_key']),
                'gemini_api_key_masked' => $maskedGemini,
                'has_openai_key' => !empty($settings['openai_api_key']),
                'openai_api_key_masked' => $maskedOpenAi,
                'autopilot_enabled' => ($settings['autopilot_enabled'] ?? '0') === '1' ? '1' : '0',
                'autopilot_min_score' => (int)($settings['autopilot_min_score'] ?? 60),
                
                // Meta Graph API
                'meta_app_id' => htmlspecialchars($settings['meta_app_id'] ?? '', ENT_QUOTES, 'UTF-8'),
                'has_meta_secret' => !empty($settings['meta_app_secret']),
                'meta_app_secret_masked' => $maskedMetaSecret,
                'meta_instagram_account_id' => htmlspecialchars($settings['meta_instagram_account_id'] ?? '', ENT_QUOTES, 'UTF-8'),
                'has_meta_token' => !empty($settings['meta_page_access_token']),
                'meta_page_access_token_masked' => $maskedMetaToken,
                'webhook_verify_token' => htmlspecialchars($settings['webhook_verify_token'] ?? 'social_boost_secure_token_2026', ENT_QUOTES, 'UTF-8')
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'POST') {
        Security::requireCsrf();
        Security::requireRateLimit('settings_mutate', 50, 60);

        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true) ?? $_POST;
        $action = Security::validateEnum($input['action'] ?? 'save_all', [
            'save_all', 'save_brand', 'set_active_brand', 'delete_brand', 'assign_account_brand', 'sync_meta', 'test_meta', 'audit_meta'
        ], 'save_all');

        // 0. Action: Assign Brand Voice to a Connected Account (Multi-Account Routing)
        if ($action === 'assign_account_brand') {
            $accountId = (int)($input['account_id'] ?? 0);
            $brandVoiceId = (int)($input['brand_voice_id'] ?? 0);

            if ($accountId <= 0 || $brandVoiceId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'account_id y brand_voice_id son requeridos.']);
                exit;
            }

            // Verify brand voice belongs to user
            $bvCheck = $pdo->prepare("SELECT id, brand_name FROM brand_voices WHERE id = :bvid AND user_id = :uid LIMIT 1");
            $bvCheck->execute([':bvid' => $brandVoiceId, ':uid' => $userId]);
            $bv = $bvCheck->fetch();

            if (!$bv) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Voz de Marca no encontrada o no pertenece a tu cuenta.']);
                exit;
            }

            // Update account
            $stmtAcc = $pdo->prepare("UPDATE accounts SET brand_voice_id = :bvid WHERE id = :id AND user_id = :uid");
            $stmtAcc->execute([':bvid' => $brandVoiceId, ':id' => $accountId, ':uid' => $userId]);

            // Propagate to all posts belonging to this account
            $stmtPosts = $pdo->prepare("UPDATE posts SET brand_voice_id = :bvid WHERE account_id = :id AND user_id = :uid");
            $stmtPosts->execute([':bvid' => $brandVoiceId, ':id' => $accountId, ':uid' => $userId]);

            CacheService::invalidateAccountMappings();

            echo json_encode([
                'success' => true,
                'message' => "Voz de Marca '{$bv['brand_name']}' asignada correctamente a la cuenta.",
                'account_id' => $accountId,
                'brand_voice_id' => $brandVoiceId,
                'brand_voice_name' => $bv['brand_name']
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 1. Action: Switch Active Brand Voice
        if ($action === 'set_active_brand') {
            $brandId = (int)($input['brand_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT id, brand_name, persona_name FROM brand_voices WHERE id = :id AND user_id = :uid LIMIT 1");
            $stmt->execute([':id' => $brandId, ':uid' => $userId]);
            $brand = $stmt->fetch();

            if ($brand) {
                $_SESSION['active_brand_id'] = (int)$brand['id'];
                echo json_encode([
                    'success' => true,
                    'message' => 'Marca activa cambiada a ' . $brand['brand_name'],
                    'brand' => $brand
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success' => false, 'error' => 'Marca no encontrada']);
            }
            exit;
        }

        // 2. Action: Save or Update Brand Voice
        if ($action === 'save_brand') {
            $brandId = isset($input['brand_id']) && !empty($input['brand_id']) ? (int)$input['brand_id'] : null;
            $brandName = Security::sanitizeString($input['brand_name'] ?? 'Nueva Marca', 150);
            $personaName = Security::sanitizeString($input['persona_name'] ?? 'Asistente de Marca', 100);
            $industry = Security::sanitizeString($input['brand_industry'] ?? $input['industry'] ?? 'Comercio & Creadores', 200);
            $toneLevel = Security::validateEnum($input['brand_tone'] ?? $input['tone_level'] ?? 'friendly_engaging', [
                'friendly_engaging', 'commercial_sales', 'executive_formal', 'humorous_casual', 'educational_expert'
            ], 'friendly_engaging');
            $language = Security::validateEnum($input['language'] ?? 'es', ['es', 'en', 'pt', 'any'], 'es');
            $systemPrompt = Security::sanitizeString($input['brand_description'] ?? $input['system_prompt'] ?? 'Asistente oficial de la marca.', 3000);

            $warmth = Security::sanitizeInt($input['brand_warmth_level'] ?? $input['warmth_level'] ?? 85, 1, 100, 85);
            $depth = Security::sanitizeInt($input['brand_depth_level'] ?? $input['depth_level'] ?? 75, 1, 100, 75);
            $energy = Security::sanitizeInt($input['brand_energy_level'] ?? $input['energy_level'] ?? 80, 1, 100, 80);
            $closingRule = Security::validateEnum($input['brand_closing_question_rule'] ?? $input['closing_question_rule'] ?? 'always', ['always', 'relevant', 'never'], 'always');
            $emojiStyle = Security::validateEnum($input['brand_emoji_style'] ?? $input['emoji_style'] ?? 'moderate', ['minimal', 'moderate', 'expressive'], 'moderate');

            // Arrays
            $keyPhrases = is_array($input['brand_key_phrases'] ?? null) ? $input['brand_key_phrases'] : [];
            $cleanKeyPhrases = array_values(array_filter(array_map(fn($p) => Security::sanitizeString((string)$p, 80), $keyPhrases)));
            $keyPhrasesJson = json_encode($cleanKeyPhrases, JSON_UNESCAPED_UNICODE);

            $forbiddenPhrases = is_array($input['brand_forbidden_phrases'] ?? null) ? $input['brand_forbidden_phrases'] : [];
            $cleanForbid = array_values(array_filter(array_map(fn($f) => Security::sanitizeString((string)$f, 80), $forbiddenPhrases)));
            $forbiddenPhrasesJson = json_encode($cleanForbid, JSON_UNESCAPED_UNICODE);

            $fewShots = is_array($input['brand_few_shot_examples'] ?? null) ? $input['brand_few_shot_examples'] : [];
            $cleanFewShots = [];
            foreach ($fewShots as $ex) {
                if (!empty($ex['comment']) && !empty($ex['reply'])) {
                    $cleanFewShots[] = [
                        'tag' => Security::sanitizeString($ex['tag'] ?? 'general', 50),
                        'comment' => Security::sanitizeString($ex['comment'], 500),
                        'reply' => Security::sanitizeString($ex['reply'], 1000)
                    ];
                }
            }
            $fewShotsJson = json_encode(!empty($cleanFewShots) ? $cleanFewShots : AiAgentService::getDefaultFewShotExamples(), JSON_UNESCAPED_UNICODE);

            if ($brandId) {
                // Update
                $stmtUp = $pdo->prepare("
                    UPDATE brand_voices 
                    SET brand_name = :bname, persona_name = :pname, industry = :industry,
                        tone_level = :tone, language = :lang, system_prompt = :prompt,
                        warmth_level = :warmth, depth_level = :depth, energy_level = :energy,
                        closing_question_rule = :crule, emoji_style = :emojis,
                        key_phrases = :kphrases, forbidden_phrases = :fphrases, few_shot_examples = :fewshots
                    WHERE id = :id AND user_id = :uid
                ");
                $stmtUp->execute([
                    ':bname' => $brandName,
                    ':pname' => $personaName,
                    ':industry' => $industry,
                    ':tone' => $toneLevel,
                    ':lang' => $language,
                    ':prompt' => $systemPrompt,
                    ':warmth' => $warmth,
                    ':depth' => $depth,
                    ':energy' => $energy,
                    ':crule' => $closingRule,
                    ':emojis' => $emojiStyle,
                    ':kphrases' => $keyPhrasesJson,
                    ':fphrases' => $forbiddenPhrasesJson,
                    ':fewshots' => $fewShotsJson,
                    ':id' => $brandId,
                    ':uid' => $userId
                ]);
            } else {
                // Insert new
                $stmtIns = $pdo->prepare("
                    INSERT INTO brand_voices (
                        user_id, brand_name, persona_name, industry, tone_level, language, system_prompt,
                        warmth_level, depth_level, energy_level, closing_question_rule, emoji_style,
                        key_phrases, forbidden_phrases, few_shot_examples, is_default
                    ) VALUES (
                        :uid, :bname, :pname, :industry, :tone, :lang, :prompt,
                        :warmth, :depth, :energy, :crule, :emojis,
                        :kphrases, :fphrases, :fewshots, 0
                    )
                ");
                $stmtIns->execute([
                    ':uid' => $userId,
                    ':bname' => $brandName,
                    ':pname' => $personaName,
                    ':industry' => $industry,
                    ':tone' => $toneLevel,
                    ':lang' => $language,
                    ':prompt' => $systemPrompt,
                    ':warmth' => $warmth,
                    ':depth' => $depth,
                    ':energy' => $energy,
                    ':crule' => $closingRule,
                    ':emojis' => $emojiStyle,
                    ':kphrases' => $keyPhrasesJson,
                    ':fphrases' => $forbiddenPhrasesJson,
                    ':fewshots' => $fewShotsJson
                ]);
                $brandId = (int)$pdo->lastInsertId();
                $_SESSION['active_brand_id'] = $brandId;
            }

            // Also keep settings in sync for active brand
            Settings::set('brand_name', $brandName);
            Settings::set('brand_industry', $industry);
            Settings::set('brand_tone', $toneLevel);
            Settings::set('brand_description', $systemPrompt);

            // Invalidate Brand Voice cache
            CacheService::invalidateBrandVoice($userId);

            echo json_encode([
                'success' => true,
                'message' => 'Voz de Marca guardada correctamente.',
                'brand_id' => $brandId
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 3. Action: Delete Brand Voice
        if ($action === 'delete_brand') {
            $brandId = (int)($input['brand_id'] ?? 0);
            
            // Check count
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM brand_voices WHERE user_id = :uid");
            $countStmt->execute([':uid' => $userId]);
            if ((int)$countStmt->fetchColumn() <= 1) {
                echo json_encode(['success' => false, 'error' => 'No puedes eliminar la única marca existente en tu cuenta.']);
                exit;
            }

            $stmtDel = $pdo->prepare("DELETE FROM brand_voices WHERE id = :id AND user_id = :uid");
            $stmtDel->execute([':id' => $brandId, ':uid' => $userId]);

            // Invalidate Brand Voice cache
            CacheService::invalidateBrandVoice($userId);

            // If active was deleted, reset to first
            if (($_SESSION['active_brand_id'] ?? 0) === $brandId) {
                unset($_SESSION['active_brand_id']);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Marca eliminada correctamente.'
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 4. Meta Actions
        if ($action === 'test_meta') {
            $tokenToTest = !empty($input['meta_page_access_token']) && !str_contains($input['meta_page_access_token'], '...') ? trim($input['meta_page_access_token']) : null;
            $testResult = MetaApiService::testMetaConnection($tokenToTest);
            echo json_encode($testResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'sync_meta') {
            $syncResult = MetaApiService::syncFromMeta($userId);
            echo json_encode($syncResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'audit_meta') {
            $auditResult = MetaApiService::auditAppReviewReadiness($userId);
            echo json_encode($auditResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 5. Action: save_all (Legacy & Global Engine Settings)
        if (isset($input['ai_provider'])) {
            Settings::set('ai_provider', Security::validateEnum($input['ai_provider'], ['gemini', 'openai', 'heuristic'], 'gemini'));
        }
        if (isset($input['autopilot_enabled'])) {
            $val = ($input['autopilot_enabled'] === '1' || $input['autopilot_enabled'] === 1 || $input['autopilot_enabled'] === true) ? '1' : '0';
            Settings::set('autopilot_enabled', $val);
        }
        if (isset($input['autopilot_min_score'])) {
            $score = Security::sanitizeInt($input['autopilot_min_score'], 0, 100, 60);
            Settings::set('autopilot_min_score', (string)$score);
        }
        if (isset($input['meta_app_id'])) {
            Settings::set('meta_app_id', Security::sanitizeString($input['meta_app_id'], 100));
        }
        if (isset($input['meta_app_secret']) && !str_contains($input['meta_app_secret'], '...')) {
            Settings::set('meta_app_secret', trim(Security::sanitizeString($input['meta_app_secret'], 150)));
        }
        if (isset($input['meta_instagram_account_id'])) {
            Settings::set('meta_instagram_account_id', Security::sanitizeString($input['meta_instagram_account_id'], 100));
        }
        if (isset($input['webhook_verify_token'])) {
            Settings::set('webhook_verify_token', Security::sanitizeString($input['webhook_verify_token'], 150));
        }

        // Only update API keys if a new non-masked string is sent
        if (!empty($input['gemini_api_key']) && !str_contains($input['gemini_api_key'], '...')) {
            Settings::set('gemini_api_key', trim(Security::sanitizeString($input['gemini_api_key'], 200)));
        }
        if (!empty($input['openai_api_key']) && !str_contains($input['openai_api_key'], '...')) {
            Settings::set('openai_api_key', trim(Security::sanitizeString($input['openai_api_key'], 200)));
        }
        if (!empty($input['meta_page_access_token']) && !str_contains($input['meta_page_access_token'], '...')) {
            Settings::set('meta_page_access_token', trim(Security::sanitizeString($input['meta_page_access_token'], 500)));
        }

        echo json_encode([
            'success' => true,
            'message' => 'Configuración de IA y claves guardadas correctamente.'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
} catch (Throwable $e) {
    Security::sendJsonError('Error al procesar la configuración de marca.', $e);
}
