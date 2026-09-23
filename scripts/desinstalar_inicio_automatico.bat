@echo off
title Desinstalar Inicio Automatico - XINDRO AI Copilot
cd /d "%~dp0\.."

echo ==============================================================================
echo  DESINSTALADOR DE INICIO AUTOMATICO
echo ==============================================================================
echo.

schtasks /Delete /TN "XindroCopilotWorker" /F

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ==============================================================================
    echo  [OK] La tarea programada ha sido eliminada con exito.
    echo ==============================================================================
) else (
    echo.
    echo [INFO] No se encontro ninguna tarea registrada previa.
)

echo.
pause
