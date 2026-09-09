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
            // Check if it contains actual emojis to reply with emojis
            $hasEmoji = preg_match('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F700}-\x{1F77F}\x{1F780}-\x{1F7FF}\x{1F800}-\x{1F8FF}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{1FA70}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{2300}-\x{23FF}\x{2B50}]/u', $text);
            if ($hasEmoji) {
                return [
                    'status' => 'valid',
                    'should_reply' => true,
                    'reason' => '🎨 Reacción con emojis de la comunidad (apto para responder con emojis)',
                    'category' => 'emoji_reaction'
                ];
            }

            return [
                'status' => 'ignored',
                'should_reply' => false,
                'reason' => '🎨 Comentario vacío o símbolo suelto sin texto para responder',
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
     * Detect if an author name is an anonymous placeholder or machine handle
     */
    public static function isGenericAuthorName(?string $name): bool {
        if ($name === null) return true;
        $clean = mb_strtolower(trim($name), 'UTF-8');
        if (empty($clean)) return true;
        if (str_starts_with($clean, 'usuario') || str_starts_with($clean, 'user') || str_starts_with($clean, 'fb_') || str_starts_with($clean, 'ig_')) return true;
        if (str_contains($clean, 'facebook') || str_contains($clean, 'instagram') || str_contains($clean, 'comunidad') || str_contains($clean, 'lector')) return true;
        if (in_array($clean, ['amigo', 'seguidor', 'cliente', 'anonimo', 'anónimo', 'fan', 'guest', 'member'])) return true;
        if (preg_match('/^[0-9_\.\-]+$/', $clean)) return true;
        return false;
    }

    /**
     * Extract a clean personal first name or empty string if anonymous/generic
     */
    public static function extractCleanFirstName(?string $authorName): string {
        if (empty($authorName) || self::isGenericAuthorName($authorName)) {
            return '';
        }
        $raw = ltrim(trim($authorName), '@');
        $parts = preg_split('/[\s_\.\-]+/u', $raw);
        $first = $parts[0] ?? '';
        if (self::isGenericAuthorName($first) || mb_strlen($first, 'UTF-8') < 2) {
            return '';
        }
        return mb_convert_case($first, MB_CASE_TITLE, 'UTF-8');
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

        // Special handling for emoji reactions (e.g. 👏👏, 🔥, ❤️, 💪, 🙌)
        if ($suitability['category'] === 'emoji_reaction') {
            return [
                'sentiment' => 'positive',
                'intent' => 'emoji_reaction',
                'highlight_score' => 75,
                'commercial_priority' => 70,
                'is_highlighted' => 0,
                'highlight_reason' => 'Reacción de apoyo y entusiasmo con emojis',
                'autopilot_ready' => true,
                'autopilot_status' => 'ready',
                'autopilot_reason' => 'Reacción positiva con emojis lista para auto-responder',
                'detected_keywords' => ['emoji_reaction']
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

        // 0. Stoic Awakening & High Impact Reality Checks (Bofetada de realidad, sacudida, reflexión profunda)
        $awakeningPatterns = [
            'bofetada', 'guante blanco', 'mensaje brutal', 'brutal', 'me hizo reflexionar',
            'me abrio los ojos', 'me abrió los ojos', 'me llego al alma', 'me llegó al alma',
            'dolió pero', 'dolio pero', 'cachetada', 'sacudida', 'despertar', 'fuerte pero real',
            'cruda verdad', 'justo lo que necesitaba', 'dio justo', 'impactante', 'me marco',
            'me marcó', 'lección dura', 'leccion dura', 'golpe de realidad', 'bofetada de realidad'
        ];

        // 0.1 Short Affirmations of Truth / Resonance (Verdad, literal, total, 100%, así es)
        $shortAffirmationPatterns = [
            'verdad', 'gran verdad', 'que gran verdad', 'qué gran verdad', 'totalmente',
            'literal', 'muy cierto', 'cierto', 'tal cual', 'exacto', 'así es', 'asi es',
            'de acuerdo', '100%', 'amén', 'amen', 'muy real', 'sin duda', 'así mismo',
            'asi mismo', 'correcto', 'total', 'es verdad', 'pura verdad', 'clarisimo', 'clarísimo'
        ];

        // 1. Philosophical, Stoic, Conceptual & Mentorship QA (Dicotomía del control, mentalidad, disciplina, conceptos, virtud)
        $conceptPatterns = [
            'dicotomia', 'dicotomía', 'dicotomia del control', 'dicotomía del control', 'estoicismo', 'estoico', 'estoica',
            'marco aurelio', 'seneca', 'séneca', 'epicteto', 'epícteto', 'amor fati', 'memento mori', 'autodominio',
            'fortaleza mental', 'disciplina diaria', 'forjar disciplina', 'como aplicar', 'cómo aplicar', 'que significa',
            'qué significa', 'miedo al fracaso', 'procrastino', 'procrastinar', 'procrastinacion', 'procrastinación',
            'sin motivacion', 'sin motivación', 'falta de motivacion', 'falta de motivación', 'consejo', 'reflexion',
            'reflexión', 'filosofia', 'filosofía', 'sabiduria', 'sabiduría', 'crecimiento personal', 'mentalidad',
            'obstaculo es el camino', 'obstáculo es el camino', 'el obstaculo', 'el obstáculo', 'habito', 'hábito',
            'virtud', 'virtudes', 'momento', 'momentos', 'decidimos', 'decidir', 'decisión', 'decisiones', 'perfecto',
            'perfecta', 'perfección', 'perfeccion', 'tiempo', 'presente', 'propósito', 'proposito', 'carácter', 'caracter',
            'alma', 'mente', 'serenidad', 'voluntad', 'constancia', 'destino'
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

        // Detect Stoic Awakening / Reality Check
        $foundAwakening = [];
        foreach ($awakeningPatterns as $p) {
            if (str_contains($textLower, $p)) {
                $foundAwakening[] = $p;
            }
        }

        // Detect Short Stoic Affirmation (Short comments of agreement/truth <= 45 chars)
        $cleanLen = mb_strlen(trim(preg_replace('/[^\p{L}\p{N}\s]/u', '', $commentText)), 'UTF-8');
        $foundShortAffirmation = [];
        if ($cleanLen <= 45) {
            foreach ($shortAffirmationPatterns as $p) {
                if (str_contains($textLower, $p)) {
                    $foundShortAffirmation[] = $p;
                }
            }
        }

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
        if (!empty($foundAwakening)) {
            $sentiment = 'positive';
            $intent = 'stoic_awakening_impact';
            $score = 98;
            $highlightReason = '🏛️ Despertar de Consciencia & Choque de Realidad: Reflexión profunda sobre el impacto del mensaje estoico';
            $keywords = $foundAwakening;
            $autopilotReady = true;
            $autopilotStatus = 'ready';
            $autopilotReason = '✔ Apto para Autopilot (Respuesta estoica de empatía y carácter)';
        } elseif (!empty($foundShortAffirmation)) {
            $sentiment = 'positive';
            $intent = 'stoic_affirmation_short';
            $score = 95;
            $highlightReason = '🏛️ Afirmación y Validación de Verdad: Resonancia directa con el principio estoico';
            $keywords = $foundShortAffirmation;
            $autopilotReady = true;
            $autopilotStatus = 'ready';
            $autopilotReason = '✔ Apto para Autopilot (Afirmación contundente y concisa de verdad)';
        } elseif (!empty($foundConcepts)) {
            $isQuestion = str_contains($commentText, '?') || str_contains($textLower, 'cómo') || str_contains($textLower, 'como') || str_contains($textLower, 'qué') || str_contains($textLower, 'que') || str_contains($textLower, 'cuál') || str_contains($textLower, 'cual');
            $sentiment = $isQuestion ? 'question' : 'positive';
            $intent = 'knowledge_concept';
            $score = 95;
            $highlightReason = $isQuestion 
                ? '🧠 Consulta Conceptual & Mentoría: Pregunta sobre principios, disciplina y aplicación práctica'
                : '🧠 Reflexión Filosófica de la Comunidad: Aporte de alto valor sobre virtud, presencia y mentalidad';
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

        $targetUserId = (int)($runtimeOverrides['user_id'] ?? (class_exists('Auth') && Auth::check() ? Auth::id() : ($brandVoice['user_id'] ?? 1)));
        $userAiConfig = null;
        if ($targetUserId > 0) {
            try {
                $uStmt = $pdo->prepare("SELECT id, role, email, ai_model, max_tokens, used_tokens FROM users WHERE id = :id LIMIT 1");
                $uStmt->execute([':id' => $targetUserId]);
                $userAiConfig = $uStmt->fetch(PDO::FETCH_ASSOC);
            } catch (Throwable $e) {}
        }

        $aiProvider = $runtimeOverrides['ai_provider'] ?? Settings::get('ai_provider', 'openrouter');
        $openrouterKey = Settings::get('openrouter_api_key', '');
        
        // Priority: runtime override > user's assigned model from admin > system setting
        $userAssignedModel = !empty($userAiConfig['ai_model']) ? trim($userAiConfig['ai_model']) : '';
        if (!empty($userAssignedModel)) {
            if ($userAssignedModel === 'heuristic') {
                $aiProvider = 'heuristic';
            } else {
                $openrouterModel = $runtimeOverrides['openrouter_model'] ?? $userAssignedModel;
            }
        } else {
            $openrouterModel = $runtimeOverrides['openrouter_model'] ?? Settings::get('openrouter_model', 'anthropic/claude-3.5-sonnet');
        }

        // Check user token quota
        $maxTokens = (int)($userAiConfig['max_tokens'] ?? 50000);
        $usedTokens = (int)($userAiConfig['used_tokens'] ?? 0);
        $isTokensExhausted = ($maxTokens > 0 && $usedTokens >= $maxTokens);

        // Try OpenRouter API first if configured and user has remaining quota
        if ($aiProvider === 'openrouter' && !empty($openrouterKey) && !$isTokensExhausted) {
            $openrouterResult = self::callOpenRouterApi(
                $authorName, $commentText, $platform, $postCaption, 
                $brandName, $personaName, $brandIndustry, $brandTone, $brandDescription, $language,
                $warmthLevel, $depthLevel, $energyLevel,
                $closingQuestionRule, $emojiStyle, $keyPhrases, $forbiddenPhrases, $fewShotExamples,
                $openrouterKey, $openrouterModel,
                $targetUserId, $pdo
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
        // Clean author name: verify if anonymous/generic or genuine personal name
        $isGeneric = self::isGenericAuthorName($authorName);
        $displayName = $isGeneric ? '' : self::extractCleanFirstName($authorName);
        $nameVocative = !empty($displayName) ? ", $displayName" : '';

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

        // Warmth greetings (Name is included here ONLY if not generic, NEVER output "¡Hola Usuario!")
        if (!empty($displayName)) {
            if ($warmthLevel >= 80) {
                $greetEngage = "¡Hola $displayName! $eHeart";
                $greetConvert = "¡Qué tal $displayName! $eRocket";
                $greetSupport = "¡Hola $displayName! Con gusto te apoyo. $eLight";
            } elseif ($warmthLevel >= 50) {
                $greetEngage = "Hola $displayName $eHeart";
                $greetConvert = "Hola $displayName $eRocket";
                $greetSupport = "Hola $displayName $eLight";
            } else {
                $greetEngage = "$eHeart";
                $greetConvert = "$eRocket";
                $greetSupport = "$eLight";
            }
        } else {
            // Natural human greetings when no real name is available (NEVER say "¡Hola Usuario!")
            $greetEngage = "¡Totalmente! $eHeart";
            $greetConvert = "¡Qué gran perspectiva! $eRocket";
            $greetSupport = "Con gusto te apoyo. $eLight";
        }

        // Closing Questions based on rule
        $questionLead = ($closingQuestionRule !== 'never') ? "¿Te gustaría conocer más detalles sobre el contenido o temario? 👇" : "Estamos a tu total disposición.";
        $questionGeneral = ($closingQuestionRule !== 'never') ? "¿En qué proyecto o hábito estás trabajando hoy? 👇" : "¡Un saludo y seguimos en contacto!";
        $questionPraise = ($closingQuestionRule !== 'never') ? "¿De qué tema te gustaría que hablemos en el próximo post? 💬" : "¡Gracias por formar parte de la comunidad!";

        // If a master few-shot example matches closely, adapt it!
        if ($matchedExample) {
            $adapted = self::adaptFewShotReply($matchedExample['reply'], $displayName);
            return [
                'source' => 'heuristic_few_shot_trained',
                'engagement' => (!empty($displayName) ? "$greetEngage " : '') . $adapted . ($closingQuestionRule === 'always' ? " " . $questionGeneral : ''),
                'conversion' => (!empty($displayName) ? "$greetConvert " : '') . $adapted,
                'support' => (!empty($displayName) ? "$greetSupport " : '') . $adapted,
                'engagement_tips' => '🧠 Respuesta enriquecida por el Ejemplo Maestro entrenado para este patrón.'
            ];
        }

        // Case 0: Pure Emoji / Emoji Reaction Comments (e.g. 👏👏, 🔥, ❤️, 💪, 🙌)
        if ($intent === 'emoji_reaction' || mb_strlen(preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F700}-\x{1F77F}\x{1F780}-\x{1F7FF}\x{1F800}-\x{1F8FF}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{1FA70}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{2300}-\x{23FF}\x{2B50}\x{200D}\x{FE0F}\s\p{P}]/u', '', $commentText), 'UTF-8') < 2) {
            $isApplause = str_contains($commentText, '👏') || str_contains($commentText, '🙌');
            $isFire = str_contains($commentText, '🔥') || str_contains($commentText, '⚡') || str_contains($commentText, '🚀');
            $isLove = str_contains($commentText, '❤️') || str_contains($commentText, '😍') || str_contains($commentText, '🥰') || str_contains($commentText, '💖');
            $isStrength = str_contains($commentText, '💪') || str_contains($commentText, '🎯') || str_contains($commentText, '🏆');

            if ($isApplause) {
                return [
                    'source' => 'heuristic_calibrated',
                    'engagement' => "¡Muchas gracias por los aplausos y el apoyo{$nameVocative}! 👏🔥 ¡Seguimos con todo!",
                    'conversion' => "¡Gracias por estar presente{$nameVocative}! 👏🚀 Si tienes cualquier duda o quieres conocer más, déjanos un DM.",
                    'support' => "¡Un honor contar con tu presencia en la comunidad{$nameVocative}! 🏛️✨ ¡Un fuerte abrazo!",
                    'engagement_tips' => '👏 Responder con rapidez a comentarios de aplausos y emojis eleva la visibilidad en el algoritmo de Meta.'
                ];
            }

            if ($isFire) {
                return [
                    'source' => 'heuristic_calibrated',
                    'engagement' => "¡A tope con esa energía y determinación{$nameVocative}! 🔥⚡ ¡Vamos con todo!",
                    'conversion' => "¡Esa es la actitud imparable{$nameVocative}! 🔥🚀 En el enlace del perfil encuentras recursos para potenciar tu enfoque.",
                    'support' => "¡Fuerza e impulso para tus metas{$nameVocative}! 🔥💪 ¡Seguimos firmes!",
                    'engagement_tips' => '🔥 La reciprocidad en comentarios de alta energía impulsa la viralidad y el alcance de la publicación.'
                ];
            }

            if ($isLove) {
                return [
                    'source' => 'heuristic_calibrated',
                    'engagement' => "¡Mucho aprecio para ti{$nameVocative}! ❤️✨ ¡Gracias de corazón por formar parte de esta comunidad!",
                    'conversion' => "¡Gracias por tanto cariño{$nameVocative}! ❤️🚀 Estamos a tu entera disposición por DM para lo que necesites.",
                    'support' => "¡Un saludo muy especial{$nameVocative}! ❤️🤝 ¡Seguimos sumando valor juntos!",
                    'engagement_tips' => '❤️ Conectar emocionalmente con las muestras de aprecio de seguidores afianza la lealtad hacia la marca.'
                ];
            }

            if ($isStrength) {
                return [
                    'source' => 'heuristic_calibrated',
                    'engagement' => "¡Disciplina, constancia y fuerza imparable{$nameVocative}! 💪⚡ ¡Vamos por más!",
                    'conversion' => "¡Con toda la determinación{$nameVocative}! 💪🚀 Tienes metodologías y recursos prácticos en el enlace de la bio.",
                    'support' => "¡Constancia y autodominio cada día{$nameVocative}! 🏛️💪 ¡Foco total en lo esencial!",
                    'engagement_tips' => '💪 Reafirmar la mentalidad y determinación refuerza la identidad y autoridad de la marca.'
                ];
            }

            // General emojis fallback
            return [
                'source' => 'heuristic_calibrated',
                'engagement' => "¡Muchas gracias por la gran vibra{$nameVocative}! 🙌✨ ¡A seguir forjando carácter juntos!",
                'conversion' => "¡Gracias por la buena energía{$nameVocative}! 🚀✨ Recuerda que estamos a un DM de distancia para lo que necesites.",
                'support' => "¡Agradecidos con tu presencia en la comunidad{$nameVocative}! 🤝✨ ¡Un saludo enorme!",
                'engagement_tips' => '✨ Responder de forma automática a los comentarios de emojis asegura una tasa de respuesta cercana al 100%.'
            ];
        }

        // Case 0.1: Stoic Awakening & Impact Reality Checks (Bofetada de realidad, me hizo reflexionar, mensaje brutal)
        if ($intent === 'stoic_awakening_impact') {
            $engagePool = [
                "Las lecciones que más transforman casi nunca vienen con palabras suaves. Cuando una verdad incomoda y cala hondo, es señal inequívoca de que hay un carácter listo para evolucionar. Gracias por reflexionar con nosotros{$nameVocative}. 🏛️",
                "A veces hace falta esa sacudida para romper la inercia del piloto automático y recordar lo que verdaderamente importa. Un honor caminar en comunidad con personas que buscan la templanza y el autodominio. 🤝",
                "Esa 'bofetada' constructiva de realidad es la que nos despierta. El dolor de la verdad es temporal; el precio de vivir engañado es permanente. ¡Seguimos firmes forjando carácter{$nameVocative}! ⚡",
                "De eso se trata la verdadera filosofía práctica: de incomodarnos para no estancarnos. Pocos tienen la humildad de recibir el mensaje y transformar la sacudida en crecimiento real. ¡Fuerza imparable{$nameVocative}! 🏛️"
            ];

            $convertPool = [
                "Ese clic mental es el punto de partida hacia el autodominio. Si esta perspectiva resonó contigo, en el enlace de nuestra biografía compartimos guías y lecturas prácticas para profundizar en la mentalidad estoica aplicada. 📖",
                "Transformar una reflexión en un cambio duradero requiere método y disciplina cotidiana. Puedes explorar nuestras herramientas y recursos formativos en el enlace del perfil para dar el siguiente paso. 🎯",
                "La lucidez llega en el momento exacto en que dejamos de justificarnos. Si deseas estructurar este enfoque con hábitos sólidos de disciplina, tienes todo el material recomendado en la bio. 🚀"
            ];

            $supportPool = [
                "Como enseñaba Séneca: 'No nos atrevemos a muchas cosas porque son difíciles, pero son difíciles porque no nos atrevemos.' La reflexión honesta es el primer paso hacia la templanza. 🏛️",
                "El valor de los principios estoicos no es adular el ego, sino afilar la mente y templar el espíritu para cualquier adversidad. Un honor contar con aportes tan valiosos en esta comunidad."
            ];

            $rotKey = abs(crc32($commentText . $authorName . 'awake'));
            return [
                'source' => 'heuristic_calibrated',
                'engagement' => $engagePool[$rotKey % count($engagePool)],
                'conversion' => $convertPool[$rotKey % count($convertPool)],
                'support' => $supportPool[$rotKey % count($supportPool)],
                'engagement_tips' => '🏛️ Validar el despertar de consciencia con serenidad y temple afianza la autoridad y fidelidad de la comunidad.'
            ];
        }

        // Case 0.2: Stoic Short Affirmations of Truth (Verdad, literal, total, 100%, así es)
        if ($intent === 'stoic_affirmation_short') {
            $engagePool = [
                "La verdad no necesita adornos ni justificaciones; solo la determinación diaria de vivirla con coherencia. ¡Seguimos firmes{$nameVocative}! 🏛️",
                "Pocos tienen la valentía de mirar la realidad de frente y asumirla sin excusas. De eso se trata la verdadera fortaleza. 🤝",
                "Así es{$nameVocative}. Reconocer el principio es el primer paso; forjar la disciplina para sostenerlo cada día es el verdadero trabajo interior. ⚡",
                "Exacto. La claridad mental empieza en el momento exacto en que dejamos de negociar con lo esencial. ¡Un honor caminar juntos en esta comunidad! 🏛️",
                "Totalmente de acuerdo{$nameVocative}. En un mundo lleno de distracciones y excusas, mantenerse fiel a lo correcto es el mayor acto de autodominio. 💪"
            ];

            $convertPool = [
                "Exacto{$nameVocative}. Cuando tienes claros estos fundamentos, dejas de malgastar energía en lo que no depende de ti. En el enlace de nuestra biografía compartimos recursos prácticos para seguir forjando esa mentalidad. 📖",
                "Totalmente. Los principios correctos lo cambian todo cuando se aplican a diario. Si buscas herramientas estructuradas de mentalidad y disciplina, encuéntralas en el enlace de nuestro perfil. 🎯",
                "Es así. La teoría sin acción no transforma vidas; por eso creamos metodologías prácticas de autodominio. Toda la información disponible en el enlace de la bio. 🚀"
            ];

            $supportPool = [
                "Marco Aurelio lo resumió con maestría: 'Si no es correcto, no lo hagas; si no es verdad, no lo digas.' Un pilar innegociable de carácter. 🏛️",
                "La serenidad y la fuerza interior nacen de aceptar la verdad y enfocarnos al 100% en nuestras propias decisiones. Foco en lo que está bajo nuestro control. 💪",
                "Una gran verdad que distingue a quienes solo opinan de quienes construyen templanza en su vida cotidiana. Seguimos sumando valor juntos."
            ];

            $rotKey = abs(crc32($commentText . $authorName . 'affirm'));
            return [
                'source' => 'heuristic_calibrated',
                'engagement' => $engagePool[$rotKey % count($engagePool)],
                'conversion' => $convertPool[$rotKey % count($convertPool)],
                'support' => $supportPool[$rotKey % count($supportPool)],
                'engagement_tips' => '⚡ Las respuestas concisas y firmes a comentarios cortos refuerzan la autenticidad y autoridad estoica.'
            ];
        }

        // Case 1: Knowledge / Philosophical / Stoic / Concept Explanation & Virtue Reflections
        if ($intent === 'knowledge_concept') {
            $isVirtueReflection = str_contains($textLower, 'virtud') || str_contains($textLower, 'momento') || str_contains($textLower, 'decid') || str_contains($textLower, 'crear') || str_contains($textLower, 'perfect') || str_contains($textLower, 'presente') || str_contains($textLower, 'tiempo') || str_contains($textLower, 'alma') || str_contains($textLower, 'sabidur') || str_contains($textLower, 'serenidad');
            $isDichotomy = str_contains($textLower, 'dicotomia') || str_contains($textLower, 'dicotomía') || str_contains($textLower, 'control');
            $isDiscipline = str_contains($textLower, 'disciplina') || str_contains($textLower, 'motivacion') || str_contains($textLower, 'motivación') || str_contains($textLower, 'procrastin');

            if ($isVirtueReflection) {
                return [
                    'source' => 'heuristic_calibrated',
                    'engagement' => "¡Totalmente de acuerdo{$nameVocative}! $eHeart La verdadera virtud no reside en buscar condiciones perfectas, sino en actuar con rectitud en el momento presente con los recursos que disponemos. ¡Gracias por aportar una reflexión tan lúcida a la comunidad! ✨",
                    'conversion' => "¡Qué gran perspectiva{$nameVocative}! $eRocket Justamente esa filosofía de presencia y autodominio es el pilar de lo que compartimos. Si deseas profundizar en nuestras guías prácticas sobre mentalidad, en el enlace de la bio tienes el material recomendado. 📖",
                    'support' => "Una gran verdad{$nameVocative}. $ePillar Como enseñaban los antiguos estoicos, el carácter se forja eligiendo hacer propio cada instante sin buscar validación externa. Un honor contar con aportes de este calibre en la comunidad. 🏛️",
                    'engagement_tips' => '🏛️ Reconocer y validar reflexiones profundas de la comunidad consolida la autoridad y lealtad de marca.'
                ];
            }

            if ($isDichotomy) {
                return [
                    'source' => 'heuristic_calibrated',
                    'engagement' => "$greetEngage La dicotomía del control consiste en enfocar el 100% de nuestra energía en lo que sí depende de nosotros (nuestras decisiones, acciones y actitud) y aceptar con serenidad lo externo. " . (($closingQuestionRule !== 'never') ? "¿En qué situación de tu día te gustaría empezar a aplicarlo? 👇" : "Un principio clave para el autodominio."),
                    'conversion' => "$greetConvert Dominar la dicotomía del control transforma por completo tu enfoque y claridad mental. En el enlace de nuestra biografía compartimos guías y recursos prácticos sobre mentalidad estoica si deseas profundizar. ¿Qué aspecto de tu rutina buscas fortalecer hoy?",
                    'support' => "$greetSupport Para aplicarlo en lo cotidiano: ante cualquier obstáculo pregúntate '¿Está bajo mi control directo?'. Si lo está, actúa con determinación; si no, canaliza tu energía en tu propia respuesta y suelta lo demás.",
                    'engagement_tips' => '🧠 Las respuestas fundamentadas en sabiduría y autoridad consolidan a tu marca como referente de valor.'
                ];
            }

            if ($isDiscipline) {
                return [
                    'source' => 'heuristic_calibrated',
                    'engagement' => "$greetEngage La motivación es pasajera, pero la disciplina diaria se construye con pequeñas victorias cotidianas. No busques perfección inmediata, sino consistencia innegociable. " . (($closingQuestionRule !== 'never') ? "¿Cuál es esa pequeña acción que puedes completar hoy? 👇" : "El progreso diario lo cambia todo."),
                    'conversion' => "$greetConvert Cuando aplicas un método estructurado, la disciplina se vuelve un hábito natural. Puedes consultar nuestras herramientas y metodologías en el enlace de la bio para dar el siguiente paso. ¿Te gustaría conocer más sobre el método?",
                    'support' => "$greetSupport La clave para vencer la procrastinación es dividir el objetivo en una micro-tarea que puedas empezar de inmediato. ¿En qué meta estás enfocado esta semana?",
                    'engagement_tips' => '💡 Aportar consejos prácticos y accionables fomenta conversaciones de alto engagement.'
                ];
            }

            $conceptEngagePool = [
                "$greetEngage Los principios sólidos nos permiten mantener el rumbo sin importar las circunstancias externas. " . (($closingQuestionRule !== 'never') ? "¿Qué concepto o hábito te ha resultado más transformador? 💬" : "Un gusto reflexionar juntos en comunidad."),
                "Tener claridad en estos fundamentos marca el camino hacia la templanza. Gracias por enriquecer la conversación en la comunidad{$nameVocative}. 🏛️",
                "Quien domina sus pensamientos, domina su destino. La práctica cotidiana de la virtud es el mayor refugio ante la incertidumbre{$nameVocative}."
            ];
            $rotConcept = abs(crc32($commentText . $authorName . 'concept'));

            return [
                'source' => 'heuristic_calibrated',
                'engagement' => $conceptEngagePool[$rotConcept % count($conceptEngagePool)],
                'conversion' => "$greetConvert Profundizar en estos fundamentos marca la diferencia en cualquier proyecto. Te invitamos a revisar los recursos formativos en el enlace de nuestra biografía. ¿En qué área estás buscando evolucionar hoy?",
                'support' => "$greetSupport La claridad mental surge de la práctica constante y el pensamiento reflexivo. Con gusto seguimos compartiendo contenidos sobre este tema. ¿Qué duda puntual te gustaría que abordemos en el próximo post?",
                'engagement_tips' => '🏛️ El contenido de valor y reflexión genera seguidores altamente fidelizados.'
            ];
        }

        // Case 2: Commercial Lead / Course / Product / Pricing
        if ($intent === 'lead_info') {
            $isCourseStructure = str_contains($textLower, 'clases grabadas') || str_contains($textLower, 'grabada') || str_contains($textLower, 'tiempo de acceso') || str_contains($textLower, 'cuanto tiempo') || str_contains($textLower, 'cuánto tiempo') || str_contains($textLower, 'duracion') || str_contains($textLower, 'duración') || str_contains($textLower, 'temario');

            if ($isCourseStructure) {
                return [
                    'source' => 'heuristic_calibrated',
                    'engagement' => "$greetEngage Sí, el programa incluye acceso flexible a clases grabadas para que avances a tu propio ritmo con acceso continuo y material de apoyo. " . $questionLead,
                    'conversion' => "$greetConvert Cuentas con acceso a todas las clases grabadas, recursos prácticos y actualizaciones del curso. Puedes consultar el temario completo y registrarte directamente en el enlace de nuestra biografía o enviarnos un DM si tienes alguna duda puntual.",
                    'support' => "$greetSupport El contenido formativo está estructurado en módulos grabados de alta calidad para repasar cuantas veces necesites. Encuentras la información oficial y los módulos en el enlace de nuestro perfil.",
                    'engagement_tips' => '🎯 Responder directamente a dudas técnicas del curso genera confianza inmediata y acelera la decisión de compra.'
                ];
            }

            return [
                'source' => 'heuristic_calibrated',
                'engagement' => "$greetEngage ¡Qué gusto tu interés! Manejamos opciones adaptadas a tus objetivos y necesidades. Puedes consultar todos los detalles en el enlace de nuestra bio o escribirnos por DM. $questionLead",
                'conversion' => "$greetConvert Puedes ver la información completa, planes y disponibilidad directamente en el enlace de nuestra biografía, o si prefieres envíanos un DM y con gusto te orientamos.",
                'support' => "$greetSupport Toda la información de inversión, metodología y opciones disponibles está detallada en el link de nuestro perfil. Si deseas una recomendación personalizada, déjanos un mensaje privado.",
                'engagement_tips' => '🎯 Responder con claridad e invitar a los canales oficiales eleva la conversión sin crear falsas expectativas.'
            ];
        }

        // Case 3: Sales Objections / Guarantees / Doubts
        if ($intent === 'sales_objection') {
            return [
                'source' => 'heuristic_calibrated',
                'engagement' => "$greetEngage Es totalmente comprensible tu consulta. Todo nuestro trabajo cuenta con garantía de satisfacción y soporte dedicado para que tengas total tranquilidad. $questionLead",
                'conversion' => "$greetConvert Respaldamos cada programa y servicio con políticas claras de garantía y atención 1 a 1. Además, puedes revisar testimonios verificados en nuestras historias destacadas y en el enlace de la bio. ¿Te gustaría conocer más detalles?",
                'support' => "$greetSupport Tu seguridad y satisfacción son nuestra máxima prioridad. Puedes revisar los términos de satisfacción y respuestas frecuentes en el enlace de nuestro perfil, o escribirnos un DM si deseas resolver dudas específicas.",
                'engagement_tips' => '🛡️ Atender dudas con transparencia y rapidez disipa la fricción de compra y genera confianza inmediata.'
            ];
        }

        // Case 4: Customer Support / Issues
        if ($intent === 'customer_support') {
            return [
                'source' => 'heuristic_calibrated',
                'engagement' => "$greetEngage Queremos ayudarte de inmediato. Por favor envíanos un mensaje directo (DM) con los datos de tu cuenta o correo registrado para que nuestro equipo lo revise de forma prioritaria.",
                'conversion' => "$greetConvert Por favor escríbenos por mensaje privado (DM) indicándonos tu correo de registro para que nuestro equipo técnico atienda tu caso de inmediato. ¡Estamos atentos para resolverlo!",
                'support' => "$greetSupport Lamentamos cualquier inconveniente. Ya mismo nuestro equipo de asistencia está disponible: por favor contáctanos por mensaje directo para verificar tu acceso o caso hoy mismo.",
                'engagement_tips' => '🛠️ Una atención al cliente empática y ágil transforma una incidencia en una oportunidad de fidelización.'
            ];
        }

        // Case 5: Praise / Testimonials / Gratitude
        if ($intent === 'gratitude_praise') {
            return [
                'source' => 'heuristic_calibrated',
                'engagement' => "$greetEngage ¡Muchísimas gracias por tus palabras! Saber que te ha sido de gran valor es nuestra mayor satisfacción. $questionPraise",
                'conversion' => "$greetConvert ¡Qué gran alegría leer tu comentario! Nos motiva muchísimo a seguir creando lo mejor para ustedes. ¡Un fuerte abrazo!",
                'support' => "$greetSupport ¡Gracias de corazón por tu confianza y por formar parte de esta comunidad! $questionPraise",
                'engagement_tips' => '✨ Responder a los elogios con preguntas abiertas estimula la conversación e incrementa el alcance orgánico.'
            ];
        }

        // Case 6: General Comment
        return [
            'source' => 'heuristic_calibrated',
            'engagement' => "$greetEngage ¡Gracias por compartir tu opinión con la comunidad! $questionGeneral",
            'conversion' => "$greetConvert ¡Totalmente de acuerdo! Si deseas conocer más sobre lo que hacemos y recursos formativos, en el enlace de nuestra biografía tienes toda la información.",
            'support' => "$greetSupport ¡Un gran saludo! Encantados de leerte y tener tu participación en nuestra comunidad. 🙌",
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
        string $apiKey, string $model = 'anthropic/claude-3.5-sonnet',
        int $targetUserId = 0, ?PDO $pdo = null
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
            
            // Deduct / record tokens used
            $tokensUsed = (int)($resData['usage']['total_tokens'] ?? 0);
            if ($tokensUsed > 0 && $targetUserId > 0 && $pdo) {
                try {
                    $upTokens = $pdo->prepare("UPDATE users SET used_tokens = used_tokens + :tokens, last_activity_at = CURRENT_TIMESTAMP WHERE id = :uid");
                    $upTokens->execute([':tokens' => $tokensUsed, ':uid' => $targetUserId]);
                } catch (Throwable $t) {
                    error_log("Token update error: " . $t->getMessage());
                }
            }

            // Clean markdown code blocks if model wrapped output in ```json ... ```
            $content = preg_replace('/^```(?:json)?\s*/i', '', trim($content));
            $content = preg_replace('/\s*```$/', '', trim($content));

            $parsed = json_decode($content, true);

            if ($parsed && isset($parsed['engagement'])) {
                $tipNotice = 'Respuesta generada con OpenRouter (' . htmlspecialchars($selectedModel) . ') adaptada a tu voz de marca.';
                if ($tokensUsed > 0) {
                    $tipNotice .= ' [Consumo: ' . number_format($tokensUsed) . ' tokens]';
                }
                return [
                    'source' => 'openrouter_' . str_replace(['/', ':', '.'], '_', $selectedModel),
                    'engagement' => $parsed['engagement'] ?? '',
                    'conversion' => $parsed['conversion'] ?? '',
                    'support' => $parsed['support'] ?? '',
                    'tokens_used' => $tokensUsed,
                    'engagement_tips' => $parsed['engagement_tips'] ?? $tipNotice
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
5. COMENTARIOS DE SOLO EMOJIS O REACCIONES: Si el comentario del seguidor consiste en emojis o reacciones (ej. 👏👏, 🔥, ❤️, 💪, 🙌), responde de forma rápida, agradecida y cercana utilizando también emojis expresivos y coherentes con el tono de la marca, para maximizar el engagement y responder a la mayor cantidad posible de interacciones.

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
        if (empty($firstName) || self::isGenericAuthorName($firstName)) {
            $replyTemplate = str_ireplace([', {nombre}', ', {name}', ' {nombre}', ' {name}', '{nombre},', '{name},'], '', $replyTemplate);
            $replyTemplate = str_ireplace(['{nombre}', '{name}'], '', $replyTemplate);
            return preg_replace('/\s+([,\.\?!])/', '$1', preg_replace('/\s+/', ' ', trim($replyTemplate)));
        }
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
