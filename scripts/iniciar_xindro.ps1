$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$projectDir = Split-Path -Parent $scriptDir
$phpExe = "c:\xampp\php\php.exe"
$workerScript = Join-Path $scriptDir "xindro_worker.php"
$lockFile = Join-Path $projectDir "data\.worker.lock"

# Clean stale lock if process is not actually running
if (Test-Path $lockFile) {
    $oldPid = Get-Content $lockFile -ErrorAction SilentlyContinue
    if ($oldPid) {
        $proc = Get-Process -Id $oldPid -ErrorAction SilentlyContinue
        if (!$proc) {
            Remove-Item $lockFile -Force -ErrorAction SilentlyContinue
        }
    }
}

$outLog = Join-Path $projectDir "data\worker_startup_out.log"
$errLog = Join-Path $projectDir "data\worker_startup_err.log"
Start-Process -FilePath $phpExe -ArgumentList @($workerScript, "--interval=30", "--silent") -WorkingDirectory $projectDir -RedirectStandardOutput $outLog -RedirectStandardError $errLog -WindowStyle Hidden
Write-Output "OK: XINDRO Background Worker lanzado exitosamente."
