<?php
declare(strict_types=1);
if (php_sapi_name() !== 'cli') { http_response_code(403); exit(1); }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/AteneaVisualDirectorService.php';

$pdo = Database::getConnection();
$userId = 1;
$brandId = (int)($pdo->query("SELECT id FROM brand_voices WHERE user_id = 1 ORDER BY is_default DESC, id ASC LIMIT 1")->fetchColumn() ?: 1);
$pass = 0; $fail = 0;
$check = static function (string $name, bool $condition) use (&$pass, &$fail): void {
    echo ($condition ? '[PASS] ' : '[FAIL] ') . $name . PHP_EOL;
    $condition ? $pass++ : $fail++;
};
$beforeHermes = (int)$pdo->query('SELECT COUNT(*) FROM ai_learning_feedback')->fetchColumn();
$refs = AteneaVisualDirectorService::listReferences($userId, $brandId);
$afterHermes = (int)$pdo->query('SELECT COUNT(*) FROM ai_learning_feedback')->fetchColumn();
$check('Biblioteca siembra y recupera referencias aprobadas', count($refs) >= 7);
$check('Semillas visuales no escriben memoria de Hermes', $beforeHermes === $afterHermes);
$normalized = AteneaVisualDirectorService::normalizeAspectRatio('scene --ar 4:5 --v 6.1 --ar 1:1', '9:16');
$check('Normalizador deja un único --ar solicitado', substr_count($normalized, '--ar') === 1 && str_contains($normalized, '--ar 9:16'));
$guidance = AteneaVisualDirectorService::getPromptGuidance($userId, $brandId, 'disciplina samurai');
$check('Fallback visual incluye un prompt final utilizable', !empty($guidance['fallback']['midjourney_prompt']) && str_contains($guidance['fallback']['midjourney_prompt'], '--ar'));
echo "Resultado: {$pass} PASS / {$fail} FAIL" . PHP_EOL;
exit($fail === 0 ? 0 : 1);
