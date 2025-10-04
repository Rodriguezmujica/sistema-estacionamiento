@echo off
set /p msg=Escribe el mensaje del commit: 
git add .
git commit -m "%msg%"
git push origin main
pause
