cd .\nginx\
dir
start /b "" nginx.exe -s stop
timeout 2
cd ..

taskkill /IM php-cgi.exe /F
