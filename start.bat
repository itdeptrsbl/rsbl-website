@echo off
REM Jalankan di Windows jika PHP XAMPP ada pada lokasi default.
cd /d "%~dp0"
if not exist "C:\xampp\php\php.exe" (
 echo PHP tidak ditemukan. Gunakan Apache XAMPP dan buka localhost/rsbl-website/
 pause
 exit /b 1
)
echo Buka http://localhost:8080 di browser. Tekan Ctrl+C untuk berhenti.
"C:\xampp\php\php.exe" -d upload_max_filesize=50M -d post_max_size=128M -S localhost:8080 router.php
pause
