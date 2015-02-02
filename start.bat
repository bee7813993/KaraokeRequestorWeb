:RETRYFIRST
cd php\
start /b php-cgi.exe -b 127.0.0.1:9123 -c php.ini
cd ..\nginx\
start /w "" nginx.exe
timeout 2
cd ..
rem pause
goto RETRYFIRST
