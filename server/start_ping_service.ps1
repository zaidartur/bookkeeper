# ========================================================
#   Bookkeeper Ping Microservice - PowerShell Daemon Runner
#   Menjalankan server/ping.py dengan mekanisme auto-restart
# ========================================================

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $ScriptDir

$PythonExe = (Get-Command python -ErrorAction SilentlyContinue).Source
if (-not $PythonExe) {
    if (Test-Path "C:\Python313\python.exe") {
        $PythonExe = "C:\Python313\python.exe"
    } else {
        Write-Error "Python executable tidak ditemukan."
        Exit 1
    }
}

Write-Host "Menggunakan Python: $PythonExe" -ForegroundColor Cyan
Write-Host "Memastikan dependensi terpasang..." -ForegroundColor Gray
& $PythonExe -m pip install -r requirements.txt --quiet

$env:PING_SERVER_PORT = "5000"
$env:PING_SERVER_HOST = "0.0.0.0"

Write-Host "Memulai daemon Bookkeeper Ping Service di port 5000..." -ForegroundColor Green

while ($true) {
    try {
        $timestamp = (Get-Date).ToString("yyyy-MM-dd HH:mm:ss")
        Write-Host "[$timestamp] Menjalankan ping.py..." -ForegroundColor Yellow
        & $PythonExe ping.py
    } catch {
        Write-Warning "Layanan ping berhenti: $_"
    }
    
    Write-Host "Proses terhenti. Menunggu 5 detik sebelum me-restart..." -ForegroundColor Red
    Start-Sleep -Seconds 5
}
