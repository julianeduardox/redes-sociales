@echo off
title Instalar Inicio Automatico - XINDRO AI Copilot
cd /d "%~dp0\.."

echo ==============================================================================
echo  INSTALADOR DE INICIO AUTOMATICO PARA XINDRO AI COPILOT
echo ==============================================================================
echo  Esta accion registrara una tarea en Windows para que XINDRO se inicie
echo  automaticamente en segundo plano cada vez que inicies sesion en tu PC.
echo ==============================================================================
echo.

set "VBS_PATH=c:\xampp\htdocs\Redes sociales\scripts\iniciar_xindro_segundo_plano.vbs"

if not exist "%VBS_PATH%" (
    echo [ERROR] No se encontro el archivo: %VBS_PATH%
    pause
    exit /b 1
)

echo Creando tarea programada 'XindroCopilotWorker'...
schtasks /Create /TN "XindroCopilotWorker" /TR "wscript.exe \"%VBS_PATH%\"" /SC ONLOGON /F /RL HIGHEST

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ==============================================================================
    echo  [OK] Instalacion exitosa.
    echo  XINDRO arrancara en segundo plano cada vez que inicies sesion en Windows.
    echo ==============================================================================
) else (
    echo.
    echo [AVISO] Si la tarea requiere permisos elevados, ejecuta este archivo como Administrador.
)

echo.
pause
