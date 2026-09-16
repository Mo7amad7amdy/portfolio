@echo off
REM ============================================================
REM  Portfolio installer for Laragon (Windows)
REM  - Downloads the Laravel skeleton + vendor via Composer
REM  - Merges it in WITHOUT overwriting the portfolio files
REM  - Creates .env, the SQLite database, runs migrations + seed
REM ============================================================
setlocal
cd /d "%~dp0"

where php >nul 2>nul || (echo [x] PHP not found. Open this from the Laragon Terminal. & goto :fail)
where composer >nul 2>nul || (echo [x] Composer not found. Open this from the Laragon Terminal. & goto :fail)

if exist vendor\autoload.php goto :configure

echo.
echo [1/4] Downloading Laravel (this takes a minute)...
if exist _skeleton rmdir /s /q _skeleton
call composer create-project laravel/laravel _skeleton --prefer-dist --no-interaction
if errorlevel 1 goto :fail

echo.
echo [2/4] Merging Laravel into the portfolio (existing files are kept)...
REM /XC /XN /XO = skip files that already exist, so only missing files are copied.
robocopy _skeleton . /E /XC /XN /XO /NFL /NDL /NJH /NJS /NP >nul
if errorlevel 8 goto :fail
rmdir /s /q _skeleton

:configure
echo.
echo [3/4] Configuring...
if not exist .env copy .env.example .env >nul
findstr /r /c:"^APP_KEY=base64" .env >nul || call php artisan key:generate --force
if not exist database\database.sqlite type nul > database\database.sqlite

echo.
echo [4/4] Migrating and seeding your CV data...
call php artisan migrate --seed --force
if errorlevel 1 goto :fail
call php artisan optimize:clear >nul

echo.
echo ============================================================
echo  Done!
echo   Site:       http://portfolio.test   (Laragon: Menu ^> Apache/Nginx ^> Reload)
echo               or run:  php artisan serve   then open http://127.0.0.1:8000
echo   Dashboard:  /admin
echo   Login:      mohammad-hamdy@hotmail.com  /  password
echo   Change the password right away: Dashboard ^> Account.
echo ============================================================
pause
exit /b 0

:fail
echo.
echo [x] Setup failed - see the messages above.
pause
exit /b 1
