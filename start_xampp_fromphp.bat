@echo off
set APID=%AP_PARENT_PID%
set AP_PARENT_PID=
..\xampp_start.exe  2>&1
set AP_PARENT_PID=%APID%
exit
exit /B
