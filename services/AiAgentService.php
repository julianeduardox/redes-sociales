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
     * Interruptor temporal de procesos comerciales y soporte técnico.
     * Desactivado para fortaleza_imparable (página de reflexiones estoicas y filosofía).
     * Cambiar a true cuando se requiera reactivar ventas y soporte.
     */
    public const COMMERCIAL_SALES_ACTIVE = false;

    /**
     * Conservative Local Language Detector for Supported Social Languages (ES, PT, EN)
     *
     * @param string $text
     * @return array{language: string, confidence: float, source: string, is_supported: bool, is_ambiguous: bool, scores?: array}
     */
    public static function detectSupportedLanguage(string $text): array {
        $clean = trim($text);
        if (empty($clean)) {
            return [
                'language' => 'und',
                'confidence' => 0.0,
                'source' => 'local_detector',
                'is_supported' => false,
                'is_ambiguous' => true
            ];
        }

        // ══════════════════════════════════════════════════════════════════
        // Step 1: Detect and separate greeting signatures / multi-language sign-offs
        // Followers frequently post quotes/thoughts followed by routine courtesy greetings
        // in 2-4 languages: e.g. "Buenos tardes\nBoa tarde\nKonnichiwa\nGood night"
        // ══════════════════════════════════════════════════════════════════
        $lines = preg_split('/[\r\n]+/u', $clean, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $substantiveLines = [];
        $greetingLines = [];
        $greetingRegex = '/^(?:buenos?\s+(?:d[ií]as?|tardes?|noches?)|buenas?\s+(?:tardes?|noches?)|bom\s+dia|boa\s+(?:tarde|noite)|konnichiwa|konbanwa|arigato|ohayo|good\s+(?:morning|afternoon|evening|night)|namaste|salut|hola|oi|ol[aá])[\s!.,-]*$/iu';

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (preg_match($greetingRegex, $trimmed)) {
                $greetingLines[] = $trimmed;
            } else {
                $substantiveLines[] = $trimmed;
            }
        }

        // If substantive text exists, evaluate linguistic signals primarily on substantive text
        $evalText = !empty($substantiveLines) ? implode(' ', $substantiveLines) : $clean;

        // Clean URLs, handles and punctuation to evaluate linguistic signals
        $withoutMentions = preg_replace('/@[a-z0-9_\.]+/iu', ' ', $evalText);
        $withoutUrls = preg_replace('/https?:\/\/\S+/iu', ' ', $withoutMentions);
        $normalizedQuotes = str_replace(['“', '”', '‘', '’'], ['"', '"', "'", "'"], $withoutUrls);
        $plainLettersOnly = preg_replace('/[^\p{L}\s\']/u', ' ', $normalizedQuotes);
        $textLower = mb_strtolower(trim($normalizedQuotes), 'UTF-8');

        $words = preg_split('/\s+/u', mb_strtolower(trim($plainLettersOnly), 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $wordCount = count($words);

        // Pure emojis, symbols or less than 3 letters
        $letterCount = mb_strlen(preg_replace('/\s+/u', '', $plainLettersOnly), 'UTF-8');
        if ($letterCount < 3 || $wordCount === 0) {
            return [
                'language' => 'und',
                'confidence' => 0.0,
                'source' => 'local_detector',
                'is_supported' => false,
                'is_ambiguous' => true
            ];
        }

        $scoreEs = 0.0;
        $scorePt = 0.0;
        $scoreEn = 0.0;

        // ══════════════════════════════════════════════════════════════════
        // 1. Distinctive Spanish Signals
        // ══════════════════════════════════════════════════════════════════
        if (str_contains($clean, '¿') || str_contains($clean, '¡') || str_contains($textLower, 'ñ')) {
            $scoreEs += 3.5;
        }

        $esDistinctive = [
            'qué', 'cómo', 'cuándo', 'dónde', 'por qué', 'también', 'además', 'gracias', 'muchas gracias',
            'bueno', 'buenos', 'buenas', 'vida', 'hacer', 'tenemos', 'nosotros', 'ustedes', 'firmeza',
            'camino', 'hermano', 'hermana', 'guerrero', 'disciplina', 'estoico', 'estoicismo', 'pensar',
            'está', 'están', 'estoy', 'tiempo', 'siempre', 'nuestro', 'nuestra', 'nuestros', 'nuestras',
            'verdad', 'momento', 'hoy', 'mañana', 'ayer', 'buen día', 'saludos', 'abrazo', 'foco', 'totalmente',
            'acuerdo', 'increíble', 'maravilloso', 'fuerza', 'adelante', 'éxito', 'mente', 'consejo', 'pregunta',
            'sabiduría', 'hombre', 'persona', 'temor', 'miedo', 'derrota', 'propósito', 'vencido', 'vencerte'
        ];
        foreach ($esDistinctive as $token) {
            if (preg_match('/(?:\b|^)' . preg_quote($token, '/') . '(?:\b|$)/iu', $textLower)) {
                $scoreEs += 2.2;
            }
        }

        $esCommon = ['el', 'la', 'los', 'las', 'un', 'una', 'unos', 'unas', 'de', 'en', 'por', 'con', 'sin', 'pero', 'muy', 'más', 'este', 'esta', 'esto', 'estos', 'estas', 'es', 'al', 'del', 'su', 'sus', 'si', 'no'];
        foreach ($esCommon as $token) {
            if (preg_match('/(?:\b|^)' . preg_quote($token, '/') . '(?:\b|$)/iu', $textLower)) {
                $scoreEs += 1.0;
            }
        }

        // ══════════════════════════════════════════════════════════════════
        // 2. Distinctive Portuguese Signals
        // ══════════════════════════════════════════════════════════════════
        if (preg_match('/[ãõ]/u', $textLower) || preg_match('/(?:ção|ções|ência|ências)\b/iu', $textLower)) {
            $scorePt += 3.2;
        }

        $ptDistinctive = [
            'não', 'você', 'vocês', 'vitória', 'vitorias', 'derrota', 'derrotas', 'obrigado', 'obrigada',
            'muito obrigado', 'muito obrigada', 'também', 'então', 'nosso', 'nossa', 'nossos', 'nossas',
            'segundo', 'comentário', 'atenção', 'coração', 'ação', 'ações', 'isso', 'isto', 'aquilo',
            'atual', 'atuais', 'fazer', 'está', 'estão', 'estou', 'bom dia', 'boa tarde', 'boa noite',
            'abraço', 'força', 'caminho', 'guerreiro', 'tudo', 'hoje',
            'amanhã', 'ontem', 'verdade', 'irmão', 'irmã', 'foco', 'com certeza', 'valeu',
            'parabéns', 'perfeito', 'incrível', 'sucesso', 'pergunta', 'conselho', 'postagem',
            'sabedoria', 'derrotado', 'propósito'
        ];
        foreach ($ptDistinctive as $token) {
            if (preg_match('/(?:\b|^)' . preg_quote($token, '/') . '(?:\b|$)/iu', $textLower)) {
                $scorePt += 2.2;
            }
        }

        $ptCommon = ['o', 'a', 'os', 'as', 'do', 'da', 'dos', 'das', 'no', 'na', 'nos', 'nas', 'pelo', 'pela', 'pelos', 'pelas', 'com', 'sem', 'mas', 'muito', 'mais', 'é', 'um', 'uma', 'uns', 'umas', 'seu', 'sua', 'seus', 'suas', 'se'];
        foreach ($ptCommon as $token) {
            if (preg_match('/(?:\b|^)' . preg_quote($token, '/') . '(?:\b|$)/iu', $textLower)) {
                $scorePt += 1.2;
            }
        }

        // ══════════════════════════════════════════════════════════════════
        // 3. Distinctive English Signals
        // ══════════════════════════════════════════════════════════════════
        $enDistinctive = [
            'what', 'where', 'when', 'which', 'who', 'why', 'how', 'thank you', 'thanks', 'because',
            'your', 'yours', 'with', 'without', 'please', 'awesome', 'great', 'about', 'would', 'could',
            'should', 'there', 'their', 'they', 'people', 'really', 'today', 'looking', 'always', 'never', 'good',
            'nice', 'love', 'post', 'view', 'picture', 'beautiful', 'brother', 'strength', 'discipline',
            'journey', 'path', 'focus', 'morning', 'afternoon', 'night', 'mindset', 'stoic', 'stoicism',
            'mind', 'life', 'work', 'well said', 'keep it up', 'proud', 'amazing', 'question', 'advice',
            'success', 'victory', 'defeat', 'system', 'current', 'strong', 'stay', 'give', 'never give up',
            'wise', 'wisdom', 'person', 'fear', 'final', 'setback', 'purpose', 'invincible', 'beat', 'yourself',
            'tired', 'stop', 'finished', 'eliminate', 'unnecessary', 'thoughts', 'world', 'clarity',
            'effort', 'efforts', 'repeated', 'day', 'days', 'small', 'sum', 'don\'t', 'doesn\'t', 'didn\'t',
            'won\'t', 'can\'t', 'cannot', 'truth', 'true', 'power', 'powerful'
        ];
        foreach ($enDistinctive as $token) {
            if (preg_match('/(?:\b|^)' . preg_quote($token, '/') . '(?:\b|$)/iu', $textLower)) {
                $scoreEn += 2.2;
            }
        }

        $enCommon = [
            'the', 'and', 'is', 'are', 'was', 'were', 'have', 'has', 'had', 'this', 'that', 'these', 'those',
            'from', 'to', 'for', 'in', 'on', 'at', 'by', 'it', 'its', 'you', 'we', 'they', 'i', 'my', 'me',
            'of', 'so', 'but', 'can', 'will', 'do', 'did', 'be', 'been', 'if', 'not', 'no', 'up', 'out', 'all',
            'just', 'like', 'more', 'only', 'than', 'our', 'see'
        ];
        foreach ($enCommon as $token) {
            if (preg_match('/(?:\b|^)' . preg_quote($token, '/') . '(?:\b|$)/iu', $textLower)) {
                $scoreEn += 1.2;
            }
        }

        // Shared words between Spanish and Portuguese (distributed neutrally)
        $sharedEsPt = ['que', 'para', 'como', 'por', 'de', 'vida', 'disciplina', 'mente', 'tempo', 'sempre', 'sistema'];
        foreach ($sharedEsPt as $token) {
            if (preg_match('/(?:\b|^)' . preg_quote($token, '/') . '(?:\b|$)/iu', $textLower)) {
                $scoreEs += 0.5;
                $scorePt += 0.5;
            }
        }

        $scores = ['es' => $scoreEs, 'pt' => $scorePt, 'en' => $scoreEn];
        arsort($scores);
        $topLang = array_key_first($scores);
        $topScore = $scores[$topLang];
        $secondLang = array_keys($scores)[1];
        $secondScore = $scores[$secondLang];
        $totalScore = array_sum($scores);

        if ($totalScore <= 0.8 || $topScore < 1.4) {
            return [
                'language' => 'und',
                'confidence' => 0.35,
                'source' => 'local_detector',
                'is_supported' => false,
                'is_ambiguous' => true,
                'scores' => $scores
            ];
        }

        // Mixed Language Check: only if second language is strong and represents >= 35% of total score
        if ($secondScore >= 2.5 && ($secondScore / $totalScore) >= 0.35) {
            return [
                'language' => 'mixed',
                'confidence' => 0.50,
                'source' => 'local_detector',
                'is_supported' => false,
                'is_ambiguous' => true,
                'details' => ['primary' => $topLang, 'secondary' => $secondLang],
                'scores' => $scores
            ];
        }

        // Confidence calculation based on dominance margin and evidence strength
        $margin = ($topScore - $secondScore) / max(1.0, $totalScore);
        $evidenceFactor = min(1.0, $topScore / 6.0);
        $confidence = round(min(0.98, max(0.50, 0.55 + ($margin * 0.30) + ($evidenceFactor * 0.15))), 2);

        $isAmbiguous = ($confidence < 0.65 || $wordCount <= 3);

        return [
            'language' => $topLang,
            'confidence' => $confidence,
            'source' => 'local_detector',
            'is_supported' => in_array($topLang, ['es', 'pt', 'en'], true),
            'is_ambiguous' => $isAmbiguous,
            'scores' => $scores
        ];
    }

    /**
     * Post-Generation Language Validator
     * Strictly differentiates strong contradictions vs brief/ambiguous replies
     *
     * @param string $reply
     * @param string $expectedLanguage ('es'|'pt'|'en')
     * @param string $commentText
     * @return array
     */
    public static function validateReplyLanguage(string $reply, string $expectedLanguage, string $commentText = ''): array {
        $clean = trim($reply);
        if (empty($clean)) {
            return [
                'valid' => false,
                'reason' => 'EMPTY_RESPONSE',
                'action' => 'NO_REPLY',
                'reply' => ''
            ];
        }

        $expected = in_array(strtolower($expectedLanguage), ['es', 'pt', 'en'], true) ? strtolower($expectedLanguage) : 'es';
        $words = preg_split('/\s+/u', $clean, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $wordCount = count($words);

        // Check for pure emojis or very short replies (e.g. "É isso. 👏", "Well said.", "Totalmente.")
        if ($wordCount <= 3) {
            return [
                'valid' => true,
                'is_ambiguous' => true,
                'detected_language' => $expected,
                'confidence' => 0.70,
                'reason' => 'SHORT_REPLY_ACCEPTED',
                'action' => 'REPLY',
                'reply' => $clean
            ];
        }

        $detected = self::detectSupportedLanguage($clean);

        // 1. Strong Contradiction Check: If detected language is confirmed in another supported language
        // with high confidence (>= 0.75) and clearly different from expected
        if ($detected['is_supported'] && !$detected['is_ambiguous'] && $detected['language'] !== $expected && $detected['confidence'] >= 0.75) {
            return [
                'valid' => false,
                'is_ambiguous' => false,
                'is_contradiction' => true,
                'detected_language' => $detected['language'],
                'expected_language' => $expected,
                'confidence' => $detected['confidence'],
                'reason' => 'CONTRADICTORY_LANGUAGE',
                'action' => 'NO_REPLY',
                'reply' => ''
            ];
        }

        // 2. Ambiguous or inconclusive detection: keep reply for human review, do not mark as corrupt/invalid
        if ($detected['is_ambiguous'] || $detected['confidence'] < 0.65) {
            return [
                'valid' => true,
                'is_ambiguous' => true,
                'detected_language' => $detected['language'],
                'expected_language' => $expected,
                'confidence' => $detected['confidence'],
                'reason' => 'AMBIGUOUS_LANGUAGE_REVIEW_NEEDED',
                'action' => 'REPLY',
                'requires_human_review' => true,
                'reply' => $clean
            ];
        }

        // 3. Language matches expected
        return [
            'valid' => true,
            'is_ambiguous' => false,
            'detected_language' => $detected['language'],
            'expected_language' => $expected,
            'confidence' => $detected['confidence'],
            'reason' => 'LANGUAGE_MATCHED',
            'action' => 'REPLY',
            'reply' => $clean
        ];
    }

    /**
     * Evaluate if a comment is suitable for Auto-Responder or if it should be marked as SPAM / TOXIC / STICKER
     */
    public static function evaluateCommentSuitability(string $commentText, string $allowedLang = 'any', ?array $attachment = null): array {
        $text = trim($commentText);
        $textLower = mb_strtolower($text, 'UTF-8');

        // 1. Check for Link Spam / Crypto / Bot Promotion / Unsolicited Commercial CTA
        $spamPatterns = [
            'http://', 'https://', 'www.', '.com', '.io', '.xyz', '.net', '.org', 't.me/', 'wa.me/',
            'telegram', 'whatsapp', 'send pic on', 'promote on', 'promote it on', 'dm me on', 'inbox me on',
            'check my bio', 'clic en mi bio', 'ganar dinero', 'inversion segura', 'inversión segura',
            'trabajo desde casa', 'crypto', 'bitcoin', 'binance', 'forex', 'trading bot', 'free followers',
            'ganar seguidores', 'hacks', 'recupero cuentas', 'recuperar cuenta', 'dm us on', 'send dm to',
            'follow us on', 'check out our', 'hire me', 'investment platform', 'tinder', 'onlyfans',
            'compra aquí', 'compra aqui', 'comprar aquí', 'comprar aqui', 'vendo', 'descuento especial',
            'oferta por tiempo limitado', 'sígueme en mi página', 'sigueme en mi pagina', 'sígueme en mi canal',
            'sigueme en mi canal', 'entra a mi canal', 'unete a mi canal', 'únete a mi canal', 'suscríbete a mi canal',
            'subscribete', 'precios por dm', 'informes por dm', 'escríbeme al privado', 'escribeme al privado',
            'gana dinero facil', 'gana dinero fácil', 'trabaja conmigo',
            'ganhe dinheiro facil', 'ganhe dinheiro fácil', 'renda extra', 'trabalhe em casa'
        ];

        foreach ($spamPatterns as $sp) {
            if (str_contains($textLower, $sp)) {
                return [
                    'status' => 'spam',
                    'should_reply' => false,
                    'reason' => '🚫 Marcado como Spam / Bot promocional o enlace externo para revisión',
                    'category' => 'spam',
                    'action' => 'NO_REPLY'
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
            'muérete', 'muerete', 'inútil', 'inutil', 'inútiles', 'payaso', 'payasos', 'asco de cuenta',
            'csm', 'csmr', 'ctm', 'ctmr', 'alv', 'chupala', 'pendejo', 'pendejos', 'pendeja', 'pendejas', 'pendejada',
            // Portuguese toxic & insult terms
            'lixo', 'babaca', 'arrombado', 'otario', 'otário', 'canalha', 'desgraçado', 'desgracado', 'safado',
            'golpista', 'filho da puta', 'vai se foder', 'merda de conta',
            // English toxic & insult terms
            'garbage', 'trash', 'idiot', 'idiots', 'stupid', 'scammer', 'scammers', 'scam', 'asshole',
            'bullshit', 'loser', 'piece of shit', 'fuck off', 'moron'
        ];

        // 1.55 Check for Hostile Bot-Shaming or AI Mockery Attack (Silencio Operativo Inmediato - HARASSMENT)
        $isBotAttack = (bool)preg_match('/\b(esa ia|es una ia|pinche bot|bot mediocre|ni escribir sabe|ia de mierda|ia csmr?|maldita ia|eres un bot|eres una ia)\b/iu', $textLower);
        if ($isBotAttack) {
            return [
                'status' => 'toxic',
                'should_reply' => false,
                'reason' => '🛡️ Silencio Operativo: Ataque o burla hostil hacia el sistema/IA. Bloqueado en Autopilot para no entrar en polémicas ni validar al troll.',
                'category' => 'HARASSMENT',
                'action' => 'NO_REPLY'
            ];
        }

        if (!$isPersonalVenting) {
            foreach ($severeToxicKeywords as $tw) {
                if (preg_match('/\b' . preg_quote($tw, '/') . '\b/iu', $textLower)) {
                    return [
                        'status' => 'toxic',
                        'should_reply' => false,
                        'reason' => '🛡️ Silencio Operativo: Comentario con insultos directos o toxicidad severa detectada. Bloqueado en Autopilot para no alimentar al hater ni darle visibilidad algorítmica.',
                        'category' => 'HARASSMENT',
                        'action' => 'NO_REPLY'
                    ];
                }
            }
        }

        // 2. Multilingual Bot Spam & Malicious Solicitations (ES, PT, EN)
        // Detects actual automated spam/solicitation regardless of language, NEVER penalizing legitimate conversational comments
        $botSpamPatterns = [
            '/\b(?:dm me to (?:invest|earn|get)|send dm to @|check out @|contact @[a-z0-9_]+ on telegram)\b/iu',
            '/\b(?:whatsapp\s*(?:me|us|directly)?\s*[:\+]?\s*\d{7,}|chama no whats|fala no whatsapp)\b/iu',
            '/\b(?:ganhe dinheiro rapido|renda extra com|investimento seguro com|lucro garantido)\b/iu',
            '/\b(?:binary options|forex trading expert|crypto mining|bitcoin trading|trading bot)\b/iu',
            '/\b(?:telegram\s*[:@]\s*[a-z0-9_]{4,}|t\.me\/[a-z0-9_]+)\b/iu',
            '/\b(?:promoted? on @|promote it on @|send pic on @|share on @)\b/iu',
        ];
        foreach ($botSpamPatterns as $bsp) {
            if (preg_match($bsp, $textLower)) {
                return [
                    'status' => 'spam',
                    'should_reply' => false,
                    'reason' => '🚫 Silencio Operativo: Patrón de spam promocional o bot externo detectado (NO_REPLY)',
                    'category' => 'SPAM_BOT',
                    'action' => 'NO_REPLY'
                ];
            }
        }

        // 3. Check for Visual Attachments (Stickers, GIFs, Photos, Flyers)
        if (!empty($attachment)) {
            $attachType = strtolower($attachment['type'] ?? '');
            $isPhotoOrImage = in_array($attachType, ['photo', 'image', 'share', 'album']) || !empty($attachment['media']['image']);

            if ($isPhotoOrImage && $attachType !== 'sticker' && $attachType !== 'animated_image_share') {
                // Check if the image attachment has commercial / promotional signals (Flyer, QR, Ad)
                $imgEval = self::classifyImageAttachment($attachment, $commentText);
                if ($imgEval['is_promotional']) {
                    return [
                        'status' => 'spam',
                        'should_reply' => false,
                        'reason' => '🚫 Silencio Operativo: Imagen clasificada como flyer/afiche promocional o publicidad (NO_REPLY)',
                        'category' => 'PROMOTIONAL_IMAGE',
                        'action' => 'NO_REPLY'
                    ];
                }
                // Legitimate photo/quote image -> allow normal flow
            } else {
                // Regular Sticker / GIF from platform catalogue
                $stickerEval = self::classifyStickerSentiment($attachment, $commentText);
                if ($stickerEval['sentiment'] === 'mocking') {
                    return [
                        'status' => 'ignored',
                        'should_reply' => false,
                        'reason' => '🛡️ Silencio Operativo: Sticker de burla o doble sentido negativo ignorado en Autopilot',
                        'category' => 'mocking_sticker',
                        'action' => 'NO_REPLY'
                    ];
                }
                return [
                    'status' => 'valid',
                    'should_reply' => true,
                    'reason' => '🎨 Sticker amigable de la comunidad (apto para respuesta ágil)',
                    'category' => 'friendly_sticker',
                    'description' => $stickerEval['description'] ?? 'Sticker amigable'
                ];
            }
        } elseif (str_starts_with($text, '[Sticker') || str_starts_with($text, '[GIF')) {
            $stickerEval = self::classifyStickerSentiment([], $commentText);
            if ($stickerEval['sentiment'] === 'mocking') {
                return [
                    'status' => 'ignored',
                    'should_reply' => false,
                    'reason' => '🛡️ Silencio Operativo: Sticker de burla ignorado en Autopilot',
                    'category' => 'mocking_sticker',
                    'action' => 'NO_REPLY'
                ];
            }
            return [
                'status' => 'valid',
                'should_reply' => true,
                'reason' => '🎨 Sticker amigable de la comunidad (apto para respuesta ágil)',
                'category' => 'friendly_sticker',
                'description' => $stickerEval['description'] ?? 'Sticker amigable'
            ];
        }

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
                'category' => 'empty_comment'
            ];
        }

        // 4. Language suitability check
        $detLang = self::detectSupportedLanguage($text);
        if ($allowedLang !== 'any') {
            if ($detLang['is_supported'] && $detLang['language'] !== $allowedLang && $detLang['confidence'] >= 0.70) {
                return [
                    'status' => 'blocked_unsupported_language',
                    'should_reply' => false,
                    'reason' => "🛡️ Silencio Operativo: Idioma '{$detLang['language']}' no admitido por la voz de marca (configurada en '{$allowedLang}').",
                    'category' => 'UNSUPPORTED_LANGUAGE',
                    'action' => 'NO_REPLY',
                    'detected_language' => $detLang['language'],
                    'language_confidence' => $detLang['confidence']
                ];
            }
        }

        // 5. Valid comment
        return [
            'status' => 'valid',
            'should_reply' => true,
            'reason' => '✅ Comentario legítimo apto para responder',
            'category' => 'valid',
            'detected_language' => $detLang['language'],
            'language_confidence' => $detLang['confidence'],
            'is_language_ambiguous' => $detLang['is_ambiguous']
        ];
    }

    /**
     * Detect if an author name is an anonymous placeholder, machine handle, or slang nickname
     */
    public static function isGenericAuthorName(?string $name): bool {
        if ($name === null) return true;
        $clean = mb_strtolower(trim($name), 'UTF-8');
        if (empty($clean)) return true;

        // Handles with numbers are usernames/gamertags, NOT clean first names (e.g. Samuelongo380, Juan123)
        if (preg_match('/\d/', $clean)) return true;

        // Slang, gamertag or meme suffixes/words
        if (preg_match('/(longo|gamer|master|bot|pro|play|tv|yt|tiktok|page|vip|club|team|stream|gaming|official|oficial)$/i', $clean)) return true;

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
        // If the handle contains numbers, it's not a verified personal first name
        if (preg_match('/\d/', $raw)) {
            return '';
        }
        $parts = preg_split('/[\s_\.\-]+/u', $raw);
        $first = $parts[0] ?? '';
        if (self::isGenericAuthorName($first) || mb_strlen($first, 'UTF-8') < 2 || mb_strlen($first, 'UTF-8') > 15) {
            return '';
        }
        // Must contain only alphabetical characters (Spanish and Latin letters)
        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ]+$/u', $first)) {
            return '';
        }
        return mb_convert_case($first, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Detect follower gender context (female, male, neutral) strictly from comment text.
     * HERMES v2.1: NEVER assume gender or name from author profile, handle or Meta profile.
     * If the comment text does not explicitly declare gender, status is strictly 'neutral'.
     * Returns: ['gender' => 'female'|'male'|'neutral', 'first_name' => string, 'confidence' => float, 'reason' => string]
     */
    public static function detectGenderContext(?string $authorName, string $commentText = ''): array {
        $commentLower = mb_strtolower($commentText, 'UTF-8');

        // Extract name ONLY if explicitly written by user in the comment text
        $explicitFirstName = '';
        if (preg_match('/\b(me llamo|mi nombre es|soy)\s+([a-záéíóúñ]+)\b/iu', $commentText, $mName)) {
            $explicitFirstName = mb_convert_case($mName[2], MB_CASE_TITLE, 'UTF-8');
        }

        // Text explicit markers take absolute priority - ONLY checked in comment text
        if (preg_match('/\b(soy mujer|como mujer|de mujer|siendo mujer|agradecida|cansada|encantada|preparada|dispuesta|sola|tranquila|segura|abrumada|orgullosa|madre|abuela|esposa|chica|niña|mujer)\b/iu', $commentLower)) {
            return [
                'gender' => 'female',
                'first_name' => $explicitFirstName,
                'confidence' => 0.98,
                'reason' => 'Autoidentificación o concordancia gramatical femenina explícita en el comentario'
            ];
        }

        if (preg_match('/\b(soy hombre|como hombre|de hombre|siendo hombre|agradecido|cansado|encantado|preparado|dispuesto|solo|tranquilo|seguro|abrumado|orgulloso|padre|abuelo|esposo|chico|niño|hombre)\b/iu', $commentLower)) {
            return [
                'gender' => 'male',
                'first_name' => $explicitFirstName,
                'confidence' => 0.95,
                'reason' => 'Autoidentificación o concordancia gramatical masculina explícita en el comentario'
            ];
        }

        // HERMES v2.1 Rule: Never infer gender from authorName or profile handle. Strictly neutral.
        return [
            'gender' => 'neutral',
            'first_name' => $explicitFirstName,
            'confidence' => 1.0,
            'reason' => 'Género neutro (no declarado explícitamente en el comentario)'
        ];
    }

    /**
     * Hermes v2: Evaluate Image Attachment for Promotional / Commercial Spam
     * Differentiates promotional flyers / ads / QR codes from legitimate user photos or stoic quote images.
     */
    public static function classifyImageAttachment(array $attachment, string $commentText = '', ?string $apiKey = null): array {
        $type = strtolower($attachment['type'] ?? '');
        $title = strtolower($attachment['title'] ?? '');
        $desc = strtolower($attachment['description'] ?? '');
        $url = strtolower($attachment['url'] ?? '');
        $targetUrl = strtolower($attachment['target']['url'] ?? '');
        $imgSrc = $attachment['media']['image']['src'] ?? '';
        $textLower = mb_strtolower($commentText, 'UTF-8');
        $fullMetadata = "$type $title $desc $url $targetUrl $textLower";

        // 1. Metadata & Text Promotional Signals (Flyer, Event, QR, Sales, Nibiru, Conspiracies, Commercial Channels)
        $promoPatterns = [
            'flyer', 'cartel', 'promocion', 'promoción', 'descuento', 'precio', 'qr', 'codigo qr', 'código qr',
            'nibiru', 'planeta x', 'fin se acerca', 'conferencia', 'evento', 'taller', 'curso', 'seminario',
            'inversión', 'inversion', 'crypto', 'afiche', 'publicidad', 'anuncio', 'canal de telegram',
            'canal de youtube', 'contactanos', 'contáctanos', 'informes al', 'inbox me', 'send pic',
            'ciencia del energismo', 'despierta', 'siguenos', 'síguenos'
        ];

        foreach ($promoPatterns as $pat) {
            if (str_contains($fullMetadata, $pat)) {
                return [
                    'is_promotional' => true,
                    'status' => 'spam',
                    'reason' => "Metadatos o texto con patrón promocional/publicitario: '$pat'",
                    'description' => 'Flyer o cartel promocional publicitario'
                ];
            }
        }

        // 2. Vision Check via OpenRouter if image URL is available and key is configured
        $resolvedApiKey = !empty($apiKey) ? $apiKey : Settings::get('openrouter_api_key', '');
        if (!empty($resolvedApiKey) && !empty($imgSrc) && filter_var($imgSrc, FILTER_VALIDATE_URL)) {
            try {
                $payload = [
                    'model' => 'openai/gpt-4o-mini',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => 'Analiza esta imagen adjunta a un comentario de Facebook/Instagram. ¿Es un FLYER/CARTEL PUBLICITARIO, ANUNCIO COMERCIAL, AFICHE DE EVENTO, CÓDIGO QR O PROPAGANDA COMERCIAL/SPAM ("promotional_ad")? ¿O es una IMAGEN LEGÍTIMA de un seguidor como una frase estoica/reflexión personal, fotografía personal sin venta o meme amigable ("legitimate_content")? Responde en JSON estricto: {"category": "promotional_ad" | "legitimate_content", "description": "breve descripcion"}'
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => ['url' => $imgSrc]
                                ]
                            ]
                        ]
                    ],
                    'response_format' => ['type' => 'json_object']
                ];

                $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $resolvedApiKey
                ]);
                curl_setopt($ch, CURLOPT_TIMEOUT, 6);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                $res = curl_exec($ch);
                curl_close($ch);

                if ($res) {
                    $json = json_decode($res, true);
                    $content = $json['choices'][0]['message']['content'] ?? '';
                    $parsed = json_decode($content, true);
                    if (!empty($parsed['category']) && $parsed['category'] === 'promotional_ad') {
                        return [
                            'is_promotional' => true,
                            'status' => 'spam',
                            'reason' => 'Visión AI detectó flyer o anuncio promocional: ' . ($parsed['description'] ?? ''),
                            'description' => $parsed['description'] ?? 'Flyer publicitario'
                        ];
                    }
                }
            } catch (Throwable $e) {
                error_log("Image Vision Check Error: " . $e->getMessage());
            }
        }

        // Default: If no commercial/promotional signals, treat as legitimate user image
        return [
            'is_promotional' => false,
            'status' => 'valid',
            'reason' => 'Imagen legítima sin señales de publicidad comercial',
            'description' => 'Imagen legítima de seguidor'
        ];
    }

    /**
     * Classify Sticker or GIF sentiment: Friendly vs Mocking/Negative
     * Uses metadata analysis + OpenRouter Vision fallback for image inspection
     */
    public static function classifyStickerSentiment(array $attachment, string $commentText = '', ?string $apiKey = null): array {
        $type = strtolower($attachment['type'] ?? '');
        $title = strtolower($attachment['title'] ?? '');
        $desc = strtolower($attachment['description'] ?? '');
        $url = strtolower($attachment['url'] ?? '');
        $targetUrl = strtolower($attachment['target']['url'] ?? '');
        $imgSrc = $attachment['media']['image']['src'] ?? '';
        $fullMetadata = "$type $title $desc $url $targetUrl $commentText";

        // 1. Text or metadata mocking patterns
        $mockingPatterns = [
            'haha', 'hahaha', 'jaja', 'jajaja', 'jeje', 'jejeje', 'lol', 'lmao', 'xd', 'rofl',
            'einstein', 'tongue', 'lengua', 'burla', 'burlon', 'burlón', 'sarcas', 'chiste', 'joke',
            'clown', 'payaso', 'laugh', 'laughing', 'giggle', 'snicker', 'mock', 'mocking', 'troll',
            'ridicule', 'carcajada', 'muerto de risa', 'morir de risa'
        ];

        foreach ($mockingPatterns as $pat) {
            if (preg_match('/\b' . preg_quote($pat, '/') . '\b/iu', $fullMetadata)) {
                return [
                    'sentiment' => 'mocking',
                    'reason' => "Patrón de burla o mofa detectado en metadatos: '$pat'",
                    'description' => $title ?: 'Sticker/GIF con patrón de risa o mofa'
                ];
            }
        }

        if (preg_match('/[😂🤣😹😜🤪😝🤡]/u', $commentText)) {
            return [
                'sentiment' => 'mocking',
                'reason' => 'Emojis de burla o mofa detectados junto al sticker',
                'description' => 'Comentario con emojis de burla'
            ];
        }

        // 2. Explicit friendly patterns in metadata
        $friendlyPatterns = [
            'cierto', 'agree', 'clap', 'clapping', 'handshake', 'aplauso', 'apreton', 'apretón',
            'deal', 'pusheen', 'cat', 'hug', 'love', 'corazon', 'corazón', 'gracias', 'thanks',
            'star', 'estrella', 'flor', 'flower', 'respect', 'respeto', 'strength', 'fuerza',
            'support', 'apoyo', '100%', 'thumbs up', 'firme'
        ];

        foreach ($friendlyPatterns as $fpat) {
            if (preg_match('/\b' . preg_quote($fpat, '/') . '\b/iu', $fullMetadata)) {
                return [
                    'sentiment' => 'friendly',
                    'reason' => "Patrón amigable detectado en metadatos: '$fpat'",
                    'description' => $title ?: 'Sticker de apoyo o acuerdo'
                ];
            }
        }

        // 3. Fast Vision Inspection via OpenRouter if image URL is available and key exists
        $resolvedApiKey = !empty($apiKey) ? $apiKey : Settings::get('openrouter_api_key', '');
        if (!empty($resolvedApiKey) && !empty($imgSrc) && (filter_var($imgSrc, FILTER_VALIDATE_URL))) {
            try {
                $payload = [
                    'model' => 'openai/gpt-4o-mini',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => 'Analiza este sticker o imagen de un comentario de Facebook en una página de filosofía estoica y superación personal. ¿Qué muestra la imagen? ¿Es un sticker de apoyo/amigable (ej. aplausos, apretón de manos, emoción, ternura, felicitación, flores, respeto) o es de burla/carcajada/sarcasmo (ej. risa descarada "HA HA HA", mofa, burla, meme negativo)? Responde en formato JSON estricto: {"description": "...", "sentiment": "friendly" o "mocking"}'
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => ['url' => $imgSrc]
                                ]
                            ]
                        ]
                    ],
                    'response_format' => ['type' => 'json_object']
                ];

                $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $resolvedApiKey
                ]);
                curl_setopt($ch, CURLOPT_TIMEOUT, 6);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                $res = curl_exec($ch);
                curl_close($ch);

                if ($res) {
                    $json = json_decode($res, true);
                    $content = $json['choices'][0]['message']['content'] ?? '';
                    $parsed = json_decode($content, true);
                    if (!empty($parsed['sentiment'])) {
                        $sent = strtolower($parsed['sentiment']) === 'mocking' ? 'mocking' : 'friendly';
                        return [
                            'sentiment' => $sent,
                            'reason' => 'Análisis de visión AI: ' . ($parsed['description'] ?? ''),
                            'description' => $parsed['description'] ?? ''
                        ];
                    }
                }
            } catch (Throwable $e) {
                error_log("Sticker Vision Check Error: " . $e->getMessage());
            }
        }

        // Default: If no mocking detected, treat as friendly community reaction
        return [
            'sentiment' => 'friendly',
            'reason' => 'Reacción visual amigable de la comunidad',
            'description' => 'Sticker amigable'
        ];
    }

    /**
     * Failsafe Post-Processing Guard: Ensure no gender vocatives or assumptions slip through when undeclared.
     * HERMES v2.1: Prohibits hermano, hermana, amigo, amiga, guerrero, guerrera, campeón, campeona, bienvenido, bienvenida.
     */
    public static function sanitizeGenderVocatives(string $reply, string $gender, string $firstName = ''): string {
        if ($gender === 'female') {
            $replacement = !empty($firstName) ? $firstName : '';
            $reply = preg_replace('/\bhermano\b/iu', $replacement, $reply);
            $reply = preg_replace('/\bhermanos\b/iu', '', $reply);
            $reply = preg_replace('/\bamigo\b/iu', $replacement, $reply);
            $reply = preg_replace('/\bamigos\b/iu', '', $reply);
            $reply = preg_replace('/\bcamarada\b/iu', $replacement, $reply);
            $reply = preg_replace('/\bcamaradas\b/iu', '', $reply);
            $reply = preg_replace('/\bcompa\b/iu', $replacement, $reply);
            $reply = preg_replace('/\bbro\b/iu', $replacement, $reply);
            $reply = preg_replace('/\brey\b/iu', '', $reply);
            $reply = preg_replace('/\bguerrero\b/iu', '', $reply);
            $reply = preg_replace('/\bcampe[oó]n\b/iu', '', $reply);
            $reply = preg_replace('/\bbienvenido\b/iu', 'gracias por sumarte', $reply);
        } elseif ($gender === 'male') {
            $replacement = !empty($firstName) ? $firstName : '';
            // Hermes v2.1: Keep tone sober and avoid hype vocatives even for men
            $reply = preg_replace('/\b(rey|campe[oó]n|guerrero|bro|compa)\b/iu', $replacement, $reply);
        } else {
            // Strictly eliminate any assumed gender vocative to keep the message universal and clean
            $patternStart = '/(^|[.!?]\s*)\b(amigo|amiga|amigos|amigas|hermano|hermana|hermanos|hermanas|guerrero|guerrera|guerreros|guerreras|campe[oó]n|campeona|campeones|campeonas|bienvenido|bienvenida|bienvenidos|bienvenidas|nuevo guerrero|nueva guerrera|camarada|camaradas|compa|compas|bro|rey|reina)\b\s*,?\s*/iu';
            $patternMiddle = '/,?\s*\b(amigo|amiga|amigos|amigas|hermano|hermana|hermanos|hermanas|guerrero|guerrera|guerreros|guerreras|campe[oó]n|campeona|campeones|campeonas|bienvenido|bienvenida|bienvenidos|bienvenidas|nuevo guerrero|nueva guerrera|camarada|camaradas|compa|compas|bro|rey|reina)\b\s*,?/iu';
            $reply = preg_replace($patternStart, '$1', $reply);
            $reply = preg_replace($patternMiddle, '', $reply);
            $reply = preg_replace('/^\s*,\s*/u', '', $reply);
            // Capitalize sentence beginnings if a leading vocative was stripped
            $reply = preg_replace_callback('/(^|[.!?]\s+)([a-záéíóúñ])/u', function($m) {
                return $m[1] . mb_strtoupper($m[2], 'UTF-8');
            }, $reply);
        }
        $reply = preg_replace('/\s+([.,;:!?])/u', '$1', $reply);
        $reply = preg_replace('/\s{2,}/u', ' ', $reply);
        return trim($reply);
    }

    /**
     * Universal Intent & Sentiment Commercial Classifier
     */
    public static function analyzeComment(string $commentText, string $postCaption = '', int $likesCount = 0, string $authorName = '', ?array $attachment = null, string $allowedLang = 'any'): array {
        $suitability = self::evaluateCommentSuitability($commentText, $allowedLang, $attachment);
        $langDetection = self::detectSupportedLanguage($commentText);
        if (!$suitability['should_reply']) {
            $isToxic = ($suitability['status'] === 'toxic' || $suitability['category'] === 'toxic_hostile' || $suitability['category'] === 'HARASSMENT');
            return [
                'action' => 'NO_REPLY',
                'sentiment' => $isToxic ? 'toxic' : ($suitability['status'] === 'spam' ? 'spam' : 'neutral'),
                'intent' => $suitability['category'],
                'highlight_score' => $isToxic ? 15 : ($suitability['status'] === 'spam' ? 10 : 25),
                'commercial_priority' => $isToxic ? 10 : 20,
                'is_highlighted' => 0,
                'highlight_reason' => $suitability['reason'],
                'autopilot_ready' => false,
                'autopilot_status' => 'ignored',
                'autopilot_reason' => $suitability['reason'],
                'detected_keywords' => $isToxic ? ['toxic_severe'] : [],
                'detected_language' => $langDetection['language'],
                'language_confidence' => $langDetection['confidence'],
                'language_source' => $langDetection['source'],
                'is_language_supported' => $langDetection['is_supported'],
                'is_language_ambiguous' => $langDetection['is_ambiguous']
            ];
        }

        // Special handling for friendly sticker reactions
        if ($suitability['category'] === 'friendly_sticker') {
            return [
                'action' => 'REPLY',
                'sentiment' => 'positive',
                'intent' => 'friendly_sticker_reaction',
                'highlight_score' => 80,
                'commercial_priority' => 75,
                'is_highlighted' => 0,
                'highlight_reason' => $suitability['reason'],
                'autopilot_ready' => true,
                'autopilot_status' => 'approved',
                'autopilot_reason' => 'Sticker amigable de la comunidad aprobado para respuesta ágil',
                'detected_keywords' => ['sticker_friendly']
            ];
        }

        // Special handling for pure emoji reactions (HERMES v2: EMOJI_ONLY intent)
        if ($suitability['category'] === 'emoji_reaction') {
            return [
                'action' => 'REPLY',
                'sentiment' => 'positive',
                'intent' => 'EMOJI_ONLY',
                'highlight_score' => 75,
                'commercial_priority' => 70,
                'is_highlighted' => 0,
                'highlight_reason' => 'Reacción de solo emojis de la comunidad (política minimalista 1-2 emojis o máx 3 palabras)',
                'autopilot_ready' => true,
                'autopilot_status' => 'ready',
                'autopilot_reason' => 'Reacción positiva con emojis lista para auto-responder',
                'detected_keywords' => ['emoji_only']
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

        // 0.65 Existential Doubt, Hardship, Grief or Pain (Miseria, sufrir, para qué vivir, dolor, tristeza, vacío)
        $existentialPatterns = [
            'miseria', 'para que vivir', 'para qué vivir', 'de que sirve', 'de qué sirve',
            'tanto dolor', 'mucho dolor', 'sufrir tanto', 'para que sufrir', 'para qué sufrir',
            'no le veo sentido', 'sin sentido', 'vida de mierda', 'cansado de vivir',
            'ganas de tirar la toalla', 'rendirse', 'no puedo mas', 'no puedo más',
            'todo sale mal', 'para que luchar', 'para qué luchar', 'desesperanza',
            'angustia', 'soledad', 'vacío', 'vacio'
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

        // 0.9 Humor, Banter, Memes & Sarcasm (Carnitas, al cazo, al sartén, cerdo, puerco, memes, chistes, ironía callejera)
        $humorPatterns = [
            'carnitas', 'al cazo', 'al sartén', 'al sarten', 'a la cazuela', 'al matadero', 'lo hubieras hecho',
            'lo hubiera hecho', 'es un cerdo', 'al asador', 'jajaja', 'jejeje', 'jajaj', 'jaja', 'jeje', 'xd', 'lol', 'lmao',
            'qué risa', 'que risa', 'morí de risa', 'mori de risa', 'me dio risa', 'me dió risa', 'se mamó',
            'se mamo', 'te mamaste', 'no mames', 'no manches', 'tremendo personaje', 'chistoso', 'burlón',
            'burlon', 'payaso', 'meme', 'sacar los prohibidos', 'se pasó', 'se paso', 'buena esa'
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

        // Detect Existential Doubt, Hardship or Suffering
        $foundExistential = [];
        foreach ($existentialPatterns as $p) {
            if (str_contains($textLower, $p)) {
                $foundExistential[] = $p;
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

        // Detect Humor, Banter, Memes & Irony
        $foundHumor = [];
        foreach ($humorPatterns as $p) {
            if (str_contains($textLower, $p)) {
                $foundHumor[] = $p;
            }
        }
        $hasHumorEmoji = (bool)preg_match('/[😝😜🤪😂🤣😆😹🤡]/u', $commentText);

        // Check if there is an explicit question or commercial buying inquiry
        $hasQuestionMark = str_contains($commentText, '?') || str_contains($commentText, '¿');
        $hasBuyingTerm   = (bool)preg_match('/\b(precio|costo|planes|comprar|cuotas|link de compra|cómo compro|donde compro)\b/iu', $textLower);
        $hasQuestionWord = (bool)preg_match('/\b(cómo|cuánto|cuanto|dónde|cuál|cual|por qué)\b/iu', $textLower) && 
                           ($hasQuestionMark || $hasBuyingTerm || str_starts_with($textLower, 'cómo ') || str_starts_with($textLower, 'como ') || str_starts_with($textLower, 'donde ') || str_starts_with($textLower, 'dónde '));
        $hasQuestion     = $hasQuestionMark || $hasBuyingTerm || $hasQuestionWord;

        // ══════════════════════════════════════════════════════════════════════
        // HERMES v2 Pattern Detectors (Explicit Taxonomy Calibration)
        // ══════════════════════════════════════════════════════════════════════

        // 1. Troll Provocation (Mocking without constructive argument -> NO_REPLY)
        $isTrollProvocation = (bool)preg_match('/\b(otra cuenta de frases|frases motivacionales vac[ií]as|frases vac[ií]as|puro humo|vendehumos|vende humo|pura payasada|filosof[ií]a barata|payasos|payaso|charlatanes|charlat[aá]n)\b/iu', $textLower)
            || (preg_match('/[😂🤣😹]/u', $commentText) && preg_match('/\b(cuenta|frases|humo|payaso|chiste|tonter[ií]a)\b/iu', $textLower));

        // 2. Explicit New Follower (Requires explicit statement, never assume on brief words)
        $newFollowerPatterns = [
            'te sigo', 'los sigo', 'nueva por aquí', 'nueva por aqui', 'nuevo por aquí', 'nuevo por aqui',
            'acabo de seguirte', 'acabo de seguir la página', 'acabo de seguir la pagina',
            'soy nueva seguidora', 'soy nuevo seguidor', 'nueva seguidora', 'nuevo seguidor',
            'empecé a seguirte', 'empece a seguirte', 'primera vez que veo tu página',
            'primera vez que veo tu pagina', 'me uno a la página', 'me uno a la pagina', 'me acabo de unir'
        ];
        $isExplicitNewFollower = false;
        foreach ($newFollowerPatterns as $nfp) {
            if (str_contains($textLower, $nfp)) {
                $isExplicitNewFollower = true;
                break;
            }
        }

        // 3. Greeting Only (Cordial greetings under 5 words)
        $isGreetingOnly = (bool)preg_match('/^(hola|holas|buenos d[ií]as|buen d[ií]a|buenas tardes|buenas noches|saludos|un saludo|bendiciones|muchas bendiciones)[!.\s\p{P}]*$/iu', trim($commentText));

        // 4. Disagreement (Reasoned disagreement with post thesis -> CAN REPLY with stoic distinction)
        $isDisagreement = (bool)preg_match('/\b(no estoy de acuerdo|no comparto|discrepo|eso no es as[ií]|no es as[ií]|no creo que sea|aguantarlo todo no|no significa aguantar|no todo se aguanta|no se trata de aguantar)\b/iu', $textLower);

        // 5. Criticism (Critique of depth, phrasing or oversimplification -> CAN REPLY with sobriety)
        $isCriticism = (bool)preg_match('/\b(demasiado simplista|simplista|fuera de contexto|no creo que funcione as[ií]|muy superficial|falta profundizar|no funciona as[ií]|mal interpretado|interpretaci[oó]n err[oó]nea|demasiado b[aá]sico|frase incompleta)\b/iu', $textLower);

        // 6. Brief Agreement (Validation of truth, 1 to 5 words, e.g. "Importante", "Clave", "Exacto")
        $briefAgreementPatterns = [
            'importante', 'clave', 'fundamental', 'necesario', 'exacto', 'exactamente',
            'así es', 'asi es', 'totalmente', 'tal cual', 'muy cierto', 'cierto', 'sierto',
            'de acuerdo', 'sin duda', 'correcto', 'total', 'es verdad', 'gran verdad',
            'pura verdad', '100%', '100', 'muy real', 'así mismo', 'asi mismo'
        ];
        $isBriefAgreement = false;
        $cleanWords = preg_split('/\s+/u', trim($commentText), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($cleanWords) <= 5 && !$isDisagreement && !$isCriticism) {
            foreach ($briefAgreementPatterns as $bap) {
                if (str_contains($textLower, $bap)) {
                    $isBriefAgreement = true;
                    break;
                }
            }
        }

        // 7. Personal Story / Struggle (Anecdote of loss, personal trial, fatigue, job loss)
        $charCount = mb_strlen(trim($commentText), 'UTF-8');
        $hasPersonalStory = (bool)preg_match('/\b(cuando perdí|cuando perdi|mi trabajo|mi condición|mi condicion|falta de concentración|falta de concentracion|durante años|durante anos|con el tiempo entendí|con el tiempo entendi|he aprendido|aprendí que|aprendi que|en mi caso|mi experiencia|me costó|me costo|mi vida|mi familia|mi dolor|mi enfermedad|mi situación|mi situacion|estoy pasando por|lo viví|lo vivi|me pasó|me paso)\b/iu', $textLower)
            || ($charCount > 70 && preg_match('/\b(entendí|entendi|aprendí|aprendi|sentí|senti|descubrí|descubri|pensé|pense|sufrí|sufri|luché|luche)\b/iu', $textLower));

        // Priority Classification (Layer 1 & Layer 2)
        if ($isTrollProvocation) {
            $sentiment = 'negative';
            $intent = 'TROLL_PROVOCATION';
            $score = 30;
            $highlightReason = '🛡️ Silencio Operativo: Provocación troll superficial sin contenido constructivo (NO_REPLY)';
            $autopilotReady = false;
            $autopilotStatus = 'ignored';
            $autopilotReason = 'Silencio operativo: Provocación troll superficial sin contenido constructivo';
        } elseif ($isExplicitNewFollower) {
            $sentiment = 'positive';
            $intent = 'NEW_FOLLOWER';
            $score = 95;
            $highlightReason = '🏛️ Nuevo Seguidor Verificado: Señal explícita de seguimiento para bienvenida sobria';
            $autopilotReady = true;
            $autopilotStatus = 'ready';
            $autopilotReason = '✔ Apto para Autopilot (Bienvenida sobria a Fortaleza Imparable)';
        } elseif ($hasPersonalStory) {
            $sentiment = 'reflective_pain';
            $intent = 'PERSONAL_STORY';
            $score = 98;
            $highlightReason = '🏛️ Experiencia o Lucha Personal: Anécdota o desahogo profundo que amerita respuesta estoica estructurada';
            $autopilotReady = true;
            $autopilotStatus = 'ready';
            $autopilotReason = '✔ Apto para Autopilot (Reconocimiento + Idea Central + Reflexión Breve)';
        } elseif ($isDisagreement) {
            $sentiment = 'neutral';
            $intent = 'DISAGREEMENT';
            $score = 85;
            $highlightReason = '⚖️ Desacuerdo Respetuoso: Cuestionamiento de perspectiva; responder con distinción estoica serena';
            $autopilotReady = true;
            $autopilotStatus = 'ready';
            $autopilotReason = '✔ Apto para Autopilot (Distinción serena sin confrontar ni superioridad)';
        } elseif ($isCriticism) {
            $sentiment = 'neutral';
            $intent = 'CRITICISM';
            $score = 80;
            $highlightReason = '🔍 Crítica Constructiva al Contenido: Responder con sobriedad y claridad conceptual';
            $autopilotReady = true;
            $autopilotStatus = 'ready';
            $autopilotReason = '✔ Apto para Autopilot (Respuesta sobria y clara a la crítica)';
        } elseif ($isGreetingOnly) {
            $sentiment = 'positive';
            $intent = 'GREETING';
            $score = 75;
            $highlightReason = '👋 Saludo Cordial Breve: Responder con saludo cordial breve de 4 a 8 palabras';
            $autopilotReady = true;
            $autopilotStatus = 'ready';
            $autopilotReason = '✔ Apto para Autopilot (Saludo breve y sobrio)';
        } elseif ($isBriefAgreement) {
            $sentiment = 'positive';
            $intent = 'BRIEF_AGREEMENT';
            $score = 88;
            $highlightReason = '🏛️ Acuerdo / Validación Breve: Comentario de 1 palabra o frase corta confirmando el valor del post';
            $autopilotReady = true;
            $autopilotStatus = 'ready';
            $autopilotReason = '✔ Apto para Autopilot (Ratificación estoica concisa de 4 a 8 palabras sin bienvenida)';
        } elseif (!empty($foundVenting)) {
            $sentiment = 'neutral';
            $intent = 'emotional_venting_resilience';
            $score = 92;
            $highlightReason = '🧠 Desahogo Emocional & Resiliencia: Usuario expresando frustración con el entorno; responder con serenidad y contención mental';
            $keywords = $foundVenting;
            $autopilotReady = true;
            $autopilotStatus = 'ready';
            $autopilotReason = '✔ Apto para Autopilot (Contención estoica sobria sin validar el insulto ni usar fiesta)';
        } elseif (!empty($foundExistential)) {
            $sentiment = 'reflective_pain';
            $intent = 'existential_doubt';
            $score = 95;
            $highlightReason = '🏛️ Duda Existencial o Dolor: Cuestionamiento sobre la miseria, sufrimiento o sentido de la vida; responder con compasión serena y fortaleza estoica, CERO agradecimientos superficiales';
            $keywords = $foundExistential;
            $autopilotReady = true;
            $autopilotStatus = 'ready';
            $autopilotReason = '✔ Apto para Autopilot (Respuesta empática, sabia y reflexiva con filosofía estoica)';
        } elseif (!empty($foundHumor) || ($hasHumorEmoji && empty($foundVenting))) {
            $sentiment = 'positive';
            $intent = 'humor_banter_joke';
            $score = 86;
            $highlightReason = '😄 Humor, Broma o Banter: Comentario con tono cómico, meme o broma; responder con complicidad, ingenio y risa sin ponerse solemne ni hacer cuestionarios';
            $keywords = array_merge($foundHumor, $hasHumorEmoji ? ['[emoji_humor]'] : []);
            $autopilotReady = true;
            $autopilotStatus = 'ready';
            $autopilotReason = '✔ Apto para Autopilot (Respuesta fresca con complicidad y humor)';
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

        // Question mark boost & QUESTION intent
        if (str_contains($commentText, '?') || str_contains($commentText, '¿')) {
            if ($sentiment === 'neutral' || $intent === 'general' || $intent === 'general_conversation') {
                $sentiment = 'question';
                $intent = 'QUESTION';
                $score = 85;
                $highlightReason = '❓ Pregunta de la comunidad que espera respuesta';
                $autopilotReady = true;
                $autopilotStatus = 'ready';
                $autopilotReason = '✔ Apto para Autopilot (Respuesta a pregunta conceptual/práctica)';
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

        $action = ($intent === 'TROLL_PROVOCATION' || $intent === 'HARASSMENT' || (!$autopilotReady && $autopilotStatus === 'ignored')) ? 'NO_REPLY' : 'REPLY';

        return [
            'action' => $action,
            'sentiment' => $sentiment,
            'intent' => $intent,
            'highlight_score' => $score,
            'commercial_priority' => $score,
            'is_highlighted' => $isHighlighted,
            'highlight_reason' => $highlightReason,
            'autopilot_ready' => $autopilotReady,
            'autopilot_status' => $autopilotStatus,
            'autopilot_reason' => $autopilotReason,
            'detected_keywords' => $keywords,
            'detected_language' => $langDetection['language'],
            'language_confidence' => $langDetection['confidence'],
            'language_source' => $langDetection['source'],
            'is_language_supported' => $langDetection['is_supported'],
            'is_language_ambiguous' => $langDetection['is_ambiguous']
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
        if (str_contains($capLower, 'minamoto') || str_contains($capLower, 'yoshitsune')) {
            return 'minamoto';
        }
        if (str_contains($capLower, 'musashi') || str_contains($capLower, 'miyamoto')) {
            return 'musashi';
        }
        if (str_contains($capLower, 'sun tzu') || str_contains($capLower, 'sun-tzu') || str_contains($capLower, 'suntzu')) {
            return 'sun_tzu';
        }
        if (str_contains($capLower, 'nietzsche')) {
            return 'nietzsche';
        }
        return 'general';
    }

    /**
     * Generate 3 Universal AI response variations:
     * 1. 🤝 Conexión & Fraternidad (Cálida, humana, de comunidad)
     * 2. 🏛️ Sabiduría & Fortaleza Estoica (Profunda, filosófica, autodominio, templanza) [clave interna 'conversion']
     * 3. ⚡ Impulso & Determinación (Motivadora, disciplina mental, resiliencia) [clave interna 'support']
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
        // Hard constraint: NEVER hallucinate or assume user names. Only use name if explicit in comment text
        $explicitName = '';
        if (preg_match('/\b(me llamo|mi nombre es|soy)\s+([a-záéíóúñ]+)\b/iu', $commentText, $mName)) {
            $explicitName = mb_convert_case($mName[2], MB_CASE_TITLE, 'UTF-8');
        }
        $nameVocative = !empty($explicitName) ? " $explicitName" : '';

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

        // Dynamic Human-in-the-Loop Continuous Learning Memory
        $brandVoiceId = (int)($brandVoice['id'] ?? 1);
        $learningExamples = $runtimeOverrides['learning_examples'] ?? self::fetchRecentLearningExamples($pdo, $targetUserId, $brandVoiceId, 4);

        $userAiConfig = null;
        if ($targetUserId > 0) {
            try {
                $uStmt = $pdo->prepare("SELECT id, role, email, ai_model, max_tokens, used_tokens FROM users WHERE id = :id LIMIT 1");
                $uStmt->execute([':id' => $targetUserId]);
                $userAiConfig = $uStmt->fetch(PDO::FETCH_ASSOC);
            } catch (Throwable $e) {}
        }

        $aiProvider = $runtimeOverrides['ai_provider'] ?? Settings::get('ai_provider', 'openrouter');
        $openrouterKey = $runtimeOverrides['openrouter_api_key'] ?? Settings::get('openrouter_api_key', '');
        
        // Priority: runtime override > user's assigned model from admin > system setting
        $userAssignedModel = !empty($userAiConfig['ai_model']) ? trim($userAiConfig['ai_model']) : '';
        if (!empty($userAssignedModel)) {
            if ($userAssignedModel === 'heuristic') {
                $aiProvider = 'heuristic';
            } else {
                $openrouterModel = $runtimeOverrides['openrouter_model'] ?? $userAssignedModel;
            }
        } else {
            $openrouterModel = $runtimeOverrides['openrouter_model'] ?? Settings::get('openrouter_model', 'nousresearch/hermes-3-llama-3.1-70b');
        }

        // Normalize obsolete slugs from OpenRouter
        if ($openrouterModel === 'anthropic/claude-3.5-sonnet' || $openrouterModel === 'anthropic/claude-3-5-sonnet') {
            $openrouterModel = 'anthropic/claude-sonnet-4.5';
        }

        // Check user token quota (Admins or accounts with max_tokens <= 0 have unlimited quota)
        $userRole = $userAiConfig['role'] ?? '';
        $maxTokens = (int)($userAiConfig['max_tokens'] ?? 50000);
        $usedTokens = (int)($userAiConfig['used_tokens'] ?? 0);
        $isTokensExhausted = ($userRole !== 'admin') && ($maxTokens > 0 && $usedTokens >= $maxTokens);

        // Resolve Brand Voice language & Comment Language Detection
        $configuredLanguage = $runtimeOverrides['language'] ?? ($brandVoice['language'] ?? 'es');
        $langDetection = self::detectSupportedLanguage($commentText);

        // Resolve Final Target Language:
        $resolvedLanguage = 'es';
        $isAmbiguousLanguage = false;

        if ($configuredLanguage === 'any') {
            if ($langDetection['is_supported'] && $langDetection['confidence'] >= 0.65) {
                $resolvedLanguage = $langDetection['language'];
            } elseif (!empty($langDetection['scores'])) {
                // If scores indicate a clear leader among supported languages (e.g. English quotes with multi greetings)
                $scores = $langDetection['scores'];
                arsort($scores);
                $top = array_key_first($scores);
                if (in_array($top, ['es', 'pt', 'en'], true) && $scores[$top] >= 1.2) {
                    $resolvedLanguage = $top;
                    $isAmbiguousLanguage = ($langDetection['confidence'] < 0.65);
                } else {
                    $resolvedLanguage = 'es';
                    $isAmbiguousLanguage = true;
                }
            } else {
                $resolvedLanguage = 'es';
                $isAmbiguousLanguage = true;
            }
        } else {
            $resolvedLanguage = in_array($configuredLanguage, ['es', 'pt', 'en'], true) ? $configuredLanguage : 'es';
        }

        // Pre-Flight Suitability & Safety Gate (HERMES v2.1 Fail-Closed)
        $attachment = $runtimeOverrides['attachment'] ?? null;
        $analysis = self::analyzeComment($commentText, $postCaption, 0, $authorName, $attachment, $configuredLanguage);
        $commentIntent = $analysis['intent'] ?? 'general_conversation';

        if (($analysis['action'] ?? '') === 'NO_REPLY' || !($analysis['autopilot_ready'] ?? true)) {
            return [
                'action' => 'NO_REPLY',
                'source' => 'hermes_safety_guard',
                'reason' => 'SAFETY_FILTER_TRIGGERED',
                'engagement' => '',
                'conversion' => '',
                'support' => '',
                'detected_language' => $langDetection['language'],
                'language_confidence' => $langDetection['confidence'],
                'language_source' => $langDetection['source'],
                'response_language' => $resolvedLanguage,
                'requires_human_review' => true,
                'engagement_tips' => $analysis['highlight_reason'] ?? 'Silencio operativo (NO_REPLY)'
            ];
        }

        // Try OpenRouter API first if configured and user has remaining quota (or mock provided)
        if ($aiProvider === 'openrouter' && (!empty($openrouterKey) || isset($runtimeOverrides['mock_openrouter_response'])) && !$isTokensExhausted) {
            $openrouterResult = self::callOpenRouterApi(
                $authorName, $commentText, $platform, $postCaption, 
                $brandName, $personaName, $brandIndustry, $brandTone, $brandDescription, $resolvedLanguage,
                $warmthLevel, $depthLevel, $energyLevel,
                $closingQuestionRule, $emojiStyle, $keyPhrases, $forbiddenPhrases, $fewShotExamples,
                $openrouterKey, $openrouterModel,
                $targetUserId, $pdo,
                $postAuthor, $lengthCategory, $recentThreadReplies, $analysis, $learningExamples,
                $runtimeOverrides
            );
            if ($openrouterResult !== null) {
                if (($openrouterResult['action'] ?? '') === 'NO_REPLY') {
                    $openrouterResult['requires_human_review'] = true;
                    if (empty($openrouterResult['reason'])) {
                        $openrouterResult['reason'] = 'AI_UNAVAILABLE_OR_INVALID';
                    }
                    $openrouterResult['target_language'] = $resolvedLanguage;
                    $openrouterResult['detected_language'] = $langDetection['language'];
                    $openrouterResult['language_confidence'] = $langDetection['confidence'];
                    $openrouterResult['language_source'] = $langDetection['source'];
                    $openrouterResult['response_language'] = $resolvedLanguage;
                    return $openrouterResult;
                }
                if (!empty($openrouterResult['engagement'])) {
                    // Pre-publication check on source: reject any heuristic origin
                    if (str_starts_with($openrouterResult['source'] ?? '', 'heuristic')) {
                        return [
                            'action' => 'NO_REPLY',
                            'reason' => 'HEURISTIC_SOURCE_FORBIDDEN',
                            'source' => 'fail_closed',
                            'engagement' => '',
                            'conversion' => '',
                            'support' => '',
                            'target_language' => $resolvedLanguage,
                            'detected_language' => $langDetection['language'],
                            'language_confidence' => $langDetection['confidence'],
                            'language_source' => $langDetection['source'],
                            'response_language' => $resolvedLanguage,
                            'requires_human_review' => true,
                            'engagement_tips' => 'Respuestas heurísticas bloqueadas para publicación pública en HERMES v2.1.'
                        ];
                    }
                    $sanitized = self::sanitizeRepliesWithForbidden($openrouterResult, $forbiddenPhrases);
                    $genderCtx = self::detectGenderContext($authorName, $commentText);
                    foreach ($sanitized as $k => $v) {
                        if (is_string($v)) {
                            $sanitized[$k] = self::sanitizeGenderVocatives($v, $genderCtx['gender'], $genderCtx['first_name']);
                        }
                    }
                    $sanitized['target_language'] = $resolvedLanguage;
                    $sanitized['detected_language'] = $openrouterResult['detected_comment_language'] ?? $langDetection['language'];
                    $sanitized['language_confidence'] = $langDetection['confidence'];
                    $sanitized['language_source'] = $langDetection['source'];
                    $sanitized['response_language'] = $openrouterResult['response_language'] ?? $resolvedLanguage;
                    if ($isAmbiguousLanguage || $langDetection['is_ambiguous'] || !empty($openrouterResult['requires_human_review'])) {
                        $sanitized['requires_human_review'] = true;
                    }
                    return $sanitized;
                }
            }
        }

        // HERMES v2.1 RULE: Never substitute failed AI responses with heuristic text for public replies.
        // Heuristics are strictly restricted to internal classification, spam and toxicity detection.
        // Public output must strictly return Fail-Closed NO_REPLY with requires_human_review = true.
        return [
            'action' => 'NO_REPLY',
            'reason' => 'AI_UNAVAILABLE_OR_INVALID',
            'source' => 'fail_closed',
            'engagement' => '',
            'conversion' => '',
            'support' => '',
            'target_language' => $resolvedLanguage,
            'detected_language' => $langDetection['language'],
            'language_confidence' => $langDetection['confidence'],
            'language_source' => $langDetection['source'],
            'response_language' => $resolvedLanguage,
            'requires_human_review' => true,
            'engagement_tips' => 'OpenRouter no disponible o respuesta no válida. Comentario retenido para revisión humana supervisada (HERMES v2.1 Fail-Closed).'
        ];
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
        // Hard constraint: NEVER hallucinate or assume user names. Only use name if explicit in comment text
        $explicitName = '';
        if (preg_match('/\b(me llamo|mi nombre es|soy)\s+([a-záéíóúñ]+)\b/iu', $cleanComment, $mName)) {
            $explicitName = mb_convert_case($mName[2], MB_CASE_TITLE, 'UTF-8');
        }
        $displayName = $explicitName;
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
        // HERMES v2 DEDICATED INTENT HANDLERS (Master Voice & Proportionality)
        // ══════════════════════════════════════════════════════════════════════

        // 1. TROLL PROVOCATION & HARASSMENT (Strict Silencio Operativo - NO_REPLY)
        if ($intent === 'TROLL_PROVOCATION' || $intent === 'HARASSMENT') {
            return [
                'action' => 'NO_REPLY',
                'source' => 'heuristic_calibrated',
                'engagement' => '',
                'conversion' => '',
                'support' => '',
                'engagement_tips' => '🛡️ Silencio Operativo: Provocación troll superficial o acoso. Decisión estricta: NO_REPLY.'
            ];
        }

        // 2. EMOJI ONLY (Policy: ~70% pure emoji, ~30% 1-3 words + 1 emoji)
        if ($intent === 'EMOJI_ONLY') {
            $pureEmojiPool = [
                '🔥🏛️',
                '🏛️✨',
                '🔥',
                '🤝🏛️',
                '🙌✨',
                '👊🏛️',
                '🏛️'
            ];
            $shortWordPool = [
                'Firmeza total. 🏛️',
                'Así es. 🔥',
                'Fuerza. 👊',
                'Paso firme. ⚡',
                'Constancia diaria. 🏛️'
            ];
            return [
                'action' => 'REPLY',
                'source' => 'heuristic_calibrated',
                'engagement' => $pick($pureEmojiPool),
                'conversion' => $pick($pureEmojiPool, 1),
                'support' => $pick($shortWordPool, 2),
                'engagement_tips' => 'Minimalismo proporcional: 1-2 emojis o micro-frase de 1-3 palabras.'
            ];
        }

        // 3. BRIEF AGREEMENT (Validation of truth in 1 word or minimal phrase -> 4 to 10 words, CERO welcome)
        if ($intent === 'BRIEF_AGREEMENT') {
            $briefAgreementPool = [
                'Así es. Foco en lo esencial.',
                'Totalmente. La constancia lo es todo. 🏛️',
                'Exacto. Lo que depende de uno es lo que cuenta.',
                'Paso firme y mente clara. 🏛️',
                'Así es. Templanza y discernimiento diario.',
                'Totalmente de acuerdo. Fuerza en el camino. 🏛️'
            ];
            return [
                'action' => 'REPLY',
                'source' => 'heuristic_calibrated',
                'engagement' => $pick($briefAgreementPool),
                'conversion' => $pick($briefAgreementPool, 1),
                'support' => $pick($briefAgreementPool, 2),
                'engagement_tips' => '🏛️ Ratificación estoica concisa (4-10 palabras) sin bienvenidas falsas.'
            ];
        }

        // 4. GREETING (Cordial brief greeting -> 4 to 8 words, CERO welcome ceremonial)
        if ($intent === 'GREETING') {
            $greetingPool = [
                '¡Hola! Qué gusto saludarte. Un gran abrazo. 🤝',
                '¡Saludos! Que tengas un excelente día. ✨',
                '¡Hola! Un saludo fraterno y buena jornada. 🏛️',
                '¡Buenos días! Fuerte abrazo y mente clara hoy. 🤝'
            ];
            return [
                'action' => 'REPLY',
                'source' => 'heuristic_calibrated',
                'engagement' => $pick($greetingPool),
                'conversion' => $pick($greetingPool, 1),
                'support' => $pick($greetingPool, 2),
                'engagement_tips' => '👋 Saludo cordial y sobrio de 4-8 palabras.'
            ];
        }

        // 5. NEW FOLLOWER (Explicit statement of following -> 6 to 12 words varied & natural)
        if ($intent === 'NEW_FOLLOWER') {
            $newFollowerPool = [
                'Gracias por sumarte. 🙌',
                'Gracias por estar aquí. Que el contenido te aporte. 🏛️',
                'Un gusto tenerte por aquí. Seguimos trabajando en ello. 🤝',
                'Gracias por seguir el contenido. Seguimos en el camino. 🏛️'
            ];
            return [
                'action' => 'REPLY',
                'source' => 'heuristic_calibrated',
                'engagement' => $pick($newFollowerPool),
                'conversion' => $pick($newFollowerPool, 1),
                'support' => $pick($newFollowerPool, 2),
                'engagement_tips' => '🏛️ Bienvenida sobria y natural sin plantillas repetitivas.'
            ];
        }

        // 6. DISAGREEMENT (Reasoned disagreement -> 12 to 25 words with stoic distinction)
        if ($intent === 'DISAGREEMENT') {
            $disagreePool = [
                'Se entiende tu punto. La templanza no es pasividad ante lo injusto, sino claridad para actuar sin ira. 🏛️',
                'Válida perspectiva. El autodominio no exige callar, sino elegir con lucidez nuestras batallas. 🤝',
                'Respetable criterio. Cada situación exige discernimiento; la calma interior es el punto de partida para decidir bien. 🏛️'
            ];
            return [
                'action' => 'REPLY',
                'source' => 'heuristic_calibrated',
                'engagement' => $pick($disagreePool),
                'conversion' => $pick($disagreePool, 1),
                'support' => $pick($disagreePool, 2),
                'engagement_tips' => '⚖️ Distinción serena ante el desacuerdo, sin entrar en debates ni pretender ganar.'
            ];
        }

        // 7. CRITICISM (Constructive critique of content depth -> 12 to 25 words sober & open)
        if ($intent === 'CRITICISM') {
            $criticismPool = [
                'Punto válido. En pocas líneas se sintetiza una idea, pero la práctica real requiere discernimiento y profundidad diaria. 🏛️',
                'Comprendo tu punto. Ninguna frase reemplaza el criterio propio; lo valioso es llevar la reflexión a los hechos. 🤝',
                'Agradezco la crítica. La filosofía estoica es exigente y los matices importan; seguimos buscando aportar valor real. ✨'
            ];
            return [
                'action' => 'REPLY',
                'source' => 'heuristic_calibrated',
                'engagement' => $pick($criticismPool),
                'conversion' => $pick($criticismPool, 1),
                'support' => $pick($criticismPool, 2),
                'engagement_tips' => '🔍 Respuesta sobria a la crítica constructiva sin reactividad defensiva.'
            ];
        }

        // 8. PERSONAL STORY (Anecdote of loss, personal trial, fatigue -> empathy before aphorism)
        if ($intent === 'PERSONAL_STORY') {
            $personalStoryPool = [
                'Lo siento por lo que estás atravesando. Perder algo por lo que trabajaste duele. Ojalá este contenido te acompañe en el camino.',
                'Gracias por compartirlo. Hay momentos en que toca asimilar el golpe y avanzar un paso a la vez con respeto a tu proceso. 🤝',
                'Cuesta mucho cuando las cosas no salen como uno espera. Mucha fuerza y serenidad para este momento. 🏛️'
            ];
            return [
                'action' => 'REPLY',
                'source' => 'heuristic_calibrated',
                'engagement' => $pick($personalStoryPool),
                'conversion' => $pick($personalStoryPool, 1),
                'support' => $pick($personalStoryPool, 2),
                'engagement_tips' => '🏛️ Empatía humana sobria y escucha respetuosa antes que aforismos estoicos.'
            ];
        }

        // 9. QUESTION (Direct conceptual or practical answer)
        if ($intent === 'QUESTION') {
            $questionPool = [
                'El estoicismo no busca eliminar las emociones, sino aprender a gobernar nuestra respuesta consciente ante ellas. 🏛️',
                'La clave está en separar con calma lo que depende de ti de lo que escapa a tu control. Foco en tus acciones. 🏛️✨',
                'Se aplica en las pequeñas decisiones cotidianas: responder con serenidad en lugar de reaccionar impulsivamente. 🤝'
            ];
            return [
                'action' => 'REPLY',
                'source' => 'heuristic_calibrated',
                'engagement' => $pick($questionPool),
                'conversion' => $pick($questionPool, 1),
                'support' => $pick($questionPool, 2),
                'engagement_tips' => '❓ Respuesta directa y fundamentada a la pregunta del seguidor.'
            ];
        }

        // ══════════════════════════════════════════════════════════════════════
        // CASE 0: Toxicidad Hostil / Insultos Graves (Silencio Operativo / Sobriedad)
        // ══════════════════════════════════════════════════════════════════════
        if ($intent === 'toxic_hostile') {
            return [
                'action' => 'NO_REPLY',
                'source' => 'heuristic_calibrated',
                'engagement' => '',
                'conversion' => '',
                'support' => '',
                'engagement_tips' => '🛡️ Silencio Operativo: No responder públicamente a insultos graves.'
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
                "Comprendemos que existan posturas escépticas. La verdadera filosofía no busca convencer con palabras, sino demostrarse con el ejemplo cotidiano. 🏛️",
                "El valor de los principios estoicos se comprueba en la práctica cotidiana y en la serenidad que aportan ante la adversidad. ⚡",
                "Respetamos cada criterio. Seguimos firmes compartiendo reflexiones libres para quienes buscan cultivar carácter y templanza. 🏛️✨"
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
                "Canalizar la fricción diaria en disciplina constructiva es el verdadero reto. Firmeza en lo que depende de ti y serenidad para soltar lo demás. 🏛️⚡",
                "Cuando las circunstancias externas retan tu paciencia, la templanza es tu mejor escudo. No concedas a nadie el poder de perturbarte. 🎯🏛️",
                "El control emocional se entrena como un músculo en cada pequeña reacción cotidiana. Fuerza y autodominio siempre. 🧠✨"
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
                "¡Esa es la verdadera tribu! Caminar juntos con propósito y templanza multiplica la fuerza de cada uno. 🏛️⚡",
                "Comunidades unidas llegan mucho más lejos. Firmes en el camino del carácter y la disciplina compartida. 🤝🏛️",
                "¡Gracias por hacer crecer esta comunidad! Un honor compartir este camino de autodominio con personas con tu visión. 🏛️✨"
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
                "Comprender que la lealtad se demuestra con hechos transforma nuestras relaciones. Serenidad, criterio y rectitud ante todo. 🏛️⚡",
                "El discernimiento y la claridad en nuestros vínculos son claves de crecimiento. Cero desgaste innecesario y foco total en lo que depende de ti. 🎯🏛️",
                "Cuando filtras con serenidad, ahorras energía para lo verdaderamente importante. La templanza es la mejor coraza. 🏛️✨"
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
                    "¡Gracias por el impulso{$nameVocative}! Seguimos firmes compartiendo reflexiones de templanza y carácter. 🏛️⚡",
                    "¡Agradecidos al 100%{$nameVocative}! La disciplina diaria forja un espíritu inquebrantable. 🎯🏛️",
                    "¡Esa es la visión{$nameVocative}! Adelante siempre con la frente en alto y autodominio. ⚡💪",
                    "¡Seguimos sumando! Cada día cuenta en la forja de la mejor versión de uno mismo. 🏛️✨"
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
                    "¡Gracias por el impulso{$nameVocative}! La verdadera filosofía se demuestra en las acciones cotidianas. 🏛️⚡",
                    "¡Agradecidos por la confianza{$nameVocative}! Firmeza total en este camino de autodominio y temple estoico. 🎯🏛️",
                    "¡Esa es la visión{$nameVocative}! Quien vence sus propias excusas y pereza diaria conquista su vida. 🏛️✨",
                    "Nos alegra que te sirva de inspiración. Sigamos forjando un carácter inquebrantable día con día. ⚡💪"
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
                'conversion' => "¡A seguir forjando ese carácter con disciplina inquebrantable{$nameVocative}! 🏛️⚡ ¡Seguimos firmes!",
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
                    'conversion' => "¡Agradecidos al 100% por tu respaldo{$nameVocative}! 💯🏛️ Fuerza, templanza y foco total en lo que depende de ti.",
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
                    'conversion' => "Agradecidos de corazón por tu respeto y buena vibra{$nameVocative}. 🙏🏛️ Que la templanza y la serenidad guíen siempre tus pasos.",
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
                    'conversion' => "¡A seguir forjando ese carácter con disciplina inquebrantable{$nameVocative}! 👏🏛️ ¡Seguimos firmes!",
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
                    'conversion' => "¡Esa es la actitud imparable{$nameVocative}! 🔥⚡ Foco total en la disciplina y el autodominio.",
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
                    'conversion' => "¡Mucho aprecio fraternal{$nameVocative}! ❤️🏛️ Unidos en este camino de templanza y fortaleza interior.",
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
                    'conversion' => "¡Con toda la determinación{$nameVocative}! 💪🏛️ La disciplina diaria vence cualquier adversidad.",
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
                'conversion' => "¡Esa es la actitud{$nameVocative}! 🏛️⚡ Constancia, templanza y paso firme siempre.",
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
                    "¡Así es{$nameVocative}! La claridad y la serenidad marcan el camino. 👊",
                    "¡Así se habla{$nameVocative}! El mejor filtro es el tiempo. 🎯",
                    "¡Totalmente{$nameVocative}! Foco en lo que sí depende de nosotros. ⚡",
                    "¡De una{$nameVocative}! La firmeza interior no necesita adornos. 👊",
                    "¡Exacto{$nameVocative}! Adelante con firmeza y templanza. ⚡",
                    "Gran verdad{$nameVocative}. Gracias por acompañarnos y sumar. 👍",
                    "¡Tal cual{$nameVocative}! Mente clara y paso firme siempre. 🏛️",
                    "¡Sin duda{$nameVocative}! Foco y calma en lo esencial. ⚡"
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
                "Exacto{$nameVocative}. Dominar el propio juicio es el mayor superpoder ante cualquier obstáculo. 🏛️",
                "Totalmente{$nameVocative}. La disciplina diaria y la serenidad interior valen más que mil palabras. ⚡",
                "Es así{$nameVocative}. Quien se conquista a sí mismo en silencio no necesita demostrar nada a nadie. 🏛️✨",
                "Gran verdad{$nameVocative}. Mantener la templanza cuando todo se agita es la marca del verdadero carácter. 🏛️"
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
                "¡Tal cual{$nameVocative}! Vencerse a uno mismo en silencio cada día es la verdadera gloria estoica. 🏛️⚡",
                "Exacto{$nameVocative}. La verdadera victoria empieza adentro cuando apagas el ruido exterior y eliges la templanza. ⚡💪",
                "Esa batalla interna se gana con pequeñas decisiones de disciplina sostenida. Firmeza total{$nameVocative}. 👊🏛️"
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
                "Gran convicción y fortaleza{$nameVocative}. La fe y la rectitud moral son el mejor escudo ante la adversidad. 🙏🏛️",
                "Una fuente inagotable de serenidad interior{$nameVocative}. Agradecidos de tener tu reflexión en la comunidad. 🙌✨",
                "La convicción espiritual unida al autodominio cotidiano edifica un espíritu inquebrantable. 🙏⚡"
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
                "¡Gran compromiso{$nameVocative}! Cada día que eliges el autodominio sobre la queja estás forjando tu destino. 🏛️⚡",
                "¡Esa es la actitud{$nameVocative}! El hábito diario y silencioso es el único constructor infalible de fortaleza. ⚡💪",
                "El progreso continuo y sin prisa marca la diferencia. Paso a paso edificando un carácter sólido. 🏛️✨",
                "Mantener el estándar diario aun cuando nadie te mira es la esencia del estoicismo. ¡Adelante{$nameVocative}! 👊🔥"
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
                "Ese clic mental es el punto de partida hacia el autodominio. A usar esa energía para templar el carácter hoy mismo. 🏛️⚡",
                "Transformar la lucidez en hechos diarios es lo que distingue a los sabios de los soñadores. ¡Adelante{$nameVocative}! ⚡💪",
                "La lucidez llega en el momento exacto en que dejamos de justificarnos y tomamos responsabilidad total. 🏛️✨",
                "Ese golpe de realidad forja templanza cuando se canaliza en acciones concretas. Seguimos firmes en el camino. 👊🏛️"
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
            if (!self::COMMERCIAL_SALES_ACTIVE) {
                return [
                    'source' => 'heuristic_calibrated',
                    'engagement' => $helloName . "¡Hola! En Fortaleza Imparable compartimos reflexiones libres y principios de filosofía estoica para la comunidad. No tenemos cursos ni programas de venta activos; si deseas conversar sobre estos principios, con gusto podemos hablar por DM. 🏛️💬",
                    'conversion' => "Nos enfocamos 100% en aportar valor filosófico, desarrollo de carácter y mentalidad estoica libre para la comunidad. No comercializamos cursos ni libros. ¡Un fuerte abrazo fraternal{$nameVocative}! 🏛️✨",
                    'support' => "Esta es una comunidad dedicada al crecimiento personal y la filosofía práctica sin fines de venta directa. Si tienes alguna inquietud personal, déjanos un mensaje privado. 🏛️🤝",
                    'engagement_tips' => '🏛️ La honestidad y transparencia sobre el propósito comunitario fortalece el respeto y la confianza.'
                ];
            }

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
            if (!self::COMMERCIAL_SALES_ACTIVE) {
                return [
                    'source' => 'heuristic_calibrated',
                    'engagement' => "Comprendemos perfectamente tu punto{$nameVocative}. En Fortaleza Imparable nuestro compromiso es 100% auténtico con la filosofía estoica y la comunidad fraternal, sin vender productos ni membresías. 🤝🏛️",
                    'conversion' => "La transparencia y la rectitud moral son innegociables para nosotros. No ofrecemos procesos comerciales; compartimos sabiduría para el autodominio y la templanza diaria. 🏛️✨",
                    'support' => "Operamos con total autenticidad en cada publicación. Si deseas dialogar sobre algún principio estoico, nuestro equipo está a un mensaje directo de distancia. 🏛️",
                    'engagement_tips' => '🛡️ La autenticidad y rectitud de principios consolidan la confianza de la comunidad.'
                ];
            }

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
            if (!self::COMMERCIAL_SALES_ACTIVE) {
                return [
                    'source' => 'heuristic_calibrated',
                    'engagement' => "Queremos escucharte y apoyarte{$nameVocative}. Si deseas conversar con más calma o compartir tu situación personal, déjanos un mensaje privado (DM) y con gusto te leemos. 🏛️🤝",
                    'conversion' => "En esta comunidad nos apoyamos mutuamente en el camino del carácter y la templanza. Déjanos un mensaje directo para charlar con tranquilidad. ⚡🏛️",
                    'support' => "Tu voz es muy valiosa para nosotros. Por favor contáctanos por mensaje directo para atender tu consulta con el debido tiempo y atención fraternal. 🏛️🤝",
                    'engagement_tips' => '🏛️ La escucha activa y la cercanía fraternal fortalecen los lazos de la comunidad.'
                ];
            }

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
                    'conversion' => "Dominar la dicotomía del control transforma por completo tu enfoque y claridad mental. Recuerda siempre: actúa con firmeza en lo que dominas y suelta en paz lo demás. 🏛️⚡",
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
                    'conversion' => "Cuando conviertes la disciplina en un estándar innegociable, el carácter se vuelve indestructible. Firmeza en tu camino{$nameVocative}. 🏛️💪",
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
                "Dominar estos fundamentos marca la diferencia en el día a día. La verdadera filosofía se demuestra en las acciones cotidianas. 🏛️",
                "Llevar la teoría estoica a la conducta práctica es el mayor desafío y el más gratificante. ¡Seguimos firmes{$nameVocative}! ⚡",
                "La claridad de principios disuelve la confusión mental y forja un carácter inquebrantable. Un honor reflexionar juntos en comunidad. 🏛️✨"
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
                "Gracias por el apoyo. Me alegra que resuene contigo. 🤝",
                "Gracias. Seguimos aportando valor y criterio en cada publicación. 🏛️",
                "Un gusto compartir estas reflexiones. Foco en lo esencial. ✨",
                "Gracias por pasar y dejar tu aporte. Seguimos firmes. 🏛️",
                "Agradecidos con tu presencia en la conversación. 🤝",
                "Totalmente de acuerdo. Paso firme y mente clara. 🏛️"
            ];
            $generalConvert = [
                "Así es. Quien domina sus impulsos y elige la constancia mantiene el rumbo. 🏛️",
                "Foco innegociable en mantener el estándar diario y la templanza interior. 🎯🏛️",
                "La verdadera fortaleza se forja en silencio con disciplina cotidiana. 🏛️✨"
            ];
            $generalSupport = [
                "Gracias por estar presente en la comunidad. Un saludo. 🏛️",
                "Un honor contar con tu participación. Seguimos firmes aportando valor cada día. 🏛️✨",
                "La constancia de nuestra comunidad es lo que nos impulsa. Un saludo fraternal. 🏛️🤝"
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
            "Una perspectiva muy interesante. Gracias por dejar tu reflexión y sumar valor a la conversación. 🎯",
            "Totalmente de acuerdo. Gracias por compartir tu punto de vista con la comunidad. 🤝✨",
            "Un punto de vista muy valioso. Da gusto contar con aportes reflexivos en esta comunidad. 🙌",
            "Muchas gracias por sumar tu voz a la conversación. Seguimos firmes creando contenido con criterio. 🏛️",
            "Coincido con tu análisis. Llevar la reflexión a los hechos cotidianos es lo que marca la diferencia. 🏛️✨",
            "Comentarios reflexivos como el tuyo enriquecen el debate en esta comunidad. Un saludo. 🤝"
        ];
        $generalMediumConvert = [
            "Totalmente. Cuando alineas tu mente con principios sólidos de autodominio, nada externo puede perturbarte. 🏛️⚡",
            "Para continuar forjando carácter, el mayor reto es la constancia silenciosa día tras día. Firmeza en el camino. 🎯🏛️",
            "La templanza cotidiana es la mayor armadura ante la adversidad. Un honor compartir este camino en comunidad. 🏛️✨"
        ];
        $generalMediumSupport = [
            "Un gran saludo. Encantados de leerte y tener tu participación reflexiva en nuestra comunidad. 🏛️",
            "La claridad de criterio se construye compartiendo y debatiendo ideas sólidas. Gracias por tu aporte. 🏛️✨",
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
     * OpenRouter API Dynamic Integration (Supports Gemini 2.5 Flash, Claude Sonnet 4.5, DeepSeek V3, GPT-4o Mini, etc.)
     * Enriched with Heuristic Brain Modules 1-5 (Proportionality, Author Context, Clean Vocatives, Thread Memory, Intent Guidance)
     */
    public static function callOpenRouterApi(
        string $authorName, string $commentText, string $platform, string $postCaption,
        string $brandName, string $personaName, string $brandIndustry, string $brandTone, string $brandDescription, string $language,
        int $warmthLevel, int $depthLevel, int $energyLevel,
        string $closingQuestionRule, string $emojiStyle, array $keyPhrases, array $forbiddenPhrases, array $fewShotExamples,
        string $apiKey, string $model = 'nousresearch/hermes-3-llama-3.1-70b',
        int $targetUserId = 0, ?PDO $pdo = null,
        string $postAuthor = 'general', string $lengthCategory = 'medium', array $recentThreadReplies = [], ?array $commentAnalysis = null,
        array $learningExamples = [], array $runtimeOverrides = []
    ): ?array {
        $prompt = self::buildUniversalPrompt(
            $authorName, $commentText, $platform, $postCaption,
            $brandName, $personaName, $brandIndustry, $brandTone, $brandDescription, $language,
            $warmthLevel, $depthLevel, $energyLevel,
            $closingQuestionRule, $emojiStyle, $keyPhrases, $forbiddenPhrases, $fewShotExamples,
            $postAuthor, $lengthCategory, $recentThreadReplies, $commentAnalysis, $learningExamples
        );

        $url = 'https://openrouter.ai/api/v1/chat/completions';
        $selectedModel = !empty($model) ? trim($model) : 'nousresearch/hermes-3-llama-3.1-70b';
        if ($selectedModel === 'anthropic/claude-3.5-sonnet' || $selectedModel === 'anthropic/claude-3-5-sonnet') {
            $selectedModel = 'anthropic/claude-sonnet-4.5';
        }

        $charCount = mb_strlen(trim($commentText), 'UTF-8');
        $wordsArray = preg_split('/\s+/u', trim($commentText), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $wordCount = count($wordsArray);
        $textNoEmojiCheck = trim(preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F700}-\x{1F77F}\x{1F780}-\x{1F7FF}\x{1F800}-\x{1F8FF}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{1FA70}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{2300}-\x{23FF}\x{2B50}\x{200D}\x{FE0F}\s\p{P}]/u', '', $commentText));

        // Solo es puro emoji o micro-reacción si NO contiene preguntas ni carga semántica de dolor, duda o reflexión
        $hasQuestionOrSemantic = (bool)preg_match('/[¿\?]|(\b(pero|por qué|porque|para que|para qué|cómo|como|quien|quién|cuál|cual|miseria|dolor|difícil|dificil|no|si|vida|tiempo|mente|sentido|lucha|camino)\b)/iu', $commentText);
        $isPureEmojiOrShort = !$hasQuestionOrSemantic && (
            (mb_strlen($textNoEmojiCheck, 'UTF-8') === 0) ||
            ($wordCount <= 3 && in_array(mb_strtolower(trim($commentText), 'UTF-8'), ['amen', 'amén', 'top', 'total', 'exacto', 'de una', 'asi es', 'así es', 'tal cual', 'de acuerdo', '100%']))
        );

        $intent = $commentAnalysis['intent'] ?? 'general_conversation';

        if ($intent === 'EMOJI_ONLY') {
            $systemPromptContent = "Eres Hermes, voz oficial de la comunidad Fortaleza Imparable. El seguidor comentó ÚNICAMENTE con emojis. Tu regla central es la sobriedad y la proporcionalidad estricta.
POLÍTICA: En la gran mayoría de casos (~70%), responde ÚNICAMENTE con 1 o 2 emojis relevantes (ej. 🔥🏛️, 🏛️✨, 🤝, 🙌, 💪). CERO PALABRAS.
En el ~30% restante, si el contexto lo hace más natural, puedes responder con MÁXIMO 1 a 3 palabras contundentes más 1 emoji (ej. 'Firmeza total. 🏛️' o 'Así es. 🔥').
PROHIBICIÓN ABSOLUTA: Queda TERMINANTEMENTE PROHIBIDO escribir oraciones de más de 3 palabras, párrafos, discursos, bienvenidas o preguntas de cierre. NUNCA trates de sonar profundo ante un simple emoji.
PROHIBICIÓN TOTAL DE JERGA: Prohibido decir 'ñero', 'bro', 'pana', 'compa', etc.
Responde en JSON estructurado.";
        } elseif ($intent === 'BRIEF_AGREEMENT') {
            $systemPromptContent = "Eres Hermes, voz oficial de Fortaleza Imparable. El seguidor dejó un acuerdo breve de 1 palabra o frase mínima (ej. 'Importante', 'Exacto', 'Clave').
Responde con sobriedad y proporcionalidad (1 sola frase de 4 a 10 palabras que valide el punto central con foco estoico, ej. 'Así es. Foco en lo esencial.' o 'Totalmente. La constancia lo es todo. 🏛️').
PROHIBICIÓN TOTAL DE NOMBRES INVENTADOS: Queda TERMINANTEMENTE PROHIBIDO inventar o usar nombres de pila (NUNCA digas 'Así es, Luis' ni asumas nombres). Habla directamente.
PROHIBICIÓN TERMINANTE DE BIENVENIDA: Queda estrictamente prohibido dar bienvenidas a la comunidad, decir 'gracias por sumarte', o asumir que es nuevo seguidor.
PROHIBICIÓN TOTAL DE PREGUNTAS Y CLICHÉS: Cero preguntas cliché de bot o coach ('¿En qué buscas aplicarlo?'), cero frases vacías de autoayuda ('La perseverancia es la clave del éxito').
PROHIBICIÓN TOTAL DE JERGA: Prohibido 'ñero', 'bro', 'pana', etc.
Responde en JSON estructurado.";
        } elseif ($intent === 'GREETING') {
            $systemPromptContent = "Eres Hermes, voz oficial de Fortaleza Imparable. El seguidor envió un saludo breve. Responde con un saludo cordial, educado y sobrio (1 frase de 4 a 8 palabras, ej. '¡Hola! Qué gusto saludarte. Un gran abrazo. 🤝').
PROHIBICIÓN TOTAL DE NOMBRES INVENTADOS: Cero nombres de pila si el usuario no los escribió.
PROHIBICIÓN TOTAL: Cero discursos de iniciación estoica, cero preguntas reflexivas, cero jergas ('ñero', 'bro').
Responde en JSON estructurado.";
        } elseif ($intent === 'NEW_FOLLOWER') {
            $systemPromptContent = "Eres Hermes, voz oficial de Fortaleza Imparable. El seguidor indica explícitamente que es nuevo seguidor.
Brinda un agradecimiento o bienvenida sobria y natural (1 frase de 6 a 12 palabras).
VARIACIÓN OBLIGATORIA (No uses siempre la misma plantilla 'Bienvenido a la comunidad'):
- 'Gracias por sumarte. 🙌'
- 'Gracias por estar aquí. Que el contenido te aporte. 🏛️'
- 'Un gusto tenerte por aquí. Seguimos trabajando en ello. 🤝'
- 'Gracias por seguir el contenido. Seguimos en el camino. 🏛️'
PROHIBICIÓN TOTAL DE NOMBRES INVENTADOS: NUNCA uses un nombre si el usuario no lo escribió en su comentario.
Cero adulaciones exageradas o jerga callejera.
Responde en JSON estructurado.";
        } elseif ($intent === 'DISAGREEMENT') {
            $systemPromptContent = "Eres Hermes, voz oficial de Fortaleza Imparable. El seguidor expresa un desacuerdo respetuoso con el postulado de la publicación.
Responde con serenidad estoica y respeto (1 a 2 frases breves, 12 a 25 palabras). No debatas para 'ganar' la discusión; ofrece una distinción estoica clara con elegancia y calma interior.
PROHIBICIÓN DE NOMBRES INVENTADOS: No uses nombres asumidos.
Responde en JSON estructurado.";
        } elseif ($intent === 'CRITICISM') {
            $systemPromptContent = "Eres Hermes, voz oficial de Fortaleza Imparable. El seguidor hace una crítica constructiva a la profundidad del contenido.
Responde con sobriedad estoica y apertura reflexiva (1 a 2 frases breves, 12 a 25 palabras), aceptando el valor del discernimiento práctico sin reactividad defensiva.
PROHIBICIÓN DE NOMBRES INVENTADOS: No uses nombres asumidos.
Responde en JSON estructurado.";
        } elseif ($intent === 'PERSONAL_STORY' || $intent === 'emotional_venting_resilience' || $intent === 'existential_doubt') {
            $systemPromptContent = "Eres Hermes, voz oficial de Fortaleza Imparable. El seguidor comparte una vivencia difícil, pérdida, fracaso o desahogo.
EMPATÍA ANTES QUE AFORISMOS: Prioriza la empatía humana genuina, la presencia sobria y la escucha respetuosa (15 a 30 palabras).
PROHIBICIÓN DE SENTENCIAS O AFORISMOS SOLEMNES: NUNCA conviertas el dolor o fracaso del seguidor en una conferencia estoica abstracta ('El fracaso forja el carácter; levantarse con temple es la victoria estoica'). Prefiere validar su sentir con sobriedad humana ('Lamento por lo que estás atravesando. Perder algo por lo que trabajaste duele. Ojalá este contenido te acompañe en el camino.').
PROHIBICIÓN TOTAL DE NOMBRES INVENTADOS: NUNCA uses un nombre de pila si el seguidor no lo escribió en el texto.
PROHIBICIÓN ABSOLUTA: CERO emojis festivos insensibles (🔥/❤️/😂 ante el dolor), CERO optimismo ingenuo, CERO jerga callejera.
Responde en JSON estructurado.";
        } else {
            $systemPromptContent = "Eres Hermes, la voz de Fortaleza Imparable (filosofía estoica, criterio y autodominio). Tu misión es responder como una persona real, con sobriedad, criterio y naturalidad (8 a 20 palabras). Responde directamente a lo que el seguidor plantea.
PROHIBICIÓN ABSOLUTA DE INVENTAR CONTEXTO O NOMBRES (HALLUCINATED_CONTEXT): Si el usuario no escribió su nombre en el comentario, NUNCA te dirijas a él por un nombre de pila.
PROHIBICIÓN DE CLICHÉS MOTIVACIONALES GENÉRICOS: Queda TERMINANTEMENTE PROHIBIDO usar frases de autoayuda intercambiables como 'La perseverancia es la clave del éxito', 'Juntos somos más fuertes', 'El éxito está en tus manos', 'Nunca te rindas', 'Sigue luchando', 'El espíritu indomable'.
PROHIBICIÓN TOTAL DE JERGA: Queda TERMINANTEMENTE PROHIBIDO 'ñero', 'bro', 'pana', 'compa', 'wey'.
PROHIBICIÓN TOTAL DE PREGUNTAS CLICHÉ: Prohibido cerrar con '¿En qué buscas aplicarlo?'.
PROHIBICIÓN DE FALSAS BIENVENIDAS: Solo da la bienvenida si el usuario dice explícitamente que es nuevo seguidor.
Responde en JSON estructurado.";
        }

        $payload = [
            'model' => $selectedModel,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemPromptContent
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.75,
            'frequency_penalty' => 0.45,
            'presence_penalty' => 0.35
        ];

        $appUrl = Settings::get('app_url', 'http://localhost/Redes%20sociales');

        $executeCurl = function(array $p) use ($url, $apiKey, $appUrl) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($p));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
                'HTTP-Referer' => $appUrl,
                'X-Title: XINDRO Social AI'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            $res = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return [$code, $res];
        };

        // Support Mock OpenRouter Response for Deterministic & Isolated Testing
        if (isset($runtimeOverrides['mock_openrouter_response'])) {
            $mock = $runtimeOverrides['mock_openrouter_response'];
            if ($mock === null || $mock === false || $mock === 'NETWORK_ERROR') {
                return null;
            }
            if (is_callable($mock)) {
                $mock = $mock($commentText, $language);
            }
            if (is_array($mock) && isset($mock['action']) && $mock['action'] === 'NO_REPLY') {
                return $mock;
            }
            $httpCode = 200;
            $mockContent = is_array($mock) ? json_encode($mock, JSON_UNESCAPED_UNICODE) : (string)$mock;
            $response = json_encode([
                'choices' => [
                    ['message' => ['content' => $mockContent]]
                ],
                'usage' => ['total_tokens' => 0]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            [$httpCode, $response] = $executeCurl($payload);
        }

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
                // Pre-Publication Validation (HERMES v2 Quality Gate)
                $val = self::validateAndSanitizeReply($parsed['engagement'], $intent, 0, $commentText);

                if (!$val['valid']) {
                    // Attempt Regeneration with Strict Intent Constraint (Non-mechanical)
                    $retryConstraint = ($intent === 'EMOJI_ONLY')
                        ? "La salida DEBE contener ÚNICAMENTE 1 o 2 emojis relevantes (o máximo 1-3 palabras + 1 emoji). CERO oraciones largas. CERO párrafos."
                        : "Tu respuesta anterior violó la regla de calidad '{$val['reason']}' (detalle: " . ($val['token'] ?? '') . "). NO uses jerga ('ñero', 'bro', 'pana'), NO inventes nombres si el usuario no los escribió, NO uses clichés de coach ('la perseverancia es la clave del éxito'), NO des bienvenidas falsas, mantén sobriedad y responde exactamente al comentario.";

                    $retryPayload = $payload;
                    $retryPayload['messages'] = [
                        ['role' => 'system', 'content' => $systemPromptContent],
                        ['role' => 'user', 'content' => $prompt],
                        ['role' => 'assistant', 'content' => $content],
                        ['role' => 'user', 'content' => "CORRECCIÓN OBLIGATORIA: $retryConstraint Genera de nuevo las 3 opciones JSON válidas."]
                    ];

                    [$httpCode2, $response2] = isset($runtimeOverrides['mock_openrouter_response'])
                        ? [200, $response]
                        : $executeCurl($retryPayload);

                    if ($httpCode2 === 200 && $response2) {
                        $resData2 = json_decode($response2, true);
                        $content2 = $resData2['choices'][0]['message']['content'] ?? '';
                        $content2 = preg_replace('/^```(?:json)?\s*/i', '', trim($content2));
                        $content2 = preg_replace('/\s*```$/', '', trim($content2));
                        $parsed2 = json_decode($content2, true);

                        if ($parsed2 && isset($parsed2['engagement'])) {
                            $val2 = self::validateAndSanitizeReply($parsed2['engagement'], $intent, 1, $commentText);
                            if ($val2['valid']) {
                                $parsed = $parsed2;
                            } else {
                                // Fail-Closed: Return NO_REPLY instead of publishing flawed or broken output
                                return [
                                    'action' => 'NO_REPLY',
                                    'source' => 'hermes_validator_fail_closed',
                                    'reason' => $val2['reason'],
                                    'engagement' => '',
                                    'conversion' => '',
                                    'support' => '',
                                    'engagement_tips' => 'Falló validación de calidad tras reintento (' . $val2['reason'] . ')'
                                ];
                            }
                        }
                    } else {
                        // Fail-closed if retry request fails
                        return [
                            'action' => 'NO_REPLY',
                            'source' => 'hermes_validator_fail_closed',
                            'reason' => $val['reason'],
                            'engagement' => '',
                            'conversion' => '',
                            'support' => '',
                            'engagement_tips' => 'Falló validación inicial y reintento no conectó'
                        ];
                    }
                }

                // Post-Generation Language Verification: Check for strong contradictory language
                $langCheck = self::validateReplyLanguage($parsed['engagement'], $language, $commentText);
                if (!$langCheck['valid'] && !empty($langCheck['is_contradiction'])) {
                    return [
                        'action' => 'NO_REPLY',
                        'source' => 'hermes_language_validator_fail_closed',
                        'reason' => 'INVALID_AI_OUTPUT_LANGUAGE',
                        'engagement' => '',
                        'conversion' => '',
                        'support' => '',
                        'detected_comment_language' => $parsed['detected_comment_language'] ?? ($commentAnalysis['detected_language'] ?? $language),
                        'response_language' => $langCheck['detected_language'] ?? 'unknown',
                        'requires_human_review' => true,
                        'engagement_tips' => 'El idioma generado (' . ($langCheck['detected_language'] ?? 'desconocido') . ') no coincide con el idioma esperado (' . $language . '). Comentario retenido para revisión humana.'
                    ];
                }

                $engagement = trim($parsed['engagement'] ?? '');
                if (empty($engagement)) {
                    return [
                        'action' => 'NO_REPLY',
                        'source' => 'fail_closed',
                        'reason' => 'EMPTY_AI_OUTPUT',
                        'engagement' => '',
                        'conversion' => '',
                        'support' => '',
                        'requires_human_review' => true,
                        'engagement_tips' => 'La IA no devolvió texto de respuesta para engagement.'
                    ];
                }

                $conversion = trim($parsed['conversion'] ?? '');
                if (!empty($conversion)) {
                    $valC = self::validateAndSanitizeReply($conversion, $intent, 1, $commentText, 'openrouter');
                    if ($valC['valid']) {
                        $langC = self::validateReplyLanguage($conversion, $language, $commentText);
                        if (!$langC['valid'] && !empty($langC['is_contradiction'])) {
                            $conversion = '';
                        }
                    } else {
                        $conversion = '';
                    }
                }

                $support = trim($parsed['support'] ?? '');
                if (!empty($support)) {
                    $valS = self::validateAndSanitizeReply($support, $intent, 1, $commentText, 'openrouter');
                    if ($valS['valid']) {
                        $langS = self::validateReplyLanguage($support, $language, $commentText);
                        if (!$langS['valid'] && !empty($langS['is_contradiction'])) {
                            $support = '';
                        }
                    } else {
                        $support = '';
                    }
                }

                $detectedCommentLang = $parsed['detected_comment_language'] ?? ($commentAnalysis['detected_language'] ?? $language);
                $responseLang = $parsed['response_language'] ?? $language;

                $tipNotice = 'Respuesta generada con OpenRouter (' . htmlspecialchars($selectedModel) . ') adaptada a la voz HERMES v2.1.';
                if ($tokensUsed > 0) {
                    $tipNotice .= ' [Consumo: ' . number_format($tokensUsed) . ' tokens]';
                }
                return [
                    'action' => 'REPLY',
                    'source' => 'openrouter_' . str_replace(['/', ':', '.'], '_', $selectedModel),
                    'engagement' => $engagement,
                    'conversion' => $conversion,
                    'support' => $support,
                    'detected_comment_language' => $detectedCommentLang,
                    'response_language' => $responseLang,
                    'requires_human_review' => !empty($langCheck['requires_human_review']) || !empty($commentAnalysis['is_language_ambiguous']),
                    'tokens_used' => $tokensUsed,
                    'engagement_tips' => $parsed['engagement_tips'] ?? $tipNotice
                ];
            }
        }

        return null;
    }

    /**
     * Build Universal Dynamic Prompt for OpenRouter & Local Engine
     * Transmits full heuristic wisdom (Modules 1-5) to Gemini / LLM:
     * - Module 1: Proportionality and Length Enforcement
     * - Module 2: Stoic & Cultural Post Author Detection
     * - Module 3: Intent Classification & Tactical Guidance
     * - Module 4: Clean First Name Extraction & Anti-Bot Sanitization
     * - Module 5: Thread Memory & Deduplication against recent post replies
     */
    public static function buildUniversalPrompt(
        string $authorName, string $commentText, string $platform, string $postCaption,
        string $brandName, string $personaName, string $brandIndustry, string $brandTone, string $brandDescription, string $language = 'es',
        int $warmthLevel = 85, int $depthLevel = 75, int $energyLevel = 80,
        string $closingQuestionRule = 'always', string $emojiStyle = 'moderate', array $keyPhrases = [], array $forbiddenPhrases = [], array $fewShotExamples = [],
        string $postAuthor = 'general', string $lengthCategory = 'medium', array $recentThreadReplies = [], ?array $commentAnalysis = null,
        array $learningExamples = []
    ): string {
        // Module 4: Follower Gender Context & Explicit Name Extraction
        $genderCtx = self::detectGenderContext($authorName, $commentText);
        $gender = $genderCtx['gender'];

        // Hard constraint: NEVER address the user by name unless explicitly written in comment text
        $explicitName = '';
        if (preg_match('/\b(me llamo|mi nombre es|soy)\s+([a-záéíóúñ]+)\b/iu', $commentText, $mName)) {
            $explicitName = mb_convert_case($mName[2], MB_CASE_TITLE, 'UTF-8');
        }

        $nameInstruction = "";
        if (!empty($explicitName)) {
            $nameInstruction = "- El seguidor se presentó explícitamente en su comentario como \"$explicitName\". Puedes dirigirte a él/ella por este nombre con sobriedad y respeto estoico.";
        } else {
            $nameInstruction = "- REGLA INQUEBRANTABLE: PROHIBICIÓN ABSOLUTA DE INVENTAR O USAR NOMBRES (HALLUCINATED_NAME):\n"
                . "El seguidor NO ha escrito su nombre en el texto del comentario. Queda TERMINANTEMENTE PROHIBIDO usar el nombre de su perfil ('$authorName'), inventar un nombre, deducirlo o asumir un nombre de pila. NUNCA digas 'Hola [Nombre]', 'Gracias, [Nombre]', 'Así es, [Nombre]' ni 'Lamento tu pérdida, [Nombre]'. Dirígete a la persona de forma directa, humana y sobria, sin vocativos inventados.";
        }

        $genderInstruction = "";
        if ($gender === 'female') {
            $nameMention = !empty($explicitName) ? "Dirígete a ella como \"$explicitName\"" : "Dirígete a ella de forma cercana y cálida sin género forzado ni nombres inventados";
            $genderInstruction = "DIRECTIVA ESTRICTA DE GÉNERO [SEGUIDORA MUJER (DECLARADO EN TEXTO) - PROHIBICIÓN TOTAL DE 'HERMANO' Y 'BIENVENIDO']:\n"
                . "- La seguidora indicó explícitamente ser mujer en el comentario.\n"
                . "- PROHIBICIÓN TOTAL Y TERMINANTE: Queda ESTRICTAMENTE PROHIBIDO decirle \"hermano\", \"amigo\", \"rey\", \"bienvenido\" o cualquier término masculino.\n"
                . "- TRATAMIENTO OBLIGATORIO: $nameMention. NUNCA inventes nombres de pila ni uses clichés.";
        } elseif ($gender === 'male') {
            $nameMention = !empty($explicitName) ? "El seguidor indicó explícitamente su nombre (\"$explicitName\")." : "CERO nombres de pila inventados.";
            $genderInstruction = "DIRECTIVA DE GÉNERO [SEGUIDOR HOMBRE (DECLARADO EN TEXTO)]:\n"
                . "- El seguidor declaró explícitamente ser hombre en el texto. Mantén tono sobrio, respetuoso y humano. $nameMention Evita superlativos de coach o excesos de confianza.";
        } else {
            $genderInstruction = "DIRECTIVA ESTRICTA DE GÉNERO [GÉNERO NO DECLARADO EN TEXTO - OBLIGATORIO NEUTRO]:\n"
                . "- El texto del comentario no declara género de forma explícita.\n"
                . "- PROHIBICIÓN TERMINANTE DE TÉRMINOS CON GÉNERO: Queda TERMINANTEMENTE PROHIBIDO usar \"hermano\", \"hermana\", \"amigo\", \"amiga\", \"guerrero\", \"guerrera\", \"campeón\", \"campeona\", \"bienvenido\", \"bienvenida\", \"nuevo guerrero\".\n"
                . "- TRATAMIENTO OBLIGATORIO: Usa SIEMPRE fórmulas 100% universales y neutras gramaticalmente:\n"
                . "  * 'Gracias por sumarte. 🙌'\n"
                . "  * 'Un gusto tenerte por aquí.'\n"
                . "  * 'Gracias por compartirlo.'\n"
                . "  * 'Se entiende tu punto.'\n"
                . "  * 'Lamento que hayas pasado por eso.'\n"
                . "  * 'Totalmente de acuerdo. Foco en lo esencial. 🏛️'\n"
                . "- Jamás asumas masculinidad ni género por defecto.";
        }

        // Module 2: Philosophical & Cultural Post Context
        $philosophyContext = "";
        if ($postAuthor === 'marco_aurelio') {
            $philosophyContext = "La publicación cita o aborda el pensamiento del emperador filósofo MARCO AURELIO (Meditaciones: autodominio, razón rectora, serenidad ante el caos externo, cumplimiento del deber con humildad y sin quejarse). Conecta orgánicamente con estas virtudes.";
        } elseif ($postAuthor === 'epicteto') {
            $philosophyContext = "La publicación cita o aborda la filosofía de EPICTETO (Enquiridion / Discursos: la Dicotomía del Control — separar con claridad quirúrgica lo que depende 100% de uno de lo incontrolable, libertad interior y templanza). Conecta con esta distinción fundamental.";
        } elseif ($postAuthor === 'seneca') {
            $philosophyContext = "La publicación cita o aborda el pensamiento de SÉNECA (Cartas a Lucilio / De la brevedad de la vida: el valor del tiempo presente, la serenidad ante la adversidad, la superación de la ansiedad y el dominio de las pasiones).";
        } elseif ($postAuthor === 'dostoievski') {
            $philosophyContext = "La publicación cita o aborda a FIÓDOR DOSTOYEVSKI (la forja del carácter en medio de la adversidad humana, la resiliencia moral y la fortaleza interior ante momentos oscuros).";
        } elseif ($postAuthor === 'minamoto') {
            $philosophyContext = "La publicación cita o aborda al legendario héroe samurái MINAMOTO NO YOSHITSUNE (1159–1189, brillante estratega y maestro samurái de las Guerras Genpei, símbolo de honor, lealtad y temple ante la tragedia. ¡ADVERTENCIA ABSOLUTA: NO confundir con Miyamoto Musashi!). Si el seguidor pregunta quién era, aclara su identidad con certeza histórica y respeto.";
        } elseif ($postAuthor === 'musashi') {
            $philosophyContext = "La publicación cita o aborda al maestro espadachín MIYAMOTO MUSASHI (autor de El Libro de los Cinco Anillos y Dokkodo: el camino del guerrero solitario, la disciplina innegociable y el autodominio de la mente y la espada).";
        } elseif ($postAuthor === 'sun_tzu') {
            $philosophyContext = "La publicación cita o aborda a SUN TZU (El Arte de la Guerra: la estrategia, la serenidad mental, la adaptabilidad y el autodominio táctico ante el conflicto).";
        } elseif ($postAuthor === 'nietzsche') {
            $philosophyContext = "La publicación cita o aborda a FRIEDRICH NIETZSCHE (Amor Fati, la superación personal, abrazar el sufrimiento como motor de grandeza y la forja de una voluntad inquebrantable).";
        } else {
            $philosophyContext = "La publicación aborda principios estoicos universales y desarrollo de carácter: autodominio, fortaleza mental, forja de hábitos inquebrantables, disciplina y templanza práctica.";
        }

        // Module 1: Proportionality & Length Directives (Hermes 3 Engine)
        $charCount = mb_strlen(trim($commentText), 'UTF-8');
        $wordsArray = preg_split('/\s+/u', trim($commentText), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $wordCount = count($wordsArray);
        // Module 1: Proportionality & Length Directives (HERMES v2 Engine)
        $charCount = mb_strlen(trim($commentText), 'UTF-8');
        $wordsArray = preg_split('/\s+/u', trim($commentText), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $wordCount = count($wordsArray);
        $textNoEmoji = trim(preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F700}-\x{1F77F}\x{1F780}-\x{1F7FF}\x{1F800}-\x{1F8FF}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{1FA70}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{2300}-\x{23FF}\x{2B50}\x{200D}\x{FE0F}\s\p{P}]/u', '', $commentText));

        $intent = $commentAnalysis['intent'] ?? 'general_conversation';

        // Detección de saludos cordiales breves (ej. "Saludos", "Hola", "Buenos días", "Excelente", etc.)
        $isGreetingComment = (bool)preg_match('/^(saludos|saludo|hola|holas|buen d[ií]a|buenos d[ií]as|buenas tardes|buenas noches|bendiciones|gracias|muchas gracias|excelente)[!.\s]*$/iu', trim($commentText));

        // Solo es comentario breve de reacción si no contiene preguntas ni carga semántica reflexiva/dolorosa
        $hasQuestionOrSemantic = (bool)preg_match('/[¿\?]|(\b(pero|por qué|porque|para que|para qué|cómo|como|quien|quién|cuál|cual|miseria|dolor|difícil|dificil|no|si|vida|tiempo|mente|sentido|lucha|camino)\b)/iu', $commentText);
        $isShortComment = !$hasQuestionOrSemantic && (
            $isGreetingComment ||
            (mb_strlen($textNoEmoji, 'UTF-8') === 0) ||
            ($wordCount <= 3 && in_array(mb_strtolower(trim($commentText), 'UTF-8'), ['amen', 'amén', 'top', 'total', 'exacto', 'de una', 'asi es', 'así es', 'tal cual', 'de acuerdo', '100%', 'saludos', 'hola']))
        );

        $isStickerComment = str_starts_with($commentText, '[Sticker') || str_starts_with($commentText, '[GIF') || (($commentAnalysis['intent'] ?? '') === 'friendly_sticker_reaction');

        $proportionalityDirective = "";
        if ($intent === 'EMOJI_ONLY') {
            $proportionalityDirective = "DIRECTIVA EXCLUSIVA PARA COMENTARIO DE SOLO EMOJIS (HERMES v2 - PROPORCIONALIDAD MINIMALISTA):\n"
                . "- El seguidor comentó ÚNICAMENTE con emojis.\n"
                . "- POLÍTICA DE CALIBRACIÓN: En aproximadamente el 70% de las respuestas, responde ÚNICAMENTE con 1 o 2 emojis relevantes (ej. 🔥, 🏛️✨, 🤝, 🙌, 💪). CERO PALABRAS.\n"
                . "- En el ~30% restante, si el contexto lo hace más natural, puedes responder con MÁXIMO 1 a 3 palabras contundentes más 1 emoji (ej. 'Firmeza total. 🏛️' o 'Así es. 🔥').\n"
                . "- PROHIBICIÓN ABSOLUTA: Queda TERMINANTEMENTE PROHIBIDO escribir oraciones de más de 3 palabras, párrafos, discursos, bienvenidas o preguntas de cierre. NUNCA trates de sonar profundo ante un simple emoji.";
            $closingQuestionRule = "DESACTIVADA (Es un comentario de emojis; prohibido hacer preguntas).";
        } elseif ($intent === 'BRIEF_AGREEMENT') {
            $proportionalityDirective = "DIRECTIVA EXCLUSIVA PARA ACUERDO BREVE O VALIDACIÓN DE 1 PALABRA (MÁXIMO 4 A 10 PALABRAS):\n"
                . "- El seguidor comentó con una sola palabra o frase mínima de acuerdo (ej. 'Importante', 'Exacto', 'Clave', 'Totalmente').\n"
                . "- Responde con sobriedad y proporcionalidad, ratificando el punto central de forma estoica y directa (entre 4 y 10 palabras, ej. 'Así es. Foco en lo esencial.', 'Totalmente. La constancia lo es todo. 🏛️', 'Exacto. Lo que depende de uno es lo que cuenta.').\n"
                . "- PROHIBICIÓN ABSOLUTA DE BIENVENIDA: Queda TERMINANTEMENTE PROHIBIDO darle la bienvenida a la comunidad, decir 'gracias por sumarte', o asumir que es nuevo seguidor.\n"
                . "- PROHIBICIÓN ABSOLUTA DE PREGUNTAS: Cero preguntas cliché de bot o coach ('¿En qué buscas aplicarlo?').";
            $closingQuestionRule = "DESACTIVADA (Es un acuerdo breve; prohibido hacer preguntas de cierre).";
        } elseif ($intent === 'GREETING' || $isGreetingComment) {
            $proportionalityDirective = "DIRECTIVA EXCLUSIVA PARA SALUDO BREVE (MÁXIMO 4 A 8 PALABRAS):\n"
                . "- El seguidor envió un saludo cordial breve (ej. 'Hola', 'Buenos días', 'Saludos').\n"
                . "- Responde con cordialidad educada y sobria (entre 4 y 8 palabras, ej. '¡Hola! Qué gusto saludarte. Un gran abrazo. 🤝').\n"
                . "- PROHIBICIÓN ABSOLUTA: Cero discursos solemnes de iniciación, cero bienvenidas ceremoniales, cero preguntas de cierre.";
            $closingQuestionRule = "DESACTIVADA (Es un saludo breve; prohibido hacer preguntas).";
        } elseif ($intent === 'NEW_FOLLOWER') {
            $proportionalityDirective = "DIRECTIVA PARA NUEVO SEGUIDOR VERIFICADO (6 A 12 PALABRAS):\n"
                . "- El seguidor indicó explícitamente que es nuevo seguidor o acaba de seguir la cuenta.\n"
                . "- Brinda un agradecimiento o bienvenida sobria y natural (6 a 12 palabras). Variaciones obligatorias (evita decir siempre 'Bienvenido a la comunidad'):\n"
                . "  * 'Gracias por sumarte. 🙌'\n"
                . "  * 'Gracias por estar aquí. Que el contenido te aporte. 🏛️'\n"
                . "  * 'Un gusto tenerte por aquí. Seguimos trabajando en ello. 🤝'\n"
                . "- PROHIBICIÓN: NUNCA uses nombres inventados ni adulaciones.";
        } elseif ($intent === 'DISAGREEMENT') {
            $proportionalityDirective = "DIRECTIVA PARA DESACUERDO (12 A 25 PALABRAS):\n"
                . "- El seguidor discrepa respetuosamente con el postulado de la publicación.\n"
                . "- Responde con serenidad estoica y respeto. No debatas para 'ganar' la discusión; ofrece una distinción estoica clara con elegancia y calma interior (ej. 'Se entiende tu punto. La templanza no es pasividad ante lo injusto, sino claridad para actuar sin ira. 🏛️').";
            $closingQuestionRule = "DESACTIVADA (Es un desacuerdo; desescalar con calma).";
        } elseif ($intent === 'CRITICISM') {
            $proportionalityDirective = "DIRECTIVA PARA CRÍTICA AL CONTENIDO (12 A 25 PALABRAS):\n"
                . "- El seguidor critica la profundidad o formulación del post (ej. 'demasiado simplista', 'fuera de contexto').\n"
                . "- Responde con sobriedad estoica y apertura reflexiva, sin ponerte a la defensiva (ej. 'Punto válido. En pocas líneas se sintetiza una idea, pero la práctica real requiere discernimiento y profundidad diaria. 🏛️').";
            $closingQuestionRule = "DESACTIVADA";
        } elseif ($intent === 'PERSONAL_STORY') {
            $proportionalityDirective = "DIRECTIVA PARA EXPERIENCIA O LUCHA PERSONAL (EMPATÍA ANTES QUE AFORISMO):\n"
                . "- El seguidor comparte una vivencia difícil, pérdida, fracaso o desahogo.\n"
                . "- REGLA DE ORO: Prioriza la empatía humana, la escucha y el respeto antes que una sentencia estoica automática. NUNCA respondas con sermones moralistas sentenciosos (ej. evitar 'El fracaso forja el carácter; levantarse con temple es la victoria estoica').\n"
                . "- PREFERENCIA: 'Lo siento por lo que estás atravesando. Perder algo por lo que trabajaste duele. Ojalá este contenido te acompañe en el camino.'\n"
                . "- CERO emojis festivos (🔥/❤️/😂 ante el dolor), CERO optimismo ingenuo, CERO lecciones no solicitadas.";
        } elseif ($isStickerComment) {
            $proportionalityDirective = "DIRECTIVA EXCLUSIVA PARA RESPUESTA A STICKER AMIGABLE (MÁXIMO 5 A 8 PALABRAS):\n"
                . "- El seguidor comentó con un STICKER o GIF AMIGABLE de apoyo, acuerdo o afecto.\n"
                . "- Responde de forma agradable, cálida y ULTRA BREVE (ESTRICTAMENTE ENTRE 5 Y 8 PALABRAS, 1 SOLA FRASE).\n"
                . "- REMATE: Termina con 1 emoji cálido o afín (✨, 🤝, 🏛️, 👊).\n"
                . "- PROHIBICIÓN ABSOLUTA: Cero discursos solemnes, cero preguntas de cierre.";
            $closingQuestionRule = "DESACTIVADA";
        } elseif ($isShortComment) {
            $proportionalityDirective = "REGLA DE RESPUESTA A REACCIÓN O ACUERDO BREVE (4 A 10 PALABRAS):\n"
                . "- El seguidor dejó un emoji o acuerdo breve. Responde de forma sobria, ágil y contundente (4 a 10 palabras).\n"
                . "- CERO discursos de bienvenida, CERO preguntas de cierre.";
            $closingQuestionRule = "DESACTIVADA";
        } elseif ($lengthCategory === 'long') {
            $proportionalityDirective = "PROPORCIONALIDAD ESTRICTA: El comentario del seguidor es reflexivo o extenso (>80 caracteres). Tu respuesta debe ser humana, empática y con sustancia estoica (entre 15 y 30 palabras).";
        } else {
            $proportionalityDirective = "PROPORCIONALIDAD: Tu respuesta debe tener entre 12 y 25 palabras, conversacional y bien pensada. Evita sonar esquemático o robótico.";
        }

        // Module 3: Intent Tactical Guidance
        $commentLower = mb_strtolower($commentText, 'UTF-8');
        $intentGuidance = "";

        // Detect Humor, Banter, Sarcasm or Meme (Solo si no contiene toxicidad, insultos o ataques hacia la IA)
        $hasHostileOrBotAttack = (bool)preg_match('/\b(csm|csmr|ctm|ctmr|mierda|puta|puto|hdp|estupido|estúpido|idiota|imbecil|imbécil|esa ia|ni escribir sabe|bot mediocre|ia de mierda)\b/iu', $commentLower);
        $isHumorBanter = !$hasHostileOrBotAttack && (
            ($intent === 'humor_banter_joke') 
            || (bool)preg_match('/[😝😜🤪😂🤣😆😹🤡]/u', $commentText)
            || (bool)preg_match('/\b(carnitas|al cazo|cazo|al sart[eé]n|matadero|jajaja|jaja|jeje|xd|lol|lmao|qu[eé] risa|mor[ií] de risa|se mam[oó]|te mamaste|no mames|no manches|chiste|broma|cerdo|puerco|al asador)\b/iu', $commentText)
        );

        if ($isHumorBanter) {
            $intentGuidance = "DIRECTIVA DE INTENCIÓN [😄 Humor, Broma, Meme o Banter]:\n"
                . "- El seguidor hace un comentario cómico, burlón, meme o broma informal (ej. 'hacerlo carnitas', 'al cazo', risas, emojis 😝/😂).\n"
                . "- REGLA DE ORO DE TONO: Responde con naturalidad, complicidad, picardía y simpatía fresca. Ríete con él ('Jajaja...', '😅', '😂'), sigue el chiste o remata con humor sobre no tomarse las cosas tan a pecho. Sé humano y breve (1 sola frase contundente o máximo 2 frases breves, <25 palabras).\n"
                . "- PROHIBICIÓN ABSOLUTA: NUNCA respondas con discursos solemnes, corporativos o de agradecimiento robótico (ej. TERMINANTEMENTE PROHIBIDO decir 'Apreciamos mucho que dediques tiempo a interactuar y reflexionar con nosotros...').\n"
                . "- PROHIBICIÓN ABSOLUTA: NUNCA hagas preguntas existenciales o filosóficas de cierre (ej. PROHIBIDO preguntar '¿En qué situación buscas aplicarlo hoy?'). Las preguntas de cierre están TERMINANTEMENTE DESACTIVADAS para este comentario.";

            $closingQuestionRule = "DESACTIVADA (Es un comentario de humor/broma. Queda ESTRICTAMENTE PROHIBIDO hacer preguntas reflexivas de cierre).";

            $forbiddenPhrases[] = 'Apreciamos mucho que dediques tiempo';
            $forbiddenPhrases[] = 'interactuar y reflexionar con nosotros';
            $forbiddenPhrases[] = '¿En qué situación o reto buscas aplicarlo hoy?';
            $forbiddenPhrases[] = '¿En qué buscas aplicarlo?';
            $forbiddenPhrases[] = 'dediques tiempo a interactuar';
            $forbiddenPhrases[] = 'reflexionar con nosotros';
            $forbiddenPhrases[] = '¡Seguimos adelante! 🏛️ ¿En qué';
        } elseif ($intent === 'existential_doubt' || str_contains($commentLower, 'miseria') || str_contains($commentLower, 'para que vivir') || str_contains($commentLower, 'para qué vivir') || str_contains($commentLower, 'de que sirve') || str_contains($commentLower, 'de qué sirve') || str_contains($commentLower, 'sin sentido')) {
            $intentGuidance = "DIRECTIVA DE INTENCIÓN [Duda Existencial / Dolor / Cuestionamiento de la Miseria]:\n"
                . "- El seguidor plantea un cuestionamiento sobre el dolor, la miseria o el sentido de vivir ante la adversidad.\n"
                . "- REGLA DE ORO: Responde con profunda empatía humana y serenidad estoica. Valida con respeto su sentir sin juzgar, y comparte con calidez que aunque no elegimos las dificultades externas, la mayor dignidad humana reside en la fortaleza del espíritu y el autodominio interior.\n"
                . "- PROHIBICIÓN ABSOLUTA: QUEDA TERMINANTEMENTE PROHIBIDO darle las gracias por apoyar ('gracias por estar siempre apoyando'), festejar o usar optimismo ingenuo y superficial.";
        } elseif (str_starts_with($intent, 'lead_') || $intent === 'price_lead' || str_contains($commentLower, 'precio') || str_contains($commentLower, 'costo') || str_contains($commentLower, 'mentoría') || str_contains($commentLower, 'mentoria') || str_contains($commentLower, 'curso')) {
            if (!self::COMMERCIAL_SALES_ACTIVE) {
                $intentGuidance = "DIRECTIVA DE INTENCIÓN [Pregunta de Precio/Curso/Acceso]: Esta es una comunidad de reflexión filosófica y frases estoicas sin catálogo de venta activo ni cursos de pago. Agradece con amabilidad y humildad fraternal, aclarando que compartimos reflexiones libres para la comunidad, e invita a enviar un DM si desea charlar o profundizar personalmente sobre algún principio.";
            } else {
                $intentGuidance = "DIRECTIVA DE INTENCIÓN [Interés Comercial / Precio / Mentoría]: Destaca el valor transformador del programa o mentoría e invita amablemente a revisar el enlace en la bio o a enviar un DM para coordinar detalles. NUNCA inventes precios o cifras ficticias.";
            }
        } elseif ($intent === 'venting_resilience' || str_contains($commentLower, 'ansiedad') || str_contains($commentLower, 'desmorona') || str_contains($commentLower, 'cuesta') || str_contains($commentLower, 'difícil') || str_contains($commentLower, 'dificil') || str_contains($commentLower, 'no puedo')) {
            $intentGuidance = "DIRECTIVA DE INTENCIÓN [Desahogo Emocional / Búsqueda de Resiliencia]: El seguidor comparte una dificultad, miedo o frustración real. Aplica profunda empatía humana: valida su desafío con respeto fraternal y enfócalo en lo que sí está bajo su control (Dicotomía del Control). NUNCA le vendas agresivamente ni uses clichés superficiales de autoayuda.";
        } elseif ($intent === 'support' || $intent === 'support_request') {
            if (!self::COMMERCIAL_SALES_ACTIVE) {
                $intentGuidance = "DIRECTIVA DE INTENCIÓN [Consulta de Comunidad]: Responde con cortesía y cercanía fraternal, e invita amablemente a un mensaje directo (DM) para conversar con calma. CERO soporte técnico de software o pedidos.";
            } else {
                $intentGuidance = "DIRECTIVA DE INTENCIÓN [Consulta de Soporte / Duda Técnica]: Resuelve con precisión y cortesía. Si requiere verificación interna o acceso de cuenta, oriéntalo a escribir por DM.";
            }
        } elseif ($intent === 'praise_positive' || $intent === 'gratitude_community') {
            $intentGuidance = "DIRECTIVA DE INTENCIÓN [Agradecimiento / Comunidad]: Agradece con humildad fraternal y remata con una frase o reflexión que continúe enriqueciendo la comunidad.";
        }

        // Module 5: Thread Memory & Deduplication
        $threadMemoryBlock = "";
        if (!empty($recentThreadReplies)) {
            $threadMemoryBlock = "MEMORIA DE HILO RECIENTE (Respuestas ya publicadas a otros seguidores en este mismo post):\n";
            foreach (array_slice($recentThreadReplies, 0, 3) as $idx => $r) {
                $snippet = mb_substr(trim($r), 0, 90);
                $threadMemoryBlock .= "- Ya usada #" . ($idx + 1) . ": \"$snippet...\"\n";
            }
            $threadMemoryBlock .= "DIRECTIVA ANTI-DUPLICACIÓN: Varía el saludo, los verbos y las preguntas de cierre. NUNCA repitas las mismas fórmulas de las respuestas recientes mostradas arriba.\n\n";
        }

        // Module 6: Human-in-the-Loop Continuous Learning Memory
        $learningBlock = "";
        if (!empty($learningExamples)) {
            $learningBlock = "MEMORIA DE APRENDIZAJE HUMANO (Respuestas reales aprobadas o corregidas recientemente por el administrador humano):\n";
            foreach ($learningExamples as $idx => $lex) {
                $cTextSnippet = mb_substr(trim($lex['comment_text'] ?? ''), 0, 100);
                $rTextSnippet = trim($lex['final_reply'] ?? '');
                $statusLabel = !empty($lex['is_gold_example']) ? "⭐ EJEMPLO DE ORO FAVORITO" : (!empty($lex['was_edited']) ? "✏️ HUMANO CORRIGIÓ Y PREFIRIÓ" : "✅ HUMANO APROBÓ");
                $learningBlock .= "- Caso #" . ($idx + 1) . " [$statusLabel]:\n";
                $learningBlock .= "  Comentario seguidor: \"$cTextSnippet\"\n";
                $learningBlock .= "  Respuesta humana definitiva: \"$rTextSnippet\"\n";
            }
            $learningBlock .= "DIRECTIVA DE APRENDIZAJE CONTINUO: Observa con máxima atención el estilo, vocabulario, cercanía y nivel de síntesis que el administrador humano ha aprobado y corregido en los casos de arriba. Adapta tus 3 opciones para reflejar con absoluta precisión este estándar humano preferido.\n\n";
        }

        $keyPhrasesText = !empty($keyPhrases) ? implode(', ', $keyPhrases) : 'Autodominio, Fortaleza mental, Disciplina diaria, Comunidad oficial';
        $forbiddenText = !empty($forbiddenPhrases) ? implode(', ', $forbiddenPhrases) : 'Estimado cliente, Compra ya, Oferta engañosa, Somos un bot';

        $fewShotText = '';
        if (!empty($fewShotExamples)) {
            $fewShotText .= "EJEMPLOS DE ORO DE LA MARCA (Imita este estilo exacto y nivel de naturalidad):\n";
            // Filter or prioritize examples matching current intent
            $examplesToShow = [];
            foreach ($fewShotExamples as $ex) {
                $tag = $ex['tag'] ?? '';
                if ($intent === 'EMOJI_ONLY' && str_contains($tag, 'emojis')) {
                    $examplesToShow[] = $ex;
                } elseif ($intent === 'BRIEF_AGREEMENT' && (str_contains($tag, 'acuerdo') || str_contains($tag, 'minimo'))) {
                    $examplesToShow[] = $ex;
                } elseif ($intent === 'GREETING' && str_contains($tag, 'saludo')) {
                    $examplesToShow[] = $ex;
                } elseif ($intent === 'NEW_FOLLOWER' && str_contains($tag, 'seguidora')) {
                    $examplesToShow[] = $ex;
                } elseif ($intent === 'DISAGREEMENT' && str_contains($tag, 'desacuerdo')) {
                    $examplesToShow[] = $ex;
                } elseif ($intent === 'PERSONAL_STORY' && (str_contains($tag, 'experiencia') || str_contains($tag, 'mujer'))) {
                    $examplesToShow[] = $ex;
                } elseif ($intent === 'QUESTION' && str_contains($tag, 'pregunta')) {
                    $examplesToShow[] = $ex;
                }
            }
            if (empty($examplesToShow)) {
                $examplesToShow = array_slice($fewShotExamples, 0, 5);
            }
            foreach ($examplesToShow as $idx => $ex) {
                $c = $ex['comment'] ?? '';
                $r = $ex['reply'] ?? '';
                $fewShotText .= "Ejemplo #" . ($idx + 1) . ":\n- Comentario de Seguidor: \"$c\"\n- Respuesta Maestra Ideal: \"$r\"\n\n";
            }
        }

        $cleanCommentText = addslashes($commentText);
        $cleanPostCaption = addslashes($postCaption);

        $optionsInstructions = "";
        if ($intent === 'EMOJI_ONLY') {
            $optionsInstructions = <<<OPTS
INSTRUCCIÓN PARA COMENTARIO DE SOLO EMOJIS (HERMES v2 - PROPORCIONALIDAD ESTRICTA):
El seguidor comentó únicamente con emojis. Genera 3 opciones de respuesta:
1. "engagement": [1-2 Emojis]: ÚNICAMENTE 1 o 2 emojis relevantes (ej. '🔥🏛️' o '🏛️✨'). CERO PALABRAS.
2. "conversion": [1-2 Emojis]: 1 o 2 emojis (ej. '🤝🏛️' o '🙌✨'). CERO PALABRAS.
3. "support": [Micro-frase]: MÁXIMO 1 a 3 palabras contundentes con 1 emoji (ej. 'Firmeza total. 🏛️' o 'Así es. 🔥').
PROHIBICIÓN TERMINANTE: CERO oraciones largas, CERO párrafos, CERO discursos.
OPTS;
        } elseif ($intent === 'BRIEF_AGREEMENT') {
            $optionsInstructions = <<<OPTS
INSTRUCCIÓN PARA ACUERDO BREVE (HERMES v2 - 4 A 10 PALABRAS):
El seguidor dejó un acuerdo de 1 palabra o frase mínima (ej. 'Importante', 'Exacto', 'Clave').
Genera 3 opciones de respuesta sobrias y proporcionales (4 a 10 palabras cada una):
1. "engagement": [Ratificación Serena]: 'Así es. Foco en lo esencial.'
2. "conversion": [Principio Estoico Breve]: 'Totalmente. La constancia diaria marca la diferencia. 🏛️'
3. "support": [Firmeza y Templanza]: 'Exacto. Lo que depende de uno es lo que cuenta.'
PROHIBICIÓN TERMINANTE: CERO bienvenidas a la comunidad, CERO discursos, CERO preguntas de cierre.
OPTS;
        } elseif ($intent === 'GREETING') {
            $optionsInstructions = <<<OPTS
INSTRUCCIÓN PARA SALUDO BREVE:
Genera 3 opciones cordiales y sobrias de 4 a 8 palabras (ej. '¡Hola! Qué gusto saludarte. Un gran abrazo. 🤝'). CERO discursos ni bienvenidas solemnes.
OPTS;
        } elseif ($intent === 'NEW_FOLLOWER') {
            $optionsInstructions = <<<OPTS
INSTRUCCIÓN PARA NUEVO SEGUIDOR VERIFICADO:
Genera 3 opciones de bienvenida sobria y cálida de 8 a 15 palabras (ej. 'Bienvenido a Fortaleza Imparable. Aquí forjamos carácter y templanza día a día. 🏛️').
OPTS;
        } elseif ($intent === 'DISAGREEMENT') {
            $optionsInstructions = <<<OPTS
INSTRUCCIÓN PARA DESACUERDO:
Genera 3 opciones con serenidad estoica y distinción conceptual clara (12 a 25 palabras). No confrontes ni intentes ganar la discusión.
OPTS;
        } elseif ($intent === 'CRITICISM') {
            $optionsInstructions = <<<OPTS
INSTRUCCIÓN PARA CRÍTICA AL CONTENIDO:
Genera 3 opciones sobrias y reflexivas (12 a 25 palabras), aceptando la perspectiva con madurez estoica sin ponerte a la defensiva.
OPTS;
        } elseif ($intent === 'PERSONAL_STORY') {
            $optionsInstructions = <<<OPTS
INSTRUCCIÓN PARA LUCHA O HISTORIA PERSONAL:
Genera 3 opciones con empatía humana sobria (15 a 30 palabras): reconocimiento del reto, idea central de resiliencia y templanza estoica. CERO emojis festivos (🔥/❤️/😂 ante el dolor).
OPTS;
        } elseif ($isShortComment) {
            $optionsInstructions = <<<OPTS
INSTRUCCIÓN PARA COMENTARIO BREVE O REACCIÓN (LIBERTAD Y VARIEDAD - HERMES):
El seguidor dejó una reacción breve o acuerdo. Genera 3 variaciones ágiles de 4 a 10 palabras cada una:
1. "engagement": [Validación Cálida & Humana]: Acuerdo auténtico y natural con 1 emoji.
2. "conversion": [Impulso Estoico Breve]: Determinación y templanza sin ventas.
3. "support": [Hermandad & Firmeza]: Remate contundente y fraternal sin soporte técnico.
DIRECTIVA DE VARIEDAD: Cero discursos solemnes, cero preguntas forzadas y máxima frescura en cada opción.
OPTS;
        } else {
            if (!self::COMMERCIAL_SALES_ACTIVE) {
                $optionsInstructions = <<<OPTS
Genera 3 opciones de respuesta con enfoque exclusivo en COMUNIDAD Y FILOSOFÍA ESTOICA (1 sola frase concisa, entre 12 y 25 palabras, CERO VENTAS, CERO ENLACES EN BIO, CERO SOPORTE TÉCNICO):
1. "engagement": [🤝 Conexión & Fraternidad]: Cálida, humana y cercana, validando con empatía fraternal y comprensión real de lo que dijo el seguidor.
2. "conversion": [🏛️ Sabiduría & Fortaleza Estoica]: Fundamentada en principios estoicos de autodominio y temple (CERO ventas).
3. "support": [⚡ Impulso & Determinación]: Motivadora, con garra y disciplina mental inquebrantable (CERO soporte técnico).
OPTS;
            } else {
                $optionsInstructions = <<<OPTS
Genera 3 opciones de respuesta adaptadas a las directrices anteriores sin sonar robótico ni usar frases prohibidas:
1. "engagement": [🤝 Conexión & Empatía]: Cálida, humana, conversacional y cercana.
2. "conversion": [🎯 Conversión & Venta / CTA]: Proactiva, enfocada en valor y orientando a la acción (DM, link, compra).
3. "support": [💡 Autoridad & Solución]: Informativa, clara y profesional, resolviendo dudas.
OPTS;
            }
        }

        $langNames = ['es' => 'ESPAÑOL', 'pt' => 'PORTUGUÊS', 'en' => 'ENGLISH'];
        $langName = $langNames[strtolower($language)] ?? strtoupper($language);

        $languageStrictDirective = <<<LANG
DIRECTIVA ESTRICTA DE IDIOMA Y NO TRADUCCIÓN (OBLIGATORIA):
- IDIOMA OBLIGATORIO DE RESPUESTA: $langName ($language).
- REGLA INQUEBRANTABLE: Responde exclusivamente en $langName. NUNCA traduzcas ni respondas por defecto en español si el idioma esperado es portugués o inglés. No mezcles idiomas salvo nombres propios, citas exactas o términos universales inevitables.
- Cada una de las 3 opciones (engagement, conversion, support) DEBE estar redactada con fluidez nativa y natural en $langName.
LANG;

        return <<<PROMPT
Eres "$personaName", el estratega oficial de comunicación y gestor de comunidad de la marca "$brandName" en $platform.
Industria / Nicho: $brandIndustry.
Directrices y personalidad de la marca: $brandDescription.
Tono configurado: $brandTone.

$languageStrictDirective

CALIBRACIÓN DE IDENTIDAD:
- Nivel de Cercanía & Calidez: $warmthLevel% (Trata a la persona con amabilidad y calidez genuina).
- Nivel de Profundidad / Expertise: $depthLevel% (Aporta respuestas útiles, fundamentadas y de valor).
- Nivel de Firmeza & Enfoque a la Acción: $energyLevel% (Impulsa a la acción con energía y claridad).
- Regla de Pregunta de Cierre: $closingQuestionRule (Si es 'always', remata con una pregunta relevante para fomentar la conversación o cerrar ventas).
- Estilo de Emojis: $emojiStyle.

TRATAMIENTO DEL NOMBRE DEL SEGUIDOR:
$nameInstruction

TRATAMIENTO DE GÉNERO Y VOCATIVOS:
$genderInstruction

FILOSOFÍA Y CONTEXTO CULTURAL DEL POST:
$philosophyContext

PAUTA DE PROPORCIÓN Y LONGITUD:
$proportionalityDirective

$intentGuidance

$learningBlock
$threadMemoryBlock
CONCEPTOS CLAVE A DESTACAR: $keyPhrasesText.
FRASES TOTALMENTE PROHIBIDAS (NUNCA LAS USES): $forbiddenText.

REGLAS ESTRICTAS DE FILOSOFÍA ESTOICA Y VERACIDAD (OBLIGATORIAS):
1. CERO VENTAS Y CERO LINKS COMERCIALES: Esta página NO VENDE cursos, mentorías ni productos. QUEDA TERMINANTEMENTE PROHIBIDO decir "mira el enlace de la bio para inscribirte", "compra aquí", "nuestros cursos" o invitar a adquirir nada.
2. CERO ACCIONES NO REALIZADAS: NUNCA afirmes haber enviado un mensaje directo (DM), correo o realizado acciones externas ("te acabo de enviar un DM", "ya te escribí").
3. CONSULTAS DE ACCESO O PRECIOS: Si alguien pregunta por costos o cursos, aclara amablemente que compartimos reflexiones libres y principios estoicos sin catálogo de venta activo, e invita a un DM si desea charlar fraternalmente.
4. PREGUNTAS CONCEPTUALES Y FILOSÓFICAS: Si el seguidor consulta sobre un concepto, metodología o filosofía estoica (ej. Dicotomía del control, memento mori, amor fati), responde con fundamento, claridad y valor práctico. NUNCA desvíes preguntas conceptuales a soporte técnico de pedidos o reclamos.
5. COMENTARIOS DE SOLO EMOJIS: Si el seguidor comentó solo emojis (ej. 👏👏, 🔥, ❤️, 💪, 🙌), responde de forma MINIMALISTA (~70% solo 1-2 emojis sobrios como 🔥🏛️ o 🤝✨, ~30% 1 a 3 palabras contundentes + 1 emoji como 'Firmeza total. 🏛️' o 'Así es. 🔥'). QUEDA TERMINANTEMENTE PROHIBIDO redactar párrafos explicativos, discursos solemnes, oraciones largas o formular preguntas de cierre a un simple emoji.
6. COMENTARIOS BURLONES, CHISTES O MEMES: Si el seguidor hace un chiste, broma, ironía o comentario cómico (ej. 'al cazo', 'carnitas', risas, emojis 😝/😂), NUNCA te pongas solemne, NUNCA agradezcas como corporación formal ("Apreciamos que dediques tiempo a reflexionar...") y NUNCA hagas preguntas existenciales ("¿cómo buscas aplicarlo hoy?"). Responde con complicidad, ingenio y risa ("Jajaja...", "😅"), manteniendo la respuesta corta y humana.
7. VOCATIVO Y NICKNAMES: Si el seguidor tiene un usuario con números (ej. Samuelongo380) o apodos no verificados, NUNCA uses ese handle como nombre de pila. Habla de tú a tú directamente y con fluidez natural sin vocativos forzados.
8. LECTURA CRÍTICA Y RESPUESTAS DIRECTAS: Si el seguidor hace una pregunta puntual (ej. "¿Quién era ese Minamoto?", "¿De quién es la frase?"), RESPONDE DIRECTAMENTE a lo que pregunta con precisión histórica y cultural (¡CUIDADO: Minamoto no Yoshitsune NO es Miyamoto Musashi!). Jamás te vayas por las ramas ni des discursos genéricos cuando te hacen una pregunta concreta.
9. ERRADICACIÓN DE PREGUNTAS CLICHÉ DE BOT: Queda TERMINANTEMENTE PROHIBIDO cerrar las respuestas con preguntas forzadas de coach o bot como "¿En qué situación o reto buscas aplicarlo hoy?", "¿Cuál consideras tu mayor desafío respecto a esto hoy?" o "¿Cómo lo aplicas en tu vida?". Si el seguidor no hizo una consulta que amerite repregunta, cierra con una frase contundente, fraternidad o sabiduría estoica, NUNCA con una pregunta de relleno.
10. GÉNERO Y PROHIBICIÓN DE 'HERMANO' A MUJERES: Si la seguidora es mujer (identificada arriba), queda TERMINANTEMENTE PROHIBIDO decirle "hermano". Trátala por su nombre, o como "guerrera", "hermana", o con cercanía sin género masculino. Si el género no se conoce, no asumas "hermano" por defecto.
11. STICKERS O GIFS AMIGABLES: Si el seguidor comentó con un sticker de apoyo (apretón de manos, aplauso, ¡Cierto!, emoción/afecto), responde de forma muy agradable, breve (5 a 8 palabras) y con 1 emoji afín.
12. LIBERTAD CREATIVA Y CERO MULETILLAS O DISPARATES: Piensa y reflexiona como un ser humano sabio, empático y consciente. Si alguien expresa una queja, pregunta dolorosa o reflexión existencial sobre la miseria o la dificultad, NUNCA respondas con plantillas de agradecimiento ("gracias por apoyar") ni frases mecánicas. Varía siempre tu vocabulario abordando la virtud, la resiliencia y la dicotomía del control con lenguaje renovado y genuino.
13. BIENVENIDAS RESTRINGIDAS Y VARIADAS: NUNCA des la bienvenida a la comunidad a quien deje un simple acuerdo ('Importante', 'Exacto'), una reflexión o emojis. La bienvenida se reserva EXCLUSIVAMENTE para quienes manifiesten explícitamente ser nuevos seguidores ('te sigo', 'nueva por aquí'). Cuando se dé, NO uses siempre la misma frase ("Bienvenido a la comunidad"). Varía de forma sobria y natural: "Gracias por sumarte. 🙌", "Gracias por estar aquí. Que el contenido te aporte.", "Un gusto tenerte por aquí. Seguimos trabajando en ello. 🤝".
14. ERRADICACIÓN TOTAL DE JERGA: Queda TERMINANTEMENTE PROHIBIDO usar palabras de jerga callejera como 'ñero', 'bro', 'brother', 'pana', 'compa', 'wey', 'parce', 'máquina', 'rey', 'campeón'.
15. PROHIBICIÓN ABSOLUTA DE INVENTAR CONTEXTO Y NOMBRES (HALLUCINATED_CONTEXT): Hermes NO puede inventar nombres, profesiones, vivencias, relaciones personales ni atribuir emociones no expresadas. Si el usuario NO escribió explícitamente su nombre en su comentario (ej. "Me llamo...", "Soy..."), NUNCA te dirijas a él por un nombre de pila. Queda TERMINANTEMENTE PROHIBIDO saludar con "Hola [Nombre]", "Gracias, [Nombre]", "Así es, [Nombre]" ni "Lamento tu pérdida, [Nombre]". Habla de forma directa y sobria.
16. REDUCCIÓN DE CLICHÉS MOTIVACIONALES GENÉRICOS: Queda TERMINANTEMENTE PROHIBIDO usar clichés de autoayuda o frases motivacionales intercambiables como "La perseverancia es la clave del éxito", "Juntos somos más fuertes", "Nunca te rindas", "Sigue luchando", "El éxito está en tus manos", "El espíritu indomable", "Que la disciplina guíe siempre tu camino". Hermes responde con criterio real y específico al comentario, no con aforismos predecibles.

$fewShotText

CONTEXTO ACTUAL:
- Publicación del feed: "$cleanPostCaption".
- Comentario del seguidor: "$cleanCommentText".

$optionsInstructions

Responde únicamente en formato JSON válido:
{
  "detected_comment_language": "código de idioma detectado del seguidor (es, pt o en)",
  "response_language": "$language",
  "engagement": "texto de respuesta 1 en $langName",
  "conversion": "texto de respuesta 2 en $langName",
  "support": "texto de respuesta 3 en $langName",
  "engagement_tips": "breve tip estratégico de por qué esta respuesta conecta con la audiencia"
}
PROMPT;
    }

    /**
     * Find best matching few-shot master example
     */
    private static function findMatchingFewShotExample(string $commentText, array $examples): ?array {
        $textTrim = trim(mb_strtolower($commentText, 'UTF-8'), " .!?\t\n\r\0\x0B");
        foreach ($examples as $ex) {
            $exComment = trim(mb_strtolower($ex['comment'] ?? '', 'UTF-8'), " .!?\t\n\r\0\x0B");
            if (!empty($exComment)) {
                if ($textTrim === $exComment) {
                    return $ex;
                }
                $words = explode(' ', $exComment);
                $matchCount = 0;
                foreach ($words as $w) {
                    if (mb_strlen($w) > 3 && str_contains($textTrim, $w)) {
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
        // HERMES v2.1: Never use authorName or profile handles in replies
        $nameVocative = '';

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

        // Skip tone modulation for hostile/toxic comments or NO_REPLY to preserve strict security and sobriety
        if ($intent === 'toxic_hostile' || $intent === 'HARASSMENT' || $intent === 'TROLL_PROVOCATION' || ($res['action'] ?? '') === 'NO_REPLY') {
            return $res;
        }

        // For EMOJI_ONLY, preserve strict minimal output without adding words or questions
        if ($intent === 'EMOJI_ONLY') {
            return $res;
        }

        $cleanComment = trim($commentText);
        $cleanLen = mb_strlen($cleanComment, 'UTF-8');
        $isTagOnly = (bool)preg_match('/^(@[\w\.\-]+\s*)+$/u', $cleanComment);
        $isVisualReaction = ($intent === 'visual_sticker_reaction' || $intent === 'emoji_reaction');
        $isShort = ($cleanLen <= 25) || $isTagOnly || $isVisualReaction || in_array($intent, ['BRIEF_AGREEMENT', 'GREETING']);

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
                // Tier 4: High Energy / Hype (Never for brief agreements, greetings, criticisms or disagreements)
                if ($isShort && $field === 'engagement' && !in_array($intent, ['BRIEF_AGREEMENT', 'GREETING', 'DISAGREEMENT', 'CRITICISM', 'EMOJI_ONLY']) && !str_contains($text, '⚡') && !str_contains($text, '🔥') && !str_contains($text, '💪')) {
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
                if (!$isShort && !in_array($intent, ['BRIEF_AGREEMENT', 'GREETING', 'DISAGREEMENT', 'CRITICISM', 'EMOJI_ONLY', 'humor_banter_joke']) && !str_contains($text, '?') && !str_contains($text, '¿')) {
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
     * HERMES v2: Robust Linguistic Blacklist Check with Unicode Normalization and Tokenization
     * Prevents false positives (e.g. 'compañero' is NOT 'compa') while detecting slang variants ('ñero', 'ñerito', etc.)
     */
    public static function checkBlacklistViolation(string $text): ?string {
        $normalized = mb_strtolower(trim($text), 'UTF-8');
        // Strip accents for base form comparison
        $unaccented = strtr($normalized, [
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'ñ'
        ]);

        // 1. Multi-word forbidden phrases (street slang / generic coach hype)
        $forbiddenPhrases = [
            'ñero de camino', 'ñeros de camino', 'maquina total', 'máquina total', 'rey mio', 'rey mío',
            'vamos con todo', 'a romperla', 'a por todas', 'sigamos creciendo juntos',
            'la comunidad de guerreros', 'comunidad de guerreros', 'esa energia', 'esa energía',
            'esa vibra', 'nunca te rindas', 'el exito esta en tus manos', 'el éxito está en tus manos',
            'el universo conspira', 'todo pasa por algo', 'tu vibracion atraera', 'tu vibración atraerá',
            'somos un bot', 'soy un bot', 'soy una ia', 'somos una ia', 'como ia', 'como modelo de lenguaje'
        ];

        foreach ($forbiddenPhrases as $fp) {
            if (preg_match('/\b' . preg_quote($fp, '/') . '\b/iu', $unaccented) || preg_match('/\b' . preg_quote($fp, '/') . '\b/iu', $normalized)) {
                return $fp;
            }
        }

        // 2. Tokenize by non-letter boundaries for exact token matching
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', $unaccented, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        // Exact forbidden tokens and controlled variants (avoiding substring false positives like compañero)
        $forbiddenTokens = [
            'ñero', 'ñeros', 'ñerito', 'ñeritos', 'ñerita', 'ñeritas', 'ñerazo',
            'bro', 'brother', 'brothers', 'broder',
            'pana', 'panas', 'panita', 'panitas',
            'parce', 'parces', 'parcero', 'parceros', 'parcerito',
            'wey', 'weyes', 'guey', 'güey', 'gueyes',
            'compa', 'compas', 'compadre', 'compadres', 'compita',
            'man', 'manes',
            'crack', 'cracks',
            'maquina', 'maquinas', 'máquina', 'máquinas',
            'jefe', 'jefazo',
            'rey', 'reicito',
            'campeon', 'campeona', 'campeones', 'campeonas',
            'figura', 'figuras',
            'bestia', 'animal',
            'tio', 'tios', 'tía', 'tias',
            'colega', 'colegas'
        ];

        foreach ($tokens as $t) {
            if (in_array($t, $forbiddenTokens, true)) {
                return $t;
            }
        }

        return null;
    }

    /**
     * HERMES v2.1: Output Pre-Publication Validator (Fail-Closed)
     * Verifies:
     * - Source validation: rejects any heuristic public response (HEURISTIC_SOURCE_FORBIDDEN)
     * - Blacklist check
     * - False Welcomes check
     * - Proportionality for EMOJI_ONLY
     * - Bot admission check
     * - Generic Coach clichés and questions
     * - Gender Vocatives Check: rejects hermano, guerrero, campeón, etc., when gender is undeclared in commentText
     * - Hallucinated Name Detection: rejects any profile or assumed name not explicitly written in commentText
     * - Text Quality & Corruption Detection: rejects truncated text, broken fragments (rtir, rtes), or garbled output
     */
    public static function validateAndSanitizeReply(string $reply, string $intent = '', int $retryCount = 0, string $commentText = '', string $source = ''): array {
        $clean = trim($reply);
        if (empty($clean)) {
            return ['valid' => false, 'reason' => 'EMPTY_RESPONSE', 'action' => 'NO_REPLY', 'reply' => ''];
        }

        // 0. Prohibit public replies from heuristic engine
        if (!empty($source) && (str_starts_with($source, 'heuristic') || str_starts_with($source, 'local'))) {
            return [
                'valid' => false,
                'reason' => 'HEURISTIC_SOURCE_FORBIDDEN',
                'token' => $source,
                'action' => 'NO_REPLY',
                'reply' => ''
            ];
        }

        $cleanLower = mb_strtolower($clean, 'UTF-8');
        $cLower = mb_strtolower($commentText, 'UTF-8');

        // 1. Blacklist check
        $blacklistHit = self::checkBlacklistViolation($clean);
        if ($blacklistHit !== null) {
            return [
                'valid' => false,
                'reason' => 'BLACKLIST_VIOLATION',
                'token' => $blacklistHit,
                'action' => ($retryCount < 1 ? 'REGENERATE' : 'NO_REPLY'),
                'reply' => ''
            ];
        }

        // 2. Text Quality & Corrupted Words / Fragments Check
        if (preg_match('/\b(rtes|rtir|rtiendo|compañ\b|ñer\b)\b/iu', $cleanLower, $mCorrupt)) {
            return [
                'valid' => false,
                'reason' => 'CORRUPTED_TEXT',
                'token' => $mCorrupt[1],
                'action' => ($retryCount < 1 ? 'REGENERATE' : 'NO_REPLY'),
                'reply' => ''
            ];
        }
        if (preg_match('/\b(de|la|el|en|que|con|por|un|una)\s*$/iu', $clean)) {
            return [
                'valid' => false,
                'reason' => 'TRUNCATED_TEXT',
                'action' => ($retryCount < 1 ? 'REGENERATE' : 'NO_REPLY'),
                'reply' => ''
            ];
        }

        // 3. Gender Vocatives Check: Reject if commentText does not explicitly declare gender
        $genderDeclared = false;
        if (!empty($commentText)) {
            $genderDeclared = (bool)preg_match('/\b(soy mujer|como mujer|de mujer|siendo mujer|agradecida|cansada|encantada|preparada|dispuesta|sola|tranquila|segura|abrumada|orgullosa|madre|abuela|esposa|chica|niña|mujer|soy hombre|como hombre|de hombre|siendo hombre|agradecido|cansado|encantado|preparado|dispuesto|solo|tranquilo|seguro|abrumado|orgulloso|padre|abuelo|esposo|chico|niño|hombre)\b/iu', $cLower);
        }
        if (!$genderDeclared) {
            if (preg_match('/\b(hermano|hermana|hermanos|hermanas|amigo|amiga|amigos|amigas|guerrero|guerrera|guerreros|guerreras|campe[oó]n|campeona|campeones|campeonas|bienvenido|bienvenida|bienvenidos|bienvenidas|nuevo guerrero|nueva guerrera)\b/iu', $cleanLower, $mGen)) {
                return [
                    'valid' => false,
                    'reason' => 'GENDER_ASSUMPTION',
                    'token' => $mGen[1],
                    'action' => ($retryCount < 1 ? 'REGENERATE' : 'NO_REPLY'),
                    'reply' => ''
                ];
            }
        }

        // 4. False Welcome check: Never welcome someone unless intent is explicitly NEW_FOLLOWER
        if ($intent !== 'NEW_FOLLOWER') {
            $welcomePatterns = [
                'bienvenido a la comunidad', 'bienvenida a la comunidad',
                'bienvenido a fortaleza', 'bienvenida a fortaleza',
                'sumarte a esta comunidad', 'sumarte a la comunidad',
                'unirte a esta comunidad', 'unirte a la comunidad',
                'bienvenido a nuestra comunidad', 'bienvenida a nuestra comunidad',
                'gracias por unirte', 'gracias por sumarte',
                'bienvenido', 'bienvenida'
            ];
            foreach ($welcomePatterns as $wp) {
                if (preg_match('/\b' . preg_quote($wp, '/') . '\b/iu', $cleanLower)) {
                    return [
                        'valid' => false,
                        'reason' => 'FALSE_WELCOME',
                        'token' => $wp,
                        'action' => ($retryCount < 1 ? 'REGENERATE' : 'NO_REPLY'),
                        'reply' => ''
                    ];
                }
            }
        }

        // 5. Proportionality for EMOJI_ONLY (Must not exceed 3 plain words)
        if ($intent === 'EMOJI_ONLY') {
            $wordsOnly = preg_split('/\s+/u', trim(preg_replace('/[\p{P}\p{S}]/u', '', $clean)), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            if (count($wordsOnly) > 3) {
                return [
                    'valid' => false,
                    'reason' => 'EMOJI_OVERRESPONSE',
                    'action' => ($retryCount < 1 ? 'REGENERATE' : 'NO_REPLY'),
                    'reply' => ''
                ];
            }
        }

        // 6. Bot admission check
        if (preg_match('/\b(como modelo de lenguaje|como inteligencia artificial|soy una ia|soy un bot|somos una ia|somos un bot|como ia)\b/iu', $cleanLower)) {
            return [
                'valid' => false,
                'reason' => 'BOT_ADMISSION',
                'action' => ($retryCount < 1 ? 'REGENERATE' : 'NO_REPLY'),
                'reply' => ''
            ];
        }

        // 7. Generic Coach Cliché questions check
        if (preg_match('/\b(en qu[eé] buscas aplicarlo|en qu[eé] situaci[oó]n buscas aplicarlo|cu[aá]l consideras tu mayor desaf[ií]o|c[oó]mo lo aplicas en tu vida)\b/iu', $cleanLower)) {
            return [
                'valid' => false,
                'reason' => 'GENERIC_COACH_QUESTION',
                'action' => ($retryCount < 1 ? 'REGENERATE' : 'NO_REPLY'),
                'reply' => ''
            ];
        }

        // 8. Generic Motivational Coach Clichés check
        $coachCliches = [
            'la perseverancia es la clave',
            'la perseverancia es la llama',
            'juntos somos más fuertes',
            'nunca te rindas',
            'sigue luchando',
            'el éxito está en tus manos',
            'el exito esta en tus manos',
            'que la disciplina guíe siempre',
            'espíritu indomable',
            'espiritu indomable',
            'vamos con todo a romperla',
            'a romperla con todo',
            'las cosas pasan por algo',
            'el universo conspira',
            'tú puedes vencer cualquier',
            'tu puedes vencer cualquier'
        ];
        foreach ($coachCliches as $cc) {
            if (str_contains($cleanLower, $cc)) {
                return [
                    'valid' => false,
                    'reason' => 'GENERIC_COACH_CLICHE',
                    'token' => $cc,
                    'action' => ($retryCount < 1 ? 'REGENERATE' : 'NO_REPLY'),
                    'reply' => ''
                ];
            }
        }

        // 9. Hallucinated Name Detection
        // A name is ONLY allowed if explicitly present in commentText
        if (preg_match_all('/(?:,\s*|\b(?:gracias|así es|asi es|hola|lamento|saludos|totalmente|de acuerdo|un gusto)\s+)([A-ZÁÉÍÓÚ][a-záéíóúñ]+)\b/u', $clean, $mNames)) {
            $nonNames = ['a', 'al', 'por', 'de', 'en', 'con', 'mi', 'su', 'la', 'el', 'los', 'las', 'que', 'pero', 'foco', 'paso', 'totalmente', 'firmeza', 'buena', 'buen', 'muchas', 'gran', 'un', 'una', 'fortaleza', 'amigo', 'amiga', 'hermano', 'hermana', 'guerrero', 'guerrera', 'así', 'asi', 'gracias', 'hola', 'saludos', 'bienvenido', 'bienvenida', 'dios', 'fuerza', 'serenidad', 'mente', 'temple', 'carácter', 'caracter', 'disciplina', 'camino', 'respira', 'recuerda'];
            foreach ($mNames[1] as $detectedName) {
                if (!in_array(mb_strtolower($detectedName, 'UTF-8'), $nonNames, true)) {
                    if (empty($commentText) || !preg_match('/\b' . preg_quote($detectedName, '/') . '\b/iu', $commentText)) {
                        return [
                            'valid' => false,
                            'reason' => 'HALLUCINATED_NAME',
                            'token' => $detectedName,
                            'action' => ($retryCount < 1 ? 'REGENERATE' : 'NO_REPLY'),
                            'reply' => ''
                        ];
                    }
                }
            }
        }

        // Common names check (rejecting profile names leaking into reply when absent in comment)
        $commonNames = ['carlos', 'diego', 'ana', 'laura', 'luis', 'maría', 'maria', 'juan', 'pedro', 'jorge', 'elena', 'sofia', 'sofía', 'marcos', 'andres', 'andrés', 'claudia', 'hector', 'héctor', 'ricardo', 'axel', 'julian', 'julián', 'mario', 'roberto', 'manuel'];
        foreach ($commonNames as $cn) {
            if (preg_match('/\b' . preg_quote($cn, '/') . '\b/iu', $cleanLower)) {
                if (empty($commentText) || !preg_match('/\b' . preg_quote($cn, '/') . '\b/iu', $cLower)) {
                    return [
                        'valid' => false,
                        'reason' => 'HALLUCINATED_NAME',
                        'token' => $cn,
                        'action' => ($retryCount < 1 ? 'REGENERATE' : 'NO_REPLY'),
                        'reply' => ''
                    ];
                }
            }
        }

        return [
            'valid' => true,
            'reason' => 'VALIDATED',
            'action' => 'REPLY',
            'reply' => $clean
        ];
    }

    /**
     * Guarantee no forbidden phrases appear in generated outputs
     */
    private static function sanitizeRepliesWithForbidden(array $res, array $forbiddenPhrases): array {
        $clicheAlternates = [
            'Así es. Foco en lo esencial.',
            'Pequeñas victorias diarias forjan el carácter. 🏛️',
            'Foco en lo que sí depende de uno. ✨',
            'Paso firme y mente clara. 🏛️',
            'La templanza diaria marca la verdadera diferencia. ⚡'
        ];
        $altIdx = 0;

        foreach (['engagement', 'conversion', 'support'] as $key) {
            if (isset($res[$key]) && is_string($res[$key])) {
                foreach ($forbiddenPhrases as $badPhrase) {
                    $trimmed = trim($badPhrase);
                    if (!empty($trimmed)) {
                        // Word-boundary replacement prevents cutting into valid words (e.g. compa in compartir)
                        $res[$key] = preg_replace('/\b' . preg_quote($trimmed, '/') . '\b/iu', '', $res[$key]);
                    }
                }

                // Interceptar cliché obsesivo
                if (preg_match('/(?:con (?:toda )?la verdad|la verdad (?:siempre|como) (?:delante|adelante|faro|br[úu]jula|libera)|con la verdad (?:por|siempre)?\s*(?:delante|adelante|como faro))/iu', $res[$key])) {
                    $replacement = $clicheAlternates[$altIdx % count($clicheAlternates)];
                    $altIdx++;
                    $res[$key] = $replacement;
                }

                // Interceptar cualquier autoincriminación o respuesta tonta a ataques de bot
                if (preg_match('/(?:jajaja,?\s*(?:muy cierto|tienes raz[oó]n|es verdad)|soy una ia|somos una ia|ni escribir s[eé]|como ia|como modelo)/iu', $res[$key])) {
                    $res[$key] = 'La serenidad y el autodominio están por encima de cualquier ruido externo. Firmeza y buen camino. 🏛️';
                }

                $res[$key] = preg_replace('/\s+([.,;:!?])/u', '$1', $res[$key]);
                $res[$key] = preg_replace('/\s{2,}/u', ' ', trim($res[$key]));
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
        // HERMES v2 Official Master Few-Shot Examples (Fortaleza Imparable)
        return [
            [
                'tag' => 'v2_ejemplo_1_emojis',
                'comment' => '❤️🍀🔥',
                'reply' => '🔥🏛️'
            ],
            [
                'tag' => 'v2_ejemplo_2_acuerdo',
                'comment' => 'Importante.',
                'reply' => 'Así es. Foco en lo esencial.'
            ],
            [
                'tag' => 'v2_ejemplo_3_saludo',
                'comment' => 'Buenos días.',
                'reply' => 'Buenos días. Paso firme. 🏛️'
            ],
            [
                'tag' => 'v2_ejemplo_4_nueva_seguidora',
                'comment' => 'Nueva por aquí. Acabo de seguir la página.',
                'reply' => 'Bienvenida a Fortaleza Imparable. 🏛️'
            ],
            [
                'tag' => 'v2_ejemplo_5_experiencia_personal',
                'comment' => 'Durante años pensé que ser fuerte significaba no mostrar cansancio. Con el tiempo entendí que también hay fuerza en saber cuándo detenerse para poder continuar.',
                'reply' => 'Reconocer nuestros límites también forma parte del autodominio. Descansar no siempre es retroceder; a veces es prepararse para continuar.'
            ],
            [
                'tag' => 'v2_ejemplo_6_mujer_sin_jerga',
                'comment' => 'Se trata de descansar y después retomar, pero no renunciar. Mi condición física y falta de concentración prolongada me impide terminar de una vez tareas que requieren mucho esfuerzo.',
                'reply' => 'Exactamente. Saber dosificar el esfuerzo también es disciplina. No todo se conquista de una sola vez.'
            ],
            [
                'tag' => 'v2_ejemplo_7_spam',
                'comment' => 'Ciencia del Energismo [imagen: flyer promocional con QR, enlaces y publicidad]',
                'reply' => 'NO_REPLY'
            ],
            [
                'tag' => 'v2_ejemplo_8_desacuerdo',
                'comment' => 'No estoy de acuerdo. Aguantarlo todo no significa ser fuerte.',
                'reply' => 'Es una distinción importante. La fortaleza también consiste en saber qué merece ser soportado y qué debe cambiarse.'
            ],
            [
                'tag' => 'v2_ejemplo_9_comentario_minimo',
                'comment' => 'Clave.',
                'reply' => 'Así es. Lo esencial primero. 🏛️'
            ],
            [
                'tag' => 'v2_ejemplo_10_comentario_profundo',
                'comment' => 'He aprendido que muchas veces no me derrotaban los problemas, sino mi necesidad de que las cosas ocurrieran exactamente como yo quería. Cuando acepté eso, empecé a vivir con mucha más calma.',
                'reply' => 'Ahí aparece una de las lecciones más difíciles del autodominio: no controlar el mundo, sino nuestra relación con aquello que no podemos cambiar.'
            ],
            [
                'tag' => 'v2_ejemplo_11_troll',
                'comment' => 'Otra cuenta de frases motivacionales vacías 😂',
                'reply' => 'NO_REPLY'
            ],
            [
                'tag' => 'v2_ejemplo_12_pregunta',
                'comment' => '¿Entonces el estoicismo significa no tener emociones?',
                'reply' => 'No. El estoicismo no busca eliminar las emociones, sino aprender a gobernar nuestra respuesta ante ellas.'
            ]
        ];
    }

    /**
     * Record human-approved or human-edited reply feedback for continuous learning
     */
    public static function recordLearningFeedback(
        int $userId,
        int $brandVoiceId,
        string $commentText,
        string $finalReply,
        string $originalSuggestion = '',
        bool $wasEdited = false,
        bool $isGold = false,
        ?int $commentId = null
    ): bool {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                INSERT INTO ai_learning_feedback (
                    user_id, brand_voice_id, comment_id, comment_text, original_suggestion, final_reply, was_edited, is_gold_example
                ) VALUES (
                    :uid, :bvid, :cid, :comment, :orig, :final, :edited, :gold
                )
            ");
            $stmt->execute([
                ':uid' => $userId,
                ':bvid' => $brandVoiceId > 0 ? $brandVoiceId : 1,
                ':cid' => $commentId,
                ':comment' => $commentText,
                ':orig' => !empty($originalSuggestion) ? $originalSuggestion : null,
                ':final' => $finalReply,
                ':edited' => $wasEdited ? 1 : 0,
                ':gold' => $isGold ? 1 : 0
            ]);

            // If marked as gold example, also update brand_voices.few_shot_examples
            if ($isGold && $brandVoiceId > 0) {
                self::addGoldExampleToBrandVoice($pdo, $brandVoiceId, $userId, $commentText, $finalReply);
            }

            return true;
        } catch (Throwable $e) {
            error_log("Error in recordLearningFeedback: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add a gold example into brand_voices few_shot_examples without duplicating
     */
    public static function addGoldExampleToBrandVoice(PDO $pdo, int $brandVoiceId, int $userId, string $comment, string $reply): void {
        try {
            $stmt = $pdo->prepare("SELECT id, few_shot_examples FROM brand_voices WHERE id = :id AND user_id = :uid LIMIT 1");
            $stmt->execute([':id' => $brandVoiceId, ':uid' => $userId]);
            $bvRow = $stmt->fetch(PDO::FETCH_ASSOC);

            // If not found by given ID, fallback to the user's primary/active brand voice
            if (!$bvRow) {
                $stmt = $pdo->prepare("SELECT id, few_shot_examples FROM brand_voices WHERE user_id = :uid ORDER BY is_active DESC, id ASC LIMIT 1");
                $stmt->execute([':uid' => $userId]);
                $bvRow = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            if (!$bvRow) {
                return;
            }

            $brandVoiceId = (int)$bvRow['id'];
            $raw = $bvRow['few_shot_examples'] ?? '';
            $examples = !empty($raw) ? json_decode($raw, true) : [];
            if (!is_array($examples)) $examples = [];

            // Check if comment already exists to prevent duplication
            foreach ($examples as $ex) {
                if (trim($ex['comment'] ?? '') === trim($comment)) {
                    return;
                }
            }

            // Prepend new gold example and keep max 12
            array_unshift($examples, [
                'tag' => 'ejemplo_oro_humano',
                'comment' => $comment,
                'reply' => $reply
            ]);
            $examples = array_slice($examples, 0, 12);

            $upStmt = $pdo->prepare("UPDATE brand_voices SET few_shot_examples = :ex WHERE id = :id AND user_id = :uid");
            $upStmt->execute([
                ':ex' => json_encode($examples, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                ':id' => $brandVoiceId,
                ':uid' => $userId
            ]);
            CacheService::invalidateBrandVoice($userId);
        } catch (Throwable $e) {
            error_log("Error in addGoldExampleToBrandVoice: " . $e->getMessage());
        }
    }

    /**
     * Fetch recent human-approved / human-edited examples for dynamic few-shot learning
     */
    public static function fetchRecentLearningExamples(PDO $pdo, int $userId, int $brandVoiceId = 1, int $limit = 4): array {
        try {
            $stmt = $pdo->prepare("
                SELECT comment_text, original_suggestion, final_reply, was_edited, is_gold_example
                FROM ai_learning_feedback
                WHERE user_id = :uid AND (brand_voice_id = :bvid OR brand_voice_id = 1)
                ORDER BY is_gold_example DESC, id DESC
                LIMIT :lim
            ");
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':bvid', $brandVoiceId, PDO::PARAM_INT);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }
}
