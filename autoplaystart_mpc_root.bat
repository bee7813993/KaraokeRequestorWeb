cd nginx\html\
:RETRYFIRST
..\..\php\php.exe -f manage-mpc.php
timeout 2
goto RETRYFIRST
