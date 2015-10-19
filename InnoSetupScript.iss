; -- Example1.iss --
; Demonstrates copying 3 files and creating an icon.

; SEE THE DOCUMENTATION FOR DETAILS ON CREATING .ISS SCRIPT FILES!

[Setup]
AppName=KaraokeRequestorWeb for xampp
AppVersion=0.09.3
DefaultDirName=C:\xampp\htdocs
UsePreviousAppDir=yes
AppendDefaultDirName=no
DefaultGroupName=KaraokeRequestorWeb
Compression=lzma2
SolidCompression=yes
OutputDir=userdocs:Inno Setup Examples Output
;;SourceDir={userdocs}\GitHub\KaraokeRequestorWeb
;;UninstallFilesDir={userdocs}\krw
SetupIconFile=krw.ico
;;CreateAppDir=no
OutputBaseFilename=KaraokeRequestorWebSetup

[Files]
;;Source: "package\xampp-win32-5.6.12-0-VC11-installer.exe"; DestDir: {tmp}; Components: xampp
Source: "*.php"; DestDir: "{app}"; Components: main
Source: "*.bat"; DestDir: "{app}"; Components: main
Source: "*.js"; DestDir: "{app}"; Components: main
Source: "LICENSE"; DestDir: "{app}"; Components: main
Source: "css\*"; DestDir: "{app}\css"; Components: main
Source: "ddns\*"; DestDir: "{app}\ddns"; Components: main
Source: "images\*"; DestDir: "{app}\images"; Components: main
Source: "fonts\*"; DestDir: "{app}\fonts"; Components: main
Source: "js\*"; DestDir: "{app}\js"; Components: main
Source: "modules\*"; DestDir: "{app}\modules"; Components: main
Source: "notfoundrequest\*.php"; DestDir: "{app}\notfoundrequest"; Components: main
Source: "foobar2000\*"; DestDir: "{app}\foobar2000";Flags: recursesubdirs; Components: main
Source: "krw.ico"; DestDir: "{app}"; Components: main
Source: "favicon.ico"; DestDir: "{app}"; Components: main

[Run]
;;Filename: "{tmp}\xampp-win32-5.6.12-0-VC11-installer.exe";  StatusMsg: "Installing xampp..."

[Components]
Name: main;  Description: main files ; Types: full compact custom; Flags: fixed
;;Name: xampp; Description: xampp setup


[Icons]
Name: "{group}\KaraokeRequestorWeb for xampp"; Filename: "{app}\krw.ico"
