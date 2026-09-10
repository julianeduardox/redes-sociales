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

        // 1.5 Check for Severe Toxicity / Direct Insults / Defamation / Hate Speech (Silencio Operativo)
        // Distinguir ataque a la marca vs desahogo personal del seguidor sobre sus circunstancias (búsqueda de resiliencia)
        $isDirectHateOnBrand = (
            str_contains($textLower, 'estafa') || str_contains($textLower, 'estafador') ||
            str_contains($textLower, 'estafadores') || str_contains($textLower, 'ladron') ||
            str_contains($textLower, 'ladrones') || str_contains($textLower, 'asco de cuenta') ||
            str_contains($textLower, 'muérete') || str_contains($textLower, 'muerete')
        );

        $isPersonalVenting = !$isDirectHateOnBrand && (
            str_contains($textLower, 'difícil es aguantar') || str_contains($textLower, 'dificil es aguantar') ||
            str_contains($textLower, 'aguantar a') || str_contains($textLower, 'aguantar al') ||
            str_contains($textLower, 'aguantar los') || str_contains($textLower, 'aguantar todo el día') ||
            str_contains($textLower, 'aguantar todo el dia') || str_contains($textLower, 'volverme fuerte') ||
            str_contains($textLower, 'volverme demasiado fuerte') || str_contains($textLower, 'volverme tan fuerte') ||
            str_contains($textLower, 'cansado de aguantar') || str_contains($textLower, 'cuesta aguantar') ||
            str_contains($textLower, 'cansado pero sigo') || str_contains($textLower, 'soportar a la gente') ||
            str_contains($textLower, 'soportar a los') || str_contains($textLower, 'quiere volverme') ||
            str_contains($textLower, 'hijosderemilputa') || str_contains($textLower, 'hijos de remil puta')
        );

        $severeToxicKeywords = [
            'estúpido', 'estupido', 'estúpida', 'estupida', 'idiota', 'idiotas', 'imbécil', 'imbecil',
            'imbéciles', 'imbeciles', 'basura', 'estafa', 'estafas', 'estafador', 'estafadores', 'estafando',
            'estafaron', 'fraude', 'fraudulento', 'ladrones', 'ladrón', 'ladron', 'robando', 'rateros',
            'mierda', 'puta', 'putas', 'puto', 'putos', 'hdp', 'hijo de puta', 'hija de puta', 'malparido',
            'malparidos', 'sinvergüenza', 'sinverguenza', 'sinvergüenzas', 'asqueroso', 'asquerosa',
            'muérete', 'muerete', 'inútil', 'inutil', 'inútiles', 'payaso', 'payasos', 'asco de cuenta'
        ];

        if (!$isPersonalVenting) {
            foreach ($severeToxicKeywords as $tw) {
                if (preg_match('/\b' . preg_quote($tw, '/') . '\b/iu', $textLower)) {
                    return [
                        'status' => 'toxic',
                        'should_reply' => false,
                        'reason' => '🛡️ Silencio Operativo: Comentario con insultos directos o toxicidad severa detectada. Bloqueado en Autopilot para no alimentar al hater ni darle visibilidad algorítmica.',
                        'category' => 'toxic_hostile'
                    ];
                }
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
    public static function analyzeComment(string $commentText, string $postCaption = '', int $likesCount = 0, string $authorName = ''): array {
        $suitability = self::evaluateCommentSuitability($commentText);
        if (!$suitability['should_reply']) {
            $isToxic = ($suitability['status'] === 'toxic' || $suitability['category'] === 'toxic_hostile');
            return [
                'sentiment' => $isToxic ? 'toxic' : ($suitability['status'] === 'spam' ? 'spam' : 'neutral'),
                'intent' => $suitability['category'],
                'highlight_score' => $isToxic ? 15 : ($suitability['status'] === 'spam' ? 10 : 25),
                'commercial_priority' => $isToxic ? 10 : 20,
                'is_highlighted' => 0,
                'highlight_reason' => $suitability['reason'],
                'autopilot_ready' => false,
                'autopilot_status' => 'ignored',
                'autopilot_reason' => $suitability['reason'],
                'detected_keywords' => $isToxic ? ['toxic_severe'] : []
            ];
        }

        // Special handling for visual / sticker / emoji reactions (e.g. 🦚, 🦁, 👏👏, 🔥, ❤️, 💪, 🙌)
        if ($suitability['category'] === 'emoji_reaction') {
            if (str_contains($commentText, '🦚') || str_contains($commentText, '🦁')) {
                return [
                    'sentiment' => 'positive',
                    'intent' => 'visual_sticker_reaction',
                    'highlight_score' => 85,
                    'commercial_priority' => 80,
                    'is_highlighted' => 0,
                    'highlight_reason' => '🎨 Reacción visual con sticker o emoji representativo',
                    'autopilot_ready' => true,
                    'autopilot_status' => 'ready',
                    'autopilot_reason' => '✔ Apto para Autopilot (Agradecimiento visual rápido y enérgico)',
                    'detected_keywords' => ['visual_sticker']
                ];
            }

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
        // Normalize repeated letters for semantic matching (e.g. "yooooooooo" -> "yo", "siiiiii" -> "si")
        $textNormalized = preg_replace('/(.)\1{2,}/u', '$1', $textLower);
        $textSearch = $textLower . ' ' . $textNormalized;
        
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

        // 0.1 Short Affirmations of Truth / Resonance (Verdad, sierto, literal, total, 100%, así es)
        $shortAffirmationPatterns = [
            'verdad', 'gran verdad', 'que gran verdad', 'qué gran verdad', 'totalmente',
            'literal', 'muy cierto', 'cierto', 'sierto', 'tal cual', 'exacto', 'exactamente',
            'así es', 'asi es', 'de acuerdo', '100%', '100', 'muy real', 'sin duda', 'así mismo',
            'asi mismo', 'correcto', 'total', 'es verdad', 'pura verdad', 'clarisimo', 'clarísimo',
            'sii', 'siii', 'de una', 'tal como dices'
        ];

        // 0.2 Inner Battle & Self-Mastery (El único rival, vencerse a uno mismo, imbatible, enemigo interno)
        $innerBattlePatterns = [
            'único rival', 'unico rival', 'enemigo real', 'vencerse a uno mismo', 'vencerse a si mismo',
            'vencerse a sí mismo', 'vencerte a ti mismo', 'vencerte a ti', 'si le ganas', 'sos imbatible',
            'eres imbatible', 'peor enemigo', 'nuestro propio enemigo', 'ganarle a uno mismo',
            'rival y enemigo', 'vencer el ego', 'lucha interna', 'batalla interna', 'derrotarse a uno mismo'
        ];

        // 0.3 Spiritual, Biblical & Faith References (Cristo, Filipenses, Apóstol, Dios, Salmos)
        $spiritualPatterns = [
            'cristo', 'filipenses', 'apóstol', 'apostol', 'carta a los', 'versículo', 'versiculo',
            'salmo', 'salmos', 'proverbios', 'jehová', 'jehova', 'todo lo puedo en cristo',
            'dios te bendiga', 'dios me fortalece', 'gloria a dios', 'bendito dios', 'palabra de dios',
            'amén', 'amen'
        ];

        // 0.4 Personal Growth Process & Active Discipline (Trabajando en eso, en proceso, un día a la vez)
        $processPatterns = [
            'trabajando en eso', 'trabajando en ello', 'en proceso', 'ahí vamos', 'ahi vamos',
            'un día a la vez', 'un dia a la vez', 'intentándolo', 'intentandolo', 'cuesta pero',
            'en camino', 'paso a paso', 'poniéndolo en práctica', 'poniendolo en practica',
            'construyendo esa fortaleza', 'cada día mejorando', 'cada dia mejorando'
        ];

        // 0.5 Cynicism, Sarcasm & Light Provocation (Puro humo, vendehumos, filosofía barata, cagarse en todos, payasada)
        $cynicalPatterns = [
            'puro humo', 'vende humo', 'vendehumo', 'vendehumos', 'filosofia barata', 'filosofía barata',
            'charlatan', 'charlatán', 'payasada', 'cagarse', 'cagar en', 'vaya tontería', 'vaya tonteria',
            'tonterías', 'tonterias', 'patético', 'patetico', 'ridículo', 'ridiculo', 'muy fácil hablar',
            'muy facil hablar', 'muy fácil decirlo', 'muy facil decirlo', 'pura mierda', 'que estupidez',
            'qué estupidez', 'falacia', 'vaya payasada', 'charlatanes', 'cháchara', 'chachara',
            'pura palabrería', 'pura palabreria', 'cagarse en todos', 'cagarce en todos', 'cagarse entodos'
        ];

        // 0.6 Emotional Venting & Resilience Seeking (Desahogo personal hacia el entorno buscando fortaleza)
        $ventingPatterns = [
            'difícil es aguantar', 'dificil es aguantar', 'aguantar a los', 'aguantar todo el día',
            'aguantar todo el dia', 'volverme fuerte', 'volverme demasiado fuerte', 'soportar todo el día',
            'soportar todo el dia', 'soportar a la gente', 'soportar a los', 'hijosderemilputa',
            'hijos de remil puta', 'cansado de aguantar', 'cuesta aguantar', 'quiere volverme', 'volverme tan fuerte',
            'cuesta mucho mantener', 'cuesta mantener', 'cuando todo se complica', 'se complica todo',
            'difícil mantener la calma', 'dificil mantener la calma'
        ];

        // 0.7 Community Peer Support (Seguidores apoyándose mutuamente en los comentarios)
        $peerSupportPatterns = [
            'si quieres puedes', 'si quieres podes', 'si querés podés', 'ánimo hermano', 'animo hermano',
            'tú puedes', 'tu puedes', 'vos podes', 'vos podés', 'fuerza hermano', 'estamos juntos',
            'gracias a vos', 'te entiendo hermano', 'cuenta conmigo', 'mucho ánimo', 'mucho animo', 'arriba ese ánimo'
        ];

        // 0.8 Stoic Trust & Time Filter Reflections (Dostoievski: el tiempo filtra, ellos mismos se borran)
        $trustFilterPatterns = [
            'no hay necesidad de borrar', 'no hay necesidad de eliminar', 'ellos mismos se borran',
            'ellos mismos se borraron', 'se borran solo', 'se borran solos', 'se eliminan solos',
            'se eliminan solo', 'accidente de la confianza', 'filtro del tiempo', 'el tiempo acomoda',
            'el tiempo filtra', 'el tiempo pone a cada quien', 'se van solos', 'nadie elimina a nadie',
            'se caen solos', 'se caen solas', 'solos se van', 'se borran sola', 'se borran solas',
            'se borraran solo', 'se borrarán solo', 'se borraran solos', 'se borrarán solos'
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
            'alma', 'mente', 'serenidad', 'voluntad', 'constancia', 'destino',
            'victoria es', 'victoria', 'bien comun', 'bien común', 'pensar en todos'
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
            'súper', 'top', 'felicidades', 'gracias infinitas', 'cambio mi vida', 'cambió mi vida', 'los mejores',
            'hermoso', 'hermosa', 'belleza', 'precioso', 'preciosa', 'maravilloso', 'maravillosa', 'muy lindo', 'muy linda', 'lindo', 'linda'
        ];

        // 5.1 Community Cheer, Celebration, Encouragement & Emotional Support
        $cheerPatterns = [
            'fortaleza imparable', 'vamos por más', 'vamos por mas', 'vamos con todo', 'a seguir creciendo',
            'felicitaciones', 'felicidades', 'enhorabuena', 'bendiciones', 'gran labor', 'gran trabajo',
            'excelente trabajo', 'sigan asi', 'sigan así', 'que sigan los exitos', 'que sigan los éxitos',
            'cracks', 'crack', 'los mejores', 'ídolos', 'idolos', 'un saludo', 'saludos', 'admiracion',
            'admiración', 'orgullo', 'admirador', 'admiradora', 'mucho éxito', 'mucho exito', 'adelante',
            'a tope', 'a romperla', 'pura inspiracion', 'pura inspiración', 'me encanta su contenido',
            'me encanta lo que hacen', 'gracias por su trabajo', 'gracias por compartir', 'buenisimo', 'buenísimo',
            'geniales', 'son los mejores', 'son unos cracks', 'mucho valor', 'gran contenido', 'inspirador'
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

        // Detect Cheer & Celebration
        $foundCheer = [];
        foreach ($cheerPatterns as $p) {
            if (str_contains($textLower, $p)) {
                $foundCheer[] = $p;
            }
        }

        // Detect Inner Battle & Self-Mastery
        $foundInnerBattle = [];
        foreach ($innerBattlePatterns as $p) {
            if (str_contains($textLower, $p)) {
                $foundInnerBattle[] = $p;
            }
        }

        // Detect Spiritual & Faith References
        $foundSpiritual = [];
        foreach ($spiritualPatterns as $p) {
            if (str_contains($textLower, $p)) {
                $foundSpiritual[] = $p;
            }
        }

        // Detect Personal Growth Process & Discipline
        $foundProcess = [];
        foreach ($processPatterns as $p) {
            if (str_contains($textLower, $p)) {
                $foundProcess[] = $p;
            }
        }

        // Detect friend tags only (e.g. "@amigo" or "@usuario1 @usuario2")
        $isTagOnly = (bool)preg_match('/^(@[\w\.\-]+\s*)+$/u', trim($commentText));

        // Detect Pure Visual / Sticker / GIF / Single Emoji reactions
        $strippedText = trim(preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F700}-\x{1F77F}\x{1F780}-\x{1F7FF}\x{1F800}-\x{1F8FF}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{1FA70}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{2300}-\x{23FF}\x{2B50}\x{200D}\x{FE0F}\s\p{P}\.]/u', '', $commentText));
        $isVisualOnly = empty($strippedText) 
            || $isTagOnly
            || (!empty($authorName) && strcasecmp(trim($commentText), trim($authorName)) === 0)
            || str_contains($textLower, '[gif]') 
            || str_contains($textLower, '[sticker]') 
            || str_contains($textLower, 'giphy.com')
            || str_contains($textLower, 'tenor.com');

        // Detect Conceptual / Philosophy / Stoic / Mentorship
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

        // Detect Cynicism & Provocation
        $foundCynic = [];
        foreach ($cynicalPatterns as $p) {
            if (str_contains($textLower, $p)) {
                $foundCynic[] = $p;
            }
        }

        // Detect Emotional Venting & Resilience Seeking
        $foundVenting = [];
        foreach ($ventingPatterns as $p) {
            if (str_contains($textLower, $p)) {
                $foundVenting[] = $p;
            }
        }

        // Detect Community Peer Support
        $foundPeerSupport = [];
        foreach ($peerSupportPatterns as $p) {
            if (str_contains($textLower, $p)) {
                $foundPeerSupport[] = $p;
            }
        }
        if (preg_match('/\b[aá]nimo\b/u', $textLower) && !str_contains($textLower, 'cuesta') && !str_contains($textLower, 'falta') && !str_contains($textLower, 'perder') && !str_contains($textLower, 'sin ') && empty($foundVenting)) {
            $foundPeerSupport[] = 'ánimo';
        }

        // Detect Stoic Trust & Time Filter Reflections
        $foundTrustFilter = [];
        foreach ($trustFilterPatterns as $p) {
            if (str_contains($textSearch, $p)) {
                $foundTrustFilter[] = $p;
            }
        }

        // Check if there is an explicit question or commercial buying inquiry
        $hasQuestionMark = str_contains($commentText, '?') || str_contains($commentText, '¿');
        $hasBuyingTerm   = (bool)preg_match('/\b(precio|costo|planes|comprar|cuotas|link de compra|cómo compro|donde compro)\b/iu', $textLower);
        $hasQuestionWord = (bool)preg_match('/\b(cómo|cuánto|cuanto|dónde|cuál|cual|por qué)\b/iu', $textLower) && 
                           ($hasQuestionMark || $hasBuyingTerm || str_starts_with($textLower, 'cómo ') || str_starts_with($textLower, 'como ') || str_starts_with($textLower, 'donde ') || str_starts_with($textLower, 'dónde '));
        $hasQuestion     = $hasQuestionMark || $hasBuyingTerm || $hasQuestionWord;

        // Priority Classification (Layer 1 & Layer 2)
        if (!empty($foundVenting)) {
            $sentiment = 'neutral';
            $intent = 'emotional_venting_resilience';
            $score = 92;
            $highlightReason = '🧠 Desahogo Emocional & Resiliencia: Usuario expresando frustración con el entorno; responder con serenidad y contención mental';
            $keywords = $foundVenting;
            $autopilotReady = true;
            $autopilotStatus = 'ready';
            $autopilotReason = '✔ Apto para Autopilot (Contención estoica sobria sin validar el insulto ni usar fiesta)';
        } elseif (!empty($foundTrustFilter)) {
            $sentiment = 'positive';
            $intent = 'life_filter_reflection';
            $score = 94;
            $highlightReason = '🏛️ Reflexión sobre la Confianza & Filtro del Tiempo: Usuario complementando la idea de que el tiempo aparta a quien no cuida la confianza';
            $keywords = $foundTrustFilter;
            $autopilotReady = true;
            $autopilotStatus = 'ready';
            $autopilotReason = '✔ Apto para Autopilot (Validación empática de la lealtad y el filtro natural del tiempo)';
        } elseif (!empty($foundPeerSupport)) {
            $sentiment = 'positive';
            $intent = 'community_peer_support';
            $score = 90;
            $highlightReason = '🤝 Apoyo entre la Comunidad: Seguidores dándose ánimo mutuamente en los hilos';
            $keywords = $foundPeerSupport;
            $autopilotReady = true;
            $autopilotStatus = 'ready';
            $autopilotReason = '✔ Apto para Autopilot (Celebración de la tribu y compañerismo)';
        } elseif (!empty($foundCynic)) {
            $sentiment = 'negative';
            $intent = 'criticism_cynical';
            $score = 65;
            $highlightReason = '⚖️ Desacuerdo, Cinismo o Provocación: Responder con templanza y serenidad estoica sin confrontar ni usar emojis festivos';
            $keywords = $foundCynic;
            $autopilotReady = true;
            $autopilotStatus = 'ready';
            $autopilotReason = '✔ Apto para Autopilot (Desescalada neutral y templada de marca)';
        } elseif (!empty($foundInnerBattle)) {
            $sentiment = 'positive';
            $intent = 'stoic_inner_battle';
            $score = 98;
            $highlightReason = '🏛️ Batalla Interna & Autodominio: Validación del único rival real (vencerse a uno mismo)';
            $keywords = $foundInnerBattle;
            $autopilotReady = true;
            $autopilotStatus = 'ready';
            $autopilotReason = '✔ Apto para Autopilot (Alineación con la batalla interior estoica)';
        } elseif (!empty($foundSpiritual)) {
            $sentiment = 'positive';
            $intent = 'spiritual_biblical_faith';
            $score = 95;
            $highlightReason = '🙏 Cita Espiritual / Bíblica / Fe: Mensaje de inspiración religiosa o espiritual';
            $keywords = $foundSpiritual;
            $autopilotReady = true;
            $autopilotStatus = 'ready';
            $autopilotReason = '✔ Apto para Autopilot (Respeto fraternal y validación espiritual)';
        } elseif (!empty($foundProcess)) {
            $sentiment = 'positive';
            $intent = 'personal_growth_process';
            $score = 95;
            $highlightReason = '💪 Compromiso Personal & Proceso: Usuario poniendo en práctica la disciplina diaria';
            $keywords = $foundProcess;
            $autopilotReady = true;
            $autopilotStatus = 'ready';
            $autopilotReason = '✔ Apto para Autopilot (Motivación empática hacia la disciplina diaria)';
        } elseif ($isVisualOnly) {
            $sentiment = 'positive';
            $intent = 'visual_sticker_reaction';
            $score = 90;
            $highlightReason = $isTagOnly 
                ? '🤝 Etiqueta a Amigos: El seguidor recomienda la publicación compartiéndola con un conocido' 
                : '🎨 Reacción Visual / Sticker / GIF: Participación gráfica sin texto o con emojis representativos';
            $keywords = $isTagOnly ? ['[etiqueta_amigo]'] : ['[sticker_o_gif]'];
            $autopilotReady = true;
            $autopilotStatus = 'ready';
            $autopilotReason = '✔ Apto para Autopilot (Agradecimiento visual rápido y enérgico)';
        } elseif (!empty($foundAwakening)) {
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
        } elseif (!empty($foundCheer) && !$hasQuestion) {
            $sentiment = 'positive';
            $intent = 'celebracion_apoyo';
            $score = 96;
            $highlightReason = '🎉 Celebración, Apoyo & Entusiasmo: Reconocimiento afectuoso a la labor de la comunidad';
            $keywords = $foundCheer;
            $autopilotReady = true;
            $autopilotStatus = 'ready';
            $autopilotReason = '✔ Apto para Autopilot (Agradecimiento cálido, humano y recíproco)';
        } elseif (!empty($foundPraise) && !$hasQuestion) {
            $sentiment = 'positive';
            $intent = 'celebracion_apoyo';
            $score = 94;
            $highlightReason = '✨ Elogio & Apreciación de la Comunidad: Comentario positivo de alta valoración';
            $keywords = $foundPraise;
            $autopilotReady = true;
            $autopilotStatus = 'ready';
            $autopilotReason = '✔ Apto para Autopilot (Agradecimiento cálido y humano)';
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
     * Detect philosophical or cultural author in the post caption (Module 2)
     */
    public static function detectPostAuthor(string $caption): string {
        $capLower = mb_strtolower($caption, 'UTF-8');
        if (str_contains($capLower, 'marco aurelio') || str_contains($capLower, 'marco-aurelio')) {
            return 'marco_aurelio';
        }
        if (str_contains($capLower, 'seneca') || str_contains($capLower, 'séneca')) {
            return 'seneca';
        }
        if (str_contains($capLower, 'dostoyevski') || str_contains($capLower, 'dostoievski') || str_contains($capLower, 'dostoevsky')) {
            return 'dostoievski';
        }
        if (str_contains($capLower, 'epicteto') || str_contains($capLower, 'epictetus')) {
            return 'epicteto';
        }
        return 'general';
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

        // 1.1 Detect post author for contextual replies (Module 2)
        $postAuthor = self::detectPostAuthor($postCaption);

        // 1.2 Determine comment length category (Module 1)
        $commentLen = mb_strlen($commentText);
        if ($commentLen <= 25) {
            $lengthCategory = 'short';
        } elseif ($commentLen > 80) {
            $lengthCategory = 'long';
        } else {
            $lengthCategory = 'medium';
        }

        // 1.3 Seed rotation with reply index, thread memory and daily seed (Module 5 Deep)
        $replyIndex = (int)($runtimeOverrides['reply_index'] ?? 0);
        $postId = (int)($runtimeOverrides['post_id'] ?? 0);
        $rotKey = abs(crc32($authorName . $replyIndex . $postId . date('Ymd')));
        $nameVocative = self::extractCleanFirstName($authorName);
        $nameVocative = $nameVocative ? " $nameVocative" : '';

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

        // Thread Memory: fetch recent replies to comments on this post for deduplication (Module 5 Deep)
        $recentThreadReplies = $runtimeOverrides['recent_thread_replies'] ?? [];
        if (empty($recentThreadReplies) && $postId > 0) {
            $recentThreadReplies = self::fetchRecentPostReplies($pdo, $postId, $targetUserId, 15);
        }

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

        // Fallback / Standalone: High-Context Calibrated Zero-Token Heuristic Engine (Modules 1-5 Deep)
        $localResult = self::generateHeuristicReplies(
            $authorName, $commentText, $platform, $postCaption, 
            $brandName, $personaName, $brandIndustry, $brandTone, $brandDescription, $language,
            $warmthLevel, $depthLevel, $energyLevel,
            $closingQuestionRule, $emojiStyle, $keyPhrases, $forbiddenPhrases, $fewShotExamples,
            $replyIndex,
            $postId,
            $recentThreadReplies
        );

        return self::sanitizeRepliesWithForbidden($localResult, $forbiddenPhrases);
    }

    /**
     * Built-in Calibrated Zero-Token Universal Engine
     * Modulated by Dynamic Tone Metrics (Module 4) & Thread Anti-Repetition (Module 5)
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
        array $fewShotExamples = [],
        int $replyIndex = 0,
        int $postId = 0,
        array $recentThreadReplies = []
    ): array {
        $cleanComment = trim($commentText);
        $analysis = self::analyzeComment($commentText, $postCaption, 0, $authorName);
        $intent = $analysis['intent'] ?? 'general_conversation';
        $postAuthor = self::detectPostAuthor($postCaption);

        // Seed for consistent yet varied rotation across identical comments & threads (Module 5 Deep)
        $rotKey = abs(crc32($cleanComment . '|' . $authorName . '|' . $intent . '|' . $replyIndex . '|' . $postId . '|' . date('Ymd')));

        $rawReplies = self::_generateRawHeuristicReplies(
            $authorName,
            $commentText,
            $platform,
            $postCaption,
            $brandName,
            $personaName,
            $brandIndustry,
            $brandTone,
            $brandDescription,
            $language,
            $warmthLevel,
            $depthLevel,
            $energyLevel,
            $closingQuestionRule,
            $emojiStyle,
            $keyPhrases,
            $forbiddenPhrases,
            $fewShotExamples,
            $replyIndex,
            $rotKey,
            $analysis,
            $recentThreadReplies
        );

        // Second-layer Anti-Repetition Guarantee: for short or reaction comments, if result collides with thread history, synthesize modularly
        $isTagOnly = (bool)preg_match('/^(@[\w\.\-]+\s*)+$/u', $cleanComment);
        $isVisualReaction = ($intent === 'visual_sticker_reaction' || $intent === 'emoji_reaction');
        $isShort = (mb_strlen($cleanComment, 'UTF-8') <= 25) || $isTagOnly || $isVisualReaction;

        if ($isShort && !empty($recentThreadReplies) && isset($rawReplies['engagement']) && self::isTooSimilarToRecent($rawReplies['engagement'], $recentThreadReplies, 0.65)) {
            $rawReplies['engagement'] = self::generateModularCombinatorialReply(
                $authorName,
                $postCaption,
                $postAuthor,
                $warmthLevel,
                $energyLevel,
                $depthLevel,
                $emojiStyle,
                $rotKey + $replyIndex,
                $recentThreadReplies
            );
        }

        return self::applyDynamicToneAndStyle(
            $rawReplies,
            $warmthLevel,
            $depthLevel,
            $energyLevel,
            $emojiStyle,
            $closingQuestionRule,
            $commentText,
            $rotKey,
            $intent,
            $postAuthor
        );
    }

    /**
     * Raw Heuristic Reply Generator using Intent-Calibrated Pools and Context (Modules 1-5)
     */
    private static function _generateRawHeuristicReplies(
        string $authorName,
        string $commentText,
        string $platform,
        string $postCaption,
        string $brandName,
        string $personaName,
        string $brandIndustry,
        string $brandTone,
        string $brandDescription,
        string $language,
        int $warmthLevel,
        int $depthLevel,
        int $energyLevel,
        string $closingQuestionRule,
        string $emojiStyle,
        array $keyPhrases,
        array $forbiddenPhrases,
        array $fewShotExamples,
        int $replyIndex,
        int $rotKey,
        ?array $analysis = null,
        array $recentThreadReplies = []
    ): array {
        $cleanComment = trim($commentText);
        $isGeneric = self::isGenericAuthorName($authorName);
        $displayName = $isGeneric ? '' : self::extractCleanFirstName($authorName);
        $nameVocative = (!empty($displayName) && $warmthLevel >= 45) ? ", $displayName" : '';

        if ($analysis === null) {
            $analysis = self::analyzeComment($commentText, $postCaption, 0, $authorName);
        }
        $intent = $analysis['intent'] ?? 'general_conversation';
        $textLower = mb_strtolower($cleanComment, 'UTF-8');

        // Check if there is a matching master few-shot example registered by the user
        $matchedExample = self::findMatchingFewShotExample($commentText, $fewShotExamples);
        if ($matchedExample) {
            $adapted = self::adaptFewShotReply($matchedExample['reply'], $displayName);
            return [
                'source' => 'heuristic_few_shot_trained',
                'engagement' => $adapted,
                'conversion' => $adapted,
                'support' => $adapted,
                'engagement_tips' => '🧠 Respuesta enriquecida por el Ejemplo Maestro entrenado para este patrón.'
            ];
        }

        // Layer 1: Input Metrics & Proportionality (Module 1)
        $charCount = mb_strlen($cleanComment, 'UTF-8');
        if ($charCount <= 25) {
            $lengthCategory = 'short';
        } elseif ($charCount > 80) {
            $lengthCategory = 'long';
        } else {
            $lengthCategory = 'medium';
        }
        $isShort = ($lengthCategory === 'short');
        $isLong  = ($lengthCategory === 'long');

        // Post Author Detection (Module 2)
        $postAuthor = self::detectPostAuthor($postCaption);

        // Friend Tag Detection (Edge case)
        $isTagOnly = (bool)preg_match('/^(@[\w\.\-]+\s*)+$/u', $cleanComment);

        // Friendly personal name connectors (NEVER use "¡Hola Usuario!")
        $namePrefix = !empty($displayName) ? "¡Muchas gracias, $displayName! " : "¡Muchas gracias! ";
        $helloName  = !empty($displayName) ? "¡Hola $displayName! " : "¡Hola! ";

        // Rotation picker closure ensuring seed shift and deduplication with $recentThreadReplies (Module 5 Deep)
        $pick = function(array $pool, int $step = 0) use ($rotKey, $replyIndex, $recentThreadReplies): string {
            $poolCount = count($pool);
            if ($poolCount === 0) return '';
            if (empty($recentThreadReplies)) {
                return $pool[($rotKey + $replyIndex + $step) % $poolCount];
            }
            for ($i = 0; $i < $poolCount; $i++) {
                $candidate = $pool[($rotKey + $replyIndex + $step + $i) % $poolCount];
                if (!self::isTooSimilarToRecent($candidate, $recentThreadReplies, 0.65)) {
                    return $candidate;
                }
            }
            return $pool[($rotKey + $replyIndex + $step) % $poolCount];
        };

        // ══════════════════════════════════════════════════════════════════════
        // CASE 0: Toxicidad Hostil / Insultos Graves (Silencio Operativo / Sobriedad)
        // ══════════════════════════════════════════════════════════════════════
        if ($intent === 'toxic_hostile') {
            return [
                'source' => 'heuristic_calibrated',
                'engagement' => "En esta comunidad priorizamos el respeto mutuo. Cualquier duda o consulta sobre nuestros proyectos puede canalizarse con gusto por mensaje privado. Saludos.",
                'conversion' => "Fomentamos un espacio constructivo y de respeto. Toda consulta formal se atiende por mensaje privado.",
                'support' => "El criterio y la templanza se demuestran con respeto. Te deseamos lo mejor en tu camino.",
                'engagement_tips' => '🛡️ Silencio Operativo: No responder públicamente a insultos graves para no alimentar al hater ni darle tracción algorítmica.'
            ];
        }

        // ══════════════════════════════════════════════════════════════════════
        // CASE 0.5: Cinismo, Sarcasmo, Provocación o Desacuerdo Ácido (criticism_cynical)
        // ══════════════════════════════════════════════════════════════════════
        if ($intent === 'criticism_cynical') {
            if ($isShort) {
                $cynicEngagePool = [
                    "Agradecemos tu tiempo y perspectiva{$nameVocative}. Saludos cordiales. 🏛️",
                    "Respetamos tu punto de vista{$nameVocative}. Que tengas un buen día. ✨",
                    "Cada quien tiene su propio criterio{$nameVocative}. Seguimos firmes. 🏛️",
                    "Agradecemos tu comentario{$nameVocative}. Un saludo respetuoso. ✨",
                    "El criterio es libre{$nameVocative}. Te deseamos una excelente semana. 🏛️"
                ];
            } else {
                $cynicEngagePool = [
                    "Agradecemos tu tiempo y perspectiva{$nameVocative}. Seguimos enfocados en compartir contenido constructivo para quienes buscan crecer día a día. ¡Que tengas un buen día! ✨",
                    "Comprendemos que no todas las visiones coincidan{$nameVocative}. En esta comunidad compartimos principios prácticos con quienes desean aplicarlos. Un saludo respetuoso. 🏛️",
                    "Respetamos tu punto de vista{$nameVocative}. La autoexigencia y el criterio propio son libres; seguimos firmes aportando valor a quienes les resuene. ✨",
                    "La serenidad estoica enseña a convivir con la diversidad de criterios{$nameVocative}. Nos enfocamos en aportar valor a quienes buscan mejorar. Saludos cordiales. 🏛️",
                    "Agradecemos el comentario{$nameVocative}. Cada experiencia es distinta; nosotros seguimos dedicados a forjar disciplina y criterio en nuestra comunidad. ✨"
                ];
            }

            $cynicConvertPool = [
                "Comprendemos que existan posturas escépticas. Para quienes buscan metodologías estructuradas de mentalidad y disciplina, nuestros recursos están siempre disponibles en el perfil. 🎯",
                "El valor de un método se comprueba en la práctica cotidiana. Puedes consultar guías y herramientas estructuradas en el enlace de nuestra biografía. 📖",
                "Respetamos cada criterio. Las herramientas formativas oficiales continúan disponibles en el enlace del perfil para quienes deseen profundizar con método. 🚀"
            ];

            $cynicSupportPool = [
                "La filosofía práctica no busca complacer a todos, sino invitar a la autoexigencia personal. Respetamos tu opinión y te deseamos lo mejor. 🏛️",
                "El autodominio se demuestra manteniendo la compostura ante cualquier desacuerdo. Un saludo respetuoso{$nameVocative}. 🏛️",
                "Como recordaban los clásicos, la tranquilidad interior depende de nuestros propios juicios, no de las opiniones externas. Saludos. 🏛️"
            ];

            return [
                'source' => 'heuristic_calibrated',
                'engagement' => $pick($cynicEngagePool),
                'conversion' => $pick($cynicConvertPool),
                'support'    => $pick($cynicSupportPool),
                'engagement_tips' => '⚖️ Desarmar el cinismo con cortesía y templanza. Prohibición estricta de emojis festivos para proyectar autoridad.'
            ];
        }

        // ══════════════════════════════════════════════════════════════════════
        // CASE 0.7: Desahogo Emocional & Resiliencia (Frustración con el entorno)
        // ══════════════════════════════════════════════════════════════════════
        if ($intent === 'emotional_venting_resilience') {
            if ($isShort) {
                $ventingEngagePool = [
                    "Mantén la calma y el temple{$nameVocative}. Fuerza y mente fría. 🧠",
                    "Foco total en ti{$nameVocative}. No dejes que lo externo te quite la paz. 🏛️",
                    "Temple y serenidad{$nameVocative}. Las tormentas pasan, el carácter queda. 🧠✨",
                    "Fuerza interior{$nameVocative}. Solo controlas tu propia respuesta, nada más. 🏛️",
                    "Respira hondo y adelante{$nameVocative}. Firmeza absoluta en tu camino. 🧠💪"
                ];
            } else {
                $authorQuote = match($postAuthor) {
                    'seneca' => ' Como enseñaba Séneca, las dificultades no vienen a destruirnos, sino a forjar y medir nuestro carácter.',
                    'marco_aurelio' => ' Como recordaba Marco Aurelio, nadie puede herir tu mente si tú no le concedes ese poder.',
                    'epicteto' => ' Como enseñaba Epicteto, no nos perturban los hechos ajenos, sino el juicio que hacemos de ellos.',
                    default => ' Las mayores batallas son las que forjan un espíritu inquebrantable.'
                };

                $ventingEngagePool = [
                    "Las batallas diarias son las que más temple exigen{$nameVocative}. Mantén la calma y enfócate en tu propio crecimiento mental. Ánimo. 🧠✨",
                    "Totalmente comprensible la frustración{$nameVocative}.{$authorQuote} Mantén la serenidad y el foco en ti. 🏛️",
                    "El entorno a menudo pone a prueba la paciencia{$nameVocative}. Recuerda que no podemos controlar a los demás, solo nuestro propio autodominio y respuesta. Fuerza y mente fría. 🧠",
                    "Cuando el exterior aprieta, el verdadero refugio es la serenidad interna{$nameVocative}. Tu paz vale mucho más que cualquier fricción externa. Firmeza y temple. 🏛️",
                    "Respirar con calma y mantener el autodominio{$nameVocative}. Quien domina sus reacciones ante la adversidad se vuelve imbatible. Estamos contigo en el camino. 🧠✨"
                ];
            }

            $ventingConvertPool = [
                "Canalizar la fricción diaria en disciplina constructiva es el verdadero reto. En el enlace de nuestro perfil compartimos herramientas prácticas sobre mentalidad y autodominio estoico. 🏛️",
                "Cuando las circunstancias externas retan tu paciencia, el método es tu mejor aliado. En el enlace de la bio tienes guías aplicadas para templar el carácter. 🎯",
                "El control emocional se entrena como un músculo. Te invitamos a revisar las lecturas y recursos formativos disponibles en nuestro perfil. 📖"
            ];

            $ventingSupportPool = [
                "El autodominio se forja en el choque con la realidad cotidiana. Respira, mantén la serenidad y enfócate únicamente en lo que sí está bajo tu control. 🏛️",
                "La serenidad no es ausencia de problemas, sino la capacidad de responder con criterio y templanza. Fuerza en tu jornada{$nameVocative}. 🏛️",
                "Cada desafío cotidiano es una oportunidad práctica para ejercitar la paciencia y el autodominio. Foco innegociable en tu tranquilidad. 🧠"
            ];

            return [
                'source' => 'heuristic_calibrated',
                'engagement' => $pick($ventingEngagePool),
                'conversion' => $pick($ventingConvertPool),
                'support'    => $pick($ventingSupportPool),
                'engagement_tips' => '🧠 Contención sobria y psicológica. Cero validación de insultos y CERO emojis festivos (🔥/❤️/😂) para proyectar madurez estoica.'
            ];
        }

        // ══════════════════════════════════════════════════════════════════════
        // CASE 0.8: Apoyo entre Seguidores de la Comunidad (Peer Support en hilos)
        // ══════════════════════════════════════════════════════════════════════
        if ($intent === 'community_peer_support') {
            if ($isTagOnly) {
                $peerEngagePool = [
                    "¡Muchas gracias por compartirlo con tu gente{$nameVocative}! 🙌✨",
                    "¡Qué buena onda por pasar la voz{$nameVocative}! Se aprecia mucho el apoyo en la comunidad. 🤝🔥",
                    "¡Gracias por etiquetar y sumar a la tribu{$nameVocative}! Vamos con todo. ⚡",
                    "¡Agradecidos al 100% de que lo compartas{$nameVocative}! Un fuerte abrazo. 🚀✨",
                    "¡Esa es la actitud, expandiendo la tribu{$nameVocative}! Mucho valor por aquí. 🏛️🤝"
                ];
            } elseif ($isShort) {
                $peerEngagePool = [
                    "¡Esa es la tribu{$nameVocative}! Apoyo total. 🤝🔥",
                    "¡Qué gran vibra{$nameVocative}! Juntos somos más fuertes. 👊⚡",
                    "¡Así se habla{$nameVocative}! Hermandad pura. 🤝✨",
                    "¡De una{$nameVocative}! Fuerza y apoyo mutuo. 🔥🙌",
                    "¡Totalmente{$nameVocative}! Comunidad unida. ⚡🤝"
                ];
            } else {
                $peerEngagePool = [
                    "¡Qué gran comunidad de apoyo mutuo se armó por aquí{$nameVocative}! Así se forja una verdadera tribu. 🤝🔥",
                    "Exactamente{$nameVocative}. El apoyo entre nosotros y la determinación compartida multiplican la fuerza. ¡Vamos con todo! 👊✨",
                    "Da gusto ver esta hermandad en los comentarios{$nameVocative}. Juntos nos impulsamos a ser mejores cada día. 🤝⚡",
                    "La fuerza del grupo multiplica el temple individual{$nameVocative}. ¡Ese es el verdadero sentido de formar comunidad! 🤝🏛️",
                    "Caminar acompañados de personas con la misma determinación no tiene precio{$nameVocative}. ¡A seguir sumando juntos! 🙌🔥"
                ];
            }

            $peerConvertPool = [
                "¡Esa es la verdadera tribu! Para seguir fortaleciendo este compromiso en equipo, en el enlace de la bio encuentras recursos de formación y mentalidad. 🚀",
                "Comunidades unidas llegan mucho más lejos. Tienes recursos formativos y guías de hábitos en el enlace del perfil para avanzar en equipo. 🎯",
                "¡Gracias por hacer crecer esta comunidad! En el enlace de nuestra biografía compartimos materiales y guías para potenciar este crecimiento conjunto. 📖✨"
            ];

            $peerSupportPool = [
                "Como enseñaba Marco Aurelio: 'Hemos nacido para colaborar unos con otros, como los pies, las manos y los ojos.' Un gran ejemplo de comunidad{$nameVocative}. 🏛️🤝",
                "El soporte mutuo y el respeto fraterno son las columnas que sostienen a cualquier grupo con propósito. Un honor contar contigo. 🏛️✨",
                "Cuando compartimos principios y empuje mutuo, los obstáculos se vuelven lecciones colectivas. ¡Seguimos firmes{$nameVocative}! 🤝⚡"
            ];

            return [
                'source' => 'heuristic_calibrated',
                'engagement' => $pick($peerEngagePool),
                'conversion' => $pick($peerConvertPool),
                'support'    => $pick($peerSupportPool),
                'engagement_tips' => '🤝 Celebrar la camaradería entre seguidores fortalece el sentido de pertenencia y engagement orgánico.'
            ];
        }

        // ══════════════════════════════════════════════════════════════════════
        // CASE 0.9: Reflexión sobre la Confianza & Filtro del Tiempo (Dostoievski)
        // ══════════════════════════════════════════════════════════════════════
        if ($intent === 'life_filter_reflection') {
            if ($isShort) {
                $trustPool = [
                    "Totalmente de acuerdo{$nameVocative}, el tiempo solo acomoda las cosas. 🎯",
                    "¡Así es{$nameVocative}! El mejor filtro es el tiempo. 🤝✨",
                    "Tal cual{$nameVocative}. Los hechos siempre hablan solos. 🏛️",
                    "Exacto{$nameVocative}. La lealtad se demuestra con acciones. 🎯",
                    "Sin duda{$nameVocative}. El tiempo pone a cada quien en su lugar. ⏳✨",
                    "¡De una{$nameVocative}! Quien no cuida la confianza se aparta solo. 👊"
                ];
            } else {
                $dostoQuote = ($postAuthor === 'dostoievski') 
                    ? " Como escribía Dostoyevski, el carácter y la lealtad se revelan en los hechos decisivos; dejar que el tiempo filtre es sabiduría pura. ⏳🏛️"
                    : " Dejar que el tiempo filtre sin desgaste personal es sabiduría pura. ⏳🏛️";

                $trustPool = [
                    "Totalmente de acuerdo{$nameVocative}, el tiempo solo acomoda las cosas y filtra a quien debe estar. 🎯",
                    "¡Así es{$nameVocative}! El mejor filtro es el tiempo; quien no valora la confianza se aparta por su propia cuenta. 🤝✨",
                    "Tal cual{$nameVocative}. No hace falta desgastarse en borrar a nadie; la vida y los hechos ponen a cada quien en su lugar. 🏛️",
                    "Completamente de acuerdo{$nameVocative}.{$dostoQuote}",
                    "Una verdad contundente{$nameVocative}. Las acciones siempre terminan desnudando las intenciones; no hay prisa cuando la calma y el criterio están de nuestro lado. 🎯",
                    "Muy cierta tu reflexión{$nameVocative}. Mantener la serenidad y no forzar lealtades es la mayor muestra de madurez y templanza estoica. 🏛️✨"
                ];
            }

            $trustConvertPool = [
                "Comprender que la lealtad se demuestra con hechos transforma nuestras relaciones. En el enlace de nuestra bio compartimos recursos y guías prácticas sobre criterio y mentalidad. 🚀",
                "El discernimiento y la claridad en nuestros vínculos son claves de crecimiento. Tienes lecturas y metodologías recomendadas en el perfil. 🎯",
                "Cuando filtras con serenidad, ahorras energía para lo verdaderamente importante. Encuentra recursos prácticos de mentalidad en el link de la bio. 📖✨"
            ];

            $trustSupportPool = [
                "Como enseñaba la filosofía clásica, no nos corresponde forzar lealtades, sino vivir con rectitud y dejar que las acciones de cada quien hablen por sí solas. 🏛️",
                "El tiempo es el juez más paciente e infalible. Quien actúa con integridad jamás pierde, simplemente decanta lo que no suma. 🏛️✨",
                "La paz interior se protege dejando ir sin rencores y con la frente en alto. Una reflexión impecable{$nameVocative}. 🏛️🤝"
            ];

            return [
                'source' => 'heuristic_calibrated',
                'engagement' => $pick($trustPool),
                'conversion' => $pick($trustConvertPool),
                'support'    => $pick($trustSupportPool),
                'engagement_tips' => '🎯 Validar reflexiones profundas sobre la confianza genera una enorme afinidad y respeto en la comunidad.'
            ];
        }

        // ══════════════════════════════════════════════════════════════════════
        // CASE 1: Celebración, Apoyo, Elogios y Felicitaciones (Fast Track)
        // ══════════════════════════════════════════════════════════════════════
        if ($intent === 'celebracion_apoyo' || $intent === 'gratitude_praise') {
            $isAestheticPraise = str_contains($textLower, 'hermos') || str_contains($textLower, 'bello') || str_contains($textLower, 'bella') || str_contains($textLower, 'lindo') || str_contains($textLower, 'linda') || str_contains($textLower, 'precios');

            if ($isShort) {
                if ($isAestheticPraise) {
                    $engagePool = [
                        "¡Muchas gracias{$nameVocative}! Qué bueno que resuene contigo. ✨",
                        "¡Muchas gracias de corazón{$nameVocative}! Un gusto enorme que te transmita tanto. ✨🙌",
                        "¡Qué alegría leerte{$nameVocative}! Apreciamos mucho que formes parte de esta comunidad. ✨",
                        "¡Mucho aprecio{$nameVocative}! Qué lindo mensaje de apoyo. ❤️✨",
                        "¡Mil gracias{$nameVocative}! Un saludo con todo el cariño. ✨🙌"
                    ];
                } else {
                    // Respuestas cortas, enérgicas y recíprocas (1 línea contundente + emoji)
                    $engagePool = [
                        "¡A tope con esa energía{$nameVocative}! Un fuerte abrazo. 🙌✨",
                        "¡Muchísimas gracias por el apoyo constante{$nameVocative}! Seguimos con todo. 🤝🔥",
                        "¡Esa es la actitud{$nameVocative}! Un gusto enorme tenerte en la comunidad. ⚡🙌",
                        "¡Seguimos firmes y creciendo juntos{$nameVocative}! Gracias de corazón. 💪✨",
                        "¡Qué buena onda{$nameVocative}! ¡A romperla hoy! 🔥🚀",
                        "¡Así se habla{$nameVocative}! Puro impulso y determinación. ⚡👊",
                        "¡De una{$nameVocative}! Seguimos creando contenido de valor para ti. 🤝🔥",
                        "¡Mucho aprecio por el apoyo constante{$nameVocative}! Adelante siempre. ⚡"
                    ];
                }

                $convertPool = [
                    "¡Gracias por el impulso{$nameVocative}! En el enlace del perfil compartimos más herramientas para seguir sumando. 🚀",
                    "¡Agradecidos al 100%{$nameVocative}! Tienes recursos prácticos en la bio para llevar esto al siguiente nivel. 🎯",
                    "¡Esa es la visión{$nameVocative}! En el link de nuestra biografía encuentras guías clave para continuar avanzando. 📖✨",
                    "¡Seguimos sumando! En el enlace de nuestro perfil encuentras materiales exclusivos para tu formación. 🚀"
                ];

                $supportPool = [
                    "Agradecemos el reconocimiento{$nameVocative}. Constancia y disciplina cada día. 🏛️💪",
                    "Un honor contar con tu presencia en la comunidad{$nameVocative}. Seguimos firmes forjando carácter. ⚡🏛️",
                    "La verdadera fortaleza se forja con el trabajo diario. ¡Un saludo muy especial{$nameVocative}! 🏛️🤝",
                    "Firmeza y convicción en cada paso. Agradecemos mucho tu acompañamiento en la comunidad. 🏛️"
                ];
            } else {
                // Comentarios medianos / largos efusivos
                $engagePool = [
                    (!empty($displayName) ? "¡Qué gran alegría leerte, $displayName! " : "¡Qué gran alegría leerte! ") . "Apreciamos de corazón la buena energía y el apoyo. ¡Vamos por más con todo! 🙌✨",
                    "Comentarios como el tuyo motivan muchísimo a seguir creando y mejorando cada día{$nameVocative}. ¡Seguimos con todo! 🤝🔥",
                    "¡Esa es la actitud imparable{$nameVocative}! Gracias por sumar tanto a esta comunidad. Seguimos firmes y creciendo juntos. 💪✨",
                    "¡Muchísimas gracias por las buenas palabras y el impulso constante{$nameVocative}! Da gusto caminar en comunidad con esta determinación. 🙌🚀",
                    "¡Totalmente! Gracias por la vibra y por acompañarnos en este camino{$nameVocative}. ¡Seguimos forjando carácter juntos! ⚡✨",
                    "¡Qué orgullo contar con tu participación activa y tu buena energía{$nameVocative}! Vamos adelante con más fuerza que nunca. 👊🔥",
                    "Apreciamos inmensamente que te tomes el tiempo de dejarnos un mensaje tan motivador{$nameVocative}. ¡A seguir sumando valor! ✨🤝"
                ];

                $convertPool = [
                    "¡Gracias por el impulso{$nameVocative}! Si quieres llevar esta mentalidad al siguiente nivel, en el enlace de nuestra bio tienes recursos y guías prácticas. 🚀",
                    "¡Agradecidos por la confianza{$nameVocative}! Recuerda que en nuestro perfil compartimos herramientas formativas para seguir avanzando con método. 🎯",
                    "¡Esa es la visión{$nameVocative}! Toda la metodología y herramientas recomendadas las encuentras en el enlace de la bio. 📖✨",
                    "Nos alegra que te sirva de inspiración. En el enlace de nuestro perfil compartimos guías completas para estructurar tus objetivos diarios. 🚀"
                ];

                $supportPool = [
                    "El crecimiento sostenido se forja con disciplina y constancia diaria. Un honor contar con tu presencia en la comunidad{$nameVocative}. 🏛️💪",
                    "Agradecemos de corazón el reconocimiento{$nameVocative}. La verdadera fortaleza se demuestra cada día con hechos y enfoque innegociable. ⚡🏛️",
                    "Un saludo muy especial{$nameVocative}. Seguimos enfocados en aportar valor real, templanza y criterio a la comunidad. 🏛️🤝",
                    "La consistencia es lo que separa las intenciones de los resultados tangibles. Gracias por tu continuo respaldo{$nameVocative}. 🏛️"
                ];
            }

            return [
                'source' => 'heuristic_calibrated',
                'engagement' => $pick($engagePool),
                'conversion' => $pick($convertPool),
                'support'    => $pick($supportPool),
                'engagement_tips' => '🎉 Agradecer con reciprocidad y cercanía humana genera un fuerte lazo con la comunidad.'
            ];
        }

        // ══════════════════════════════════════════════════════════════════════
        // CASE 2: Reacciones Visuales / Stickers / GIFs / Mención sin texto
        // ══════════════════════════════════════════════════════════════════════
        if ($intent === 'visual_sticker_reaction' || str_contains($commentText, '🦚') || str_contains($commentText, '🦁') || str_contains($commentText, '🙏') || str_contains($commentText, '💯') || str_contains($textLower, '100') || (!empty($authorName) && strcasecmp(trim($commentText), trim($authorName)) === 0)) {
            $isPeacock = str_contains($commentText, '🦚');
            $isLion    = str_contains($commentText, '🦁');
            $isPray    = str_contains($commentText, '🙏');
            $isHundred = str_contains($commentText, '💯') || str_contains($commentText, '100');
            $isClap    = str_contains($commentText, '👏') || str_contains($commentText, '🎬') || str_contains($textLower, 'gif');
            $isSameAsAuthor = (!empty($authorName) && strcasecmp(trim($commentText), trim($authorName)) === 0);

            if ($isTagOnly) {
                $engagePool = [
                    "¡Muchas gracias por compartirlo con tu gente{$nameVocative}! 🙌✨",
                    "¡Qué buena onda por pasar la voz{$nameVocative}! Se aprecia mucho el apoyo. 🤝🔥",
                    "¡Gracias por etiquetar y sumar a la tribu{$nameVocative}! Vamos con todo. ⚡",
                    "¡Agradecidos al 100% de que lo compartas{$nameVocative}! Un fuerte abrazo. 🚀✨"
                ];
                $engage = $pick($engagePool);
            } elseif ($isPeacock) {
                $engage = "¡Gracias por pasar a dejar buena energía por aquí{$nameVocative}! 🦚💪";
            } elseif ($isLion) {
                $engage = "¡Esa es la actitud y la fuerza imparable{$nameVocative}! 🦁⚡ ¡Seguimos firmes!";
            } elseif ($isPray) {
                $engage = "¡Muchas gracias por la bendición y el respeto{$nameVocative}! 🤝✨ ¡Seguimos firmes!";
            } elseif ($isHundred) {
                $engage = "¡Muchas gracias por el apoyo y la buena energía{$nameVocative}! 🙌✨";
            } elseif ($isClap || $isSameAsAuthor) {
                $engage = (str_contains($textLower, 'cierto') || str_contains($textLower, 'aplauso') || str_contains($commentText, '👏'))
                    ? "¡Gracias por estar siempre compartiendo tu perspectiva por aquí{$nameVocative}! ✨"
                    : "¡Gracias por el respaldo y la buena vibra{$nameVocative}! Vamos con todo. 🎬🔥";
            } else {
                $engagePool = [
                    "¡Muchas gracias por pasar a sumar buena vibra por aquí{$nameVocative}! 🙌✨",
                    "¡Gracias por el respaldo y el apoyo constante{$nameVocative}! Vamos con todo. 🎬🔥",
                    "¡Esa es la actitud{$nameVocative}! Un fuerte abrazo y a seguir creciendo juntos. 💪✨",
                    "¡Pura buena energía{$nameVocative}! Seguimos firmes en el camino. ⚡🙌"
                ];
                $engage = $pick($engagePool);
            }

            return [
                'source' => 'heuristic_calibrated',
                'engagement' => $engage,
                'conversion' => "¡Gracias por el apoyo{$nameVocative}! Tienes recursos prácticos en el enlace del perfil para seguir sumando. 🚀",
                'support' => ($isPray || $isHundred)
                    ? "El respeto y la gratitud mutua son el cimiento de nuestra comunidad{$nameVocative}. ¡Un fuerte y fraternal abrazo! 🏛️🤝"
                    : "Agradecemos de corazón tu presencia en la comunidad{$nameVocative}. ¡Seguimos con todo! 🏛️💪",
                'engagement_tips' => '🎨 Responder con agilidad a stickers y menciones refuerza la cercanía y humanización.'
            ];
        }

        // ══════════════════════════════════════════════════════════════════════
        // CASE 2.5: Reacciones de Emojis Puros (👏, 🔥, ❤️, 💪, 🙌, 🙏, 💯)
        // ══════════════════════════════════════════════════════════════════════
        if ($intent === 'emoji_reaction' || mb_strlen(preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F700}-\x{1F77F}\x{1F780}-\x{1F7FF}\x{1F800}-\x{1F8FF}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{1FA70}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{2300}-\x{23FF}\x{2B50}\x{200D}\x{FE0F}\s\p{P}]/u', '', $commentText), 'UTF-8') < 2) {
            $isApplause = str_contains($commentText, '👏') || str_contains($commentText, '🙌');
            $isFire     = str_contains($commentText, '🔥') || str_contains($commentText, '⚡') || str_contains($commentText, '🚀');
            $isLove     = str_contains($commentText, '❤️') || str_contains($commentText, '😍') || str_contains($commentText, '🥰');
            $isStrength = str_contains($commentText, '💪') || str_contains($commentText, '🎯') || str_contains($commentText, '🏆');
            $isPray     = str_contains($commentText, '🙏');
            $isHundred  = str_contains($commentText, '💯') || str_contains($commentText, '100');

            if ($isHundred) {
                $engageHundred = [
                    "¡Muchas gracias por el apoyo y la buena energía{$nameVocative}! 🙌✨",
                    "¡Agradecidos al 100% por tu respaldo{$nameVocative}! 💯🙌",
                    "¡Esa es la actitud imparable{$nameVocative}! 💯⚡ ¡Seguimos firmes!"
                ];
                return [
                    'source' => 'heuristic_calibrated',
                    'engagement' => $pick($engageHundred),
                    'conversion' => "¡Agradecidos al 100% por tu apoyo{$nameVocative}! 💯 Tienes más recursos en el enlace del perfil.",
                    'support' => "Agradecemos de corazón tu presencia y respaldo en la comunidad{$nameVocative}. 🏛️💯",
                    'engagement_tips' => '💯 Responder con gratitud simétrica a stickers numéricos eleva la fidelidad comunitaria.'
                ];
            }

            if ($isPray) {
                $engagePray = [
                    "¡Muchas gracias por la bendición y el respeto{$nameVocative}! 🤝✨ ¡Seguimos firmes!",
                    "¡Agradecidos con tu respeto fraternal{$nameVocative}! 🙏✨ Un fuerte abrazo.",
                    "¡Muchas bendiciones para ti también{$nameVocative}! Sigamos construyendo comunidad. 🤝🙌",
                    "¡El respeto mutuo nos hace más fuertes{$nameVocative}! 🙏🏛️ Un saludo cordial.",
                    "¡Agradecidos de corazón por tu presencia{$nameVocative}! 🙏✨ ¡Seguimos con todo!",
                    "¡Gran bendición contar contigo{$nameVocative}! 🤝⚡ Sigamos firmes en el camino.",
                    "¡Paz y serenidad en tu camino{$nameVocative}! 🙏🏛️ ¡Adelante!",
                    "¡Un honor compartir estos principios contigo{$nameVocative}! 🤝✨ ¡Excelente día!"
                ];
                return [
                    'source' => 'heuristic_calibrated',
                    'engagement' => $pick($engagePray),
                    'conversion' => "Agradecidos de corazón por tu apoyo{$nameVocative}. 🙏 En el enlace de nuestro perfil encuentras más contenidos para inspirarte.",
                    'support' => "El respeto mutuo es el cimiento de nuestra comunidad{$nameVocative}. ¡Un fuerte y fraternal abrazo! 🏛️🤝",
                    'engagement_tips' => '🙏 Responder con respeto fraternal a manos unidas refuerza la lealtad comunitaria.'
                ];
            }

            if ($isApplause) {
                $engageApplause = [
                    "¡Muchas gracias por el apoyo{$nameVocative}! 👏✨ ¡Seguimos con todo!",
                    "¡Esa es la actitud{$nameVocative}! 👏⚡ ¡Vamos por más!",
                    "¡Gracias por los aplausos y la buena vibra{$nameVocative}! 🙌✨ Seguimos firmes.",
                    "¡Agradecidos por el respaldo constante{$nameVocative}! 👏🚀 ¡A romperla!",
                    "¡Pura buena energía{$nameVocative}! 🙌⚡ ¡A seguir construyendo juntos!",
                    "¡Qué alegría contar con tu presencia{$nameVocative}! 👏✨ Un abrazo enorme.",
                    "¡Así se habla{$nameVocative}! 🙌🔥 ¡Con toda la determinación!",
                    "¡Gran actitud{$nameVocative}! 👏🏛️ Firmes en el propósito.",
                    "¡Mucho aprecio por acompañarnos{$nameVocative}! 🙌✨ ¡Excelente jornada!",
                    "¡Seguimos con paso firme y constancia{$nameVocative}! 👏💪 ¡Adelante!"
                ];
                return [
                    'source' => 'heuristic_calibrated',
                    'engagement' => $pick($engageApplause),
                    'conversion' => "¡Gracias por estar presente{$nameVocative}! 👏🚀 Tienes más recursos en el enlace del perfil.",
                    'support' => "¡Un honor contar con tu presencia en la comunidad{$nameVocative}! 🏛️✨ ¡Un fuerte abrazo!",
                    'engagement_tips' => '👏 Responder rápido a comentarios de aplausos y emojis eleva la visibilidad en el algoritmo.'
                ];
            }

            if ($isFire) {
                $engageFire = [
                    "¡A tope con esa energía y determinación{$nameVocative}! 🔥⚡ ¡Vamos con todo!",
                    "¡Fuego y enfoque total{$nameVocative}! 🔥💪 ¡A no aflojar jamás!",
                    "¡Con toda la intensidad{$nameVocative}! ⚡🔥 ¡Imparables hoy!",
                    "¡Puro impulso{$nameVocative}! 🔥🚀 Esa es la energía que nos mueve.",
                    "¡Esa es la chispa que lo transforma todo{$nameVocative}! 🔥✨ ¡Seguimos firmes!",
                    "¡Determinación al máximo nivel{$nameVocative}! ⚡💪 ¡A por todas!",
                    "¡Con la llama del propósito bien encendida{$nameVocative}! 🔥🏛️ ¡Adelante!",
                    "¡Fuerza imparable para tu jornada{$nameVocative}! ⚡🔥 ¡Excelente actitud!"
                ];
                return [
                    'source' => 'heuristic_calibrated',
                    'engagement' => $pick($engageFire),
                    'conversion' => "¡Esa es la actitud imparable{$nameVocative}! 🔥🚀 En el enlace del perfil encuentras recursos para potenciar tu enfoque.",
                    'support' => "¡Fuerza e impulso para tus metas{$nameVocative}! 🔥💪 ¡Seguimos firmes!",
                    'engagement_tips' => '🔥 La reciprocidad en comentarios de alta energía impulsa el alcance de la publicación.'
                ];
            }

            if ($isLove) {
                $engageLove = [
                    "¡Mucho aprecio para ti{$nameVocative}! ❤️✨ ¡Gracias de corazón por formar parte de esta comunidad!",
                    "¡Gracias por el cariño y la calidez{$nameVocative}! ❤️🙌 Un abrazo muy especial.",
                    "¡Qué alegría contar con tu presencia tan linda{$nameVocative}! ❤️✨ ¡Seguimos sumando juntos!",
                    "¡Agradecidos con tu cariño constante{$nameVocative}! ❤️🤝 ¡Un saludo muy fraternal!",
                    "¡Pura calidez y gratitud para ti{$nameVocative}! ❤️✨ ¡Que tengas un día grandioso!"
                ];
                return [
                    'source' => 'heuristic_calibrated',
                    'engagement' => $pick($engageLove),
                    'conversion' => "¡Gracias por el cariño{$nameVocative}! ❤️🚀 Recuerda que estamos a un DM de distancia para lo que necesites.",
                    'support' => "¡Un saludo muy especial{$nameVocative}! ❤️🤝 ¡Seguimos sumando valor juntos!",
                    'engagement_tips' => '❤️ Conectar con aprecio afianza la lealtad hacia la marca.'
                ];
            }

            if ($isStrength) {
                $engageStrength = [
                    "¡Disciplina, constancia y fuerza imparable{$nameVocative}! 💪⚡ ¡Vamos por más!",
                    "¡Fuerza y carácter{$nameVocative}! 💪🔥 No hay obstáculo que nos detenga.",
                    "¡A tope con esa determinación{$nameVocative}! 👊⚡ Firmeza en el camino.",
                    "¡Temple de acero{$nameVocative}! 💪🏛️ Cada día más fuertes.",
                    "¡La constancia silenciosa vence cualquier reto{$nameVocative}! 👊✨ ¡Adelante!",
                    "¡Foco total y mente inquebrantable{$nameVocative}! 💪🎯 ¡Seguimos con todo!",
                    "¡Construyendo carácter paso a paso{$nameVocative}! 🏛️💪 ¡Gran actitud!"
                ];
                return [
                    'source' => 'heuristic_calibrated',
                    'engagement' => $pick($engageStrength),
                    'conversion' => "¡Con toda la determinación{$nameVocative}! 💪🚀 Tienes guías prácticas en el enlace de la bio.",
                    'support' => "¡Constancia y autodominio cada día{$nameVocative}! 🏛️💪 ¡Foco total en lo esencial!",
                    'engagement_tips' => '💪 Reafirmar la mentalidad y determinación refuerza la identidad de la marca.'
                ];
            }

            $engageGeneralEmoji = [
                "¡Muchas gracias por la gran vibra{$nameVocative}! 🙌✨ ¡A seguir con todo!",
                "¡Qué buena onda leerte por aquí{$nameVocative}! ✨🚀 ¡Vamos con fuerza!",
                "¡Agradecidos con tu constante presencia{$nameVocative}! 🤝✨ ¡Un saludo enorme!",
                "¡Esa es la actitud para seguir creciendo juntos{$nameVocative}! ⚡👊",
                "¡Pura buena energía{$nameVocative}! 🌟🤝 ¡Seguimos firmes!",
                "¡Mucho aprecio por sumar tu apoyo{$nameVocative}! ✨🏛️ ¡Excelente jornada!"
            ];
            return [
                'source' => 'heuristic_calibrated',
                'engagement' => $pick($engageGeneralEmoji),
                'conversion' => "¡Gracias por la buena energía{$nameVocative}! 🚀✨ Encuentra más recursos en el enlace del perfil.",
                'support' => "¡Agradecidos con tu presencia en la comunidad{$nameVocative}! 🤝✨ ¡Un saludo enorme!",
                'engagement_tips' => '✨ Responder de inmediato a emojis asegura una alta tasa de engagement.'
            ];
        }

        // ══════════════════════════════════════════════════════════════════════
        // CASE 3: Afirmaciones Cortas de Verdad (Así es, verdad, sierto, total, 100%, exacto)
        // ══════════════════════════════════════════════════════════════════════
        if ($intent === 'stoic_affirmation_short') {
            $hasBlessing = str_contains($textLower, 'bendicion') || str_contains($textLower, 'bendición');

            if ($hasBlessing) {
                $engagePool = [
                    "¡Así es{$nameVocative}! Muchas bendiciones y un fuerte abrazo para ti también. 🙌✨ ¡Seguimos firmes!",
                    "Totalmente de acuerdo{$nameVocative}. Bendiciones y adelante con toda la determinación. 🙌✨",
                    "¡Amén y así es{$nameVocative}! Gracias por la bendición y por sumar tanta buena energía a la comunidad. 🤝✨",
                    "¡Muchas bendiciones para ti y los tuyos{$nameVocative}! A seguir con paso firme. 🙏✨",
                    "¡Así es{$nameVocative}! Bendiciones y enfoque total en lo bueno. Un saludo fraternal. 🙌"
                ];
            } elseif ($isShort) {
                // Respuestas cortas contundentes de 1 sola línea (Module 1)
                $engagePool = [
                    "¡Así es{$nameVocative}! Con la verdad por delante. 👊",
                    "¡Así se habla{$nameVocative}! El mejor filtro es el tiempo. 🎯",
                    "¡Totalmente{$nameVocative}! Foco en lo que sí depende de nosotros. ⚡",
                    "¡De una{$nameVocative}! La verdad no necesita adornos. 👊",
                    "¡Exacto{$nameVocative}! Adelante con firmeza total. ⚡",
                    "Gran verdad{$nameVocative}. Gracias por acompañarnos. 👍",
                    "¡Tal cual{$nameVocative}! Mente clara y paso firme. 🏛️",
                    "¡Sin duda{$nameVocative}! Foco en lo esencial. ⚡"
                ];
            } else {
                // Respuestas con profundidad y contexto del autor del post (Module 2)
                $authorQuote = match($postAuthor) {
                    'marco_aurelio' => " Marco Aurelio lo resumió con maestría: 'Si no es correcto, no lo hagas; si no es verdad, no lo digas.'",
                    'seneca' => " Como enseñaba Séneca, la verdad es sencilla y no necesita artificios; la disciplina de vivirla es lo que forja el carácter.",
                    'dostoievski' => " Como recordaba Dostoyevski, la verdad y la lealtad se prueban en el tiempo y con los hechos, no con palabras.",
                    'epicteto' => " Como decía Epicteto, no son las opiniones externas las que mandan, sino el dominio de nuestro propio juicio.",
                    default => " La verdad no necesita adornos, solo la disciplina y el coraje de vivirla en el día a día."
                };

                $engagePool = [
                    "Totalmente de acuerdo{$nameVocative}.{$authorQuote} Un pilar innegociable. 🏛️✨",
                    "Gran verdad{$nameVocative}. Quien conquista su mente no negocia su tranquilidad con nadie. Foco en lo esencial. ⚡",
                    "Sin duda{$nameVocative}. Mantenerse fiel a los principios cuando las circunstancias aprietan es la mayor victoria personal. 🏛️",
                    "Exactamente{$nameVocative}. La claridad mental nace de aceptar las cosas tal como son y actuar con determinación. 🤝✨",
                    "Una perspectiva impecable{$nameVocative}. Da gusto compartir estos principios con personas que aprecian el valor de la templanza. ⚡🏛️"
                ];
            }

            $convertPool = [
                "Exacto{$nameVocative}. En el enlace de nuestra biografía compartimos lecturas y herramientas para seguir forjando esa mentalidad. 📖",
                "Totalmente. Si buscas herramientas prácticas de disciplina y enfoque, encuéntralas en el enlace del perfil. 🎯",
                "Es así{$nameVocative}. La teoría sin acción no transforma vidas; en el enlace de la bio tienes guías aplicadas. 🚀",
                "Para seguir profundizando en principios de mentalidad y carácter, te invitamos a explorar los recursos en nuestro perfil. 📖✨"
            ];

            $supportPool = [
                "Marco Aurelio lo resumió con maestría: 'Si no es correcto, no lo hagas; si no es verdad, no lo digas.' Un pilar innegociable. 🏛️",
                "La serenidad nace de aceptar la realidad y enfocarnos en nuestras decisiones. ¡Foco total en lo esencial{$nameVocative}! 💪",
                "Una gran verdad que distingue a quienes construyen templanza en su día a día. ¡Agradecidos por tu presencia! 🏛️",
                "El autodominio se forja reafirmando la verdad en cada pequeña decisión cotidiana. Firmeza en tu camino{$nameVocative}. ⚡🏛️"
            ];

            return [
                'source' => 'heuristic_calibrated',
                'engagement' => $pick($engagePool),
                'conversion' => $pick($convertPool),
                'support'    => $pick($supportPool),
                'engagement_tips' => '⚡ Respuestas concisas y firmes a afirmaciones cortas refuerzan la autenticidad.'
            ];
        }

        // ══════════════════════════════════════════════════════════════════════
        // CASE 3.1: Batalla Interna & Autodominio (El único rival, vencerse a uno mismo)
        // ══════════════════════════════════════════════════════════════════════
        if ($intent === 'stoic_inner_battle') {
            if ($isShort) {
                $engagePool = [
                    "¡Totalmente{$nameVocative}! La batalla más grande es con uno mismo. ⚡",
                    "¡Así es{$nameVocative}! Vencerse a uno mismo cada día. 💪🔥",
                    "¡De una{$nameVocative}! Autodominio puro. 🏛️👊",
                    "¡Esa es la clave{$nameVocative}! Dominar la mente ante todo. ⚡",
                    "¡Firmeza total{$nameVocative}! El único rival es el de ayer. 👊🔥"
                ];
            } else {
                $authorQuote = match($postAuthor) {
                    'seneca' => " Como enseñaba Séneca: 'La mayor de las victorias es conquistarse a uno mismo.'",
                    'marco_aurelio' => " Como recordaba Marco Aurelio, tienes poder sobre tu mente, no sobre los acontecimientos externos.",
                    'epicteto' => " Como decía Epicteto, nadie es libre si no es dueño de sí mismo.",
                    default => " Vencer las propias excusas es la única batalla que realmente transforma la vida."
                };

                $engagePool = [
                    "Totalmente de acuerdo{$nameVocative}. Vencerse a uno mismo es la batalla más dura, pero la única que realmente importa. 🤝✨",
                    "Exactamente{$nameVocative}. El verdadero dominio no consiste en controlar el exterior, sino en conquistarse a uno mismo cada día. ⚡🏛️",
                    "Así es{$nameVocative}. Quien vence sus propias excusas y temores se vuelve imbatible. ¡Seguimos firmes forjando carácter! 💪🔥",
                    "Una gran reflexión{$nameVocative}.{$authorQuote} Conquistar la propia voluntad es el cimiento de la tranquilidad. 🏛️⚡",
                    "Sin duda{$nameVocative}. La competencia no es con los demás, sino con la versión de nosotros que busca el camino fácil. ¡Vamos con todo! 👊✨"
                ];
            }

            $convertPool = [
                "¡Tal cual{$nameVocative}! Dominar la mente requiere método y constancia; en el enlace de nuestra biografía compartimos herramientas de autodominio. 🚀",
                "Exacto. La verdadera victoria empieza adentro. En el link del perfil tienes metodologías y guías para templar tu disciplina diaria. 🎯",
                "Para acompañar esa batalla interna con métodos claros de enfoque y hábitos, revisa los recursos formativos en nuestro perfil. 📖✨"
            ];

            $supportPool = [
                "Como enseñaba Séneca: 'La mayor de las victorias es conquistarse a uno mismo.' Una reflexión impecable{$nameVocative}. 🏛️",
                "Quien no se deja vencer por su propia mente, jamás podrá ser derrotado por circunstancias externas. Un gran honor leerte{$nameVocative}. ⚡🏛️",
                "El autodominio cotidiano es la piedra angular del carácter estoico. Foco constante en vencer las excusas{$nameVocative}. 🏛️"
            ];

            return [
                'source' => 'heuristic_calibrated',
                'engagement' => $pick($engagePool),
                'conversion' => $pick($convertPool),
                'support'    => $pick($supportPool),
                'engagement_tips' => '🏛️ Profundizar en la batalla interna conecta profundamente con la filosofía de marca.'
            ];
        }

        // ══════════════════════════════════════════════════════════════════════
        // CASE 3.2: Referencias Espirituales, Fe & Citas Bíblicas (Filipenses, Cristo, Dios)
        // ══════════════════════════════════════════════════════════════════════
        if ($intent === 'spiritual_biblical_faith') {
            if ($isShort) {
                $engagePool = [
                    "Amén{$nameVocative}. Mucha fortaleza y bendiciones. 🙌",
                    "Amén{$nameVocative}. Fe, convicción y constancia. 🙏✨",
                    "Totalmente{$nameVocative}. Fuerza espiritual que impulsa. 🙌",
                    "¡Amén{$nameVocative}! Adelante con el corazón firme. 🙏",
                    "Bendiciones{$nameVocative}. Que esa convicción guíe tu camino. 🙌✨"
                ];
            } else {
                $engagePool = [
                    "Amén{$nameVocative}. Una gran fuente de fortaleza espiritual que complementa la disciplina mental. Muchas gracias por compartirlo. 🙌",
                    "Amén{$nameVocative}. Gran cita de fe y fortaleza; la convicción interior unida a la disciplina diaria forja un espíritu inquebrantable. Muchas gracias por tu aporte. 🙏✨",
                    "Totalmente de acuerdo{$nameVocative}. La fe y la constancia son pilares que sostienen el carácter ante cualquier adversidad. 🙌✨",
                    "Apreciamos mucho que compartas este mensaje de fe y esperanza{$nameVocative}. La fortaleza espiritual y la rectitud moral van de la mano. 🙏🏛️",
                    "Palabras llenas de inspiración{$nameVocative}. Cuando los valores trascienden lo material, cualquier obstáculo se afronta con serenidad. 🙌✨"
                ];
            }

            $convertPool = [
                "Gran cita de fe y fortaleza{$nameVocative}. En el enlace de nuestro perfil compartimos recursos diarios para forjar el carácter y los hábitos. 🙏✨",
                "Una fuente inagotable de fortaleza interior{$nameVocative}. Agradecidos de tener tu voz y perspectiva en la comunidad. 🎯",
                "La convicción unida a los buenos hábitos transforma cualquier propósito. Encuentra guías prácticas en el enlace de nuestra biografía. 🚀"
            ];

            $supportPool = [
                "La fortaleza espiritual y la templanza en las acciones caminan siempre juntas. Agradecemos mucho tu valioso aporte{$nameVocative}. 🏛️🙌",
                "Cuando la convicción espiritual guía nuestras decisiones, no hay obstáculo que derrumbe el propósito. ¡Un saludo fraternal{$nameVocative}! 🏛️",
                "Vivir con rectitud, gratitud y constancia es la mejor manifestación de fe. Gracias por enriquecer a la comunidad{$nameVocative}. 🙏🏛️"
            ];

            return [
                'source' => 'heuristic_calibrated',
                'engagement' => $pick($engagePool),
                'conversion' => $pick($convertPool),
                'support'    => $pick($supportPool),
                'engagement_tips' => '🙏 Validar con respeto fraternal y sincretismo refuerza una comunidad respetuosa y sólida.'
            ];
        }

        // ══════════════════════════════════════════════════════════════════════
        // CASE 3.3: Compromiso Personal & Proceso Activo (Trabajando en eso, un día a la vez)
        // ══════════════════════════════════════════════════════════════════════
        if ($intent === 'personal_growth_process') {
            if ($isShort) {
                $engagePool = [
                    "¡Ese es el espíritu{$nameVocative}! Un día a la vez. Dale con todo. 💪🔥",
                    "¡Paso a paso{$nameVocative}! Firmeza total en el camino. ⚡💪",
                    "¡Constancia pura{$nameVocative}! A no aflojar jamás. 👊🔥",
                    "¡Esa es la actitud{$nameVocative}! Pequeñas victorias diarias. ⚡",
                    "¡Firmeza total{$nameVocative}! Cada día cuenta. 💪✨",
                    "¡De una{$nameVocative}! Paso a paso construyendo carácter. 🏛️💪"
                ];
            } else {
                $authorPhrase = match($postAuthor) {
                    'marco_aurelio' => ' Como recordaba Marco Aurelio, la disciplina diaria forja el carácter.',
                    'seneca' => ' Como enseñaba Séneca, cada paso firme forja el espíritu.',
                    'dostoievski' => ' Como escribía Dostoyevski, el verdadero carácter se prueba en los hechos cotidianos.',
                    'epicteto' => ' Como decía Epicteto, la voluntad inquebrantable es tu mejor escudo.',
                    default => ' La victoria diaria sobre la pereza es la que edifica el destino.'
                };

                $engagePool = [
                    "¡Ese es el espíritu{$nameVocative}! Un día a la vez construyendo esa fortaleza. Dale con todo. 💪🔥",
                    "Paso a paso y sin aflojar{$nameVocative}. Cada día que elijes la disciplina estás forjando tu mejor versión. ¡Adelante! ⚡💪",
                    "¡Constancia pura{$nameVocative}! El proceso no es fácil, pero la recompensa de no rendirse es innegociable. ¡Vamos con todo! 👊🔥",
                    "Paso a paso forjando esa fortaleza{$nameVocative}.{$authorPhrase} ¡Adelante con determinación! 🏛️💪",
                    "El verdadero cambio no es un evento aislado, sino el compromiso que renuevas cada mañana. ¡Seguimos firmes{$nameVocative}! ⚡✨",
                    "Esa determinación silenciosa es la que marca la diferencia a largo plazo{$nameVocative}. Un gusto enorme tenerte en la comunidad. 👊🔥"
                ];
            }

            $convertPool = [
                "¡Gran compromiso{$nameVocative}! Recuerda que en el enlace de la bio tienes recursos y guías de hábitos para acompañar tu proceso diario. 🎯🚀",
                "¡Esa es la actitud! Para acompañar ese proceso diario, en el enlace del perfil tienes herramientas estructuradas de mentalidad. 📖✨",
                "El progreso continuo marca la diferencia. En el enlace de nuestra biografía compartimos guías prácticas para optimizar tus hábitos. 🚀",
                "Para estructurar ese avance con un método probado de enfoque, consulta los materiales formativos en nuestro perfil. 🎯"
            ];

            $supportPool = [
                "La victoria no se logra de golpe, sino en cada pequeña decisión diaria. Seguimos firmes en el camino{$nameVocative}. ⚡🏛️",
                "El hábito diario es el único constructor infalible del carácter. ¡Un saludo con toda la determinación{$nameVocative}! 🏛️💪",
                "La constancia vence al talento cuando el talento no es constante. Firmeza en tu camino{$nameVocative}. 🏛️",
                "Cada pequeña decisión cotidiana moldea nuestro destino. Foco innegociable en mantener el estándar diario{$nameVocative}. 🏛️"
            ];

            return [
                'source' => 'heuristic_calibrated',
                'engagement' => $pick($engagePool),
                'conversion' => $pick($convertPool),
                'support'    => $pick($supportPool),
                'engagement_tips' => '💪 Fomentar el compromiso paso a paso incrementa la fidelidad de la audiencia.'
            ];
        }

        // ══════════════════════════════════════════════════════════════════════
        // CASE 4: Despertar de Consciencia / Choque de Realidad
        // ══════════════════════════════════════════════════════════════════════
        if ($intent === 'stoic_awakening_impact') {
            $isPunchyBrutal = $isShort || (str_contains($textLower, 'brutal') && $charCount <= 35) || (str_contains($commentText, '🔥') && str_contains($commentText, '💪') && $charCount <= 35);

            if ($isPunchyBrutal) {
                // Comentarios cortos de impacto ("Brutal 🔥💪") -> 1 línea contundente (Module 1)
                $engagePool = [
                    "¡Así se habla{$nameVocative}! Con toda la fuerza e intensidad para superar cualquier obstáculo. 🔥👊",
                    "¡Con toda la determinación{$nameVocative}! Sin filtros y con enfoque total. 🔥💪",
                    "¡A tope{$nameVocative}! Esa es la actitud para no detenerse ante nada. ⚡🔥",
                    "¡Dale con todo{$nameVocative}! Mentalidad inquebrantable hoy y siempre. 👊⚡",
                    "¡Totalmente{$nameVocative}! Sacudiendo la mente para avanzar al siguiente nivel. 🔥🚀",
                    "¡Qué energía{$nameVocative}! Foco y determinación pura. ⚡💪",
                    "¡De una{$nameVocative}! Sin rodeos y con la mirada en la meta. 🔥👊"
                ];
            } else {
                // Comentarios con mayor desarrollo reflexivo (Module 2 contextual)
                $authorQuote = match($postAuthor) {
                    'seneca' => " Como enseñaba Séneca: 'No nos atrevemos a muchas cosas porque son difíciles, pero son difíciles porque no nos atrevemos.'",
                    'marco_aurelio' => " Como recordaba Marco Aurelio: 'El impedimento a la acción avanza la acción. Lo que se interpone en el camino se convierte en el camino.'",
                    'dostoievski' => " Como escribía Dostoyevski, el hombre es capaz de acostumbrarse a todo, pero la lucidez comienza cuando decide no engañarse.",
                    'epicteto' => " Como decía Epicteto: '¿Cuánto tiempo vas a esperar antes de exigir lo mejor de ti mismo?'",
                    default => " Cuando una verdad incomoda y cala hondo, es señal de que hay un carácter listo para evolucionar."
                };

                $engagePool = [
                    "De eso se trata{$nameVocative}, de sacudir un poco la perspectiva. Gracias a ti por darte el tiempo de reflexionar con nosotros. ✨",
                    "Las lecciones que más transforman casi nunca vienen con palabras suaves.{$authorQuote} Gracias por reflexionar con nosotros{$nameVocative}. 🏛️",
                    "Esa 'bofetada' constructiva de realidad es la que nos despierta. El dolor de la verdad es temporal; el precio de vivir engañado es permanente. ¡Seguimos firmes{$nameVocative}! ⚡",
                    "De eso se trata la verdadera filosofía práctica: de incomodarnos para no estancarnos. ¡Fuerza imparable{$nameVocative}! 🏛️",
                    "Cuando un mensaje te sacude de esa forma, es porque tu mente ya estaba buscando esa claridad. ¡A usar esa energía para actuar{$nameVocative}! ⚡🏛️",
                    "Ese impacto mental es el verdadero punto de inflexión. Gracias por sumar tu reflexión a la comunidad{$nameVocative}. 🏛️✨"
                ];
            }

            $convertPool = [
                "Ese clic mental es el punto de partida hacia el autodominio. Si esta perspectiva resonó contigo, en el enlace de nuestra biografía compartimos guías para profundizar en la mentalidad estoica aplicada. 📖",
                "Transformar una reflexión en un cambio duradero requiere método y disciplina cotidiana. Puedes explorar nuestras herramientas formativas en el enlace del perfil. 🎯",
                "La lucidez llega en el momento exacto en que dejamos de justificarnos. Tienes recursos recomendados en el enlace de la bio. 🚀",
                "Ese golpe de realidad se convierte en crecimiento cuando hay un plan diario. Encuentra herramientas y guías en nuestro enlace del perfil. 📖✨"
            ];

            $supportPool = [
                "Como enseñaba Séneca: 'No nos atrevemos a muchas cosas porque son difíciles, pero son difíciles porque no nos atrevemos.' La reflexión honesta es el primer paso hacia la templanza. 🏛️",
                "El valor de los principios estoicos no es adular el ego, sino afilar la mente y templar el espíritu para cualquier adversidad. Un honor contar con aportes tan valiosos en esta comunidad. 🏛️",
                "La incomodidad de la verdad es el fertilizante del carácter. Quien despierta a tiempo toma el control de su vida. Un gran saludo{$nameVocative}. ⚡🏛️"
            ];

            return [
                'source' => 'heuristic_calibrated',
                'engagement' => $pick($engagePool),
                'conversion' => $pick($convertPool),
                'support'    => $pick($supportPool),
                'engagement_tips' => '🏛️ Validar el despertar de consciencia con serenidad afianza la autoridad de la marca.'
            ];
        }

        // ══════════════════════════════════════════════════════════════════════
        // CASE 5: Consultas Comerciales / Precio / Acceso / Lead
        // ══════════════════════════════════════════════════════════════════════
        if ($intent === 'lead_info') {
            $isCourseStructure = str_contains($textLower, 'clases grabadas') || str_contains($textLower, 'grabada') || str_contains($textLower, 'tiempo de acceso') || str_contains($textLower, 'temario');

            if ($isCourseStructure) {
                $engageCourse = [
                    $helloName . "Sí, el programa incluye acceso flexible a clases grabadas y materiales prácticos para avanzar a tu propio ritmo. ¿Te gustaría conocer el temario completo? 💬",
                    $helloName . "Efectivamente, cuentas con acceso continuo a las grabaciones y ejercicios prácticos desde cualquier dispositivo. ¿Qué temática te interesa más? 🎯"
                ];
                $convertCourse = [
                    $helloName . "Cuentas con acceso continuo a las clases grabadas y recursos prácticos. Puedes consultar el temario y registrarte directamente en el enlace de nuestra bio o escribirnos al DM. 🚀",
                    "El acceso es 100% flexible con materiales descargables y soporte. Revisa las opciones de inscripción en el enlace de la bio o envíanos un DM. 📖✨"
                ];
                return [
                    'source' => 'heuristic_calibrated',
                    'engagement' => $pick($engageCourse),
                    'conversion' => $pick($convertCourse),
                    'support' => "El contenido está estructurado en módulos grabados de alta calidad para repasar a tu ritmo. Encuentras la información oficial en el enlace de nuestro perfil. 🏛️",
                    'engagement_tips' => '🎯 Responder directamente sobre la estructura genera confianza y acelera la decisión de compra.'
                ];
            }

            $engageLead = [
                $helloName . "Con gusto te compartimos los detalles de precios y opciones directamente por mensaje privado (DM) para orientarte según lo que buscas. 💬",
                $helloName . "¡Hola! Todos los planes y detalles de inversión están disponibles en el perfil, o puedes escribirnos al DM y te asesoramos con gusto. 🎯",
                "Con mucho gusto te orientamos{$nameVocative}. Escríbenos por DM para brindarte atención personalizada según tus objetivos. 💬✨"
            ];
            $convertLead = [
                "Puedes ver todos los planes, temarios y precios directamente en el enlace de nuestra biografía o enviarnos un DM y te guiamos paso a paso. 🚀",
                "En el enlace de nuestro perfil tienes la información oficial completa con facilidades y accesos. ¡Te esperamos dentro! 🎯",
                "Escríbenos un mensaje privado (DM) indicándonos lo que buscas y te compartimos el enlace directo con beneficios activos. 📩🚀"
            ];

            return [
                'source' => 'heuristic_calibrated',
                'engagement' => $pick($engageLead),
                'conversion' => $pick($convertLead),
                'support' => "Toda la información de inversión, metodología y opciones disponibles está detallada en el enlace de nuestro perfil. Si deseas una recomendación puntual, déjanos un mensaje privado. 🏛️",
                'engagement_tips' => '🎯 Responder con claridad e invitar al canal oficial eleva la conversión sin crear fricción.'
            ];
        }

        // ══════════════════════════════════════════════════════════════════════
        // CASE 6: Objeciones de Venta / Garantías / Dudas de Compra
        // ══════════════════════════════════════════════════════════════════════
        if ($intent === 'sales_objection') {
            $objEngage = [
                "Es totalmente comprensible tu consulta{$nameVocative}. Todo nuestro trabajo cuenta con garantía de satisfacción y soporte dedicado para tu total tranquilidad. 🤝",
                "Comprendemos perfectamente tu duda{$nameVocative}. Respaldamos cada proceso con garantía comprobada y acompañamiento continuo. 🛡️✨",
                "Tu tranquilidad es lo primero{$nameVocative}. Puedes comprobar testimonios reales y políticas claras de garantía en nuestro perfil oficial. 🤝"
            ];
            $objConvert = [
                "Respaldamos cada programa con políticas claras de garantía y atención personalizada. Además, puedes revisar testimonios verificados en nuestras historias destacadas y en el enlace de la bio. 🎯",
                "La transparencia es nuestro compromiso. En el enlace de nuestra bio encuentras testimonios en video y todas las garantías detalladas. 🚀",
                "Ofrecemos garantía de satisfacción para que tomes tu decisión con absoluta certeza. Encuentra los detalles en el enlace del perfil. 📖"
            ];
            $objSupport = [
                "Tu seguridad y satisfacción son prioridad. Puedes revisar los términos de satisfacción en el enlace del perfil o escribirnos por DM para resolver dudas específicas. 🏛️",
                "Operamos con total transparencia en cada término y condición. Si requieres asistencia puntual, nuestro equipo está a un mensaje directo de distancia. 🏛️"
            ];

            return [
                'source' => 'heuristic_calibrated',
                'engagement' => $pick($objEngage),
                'conversion' => $pick($objConvert),
                'support'    => $pick($objSupport),
                'engagement_tips' => '🛡️ Atender dudas con transparencia disipa la fricción de compra.'
            ];
        }

        // ══════════════════════════════════════════════════════════════════════
        // CASE 7: Soporte Técnico / Acceso / Pedidos
        // ══════════════════════════════════════════════════════════════════════
        if ($intent === 'customer_support') {
            $suppEngage = [
                "Lamentamos cualquier inconveniente{$nameVocative}. Por favor envíanos un mensaje directo (DM) con tu correo registrado para que nuestro equipo lo revise de forma prioritaria ya mismo. 🛠️",
                "Queremos ayudarte de inmediato{$nameVocative}. Déjanos un mensaje privado con los datos de tu cuenta para resolverlo a la brevedad. 🤝",
                "Tu atención es lo más importante. Escríbenos por DM con tu correo de usuario y le damos seguimiento prioritario. 🛠️✨"
            ];
            $suppConvert = [
                "Queremos ayudarte de inmediato{$nameVocative}. Por favor escríbenos por DM indicándonos tu correo de registro para asistirte hoy mismo. 📩",
                "Por favor contáctanos por mensaje directo con tu número de pedido o correo para agilizar la solución hoy mismo. 📩"
            ];
            $suppSupport = [
                "Tu atención es prioridad. Nuestro equipo de asistencia ya está disponible: por favor contáctanos por mensaje directo para verificar tu acceso o caso hoy mismo. 🤝",
                "Cuentas con nuestro respaldo técnico completo. Escríbenos al DM para validar y solventar cualquier incidencia de inmediato. 🛠️"
            ];

            return [
                'source' => 'heuristic_calibrated',
                'engagement' => $pick($suppEngage),
                'conversion' => $pick($suppConvert),
                'support'    => $pick($suppSupport),
                'engagement_tips' => '🛠️ Una atención empática y ágil transforma una incidencia en fidelización.'
            ];
        }

        // ══════════════════════════════════════════════════════════════════════
        // CASE 8: Preguntas Conceptuales & Filosofía Práctica
        // ══════════════════════════════════════════════════════════════════════
        if ($intent === 'knowledge_concept') {
            $isDichotomy  = str_contains($textLower, 'dicotomia') || str_contains($textLower, 'dicotomía') || str_contains($textLower, 'control');
            $isDiscipline = str_contains($textLower, 'disciplina') || str_contains($textLower, 'motivacion') || str_contains($textLower, 'motivación') || str_contains($textLower, 'procrastin');

            if ($isDichotomy) {
                $dichoEngage = [
                    "La dicotomía del control consiste en enfocar el 100% de tu energía en lo que sí depende de ti (tus decisiones, acciones y actitud) y aceptar con serenidad lo externo. ¿En qué situación buscas aplicarlo hoy? 💬",
                    "Separar lo que depende de nosotros de lo que escapa a nuestro control es la base de la tranquilidad mental estoica. Enfócate solo en tu respuesta interna. 🏛️✨",
                    "Epicteto enseñaba que la infelicidad nace de intentar controlar lo incontrolable. Cuando aceptas lo externo y dominas tus acciones, nada puede perturbarte. 🧠🏛️"
                ];
                return [
                    'source' => 'heuristic_calibrated',
                    'engagement' => $pick($dichoEngage),
                    'conversion' => "Dominar la dicotomía del control transforma por completo tu enfoque y claridad mental. En el enlace de nuestra biografía compartimos guías y recursos prácticos sobre mentalidad estoica aplicada. 🚀",
                    'support' => "Ante cualquier obstáculo pregúntate: '¿Esto depende de mí?'. Si depende de ti, actúa con determinación; si no, canaliza tu energía en tu propia respuesta y suelta lo demás. 🏛️",
                    'engagement_tips' => '🧠 Respuestas claras sobre principios clave consolidan a tu marca como referente.'
                ];
            }

            if ($isDiscipline) {
                $discEngage = [
                    "La motivación es pasajera, pero la disciplina diaria se forja con pequeñas victorias cotidianas. No busques perfección inmediata, sino constancia innegociable. 💪✨",
                    "La verdadera disciplina es hacer lo necesario incluso cuando no tienes ganas. Cada pequeño esfuerzo sostenido edifica un carácter inquebrantable. ⚡💪",
                    "El secreto de la disciplina no es la fuerza bruta de voluntad, sino crear sistemas y hábitos que eliminen las fricciones cotidianas. ¡Firmeza total{$nameVocative}! 🎯"
                ];
                return [
                    'source' => 'heuristic_calibrated',
                    'engagement' => $pick($discEngage),
                    'conversion' => "Cuando aplicas un método estructurado, la disciplina se vuelve un hábito natural. Puedes consultar nuestras herramientas y metodologías en el enlace de la bio para dar el siguiente paso. 🎯",
                    'support' => "La clave para vencer la procrastinación es dividir el objetivo en una micro-tarea que puedas empezar de inmediato. La acción continuada disuelve la resistencia. 🏛️",
                    'engagement_tips' => '💡 Aportar consejos prácticos y accionables fomenta conversaciones de alto engagement.'
                ];
            }

            $conceptPool = [
                "Tener claridad en estos fundamentos marca el camino hacia el autodominio. Gracias por enriquecer la conversación en la comunidad{$nameVocative}. 🏛️✨",
                "Quien domina sus pensamientos y sus reacciones, domina su destino. La práctica cotidiana de la virtud es el mayor refugio ante la incertidumbre. 🤝",
                "Los principios sólidos nos permiten mantener el rumbo sin importar las circunstancias externas. Un gusto reflexionar juntos en comunidad{$nameVocative}. ⚡",
                "Comprender la raíz de nuestras elecciones es el primer paso hacia una vida con templanza y serenidad. Gran reflexión{$nameVocative}. 🏛️",
                "Cuando la mente se apoya en fundamentos claros, el ruido exterior pierde toda fuerza. Seguimos compartiendo valor con personas de tu criterio. ⚡✨"
            ];
            $conceptConvert = [
                "Profundizar en estos fundamentos marca la diferencia. Te invitamos a revisar los recursos formativos en el enlace de nuestra biografía. 📖",
                "En el enlace de nuestro perfil encuentras metodologías estructuradas para aplicar estos principios en tu día a día. 🎯",
                "Llevar la teoría filosófica a la acción diaria es nuestro principal objetivo. Encuentra más herramientas en el perfil. 🚀"
            ];
            $conceptSupport = [
                "La claridad mental surge de la práctica constante y el pensamiento reflexivo. Con gusto seguimos compartiendo contenidos sobre este tema. 🏛️",
                "La filosofía práctica tiene sentido únicamente cuando se transforma en conducta y templanza cotidiana. Un saludo muy especial{$nameVocative}. 🏛️🤝",
                "Seguimos dedicados a difundir principios que forjen carácter y serenidad ante la incertidumbre. 🏛️✨"
            ];

            return [
                'source' => 'heuristic_calibrated',
                'engagement' => $pick($conceptPool),
                'conversion' => $pick($conceptConvert),
                'support'    => $pick($conceptSupport),
                'engagement_tips' => '🏛️ El contenido de valor y reflexión genera seguidores altamente fidelizados.'
            ];
        }

        // ══════════════════════════════════════════════════════════════════════
        // CASE 9: General Fallback Proporcional (Module 1: SHORT vs MEDIUM/LONG)
        // ══════════════════════════════════════════════════════════════════════
        if ($isShort) {
            $generalPool = [
                "¡Gracias por estar presente{$nameVocative}! Un gran saludo. 🤝✨",
                "¡Un gusto leerte{$nameVocative}! Seguimos firmes sumando valor juntos. 🙌",
                "¡Mucho aprecio por el apoyo constante{$nameVocative}! Adelante con todo. ⚡",
                "¡Qué buena onda leerte{$nameVocative}! ¡Vamos por más! 👊✨",
                "¡Agradecidos con tu presencia{$nameVocative}! Un fuerte abrazo. 🤝🚀",
                "¡Así se habla{$nameVocative}! Saludos con toda la energía. ⚡"
            ];
            $generalConvert = [
                "¡Gracias por acompañarnos! En el enlace del perfil encuentras más contenido y recursos formativos. 🚀",
                "¡Agradecidos con tu presencia! Tienes recursos prácticos en la bio para seguir avanzando. 🎯",
                "Encuentra más herramientas y guías exclusivas en el link de nuestra biografía. 📖✨"
            ];
            $generalSupport = [
                "Agradecemos tu presencia en la comunidad{$nameVocative}. ¡Un fuerte abrazo! 🏛️",
                "Un honor contar con tu participación. Seguimos firmes aportando valor cada día. 🏛️✨",
                "La constancia de nuestra comunidad es lo que nos impulsa. ¡Un saludo fraternal{$nameVocative}! 🏛️🤝"
            ];
            return [
                'source' => 'heuristic_calibrated',
                'engagement' => $pick($generalPool),
                'conversion' => $pick($generalConvert),
                'support'    => $pick($generalSupport),
                'engagement_tips' => '💬 Las respuestas breves y naturales mantienen la cercanía.'
            ];
        }

        $generalMediumPool = [
            "Una perspectiva muy interesante{$nameVocative}. Gracias por dejar tu reflexión y sumar valor a la conversación. 🎯",
            "¡Totalmente de acuerdo{$nameVocative}! Gracias por compartir tu punto de vista con la comunidad. 🤝✨",
            "Un punto de vista muy valioso{$nameVocative}. Da gusto contar con aportes reflexivos en esta comunidad. 🙌",
            "¡Muchas gracias por sumar tu voz a la conversación{$nameVocative}! Seguimos firmes creando contenido de valor. ⚡",
            "Apreciamos mucho que dediques tiempo a interactuar y reflexionar con nosotros{$nameVocative}. ¡Seguimos adelante! 🏛️✨",
            "Comentarios enriquecedores como el tuyo le dan un sentido mucho mayor a esta comunidad{$nameVocative}. ¡Vamos por más! 👊🔥"
        ];
        $generalMediumConvert = [
            "¡Totalmente! Si deseas profundizar en estos enfoques y herramientas, en el enlace de nuestra biografía tienes más información. 🚀",
            "Para continuar desarrollando estos conceptos con metodologías prácticas, visita los recursos en el perfil. 🎯",
            "En el enlace de nuestra bio compartimos guías estructuradas para seguir cultivando esta mentalidad día con día. 📖✨"
        ];
        $generalMediumSupport = [
            "¡Un gran saludo{$nameVocative}! Encantados de leerte y tener tu participación reflexiva en nuestra comunidad. 🏛️",
            "La claridad de criterio se construye compartiendo y debatiendo ideas sólidas. Gracias por tu aporte{$nameVocative}. 🏛️✨",
            "Seguimos firmes compartiendo principios que fortalezcan el criterio y la templanza en el día a día. 🏛️🤝"
        ];

        return [
            'source' => 'heuristic_calibrated',
            'engagement' => $pick($generalMediumPool),
            'conversion' => $pick($generalMediumConvert),
            'support'    => $pick($generalMediumSupport),
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
     * Fetch recent replies posted in the same post thread for deduplication (Module 5 Deep)
     */
    public static function fetchRecentPostReplies(PDO $pdo, int $postId, int $userId = 0, int $limit = 15): array {
        if ($postId <= 0) return [];
        try {
            $sql = "
                SELECT r.reply_text 
                FROM replies r
                JOIN comments c ON r.comment_id = c.id
                WHERE c.post_id = :post_id " . ($userId > 0 ? "AND r.user_id = :uid " : "") . "
                ORDER BY r.id DESC
                LIMIT :limit
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':post_id', $postId, PDO::PARAM_INT);
            if ($userId > 0) {
                $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Check if candidate reply is too similar to any recent reply in the post thread (Module 5 Deep)
     */
    public static function isTooSimilarToRecent(string $candidate, array $recentReplies, float $threshold = 0.65): bool {
        if (empty($recentReplies)) return false;

        $candClean = mb_strtolower(trim(preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F700}-\x{1F77F}\x{1F780}-\x{1F7FF}\x{1F800}-\x{1F8FF}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{1FA70}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{2300}-\x{23FF}\x{2B50}\x{200D}\x{FE0F}\p{P}\s]+/u', ' ', $candidate)), 'UTF-8');

        foreach ($recentReplies as $recent) {
            if (!is_string($recent) || empty(trim($recent))) continue;
            $recClean = mb_strtolower(trim(preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F700}-\x{1F77F}\x{1F780}-\x{1F7FF}\x{1F800}-\x{1F8FF}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{1FA70}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{2300}-\x{23FF}\x{2B50}\x{200D}\x{FE0F}\p{P}\s]+/u', ' ', $recent)), 'UTF-8');

            if ($candClean === $recClean) {
                return true;
            }

            similar_text($candClean, $recClean, $percent);
            if (($percent / 100.0) >= $threshold) {
                return true;
            }
        }
        return false;
    }

    /**
     * Synthesize a unique 3-block combinatorial reply (Greeting + Value Core + Closing/CTA)
     * Provides over 800 non-repetitive variations for viral posts with identical follower comments
     */
    public static function generateModularCombinatorialReply(
        string $authorName,
        string $postCaption,
        string $postAuthor = 'general',
        int $warmthLevel = 85,
        int $energyLevel = 80,
        int $depthLevel = 75,
        string $emojiStyle = 'moderate',
        int $seed = 0,
        array $recentReplies = []
    ): string {
        $displayName = self::extractCleanFirstName($authorName);
        $nameVocative = (!empty($displayName) && $warmthLevel >= 40) ? " $displayName" : '';

        // Block A: Saludo / Reconocimiento (10 variantes)
        if ($warmthLevel <= 35) {
            $blockA = [
                "Agradecemos su participación.",
                "Muchas gracias por su comentario.",
                "Un saludo cordial.",
                "Totalmente de acuerdo.",
                "Agradecemos su presencia en la comunidad.",
                "Muchas gracias por acompañarnos.",
                "Apreciamos su perspectiva.",
                "Un cordial saludo.",
                "Gracias por participar.",
                "Estimamos su valioso comentario."
            ];
        } else {
            $blockA = [
                "¡Qué alegría leerte{$nameVocative}!",
                "¡Totalmente de acuerdo{$nameVocative}!",
                "¡Así es{$nameVocative}!",
                "¡Excelente{$nameVocative}!",
                "¡Mucho aprecio{$nameVocative}!",
                "¡Gran verdad{$nameVocative}!",
                "¡Exacto{$nameVocative}!",
                "¡Un gran saludo{$nameVocative}!",
                "¡Me encanta tu energía{$nameVocative}!",
                "¡Gracias por estar presente{$nameVocative}!"
            ];
        }

        // Block B: Núcleo de Valor / Afirmación (10 variantes contextualizadas por autor)
        if ($postAuthor === 'seneca') {
            $blockB = [
                "El tiempo bien invertido es nuestra mayor riqueza.",
                "La serenidad interior no se negocia con nadie.",
                "Priorizar lo esencial es el verdadero secreto de la paz mental.",
                "Aprovechar el presente con sabiduría lo cambia todo.",
                "Vivir con serenidad disuelve cualquier afán externo.",
                "Menos ruido y más presencia en cada momento.",
                "El dominio del propio tiempo es la verdadera libertad.",
                "Cuidar la mente es cuidar la propia vida.",
                "Quien sabe lo que vale su tiempo no lo malgasta.",
                "Paso a paso con templanza y sabiduría."
            ];
        } elseif ($postAuthor === 'marco_aurelio') {
            $blockB = [
                "El control sobre nuestra propia mente es el único poder real.",
                "La disciplina diaria forja la verdadera libertad.",
                "Enfocarse en lo que depende de uno vence cualquier obstáculo.",
                "La constancia silenciosa supera cualquier tormenta.",
                "Mantener el carácter inquebrantable es la mayor victoria.",
                "Cumplir con el deber propio con serenidad y firmeza.",
                "La fortaleza interior se demuestra en la calma.",
                "No quejarse, actuar y seguir firmes.",
                "El obstáculo es el camino cuando hay templanza.",
                "Enfocados en el propósito sin distracciones."
            ];
        } elseif ($postAuthor === 'dostoyevski') {
            $blockB = [
                "La verdadera fortaleza florece cuando decidimos levantarnos.",
                "Incluso en la prueba más difícil se forja el carácter.",
                "Transformar la dificultad en sabiduría es la mayor victoria.",
                "La luz siempre brilla con más fuerza en la oscuridad.",
                "El alma se templa en los momentos de mayor desafío.",
                "Cada reto vivido con dignidad nos hace invencibles.",
                "La resiliencia interior es el faro que guía el camino.",
                "Quien encuentra sentido a su lucha supera cualquier límite.",
                "La belleza de levantarse con más determinación cada día.",
                "Firmeza de espíritu ante cualquier adversidad."
            ];
        } else {
            $blockB = [
                "La constancia y el enfoque marcan la diferencia cada día.",
                "Cada paso con determinación cuenta más de lo que creemos.",
                "Mantenerse firme en el propósito abre todas las puertas.",
                "La disciplina y la serenidad siempre dan grandes frutos.",
                "El verdadero progreso se construye con paciencia y método.",
                "Acción coherente y visión clara en cada etapa.",
                "Avanzar con determinación transforma cualquier meta.",
                "La constancia silenciosa siempre supera al entusiasmo pasajero.",
                "Foco absoluto en lo que genera impacto real.",
                "Seguimos comprometidos con aportar el máximo valor."
            ];
        }

        // Block C: Cierre / Sello / Saludo final (8 variantes)
        if ($warmthLevel <= 35) {
            $blockC = [
                "Un saludo cordial.",
                "Continúe adelante.",
                "Seguimos a su disposición.",
                "Excelente jornada.",
                "Agradecemos su tiempo.",
                "Con serenidad y constancia.",
                "Atentamente.",
                "Paso a paso con enfoque."
            ];
        } elseif ($energyLevel <= 30) {
            $blockC = [
                "Paso a paso con serenidad y constancia.",
                "Firmes en el camino.",
                "Con serenidad y enfoque.",
                "Un saludo fraterno.",
                "Paso a paso con calma.",
                "Enfoque en lo esencial.",
                "Templanza y constancia.",
                "Serenidad ante todo."
            ];
        } elseif ($energyLevel >= 85) {
            $blockC = [
                "¡Adelante con todo! ⚡",
                "¡A romperla con fuerza! 🔥",
                "¡Fuerza imparable! 💪",
                "¡Vamos con toda la energía! ⚡",
                "¡A seguir construyendo juntos! 🚀",
                "¡Seguimos firmes e imparables! ⚡",
                "¡Con toda la determinación! 🔥",
                "¡A por todas! 💪"
            ];
        } else {
            $blockC = [
                "¡Adelante con todo! ✨",
                "Firmes en el camino. 🏛️",
                "¡A seguir construyendo con constancia! 🚀",
                "Un fuerte abrazo. 🤝",
                "Paso a paso con serenidad. ✨",
                "¡Con toda la fuerza! 💪",
                "Un saludo cordial y excelente día. ✨",
                "¡Seguimos firmes juntos! 🤝"
            ];
        }

        $countA = count($blockA);
        $countB = count($blockB);
        $countC = count($blockC);
        $totalCombinations = $countA * $countB * $countC;

        for ($offset = 0; $offset < $totalCombinations; $offset++) {
            $k = abs($seed + $offset) % $totalCombinations;
            $idxA = $k % $countA;
            $idxB = intdiv($k, $countA) % $countB;
            $idxC = intdiv($k, $countA * $countB) % $countC;

            $candidate = $blockA[$idxA] . ' ' . $blockB[$idxB] . ' ' . $blockC[$idxC];
            $candidate = self::applyGrammaticalFormality($candidate, $warmthLevel);

            if (!self::isTooSimilarToRecent($candidate, $recentReplies, 0.65)) {
                return $candidate;
            }
        }

        $k = abs($seed) % $totalCombinations;
        $candidate = $blockA[$k % $countA] . ' ' . $blockB[intdiv($k, $countA) % $countB] . ' ' . $blockC[intdiv($k, $countA * $countB) % $countC];
        return self::applyGrammaticalFormality($candidate, $warmthLevel);
    }

    /**
     * Apply Grammatical Formality / Treatment (Ustedeo vs. Tuteo vs. Neutral) (Module 4 Deep)
     */
    /**
     * Apply Grammatical Formality / Treatment (Ustedeo vs. Tuteo vs. Neutral) (Module 4 Deep)
     */
    public static function applyGrammaticalFormality(string $text, int $warmthLevel): string {
        if (empty($text)) return $text;

        // FORMAL USTEDEO (Warmth <= 35: Corporate, institutional, serious)
        if ($warmthLevel <= 35) {
            $tuteoPhrases = [
                '¿Cómo lo vives tú en tu día a día?' => '¿Cómo lo vive usted en su día a día?',
                '¿Cómo lo vives tú?' => '¿Cómo lo vive usted?',
                '¿Cómo lo aplicas tú?' => '¿Cómo lo aplica usted?',
                '¿En qué situación o reto buscas aplicarlo hoy?' => '¿En qué situación o reto busca aplicarlo hoy?',
                '¿Cuál consideras tu mayor desafío' => '¿Cuál considera su mayor desafío',
                '¿Eso resuena más en tu faceta' => '¿Eso resuena más en su faceta',
                '¿Qué opinas tú?' => '¿Qué opina usted?',
                '¿Qué piensas?' => '¿Qué piensa usted?',
                '¿Sientes que hoy priorizaste' => '¿Siente que hoy priorizó',
                '¿Sientes que estás priorizando' => '¿Siente que está priorizando',
                '¿Qué hábito te gustaría' => '¿Qué hábito le gustaría',
                '¿En qué decides' => '¿En qué decide',
                '¿Cuál es tu mayor desafío' => '¿Cuál es su mayor desafío',
                '¿Qué reto decides' => '¿Qué reto decide',
                '¿Cómo mantienes' => '¿Cómo mantiene',
                '¿Cómo decides' => '¿Cómo decide',
                '¿Te gustaría' => '¿Le gustaría',
                'agradecemos de corazón tu presencia' => 'agradecemos sinceramente su presencia',
                'revisa el enlace' => 'revise el enlace',
                'Revisa el enlace' => 'Revise el enlace',
                'cuenta con nosotros' => 'cuente con nosotros',
                'Cuenta con nosotros' => 'Cuente con nosotros',
                'sigue adelante' => 'continúe adelante',
                'Sigue adelante' => 'Continúe adelante',
                '¡Un fuerte abrazo!' => 'Un saludo cordial.',
                '¡Un abrazo enorme!' => 'Un saludo cordial.',
                'Un fuerte abrazo.' => 'Un saludo cordial.',
                'Un fuerte abrazo' => 'Un saludo cordial',
                '¡Qué alegría leerte!' => 'Agradecemos su participación.',
                'encantados de leerte' => 'un gusto contar con su participación',
                'Encantados de leerte' => 'Un gusto contar con su participación',
                'con tu gente' => 'con su entorno'
            ];

            foreach ($tuteoPhrases as $search => $replace) {
                $text = str_ireplace($search, $replace, $text);
            }

            // Word-boundary verbal and pronoun conversions
            $wordBoundaryMap = [
                '/\bte gustaría\b/iu' => 'le gustaría',
                '/\bte agradecemos\b/iu' => 'le agradecemos',
                '/\bte invitamos\b/iu' => 'le invitamos',
                '/\bte deseamos\b/iu' => 'le deseamos',
                '/\bte enviamos\b/iu' => 'le enviamos',
                '/\bte esperamos\b/iu' => 'le esperamos',
                '/\bte saludamos\b/iu' => 'le saludamos',
                '/\bte leemos\b/iu' => 'le leemos',
                '/\bte acompañamos\b/iu' => 'le acompañamos',
                '/\bte ayudamos\b/iu' => 'le ayudamos',
                '/\bcontigo\b/iu' => 'con usted',
                '/\bpara ti\b/iu' => 'para usted',
                '/\bde ti\b/iu' => 'de usted',
                '/\ba ti\b/iu' => 'a usted',
                '/\ben ti\b/iu' => 'en usted',
                '/\bcuéntanos\b/iu' => 'cuéntenos',
                '/\bescríbenos\b/iu' => 'escríbanos',
                '/\bdéjanos\b/iu' => 'déjenos',
                '/\bhaz clic\b/iu' => 'haga clic',
                '/\brevisa\b/iu' => 'revise',
                '/\baplica\b/iu' => 'aplique',
                '/\brecuerda que\b/iu' => 'recuerde que',
                '/\btu\b/iu' => 'su',
                '/\btus\b/iu' => 'sus',
                '/\btuyo\b/iu' => 'suyo',
                '/\btuyos\b/iu' => 'suyos',
                '/\btuya\b/iu' => 'suya',
                '/\btuyas\b/iu' => 'suyas'
            ];

            foreach ($wordBoundaryMap as $pattern => $replace) {
                $text = preg_replace($pattern, $replace, $text);
            }
        } elseif ($warmthLevel >= 70) {
            // Highly warm / empathetic: ensure warm tuteo connectors
            $text = str_ireplace(
                ['le agradecemos', 'su comentario', 'le invitamos', 'cuéntenos', 'escríbanos', 'Un saludo cordial.'],
                ['te agradecemos', 'tu comentario', 'te invitamos', 'cuéntanos', 'escríbenos', '¡Un fuerte abrazo!'],
                $text
            );
        }

        return $text;
    }

    /**
     * Apply Dynamic Tone Metrics (Warmth, Depth, Energy), Emoji Style, and Closing Questions (Module 4 Deep)
     */
    public static function applyDynamicToneAndStyle(
        array $res,
        int $warmthLevel,
        int $depthLevel,
        int $energyLevel,
        string $emojiStyle,
        string $closingQuestionRule,
        string $commentText,
        int $rotKey,
        string $intent = '',
        string $postAuthor = 'general'
    ): array {
        if (empty($res)) return $res;

        // Skip tone modulation for hostile/toxic comments to preserve strict security and sobriety
        if ($intent === 'toxic_hostile' || ($res['source'] ?? '') === 'toxic_hostile') {
            return $res;
        }

        $cleanComment = trim($commentText);
        $cleanLen = mb_strlen($cleanComment, 'UTF-8');
        $isTagOnly = (bool)preg_match('/^(@[\w\.\-]+\s*)+$/u', $cleanComment);
        $isVisualReaction = ($intent === 'visual_sticker_reaction' || $intent === 'emoji_reaction');
        $isShort = ($cleanLen <= 25) || $isTagOnly || $isVisualReaction;

        $emojiRegex = '/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F700}-\x{1F77F}\x{1F780}-\x{1F7FF}\x{1F800}-\x{1F8FF}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{1FA70}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{2300}-\x{23FF}\x{2B50}\x{200D}\x{FE0F}]/u';

        foreach (['engagement', 'conversion', 'support'] as $field) {
            if (empty($res[$field]) || !is_string($res[$field])) {
                continue;
            }

            $text = $res[$field];

            // 1. WARMTH & FORMALITY MODULATION (Module 4 Deep)
            $text = self::applyGrammaticalFormality($text, $warmthLevel);

            if ($warmthLevel <= 35) {
                // Cold / Formal Corporate: Remove informal superlatives and affectionate greetings
                $text = str_ireplace(
                    [
                        '¡Muchas gracias de corazón!', 'Muchas gracias de corazón', 'de corazón', 'con todo el cariño',
                        '¡Un abrazo enorme!', 'Un abrazo enorme', '¡Un fuerte abrazo!', 'Un fuerte abrazo',
                        '¡Qué alegría leerte!', '¡Mucho aprecio para ti', 'Mucho aprecio para ti'
                    ],
                    [
                        'Muchas gracias por su comentario.', 'Muchas gracias por su comentario.', '', '',
                        'Un saludo cordial.', 'Un saludo cordial.', 'Un saludo cordial.', 'Un saludo cordial.',
                        'Gracias por participar.', 'Agradecemos su comentario', 'Agradecemos su comentario'
                    ],
                    $text
                );
                // Convert affective emojis to professional/neutral ones
                $text = str_replace(['❤️', '🥰', '🫂', '🙌', '😘'], ['✨', '🤝', '🤝', '👍', '✨'], $text);
            } elseif ($warmthLevel <= 55) {
                // Moderate Warmth: soften emotional superlatives
                $text = str_ireplace(
                    ['¡Muchas gracias de corazón!', 'Muchas gracias de corazón', '¡Un abrazo enorme!'],
                    ['¡Muchas gracias!', 'Muchas gracias', '¡Un saludo muy especial!'],
                    $text
                );
            }

            // 2. ENERGY MODULATION MATRIX (Module 4 Deep)
            if ($energyLevel <= 30) {
                // Tier 1: Zen / Stoic Sobriety
                $text = str_ireplace(
                    [
                        '¡Vamos con todo!', '¡A romperla!', '¡Con toda la determinación!',
                        '¡A tope!', '¡Fuerza imparable!', '¡Dale con todo!', '¡Vamos por más!',
                        '¡Imparable!', '¡A romperla con todo!', '¡Con toda!', '¡Pura buena energía!'
                    ],
                    [
                        'Paso a paso con serenidad y constancia.', 'Enfoque continuo en lo esencial.',
                        'Con serenidad y determinación.', 'Firmes en el camino.', 'Paso a paso con constancia.',
                        'Adelante con serenidad.', 'Con constancia y templanza.', 'Constancia inquebrantable.',
                        'Con serenidad y foco.', 'Paso a paso.', 'Serenidad en cada paso.'
                    ],
                    $text
                );
                // Punctuation calm: change excessive exclamations to calm periods
                $text = preg_replace('/!+/', '.', $text);
                $text = preg_replace('/¡/', '', $text);
                // Replace hype emojis with calm/stoic ones
                $text = str_replace(['🔥', '💥', '⚡', '🚀', '🦁', '🎉'], ['🏛️', '✨', '🎯', '🤝', '🏛️', '✨'], $text);
            } elseif ($energyLevel <= 65) {
                // Tier 2: Calm / Professional balance
                $text = str_ireplace(
                    ['¡A romperla!', '¡Fuerza imparable!', '¡A tope!'],
                    ['¡Mucho éxito!', '¡Seguimos firmes!', '¡Adelante!'],
                    $text
                );
            } elseif ($energyLevel >= 85) {
                // Tier 4: High Energy / Hype
                if ($isShort && $field === 'engagement' && !str_contains($text, '⚡') && !str_contains($text, '🔥') && !str_contains($text, '💪')) {
                    $text = rtrim($text, ' .') . ' ¡Con toda la fuerza! ⚡🔥';
                }
                $text = str_replace(['👍', '🤝'], ['💪', '⚡'], $text);
            }

            // 3. DEPTH MODULATION MATRIX (Module 4 Deep)
            if ($depthLevel <= 30) {
                // Tier 1: Practical & direct (prune long quotes, deliver concise actionable advice)
                $text = preg_replace('/Como (enseñaba|recordaba|escribía|decía)\s+[^:]+:\s*[\'"][^\'"]+[\'"]\.\s*/iu', '', $text);
                $text = preg_replace('/El principio de [^,\.]+ nos recuerda que\s*/iu', 'Recuerda que ', $text);
            }

            // 4. CLOSING QUESTION RULE & CONTEXTUAL BANK (Module 4 Deep)
            if ($closingQuestionRule === 'never') {
                $text = preg_replace('/\s*¿[^?]+\?\s*$/u', '', $text);
            } elseif (($closingQuestionRule === 'always' || ($closingQuestionRule === 'relevant' && $rotKey % 2 === 0)) && $field === 'engagement') {
                if (!$isShort && !str_contains($text, '?') && !str_contains($text, '¿')) {
                    if ($postAuthor === 'seneca') {
                        $questions = [
                            ' ¿Sientes que estás priorizando lo que realmente está bajo tu control hoy? ⏳',
                            ' ¿Qué hábito te gustaría simplificar esta semana para ganar paz mental? 🏛️',
                            ' ¿En qué decides invertir tu mejor tiempo hoy? ✨'
                        ];
                    } elseif ($postAuthor === 'marco_aurelio') {
                        $questions = [
                            ' ¿Cuál es tu mayor desafío para mantener la disciplina hoy? 🎯',
                            ' ¿Qué obstáculo decides afrontar con serenidad esta semana? 🛡️',
                            ' ¿Cómo mantienes el enfoque cuando todo alrededor parece caótico? 🏛️'
                        ];
                    } elseif ($postAuthor === 'dostoyevski') {
                        $questions = [
                            ' ¿Qué aprendizaje profundo te ha dejado ese momento de prueba? 🕯️',
                            ' ¿Cómo transformas hoy la dificultad en resiliencia interior? ✨',
                            ' ¿Qué verdad esencial descubriste en medio de la tormenta? 🤝'
                        ];
                    } elseif ($postAuthor === 'epicteto') {
                        $questions = [
                            ' ¿Qué parte de ese reto depende 100% de ti hoy? 🏛️',
                            ' ¿Cómo decides responder con serenidad ante lo incontrolable? 🎯',
                            ' ¿En qué acción concreta enfocas hoy tu energía? ✨'
                        ];
                    } else {
                        $questions = [
                            ' ¿En qué situación o reto buscas aplicarlo hoy? 💬',
                            ' ¿Cómo lo vives tú en tu día a día? 🤝',
                            ' ¿Cuál consideras tu mayor desafío respecto a esto hoy? 🎯',
                            ' ¿Eso resuena más en tu faceta personal o profesional? ✨'
                        ];
                    }
                    $q = $questions[$rotKey % count($questions)];
                    if ($warmthLevel <= 35) {
                        $q = self::applyGrammaticalFormality($q, $warmthLevel);
                    }
                    $text = rtrim($text, ' .') . $q;
                }
            }

            // 5. EMOJI STYLE POLICIES (Module 4 Deep)
            if ($emojiStyle === 'none') {
                $text = preg_replace($emojiRegex, '', $text);
            } elseif ($emojiStyle === 'minimal') {
                preg_match_all($emojiRegex, $text, $matches);
                if (!empty($matches[0])) {
                    $firstEmoji = $matches[0][0];
                    $textNoEmojis = preg_replace($emojiRegex, '', $text);
                    $text = rtrim(preg_replace('/\s+/', ' ', $textNoEmojis)) . ' ' . $firstEmoji;
                }
            } elseif ($emojiStyle === 'moderate') {
                preg_match_all($emojiRegex, $text, $matches);
                if (!empty($matches[0]) && count($matches[0]) > 2) {
                    $count = 0;
                    $text = preg_replace_callback($emojiRegex, function($m) use (&$count) {
                        $count++;
                        return ($count <= 2) ? $m[0] : '';
                    }, $text);
                }
            }

            // Cleanup any duplicate whitespace or orphan punctuation
            $text = preg_replace('/\s+([,\.\?!])/', '$1', $text);
            $text = preg_replace('/\s+/', ' ', trim($text));

            $res[$field] = $text;
        }

        return $res;
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
