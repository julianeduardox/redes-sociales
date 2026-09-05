<?php
/**
 * AiAgentService - Agnostic Multi-Niche & Multi-Brand AI Engine for Creators & Agencies
 * Features:
 * - Dynamic System Prompt builder based on active Brand Voice / Client configuration
 * - Universal Commercial Intent Classifier (Leads, Pricing, Objections, Support, Testimonials)
 * - Zero-Token Local Heuristic Engine calibrated with brand guidelines & persona
 * - Golden Few-Shot Master Examples Learning
 * - Negative Constraints (Forbidden Words / Blacklist) & Key Brand Concepts
 * - Gemini & OpenAI integrations with dynamic system prompt calibration
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
                'is_highlighted' => 0,
                'highlight_reason' => $suitability['reason'],
                'detected_keywords' => []
            ];
        }

        $textLower = mb_strtolower($commentText, 'UTF-8');
        
        $score = 50;
        $sentiment = 'neutral';
        $intent = 'general';
        $highlightReason = 'Comentario de la comunidad';
        $keywords = [];

        // 1. Commercial Leads / Pricing / Info / Buying Intent
        $leadPatterns = [
            'precio', 'precios', 'costo', 'costos', 'cuanto vale', 'cuánto vale', 'cuanto cuesta', 'cuánto cuesta',
            'como comprar', 'cómo comprar', 'donde comprar', 'dónde comprar', 'donde estan', 'dónde están',
            'envio', 'envíos', 'envío', 'informacion', 'información', 'catalogo', 'catálogo', 'info',
            'link', 'enlace', 'dm', 'inbox', 'disponible', 'disponibles', 'stock', 'promocion', 'promoción',
            'descuento', 'descuentos', 'cotizacion', 'cotización', 'agendar', 'agenda', 'asesoria', 'asesoría',
            'me interesa', 'quiero uno', 'quiero mas info', 'quiero más info', 'como contrato', 'cómo contrato'
        ];

        // 2. Sales Objections / Guarantees / Trust / Shipping Time
        $objectionPatterns = [
            'garantia', 'garantía', 'seguro', 'es seguro', 'devolucion', 'devolución', 'tarda mucho',
            'cuanto tarda', 'cuánto tarda', 'confiable', 'es confiable', 'estafa', 'funciona', 'realmente funciona',
            'duda', 'desconfianza', 'calidad', 'testimonios', 'certificacion', 'certificación'
        ];

        // 3. Customer Support / Post-Sale Issues / Help
        $supportPatterns = [
            'ayuda', 'soporte', 'problema', 'no me llego', 'no me llegó', 'no llego', 'error', 'falla',
            'mi pedido', 'estado de mi orden', 'reclamo', 'queja', 'factura', 'no puedo ingresar',
            'no funciona', 'necesito ayuda', 'asesor'
        ];

        // 4. Testimonials / High Gratitude / Praise
        $praisePatterns = [
            'excelente', 'increible', 'increíble', 'me encanto', 'me encantó', 'buenisimo', 'buenísimo',
            'genial', 'recomiendo', 'recomendado', 'lo mejor', 'felicitaciones', 'gran trabajo', 'super',
            'súper', 'top', 'felicidades', 'gracias infinitas', 'cambio mi', 'cambió mi', 'los mejores'
        ];

        // Detect Leads & Buying Intent (Top priority for conversion)
        $foundLeads = [];
        foreach ($leadPatterns as $p) {
            if (str_contains($textLower, $p)) {
                $foundLeads[] = $p;
            }
        }
        if (!empty($foundLeads)) {
            $sentiment = 'question';
            $intent = 'lead_info';
            $score = 96;
            $highlightReason = '🎯 Oportunidad Comercial / Lead Calificado: Pregunta de precio, catálogo o compra lista para cerrar';
            $keywords = $foundLeads;
        }

        // Detect Sales Objections
        $foundObjections = [];
        foreach ($objectionPatterns as $p) {
            if (str_contains($textLower, $p)) {
                $foundObjections[] = $p;
            }
        }
        if (empty($foundLeads) && !empty($foundObjections)) {
            $sentiment = 'question';
            $intent = 'sales_objection';
            $score = 92;
            $highlightReason = '🛡️ Objeción de Venta / Garantía: Resuelve la duda con autoridad para concretar la conversión';
            $keywords = $foundObjections;
        }

        // Detect Customer Support
        $foundSupport = [];
        foreach ($supportPatterns as $p) {
            if (str_contains($textLower, $p)) {
                $foundSupport[] = $p;
            }
        }
        if (empty($foundLeads) && empty($foundObjections) && !empty($foundSupport)) {
            $sentiment = 'urgent';
            $intent = 'customer_support';
            $score = 95;
            $highlightReason = '🛠️ Soporte / Asistencia al Cliente: Requiere atención rápida y resolutiva';
            $keywords = $foundSupport;
        }

        // Detect Praise & Testimonials
        $foundPraise = [];
        foreach ($praisePatterns as $p) {
            if (str_contains($textLower, $p)) {
                $foundPraise[] = $p;
            }
        }
        if (empty($foundLeads) && empty($foundObjections) && empty($foundSupport) && !empty($foundPraise)) {
            $sentiment = 'positive';
            $intent = 'gratitude_praise';
            $score = 88;
            $highlightReason = '✨ Testimonio Positivo & Fidelización: Conecta y agradece para impulsar la prueba social';
            $keywords = $foundPraise;
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
            'is_highlighted' => $isHighlighted,
            'highlight_reason' => $highlightReason,
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

        $aiProvider = $runtimeOverrides['ai_provider'] ?? Settings::get('ai_provider', 'gemini');
        $geminiKey = Settings::get('gemini_api_key', '');
        $openaiKey = Settings::get('openai_api_key', '');

        // Try Gemini API first if configured
        if ($aiProvider === 'gemini' && !empty($geminiKey)) {
            $geminiResult = self::callGeminiApi(
                $authorName, $commentText, $platform, $postCaption, 
                $brandName, $personaName, $brandIndustry, $brandTone, $brandDescription, $language,
                $warmthLevel, $depthLevel, $energyLevel,
                $closingQuestionRule, $emojiStyle, $keyPhrases, $forbiddenPhrases, $fewShotExamples, $geminiKey
            );
            if ($geminiResult !== null && !empty($geminiResult['engagement'])) {
                return self::sanitizeRepliesWithForbidden($geminiResult, $forbiddenPhrases);
            }
        }

        // Try OpenAI API if configured
        if ($aiProvider === 'openai' && !empty($openaiKey)) {
            $openaiResult = self::callOpenAiApi(
                $authorName, $commentText, $platform, $postCaption, 
                $brandName, $personaName, $brandIndustry, $brandTone, $brandDescription, $language,
                $warmthLevel, $depthLevel, $energyLevel,
                $closingQuestionRule, $emojiStyle, $keyPhrases, $forbiddenPhrases, $fewShotExamples, $openaiKey
            );
            if ($openaiResult !== null && !empty($openaiResult['engagement'])) {
                return self::sanitizeRepliesWithForbidden($openaiResult, $forbiddenPhrases);
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

        // Check if there is a matching master few-shot example registered by the user
        $matchedExample = self::findMatchingFewShotExample($commentText, $fewShotExamples);

        // Emoji styling helper
        $eHeart = ($emojiStyle === 'minimal') ? '🤝' : (($emojiStyle === 'expressive') ? '🤝 ✨' : '🤝');
        $eRocket = ($emojiStyle === 'minimal') ? '🚀' : (($emojiStyle === 'expressive') ? '🚀 🎯' : '🚀');
        $eLight = ($emojiStyle === 'minimal') ? '💡' : (($emojiStyle === 'expressive') ? '💡 🌟' : '💡');

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
        $questionLead = ($closingQuestionRule !== 'never') ? "¿Te gustaría que te comparta los detalles directos a tu DM? 📩" : "Estamos a tu total disposición.";
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

        // Case 1: Commercial Lead / Pricing / Info
        if ($intent === 'lead_info') {
            return [
                'source' => 'heuristic_calibrated',
                'engagement' => "$greetEngage ¡Qué gusto que te interese! Manejamos opciones personalizadas adaptadas a lo que necesitas. Te envío un mensaje privado con todos los detalles. $questionLead",
                'conversion' => "$greetConvert ¡Claro que sí, $firstName! Puedes ver la información completa y catálogo directamente en el enlace de nuestra biografía o si gustas te lo enviamos por DM ya mismo. ¡Será un gusto atenderte!",
                'support' => "$greetSupport Para brindarte el presupuesto exacto y la disponibilidad en tu zona, ya mismo te enviamos la lista por privado. ¡Revisa tu bandeja de mensajes!",
                'engagement_tips' => '🎯 Responder en menos de 15 minutos e invitar al DM eleva la tasa de conversión en más de un 60%.'
            ];
        }

        // Case 2: Sales Objections / Guarantees / Doubts
        if ($intent === 'sales_objection') {
            return [
                'source' => 'heuristic_calibrated',
                'engagement' => "$greetEngage Es totalmente comprensible tu consulta, $firstName. Todo nuestro trabajo cuenta con garantía de satisfacción y soporte dedicado para que tengas total tranquilidad. $questionLead",
                'conversion' => "$greetConvert ¡Excelente pregunta! Respaldamos cada producto y servicio con políticas claras de garantía y atención 1 a 1. Además, puedes revisar testimonios de clientes en nuestras historias destacadas. ¿Te gustaría agendar una llamada rápida?",
                'support' => "$greetSupport Tu seguridad y satisfacción son nuestra prioridad número uno. Te acabo de enviar un DM con los términos de garantía y respuestas a preguntas frecuentes. ¡Estamos aquí para ti!",
                'engagement_tips' => '🛡️ Atender dudas con transparencia y rapidez disipa la fricción de compra y genera confianza inmediata.'
            ];
        }

        // Case 3: Customer Support / Issues
        if ($intent === 'customer_support') {
            return [
                'source' => 'heuristic_calibrated',
                'engagement' => "$greetEngage ¡Hola $firstName! Queremos ayudarte de inmediato. Ya mismo nuestro equipo te escribe por DM para revisar tu caso y darte solución rápida.",
                'conversion' => "$greetConvert Por favor cuéntanos por mensaje privado tu número de orden o correo para gestionarlo de forma prioritaria. ¡Estamos atentos para solucionarlo!",
                'support' => "$greetSupport Lamentamos cualquier inconveniente. Ya estamos revisando tu reporte para que quede resuelto hoy mismo. ¡Revisa tus mensajes privados por favor!",
                'engagement_tips' => '🛠️ Una atención al cliente empática y ágil transforma un reclamo en una oportunidad de fidelización.'
            ];
        }

        // Case 4: Praise / Testimonials / Gratitude
        if ($intent === 'gratitude_praise') {
            return [
                'source' => 'heuristic_calibrated',
                'engagement' => "$greetEngage ¡Muchísimas gracias por tus palabras, $firstName! Saber que te ha sido de gran valor es nuestra mayor satisfacción. $questionPraise",
                'conversion' => "$greetConvert ¡Qué gran alegría leer tu comentario! Nos motiva muchísimo a seguir creando lo mejor para ustedes. ¡Un fuerte abrazo!",
                'support' => "$greetSupport ¡Gracias de corazón por tu confianza y por formar parte de esta comunidad! $questionPraise",
                'engagement_tips' => '✨ Responder a los elogios con preguntas abiertas estimula la conversación e incrementa el alcance orgánico.'
            ];
        }

        // Case 5: General Comment
        return [
            'source' => 'heuristic_calibrated',
            'engagement' => "$greetEngage ¡Gracias por compartir tu opinión con nosotros, $firstName! $questionGeneral",
            'conversion' => "$greetConvert ¡Totalmente de acuerdo, $firstName! Si necesitas cualquier información adicional sobre lo que hacemos, el link en bio tiene todo listo para ti.",
            'support' => "$greetSupport ¡Un gran saludo $firstName! Encantados de leerte en nuestra comunidad. 🙌",
            'engagement_tips' => '💬 Las respuestas dinámicas y personalizadas mantienen a tu audiencia activa y comprometida.'
        ];
    }

    /**
     * Resolve active Brand Voice for the current user (Accelerated by In-Memory Cache)
     */
    public static function resolveActiveBrandVoice(PDO $pdo, array $runtimeOverrides = []): array {
        $userId = (class_exists('Auth') && Auth::check()) ? Auth::id() : 1;
        $activeBrandId = $runtimeOverrides['brand_voice_id'] ?? ($_SESSION['active_brand_id'] ?? null);
        return CacheService::getBrandVoice($userId, $activeBrandId ? (int)$activeBrandId : null, $pdo);
    }

    /**
     * Gemini API Dynamic Integration
     */
    private static function callGeminiApi(
        string $authorName, string $commentText, string $platform, string $postCaption,
        string $brandName, string $personaName, string $brandIndustry, string $brandTone, string $brandDescription, string $language,
        int $warmthLevel, int $depthLevel, int $energyLevel,
        string $closingQuestionRule, string $emojiStyle, array $keyPhrases, array $forbiddenPhrases, array $fewShotExamples,
        string $apiKey
    ): ?array {
        $prompt = self::buildUniversalPrompt(
            $authorName, $commentText, $platform, $postCaption,
            $brandName, $personaName, $brandIndustry, $brandTone, $brandDescription, $language,
            $warmthLevel, $depthLevel, $energyLevel,
            $closingQuestionRule, $emojiStyle, $keyPhrases, $forbiddenPhrases, $fewShotExamples
        );

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . urlencode($apiKey);

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'responseMimeType' => 'application/json'
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $resData = json_decode($response, true);
            $candidateText = $resData['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $parsed = json_decode($candidateText, true);

            if ($parsed && isset($parsed['engagement'])) {
                return [
                    'source' => 'gemini_flash',
                    'engagement' => $parsed['engagement'] ?? '',
                    'conversion' => $parsed['conversion'] ?? '',
                    'support' => $parsed['support'] ?? '',
                    'engagement_tips' => $parsed['engagement_tips'] ?? 'Respuesta generada con inteligencia artificial adaptada a tu voz de marca.'
                ];
            }
        }

        return null;
    }

    /**
     * OpenAI API Dynamic Integration
     */
    private static function callOpenAiApi(
        string $authorName, string $commentText, string $platform, string $postCaption,
        string $brandName, string $personaName, string $brandIndustry, string $brandTone, string $brandDescription, string $language,
        int $warmthLevel, int $depthLevel, int $energyLevel,
        string $closingQuestionRule, string $emojiStyle, array $keyPhrases, array $forbiddenPhrases, array $fewShotExamples,
        string $apiKey
    ): ?array {
        $prompt = self::buildUniversalPrompt(
            $authorName, $commentText, $platform, $postCaption,
            $brandName, $personaName, $brandIndustry, $brandTone, $brandDescription, $language,
            $warmthLevel, $depthLevel, $energyLevel,
            $closingQuestionRule, $emojiStyle, $keyPhrases, $forbiddenPhrases, $fewShotExamples
        );

        $url = 'https://api.openai.com/v1/chat/completions';

        $payload = [
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "Eres un estratega de respuesta inteligente y asistente de marca para redes sociales. Responde siempre en JSON válido."
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.7
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $resData = json_decode($response, true);
            $content = $resData['choices'][0]['message']['content'] ?? '';
            $parsed = json_decode($content, true);

            if ($parsed && isset($parsed['engagement'])) {
                return [
                    'source' => 'openai_gpt4o',
                    'engagement' => $parsed['engagement'] ?? '',
                    'conversion' => $parsed['conversion'] ?? '',
                    'support' => $parsed['support'] ?? '',
                    'engagement_tips' => $parsed['engagement_tips'] ?? 'Respuesta generada con OpenAI adaptada a tu voz de marca.'
                ];
            }
        }

        return null;
    }

    /**
     * Build Universal Dynamic Prompt for Gemini & OpenAI
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
                'comment' => '¿Cuál es el precio del servicio y qué incluye?',
                'reply' => '¡Hola {nombre}! Con gusto te comparto todos los detalles. Manejamos paquetes adaptados al tamaño de tu negocio. Te acabo de enviar un mensaje directo (DM) con la propuesta y el catálogo completo. ¿Qué objetivo principal te gustaría alcanzar este mes?'
            ],
            [
                'tag' => 'objecion_garantia',
                'comment' => '¿Qué garantía tienen y cómo sé si funcionará para mi empresa?',
                'reply' => 'Excelente pregunta, {nombre}. Respaldamos todo nuestro trabajo con garantía de satisfacción y soporte prioritario 1 a 1. Además, puedes revisar nuestros casos de éxito verificados en el enlace de la bio. ¿Te gustaría agendar una llamada rápida de 10 min para evaluar tu caso?'
            ],
            [
                'tag' => 'soporte_ayuda',
                'comment' => 'Tengo una duda con mi cuenta y necesito soporte urgente por favor.',
                'reply' => '¡Hola {nombre}! Por supuesto, nuestro equipo de soporte está listo para asistirte. Ya mismo te escribimos por DM para solucionar tu duda de inmediato. ¡Cuenta con nosotros!'
            ],
            [
                'tag' => 'felicitacion_agradecimiento',
                'comment' => '¡Excelente contenido y qué gran servicio! Me ayudó muchísimo su recomendación.',
                'reply' => '¡Muchísimas gracias por tus palabras, {nombre}! Nos alegra enorme saber que te ha sido de gran valor. ¿De qué tema te gustaría que profundicemos en la siguiente publicación?'
            ]
        ];
    }
}
