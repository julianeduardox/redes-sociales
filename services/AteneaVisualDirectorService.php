<?php
declare(strict_types=1);

/**
 * Memoria visual aislada de Atenea. Nunca consulta ni escribe ai_learning_feedback.
 */
require_once __DIR__ . '/../config/database.php';

final class AteneaVisualDirectorService
{
    private const RATIOS = ['4:5', '9:16', '1:1'];

    /** @return array<int, array<string, mixed>> */
    public static function listReferences(int $userId, int $brandVoiceId): array
    {
        self::ensureSeedReferences($userId, $brandVoiceId);
        $stmt = Database::getConnection()->prepare(
            'SELECT * FROM atenea_visual_references
             WHERE user_id = :uid AND brand_voice_id = :bvid AND is_archived = 0
             ORDER BY is_favorite DESC, updated_at DESC, id DESC'
        );
        $stmt->execute([':uid' => $userId, ':bvid' => $brandVoiceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<string, mixed> */
    public static function addReference(int $userId, int $brandVoiceId, string $prompt, string $ratio = '4:5', bool $favorite = false): array
    {
        $prompt = trim(str_replace("\0", '', $prompt));
        if (mb_strlen($prompt) < 20 || mb_strlen($prompt) > 12000) {
            throw new InvalidArgumentException('El prompt debe tener entre 20 y 12.000 caracteres.');
        }
        $ratio = self::validateRatio($ratio);
        $attributes = self::extractAttributes($prompt, $ratio);
        $normalized = self::normalizeAspectRatio($prompt, $ratio);
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO atenea_visual_references
            (user_id, brand_voice_id, prompt_original, prompt_approved, attributes_json, subject, visual_style, palette, lighting, atmosphere, composition, aspect_ratio, source_type, is_favorite, prompt_status, is_archived, created_at, updated_at)
            VALUES (:uid, :bvid, :original, :approved, :attributes, :subject, :style, :palette, :lighting, :atmosphere, :composition, :ratio, :source, :favorite, :status, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            ':uid' => $userId, ':bvid' => $brandVoiceId, ':original' => $prompt, ':approved' => $normalized,
            ':attributes' => json_encode($attributes, JSON_UNESCAPED_UNICODE), ':subject' => $attributes['subject'],
            ':style' => $attributes['style'], ':palette' => $attributes['palette'], ':lighting' => $attributes['lighting'],
            ':atmosphere' => $attributes['atmosphere'], ':composition' => $attributes['composition'], ':ratio' => $ratio,
            ':source' => 'human_reference', ':favorite' => $favorite ? 1 : 0, ':status' => 'approved'
        ]);
        return ['id' => (int)$pdo->lastInsertId(), 'prompt' => $normalized, 'attributes' => $attributes];
    }

    public static function archiveReference(int $userId, int $brandVoiceId, int $referenceId): bool
    {
        $stmt = Database::getConnection()->prepare(
            'UPDATE atenea_visual_references SET is_archived = 1, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id AND user_id = :uid AND brand_voice_id = :bvid AND is_archived = 0'
        );
        $stmt->execute([':id' => $referenceId, ':uid' => $userId, ':bvid' => $brandVoiceId]);
        return $stmt->rowCount() === 1;
    }

    public static function normalizeAspectRatio(string $prompt, string $ratio): string
    {
        $ratio = self::validateRatio($ratio);
        $clean = preg_replace('/\s+--ar\s+\d+(?::\d+)?\b/i', '', trim($prompt)) ?? trim($prompt);
        return trim(preg_replace('/\s+/', ' ', $clean) ?? $clean) . ' --ar ' . $ratio;
    }

    /** @return array<string, mixed> */
    public static function getPromptGuidance(int $userId, int $brandVoiceId, string $theme, string $phrase = ''): array
    {
        $references = array_slice(self::listReferences($userId, $brandVoiceId), 0, 4);
        $evidence = self::getObservedEvidence($userId);
        $lines = [];
        foreach ($references as $reference) {
            $lines[] = sprintf('- %s | %s | %s | %s', $reference['subject'], $reference['composition'], $reference['palette'], $reference['atmosphere']);
        }
        return [
            'references' => $references,
            'prompt_context' => implode("\n", $lines),
            'evidence_summary' => $evidence,
            'fallback' => self::buildFallback($references, $theme, $phrase),
        ];
    }

    /** @return array<string, string> */
    private static function buildFallback(array $references, string $theme, string $phrase): array
    {
        $reference = $references[0] ?? [];
        $subject = (string)($reference['subject'] ?? 'solitary ronin seen from behind');
        $composition = (string)($reference['composition'] ?? 'small figure against a vast monumental landscape');
        $palette = (string)($reference['palette'] ?? 'deep indigo, charcoal black and restrained amber');
        $lighting = (string)($reference['lighting'] ?? 'low dawn backlight and soft volumetric mist');
        $atmosphere = (string)($reference['atmosphere'] ?? 'quiet stoic solitude, restrained drama');
        $ratio = self::validateRatio((string)($reference['aspect_ratio'] ?? '4:5'));
        $prompt = self::normalizeAspectRatio(
            "cinematic fine art image of {$subject}, {$composition}, {$palette} palette, {$lighting}, {$atmosphere}, subtle 35mm film grain, historically grounded Edo-era details, no text, no typography, no watermark, no logo",
            $ratio
        );
        return ['subject' => $subject, 'environment' => 'landscape selected from approved visual references', 'atmosphere' => $atmosphere, 'camera' => 'cinematic 35mm, natural grain', 'midjourney_prompt' => $prompt];
    }

    private static function validateRatio(string $ratio): string
    {
        return in_array($ratio, self::RATIOS, true) ? $ratio : '4:5';
    }

    /** @return array<string, string> */
    private static function extractAttributes(string $prompt, string $ratio): array
    {
        $text = mb_strtolower($prompt, 'UTF-8');
        $subject = str_contains($text, 'horse') || str_contains($text, 'caballo') ? 'warrior on horseback' : (str_contains($text, 'mech') ? 'battle-worn samurai mech' : 'solitary ronin samurai');
        $style = str_contains($text, 'ink') || str_contains($text, 'tinta') ? 'Japanese ink-wash cinematic illustration' : (str_contains($text, 'oil') || str_contains($text, 'óleo') ? 'expressive painterly cinema' : 'cinematic historical fine-art photography');
        $palette = str_contains($text, 'sepia') ? 'aged sepia, charcoal and warm yellow' : (str_contains($text, 'blue') || str_contains($text, 'índigo') ? 'deep indigo, charcoal and restrained amber' : 'muted natural earth tones');
        $lighting = str_contains($text, 'sunrise') || str_contains($text, 'amanecer') ? 'low golden sunrise backlight' : 'soft chiaroscuro with volumetric mist';
        $atmosphere = str_contains($text, 'battle') || str_contains($text, 'battlefield') ? 'tragic endurance and monumental scale' : 'quiet stoic solitude and contained drama';
        $composition = str_contains($text, 'back view') || str_contains($text, 'from behind') ? 'back view, small figure facing a vast scene' : 'cinematic wide composition with strong negative space';
        return compact('subject', 'style', 'palette', 'lighting', 'atmosphere', 'composition') + ['ratio' => $ratio];
    }

    private static function getObservedEvidence(int $userId): string
    {
        try {
            $stmt = Database::getConnection()->prepare(
                "SELECT COUNT(*) AS sample_size, AVG(p.overall_performance_score) AS avg_score,
                    (SELECT COALESCE(d2.visual_subject, '')
                     FROM atenea_post_dna d2 JOIN atenea_performance_dna p2 ON p2.post_dna_id = d2.id
                     WHERE d2.user_id = :uid2 AND d2.source_type = 'OWN'
                     ORDER BY p2.overall_performance_score DESC, d2.id DESC LIMIT 1) AS top_subject,
                    (SELECT COALESCE(d3.visual_style, '')
                     FROM atenea_post_dna d3 JOIN atenea_performance_dna p3 ON p3.post_dna_id = d3.id
                     WHERE d3.user_id = :uid3 AND d3.source_type = 'OWN'
                     ORDER BY p3.overall_performance_score DESC, d3.id DESC LIMIT 1) AS top_style
                 FROM atenea_post_dna d JOIN atenea_performance_dna p ON p.post_dna_id = d.id
                 WHERE d.user_id = :uid AND d.source_type = 'OWN'"
            );
            $stmt->execute([':uid' => $userId, ':uid2' => $userId, ':uid3' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $n = (int)($row['sample_size'] ?? 0);
            if ($n < 3) return 'Historial propio insuficiente para inferencias cuantitativas.';
            $subject = trim((string)($row['top_subject'] ?? ''));
            $style = trim((string)($row['top_style'] ?? ''));
            $detail = ($subject !== '' || $style !== '') ? ' Referencia de mejor desempeño registrada: ' . trim($subject . ($style !== '' ? " ({$style})" : '')) . '.' : '';
            return "Asociación observada en {$n} publicaciones propias; no implica causalidad." . $detail;
        } catch (Throwable) {
            return 'Sin evidencia de rendimiento disponible todavía.';
        }
    }

    private static function ensureSeedReferences(int $userId, int $brandVoiceId): void
    {
        $pdo = Database::getConnection();
        $check = $pdo->prepare('SELECT COUNT(*) FROM atenea_visual_references WHERE user_id = :uid AND brand_voice_id = :bvid');
        $check->execute([':uid' => $userId, ':bvid' => $brandVoiceId]);
        if ((int)$check->fetchColumn() > 0) return;
        $seeds = [
            'back view of lone ronin samurai sitting seiza in a vast tall green grass field, dark indigo kimono, misty green mountains, huge textured beige sky, peaceful lonely, Japanese ink wash, muted colors, paper grain',
            'a swordsman gallops on horseback along an ancient autumn road, dust haze in low morning sun, warm muted color response, 100mm lens, cinematic historical photography',
            'a kneeling warrior on a desolate battlefield, seven translucent shadow figures behind him, red sun, solemn dramatic composition',
            'panoramic Wuxia terrace at golden sunrise, distant waterfalls and cloud city, two silhouetted warriors, expressive oil painting, ancient mystery',
            'old Kyoto street, late Edo period, wet pavement, dense mist, lone samurai walking toward camera, dark blue gray cinematic historical photography',
            'traditional Chinese ink-film bamboo forest, cold gray rain, water vapor and mist, restrained solemn poetic atmosphere',
            '1945 devastated Japanese battlefield, battle-worn samurai mech, mushroom cloud horizon, black and white warm sepia, aged documentary film grain',
        ];
        foreach ($seeds as $seed) self::addReference($userId, $brandVoiceId, $seed, '4:5', true);
    }
}
