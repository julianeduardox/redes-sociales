<?php
/**
 * ══════════════════════════════════════════════════════════════════════════════
 * 🏛️ XINDRO AI Copilot - Atenea Multi-Layer Micro-Linguistic & Post DNA Service
 * Deconstrucción multi-capa (Placa + Caption + Hook + Conflicto + Reversión + Payoff + CTA)
 * e Intervalos de Wilson estadísticamente puros.
 * 100% Local en PHP y SQLite (Coste de tokens: $0.00).
 * ══════════════════════════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/../config/database.php';

class AteneaMicroDnaService {

    public const FAMILY_COND_REVERSAL_PAYOFF   = 'COND_REVERSAL_PAYOFF';
    public const FAMILY_QUESTION_REVELATION    = 'QUESTION_REVELATION';
    public const FAMILY_ASSERTION_CONTRADICTION= 'ASSERTION_CONTRADICTION';
    public const FAMILY_NEGATION_IDENTITY      = 'NEGATION_IDENTITY';
    public const FAMILY_OBSERVATION_PARADOX    = 'OBSERVATION_PARADOX';
    public const FAMILY_SHORT_APHORISM         = 'SHORT_APHORISM';
    public const FAMILY_CAUSE_CONSEQUENCE      = 'CAUSE_CONSEQUENCE';
    public const FAMILY_COMPLEX_REFLECTION     = 'COMPLEX_REFLECTION';
    public const FAMILY_EMPTY_SEMANTIC         = 'EMPTY_SEMANTIC';

    /**
     * Deconstrucción Multi-Capa: Placa Visual + Caption + Hook + Conflicto + Reversión + Payoff
     */
    public static function parsePost(string $placaText, string $captionText): array {
        $cleanPlaca = trim($placaText);
        $cleanCaption = trim($captionText);

        // Remover hashtags finales para análisis limpio del caption
        $captionWithoutHashtags = preg_replace('/#\w+/u', '', $cleanCaption);
        $captionWithoutHashtags = trim(preg_replace('/\s+/', ' ', $captionWithoutHashtags));

        // 1. ANÁLISIS DE LA CAPA VISUAL / PLACA
        $placaAnalysis = self::classifyFragment($cleanPlaca);

        // 2. EXTRACCIÓN DE ELEMENTOS CLAVE DEL CAPTION
        // 2.1 Hook Textual: primera oración o primera línea del caption
        $captionLines = array_values(array_filter(array_map('trim', explode("\n", $cleanCaption))));
        $captionHook = $captionLines[0] ?? '';
        if (mb_strlen($captionHook, 'UTF-8') > 160) {
            $parts = preg_split('/(?<=[.!?])\s+/u', $captionHook, 2);
            $captionHook = $parts[0] ?? $captionHook;
        }

        // 2.2 Frase Nuclear / Main Statement:
        // Prioridad A: Frase entre comillas (“...” o "...") en el caption
        $captionMainStatement = '';
        if (preg_match('/[“"«]([^”"»]{8,220})[”"»]/u', $cleanCaption, $mQuote)) {
            $captionMainStatement = trim($mQuote[1]);
        } elseif (count($captionLines) > 1 && mb_strlen($captionLines[1], 'UTF-8') >= 15) {
            $captionMainStatement = $captionLines[1];
        } else {
            $captionMainStatement = $captionHook;
        }

        // 2.3 CTA / Pregunta de Cierre
        $ctaText = '';
        for ($i = count($captionLines) - 1; $i >= 0; $i--) {
            $line = $captionLines[$i];
            if (str_starts_with($line, '#')) continue;
            if (str_contains($line, '¿') || str_contains($line, '?') || str_contains(mb_strtolower($line), 'comenta') || str_contains(mb_strtolower($line), 'cuál')) {
                $ctaText = $line;
                break;
            }
        }

        // 2.4 Clasificación del Caption
        $captionAnalysis = self::classifyFragment($captionMainStatement ?: $captionHook ?: $captionWithoutHashtags);

        // 3. ANÁLISIS SEMÁNTICO Y DENSIDAD
        // Detectar si la publicación es semánticamente vacía (solo emojis o menos de 2 palabras con significado)
        $placaWords = $cleanPlaca !== '' ? count(preg_split('/\s+/', $cleanPlaca)) : 0;
        $captionWords = $captionWithoutHashtags !== '' ? count(preg_split('/\s+/', $captionWithoutHashtags)) : 0;
        $totalWords = $placaWords + $captionWords;

        $semanticDensity = 'MEDIUM';
        if ($totalWords <= 2 || preg_match('/^[\p{Emoji}\s.,!?-]+$/u', $cleanPlaca . $captionWithoutHashtags)) {
            $semanticDensity = 'EMPTY';
            $placaAnalysis['family'] = self::FAMILY_EMPTY_SEMANTIC;
            $captionAnalysis['family'] = self::FAMILY_EMPTY_SEMANTIC;
        } elseif ($captionWords < 15 && $placaWords < 8) {
            $semanticDensity = 'LOW';
        } elseif ($placaAnalysis['has_identity'] || $captionAnalysis['has_identity'] || $placaAnalysis['has_reversal'] || $captionAnalysis['has_reversal']) {
            $semanticDensity = 'HIGH';
        }

        // 4. ESTRUCTURA PSICOLÓGICA (CONFLICTO, REVERSIÓN, PAYOFF, EMOCIÓN, IDENTIDAD)
        $conflict = self::extractConflict($cleanPlaca, $captionMainStatement, $captionWithoutHashtags);
        $reversal = $captionAnalysis['reversal_segment'] ?: $placaAnalysis['reversal_segment'];
        $payoff = $captionAnalysis['payoff_segment'] ?: $placaAnalysis['payoff_segment'];

        $hasCondition = $placaAnalysis['has_condition'] || $captionAnalysis['has_condition'];
        $hasReversal = $placaAnalysis['has_reversal'] || $captionAnalysis['has_reversal'];
        $hasQuestion = $placaAnalysis['has_question'] || $captionAnalysis['has_question'] || ($ctaText !== '');
        $hasIdentity = $placaAnalysis['has_identity'] || $captionAnalysis['has_identity'];

        // Nivel de abstracción global
        $abstractionLevel = 'BALANCED';
        if ($semanticDensity === 'EMPTY') {
            $abstractionLevel = 'EMPTY';
        } elseif ($placaAnalysis['abstraction'] === 'CONCRETE' || $captionAnalysis['abstraction'] === 'CONCRETE') {
            $abstractionLevel = 'CONCRETE';
        } elseif ($placaAnalysis['abstraction'] === 'ABSTRACT' && $captionAnalysis['abstraction'] === 'ABSTRACT') {
            $abstractionLevel = 'ABSTRACT';
        }

        // 5. DETERMINACIÓN DE FAMILIA PRIMARIA Y ARQUETIPO GLOBAL
        // Si el caption tiene una estructura de alta carga (ej. COND_REVERSAL_PAYOFF), prevalece sobre el short hook de la placa
        $primaryFamily = $placaAnalysis['family'];
        $syntaxArchetype = $placaAnalysis['archetype'];

        if ($captionAnalysis['family'] === self::FAMILY_COND_REVERSAL_PAYOFF) {
            $primaryFamily = self::FAMILY_COND_REVERSAL_PAYOFF;
            $syntaxArchetype = "Placa: [{$placaAnalysis['family']}] + Caption: [Condicional con Reversión]";
        } elseif ($captionAnalysis['family'] === self::FAMILY_ASSERTION_CONTRADICTION) {
            $primaryFamily = self::FAMILY_ASSERTION_CONTRADICTION;
            $syntaxArchetype = "Placa: [{$placaAnalysis['family']}] + Caption: [Quiebre de Contradicción]";
        } elseif ($placaAnalysis['family'] === self::FAMILY_SHORT_APHORISM && $captionAnalysis['family'] !== self::FAMILY_SHORT_APHORISM) {
            $primaryFamily = $captionAnalysis['family'];
            $syntaxArchetype = "Placa Corta [{$cleanPlaca}] + Caption [{$captionAnalysis['family']}]";
        }

        return [
            // Capa 1: Visual / Placa DNA
            'placa_text' => $cleanPlaca,
            'placa_family' => $placaAnalysis['family'],
            'placa_archetype' => $placaAnalysis['archetype'],

            // Capa 2: Caption DNA
            'caption_hook' => $captionHook,
            'caption_main_statement' => $captionMainStatement,
            'caption_family' => $captionAnalysis['family'],
            'caption_archetype' => $captionAnalysis['archetype'],
            'cta_text' => $ctaText,

            // Capa 3: Psychological DNA
            'conflict_statement' => $conflict,
            'reversal_statement' => $reversal,
            'payoff_statement' => $payoff,

            // Capa 4: Micro-Linguistic DNA
            'sentence_family' => $primaryFamily,
            'syntax_archetype' => $syntaxArchetype,
            'opening_segment' => $placaAnalysis['opening_segment'] ?: $captionHook,
            'tension_segment' => $conflict,
            'reversal_segment' => $reversal,
            'payoff_segment' => $payoff,
            'word_count' => $totalWords,
            'character_count' => mb_strlen($cleanPlaca . ' ' . $cleanCaption, 'UTF-8'),
            'punctuation_cadence' => $placaAnalysis['cadence'] . ' | ' . $captionAnalysis['cadence'],
            'has_condition' => $hasCondition ? 1 : 0,
            'has_reversal' => $hasReversal ? 1 : 0,
            'has_question' => $hasQuestion ? 1 : 0,
            'has_identity_marker' => $hasIdentity ? 1 : 0,
            'abstraction_level' => $abstractionLevel,
            'semantic_density' => $semanticDensity
        ];
    }

    /**
     * Clasifica un fragmento textual específico en una de las 7 familias sintácticas.
     */
    private static function classifyFragment(string $rawText): array {
        $clean = trim($rawText);
        $clean = str_replace(['“', '”', '«', '»', '"'], '', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);

        if ($clean === '' || preg_match('/^[\p{Emoji}\s.,!?-]+$/u', $clean)) {
            return [
                'family' => self::FAMILY_EMPTY_SEMANTIC,
                'archetype' => 'Contenido Semánticamente Vacío (Ruido/Emojis)',
                'opening_segment' => $clean,
                'reversal_segment' => '',
                'payoff_segment' => $clean,
                'cadence' => '',
                'has_condition' => false,
                'has_reversal' => false,
                'has_question' => false,
                'has_identity' => false,
                'abstraction' => 'EMPTY'
            ];
        }

        $lower = mb_strtolower($clean, 'UTF-8');
        $words = preg_split('/\s+/', $clean);
        $wCount = count($words);

        preg_match_all('/[.,;:¿?¡!—–\-]/u', $clean, $pM);
        $cadence = implode(' ', $pM[0] ?? []);

        $hasQuestion = (str_contains($clean, '¿') || str_contains($clean, '?'));
        
        $hasCondition = false;
        if (preg_match('/^\s*(si|cuando|en cuanto|mientras|cada vez que)\b/iu', $clean) || preg_match('/\b(si no|a menos que)\b/iu', $clean)) {
            $hasCondition = true;
        }

        $hasReversal = false;
        $reversalKeywords = ['pero', 'sin embargo', 'yo le', 'en cambio', 'antes que', 'mas no', 'al contrario', 'lejos de', 'en vez de'];
        foreach ($reversalKeywords as $rw) {
            if (str_contains($lower, $rw)) { $hasReversal = true; break; }
        }

        $identityKeywords = [
            'guerrero', 'samurái', 'samurai', 'sabio', 'hombre', 'esclavo', 'maestro', 'rey',
            'débil', 'debil', 'cobarde', 'tirano', 'carácter', 'caracter', 'disciplina', 'soberanía',
            'soberania', 'mediocre', 'líder', 'lider', 'vencedor', 'mentalidad', 'firmeza'
        ];
        $hasIdentity = false;
        foreach ($identityKeywords as $iw) {
            if (preg_match('/\b' . preg_quote($iw, '/') . '\b/iu', $lower)) { $hasIdentity = true; break; }
        }

        $concreteWords = ['espada', 'tormenta', 'sangre', 'cadenas', 'roca', 'mandíbula', 'mandibula', 'trinchera', 'vaina', 'fuego', 'hueso', 'herida', 'cuerpo', 'golpe', 'puño', 'escudo'];
        $abstractWords = ['trascendencia', 'universo', 'dimensión', 'dimension', 'energía', 'energia', 'espiritualidad', 'vibración', 'vibracion', 'éxito', 'exito', 'trinidad'];
        
        $cCount = 0; foreach ($concreteWords as $w) if (str_contains($lower, $w)) $cCount++;
        $aCount = 0; foreach ($abstractWords as $w) if (str_contains($lower, $w)) $aCount++;
        $abstraction = ($cCount >= 2) ? 'CONCRETE' : (($aCount >= 1 && $cCount === 0) ? 'ABSTRACT' : 'BALANCED');

        // Segmentación y clasificación en las familias sintácticas
        $family = self::FAMILY_COMPLEX_REFLECTION;
        $archetype = 'Reflexión Aforística Descriptiva';
        $opening = $clean;
        $reversalSeg = '';
        $payoff = $clean;

        if ($hasCondition && $hasReversal) {
            $family = self::FAMILY_COND_REVERSAL_PAYOFF;
            $archetype = 'Condicional Causa-Efecto con Reversión de Poder';
            // Dividir en punto de quiebre
            $parts = preg_split('/\b(yo le|pero|sin embargo|en cambio)\b/iu', $clean, 2, PREG_SPLIT_DELIM_CAPTURE);
            if (count($parts) >= 3) {
                $opening = trim($parts[0]);
                $reversalSeg = trim($parts[1] . ' ' . $parts[2]);
                $payoff = trim($parts[2]);
            }
        } elseif ($hasQuestion) {
            $family = self::FAMILY_QUESTION_REVELATION;
            $archetype = 'Interrogación Retórica Frontal con Revelación';
            if (preg_match('/^(.*?[?])\s*(.*)$/us', $clean, $mQ)) {
                $opening = trim($mQ[1]);
                $payoff = trim($mQ[2]) ?: trim($mQ[1]);
            }
        } elseif (preg_match('/^\s*(no|nunca|jamás|deja de)\b/iu', $clean) && $hasIdentity) {
            $family = self::FAMILY_NEGATION_IDENTITY;
            $archetype = 'Negación Inicial con Apelación a la Identidad';
            $parts = preg_split('/[,;:—–]/u', $clean, 2);
            $opening = trim($parts[0]);
            $payoff = trim($parts[1] ?? $parts[0]);
        } elseif (preg_match('/\b(nos dijeron|crees que|esperas que|confundimos|parece)\b/iu', $lower) || str_contains($lower, 'mentira') || str_contains($lower, 'engaño')) {
            $family = self::FAMILY_ASSERTION_CONTRADICTION;
            $archetype = 'Afirmación Común con Quiebre de Contradicción';
            $parts = preg_split('/[,;:—–]/u', $clean, 2);
            $opening = trim($parts[0]);
            $payoff = trim($parts[1] ?? $parts[0]);
        } elseif (str_contains($lower, 'precio') || str_contains($lower, 'cuesta') || str_contains($lower, 'más que') || str_contains($lower, 'mas que') || str_contains($lower, 'menos que')) {
            $family = self::FAMILY_OBSERVATION_PARADOX;
            $archetype = 'Observación Contraintuitiva con Paradoja de Valor';
        } elseif ($wCount <= 10) {
            $family = self::FAMILY_SHORT_APHORISM;
            $archetype = 'Axioma Corto Lapidario';
        } elseif (preg_match('/\b(quien|el que|aquel que|todo aquel)\b/iu', $lower) || str_contains($lower, 'termina') || str_contains($lower, 'conduce')) {
            $family = self::FAMILY_CAUSE_CONSEQUENCE;
            $archetype = 'Ley Causa-Consecuencia Ineludible';
        }

        return [
            'family' => $family,
            'archetype' => $archetype,
            'opening_segment' => $opening,
            'reversal_segment' => $reversalSeg,
            'payoff_segment' => $payoff,
            'cadence' => $cadence,
            'has_condition' => $hasCondition,
            'has_reversal' => $hasReversal,
            'has_question' => $hasQuestion,
            'has_identity' => $hasIdentity,
            'abstraction' => $abstraction
        ];
    }

    /**
     * Extrae el núcleo del conflicto moral de la publicación.
     */
    private static function extractConflict(string $placa, string $statement, string $caption): string {
        $text = mb_strtolower($placa . ' ' . $statement . ' ' . substr($caption, 0, 300), 'UTF-8');

        if (str_contains($text, 'rendi') || str_contains($text, 'pelea') || str_contains($text, 'derrota') || str_contains($text, 'lucha')) {
            return "El impulso de rendirse vs el deber de forjar carácter bajo fuego";
        } elseif (str_contains($text, 'destino') || str_contains($text, 'muerte') || str_contains($text, 'tiempo')) {
            return "La adversidad inevitable vs la soberanía y desprecio a la complacencia";
        } elseif (str_contains($text, 'violencia') || str_contains($text, 'inocencia') || str_contains($text, 'fuerza') || str_contains($text, 'cobard')) {
            return "La ilusión de la bondad pasiva vs la virtud ética de ser peligroso pero disciplinado";
        } elseif (str_contains($text, 'solo') || str_contains($text, 'aplauso') || str_contains($text, 'opinión') || str_contains($text, 'aprobación')) {
            return "La necesidad de encajar vs la libertad absoluta de caminar en soledad";
        } elseif (str_contains($text, 'máscara') || str_contains($text, 'mascara') || str_contains($text, 'apariencia')) {
            return "La falsedad social vs la revelación de la realidad";
        }
        return "Tensión ética entre la comodidad inmediata y la soberanía interior";
    }

    /**
     * Intervalo de Wilson puro al 95% de confianza.
     */
    public static function calculateWilsonScoreInterval(int $successes, int $total, float $confidenceZ = 1.96): array {
        if ($total <= 0) {
            return ['lower' => 0.0, 'upper' => 0.0, 'center' => 0.0, 'observed_rate' => 0.0, 'is_reliable' => false];
        }

        $p = $successes / $total;
        $z = $confidenceZ;
        $z2 = $z * $z;
        $n = $total;

        $denominator = 1 + ($z2 / $n);
        $center = ($p + ($z2 / (2 * $n))) / $denominator;
        $margin = ($z * sqrt(($p * (1 - $p) / $n) + ($z2 / (4 * $n * $n)))) / $denominator;

        return [
            'lower' => round(max(0.0, $center - $margin) * 100, 2),
            'upper' => round(min(1.0, $center + $margin) * 100, 2),
            'center' => round($center * 100, 2),
            'observed_rate' => round($p * 100, 2),
            'is_reliable' => ($n >= 15)
        ];
    }

    /**
     * Lift relativo con honestidad estadística (sin inventar certezas causales).
     */
    public static function calculatePatternLift(int $patternSuccesses, int $patternTotal, int $globalSuccesses, int $globalTotal): array {
        $wilson = self::calculateWilsonScoreInterval($patternSuccesses, $patternTotal);
        $patternRate = $patternTotal > 0 ? ($patternSuccesses / $patternTotal) : 0.0;
        $baselineRate = $globalTotal > 0 ? ($globalSuccesses / $globalTotal) : 0.20;

        $relativeLift = $baselineRate > 0 ? (($patternRate - $baselineRate) / $baselineRate) * 100 : 0.0;

        $evidenceStrength = 'PRELIMINARY';
        if ($patternTotal >= 30 && $wilson['lower'] > ($baselineRate * 100)) {
            $evidenceStrength = 'STRONG';
        } elseif ($patternTotal >= 15) {
            $evidenceStrength = 'VALIDATED';
        } elseif ($patternTotal >= 5) {
            $evidenceStrength = 'OBSERVATION';
        }

        return [
            'sample_size' => $patternTotal,
            'top_successes' => $patternSuccesses,
            'pattern_rate_pct' => round($patternRate * 100, 1),
            'baseline_rate_pct' => round($baselineRate * 100, 1),
            'relative_lift_pct' => round($relativeLift, 1),
            'wilson_interval' => $wilson,
            'evidence_strength' => $evidenceStrength,
            'disclaimer' => 'Asociación observada bajo esta muestra histórica. No implica causalidad determinista.'
        ];
    }

    /**
     * Indexa el Micro Content DNA de un post de forma multi-capa e idempotente.
     */
    public static function indexPostMicroDna(PDO $pdo, int $userId, int $postDnaId, string $placaText, string $captionText): array {
        $parsed = self::parsePost($placaText, $captionText);

        $stmt = $pdo->prepare("
            INSERT INTO atenea_micro_dna (
                user_id, post_dna_id, quote_text, sentence_family,
                placa_text, placa_family, caption_hook, caption_main_statement,
                caption_family, cta_text, opening_segment, tension_segment,
                reversal_segment, payoff_segment, word_count, character_count,
                punctuation_cadence, has_condition, has_reversal, has_question,
                has_identity_marker, abstraction_level, semantic_density,
                conflict_statement, reversal_statement, payoff_statement,
                syntax_archetype, created_at
            ) VALUES (
                :uid, :pid, :quote, :family,
                :placa, :placa_fam, :c_hook, :c_main,
                :c_fam, :cta, :opening, :tension,
                :reversal, :payoff, :words, :chars,
                :cadence, :has_cond, :has_rev, :has_q,
                :has_id, :abs, :density,
                :conflict, :rev_stmt, :pay_stmt,
                :archetype, CURRENT_TIMESTAMP
            )
            ON CONFLICT(post_dna_id) DO UPDATE SET
                quote_text = excluded.quote_text,
                sentence_family = excluded.sentence_family,
                placa_text = excluded.placa_text,
                placa_family = excluded.placa_family,
                caption_hook = excluded.caption_hook,
                caption_main_statement = excluded.caption_main_statement,
                caption_family = excluded.caption_family,
                cta_text = excluded.cta_text,
                opening_segment = excluded.opening_segment,
                tension_segment = excluded.tension_segment,
                reversal_segment = excluded.reversal_segment,
                payoff_segment = excluded.payoff_segment,
                word_count = excluded.word_count,
                character_count = excluded.character_count,
                punctuation_cadence = excluded.punctuation_cadence,
                has_condition = excluded.has_condition,
                has_reversal = excluded.has_reversal,
                has_question = excluded.has_question,
                has_identity_marker = excluded.has_identity_marker,
                abstraction_level = excluded.abstraction_level,
                semantic_density = excluded.semantic_density,
                conflict_statement = excluded.conflict_statement,
                reversal_statement = excluded.reversal_statement,
                payoff_statement = excluded.payoff_statement,
                syntax_archetype = excluded.syntax_archetype
        ");

        $quoteSummary = $parsed['placa_text'] ?: $parsed['caption_hook'];
        $stmt->execute([
            ':uid' => $userId,
            ':pid' => $postDnaId,
            ':quote' => $quoteSummary,
            ':family' => $parsed['sentence_family'],
            ':placa' => $parsed['placa_text'],
            ':placa_fam' => $parsed['placa_family'],
            ':c_hook' => $parsed['caption_hook'],
            ':c_main' => $parsed['caption_main_statement'],
            ':c_fam' => $parsed['caption_family'],
            ':cta' => $parsed['cta_text'],
            ':opening' => $parsed['opening_segment'] ?? '',
            ':tension' => $parsed['tension_segment'] ?? '',
            ':reversal' => $parsed['reversal_segment'] ?? $parsed['reversal_statement'] ?? '',
            ':payoff' => $parsed['payoff_segment'] ?? $parsed['payoff_statement'] ?? '',
            ':words' => $parsed['word_count'] ?? 0,
            ':chars' => $parsed['character_count'],
            ':cadence' => $parsed['punctuation_cadence'],
            ':has_cond' => $parsed['has_condition'],
            ':has_rev' => $parsed['has_reversal'],
            ':has_q' => $parsed['has_question'],
            ':has_id' => $parsed['has_identity_marker'],
            ':abs' => $parsed['abstraction_level'],
            ':density' => $parsed['semantic_density'],
            ':conflict' => $parsed['conflict_statement'],
            ':rev_stmt' => $parsed['reversal_statement'],
            ':pay_stmt' => $parsed['payoff_statement'],
            ':archetype' => $parsed['syntax_archetype']
        ]);
        $stmt->closeCursor();

        return $parsed;
    }

    /**
     * Procesa todas las publicaciones de `atenea_post_dna` separando placa y caption.
     */
    public static function processAllHistoricalPosts(int $userId): array {
        $pdo = Database::getConnection();
        
        $stmt = $pdo->prepare("
            SELECT id, overlay_quote, caption
            FROM atenea_post_dna
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $processed = 0;
        $placaDistribution = [];
        $captionDistribution = [];
        $densityDistribution = [];

        foreach ($rows as $r) {
            $placa = $r['overlay_quote'] ?? '';
            $caption = $r['caption'] ?? '';

            $res = self::indexPostMicroDna($pdo, $userId, (int)$r['id'], $placa, $caption);

            $placaDistribution[$res['placa_family']] = ($placaDistribution[$res['placa_family']] ?? 0) + 1;
            $captionDistribution[$res['caption_family']] = ($captionDistribution[$res['caption_family']] ?? 0) + 1;
            $densityDistribution[$res['semantic_density']] = ($densityDistribution[$res['semantic_density']] ?? 0) + 1;
            $processed++;
        }

        return [
            'total_processed' => $processed,
            'placa_distribution' => $placaDistribution,
            'caption_distribution' => $captionDistribution,
            'density_distribution' => $densityDistribution
        ];
    }

    /**
     * Obtiene muestras con la radiografía multi-capa completa para el Gate 2 de validación.
     */
    public static function getGate2AuditSamples(int $userId, string $tier, int $limit = 5): array {
        $pdo = Database::getConnection();
        $order = ($tier === 'TOP_10' || $tier === 'TOP_20') 
            ? 'p.overall_performance_score DESC' 
            : 'p.overall_performance_score ASC';

        $stmt = $pdo->prepare("
            SELECT d.id as post_dna_id, d.platform, d.caption, d.overlay_quote, d.theme,
                   m.placa_text, m.placa_family, m.caption_hook, m.caption_main_statement,
                   m.caption_family, m.cta_text, m.conflict_statement, m.reversal_statement,
                   m.payoff_statement, m.syntax_archetype, m.semantic_density, m.abstraction_level,
                   m.word_count, m.has_condition, m.has_reversal, m.has_identity_marker,
                   p.raw_likes, p.raw_comments, p.raw_shares, p.raw_saves,
                   p.overall_performance_score, p.performance_tier
            FROM atenea_post_dna d
            INNER JOIN atenea_performance_dna p ON p.post_dna_id = d.id
            LEFT JOIN atenea_micro_dna m ON m.post_dna_id = d.id
            WHERE d.user_id = ? AND p.performance_tier = ?
            ORDER BY {$order}
            LIMIT ?
        ");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $tier, PDO::PARAM_STR);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return $results;
    }
}
