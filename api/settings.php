<?php
/**
 * REST API: Settings & Brand Voice Training Controller
 * Hardened with CSRF, Rate Limiting & Strict Validation
 */
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../services/MetaApiService.php';
require_once __DIR__ . '/../services/AiAgentService.php';

Security::applySecurityHeaders(true);
Auth::requireAuth(true);

$userId = Auth::id();

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

try {
    if ($method === 'GET') {
        $settings = Settings::getAll();
        
        // Mask keys partially for safe display in dashboard
        $maskedGemini = !empty($settings['gemini_api_key']) ? substr($settings['gemini_api_key'], 0, 4) . '...' . substr($settings['gemini_api_key'], -4) : '';
        $maskedOpenAi = !empty($settings['openai_api_key']) ? substr($settings['openai_api_key'], 0, 4) . '...' . substr($settings['openai_api_key'], -4) : '';
        $maskedMetaToken = !empty($settings['meta_page_access_token']) ? substr($settings['meta_page_access_token'], 0, 6) . '...' . substr($settings['meta_page_access_token'], -4) : '';

        // Default training data if not yet saved
        $keyPhrases = !empty($settings['brand_key_phrases']) ? json_decode($settings['brand_key_phrases'], true) : [
            'Dicotomía del control', 'Amor Fati', 'Memento Mori', 'Autodominio', 'Fortaleza mental', 'Disciplina diaria'
        ];

        $forbiddenPhrases = !empty($settings['brand_forbidden_phrases']) ? json_decode($settings['brand_forbidden_phrases'], true) : [
            'Estimado cliente', 'Compra ya', 'Oferta imperdible', 'Somos un bot', 'Haz clic aquí'
        ];

        $fewShotExamples = !empty($settings['brand_few_shot_examples']) ? json_decode($settings['brand_few_shot_examples'], true) : AiAgentService::getDefaultFewShotExamples();

        echo json_encode([
            'success' => true,
            'data' => [
                'brand_name' => htmlspecialchars($settings['brand_name'] ?? 'Mente Estoica', ENT_QUOTES, 'UTF-8'),
                'brand_industry' => htmlspecialchars($settings['brand_industry'] ?? 'Estoicismo, Disciplina y Crecimiento Personal', ENT_QUOTES, 'UTF-8'),
                'brand_tone' => $settings['brand_tone'] ?? 'stoic_mentor',
                'brand_description' => htmlspecialchars($settings['brand_description'] ?? 'Comunidad dedicada a la filosofía estoica (Marco Aurelio, Séneca, Epicteto), disciplina diaria, resiliencia mental y autodominio.', ENT_QUOTES, 'UTF-8'),
                
                // Calibration Sliders & Identity Rules
                'brand_warmth_level' => (int)($settings['brand_warmth_level'] ?? 85),
                'brand_depth_level' => (int)($settings['brand_depth_level'] ?? 80),
                'brand_energy_level' => (int)($settings['brand_energy_level'] ?? 75),
                'brand_closing_question_rule' => $settings['brand_closing_question_rule'] ?? 'always',
                'brand_emoji_style' => $settings['brand_emoji_style'] ?? 'moderate',
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
                'meta_instagram_account_id' => htmlspecialchars($settings['meta_instagram_account_id'] ?? '', ENT_QUOTES, 'UTF-8'),
                'has_meta_token' => !empty($settings['meta_page_access_token']),
                'meta_page_access_token_masked' => $maskedMetaToken,
                'webhook_verify_token' => htmlspecialchars($settings['webhook_verify_token'] ?? 'social_boost_secure_token_2026', ENT_QUOTES, 'UTF-8')
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'POST') {
        // Enforce anti-CSRF check on all settings mutations
        Security::requireCsrf();
        Security::requireRateLimit('settings_mutate', 40, 60);

        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true) ?? $_POST;
        $action = Security::validateEnum($input['action'] ?? 'save_all', ['save_all', 'sync_meta', 'test_meta'], 'save_all');

        if ($action === 'test_meta') {
            $tokenToTest = !empty($input['meta_page_access_token']) && !str_contains($input['meta_page_access_token'], '...') ? trim($input['meta_page_access_token']) : null;
            $testResult = MetaApiService::testMetaConnection($tokenToTest);
            echo json_encode($testResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'sync_meta') {
            $syncResult = MetaApiService::syncFromMeta();
            echo json_encode($syncResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Validate and sanitize standard settings fields
        if (isset($input['brand_name'])) {
            Settings::set('brand_name', Security::sanitizeString($input['brand_name'], 150));
        }
        if (isset($input['brand_industry'])) {
            Settings::set('brand_industry', Security::sanitizeString($input['brand_industry'], 250));
        }
        if (isset($input['brand_tone'])) {
            $allowedTones = ['stoic_mentor', 'disciplined_drive', 'empathetic_brother', 'stoic_quotes', 'challenging', 'friendly_engaging'];
            Settings::set('brand_tone', Security::validateEnum($input['brand_tone'], $allowedTones, 'stoic_mentor'));
        }
        if (isset($input['brand_description'])) {
            Settings::set('brand_description', Security::sanitizeString($input['brand_description'], 2000));
        }

        // Calibration Sliders & Identity Rules
        if (isset($input['brand_warmth_level'])) {
            $w = Security::sanitizeInt($input['brand_warmth_level'], 1, 100, 85);
            Settings::set('brand_warmth_level', (string)$w);
        }
        if (isset($input['brand_depth_level'])) {
            $d = Security::sanitizeInt($input['brand_depth_level'], 1, 100, 80);
            Settings::set('brand_depth_level', (string)$d);
        }
        if (isset($input['brand_energy_level'])) {
            $e = Security::sanitizeInt($input['brand_energy_level'], 1, 100, 75);
            Settings::set('brand_energy_level', (string)$e);
        }
        if (isset($input['brand_closing_question_rule'])) {
            Settings::set('brand_closing_question_rule', Security::validateEnum($input['brand_closing_question_rule'], ['always', 'relevant', 'never'], 'always'));
        }
        if (isset($input['brand_emoji_style'])) {
            Settings::set('brand_emoji_style', Security::validateEnum($input['brand_emoji_style'], ['minimal', 'moderate', 'expressive'], 'moderate'));
        }

        // Arrays / JSON settings
        if (isset($input['brand_key_phrases'])) {
            $phrases = is_array($input['brand_key_phrases']) ? $input['brand_key_phrases'] : [];
            $cleanPhrases = array_values(array_filter(array_map(fn($p) => Security::sanitizeString((string)$p, 80), $phrases)));
            Settings::set('brand_key_phrases', json_encode($cleanPhrases, JSON_UNESCAPED_UNICODE));
        }

        if (isset($input['brand_forbidden_phrases'])) {
            $forbid = is_array($input['brand_forbidden_phrases']) ? $input['brand_forbidden_phrases'] : [];
            $cleanForbid = array_values(array_filter(array_map(fn($f) => Security::sanitizeString((string)$f, 80), $forbid)));
            Settings::set('brand_forbidden_phrases', json_encode($cleanForbid, JSON_UNESCAPED_UNICODE));
        }

        if (isset($input['brand_few_shot_examples'])) {
            $examples = is_array($input['brand_few_shot_examples']) ? $input['brand_few_shot_examples'] : [];
            $cleanExamples = [];
            foreach ($examples as $ex) {
                if (!empty($ex['comment']) && !empty($ex['reply'])) {
                    $cleanExamples[] = [
                        'tag' => Security::sanitizeString($ex['tag'] ?? 'general', 50),
                        'comment' => Security::sanitizeString($ex['comment'], 500),
                        'reply' => Security::sanitizeString($ex['reply'], 1000)
                    ];
                }
            }
            Settings::set('brand_few_shot_examples', json_encode($cleanExamples, JSON_UNESCAPED_UNICODE));
        }

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
            'message' => 'Identidad de marca y configuración de IA guardadas correctamente.'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
} catch (Throwable $e) {
    Security::sendJsonError('Error al procesar la configuración de marca.', $e);
}
