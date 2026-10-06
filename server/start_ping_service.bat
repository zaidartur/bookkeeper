@echo off
title Bookkeeper Ping Microservice
echo ========================================================
echo   Bookkeeper Ping Microservice Daemon
echo   Port: 5005 | Host: 0.0.0.0
echo ========================================================

cd /d "%~dp0"

REM Find Python executable
where python >nul 2>nul
if %errorlevel% neq 0 (
    if exist "C:\Python313\python.exe" (
        set "PYTHON_EXE=C:\Python313\python.exe"
    ) else (
        echo [ERROR] Python tidak ditemukan pada PATH atau C:\Python313\python.exe!
        pause
        exit /b 1
    )
) else (
    set "PYTHON_EXE=python"
)

echo Menggunakan Python: %PYTHON_EXE%
echo Menginstall dependensi jika diperlukan...
%PYTHON_EXE% -m pip install -r requirements.txt --quiet

echo Memulai layanan Ping di port 5005...
%PYTHON_EXE% ping.py

pause
