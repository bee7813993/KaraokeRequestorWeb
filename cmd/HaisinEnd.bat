cd /d %~dp0
rem prepare
sendmidicommand.exe V-1HD 0xf0 0x41 0x10 0x0 0x0 0x0 0x20 0x12 0x70 0x01 0x20 0x0 0x6f 0xf7

rem change
sendmidicommand.exe V-1HD 0xb0 0x14 0x00
sendmidicommand.exe V-1HD 0xb0 0x14 0x7f
sendmidicommand.exe V-1HD 0xb0 0x14 0x00

sendmidicommand.exe V-1HD 0xf0 0x41 0x10 0x0 0x0 0x0 0x20 0x12 0x71 0x01 0x00 0x7f 0x0f 0xf7
sendmidicommand.exe V-1HD 0xf0 0x41 0x10 0x0 0x0 0x0 0x20 0x12 0x71 0x01 0x04 0x00 0x0a 0xf7
