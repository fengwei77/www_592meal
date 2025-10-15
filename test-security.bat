@echo off
chcp 65001 > nul
echo ========================================
echo Security Settings System - Quick Test
echo ========================================
echo.

D:\laragon\bin\php\php-8.4.12-nts-Win32-vs17-x64\php.exe artisan test tests/Feature/SecuritySettingsTest.php tests/Feature/IpWhitelistTest.php tests/Feature/TwoFactorAuthTest.php

echo.
echo ========================================
echo Press any key to exit...
pause
