<?php
/**
 * ══════════════════════════════════════════════════════════════════════════════
 * 🏛️ XINDRO AI Copilot - AteneaLearningEngine
 * ══════════════════════════════════════════════════════════════════════════════
 *
 * Motor de Aprendizaje Continuo para Atenea (Directora Creativa de @fortaleza_imparable).
 *
 * Responsabilidades:
 * 1. Ingesta sistemática de publicaciones propias de Facebook e Instagram (tabla `posts`).
 * 2. Deconstrucción semántica y psicológica de ADN (Tema, Gancho, Estructura, Disparador Emocional, etc.).
 * 3. Cálculo de Performance DNA normalizado por plataforma (Percentiles TOP_10, TOP_20, AVERAGE, LOW_20).
 * 4. Minería de Patrones Aprendidos con tamaño de muestra ($n$), nivel de confianza (LOW, MEDIUM, HIGH) y Lift %.
 * 5. Detección de Anti-Patrones (qué evitar de forma obligatoria basado en el 20% inferior).
 * 6. Generador de Directivas Empíricas de Audiencia para inyección en los prompts de generación de Atenea.
 * 7. Bucle de retroalimentación cerrado (Predicción vs Resultado Real tras publicación).
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/security.php';

class AteneaLearningEngine {

    /**
     * 1. INGESTA DE PUBLICACIONES PROPIAS (@fortaleza_imparable)
     * Extrae las publicaciones de la tabla `posts` y las indexa en `atenea_post_dna`.
     */
    public static function ingestOwnPosts(int $userId): array {
        $stats = ['scanned' => 0, 'ingested' => 0, 'already_existing' => 0];
        try {
            $pdo = Database::getConnection();

            // Consultar publicaciones propias del usuario
            $stmt = $pdo->prepare("
                SELECT id, user_id, platform, external_post_id, caption, media_type,
                       total_likes, total_comments, total_shares, saved_count, reach, impressions,
                       posted_at
                FROM posts
                WHERE user_id = ?
                ORDER BY posted_at DESC
            ");
            $stmt->execute([$userId]);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $stats['scanned'] = count($posts);

            $checkStmt = $pdo->prepare("
                SELECT id FROM atenea_post_dna
                WHERE user_id = ? AND platform = ? AND external_post_id = ?
            ");

            $insertStmt = $pdo->prepare("
                INSERT INTO atenea_post_dna (
                    user_id, source_type, brand, platform, post_id, external_post_id,
                    caption, overlay_quote, word_count, character_count, format, analyzed_at
                ) VALUES (
                    ?, 'OWN', 'FORTALEZA_IMPARABLE', ?, ?, ?,
                    ?, ?, ?, ?, ?, NULL
                )
            ");

            foreach ($posts as $p) {
                $platform = strtolower(trim($p['platform'] ?? 'instagram'));
                $extId = (string)($p['external_post_id'] ?? $p['id']);

                $checkStmt->execute([$userId, $platform, $extId]);
                $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
                $checkStmt->closeCursor();

                if ($exists) {
                    $stats['already_existing']++;
                    continue;
                }

                $caption = trim($p['caption'] ?? '');
                $quoteCandidate = self::extractOverlayQuoteCandidate($caption);
                $wordCount = str_word_count(strip_tags($caption));
                $charCount = mb_strlen($caption, 'UTF-8');
                $format = !empty($p['media_type']) ? strtolower($p['media_type']) : 'single_image';

                $insertStmt->execute([
                    $userId,
                    $platform,
                    (int)$p['id'],
                    $extId,
                    $caption,
                    $quoteCandidate,
                    $wordCount,
                    $charCount,
                    $format
                ]);
                $insertStmt->closeCursor();
                $stats['ingested']++;
            }

            return ['success' => true, 'stats' => $stats];
        } catch (Throwable $e) {
            error_log("AteneaLearningEngine::ingestOwnPosts error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'stats' => $stats];
        }
    }

    /**
     * 2. EXTRACCIÓN DE ADN PSICOLÓGICO Y SEMÁNTICO (BATCH)
     * Analiza las publicaciones indexadas extrayendo dimensiones clave.
     */
    public static function analyzePostDnaBatch(int $userId, int $batchSize = 250, bool $force = false): array {
        $analyzedCount = 0;
        try {
            $pdo = Database::getConnection();

            $sql = "
                SELECT id, platform, caption, overlay_quote, format
                FROM atenea_post_dna
                WHERE user_id = ? " . ($force ? "" : "AND (analyzed_at IS NULL OR theme IS NULL)") . "
                LIMIT " . (int)$batchSize;

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $updateStmt = $pdo->prepare("
                UPDATE atenea_post_dna
                SET theme = ?,
                    subtheme = ?,
                    core_concept = ?,
                    philosophical_principle = ?,
                    hook_type = ?,
                    hook_text = ?,
                    sentence_structure = ?,
                    word_count = ?,
                    character_count = ?,
                    emotional_trigger = ?,
                    audience_pain = ?,
                    belief_challenged = ?,
                    conflict = ?,
                    transformation = ?,
                    shareability_mechanism = ?,
                    saveability_mechanism = ?,
                    visual_subject = ?,
                    visual_style = ?,
                    analyzed_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");

            foreach ($records as $rec) {
                $dna = self::heuristicExtractDna(
                    $rec['caption'] ?? '',
                    $rec['overlay_quote'] ?? '',
                    $rec['platform'] ?? 'instagram',
                    $rec['format'] ?? 'image'
                );

                $wordCount = str_word_count(strip_tags($rec['caption'] ?? ''));
                $charCount = mb_strlen($rec['caption'] ?? '', 'UTF-8');

                $updateStmt->execute([
                    $dna['theme'],
                    $dna['subtheme'],
                    $dna['core_concept'],
                    $dna['philosophical_principle'],
                    $dna['hook_type'],
                    $dna['hook_text'],
                    $dna['sentence_structure'],
                    $wordCount,
                    $charCount,
                    $dna['emotional_trigger'],
                    $dna['audience_pain'],
                    $dna['belief_challenged'],
                    $dna['conflict'],
                    $dna['transformation'],
                    $dna['shareability_mechanism'],
                    $dna['saveability_mechanism'],
                    $dna['visual_subject'],
                    $dna['visual_style'],
                    $rec['id']
                ]);
                $updateStmt->closeCursor();
                $analyzedCount++;
            }

            return ['success' => true, 'analyzed_count' => $analyzedCount];
        } catch (Throwable $e) {
            error_log("AteneaLearningEngine::analyzePostDnaBatch error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'analyzed_count' => $analyzedCount];
        }
    }

    /**
     * 3. CÁLCULO DE PERFORMANCE DNA NORMALIZADO POR PLATAFORMA
     * Evalúa métricas reales de `posts` y asigna percentiles (TOP_10, TOP_20, AVERAGE, LOW_20).
     */
    public static function calculatePerformanceDna(int $userId): array {
        $calculatedCount = 0;
        try {
            $pdo = Database::getConnection();

            // Unir atenea_post_dna con posts para obtener métricas frescas
            $stmt = $pdo->prepare("
                SELECT d.id as post_dna_id, d.platform, p.total_likes, p.total_comments, 
                       p.total_shares, p.saved_count, p.reach, p.impressions
                FROM atenea_post_dna d
                INNER JOIN posts p ON p.id = d.post_id
                WHERE d.user_id = ?
            ");
            $stmt->execute([$userId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if (empty($rows)) {
                return ['success' => true, 'calculated_count' => 0, 'message' => 'No hay publicaciones con métricas enlazadas'];
            }

            // Agrupar por plataforma para normalizar percentiles por separado
            $byPlatform = [];
            foreach ($rows as $r) {
                $plat = strtolower(trim($r['platform']));
                $byPlatform[$plat][] = $r;
            }

            $perfRecords = [];

            foreach ($byPlatform as $plat => $platRows) {
                $scores = [];

                foreach ($platRows as $item) {
                    $likes = max(0, (int)($item['total_likes'] ?? 0));
                    $comments = max(0, (int)($item['total_comments'] ?? 0));
                    $shares = max(0, (int)($item['total_shares'] ?? 0));
                    $saves = max(0, (int)($item['saved_count'] ?? 0));
                    $reach = max(0, (int)($item['reach'] ?? 0));
                    $impressions = max(0, (int)($item['impressions'] ?? 0));

                    // Fórmulas asimétricas según plataforma:
                    // En Facebook: Las veces compartido (shares) multiplican el alcance orgánico y señalan identidad
                    // En Instagram: Los guardados (saves) y likes señalan autoridad y retención profunda
                    if ($plat === 'facebook') {
                        $shareScore = $shares * 2.5;
                        $saveScore = $saves * 1.5;
                        $conversationScore = $comments * 1.8;
                        $overallScore = ($likes * 0.30) + ($conversationScore * 0.25) + ($shareScore * 0.45);
                        $metricBasis = 'shares_engagement_weighted';
                    } else { // instagram
                        $shareScore = $shares * 2.0;
                        $saveScore = $saves * 3.0; // Alto valor para guardados en IG
                        $conversationScore = $comments * 2.0;
                        $reachComponent = ($reach > 0) ? ($reach * 0.05) : ($impressions * 0.03);
                        $overallScore = ($likes * 0.30) + ($conversationScore * 0.20) + ($saveScore * 0.35) + $reachComponent;
                        $metricBasis = 'saves_reach_weighted';
                    }

                    $scores[] = [
                        'post_dna_id' => (int)$item['post_dna_id'],
                        'platform' => $plat,
                        'raw_likes' => $likes,
                        'raw_comments' => $comments,
                        'raw_shares' => $shares,
                        'raw_saves' => $saves,
                        'raw_reach' => $reach,
                        'raw_impressions' => $impressions,
                        'metric_basis' => $metricBasis,
                        'share_score' => round($shareScore, 2),
                        'save_score' => round($saveScore, 2),
                        'conversation_score' => round($conversationScore, 2),
                        'overall_performance_score' => round($overallScore, 2)
                    ];
                }

                // Ordenar por score descendente para calcular percentiles
                usort($scores, fn($a, $b) => $b['overall_performance_score'] <=> $a['overall_performance_score']);

                $count = count($scores);
                $p90Index = (int)floor($count * 0.10); // Top 10%
                $p80Index = (int)floor($count * 0.20); // Top 20%
                $p20Index = (int)floor($count * 0.80); // Bottom 20% (índice donde inician los peores)

                foreach ($scores as $idx => &$sc) {
                    if ($idx < $p90Index) {
                        $sc['performance_tier'] = 'TOP_10';
                    } elseif ($idx < $p80Index) {
                        $sc['performance_tier'] = 'TOP_20';
                    } elseif ($idx >= $p20Index && $count >= 5) {
                        $sc['performance_tier'] = 'LOW_20';
                    } else {
                        $sc['performance_tier'] = 'AVERAGE';
                    }

                    // Tasa de engagement calculada
                    $baseDivisor = ($sc['raw_reach'] > 0) ? $sc['raw_reach'] : max(100, $sc['raw_likes'] + $sc['raw_comments'] + $sc['raw_shares'] + $sc['raw_saves']);
                    $sc['engagement_rate'] = round((($sc['raw_likes'] + $sc['raw_comments'] + $sc['raw_shares'] + $sc['raw_saves']) / $baseDivisor) * 100, 2);
                    $sc['share_rate'] = round(($sc['raw_shares'] / $baseDivisor) * 100, 2);
                    $sc['save_rate'] = round(($sc['raw_saves'] / $baseDivisor) * 100, 2);
                    $sc['comment_rate'] = round(($sc['raw_comments'] / $baseDivisor) * 100, 2);

                    $perfRecords[] = $sc;
                }
            }

            // Persistir en atenea_performance_dna
            $insStmt = $pdo->prepare("
                INSERT INTO atenea_performance_dna (
                    user_id, post_dna_id, platform, raw_likes, raw_comments, raw_shares, raw_saves,
                    raw_reach, raw_impressions, engagement_rate, share_rate, save_rate, comment_rate,
                    metric_basis, share_score, save_score, conversation_score, overall_performance_score,
                    performance_tier, calculated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, CURRENT_TIMESTAMP
                )
                ON CONFLICT(post_dna_id) DO UPDATE SET
                    raw_likes = excluded.raw_likes,
                    raw_comments = excluded.raw_comments,
                    raw_shares = excluded.raw_shares,
                    raw_saves = excluded.raw_saves,
                    raw_reach = excluded.raw_reach,
                    raw_impressions = excluded.raw_impressions,
                    engagement_rate = excluded.engagement_rate,
                    share_rate = excluded.share_rate,
                    save_rate = excluded.save_rate,
                    comment_rate = excluded.comment_rate,
                    metric_basis = excluded.metric_basis,
                    share_score = excluded.share_score,
                    save_score = excluded.save_score,
                    conversation_score = excluded.conversation_score,
                    overall_performance_score = excluded.overall_performance_score,
                    performance_tier = excluded.performance_tier,
                    calculated_at = CURRENT_TIMESTAMP
            ");

            foreach ($perfRecords as $pr) {
                $insStmt->execute([
                    $userId,
                    $pr['post_dna_id'],
                    $pr['platform'],
                    $pr['raw_likes'],
                    $pr['raw_comments'],
                    $pr['raw_shares'],
                    $pr['raw_saves'],
                    $pr['raw_reach'],
                    $pr['raw_impressions'],
                    $pr['engagement_rate'],
                    $pr['share_rate'],
                    $pr['save_rate'],
                    $pr['comment_rate'],
                    $pr['metric_basis'],
                    $pr['share_score'],
                    $pr['save_score'],
                    $pr['conversation_score'],
                    $pr['overall_performance_score'],
                    $pr['performance_tier']
                ]);
                $insStmt->closeCursor();
                $calculatedCount++;
            }

            return ['success' => true, 'calculated_count' => $calculatedCount];
        } catch (Throwable $e) {
            error_log("AteneaLearningEngine::calculatePerformanceDna error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'calculated_count' => $calculatedCount];
        }
    }

    /**
     * 4. MINERÍA DE PATRONES APRENDIDOS (PATRONES GANADORES & ANTI-PATRONES)
     * Cruza dimensiones de ADN contra niveles de rendimiento para extraer reglas empíricas.
     */
    public static function discoverLearnedPatterns(int $userId): array {
        $patternsDiscovered = 0;
        try {
            $pdo = Database::getConnection();

            // Obtener todas las publicaciones con ADN y Performance
            $stmt = $pdo->prepare("
                SELECT d.id, d.platform, d.theme, d.hook_type, d.sentence_structure, 
                       d.word_count, d.emotional_trigger, d.audience_pain, d.belief_challenged,
                       d.format, p.performance_tier, p.overall_performance_score, p.raw_shares, p.raw_likes
                FROM atenea_post_dna d
                INNER JOIN atenea_performance_dna p ON p.post_dna_id = d.id
                WHERE d.user_id = ?
            ");
            $stmt->execute([$userId]);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $totalCount = count($posts);
            if ($totalCount < 10) {
                return ['success' => false, 'error' => "Se requieren al menos 10 publicaciones procesadas para minar patrones (actuales: $totalCount)"];
            }

            // Dimensiones a evaluar
            $dimensions = [
                'theme' => 'Tema / Pilar de Contenido',
                'hook_type' => 'Tipo de Gancho Psicológico',
                'sentence_structure' => 'Estructura Sintáctica',
                'emotional_trigger' => 'Gatillo Emocional',
                'word_count_range' => 'Rango de Extensión de Palabras'
            ];

            $aggregates = [];

            foreach ($posts as $p) {
                $tier = $p['performance_tier'];
                $isTop = in_array($tier, ['TOP_10', 'TOP_20'], true);
                $isLow = ($tier === 'LOW_20');

                // Clasificar rango de palabras
                $wc = (int)$p['word_count'];
                $wcRange = 'Medio (13 a 24 palabras)';
                if ($wc <= 12) {
                    $wcRange = 'Corto Aforístico (1 a 12 palabras)';
                } elseif ($wc > 35) {
                    $wcRange = 'Extenso / Párrafo (> 35 palabras)';
                } elseif ($wc > 24) {
                    $wcRange = 'Largo Reflexivo (25 a 35 palabras)';
                }

                $entries = [
                    'theme' => $p['theme'],
                    'hook_type' => $p['hook_type'],
                    'sentence_structure' => $p['sentence_structure'],
                    'emotional_trigger' => $p['emotional_trigger'],
                    'word_count_range' => $wcRange
                ];

                foreach ($entries as $dimKey => $dimVal) {
                    if (empty($dimVal)) continue;

                    $key = $dimKey . '::' . $dimVal;
                    if (!isset($aggregates[$key])) {
                        $aggregates[$key] = [
                            'dimension' => $dimKey,
                            'value' => $dimVal,
                            'sample_size' => 0,
                            'top_count' => 0,
                            'low_count' => 0,
                            'avg_count' => 0,
                            'platform' => 'all'
                        ];
                    }
                    $aggregates[$key]['sample_size']++;
                    if ($isTop) $aggregates[$key]['top_count']++;
                    elseif ($isLow) $aggregates[$key]['low_count']++;
                    else $aggregates[$key]['avg_count']++;
                }
            }

            // Tasa base global en la muestra
            $baselineTopRate = max(0.15, count(array_filter($posts, fn($x) => in_array($x['performance_tier'], ['TOP_10', 'TOP_20'], true))) / max(1, $totalCount));
            $baselineLowRate = max(0.15, count(array_filter($posts, fn($x) => $x['performance_tier'] === 'LOW_20')) / max(1, $totalCount));

            $patternsToPersist = [];

            foreach ($aggregates as $item) {
                $n = $item['sample_size'];
                if ($n < 4) continue; // Descartar observaciones anecdóticas con menos de 4 casos

                $topRate = $item['top_count'] / $n;
                $lowRate = $item['low_count'] / $n;

                // Calibración de Confianza empírica basada en muestra:
                // n < 6 => LOW (muestra incipiente)
                // 6 <= n < 15 => MEDIUM (patrón observable consistente)
                // n >= 15 => HIGH (ley empírica validada en la audiencia)
                $confidenceLevel = 'LOW';
                $confidenceScore = 0.40;
                if ($n >= 15) {
                    $confidenceLevel = 'HIGH';
                    $confidenceScore = min(0.98, 0.75 + ($n * 0.005));
                } elseif ($n >= 6) {
                    $confidenceLevel = 'MEDIUM';
                    $confidenceScore = 0.65;
                }

                $topLift = ($topRate - $baselineTopRate) / $baselineTopRate;
                $lowLift = ($lowRate - $baselineLowRate) / $baselineLowRate;

                // 1. PATRÓN GANADOR (WINNING_FORMULA)
                if ($topRate >= 0.24 && $topRate > $lowRate && $topLift >= 0.20) {
                    $liftPercent = round($topLift * 100);
                    $status = ($n >= 15 && $liftPercent >= 40) ? 'STRONG' : (($n >= 6) ? 'VALIDATED' : 'EMERGING');
                    
                    $dimLabel = $dimensions[$item['dimension']] ?? $item['dimension'];
                    $desc = "Enfoque de alto rendimiento: {$dimLabel} '{$item['value']}' incrementa la probabilidad de Top 20 en +{$liftPercent}% ({$item['top_count']} de {$n} publicaciones en el Top).";

                    $patternsToPersist[] = [
                        'pattern_type' => ($item['dimension'] === 'word_count_range') ? 'FORMAT_INSIGHT' : 'WINNING_FORMULA',
                        'platform' => 'all',
                        'theme' => ($item['dimension'] === 'theme') ? $item['value'] : null,
                        'hook_type' => ($item['dimension'] === 'hook_type') ? $item['value'] : null,
                        'structure' => ($item['dimension'] === 'sentence_structure') ? $item['value'] : null,
                        'word_count_range' => ($item['dimension'] === 'word_count_range') ? $item['value'] : null,
                        'emotional_trigger' => ($item['dimension'] === 'emotional_trigger') ? $item['value'] : null,
                        'description' => $desc,
                        'sample_size' => $n,
                        'confidence_level' => $confidenceLevel,
                        'confidence_score' => $confidenceScore,
                        'positive_evidence_count' => $item['top_count'],
                        'negative_evidence_count' => $item['low_count'],
                        'lift_metric' => "+{$liftPercent}% probabilidad Top 20",
                        'status' => $status
                    ];
                }
                // 2. ANTI-PATRÓN (ANTI_PATTERN / QUÉ EVITAR)
                elseif ($lowRate >= 0.23 && $lowRate > $topRate && $lowLift >= 0.15) {
                    $lowPercent = round($lowLift * 100);
                    $status = ($n >= 15) ? 'STRONG' : (($n >= 6) ? 'VALIDATED' : 'EMERGING');
                    
                    $dimLabel = $dimensions[$item['dimension']] ?? $item['dimension'];
                    $desc = "ANTI-PATRÓN (Evitar): {$dimLabel} '{$item['value']}' muestra una tasa de caída al Bottom 20% del " . round($lowRate * 100) . "% ({$item['low_count']} de {$n} publicaciones en la franja baja).";

                    $patternsToPersist[] = [
                        'pattern_type' => 'ANTI_PATTERN',
                        'platform' => 'all',
                        'theme' => ($item['dimension'] === 'theme') ? $item['value'] : null,
                        'hook_type' => ($item['dimension'] === 'hook_type') ? $item['value'] : null,
                        'structure' => ($item['dimension'] === 'sentence_structure') ? $item['value'] : null,
                        'word_count_range' => ($item['dimension'] === 'word_count_range') ? $item['value'] : null,
                        'emotional_trigger' => ($item['dimension'] === 'emotional_trigger') ? $item['value'] : null,
                        'description' => $desc,
                        'sample_size' => $n,
                        'confidence_level' => $confidenceLevel,
                        'confidence_score' => $confidenceScore,
                        'positive_evidence_count' => $item['low_count'],
                        'negative_evidence_count' => $item['top_count'],
                        'lift_metric' => "+{$lowPercent}% riesgo Bottom 20",
                        'status' => $status
                    ];
                }
            }

            // Limpiar patrones automáticos previos del usuario para refrescar con datos actuales
            $pdo->prepare("DELETE FROM atenea_learned_patterns WHERE user_id = ?")->execute([$userId]);

            $insPat = $pdo->prepare("
                INSERT INTO atenea_learned_patterns (
                    user_id, pattern_type, platform, theme, hook_type, structure,
                    word_count_range, emotional_trigger, description, sample_size,
                    confidence_level, confidence_score, positive_evidence_count,
                    negative_evidence_count, lift_metric, status, last_validated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?, CURRENT_TIMESTAMP
                )
            ");

            foreach ($patternsToPersist as $pat) {
                $insPat->execute([
                    $userId,
                    $pat['pattern_type'],
                    $pat['platform'],
                    $pat['theme'],
                    $pat['hook_type'],
                    $pat['structure'],
                    $pat['word_count_range'],
                    $pat['emotional_trigger'],
                    $pat['description'],
                    $pat['sample_size'],
                    $pat['confidence_level'],
                    $pat['confidence_score'],
                    $pat['positive_evidence_count'],
                    $pat['negative_evidence_count'],
                    $pat['lift_metric'],
                    $pat['status']
                ]);
                $insPat->closeCursor();
                $patternsDiscovered++;
            }

            return ['success' => true, 'patterns_discovered' => $patternsDiscovered];
        } catch (Throwable $e) {
            error_log("AteneaLearningEngine::discoverLearnedPatterns error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'patterns_discovered' => $patternsDiscovered];
        }
    }

    /**
     * 5. GENERADOR DE DIRECTIVAS EMPÍRICAS PARA EL PROMPT DE ATENEA
     * Devuelve un bloque de texto conciso y fundamentado con los patrones comprobados y anti-patrones.
     */
    public static function getActivePatternsForPrompt(int $userId, string $platform = 'all'): string {
        try {
            $pdo = Database::getConnection();

            // 1. Patrones Ganadores (Top)
            $stmt = $pdo->prepare("
                SELECT pattern_type, description, lift_metric, sample_size, confidence_level
                FROM atenea_learned_patterns
                WHERE user_id = ? 
                  AND pattern_type IN ('WINNING_FORMULA', 'FORMAT_INSIGHT')
                  AND status IN ('VALIDATED', 'STRONG', 'EMERGING')
                ORDER BY confidence_score DESC, sample_size DESC
                LIMIT 5
            ");
            $stmt->execute([$userId]);
            $winners = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            // 2. Anti-Patrones (Evitar a toda costa)
            $stmt = $pdo->prepare("
                SELECT pattern_type, description, sample_size, confidence_level
                FROM atenea_learned_patterns
                WHERE user_id = ? 
                  AND pattern_type = 'ANTI_PATTERN'
                  AND status IN ('VALIDATED', 'STRONG', 'EMERGING')
                ORDER BY confidence_score DESC, sample_size DESC
                LIMIT 4
            ");
            $stmt->execute([$userId]);
            $antiPatterns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if (empty($winners) && empty($antiPatterns)) {
                return "DIRECTIVA DE APRENDIZAJE: Mantén concisión aforística pura (8 a 22 palabras) y tono de sobriedad estoico-marcial sin clichés.";
            }

            $out = "═══════════════════════════════════════════════════════════════════════════\n";
            $out .= "📊 LECCIONES Y PATRONES EMPÍRICOS APRENDIDOS DE @FORTALEZA_IMPARABLE:\n";
            $out .= "(Información extraída de publicaciones históricas de tu propia audiencia):\n\n";

            if (!empty($winners)) {
                $out .= "⭐ PATRONES GANADORES COMPROBADOS (REPLICAR EN TUS CREACIONES):\n";
                foreach ($winners as $w) {
                    $out .= " • [Confianza: {$w['confidence_level']}, n={$w['sample_size']}] {$w['description']}\n";
                }
                $out .= "\n";
            }

            if (!empty($antiPatterns)) {
                $out .= "⚠️ ANTI-PATRONES DETECTADOS (LO QUE FRACASA EN TU AUDIENCIA - TOTALMENTE PROHIBIDO):\n";
                foreach ($antiPatterns as $ap) {
                    $out .= " • [Confianza: {$ap['confidence_level']}, n={$ap['sample_size']}] {$ap['description']}\n";
                }
                $out .= "\n";
            }

            $out .= "REGLA DE APLICACIÓN: Utiliza estos aprendizajes estadísticos para guiar la selección de gancho, extensión y ángulo en las 4 variaciones aforísticas.\n";
            $out .= "═══════════════════════════════════════════════════════════════════════════";

            return $out;
        } catch (Throwable $e) {
            error_log("AteneaLearningEngine::getActivePatternsForPrompt error: " . $e->getMessage());
            return "";
        }
    }

    /**
     * 6. RESUMEN GLOBAL DE APRENDIZAJE PARA EL DASHBOARD
     */
    public static function getLearningOverview(int $userId): array {
        try {
            $pdo = Database::getConnection();

            // Total de publicaciones en posts
            $postsStmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
            $postsStmt->execute([$userId]);
            $totalOwned = (int)$postsStmt->fetchColumn();
            $postsStmt->closeCursor();

            // Total en atenea_post_dna
            $dnaStmt = $pdo->prepare("
                SELECT COUNT(*) as total_indexed,
                       SUM(CASE WHEN analyzed_at IS NOT NULL THEN 1 ELSE 0 END) as total_analyzed,
                       SUM(CASE WHEN platform = 'facebook' THEN 1 ELSE 0 END) as fb_count,
                       SUM(CASE WHEN platform = 'instagram' THEN 1 ELSE 0 END) as ig_count
                FROM atenea_post_dna
                WHERE user_id = ?
            ");
            $dnaStmt->execute([$userId]);
            $dnaCounts = $dnaStmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $dnaStmt->closeCursor();

            // Desglose de Performance Tiers
            $tierStmt = $pdo->prepare("
                SELECT performance_tier, COUNT(*) as count, AVG(overall_performance_score) as avg_score
                FROM atenea_performance_dna
                WHERE user_id = ?
                GROUP BY performance_tier
            ");
            $tierStmt->execute([$userId]);
            $tierRows = $tierStmt->fetchAll(PDO::FETCH_ASSOC);
            $tierStmt->closeCursor();

            $tiers = [
                'TOP_10' => 0,
                'TOP_20' => 0,
                'AVERAGE' => 0,
                'LOW_20' => 0
            ];
            foreach ($tierRows as $tr) {
                $tiers[$tr['performance_tier']] = (int)$tr['count'];
            }

            // Patrones aprendidos agrupados
            $patStmt = $pdo->prepare("
                SELECT * FROM atenea_learned_patterns
                WHERE user_id = ?
                ORDER BY 
                    CASE pattern_type WHEN 'WINNING_FORMULA' THEN 1 WHEN 'FORMAT_INSIGHT' THEN 2 ELSE 3 END,
                    confidence_score DESC,
                    sample_size DESC
            ");
            $patStmt->execute([$userId]);
            $patterns = $patStmt->fetchAll(PDO::FETCH_ASSOC);
            $patStmt->closeCursor();

            $winners = [];
            $antiPatterns = [];
            $formatInsights = [];

            foreach ($patterns as $p) {
                if ($p['pattern_type'] === 'WINNING_FORMULA') {
                    $winners[] = $p;
                } elseif ($p['pattern_type'] === 'ANTI_PATTERN') {
                    $antiPatterns[] = $p;
                } else {
                    $formatInsights[] = $p;
                }
            }

            // Top 5 publicaciones históricas de mayor impacto
            $topPostsStmt = $pdo->prepare("
                SELECT d.id, d.platform, d.caption, d.overlay_quote, d.theme, d.hook_type,
                       p.raw_likes, p.raw_comments, p.raw_shares, p.raw_saves,
                       p.overall_performance_score, p.performance_tier
                FROM atenea_post_dna d
                INNER JOIN atenea_performance_dna p ON p.post_dna_id = d.id
                WHERE d.user_id = ? AND p.performance_tier IN ('TOP_10', 'TOP_20')
                ORDER BY p.overall_performance_score DESC
                LIMIT 5
            ");
            $topPostsStmt->execute([$userId]);
            $topPosts = $topPostsStmt->fetchAll(PDO::FETCH_ASSOC);
            $topPostsStmt->closeCursor();

            // Bottom 3 publicaciones de menor impacto (para análisis de qué no hacer)
            $lowPostsStmt = $pdo->prepare("
                SELECT d.id, d.platform, d.caption, d.overlay_quote, d.theme, d.hook_type,
                       p.raw_likes, p.raw_comments, p.raw_shares, p.raw_saves,
                       p.overall_performance_score, p.performance_tier
                FROM atenea_post_dna d
                INNER JOIN atenea_performance_dna p ON p.post_dna_id = d.id
                WHERE d.user_id = ? AND p.performance_tier = 'LOW_20'
                ORDER BY p.overall_performance_score ASC
                LIMIT 3
            ");
            $lowPostsStmt->execute([$userId]);
            $lowPosts = $lowPostsStmt->fetchAll(PDO::FETCH_ASSOC);
            $lowPostsStmt->closeCursor();

            return [
                'success' => true,
                'counts' => [
                    'total_owned_posts' => $totalOwned,
                    'total_indexed' => (int)($dnaCounts['total_indexed'] ?? 0),
                    'total_analyzed' => (int)($dnaCounts['total_analyzed'] ?? 0),
                    'facebook_posts' => (int)($dnaCounts['fb_count'] ?? 0),
                    'instagram_posts' => (int)($dnaCounts['ig_count'] ?? 0),
                    'patterns_total' => count($patterns),
                    'winners_count' => count($winners),
                    'anti_patterns_count' => count($antiPatterns)
                ],
                'tiers' => $tiers,
                'winners' => $winners,
                'anti_patterns' => $antiPatterns,
                'format_insights' => $formatInsights,
                'top_posts' => $topPosts,
                'low_posts' => $lowPosts
            ];
        } catch (Throwable $e) {
            error_log("AteneaLearningEngine::getLearningOverview error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 7. RECONSTRUCCIÓN COMPLETA EN UN SOLO PASO
     */
    public static function rebuildAll(int $userId): array {
        $step1 = self::ingestOwnPosts($userId);
        if (!$step1['success']) return ['success' => false, 'step' => 'ingest', 'error' => $step1['error']];

        $step2 = self::analyzePostDnaBatch($userId, 500, false);
        if (!$step2['success']) return ['success' => false, 'step' => 'analyze', 'error' => $step2['error']];

        $step3 = self::calculatePerformanceDna($userId);
        if (!$step3['success']) return ['success' => false, 'step' => 'performance', 'error' => $step3['error']];

        $step4 = self::discoverLearnedPatterns($userId);
        if (!$step4['success']) return ['success' => false, 'step' => 'patterns', 'error' => $step4['error']];

        return [
            'success' => true,
            'ingest' => $step1['stats'],
            'analyzed' => $step2['analyzed_count'],
            'performance' => $step3['calculated_count'],
            'patterns' => $step4['patterns_discovered']
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // UTILIDADES SEMÁNTICAS Y HEURÍSTICAS INTERNAS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Extrae un candidato de frase para placa visual a partir del texto de la publicación
     */
    private static function extractOverlayQuoteCandidate(string $text): string {
        $text = trim($text);
        if (empty($text)) return '';

        // Buscar texto entre comillas dobles o latinas
        if (preg_match('/["«]([^"»\n]{10,120})["»]/u', $text, $matches)) {
            return trim($matches[1]);
        }

        // Buscar primera línea con impacto si termina en punto o salto
        $lines = preg_split('/\r\n|\r|\n/', $text);
        foreach ($lines as $line) {
            $cleaned = trim(preg_replace('/^[•\-\*#\d\.\s]+/u', '', $line));
            $len = mb_strlen($cleaned, 'UTF-8');
            $words = str_word_count($cleaned);
            if ($len >= 15 && $len <= 110 && $words >= 4 && $words <= 22) {
                return $cleaned;
            }
        }

        // Si no, tomar primeros 100 caracteres hasta el primer punto
        $dotPos = mb_strpos($text, '.');
        if ($dotPos !== false && $dotPos >= 15 && $dotPos <= 120) {
            return trim(mb_substr($text, 0, $dotPos + 1));
        }

        return mb_substr($text, 0, 100);
    }

    /**
     * Extractor Heurístico de ADN de Contenido
     */
    private static function heuristicExtractDna(string $caption, string $overlayQuote, string $platform, string $format): array {
        $fullText = mb_strtolower($caption . ' ' . $overlayQuote, 'UTF-8');

        // 1. Determinar Tema
        $theme = 'Disciplina y Autodominio';
        $subtheme = 'Forja del Carácter';
        $principle = 'El dominio de los impulsos como fundamento de la libertad';

        if (preg_match('/(musashi|samurai|bushido|espada|guerrero|batalla|combate|dokkodo|honor)/i', $fullText)) {
            $theme = 'Vencedor Solitario y Forja Interior (Bushido/Dokkōdō)';
            $subtheme = 'Sendero del Guerrero Solitario';
            $principle = 'Independencia moral y desapego del juicio de las masas';
        } elseif (preg_match('/(muerte|tiempo|mori|vida|horas|morir|efimero|hoy|presente)/i', $fullText)) {
            $theme = 'Memento Mori y Urgencia Vital';
            $subtheme = 'Finitud y Aprovechamiento del Tiempo';
            $principle = 'La conciencia de la muerte como brújula de prioridades';
        } elseif (preg_match('/(control|paz|calma|seren|exterior|juicio|opini|mente|soberan)/i', $fullText)) {
            $theme = 'Dicotomía del Control y Soberanía Mental';
            $subtheme = 'Serenidad ante el Juicio Externo';
            $principle = 'Diferenciar tajantemente lo que depende de uno de lo que no';
        } elseif (preg_match('/(adversidad|dolor|sufr|caer|golpe|fuego|hierro|herida|fuerte)/i', $fullText)) {
            $theme = 'Amor Fati y Resiliencia en la Adversidad';
            $subtheme = 'El Obstáculo como Combustible';
            $principle = 'Abrazar el destino y transformar la dificultad en maestría';
        } elseif (preg_match('/(silencio|calla|demuestra|hechos|palabras|hablar|ruido|acciones)/i', $fullText)) {
            $theme = 'Acción Implacable vs Procrastinación';
            $subtheme = 'Hechos sobre Palabras';
            $principle = 'El valor real se demuestra en el silencio de los actos';
        }

        // 2. Determinar Tipo de Gancho
        $hookType = 'Golpe de Realidad Frontal';
        if (preg_match('/(pero|sin embargo|no es|aunque|mientras otros|al revés)/i', $fullText)) {
            $hookType = 'Paradoja Contraintuitiva';
        } elseif (preg_match('/(si no|cuando no|quien no|aquel que)/i', $fullText)) {
            $hookType = 'Causa-Efecto Severo';
        } elseif (preg_match('/(nunca|jamás|siempre|regla|ley|debes|no permitas)/i', $fullText)) {
            $hookType = 'Axioma Marcial / Sentencia';
        } elseif (preg_match('/(\?|por qué|acaso|cuántas veces)/i', $fullText)) {
            $hookType = 'Provocación a la Complacencia';
        }

        // 3. Estructura Sintáctica
        $words = str_word_count(strip_tags($overlayQuote ?: $caption));
        $sentenceStructure = 'Antítesis / Bipolar';
        if ($words <= 12) {
            $sentenceStructure = 'Axioma Corto Aforístico';
        } elseif (preg_match('/(si|cuando).+(entonces|,)/i', $fullText)) {
            $sentenceStructure = 'Condicional Causa-Efecto';
        } elseif (preg_match('/(no|deja|deja de|empieza|sé|domina)\b/i', $fullText)) {
            $sentenceStructure = 'Imperativo Directo';
        } elseif ($words > 30) {
            $sentenceStructure = 'Narrativa Reflexiva Larga';
        }

        // 4. Gatillo Emocional y Dolores de Audiencia
        $emotionalTrigger = 'Orgullo y Autoexigencia';
        $audiencePain = 'Falta de constancia y vulnerabilidad ante la comodidad';
        $beliefChallenged = 'Creer que la comodidad y la complacencia traen paz';
        $conflict = 'El impulso de rendirse vs el deber de forjar carácter';
        $transformation = 'Dejar de esperar validación ajena para actuar con soberanía propia';
        $shareability = 'Validación de identidad fuerte y disciplina ante su círculo social';
        $saveability = 'Recordatorio moral de sobriedad para momentos de debilidad';

        if ($theme === 'Vencedor Solitario y Forja Interior (Bushido/Dokkōdō)') {
            $emotionalTrigger = 'Identidad Guerrera y Soledad Constructiva';
            $audiencePain = 'Miedo a no encajar y necesidad de aprobación grupal';
            $beliefChallenged = 'Creer que estar solo es señal de fracaso o debilidad';
            $shareability = 'Orgullo de caminar solo por elección propia';
        } elseif ($theme === 'Memento Mori y Urgencia Vital') {
            $emotionalTrigger = 'Urgencia Moral y Despertar de Consciencia';
            $audiencePain = 'Procrastinación bajo la ilusión de tener tiempo infinito';
            $beliefChallenged = 'Posponer las decisiones difíciles pensando que habrá mañana';
            $shareability = 'Llamado a la sobriedad para amigos o círculo íntimo';
        } elseif ($theme === 'Acción Implacable vs Procrastinación') {
            $emotionalTrigger = 'Desprecio a la Queja y Pasividad';
            $audiencePain = 'Hábito de justificar la inacción con excusas lógicas';
            $beliefChallenged = 'Creer que las buenas intenciones equivalen a resultados';
            $shareability = 'Declaración pública de trabajo duro en silencio';
        }

        return [
            'theme' => $theme,
            'subtheme' => $subtheme,
            'core_concept' => mb_substr($overlayQuote ?: $caption, 0, 120),
            'philosophical_principle' => $principle,
            'hook_type' => $hookType,
            'hook_text' => mb_substr($overlayQuote ?: $caption, 0, 80),
            'sentence_structure' => $sentenceStructure,
            'emotional_trigger' => $emotionalTrigger,
            'audience_pain' => $audiencePain,
            'belief_challenged' => $beliefChallenged,
            'conflict' => $conflict,
            'transformation' => $transformation,
            'shareability_mechanism' => $shareability,
            'saveability_mechanism' => $saveability,
            'visual_subject' => 'Busto clásico de piedra obsidiana o guerrero en penumbra',
            'visual_style' => 'Claroscuro dramático cinematográfico con texturas de mármol'
        ];
    }
}
