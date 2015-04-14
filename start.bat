:RETRYFIRST
cd php\
set PHP_FCGI_MAX_REQUESTS=0
start /b php-cgi.exe -b 127.0.0.1:9123 -c php.ini
cd ..\nginx\
start /b "" nginx.exe
timeout 2
cd ..
cd nginx\html
..\..\php\php.exe -f checkservers_exec.php
goto RETRYFIRST