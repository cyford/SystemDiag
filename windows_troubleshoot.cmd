@echo off
setlocal enabledelayedexpansion

echo Windows System Network Troubleshooter
echo =====================================
echo.

set "gateway_ok=FAIL"
set "external_ok=FAIL"
set "dns_ok=FAIL"
set "port443_ok=FAIL"
set "port80_ok=FAIL"

echo === OS Information ===
systeminfo | findstr /B /C:"OS Name" /C:"OS Version"
echo.

echo === Network Interfaces ===
ipconfig /all
echo.

echo === Default Gateway ===
route print 0.0.0.0
echo.

echo === DNS Servers ===
nslookup google.com | findstr "Server:"
echo.

echo === Ping Gateway Test ===
for /f "tokens=3" %%i in ('route print ^| findstr "0.0.0.0.*0.0.0.0"') do (
    echo Pinging gateway %%i
    ping -n 4 %%i | findstr "Lost = 0" >nul && set "gateway_ok=PASS"
    ping -n 4 %%i
)
echo.

echo === External Connectivity Test (8.8.8.8) ===
ping -n 4 8.8.8.8 | findstr "Lost = 0" >nul && set "external_ok=PASS"
ping -n 4 8.8.8.8
echo.

echo === DNS Resolution Test (google.com) ===
ping -n 4 google.com | findstr "Lost = 0" >nul && set "dns_ok=PASS"
ping -n 4 google.com
echo.

echo === Firewall/Port Tests ===
echo Testing HTTPS (443) to google.com:
powershell -Command "if((Test-NetConnection google.com -Port 443).TcpTestSucceeded){exit 0}else{exit 1}" && set "port443_ok=PASS"
powershell -Command "Test-NetConnection google.com -Port 443"
echo.
echo Testing HTTP (80) to google.com:
powershell -Command "if((Test-NetConnection google.com -Port 80).TcpTestSucceeded){exit 0}else{exit 1}" && set "port80_ok=PASS"
powershell -Command "Test-NetConnection google.com -Port 80"
echo.

echo === Windows Firewall Status ===
netsh advfirewall show allprofiles state
echo.

echo === Connected Devices (ARP Table) ===
arp -a
echo.

echo === Network Connections ===
netstat -an | findstr LISTENING
echo.

echo ========================================
echo TROUBLESHOOTING SUMMARY
echo ========================================
echo.
if "!gateway_ok!"=="PASS" (echo [X] Gateway Ping - PASS) else (echo [!] Gateway Ping - FAIL)
if "!external_ok!"=="PASS" (echo [X] External Connectivity - PASS) else (echo [!] External Connectivity - FAIL)
if "!dns_ok!"=="PASS" (echo [X] DNS Resolution - PASS) else (echo [!] DNS Resolution - FAIL)
if "!port443_ok!"=="PASS" (echo [X] HTTPS Port 443 - PASS) else (echo [!] HTTPS Port 443 - FAIL)
if "!port80_ok!"=="PASS" (echo [X] HTTP Port 80 - PASS) else (echo [!] HTTP Port 80 - FAIL)
echo.
echo ISSUES FOUND:
if "!gateway_ok!"=="FAIL" echo - Gateway unreachable - Check network connection
if "!external_ok!"=="FAIL" echo - No internet access - Check firewall/ISP
if "!dns_ok!"=="FAIL" echo - DNS resolution failed - Check DNS settings
if "!port443_ok!"=="FAIL" echo - HTTPS blocked - Check firewall rules
if "!port80_ok!"=="FAIL" echo - HTTP blocked - Check firewall rules
echo.
echo TROUBLESHOOTING STEPS:
echo 1. netsh winsock reset
echo 2. ipconfig /flushdns
echo 3. netsh int ip reset
echo 4. Check Windows Firewall settings
echo 5. Restart network adapter
echo.
pause