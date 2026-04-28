@echo off
chcp 65001 > nul
echo.
echo  מתחיל את כלי הצעות המחיר...
echo.
cd /d "%~dp0"

if not exist "node_modules" (
  echo  מתקין חבילות (פעם ראשונה בלבד)...
  npm install
  echo.
)

start http://localhost:3333
node server.js
pause
