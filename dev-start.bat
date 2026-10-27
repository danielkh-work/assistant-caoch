@echo off
REM Starts the local dev server AND the queue worker together, each in its
REM own window. Without the worker running, broadcasts (scoreboard updates,
REM down changes, suggestions, everything real-time between HC/AC/QB) get
REM queued but never delivered until something processes them - the app
REM still "works" but nothing shows up live, only after a manual refresh.
REM Close either window to stop that process; closing this window does not
REM stop them (they run detached).

cd /d "%~dp0"

set PHP_EXE=C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe

start "Laravel Server (artisan serve)" cmd /k "%PHP_EXE%" artisan serve
start "Laravel Queue Worker" cmd /k "%PHP_EXE%" artisan queue:work --sleep=1 --tries=3

echo Started both windows: "Laravel Server" and "Laravel Queue Worker".
echo Close this window any time - the other two keep running.
pause
