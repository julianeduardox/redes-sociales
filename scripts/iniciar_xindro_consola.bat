@echo off
title XINDRO AI Copilot - Consola de Monitoreo en Vivo
cd /d "%~dp0\.."

echo ==============================================================================
echo  XINDRO AI COPILOT - MODO CONSOLA EN VIVO
echo ==============================================================================
echo  Iniciando monitoreo de Instagram y Facebook...
echo  (Puedes minimizar esta ventana para que siga trabajando en segundo plano)
echo ==============================================================================
echo.

if not exist "c:\xampp\php\php.exe" (
    echo [ERROR] No se encontro PHP en c:\xampp\php\php.exe
    pause
    exit /b 1
)

"c:\xampp\php\php.exe" "scripts\xindro_worker.php" --interval=30

echo.
echo ==============================================================================
echo  El motor de XINDRO se ha detenido.
echo ==============================================================================
pause
