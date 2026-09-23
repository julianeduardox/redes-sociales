<?php
/**
 * ==============================================================================
 * 🚀 XINDRO AI Copilot - Autonomous Background Worker Engine (Windows / Server)
 * ==============================================================================
 *
 * Responsibilities:
 * 1. Runs continuously in background (Daemon mode) without needing a web browser open.
 * 2. Monitors active Instagram & Facebook accounts for new comments.
 * 3. Ingests comments and evaluates AI sentiment & intent.
 * 4. Dispatches Autopilot: generates brand-tailored replies and posts them to Meta Graph API.
 * 5. Drains pending webhooks from the queue.
 * 6. Executes periodic Trends Agent synchronization (every 6h).
 * 7. Maintains real-time status in data/worker_status.json for Dashboard UI visibility.
 * 8. Safe concurrency locking via data/.worker.lock and clean shutdown via data/.worker_stop.
 *
 * Usage:
 *   c:\xampp\php\php.exe scripts/xindro_worker.php             # Continuous daemon (every 60s)
 *   c:\xampp\php\php.exe scripts/xindro_worker.php --once      # Single cycle execution
 *   c:\xampp\php\php.exe scripts/xindro_worker.php --interval=45 # Custom interval (seconds)
 */

if (php_sapi_name() !== 'cli' && !defined('STDIN')) {
    http_response_code(403);
    die("Error: Este script debe ser ejecutado exclusivamente desde la línea de comandos (CLI).\n");
}

// Environment & Paths
define('PROCESS_QUEUE_LIB_ONLY', true);
$rootDir = realpath(__DIR__ . '/..');
if ($rootDir && is_dir($rootDir)) {
    chdir($rootDir);
}

require_once $rootDir . '/config/database.php';
require_once $rootDir . '/config/settings.php';
require_once $rootDir . '/config/security.php';
require_once $rootDir . '/services/CacheService.php';
require_once $rootDir . '/services/AiAgentService.php';
require_once $rootDir . '/services/MetaApiService.php';
require_once $rootDir . '/cron/process_queue.php';

// CLI Arguments parsing
$options = getopt('', ['once', 'interval:', 'silent', 'help']);

if (isset($options['help'])) {
    echo "==============================================================================\n";
    echo " 🚀 XINDRO AI Copilot - Background Worker\n";
    echo "==============================================================================\n";
    echo "Opciones:\n";
    echo "  --once          Ejecuta un solo ciclo de sincronización y finaliza.\n";
    echo "  --interval=N    Intervalo de espera entre ciclos en segundos (default: 60).\n";
    echo "  --silent        Suprime la salida visual en consola.\n";
    echo "  --help          Muestra esta ayuda.\n";
    exit(0);
}

$runOnce = isset($options['once']);
$silent = isset($options['silent']);
$cycleInterval = isset($options['interval']) ? max(15, (int)$options['interval']) : 60;

// Data directory & Lock files
$dataDir = $rootDir . '/data';
if (!is_dir($dataDir)) {
    @mkdir($dataDir, 0755, true);
}

$lockFile = $dataDir . '/.worker.lock';
$stopFile = $dataDir . '/.worker_stop';
$statusFile = $dataDir . '/worker_status.json';
$logFile = $dataDir . '/worker.log';

// Clean leftover stop flag
if (file_exists($stopFile)) {
    @unlink($stopFile);
}

// ─── Concurrency Guard with Stale Lock Detection (Windows) ────────────────────
$myPid = getmypid();
$isAnotherRunning = false;
$existingPid = 0;

if (file_exists($lockFile)) {
    $existingPid = (int)trim(@file_get_contents($lockFile) ?: '0');
    if ($existingPid > 0 && $existingPid !== $myPid) {
        $checkCmd = "tasklist /FI \"PID eq {$existingPid}\" /FO CSV /NH 2>NUL";
        $taskOutput = [];
        @exec($checkCmd, $taskOutput);
        $taskLine = implode(' ', $taskOutput);
        if (stripos($taskLine, 'php') !== false) {
            $isAnotherRunning = true;
        } else {
            // Stale lock left over from previous process/session
            @unlink($lockFile);
        }
    }
}

if ($isAnotherRunning) {
    if (!$silent) {
        echo "[\033[0;33mALERTA\033[0m] Ya existe una instancia activa de XINDRO Worker (PID {$existingPid}) corriendo en este equipo.\n";
    }
    exit(0);
}

// Write current PID to lock file
$lockFp = @fopen($lockFile, 'c+');
if ($lockFp) {
    if (!@flock($lockFp, LOCK_EX | LOCK_NB)) {
        if (!$silent) {
            echo "[\033[0;33mALERTA\033[0m] Ya existe una instancia activa de XINDRO Worker corriendo en este equipo.\n";
        }
        @fclose($lockFp);
        exit(0);
    }
    @ftruncate($lockFp, 0);
    @fwrite($lockFp, (string)$myPid);
    @fflush($lockFp);
}

// ─── Logging Helpers ──────────────────────────────────────────────────────────
function workerLog(string $message, string $level = 'info', bool $silent = false, string $logFile = ''): void {
    $time = date('Y-m-d H:i:s');
    $timeShort = date('H:i:s');
    $cleanMsg = strip_tags($message);
    
    // File log (plain text)
    if (!empty($logFile)) {
        if (file_exists($logFile) && filesize($logFile) > 5 * 1024 * 1024) { // 5MB rotation
            @rename($logFile, $logFile . '.old');
        }
        @file_put_contents($logFile, "[{$time}] [{$level}] {$cleanMsg}\n", FILE_APPEND | LOCK_EX);
    }

    if ($silent) return;

    $colors = [
        'info'    => "\033[0;36m",
        'success' => "\033[0;32m",
        'warn'    => "\033[0;33m",
        'error'   => "\033[0;31m",
        'magenta' => "\033[0;35m",
        'bold'    => "\033[1;37m",
        'reset'   => "\033[0m"
    ];
    $c = $colors[$level] ?? $colors['info'];
    $r = $colors['reset'];

    // Safe write to avoid broken pipe crash on headless/detached Windows processes
    @file_put_contents('php://stdout', "[$timeShort] {$c}{$message}{$r}\n");
}

// ─── Status File Writer ───────────────────────────────────────────────────────
function updateWorkerStatus(string $statusFile, array $data): void {
    $data['updated_at'] = date('Y-m-d H:i:s');
    $data['pid'] = getmypid();
    $data['memory_mb'] = round(memory_get_usage(true) / 1024 / 1024, 2);
    $data['memory_peak_mb'] = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
    @file_put_contents($statusFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

// ─── Startup Banner ───────────────────────────────────────────────────────────
if (!$silent) {
    echo "\n\033[1;35m" . str_repeat('=', 65) . "\033[0m\n";
    echo " \033[1;32m🤖 XINDRO AI COPILOT - MOTOR EN SEGUNDO PLANO (WINDOWS)\033[0m\n";
    echo " \033[0;36mMonitoreo autónomo de comentarios, IA y Autopilot 24/7\033[0m\n";
    echo "\033[1;35m" . str_repeat('=', 65) . "\033[0m\n";
    echo " PID: " . getmypid() . " | Intervalo: {$cycleInterval}s | Modo: " . ($runOnce ? "Único (--once)" : "Continuo (Daemon)") . "\n";
    echo " Para detener el proceso presiona Ctrl+C o ejecuta 'scripts/detener_xindro.bat'\n\n";
}

workerLog("🚀 Motor de segundo plano iniciado con éxito.", 'success', $silent, $logFile);

$statusState = [
    'status' => 'running',
    'started_at' => date('Y-m-d H:i:s'),
    'last_beat' => date('Y-m-d H:i:s'),
    'cycle_count' => 0,
    'total_comments_synced' => 0,
    'total_replies_posted' => 0,
    'total_webhooks_processed' => 0,
    'users_monitored' => 0,
    'last_cycle_duration_ms' => 0,
    'autopilot_status' => 'active'
];
updateWorkerStatus($statusFile, $statusState);

// ─── Main Execution Loop ──────────────────────────────────────────────────────
$pdo = Database::getConnection();
$lastTrendsHour = -1;

while (true) {
    $cycleStart = microtime(true);
    $statusState['cycle_count']++;
    $statusState['last_beat'] = date('Y-m-d H:i:s');
    
    // Check stop flag
    if (file_exists($stopFile)) {
        workerLog("🛑 Señal de detención recibida (.worker_stop). Cerrando limpiamente...", 'warn', $silent, $logFile);
        @unlink($stopFile);
        break;
    }

    workerLog("─── Ciclo #{$statusState['cycle_count']} iniciando ─────────────────────────────", 'bold', $silent, $logFile);

    // 1. Drain Webhook Queue (Fast local processing)
    try {
        $qStats = processWebhookQueue($pdo, 30, null, true);
        if (!empty($qStats['processed_events'])) {
            $statusState['total_webhooks_processed'] += $qStats['processed_events'];
            $statusState['total_comments_synced'] += ($qStats['comments_ingested'] ?? 0);
            $statusState['total_replies_posted'] += ($qStats['autopilot_replies'] ?? 0);
            workerLog("📦 Cola Webhook: {$qStats['processed_events']} eventos procesados ({$qStats['comments_ingested']} comentarios)", 'info', $silent, $logFile);
        }
    } catch (Throwable $e) {
        workerLog("⚠️ Error en cola de webhooks: " . $e->getMessage(), 'error', $silent, $logFile);
    }

    // 2. Discover Active Users with Connected Meta Accounts
    try {
        $stmtUsers = $pdo->query("
            SELECT DISTINCT u.id, u.email 
            FROM users u
            JOIN accounts a ON u.id = a.user_id
            WHERE a.is_active = 1
            ORDER BY u.id ASC
        ");
        $activeUsers = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

        // Fallback: If no accounts in DB yet, monitor user 1
        if (empty($activeUsers)) {
            $stmtFallback = $pdo->query("SELECT id, email FROM users ORDER BY id ASC LIMIT 1");
            $activeUsers = $stmtFallback->fetchAll(PDO::FETCH_ASSOC);
        }

        $statusState['users_monitored'] = count($activeUsers);

        foreach ($activeUsers as $userRow) {
            $uId = (int)$userRow['id'];
            $uEmail = $userRow['email'];
            $autopilotOn = Settings::get('autopilot_enabled', '0', $uId) === '1';

            // Check if user has active accounts or Meta tokens
            $userToken = Settings::get('meta_user_access_token', '', $uId);
            $pageToken = Settings::get('meta_page_access_token', '', $uId);
            
            $stmtCountAcc = $pdo->prepare("SELECT COUNT(*) FROM accounts WHERE user_id = ? AND is_active = 1");
            $stmtCountAcc->execute([$uId]);
            $accCount = (int)$stmtCountAcc->fetchColumn();

            if ($accCount === 0 && empty($userToken) && empty($pageToken)) {
                continue;
            }

            workerLog("🔍 Sincronizando usuario [ID: {$uId} | {$uEmail}] (Autopilot: " . ($autopilotOn ? 'ON ✅' : 'OFF ⏸️') . ")...", 'info', $silent, $logFile);

            // Execute Meta QuickSync
            try {
                $syncRes = MetaApiService::quickSync($uId);
                
                $postsChecked = $syncRes['synced_new_posts'] ?? 0;
                $newComments = $syncRes['synced_new_comments'] ?? 0;
                $repliesSent = $syncRes['autopilot_replies'] ?? 0;

                $statusState['total_comments_synced'] += $newComments;
                $statusState['total_replies_posted'] += $repliesSent;

                if ($newComments > 0 || $repliesSent > 0) {
                    workerLog("✨ [ID: {$uId}] Nuevos comentarios: {$newComments} | Respuestas IA enviadas: {$repliesSent}", 'success', $silent, $logFile);
                } else {
                    workerLog("⚡ [ID: {$uId}] Sincronizado sin comentarios nuevos.", 'info', $silent, $logFile);
                }

                if (!empty($syncRes['errors']) && is_array($syncRes['errors'])) {
                    foreach ($syncRes['errors'] as $syncErr) {
                        workerLog("⚠️ Meta API Aviso: {$syncErr}", 'warn', $silent, $logFile);
                    }
                }
            } catch (Throwable $syncEx) {
                workerLog("❌ Error sincronizando usuario {$uId}: " . $syncEx->getMessage(), 'error', $silent, $logFile);
            }
        }
    } catch (Throwable $usrEx) {
        workerLog("❌ Error consultando usuarios activos: " . $usrEx->getMessage(), 'error', $silent, $logFile);
    }

    // 3. Trends Agent periodic sync (every 6 hours)
    try {
        $currentHour = (int)date('G');
        if ($currentHour % 6 === 0 && $currentHour !== $lastTrendsHour) {
            $lastTrendsHour = $currentHour;
            require_once $rootDir . '/services/TrendsAgentService.php';
            workerLog("🔥 Ejecutando sincronización de tendencias programada...", 'magenta', $silent, $logFile);
            TrendsAgentService::syncAllActiveUsers();
            workerLog("✅ Tendencias sincronizadas.", 'success', $silent, $logFile);
        }
    } catch (Throwable $trEx) {
        workerLog("⚠️ Error en sync de tendencias: " . $trEx->getMessage(), 'warn', $silent, $logFile);
    }

    // Calculate cycle duration and update status
    $cycleDuration = round((microtime(true) - $cycleStart) * 1000, 2);
    $statusState['last_cycle_duration_ms'] = $cycleDuration;
    $statusState['status'] = 'idle';
    updateWorkerStatus($statusFile, $statusState);

    workerLog("⏳ Ciclo #{$statusState['cycle_count']} completado en {$cycleDuration}ms. Próximo ciclo en {$cycleInterval}s...", 'info', $silent, $logFile);

    if ($runOnce) {
        workerLog("🏁 Ejecución única completada (--once). Saliendo.", 'success', $silent, $logFile);
        break;
    }

    // Granular sleep allowing instant shutdown detection
    $sleepLeft = $cycleInterval;
    while ($sleepLeft > 0) {
        if (file_exists($stopFile)) {
            workerLog("🛑 Señal de detención recibida durante espera.", 'warn', $silent, $logFile);
            @unlink($stopFile);
            break 2;
        }
        $chunk = min(2, $sleepLeft);
        sleep($chunk);
        $sleepLeft -= $chunk;
    }
}

// ─── Graceful Shutdown Cleanup ────────────────────────────────────────────────
$statusState['status'] = 'stopped';
$statusState['stopped_at'] = date('Y-m-d H:i:s');
updateWorkerStatus($statusFile, $statusState);

if ($lockFp) {
    flock($lockFp, LOCK_UN);
    fclose($lockFp);
}
@unlink($lockFile);

workerLog("👋 XINDRO Background Worker detenido correctamente.", 'success', $silent, $logFile);
exit(0);
