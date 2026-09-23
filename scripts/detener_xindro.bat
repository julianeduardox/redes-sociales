@echo off
title Detener XINDRO AI Copilot
cd /d "%~dp0\.."

set "STOP_FILE=data\.worker_stop"
set "LOCK_FILE=data\.worker.lock"
set "STATUS_FILE=data\worker_status.json"

echo ==============================================================================
echo  DETENIENDO XINDRO AI COPILOT
echo ==============================================================================
echo Enviando senal de apagado ordenado...
echo 1 > "%STOP_FILE%"

echo Esperando que el worker libere sus recursos...
powershell -Command "Start-Sleep -Seconds 2" >nul 2>&1

:: Si aún existe el lock, verificar PID y forzar detencion si es necesario
if exist "%LOCK_FILE%" (
    for /f "delims=" %%i in ('type "%LOCK_FILE%" 2^>nul') do (
        set "WORKER_PID=%%i"
    )
)

if defined WORKER_PID (
    echo Verificando proceso PID %WORKER_PID%...
    taskkill /F /PID %WORKER_PID% 2>nul
)

if exist "%LOCK_FILE%" del /f /q "%LOCK_FILE%" 2>nul
if exist "%STOP_FILE%" del /f /q "%STOP_FILE%" 2>nul

echo.
echo ==============================================================================
echo  [OK] XINDRO AI Copilot ha sido detenido correctamente.
echo ==============================================================================
echo.
powershell -Command "Start-Sleep -Seconds 2" >nul 2>&1
