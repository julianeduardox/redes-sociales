<?php
/**
 * AiAgentService - Agnostic Multi-Niche & Multi-Brand AI Engine for Creators & Agencies
 * Features:
 * - Dynamic System Prompt builder based on active Brand Voice / Client configuration
 * - Universal Commercial Intent Classifier (Leads, Pricing, Objections, Support, Testimonials)
 * - Zero-Token Local Heuristic Engine calibrated with brand guidelines & persona
 * - Golden Few-Shot Master Examples Learning
 * - Negative Constraints (Forbidden Words / Blacklist) & Key Brand Concepts
 * - OpenRouter unified multi-model integration (Claude 3.5 Sonnet, DeepSeek, GPT-4o, Llama 3.3)
 */
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/CacheService.php';

class AiAgentService {

    /**
     * Evaluate if a comment is suitable for Auto-Responder or if it should be marked as SPAM / FOREIGN / STICKER
     */
    public static function evaluateCommentSuitability(string $commentText, string $allowedLang = 'es'): array {
        $text = trim($commentText);
        $textLower = mb_strtolower($text, 'UTF-8');

        // 1. Check for Link Spam / Crypto / Bot Promotion
        $spamPatterns = [
            'http://', 'https://', 'www.', '.com', '.io', '.xyz', '.net', '.org', 't.me/', 'wa.me/',
            'telegram', 'whatsapp', 'send pic on', 'promote on', 'promote it on', 'dm me on', 'inbox me on',
            'check my bio', 'clic en mi bio', 'ganar dinero', 'inversion segura', 'inversión segura',
            'trabajo desde casa', 'crypto', 'bitcoin', 'binance', 'forex', 'trading bot', 'free followers',
            'ganar seguidores', 'hacks', 'recupero cuentas', 'recuperar cuenta', 'dm us on', 'send dm to',
            'follow us on', 'check out our', 'hire me', 'investment platform', 'tinder', 'onlyfans'
        ];

        foreach ($spamPatterns as $sp) {
            if (str_contains($textLower, $sp)) {
                return [
                    'status' => 'spam',
                    'should_reply' => false,
                    'reason' => '🚫 Marcado como Spam / Bot promocional o enlace externo para revisión',
                    'category' => 'spam'
                ];
            }
        }

        // 2. Check for Foreign Language if language is strictly Spanish
        if ($allowedLang === 'es') {
            $englishPhrases = [
                'check dm', 'nice post', 'follow me', 'follow back', 'love this', 'amazing post',
                'great shot', 'check out', 'hit me up', 'reach out', 'send dm', 'let me know',
                'thank you so much', 'good morning', 'nice one', 'so inspiring',
                'proud of you', 'keep it up', 'well said', 'what a view', 'awesome capture',
                'great content', 'dm to get', 'link in bio', 'great post', 'beautiful picture'
            ];

            foreach ($englishPhrases as $ep) {
                if (str_contains($textLower, $ep)) {
                    return [
                        'status' => 'spam',
                        'should_reply' => false,
                        'reason' => '🌐 Marcado para revisión: Comentario en idioma extranjero (Inglés detectado)',
                        'category' => 'foreign_language'
                    ];
                }
            }

            $englishWords = ['\bthe\b', '\band\b', '\bwith\b', '\bfrom\b', '\bhave\b', '\bthis\b', '\bthat\b', '\bwhat\b', '\byour\b', '\babout\b', '\bwould\b', '\bthere\b', '\btheir\b', '\bwill\b', '\bwhich\b', '\bvery\b', '\bbecause\b', '\bwhere\b', '\bpeople\b', '\breally\b', '\bcould\b', '\bshould\b', '\bplease\b', '\btoday\b', '\blooking\b', '\balways\b', '\bawesome\b', '\bgreat\b', '\bnice\b', '\bpost\b', '\bpicture\b'];
            $spanishWords = ['\bel\b', '\bla\b', '\blos\b', '\blas\b', '\bun\b', '\buna\b', '\bde\b', '\ben\b', '\bque\b', '\bqué\b', '\bpor\b', '\bpara\b', '\bcon\b', '\bsin\b', '\bsobre\b', '\beste\b', '\besta\b', '\besto\b', '\bcomo\b', '\bcómo\b', '\bpero\b', '\bgracias\b', '\bbuen\b', '\bbuena\b', '\bvida\b', '\btodo\b', '\btoda\b', '\bmuy\b', '\bmas\b', '\bmás\b', '\bmensaje\b', '\bprecio\b', '\binfo\b'];

            $engCount = 0;
            foreach ($englishWords as $ew) {
                if (preg_match('/' . $ew . '/iu', $textLower)) {
                    $engCount++;
                }
            }

            $spaCount = 0;
            foreach ($spanishWords as $sw) {
                if (preg_match('/' . $sw . '/iu', $textLower)) {
                    $spaCount++;
                }
            }

            if ($engCount >= 2 && $spaCount === 0) {
                return [
                    'status' => 'spam',
                    'should_reply' => false,
                    'reason' => '🌐 Marcado para revisión: Comentario en idioma extranjero (Inglés detectado)',
                    'category' => 'foreign_language'
                ];
            }
        }

        // 3. Check for Stickers / Pure Emojis (No text / fewer than 2 letters)
        $textNoEmoji = preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F700}-\x{1F77F}\x{1F780}-\x{1F7FF}\x{1F800}-\x{1F8FF}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{1FA70}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{2300}-\x{23FF}\x{2B50}\x{200D}\x{FE0F}\s\p{P}]/u', '', $text);
        
        if (mb_strlen($textNoEmoji, 'UTF-8') < 2) {
            return [
                'status' => 'ignored',
                'should_reply' => false,
                'reason' => '🎨 Comentario de solo emojis/stickers (sin texto para responder)',
                'category' => 'sticker'
            ];
        }

        // 4. Valid comment
        return [
            'status' => 'valid',
            'should_reply' => true,
            'reason' => '✅ Comentario legítimo apto para responder',
            'category' => 'valid'
        ];
    }

    /**
     * Universal Intent & Sentiment Commercial Classifier
     */
    public static function analyzeComment(string $commentText, string $postCaption = '', int $likesCount = 0): array {
        $suitability = self::evaluateCommentSuitability($commentText);
        if (!$suitability['should_reply']) {
            return [
                'sentiment' => $suitability['status'] === 'spam' ? 'spam' : 'neutral',
                'intent' => $suitability['category'],
                'highlight_score' => $suitability['status'] === 'spam' ? 10 : 25,
                'commercial_priority' => $suitability['status'] === 'spam' ? 10 : 25,
                'is_highlighted' => 0,
                'highlight_reason' => $suitability['reason'],
                'autopilot_ready' => false,
                'autopilot_status' => 'ignored',
                'autopilot_reason' => $suitability['reason'],
                'detected_keywords' => []
            ];
        }

        $textLower = mb_strtolower($commentText, 'UTF-8');
        
        $score = 50;
        $sentiment = 'neutral';
        $intent = 'general';
        $highlightReason = 'Comentario de la comunidad';
        $keywords = [];
        $autopilotReady = true;
        $autopilotStatus = 'ready';
        $autopilotReason = '✅ Respuesta verificada apta para publicación en Autopilot';

        // 1. Philosophical, Stoic, Conceptual & Mentorship QA (Dicotomía del control, mentalidad, disciplina, conceptos)
        $conceptPatterns = [
            'dicotomia', 'dicotomía', 'dicotomia del control', 'dicotomía del control', 'estoicismo', 'estoico', 'estoica',
            'marco aurelio', 'seneca', 'séneca', 'epicteto', 'epícteto', 'amor fati', 'memento mori', 'autodominio',
            'fortaleza mental', 'disciplina diaria', 'forjar disciplina', 'como aplicar', 'cómo aplicar', 'que significa',
            'qué significa', 'miedo al fracaso', 'procrastino', 'procrastinar', 'procrastinacion', 'procrastinación',
            'sin motivacion', 'sin motivación', 'falta de motivacion', 'falta de motivación', 'consejo', 'reflexion',
            'reflexión', 'filosofia', 'filosofía', 'sabiduria', 'sabiduría', 'crecimiento personal', 'mentalidad',
            'obstaculo es el camino', 'obstáculo es el camino', 'el obstaculo', 'el obstáculo', 'habito', 'hábito'
        ];

        // 2. Commercial Leads / Course / Product / Pricing / Access / Buying Intent
        $leadPatterns = [
            'precio', 'precios', 'costo', 'costos', 'cuanto vale', 'cuánto vale', 'cuanto cuesta', 'cuánto cuesta',
            'como comprar', 'cómo comprar', 'donde comprar', 'dónde comprar', 'donde estan', 'dónde están',
            'clases grabadas', 'clase grabada', 'grabadas', 'grabada', 'tiempo de acceso', 'cuanto tiempo tengo',
            'cuánto tiempo tengo', 'cuanto tiempo dura', 'cuánto tiempo dura', 'acceso de por vida', 'duracion',
            'duración', 'temario', 'contenido del curso', 'certificado', 'certificacion', 'certificación',
            'envio', 'envíos', 'envío', 'informacion', 'información', 'catalogo', 'catálogo', 'info',
            'link', 'enlace', 'dm', 'inbox', 'disponible', 'disponibles', 'stock', 'promocion', 'promoción',
            'descuento', 'descuentos', 'cotizacion', 'cotización', 'agendar', 'agenda', 'asesoria', 'asesoría',
            'me interesa', 'quiero uno', 'quiero mas info', 'quiero más info', 'como contrato', 'cómo contrato',
            'inscripcion', 'inscripción', 'matricula', 'matrícula', 'cupo', 'cupos'
        ];

        // 3. Sales Objections / Guarantees / Trust / Shipping Time
        $objectionPatterns = [
            'garantia', 'garantía', 'seguro', 'es seguro', 'devolucion', 'devolución', 'tarda mucho',
            'cuanto tarda', 'cuánto tarda', 'confiable', 'es confiable', 'estafa', 'funciona', 'realmente funciona',
            'vale la pena', 'duda', 'desconfianza', 'testimonios'
        ];

        // 4. Customer Support / Real Post-Sale Issues / Platform Access (Strictly isolated from conceptual words)
        $supportPatterns = [
            'no puedo ingresar', 'no puedo entrar', 'error al ingresar', 'falla la plataforma', 'error en la plataforma',
            'clave incorrecta', 'contraseña incorrecta', 'error de contraseña', 'error de login', 'problema tecnico',
            'problema técnico', 'problema para entrar', 'problema para ingresar', 'no me deja entrar', 'no me deja ingresar',
            'no me llego el acceso', 'no me llegó el acceso', 'no me llego el correo', 'no me llegó el correo',
            'problema con el pago', 'error en el pago', 'mi pedido', 'estado de mi orden', 'numero de orden',
            'número de orden', 'solicitar factura', 'pedir factura', 'hacer un reclamo', 'reportar error',
            'soporte tecnico', 'soporte técnico', 'ayuda con mi compra', 'no puedo ver el curso'
        ];

        // 5. Testimonials / High Gratitude / Praise
        $praisePatterns = [
            'excelente', 'increible', 'increíble', 'me encanto', 'me encantó', 'buenisimo', 'buenísimo',
            'genial', 'recomiendo', 'recomendado', 'lo mejor', 'felicitaciones', 'gran trabajo', 'super',
            'súper', 'top', 'felicidades', 'gracias infinitas', 'cambio mi vida', 'cambió mi vida', 'los mejores'
        ];

        // Detect Conceptual / Philosophy / Stoic / Mentorship First
        $foundConcepts = [];
        foreach ($conceptPatterns as $p) {
            if (str_contains($textLower, $p)) {
                $foundConcepts[] = $p;
            }
        }

        // Detect Leads & Buying Intent
        $foundLeads = [];
        foreach ($leadPatterns as $p) {
            if (str_contains($textLower, $p)) {
                $foundLeads[] = $p;
            }
        }

        // Detect Sales Objections
        $foundObjections = [];
        foreach ($objectionPatterns as $p) {
            if (str_contains($textLower, $p)) {
                $foundObjections[] = $p;
            }
        }

        // Detect Customer Support
        $foundSupport = [];
        foreach ($supportPatterns as $p) {
            if (str_contains($textLower, $p)) {
                $foundSupport[] = $p;
            }
        }

        // Detect Praise & Testimonials
        $foundPraise = [];
        foreach ($praisePatterns as $p) {
            if (str_contains($textLower, $p)) {
                $foundPraise[] = $p;
            }
        }

        // Priority Classification
        if (!empty($foundConcepts)) {
            $sentiment = 'question';
            $intent = 'knowledge_concept';
            $score = 92;
            $highlightReason = '🧠 Consulta Conceptual & Mentoría: Pregunta filosófica o metodológica para aportar autoridad de marca';
            $keywords = $foundConcepts;
            $autopilotReady = true;
            $autopilotStatus = 'ready';
            $autopilotReason = '✔ Apto para Autopilot (Respuesta conceptual verificada y fundamentada)';
        } elseif (!empty($foundLeads)) {
            $sentiment = 'question';
            $intent = 'lead_info';
            $score = 96;
            $highlightReason = '🎯 Oportunidad Comercial / Lead Calificado: Consulta de compra, acceso o programa formativo';
            $keywords = $foundLeads;
            
            // Check if it's a general verified question vs custom pricing negotiation
            $hasSpecificCustomQuery = (str_contains($textLower, 'descuento especial') || str_contains($textLower, 'pagar en cuotas') || str_contains($textLower, 'presupuesto personalizado'));
            if ($hasSpecificCustomQuery) {
                $autopilotReady = false;
                $autopilotStatus = 'needs_review';
                $autopilotReason = '⚠️ Prioridad comercial alta (96/100), pero requiere revisión humana por consultar condiciones financieras personalizadas';
            } else {
                $autopilotReady = true;
                $autopilotStatus = 'ready';
                $autopilotReason = '✔ Apto para Autopilot (Respuesta comercial directa con enlace oficial)';
            }
        } elseif (!empty($foundObjections)) {
            $sentiment = 'question';
            $intent = 'sales_objection';
            $score = 90;
            $highlightReason = '🛡️ Objeción de Venta / Garantía: Resuelve la duda con autoridad, transparencia y confianza';
            $keywords = $foundObjections;
            $autopilotReady = true;
            $autopilotStatus = 'ready';
            $autopilotReason = '✔ Apto para Autopilot (Garantías y términos institucionales verificados)';
        } elseif (!empty($foundSupport)) {
            $sentiment = 'urgent';
            $intent = 'customer_support';
            $score = 94;
            $highlightReason = '🛠️ Soporte / Asistencia Técnica: Requiere atención personalizada por mensaje privado';
            $keywords = $foundSupport;
            $autopilotReady = false;
            $autopilotStatus = 'needs_review';
            $autopilotReason = '⚠️ Requiere revisión humana / canal privado para validar datos del usuario con seguridad';
        } elseif (!empty($foundPraise)) {
            $sentiment = 'positive';
            $intent = 'gratitude_praise';
            $score = 88;
            $highlightReason = '✨ Testimonio Positivo & Fidelización: Conecta y agradece para impulsar la prueba social';
            $keywords = $foundPraise;
            $autopilotReady = true;
            $autopilotStatus = 'ready';
            $autopilotReason = '✔ Apto para Autopilot (Agradecimiento cálido de la comunidad)';
        }

        // Question mark boost
        if (str_contains($commentText, '?') || str_contains($commentText, '¿')) {
            if ($sentiment === 'neutral') {
                $sentiment = 'question';
                $score = 80;
                $highlightReason = '❓ Pregunta de la comunidad que espera respuesta';
            }
        }

        // Social Proof Boost
        if ($likesCount >= 10) {
            $score += 8;
            $highlightReason .= ' (🔥 Alta tracción: ' . $likesCount . ' likes)';
        } elseif ($likesCount >= 5) {
            $score += 4;
        }

        // Length boost
        $length = mb_strlen($commentText);
        if ($length > 60 && $score < 95) {
            $score += 6;
        }

        $score = min(100, max(10, $score));
        $isHighlighted = ($score >= 80) ? 1 : 0;

        return [
            'sentiment' => $sentiment,
            'intent' => $intent,
            'highlight_score' => $score,
            'commercial_priority' => $score,
            'is_highlighted' => $isHighlighted,
            'highlight_reason' => $highlightReason,
            'autopilot_ready' => $autopilotReady,
            'autopilot_status' => $autopilotStatus,
            'autopilot_reason' => $autopilotReason,
            'detected_keywords' => $keywords
        ];
    }

    /**
     * Generate 3 Universal AI response variations:
     * 1. 🤝 Conexión & Empatía (Cálida, humana, conversacional)
     * 2. 🎯 Conversión & Venta / CTA (Enfocada en valor, llamado a la acción, DM o link)
     * 3. 💡 Autoridad & Solución (Profesional, informativa, resolviendo dudas)
     */
    public static function generateReplies(
        string $authorName,
        string $commentText,
        string $platform = 'instagram',
        string $postCaption = '',
        string $overrideTone = '',
        array $runtimeOverrides = []
    ): array {
        $pdo = Database::getConnection();

        // 1. Resolve Brand Voice configuration
        $brandVoice = self::resolveActiveBrandVoice($pdo, $runtimeOverrides);

        $brandName = $runtimeOverrides['brand_name'] ?? ($brandVoice['brand_name'] ?? Settings::get('brand_name', 'Xindro Studio'));
        $personaName = $runtimeOverrides['persona_name'] ?? ($brandVoice['persona_name'] ?? 'Alex — Asistente de Marca');
        $brandIndustry = $runtimeOverrides['brand_industry'] ?? ($brandVoice['industry'] ?? Settings::get('brand_industry', 'Comercio Electrónico & Creadores'));
        $brandTone = !empty($overrideTone) ? $overrideTone : ($runtimeOverrides['brand_tone'] ?? ($brandVoice['tone_level'] ?? 'friendly_engaging'));
        $brandDescription = $runtimeOverrides['brand_description'] ?? ($brandVoice['system_prompt'] ?? Settings::get('brand_description', 'Marca dedicada a aportar valor y atención de calidad a la comunidad.'));
        $language = $runtimeOverrides['language'] ?? ($brandVoice['language'] ?? 'es');
        
        $warmthLevel = (int)($runtimeOverrides['brand_warmth_level'] ?? ($brandVoice['warmth_level'] ?? Settings::get('brand_warmth_level', 85)));
        $depthLevel = (int)($runtimeOverrides['brand_depth_level'] ?? ($brandVoice['depth_level'] ?? Settings::get('brand_depth_level', 75)));
        $energyLevel = (int)($runtimeOverrides['brand_energy_level'] ?? ($brandVoice['energy_level'] ?? Settings::get('brand_energy_level', 80)));
        $closingQuestionRule = $runtimeOverrides['brand_closing_question_rule'] ?? ($brandVoice['closing_question_rule'] ?? Settings::get('brand_closing_question_rule', 'always'));
        $emojiStyle = $runtimeOverrides['brand_emoji_style'] ?? ($brandVoice['emoji_style'] ?? Settings::get('brand_emoji_style', 'moderate'));

        $keyPhrases = self::parseJsonSetting($runtimeOverrides['brand_key_phrases'] ?? ($brandVoice['key_phrases'] ?? Settings::get('brand_key_phrases', '')), [
            'Calidad garantizada', 'Atención personalizada', 'Envíos a todo el país', 'Comunidad oficial', 'Asesoría directa'
        ]);

        $forbiddenPhrases = self::parseJsonSetting($runtimeOverrides['brand_forbidden_phrases'] ?? ($brandVoice['forbidden_phrases'] ?? Settings::get('brand_forbidden_phrases', '')), [
            'Estimado cliente', 'Compra ya', 'Oferta engañosa', 'Somos un bot', 'Haz clic aquí'
        ]);

        $fewShotExamples = self::parseJsonSetting($runtimeOverrides['brand_few_shot_examples'] ?? ($brandVoice['few_shot_examples'] ?? Settings::get('brand_few_shot_examples', '')), self::getDefaultFewShotExamples());

        $aiProvider = $runtimeOverrides['ai_provider'] ?? Settings::get('ai_provider', 'openrouter');
        $openrouterKey = Settings::get('openrouter_api_key', '');
        $openrouterModel = $runtimeOverrides['openrouter_model'] ?? Settings::get('openrouter_model', 'anthropic/claude-3.5-sonnet');

        // Try OpenRouter API first if configured
        if ($aiProvider === 'openrouter' && !empty($openrouterKey)) {
            $openrouterResult = self::callOpenRouterApi(
                $authorName, $commentText, $platform, $postCaption, 
                $brandName, $personaName, $brandIndustry, $brandTone, $brandDescription, $language,
                $warmthLevel, $depthLevel, $energyLevel,
                $closingQuestionRule, $emojiStyle, $keyPhrases, $forbiddenPhrases, $fewShotExamples,
                $openrouterKey, $openrouterModel
            );
            if ($openrouterResult !== null && !empty($openrouterResult['engagement'])) {
                return self::sanitizeRepliesWithForbidden($openrouterResult, $forbiddenPhrases);
            }
        }

        // Fallback / Standalone: High-Context Calibrated Zero-Token Heuristic Engine
        $localResult = self::generateHeuristicReplies(
            $authorName, $commentText, $platform, $postCaption, 
            $brandName, $personaName, $brandIndustry, $brandTone, $brandDescription, $language,
            $warmthLevel, $depthLevel, $energyLevel,
            $closingQuestionRule, $emojiStyle, $keyPhrases, $forbiddenPhrases, $fewShotExamples
        );

        return self::sanitizeRepliesWithForbidden($localResult, $forbiddenPhrases);
    }

    /**
     * Built-in Calibrated Zero-Token Universal Engine
     */
    public static function generateHeuristicReplies(
        string $authorName,
        string $commentText,
        string $platform,
        string $postCaption,
        string $brandName,
        string $personaName = 'Asistente de Marca',
        string $brandIndustry = 'Comercio & Creadores',
        string $brandTone = 'friendly_engaging',
        string $brandDescription = '',
        string $language = 'es',
        int $warmthLevel = 85,
        int $depthLevel = 75,
        int $energyLevel = 80,
        string $closingQuestionRule = 'always',
        string $emojiStyle = 'moderate',
        array $keyPhrases = [],
        array $forbiddenPhrases = [],
        array $fewShotExamples = []
    ): array {
        $firstName = explode(' ', trim($authorName))[0] ?: 'amigo';
        $analysis = self::analyzeComment($commentText, $postCaption);
        $intent = $analysis['intent'];
        $textLower = mb_strtolower($commentText, 'UTF-8');

        // Check if there is a matching master few-shot example registered by the user
        $matchedExample = self::findMatchingFewShotExample($commentText, $fewShotExamples);

        // Emoji styling helper
        $eHeart = ($emojiStyle === 'minimal') ? '🤝' : (($emojiStyle === 'expressive') ? '🤝 ✨' : '🤝');
        $eRocket = ($emojiStyle === 'minimal') ? '🚀' : (($emojiStyle === 'expressive') ? '🚀 🎯' : '🚀');
        $eLight = ($emojiStyle === 'minimal') ? '💡' : (($emojiStyle === 'expressive') ? '💡 🌟' : '💡');
        $ePillar = ($emojiStyle === 'minimal') ? '🏛️' : (($emojiStyle === 'expressive') ? '🏛️ ✨' : '🏛️');

        // Warmth greetings
        if ($warmthLevel >= 80) {
            $greetEngage = "¡Hola $firstName! $eHeart";
            $greetConvert = "¡Qué tal $firstName! $eRocket";
            $greetSupport = "¡Hola $firstName! Con gusto te apoyo. $eLight";
        } elseif ($warmthLevel >= 50) {
            $greetEngage = "Hola $firstName $eHeart";
            $greetConvert = "Hola $firstName $eRocket";
            $greetSupport = "Hola $firstName $eLight";
        } else {
            $greetEngage = "$eHeart";
            $greetConvert = "$eRocket";
            $greetSupport = "$eLight";
        }

        // Closing Questions based on rule
        $questionLead = ($closingQuestionRule !== 'never') ? "¿Te gustaría conocer más detalles sobre el contenido o temario? 👇" : "Estamos a tu total disposición.";
        $questionGeneral = ($closingQuestionRule !== 'never') ? "¿En qué proyecto o idea estás trabajando hoy? 👇" : "¡Un saludo y seguimos en contacto!";
        $questionPraise = ($closingQuestionRule !== 'never') ? "¿De qué tema te gustaría que hablemos en el próximo post? 💬" : "¡Gracias por formar parte de la comunidad!";

        // If a master few-shot example matches closely, adapt it!
        if ($matchedExample) {
            return [
                'source' => 'heuristic_few_shot_trained',
                'engagement' => "$greetEngage " . self::adaptFewShotReply($matchedExample['reply'], $firstName) . ($closingQuestionRule === 'always' ? " " . $questionGeneral : ''),
                'conversion' => "$greetConvert " . self::adaptFewShotReply($matchedExample['reply'], $firstName),
                'support' => "$greetSupport " . self::adaptFewShotReply($matchedExample['reply'], $firstName),
                'engagement_tips' => '🧠 Respuesta enriquecida por el Ejemplo Maestro entrenado para este patrón.'
            ];
        }

        // Case 1: Knowledge / Philosophical / Stoic / Concept Explanation
        if ($intent === 'knowledge_concept') {
            $isDichotomy = str_contains($textLower, 'dicotomia') || str_contains($textLower, 'dicotomía') || str_contains($textLower, 'control');
            $isDiscipline = str_contains($textLower, 'disciplina') || str_contains($textLower, 'motivacion') || str_contains($textLower, 'motivación') || str_contains($textLower, 'procrastin');

            if ($isDichotomy) {
                return [
                    'source' => 'heuristic_calibrated',
                    'engagement' => "¡Hola $firstName! $ePillar La dicotomía del control consiste en enfocar el 100% de nuestra energía en lo que sí depende de nosotros (nuestras decisiones, acciones y actitud) y aceptar con serenidad lo externo. " . (($closingQuestionRule !== 'never') ? "¿En qué situación de tu día te gustaría empezar a aplicarlo? 👇" : "Un principio clave para el autodominio."),
                    'conversion' => "¡Qué gran tema, $firstName! $eRocket Dominar la dicotomía del control transforma por completo tu enfoque y claridad mental. En el enlace de nuestra biografía compartimos guías y recursos prácticos sobre mentalidad estoica si deseas profundizar. ¿Qué aspecto de tu rutina buscas fortalecer hoy?",
                    'support' => "Excelente consulta, $firstName. $eLight Para aplicarlo en lo cotidiano: ante cualquier obstáculo pregúntate '¿Está bajo mi control directo?'. Si lo está, actúa con determinación; si no, canaliza tu energía en tu propia respuesta y suelta lo demás. ¿Qué reto estás gestionando actualmente?",
                    'engagement_tips' => '🧠 Las respuestas fundamentadas en sabiduría y autoridad consolidan a tu marca como referente de valor.'
                ];
            }

            if ($isDiscipline) {
                return [
                    'source' => 'heuristic_calibrated',
                    'engagement' => "¡Hola $firstName! $eHeart La motivación es pasajera, pero la disciplina diaria se construye con pequeñas victorias cotidianas de 5 minutos. No busques perfección inmediata, sino consistencia. " . (($closingQuestionRule !== 'never') ? "¿Cuál es esa pequeña acción que puedes completar hoy? 👇" : "El progreso diario lo cambia todo."),
                    'conversion' => "¡Totalmente de acuerdo, $firstName! $eRocket Cuando aplicas un método estructurado, la disciplina se vuelve natural. Puedes consultar nuestras herramientas y metodologías en el enlace de la bio para dar el siguiente paso. ¿Te gustaría conocer más sobre el método?",
                    'support' => "¡Hola $firstName! $eLight La clave para vencer la procrastinación es dividir el objetivo en una sola micro-tarea que puedas empezar de inmediato. ¿En qué meta estás enfocado esta semana?",
                    'engagement_tips' => '💡 Aportar consejos prácticos y accionables fomenta conversaciones de alto engagement.'
                ];
            }

            return [
                'source' => 'heuristic_calibrated',
                'engagement' => "¡Hola $firstName! $ePillar Los principios sólidos nos permiten mantener el rumbo sin importar las circunstancias externas. " . (($closingQuestionRule !== 'never') ? "¿Qué concepto o hábito te ha resultado más transformador? 💬" : "Un gusto reflexionar juntos en comunidad."),
                'conversion' => "¡Excelente reflexión, $firstName! $eRocket Profundizar en estos fundamentos marca la diferencia en cualquier proyecto. Te invitamos a revisar los recursos formativos en el enlace de nuestra biografía. ¿En qué área estás buscando evolucionar hoy?",
                'support' => "¡Hola $firstName! $eLight La claridad mental surge de la práctica constante y el pensamiento reflexivo. Con gusto seguimos compartiendo contenidos sobre este tema. ¿Qué duda puntual te gustaría que abordemos en el próximo post?",
                'engagement_tips' => '🏛️ El contenido de valor y reflexión genera seguidores altamente fidelizados.'
            ];
        }

        // Case 2: Commercial Lead / Course / Product / Pricing
        if ($intent === 'lead_info') {
            $isCourseStructure = str_contains($textLower, 'clases grabadas') || str_contains($textLower, 'grabada') || str_contains($textLower, 'tiempo de acceso') || str_contains($textLower, 'cuanto tiempo') || str_contains($textLower, 'cuánto tiempo') || str_contains($textLower, 'duracion') || str_contains($textLower, 'duración') || str_contains($textLower, 'temario');

            if ($isCourseStructure) {
                return [
                    'source' => 'heuristic_calibrated',
                    'engagement' => "¡Hola $firstName! $eHeart Sí, el programa incluye acceso flexible a clases grabadas para que avances a tu propio ritmo con acceso continuo y material de apoyo. " . $questionLead,
                    'conversion' => "¡Hola $firstName! $eRocket Efectivamente, cuentas con acceso a todas las clases grabadas, recursos prácticos y actualizaciones del curso. Puedes consultar el temario completo y registrarte directamente en el enlace de nuestra biografía o enviarnos un DM si tienes alguna pregunta puntual.",
                    'support' => "¡Con gusto, $firstName! $eLight El contenido formativo está estructurado en módulos grabados de alta calidad para repasar cuantas veces necesites. Encuentras la información oficial y los módulos en el enlace de nuestro perfil. ¿Tienes alguna duda sobre los temas incluidos?",
                    'engagement_tips' => '🎯 Responder directamente a dudas técnicas del curso genera confianza inmediata y acelera la decisión de compra.'
                ];
            }

            return [
                'source' => 'heuristic_calibrated',
                'engagement' => "$greetEngage ¡Qué gusto que te interese! Manejamos opciones adaptadas a tus objetivos y necesidades. Puedes consultar todos los detalles en el enlace de nuestra bio o escribirnos por DM. $questionLead",
                'conversion' => "$greetConvert ¡Claro que sí, $firstName! Puedes ver la información completa, planes y disponibilidad directamente en el enlace de nuestra biografía, o si prefieres envíanos un DM y con gusto te orientamos.",
                'support' => "$greetSupport Toda la información de inversión, metodología y opciones disponibles está detallada en el link de nuestro perfil. Si deseas una recomendación personalizada, déjanos un mensaje privado.",
                'engagement_tips' => '🎯 Responder con claridad e invitar a los canales oficiales eleva la conversión sin crear falsas expectativas.'
            ];
        }

        // Case 3: Sales Objections / Guarantees / Doubts
        if ($intent === 'sales_objection') {
            return [
                'source' => 'heuristic_calibrated',
                'engagement' => "$greetEngage Es totalmente comprensible tu consulta, $firstName. Todo nuestro trabajo cuenta con garantía de satisfacción y soporte dedicado para que tengas total tranquilidad. $questionLead",
                'conversion' => "$greetConvert ¡Excelente pregunta! Respaldamos cada programa y servicio con políticas claras de garantía y atención 1 a 1. Además, puedes revisar testimonios verificados en nuestras historias destacadas y en el enlace de la bio. ¿Te gustaría conocer más detalles?",
                'support' => "$greetSupport Tu seguridad y satisfacción son nuestra máxima prioridad. Puedes revisar los términos de satisfacción y respuestas frecuentes en el enlace de nuestro perfil, o escribirnos un DM si deseas resolver dudas específicas.",
                'engagement_tips' => '🛡️ Atender dudas con transparencia y rapidez disipa la fricción de compra y genera confianza inmediata.'
            ];
        }

        // Case 4: Customer Support / Issues
        if ($intent === 'customer_support') {
            return [
                'source' => 'heuristic_calibrated',
                'engagement' => "$greetEngage ¡Hola $firstName! Queremos ayudarte de inmediato. Por favor envíanos un mensaje directo (DM) con los datos de tu cuenta o correo registrado para que nuestro equipo lo revise de forma prioritaria.",
                'conversion' => "$greetConvert Por favor escríbenos por mensaje privado (DM) indicándonos tu correo de registro para que nuestro equipo técnico atienda tu caso de inmediato. ¡Estamos atentos para resolverlo!",
                'support' => "$greetSupport Lamentamos cualquier inconveniente, $firstName. Ya mismo nuestro equipo de asistencia está disponible. Por favor contáctanos por mensaje directo para verificar tu acceso o caso hoy mismo.",
                'engagement_tips' => '🛠️ Una atención al cliente empática y ágil transforma una incidencia en una oportunidad de fidelización.'
            ];
        }

        // Case 5: Praise / Testimonials / Gratitude
        if ($intent === 'gratitude_praise') {
            return [
                'source' => 'heuristic_calibrated',
                'engagement' => "$greetEngage ¡Muchísimas gracias por tus palabras, $firstName! Saber que te ha sido de gran valor es nuestra mayor satisfacción. $questionPraise",
                'conversion' => "$greetConvert ¡Qué gran alegría leer tu comentario! Nos motiva muchísimo a seguir creando lo mejor para ustedes. ¡Un fuerte abrazo!",
                'support' => "$greetSupport ¡Gracias de corazón por tu confianza y por formar parte de esta comunidad! $questionPraise",
                'engagement_tips' => '✨ Responder a los elogios con preguntas abiertas estimula la conversación e incrementa el alcance orgánico.'
            ];
        }

        // Case 6: General Comment
        return [
            'source' => 'heuristic_calibrated',
            'engagement' => "$greetEngage ¡Gracias por compartir tu opinión con nosotros, $firstName! $questionGeneral",
            'conversion' => "$greetConvert ¡Totalmente de acuerdo, $firstName! Si deseas conocer más sobre lo que hacemos, en el enlace de nuestra biografía tienes toda la información.",
            'support' => "$greetSupport ¡Un gran saludo $firstName! Encantados de leerte en nuestra comunidad. 🙌",
            'engagement_tips' => '💬 Las respuestas dinámicas y personalizadas mantienen a tu audiencia activa y comprometida.'
        ];
    }

    /**
     * Resolve active Brand Voice for the current user (Accelerated by In-Memory Cache)
     * Supports resolution by explicit brand_voice_id, account_id, active session brand, or user default.
     */
    public static function resolveActiveBrandVoice(PDO $pdo, array $runtimeOverrides = []): array {
        $userId = (class_exists('Auth') && Auth::check()) ? Auth::id() : 1;
        $activeBrandId = $runtimeOverrides['brand_voice_id'] ?? null;

        // If account_id was provided and no explicit brand_voice_id, deduce from accounts table
        if (empty($activeBrandId) && !empty($runtimeOverrides['account_id'])) {
            try {
                $stmtAcc = $pdo->prepare("SELECT brand_voice_id FROM accounts WHERE id = :acc_id AND user_id = :uid LIMIT 1");
                $stmtAcc->execute([':acc_id' => (int)$runtimeOverrides['account_id'], ':uid' => $userId]);
                $accBv = $stmtAcc->fetchColumn();
                if (!empty($accBv)) {
                    $activeBrandId = (int)$accBv;
                }
            } catch (Throwable) {}
        }

        if (empty($activeBrandId)) {
            $activeBrandId = $_SESSION['active_brand_id'] ?? null;
        }

        return CacheService::getBrandVoice($userId, $activeBrandId ? (int)$activeBrandId : null, $pdo);
    }

    /**
     * OpenRouter API Dynamic Integration (Supports Claude 3.5 Sonnet, DeepSeek V3/R1, GPT-4o, Llama 3.3, etc.)
     */
    private static function callOpenRouterApi(
        string $authorName, string $commentText, string $platform, string $postCaption,
        string $brandName, string $personaName, string $brandIndustry, string $brandTone, string $brandDescription, string $language,
        int $warmthLevel, int $depthLevel, int $energyLevel,
        string $closingQuestionRule, string $emojiStyle, array $keyPhrases, array $forbiddenPhrases, array $fewShotExamples,
        string $apiKey, string $model = 'anthropic/claude-3.5-sonnet'
    ): ?array {
        $prompt = self::buildUniversalPrompt(
            $authorName, $commentText, $platform, $postCaption,
            $brandName, $personaName, $brandIndustry, $brandTone, $brandDescription, $language,
            $warmthLevel, $depthLevel, $energyLevel,
            $closingQuestionRule, $emojiStyle, $keyPhrases, $forbiddenPhrases, $fewShotExamples
        );

        $url = 'https://openrouter.ai/api/v1/chat/completions';
        $selectedModel = !empty($model) ? trim($model) : 'anthropic/claude-3.5-sonnet';

        $payload = [
            'model' => $selectedModel,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "Eres un estratega de respuesta inteligente y asistente de marca para redes sociales. Responde siempre y exclusivamente en formato JSON estructurado válido."
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.7
        ];

        $appUrl = Settings::get('app_url', 'http://localhost/Redes%20sociales');

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
            'HTTP-Referer: ' . $appUrl,
            'X-Title: XINDRO Social AI'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $resData = json_decode($response, true);
            $content = $resData['choices'][0]['message']['content'] ?? '';
            
            // Clean markdown code blocks if model wrapped output in ```json ... ```
            $content = preg_replace('/^```(?:json)?\s*/i', '', trim($content));
            $content = preg_replace('/\s*```$/', '', trim($content));

            $parsed = json_decode($content, true);

            if ($parsed && isset($parsed['engagement'])) {
                return [
                    'source' => 'openrouter_' . str_replace(['/', ':', '.'], '_', $selectedModel),
                    'engagement' => $parsed['engagement'] ?? '',
                    'conversion' => $parsed['conversion'] ?? '',
                    'support' => $parsed['support'] ?? '',
                    'engagement_tips' => $parsed['engagement_tips'] ?? 'Respuesta generada con OpenRouter (' . htmlspecialchars($selectedModel) . ') adaptada a tu voz de marca.'
                ];
            }
        }

        return null;
    }

    /**
     * Build Universal Dynamic Prompt for OpenRouter & Local Engine
     */
    private static function buildUniversalPrompt(
        string $authorName, string $commentText, string $platform, string $postCaption,
        string $brandName, string $personaName, string $brandIndustry, string $brandTone, string $brandDescription, string $language,
        int $warmthLevel, int $depthLevel, int $energyLevel,
        string $closingQuestionRule, string $emojiStyle, array $keyPhrases, array $forbiddenPhrases, array $fewShotExamples
    ): string {
        $firstName = explode(' ', trim($authorName))[0] ?: 'amigo';
        $keyPhrasesText = !empty($keyPhrases) ? implode(', ', $keyPhrases) : 'Atención de calidad, Soluciones personalizadas';
        $forbiddenText = !empty($forbiddenPhrases) ? implode(', ', $forbiddenPhrases) : 'Estimado cliente, Compra ya, Oferta engañosa, Somos un bot';

        $fewShotText = '';
        if (!empty($fewShotExamples)) {
            $fewShotText .= "EJEMPLOS DE ORO DE LA MARCA (Imita este estilo exacto):\n";
            foreach (array_slice($fewShotExamples, 0, 4) as $idx => $ex) {
                $c = $ex['comment'] ?? '';
                $r = $ex['reply'] ?? '';
                $fewShotText .= "Ejemplo #" . ($idx + 1) . ":\n- Comentario de Seguidor: \"$c\"\n- Respuesta Maestra Ideal: \"$r\"\n\n";
            }
        }

        return <<<PROMPT
Eres "$personaName", el estratega oficial de comunicación y gestor de comunidad de la marca "$brandName" en $platform.
Industria / Nicho: $brandIndustry.
Directrices y personalidad de la marca: $brandDescription.
Tono configurado: $brandTone.
Idioma obligatorio de respuesta: $language.

CALIBRACIÓN DE IDENTIDAD:
- Nivel de Cercanía & Calidez: $warmthLevel% (Trata a la persona con amabilidad y calidez genuina).
- Nivel de Profundidad / Expertise: $depthLevel% (Aporta respuestas útiles, fundamentadas y de valor).
- Nivel de Firmeza & Enfoque a la Acción: $energyLevel% (Impulsa a la acción con energía y claridad).
- Regla de Pregunta de Cierre: $closingQuestionRule (Si es 'always', remata con una pregunta relevante para fomentar la conversación o cerrar ventas).
- Estilo de Emojis: $emojiStyle.

CONCEPTOS CLAVE A DESTACAR: $keyPhrasesText.
FRASES TOTALMENTE PROHIBIDAS (NUNCA LAS USES): $forbiddenText.

REGLAS ESTRICTAS DE VERACIDAD Y ANTI-ALUCINACIÓN (OBLIGATORIAS):
1. CERO FALSA ESCASEZ Y CERO INVENCIÓN: NUNCA inventes ofertas inexistentes, porcentajes de descuento no indicados ni cupos limitados ficticios (ej. "quedan 10 cupos").
2. CERO ACCIONES NO REALIZADAS: NUNCA afirmes haber enviado un mensaje directo (DM), correo o realizado acciones externas ("te acabo de enviar un DM", "ya te escribí"). Si corresponde, invita cortésmente al seguidor a escribir por DM o a consultar el enlace en la bio.
3. MANEJO DE DATOS FALTANTES: Si el seguidor pregunta por especificaciones internas, precios o accesos no descritos en el contexto, responde honestamente con los datos generales conocidos y oriéntalo amablemente al enlace de la bio o a enviar un DM para recibir asesoría personalizada.
4. PREGUNTAS CONCEPTUALES Y FILOSÓFICAS: Si el seguidor consulta sobre un concepto, metodología, filosofía estoica (ej. Dicotomía del control) o pide un consejo, responde con fundamento, claridad y valor práctico. NUNCA desvíes preguntas conceptuales a soporte técnico de pedidos o reclamos.

$fewShotText

CONTEXTO ACTUAL:
- Publicación del feed: "$postCaption".
- Comentario del seguidor ($firstName): "$commentText".

Genera 3 opciones de respuesta saludando a $firstName sin sonar robótico ni usar frases prohibidas:
1. "engagement": [🤝 Conexión & Empatía]: Cálida, humana, conversacional y cercana.
2. "conversion": [🎯 Conversión & Venta / CTA]: Proactiva, enfocada en valor y orientando a la acción (DM, link, compra).
3. "support": [💡 Autoridad & Solución]: Informativa, clara y profesional, resolviendo dudas.

Responde únicamente en formato JSON:
{
  "engagement": "texto de respuesta 1",
  "conversion": "texto de respuesta 2",
  "support": "texto de respuesta 3",
  "engagement_tips": "breve tip estratégico de por qué esta respuesta conecta con la audiencia"
}
PROMPT;
    }

    /**
     * Find best matching few-shot master example
     */
    private static function findMatchingFewShotExample(string $commentText, array $examples): ?array {
        $textLower = mb_strtolower($commentText, 'UTF-8');
        foreach ($examples as $ex) {
            $exComment = mb_strtolower($ex['comment'] ?? '', 'UTF-8');
            if (!empty($exComment)) {
                $words = explode(' ', $exComment);
                $matchCount = 0;
                foreach ($words as $w) {
                    if (mb_strlen($w) > 3 && str_contains($textLower, $w)) {
                        $matchCount++;
                    }
                }
                if ($matchCount >= 2) {
                    return $ex;
                }
            }
        }
        return null;
    }

    private static function adaptFewShotReply(string $replyTemplate, string $firstName): string {
        return str_replace(['{nombre}', '{name}'], $firstName, $replyTemplate);
    }

    /**
     * Guarantee no forbidden phrases appear in generated outputs
     */
    private static function sanitizeRepliesWithForbidden(array $res, array $forbiddenPhrases): array {
        foreach (['engagement', 'conversion', 'support'] as $key) {
            if (isset($res[$key]) && is_string($res[$key])) {
                foreach ($forbiddenPhrases as $badPhrase) {
                    if (!empty(trim($badPhrase))) {
                        $res[$key] = str_ireplace(trim($badPhrase), '', $res[$key]);
                    }
                }
                $res[$key] = preg_replace('/\s+/', ' ', trim($res[$key]));
            }
        }
        return $res;
    }

    private static function parseJsonSetting($val, array $default = []): array {
        if (is_array($val)) return $val;
        if (empty($val) || !is_string($val)) return $default;
        $decoded = json_decode($val, true);
        return is_array($decoded) ? $decoded : $default;
    }

    public static function getDefaultFewShotExamples(): array {
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
}
