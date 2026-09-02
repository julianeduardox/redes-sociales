<?php
/**
 * AiAgentService - Advanced Stoic & Motivational AI Brand Voice Engine
 * Features:
 * - Zero-Token Local Heuristic Engine calibrated with brand guidelines
 * - Identity Tuning (Warmth, Philosophical Depth, Discipline Energy)
 * - Golden Few-Shot Master Examples Learning (Zero Cost)
 * - Negative Constraints (Forbidden Words / Blacklist) & Key Brand Concepts
 * - Gemini & OpenAI integrations with advanced prompt calibration
 */
require_once __DIR__ . '/../config/settings.php';

class AiAgentService {

    /**
     * Evaluate if a comment is suitable for Auto-Responder or if it should be marked as SPAM / FOREIGN / STICKER
     */
    public static function evaluateCommentSuitability(string $commentText): array {
        $text = trim($commentText);
        $textLower = mb_strtolower($text, 'UTF-8');

        // 1. Check for Link Spam / Crypto / Bot Promotion
        $spamPatterns = [
            'http://', 'https://', 'www.', '.com', '.io', '.xyz', '.net', '.org', 't.me/', 'wa.me/',
            'telegram', 'whatsapp', 'send pic on', 'promote on', 'promote it on', 'dm me on', 'inbox me on',
            'check my bio', 'clic en mi bio', 'ganar dinero', 'inversion segura', 'inversión segura',
            'trabajo desde casa', 'crypto', 'bitcoin', 'binance', 'forex', 'trading bot', 'free followers',
            'ganar seguidores', 'hacks', 'recupero cuentas', 'recuperar cuenta', 'dm us on', 'send dm to',
            'follow us on', 'check out our', 'hire me', 'investment platform'
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

        // 2. Check for Foreign Language (English phrases and stopwords)
        $englishPhrases = [
            'check dm', 'nice post', 'follow me', 'follow back', 'love this', 'amazing post',
            'great shot', 'check out', 'hit me up', 'reach out', 'send dm', 'let me know',
            'how much is it', 'thank you so much', 'good morning', 'nice one', 'so inspiring',
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

        // English stopwords matching with regex
        $englishWords = ['\bthe\b', '\band\b', '\bwith\b', '\bfrom\b', '\bhave\b', '\bthis\b', '\bthat\b', '\bwhat\b', '\byour\b', '\babout\b', '\bwould\b', '\bthere\b', '\btheir\b', '\bwill\b', '\bwhich\b', '\bvery\b', '\bbecause\b', '\bwhere\b', '\bpeople\b', '\breally\b', '\bcould\b', '\bshould\b', '\bplease\b', '\btoday\b', '\blooking\b', '\balways\b', '\bawesome\b', '\bgreat\b', '\bnice\b', '\bpost\b', '\bpicture\b'];
        $spanishWords = ['\bel\b', '\bla\b', '\blos\b', '\blas\b', '\bun\b', '\buna\b', '\bde\b', '\ben\b', '\bque\b', '\bqué\b', '\bpor\b', '\bpara\b', '\bcon\b', '\bsin\b', '\bsobre\b', '\beste\b', '\besta\b', '\besto\b', '\bcomo\b', '\bcómo\b', '\bpero\b', '\bgracias\b', '\bbuen\b', '\bbuena\b', '\bvida\b', '\btodo\b', '\btoda\b', '\bmuy\b', '\bmas\b', '\bmás\b', '\bmensaje\b', '\breflexion\b', '\breflexión\b'];

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

        // 3. Check for Stickers / Pure Emojis (No text / fewer than 2 letters)
        // Remove emojis and punctuation to see if real text exists
        $textNoEmoji = preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F700}-\x{1F77F}\x{1F780}-\x{1F7FF}\x{1F800}-\x{1F8FF}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{1FA70}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{2300}-\x{23FF}\x{2B50}\x{200D}\x{FE0F}\s\p{P}]/u', '', $text);
        
        if (mb_strlen($textNoEmoji, 'UTF-8') < 2) {
            return [
                'status' => 'ignored',
                'should_reply' => false,
                'reason' => '🎨 Comentario de solo emojis/stickers (sin texto para responder)',
                'category' => 'sticker'
            ];
        }

        // 4. Legitimate Spanish comment of ANY Score
        return [
            'status' => 'valid',
            'should_reply' => true,
            'reason' => '✅ Comentario legítimo en español apto para responder',
            'category' => 'valid'
        ];
    }

    /**
     * Analyze a comment for Stoic & Motivational context
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
        
        $score = 50; // Base score for any valid community comment
        $sentiment = 'neutral';
        $intent = 'general';
        $highlightReason = 'Comentario de la comunidad';
        $keywords = [];

        // 1. Emotional Vulnerability / Needing Support & Guidance
        $vulnerablePatterns = [
            'triste', 'ansiedad', 'depresion', 'depresión', 'dolor', 'duelo', 'perdi', 'perdí', 
            'no puedo mas', 'no puedo más', 'desmotivado', 'desesperado', 'vacio', 'vacío', 
            'fracaso', 'rendirme', 'llorar', 'dificil', 'difícil', 'estancado', 'solo', 'sola',
            'angustia', 'miedo', 'terrible', 'problemas', 'pesadilla', 'necesitaba leer esto'
        ];

        // 2. Philosophical / Advice / Deep Life Questions
        $philosophicalPatterns = [
            'como hago', 'cómo hago', 'como puedo', 'cómo puedo', 'como controlar', 'cómo controlar',
            'consejo', 'recomendacion', 'recomendación', 'que opinas', 'qué opinas', 'que libro', 'qué libro',
            'marco aurelio', 'seneca', 'séneca', 'epicteto', 'filosofia', 'filosofía', 'estoicismo',
            'disciplina', 'habito', 'hábito', 'proposito', 'propósito', 'mente', 'caracter', 'carácter',
            'amor fati', 'memento mori', 'dicotomia', 'dicotomía'
        ];

        // 3. Deep Gratitude / Impact / Transformation
        $gratitudePatterns = [
            'cambio mi vida', 'cambió mi vida', 'abrio los ojos', 'abrió los ojos', 'justo a tiempo',
            'me llego al alma', 'me llegó al alma', 'gracias infinitas', 'excelente reflexion', 'excelente reflexión',
            'gran mensaje', 'oro puro', 'tremendo mensaje', 'que gran verdad', 'qué gran verdad', 'inspirador',
            'me motivas', 'mi cuenta favorita', 'todos los dias te leo', 'todos los días te leo'
        ];

        // Check Vulnerability / Emotional Support first (Top Priority for Community care)
        $foundVulnerable = [];
        foreach ($vulnerablePatterns as $p) {
            if (str_contains($textLower, $p)) {
                $foundVulnerable[] = $p;
            }
        }
        if (!empty($foundVulnerable)) {
            $sentiment = 'urgent';
            $intent = 'support';
            $score = 95;
            $highlightReason = '🛡️ Apoyo Emocional & Resiliencia: Seguidor atravesando un momento difícil o buscando desahogo';
            $keywords = $foundVulnerable;
        }

        // Check Philosophical & Advice Questions
        $foundPhil = [];
        foreach ($philosophicalPatterns as $p) {
            if (str_contains($textLower, $p)) {
                $foundPhil[] = $p;
            }
        }
        if (empty($foundVulnerable) && !empty($foundPhil)) {
            $sentiment = 'question';
            $intent = 'lead_info';
            $score = 92;
            $highlightReason = '🧠 Pregunta Filosófica / Consejo Práctico: Excelente oportunidad para debate profundo';
            $keywords = $foundPhil;
        }

        // Check Deep Gratitude & Impact
        $foundGratitude = [];
        foreach ($gratitudePatterns as $p) {
            if (str_contains($textLower, $p)) {
                $foundGratitude[] = $p;
            }
        }
        if (empty($foundVulnerable) && empty($foundPhil) && !empty($foundGratitude)) {
            $sentiment = 'positive';
            $intent = 'compliment';
            $score = 88;
            $highlightReason = '✨ Testimonio de Impacto & Fidelización: El contenido resonó fuertemente en su vida';
            $keywords = $foundGratitude;
        }

        // Check Question marks
        if (str_contains($commentText, '?') || str_contains($commentText, '¿')) {
            if ($sentiment === 'neutral') {
                $sentiment = 'question';
                $score = 80;
                $highlightReason = '❓ Pregunta abierta de la comunidad que espera respuesta';
            }
        }

        // Boost for likes / social proof
        if ($likesCount >= 10) {
            $score += 8;
            $highlightReason .= ' (🔥 Alta tracción: ' . $likesCount . ' likes)';
        } elseif ($likesCount >= 5) {
            $score += 4;
        }

        // Length bonus: long reflections signify highly engaged users
        $length = mb_strlen($commentText);
        if ($length > 70 && $score < 95) {
            $score += 6;
        }

        // Cap score
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
     * Generate 3 Stoic & Motivational response variations:
     * 1. 🏛️ Reflexión Estoica (Profunda, filosófica, introspectiva)
     * 2. ⚔️ Impulso & Disciplina (Motivadora, llamada a la acción mental y forja de carácter)
     * 3. 🤝 Empatía & Hermandad (Cálida, fraternal, de apoyo y agradecimiento sincero)
     */
    public static function generateReplies(
        string $authorName,
        string $commentText,
        string $platform = 'instagram',
        string $postCaption = '',
        string $overrideTone = '',
        array $runtimeOverrides = []
    ): array {
        $brandName = $runtimeOverrides['brand_name'] ?? Settings::get('brand_name', 'Mente Estoica');
        $brandIndustry = $runtimeOverrides['brand_industry'] ?? Settings::get('brand_industry', 'Estoicismo, Filosofía Práctica y Crecimiento Personal');
        $brandTone = !empty($overrideTone) ? $overrideTone : ($runtimeOverrides['brand_tone'] ?? Settings::get('brand_tone', 'stoic_mentor'));
        $brandDescription = $runtimeOverrides['brand_description'] ?? Settings::get('brand_description', 'Comunidad dedicada a la filosofía estoica (Marco Aurelio, Séneca, Epicteto), disciplina diaria, resiliencia mental y autodominio.');
        
        $warmthLevel = (int)($runtimeOverrides['brand_warmth_level'] ?? Settings::get('brand_warmth_level', 85));
        $depthLevel = (int)($runtimeOverrides['brand_depth_level'] ?? Settings::get('brand_depth_level', 80));
        $energyLevel = (int)($runtimeOverrides['brand_energy_level'] ?? Settings::get('brand_energy_level', 75));
        $closingQuestionRule = $runtimeOverrides['brand_closing_question_rule'] ?? Settings::get('brand_closing_question_rule', 'always');
        $emojiStyle = $runtimeOverrides['brand_emoji_style'] ?? Settings::get('brand_emoji_style', 'moderate');

        $keyPhrases = self::parseJsonSetting($runtimeOverrides['brand_key_phrases'] ?? Settings::get('brand_key_phrases', ''), [
            'Dicotomía del control', 'Amor Fati', 'Memento Mori', 'Autodominio', 'Fortaleza mental', 'Disciplina diaria'
        ]);

        $forbiddenPhrases = self::parseJsonSetting($runtimeOverrides['brand_forbidden_phrases'] ?? Settings::get('brand_forbidden_phrases', ''), [
            'Estimado cliente', 'Compra ya', 'Oferta imperdible', 'Somos un bot', 'Haz clic aquí'
        ]);

        $fewShotExamples = self::parseJsonSetting($runtimeOverrides['brand_few_shot_examples'] ?? Settings::get('brand_few_shot_examples', ''), self::getDefaultFewShotExamples());

        $aiProvider = $runtimeOverrides['ai_provider'] ?? Settings::get('ai_provider', 'gemini');
        $geminiKey = Settings::get('gemini_api_key', '');
        $openaiKey = Settings::get('openai_api_key', '');

        // Try Gemini API first if configured
        if ($aiProvider === 'gemini' && !empty($geminiKey)) {
            $geminiResult = self::callGeminiApi(
                $authorName, $commentText, $platform, $postCaption, 
                $brandName, $brandTone, $brandDescription, $warmthLevel, $depthLevel, $energyLevel,
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
                $brandName, $brandTone, $brandDescription, $warmthLevel, $depthLevel, $energyLevel,
                $closingQuestionRule, $emojiStyle, $keyPhrases, $forbiddenPhrases, $fewShotExamples, $openaiKey
            );
            if ($openaiResult !== null && !empty($openaiResult['engagement'])) {
                return self::sanitizeRepliesWithForbidden($openaiResult, $forbiddenPhrases);
            }
        }

        // Fallback / Standalone: High-Context Calibrated Zero-Token Heuristic Engine
        $localResult = self::generateHeuristicReplies(
            $authorName, $commentText, $platform, $postCaption, 
            $brandName, $brandTone, $brandDescription, $warmthLevel, $depthLevel, $energyLevel,
            $closingQuestionRule, $emojiStyle, $keyPhrases, $forbiddenPhrases, $fewShotExamples
        );

        return self::sanitizeRepliesWithForbidden($localResult, $forbiddenPhrases);
    }

    /**
     * Built-in Calibrated Zero-Token Stoic & Motivational Engine
     */
    public static function generateHeuristicReplies(
        string $authorName,
        string $commentText,
        string $platform,
        string $postCaption,
        string $brandName,
        string $brandTone,
        string $brandDescription,
        int $warmthLevel = 85,
        int $depthLevel = 80,
        int $energyLevel = 75,
        string $closingQuestionRule = 'always',
        string $emojiStyle = 'moderate',
        array $keyPhrases = [],
        array $forbiddenPhrases = [],
        array $fewShotExamples = []
    ): array {
        $firstName = explode(' ', trim($authorName))[0] ?: 'amigo';
        $textLower = mb_strtolower($commentText, 'UTF-8');
        
        $isVulnerable = str_contains($textLower, 'triste') || str_contains($textLower, 'ansiedad') || str_contains($textLower, 'dolor') || str_contains($textLower, 'duelo') || str_contains($textLower, 'perdi') || str_contains($textLower, 'perdí') || str_contains($textLower, 'dificil') || str_contains($textLower, 'difícil') || str_contains($textLower, 'fracaso') || str_contains($textLower, 'no puedo');
        $isGratitude = str_contains($textLower, 'gracias') || str_contains($textLower, 'necesitaba') || str_contains($textLower, 'cambio') || str_contains($textLower, 'cambió') || str_contains($textLower, 'oro') || str_contains($textLower, 'inspirador') || str_contains($textLower, 'excelente');
        $isQuestion = str_contains($textLower, 'como') || str_contains($textLower, 'cómo') || str_contains($textLower, 'consejo') || str_contains($textLower, 'libro') || str_contains($textLower, 'disciplina') || str_contains($textLower, 'habito') || str_contains($commentText, '?') || str_contains($commentText, '¿');

        // Check if there is a matching master few-shot example registered by the user
        $matchedExample = self::findMatchingFewShotExample($commentText, $fewShotExamples);

        // Emoji styling helper
        $eStoic = ($emojiStyle === 'minimal') ? '🏛️' : (($emojiStyle === 'expressive') ? '🏛️ 📜' : '🏛️');
        $eSword = ($emojiStyle === 'minimal') ? '⚔️' : (($emojiStyle === 'expressive') ? '⚔️ 🔥' : '⚔️');
        $eHeart = ($emojiStyle === 'minimal') ? '🤝' : (($emojiStyle === 'expressive') ? '🤝 ✨' : '🤝');

        // Warmth prefixes
        if ($warmthLevel >= 80) {
            $greetStoic = "Gran reflexión, $firstName. $eStoic";
            $greetSword = "¡Con toda la fuerza, $firstName! $eSword";
            $greetSupport = "Te mando un fuerte abrazo fraternal, $firstName. $eHeart";
        } elseif ($warmthLevel >= 50) {
            $greetStoic = "Hola $firstName. $eStoic";
            $greetSword = "Fuerza y enfoque, $firstName. $eSword";
            $greetSupport = "Gracias por compartir tu sentir, $firstName. $eHeart";
        } else {
            $greetStoic = "$eStoic";
            $greetSword = "$eSword";
            $greetSupport = "$eHeart";
        }

        // Depth phrases
        $stoicCoreQuote = ($depthLevel >= 75) 
            ? "Como enseñaba Marco Aurelio: 'Tienes poder sobre tu mente, no sobre los acontecimientos externos. Comprende esto y encontrarás la fuerza'."
            : "Recuerda la Dicotomía del Control: no gastes energía en lo que escapa a tus manos, domina tu respuesta en el presente.";

        // Energy phrases
        $energyCore = ($energyLevel >= 75)
            ? "La disciplina no depende de las ganas, sino del compromiso innegociable con tus principios."
            : "Paso a paso, forjando serenidad y paciencia ante cada reto.";

        // Closing Questions based on rule
        $questionVulnerable = ($closingQuestionRule !== 'never') ? "¿Qué pequeña acción está hoy 100% en tu control para dar el siguiente paso?" : "Mantén tu tranquilidad intacta.";
        $questionQuestion = ($closingQuestionRule !== 'never') ? "¿Cuál es el mayor obstáculo que sientes que te frena en este momento?" : "Un día a la vez forjando carácter.";
        $questionGratitude = ($closingQuestionRule !== 'never') ? "¿Qué parte de la reflexión fue la que más te hizo clic hoy?" : "Sigamos creciendo juntos en comunidad.";
        $questionGeneral = ($closingQuestionRule !== 'never') ? "¿Cómo aplicas tú este principio en tu rutina diaria? 👇" : "Foco, calma y adelante.";

        // If a master few-shot example matches closely, adapt it!
        if ($matchedExample) {
            return [
                'source' => 'heuristic_few_shot_trained',
                'engagement' => "$greetStoic " . self::adaptFewShotReply($matchedExample['reply'], $firstName) . ($closingQuestionRule === 'always' ? " " . $questionGeneral : ''),
                'conversion' => "$greetSword $energyCore " . self::adaptFewShotReply($matchedExample['reply'], $firstName),
                'support' => "$greetSupport " . self::adaptFewShotReply($matchedExample['reply'], $firstName),
                'engagement_tips' => '🧠 Respuesta enriquecida por el Ejemplo Maestro de Oro entrenado para este patrón.'
            ];
        }

        // Case 1: Difficult moment / Vulnerability / Needing strength
        if ($isVulnerable) {
            return [
                'source' => 'heuristic_calibrated',
                'engagement' => "$greetStoic Séneca decía que a veces el solo hecho de vivir es un acto de valentía. Los momentos duros no vienen a destruirte, sino a pulir tu armadura. $questionVulnerable",
                'conversion' => "$greetSword Respira hondo, $firstName. Los obstáculos son el camino (Amor Fati). Cada día es una oportunidad de oro para reconstruirte con disciplina y calma mental.",
                'support' => "$greetSupport Gracias por tu confianza y sinceridad. No estás solo en esta batalla; esta comunidad camina a tu lado. Si necesitas desahogarte, aquí estamos siempre.",
                'engagement_tips' => '🛡️ Responder con empatía profunda transforma a un seguidor ocasional en un miembro fiel y agradecido de por vida.'
            ];
        }

        // Case 2: Inquiring about advice, habits, or stoic practices
        if ($isQuestion) {
            return [
                'source' => 'heuristic_calibrated',
                'engagement' => "$greetStoic $stoicCoreQuote Cuando aplicas este filtro mental a tu rutina, la prisa y la frustración se transforman en claridad. $questionQuestion",
                'conversion' => "$greetSword Para forjar esa solidez mental: empieza por lo pequeño pero hazlo innegociable cada día. $energyCore ¡A darle con todo!",
                'support' => "$greetSupport ¡Un honor leerte! Te recomiendo comenzar con las 'Meditaciones' de Marco Aurelio y las 'Cartas a Lucilio' de Séneca. Nos encanta tenerte reflexionando con nosotros.",
                'engagement_tips' => '🧠 Invitar a identificar su mayor obstáculo invita a otros miembros a debatir y aportar soluciones en el hilo.'
            ];
        }

        // Case 3: Gratitude / "Necesitaba leer esto" / Life impact
        if ($isGratitude) {
            return [
                'source' => 'heuristic_calibrated',
                'engagement' => "$greetStoic Saber que este mensaje llegó en el momento exacto es la mayor recompensa. $questionGratitude",
                'conversion' => "$greetSword ¡Totalmente, $firstName! Guarda este recordatorio para los días difíciles. La filosofía estoica solo tiene valor cuando se practica en el campo de batalla diario. ¡A seguir forjando carácter!",
                'support' => "$greetSupport ¡Gracias de corazón por estar aquí! Tu presencia y tu energía hacen que esta comunidad siga creciendo con valores firmes. ¡Sigamos adelante!",
                'engagement_tips' => '✨ Preguntarle qué parte le hizo clic estimula que el seguidor profundice y vuelva a interactuar.'
            ];
        }

        // Case 4: General comment / Emojis / Appreciation
        return [
            'source' => 'heuristic_calibrated',
            'engagement' => "$greetStoic La mente tranquila y enfocada es invencible frente a cualquier tormenta exterior. $questionGeneral",
            'conversion' => "$greetSword ¡Foco y disciplina, $firstName! $energyCore ¡Memento Mori y adelante!",
            'support' => "$greetSupport ¡Muchas gracias por compartir tu energía con nosotros! Un placer tenerte en la comunidad estoica. 🙌",
            'engagement_tips' => '💬 Las preguntas abiertas al final de un comentario aumentan el tiempo de permanencia y las respuestas en el post.'
        ];
    }

    /**
     * Find best matching few-shot master example
     */
    private static function findMatchingFewShotExample(string $commentText, array $examples): ?array {
        $textLower = mb_strtolower($commentText, 'UTF-8');
        foreach ($examples as $ex) {
            $exComment = mb_strtolower($ex['comment'] ?? '', 'UTF-8');
            $exTag = mb_strtolower($ex['tag'] ?? '', 'UTF-8');
            
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

            if (!empty($exTag) && str_contains($textLower, $exTag)) {
                return $ex;
            }
        }
        return null;
    }

    private static function adaptFewShotReply(string $masterReply, string $firstName): string {
        $adapted = str_ireplace('{nombre}', $firstName, $masterReply);
        $adapted = str_ireplace('{name}', $firstName, $adapted);
        return $adapted;
    }

    /**
     * Gemini API Integration with Calibrated Identity
     */
    private static function callGeminiApi(
        string $authorName,
        string $commentText,
        string $platform,
        string $postCaption,
        string $brandName,
        string $brandTone,
        string $brandDescription,
        int $warmthLevel,
        int $depthLevel,
        int $energyLevel,
        string $closingQuestionRule,
        string $emojiStyle,
        array $keyPhrases,
        array $forbiddenPhrases,
        array $fewShotExamples,
        string $apiKey
    ): ?array {
        $prompt = self::buildSystemPrompt(
            $authorName, $commentText, $platform, $postCaption, 
            $brandName, $brandTone, $brandDescription, $warmthLevel, $depthLevel, $energyLevel,
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            error_log("Gemini API cURL Error: " . $curlErr);
        }

        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $parsed = json_decode($rawText, true);
            if (isset($parsed['engagement']) && isset($parsed['conversion']) && isset($parsed['support'])) {
                return [
                    'source' => 'gemini_ai_calibrated',
                    'engagement' => $parsed['engagement'],
                    'conversion' => $parsed['conversion'],
                    'support' => $parsed['support'],
                    'engagement_tips' => $parsed['engagement_tips'] ?? 'Hacer preguntas introspectivas motiva a otros seguidores a compartir sus propias experiencias.'
                ];
            }
        }

        return null;
    }

    /**
     * OpenAI API Integration with Calibrated Identity
     */
    private static function callOpenAiApi(
        string $authorName,
        string $commentText,
        string $platform,
        string $postCaption,
        string $brandName,
        string $brandTone,
        string $brandDescription,
        int $warmthLevel,
        int $depthLevel,
        int $energyLevel,
        string $closingQuestionRule,
        string $emojiStyle,
        array $keyPhrases,
        array $forbiddenPhrases,
        array $fewShotExamples,
        string $apiKey
    ): ?array {
        $prompt = self::buildSystemPrompt(
            $authorName, $commentText, $platform, $postCaption, 
            $brandName, $brandTone, $brandDescription, $warmthLevel, $depthLevel, $energyLevel,
            $closingQuestionRule, $emojiStyle, $keyPhrases, $forbiddenPhrases, $fewShotExamples
        );
        
        $url = 'https://api.openai.com/v1/chat/completions';

        $payload = [
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'Eres el mentor de comunidad de la marca. Devuelve únicamente JSON válido estrictamente alineado con las reglas de identidad.'],
                ['role' => 'user', 'content' => $prompt]
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            error_log("OpenAI API cURL Error: " . $curlErr);
        }

        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            $rawJsonText = $data['choices'][0]['message']['content'] ?? '';
            $parsed = json_decode($rawJsonText, true);
            if (isset($parsed['engagement']) && isset($parsed['conversion']) && isset($parsed['support'])) {
                return [
                    'source' => 'openai_ai_calibrated',
                    'engagement' => $parsed['engagement'],
                    'conversion' => $parsed['conversion'],
                    'support' => $parsed['support'],
                    'engagement_tips' => $parsed['engagement_tips'] ?? 'Una respuesta estoica y profunda genera respeto y fidelidad en la comunidad.'
                ];
            }
        }

        return null;
    }

    /**
     * Build Prompt with Calibrated Parameters & Few-Shot Examples
     */
    private static function buildSystemPrompt(
        string $authorName,
        string $commentText,
        string $platform,
        string $postCaption,
        string $brandName,
        string $brandTone,
        string $brandDescription,
        int $warmthLevel = 85,
        int $depthLevel = 80,
        int $energyLevel = 75,
        string $closingQuestionRule = 'always',
        string $emojiStyle = 'moderate',
        array $keyPhrases = [],
        array $forbiddenPhrases = [],
        array $fewShotExamples = []
    ): string {
        $firstName = explode(' ', trim($authorName))[0] ?: 'amigo';
        $keyPhrasesText = !empty($keyPhrases) ? implode(', ', $keyPhrases) : 'Dicotomía del control, Amor Fati, Memento Mori, Autodominio';
        $forbiddenText = !empty($forbiddenPhrases) ? implode(', ', $forbiddenPhrases) : 'Estimado cliente, Compra ya, Oferta imperdible, Somos un bot';

        $fewShotText = '';
        if (!empty($fewShotExamples)) {
            $fewShotText .= "EJEMPLOS DE ORO MAESTROS DE LA MARCA (Imita este estilo exacto):\n";
            foreach (array_slice($fewShotExamples, 0, 4) as $idx => $ex) {
                $c = $ex['comment'] ?? '';
                $r = $ex['reply'] ?? '';
                $fewShotText .= "Ejemplo #" . ($idx + 1) . ":\n- Comentario de Seguidor: \"$c\"\n- Respuesta Maestra Ideal: \"$r\"\n\n";
            }
        }

        return <<<PROMPT
Eres el creador y mentor detrás de la cuenta "$brandName" en $platform.
Filosofía y valores de la cuenta: $brandDescription.
Tono configurado: $brandTone.

CALIBRACIÓN DE IDENTIDAD INNEGOCIABLE:
- Nivel de Cercanía & Calidez Humana: $warmthLevel% (Trata a la persona con respeto, fraternidad y cercanía genuina).
- Nivel de Profundidad Filosófica / Sabiduría: $depthLevel% (Incluye principios estoicos de Marco Aurelio, Séneca o Epicteto).
- Nivel de Firmeza & Disciplina: $energyLevel% (Enfócate en la acción y en superar la pereza mental).
- Regla de Pregunta de Cierre: $closingQuestionRule (Si es 'always', termina la respuesta con una pregunta introspectiva para elevar el engagement).
- Estilo de Emojis: $emojiStyle.

CONCEPTOS CLAVE A PROMOVER: $keyPhrasesText.
FRASES TOTALMENTE PROHIBIDAS (NUNCA LAS USES): $forbiddenText.

$fewShotText

CONTEXTO ACTUAL:
- Publicación del feed: "$postCaption".
- Comentario del seguidor ($firstName): "$commentText".

Genera 3 opciones de respuesta saludando a $firstName sin sonar robótico ni usar frases prohibidas:
1. "engagement": [🏛️ Reflexión Estoica]: Profunda, sabia y con perspectiva estoica. Remata con una pregunta introspectiva.
2. "conversion": [⚔️ Impulso & Disciplina]: Motivadora, enérgica y orientada a la acción.
3. "support": [🤝 Empatía & Hermandad]: Cálida, fraternal y de apoyo si expresa dolor o agradecimiento.

Responde únicamente en formato JSON:
{
  "engagement": "texto de respuesta 1",
  "conversion": "texto de respuesta 2",
  "support": "texto de respuesta 3",
  "engagement_tips": "breve tip de por qué esta respuesta conecta con la audiencia"
}
PROMPT;
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
                'tag' => 'desmotivacion',
                'comment' => 'Llevo semanas sin ganas de entrenar ni trabajar, siento que no avanzo nada.',
                'reply' => 'Te entiendo, {nombre}. La motivación es solo una chispa pasajera; la disciplina es el fuego que mantienes encendido incluso cuando no tienes ganas. Séneca decía que no nos atrevemos porque las cosas son difíciles, sino que son difíciles porque no nos atrevemos. ¿Qué pequeño paso de solo 5 minutos puedes dar hoy?'
            ],
            [
                'tag' => 'libros',
                'comment' => '¿Qué libro me recomiendas para empezar en el estoicismo desde cero?',
                'reply' => '¡Excelente decisión, {nombre}! Empieza directo por las "Meditaciones" de Marco Aurelio (traducción de Robin Hard o Carlos García Gual) y las "Cartas a Lucilio" de Séneca. Léelos sin prisa, aplicando una frase cada día. ¿Cuál es el mayor desafío que buscas superar con esta lectura?'
            ],
            [
                'tag' => 'agradecimiento',
                'comment' => 'Justo necesitaba leer esto hoy, gracias infinitas por este contenido tan valioso.',
                'reply' => '¡Gracias de corazón por tus palabras, {nombre}! 🏛️ Saber que estas reflexiones te acompañan en tu camino diario es el mayor honor. Recuerda: abraza lo que te toque vivir hoy con serenidad (Amor Fati). ¡Sigamos creciendo juntos!'
            ]
        ];
    }
}
