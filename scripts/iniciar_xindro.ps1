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

$cmd = "`"$phpExe`" `"$workerScript`" --interval=30 --silent"
Invoke-CimMethod -ClassName Win32_Process -MethodName Create -Arguments @{ CommandLine = $cmd; CurrentDirectory = $projectDir } | Out-Null
Write-Output "OK: XINDRO Background Worker lanzado exitosamente."
