@echo off
title XINDRO AI Copilot - Segundo Plano
cd /d "%~dp0\.."

echo ==============================================================================
echo  XINDRO AI COPILOT - INICIANDO EN SEGUNDO PLANO
echo ==============================================================================

if not exist "c:\xampp\php\php.exe" (
    echo [ERROR] No se encontro PHP en c:\xampp\php\php.exe
    echo Por favor verifica tu instalacion de XAMPP.
    pause
    exit /b 1
)

if not exist "data" mkdir "data"

:: Iniciar el proceso desacoplado e invisible con PowerShell
powershell -NoProfile -ExecutionPolicy Bypass -Command "Start-Process -FilePath 'c:\xampp\php\php.exe' -ArgumentList 'scripts\xindro_worker.php', '--interval=30' -WorkingDirectory '%cd%' -RedirectStandardOutput 'data\worker_background.log' -RedirectStandardError 'data\worker_background_err.log' -WindowStyle Hidden"

echo.
echo [OK] XINDRO AI Copilot esta ahora activo en segundo plano.
echo  - Monitoreando Instagram y Facebook cada 30 segundos.
echo  - Autopilot de Gemini listo para responder comentarios.
echo  - Puedes cerrar el navegador con total seguridad.
echo.
echo Para verificar el estado abre 'data\worker_status.json' o tu panel web.
echo Para detenerlo en cualquier momento ejecuta 'detener_xindro.bat'.
echo ==============================================================================
powershell -NoProfile -Command "Start-Sleep -Seconds 2" >nul 2>&1
