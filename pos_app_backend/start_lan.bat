@echo off
echo Starting POS System on LAN...
echo Backend: http://10.40.0.188:8000
echo Frontend: http://10.40.0.188:3000

start "Backend Server" php -S 10.40.0.188:8000 -t public
start "Frontend Server" npm run dev

echo Both servers have been started in new windows.
pause
