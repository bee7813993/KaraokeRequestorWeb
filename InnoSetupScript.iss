; -- Example1.iss --
; Demonstrates copying 3 files and creating an icon.

; SEE THE DOCUMENTATION FOR DETAILS ON CREATING .ISS SCRIPT FILES!


[Setup]
AppName=「ゆかり」Universal KAraoke REquest Web tool
AppVersion=0.09.9
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
AppPublisher=KaraokeRequestorWeb
AppPublisherURL=https://github.com/bee7813993/KaraokeRequestorWeb

[Languages]
Name: japanese; MessagesFile: compiler:Languages\Japanese.isl 

[Messages]
WelcomeLabel2=このプログラムはご使用のコンピューターへ [name/ver] をインストールします。%n%nこのプログラムの実行には事前にxamppをインストールしておく必要があります。%n(後からxamppをインストールしようとするとxamppのセットアップが失敗します)%nhttps://www.apachefriends.org/jp/index.html
SelectDirLabel3=[name] をインストールするXAMPPをインストールしたフォルダ内のhtdocsフォルダを指定して、「次へ」をクリックしてください。

[Files]
;;Source: "package\xampp-win32-5.6.12-0-VC11-installer.exe"; DestDir: {tmp}; Components: xampp
Source: "*.php"; DestDir: "{app}"; Flags: IgnoreVersion; Components: main
Source: "version"; DestDir: "{app}"; Flags: IgnoreVersion; Components: main
Source: "*.bat"; DestDir: "{app}"; Flags: IgnoreVersion; Components: main
Source: "*.js"; DestDir: "{app}"; Flags: IgnoreVersion; Components: main
Source: "LICENSE"; DestDir: "{app}"; Flags: IgnoreVersion; Components: main
Source: "css\*"; DestDir: "{app}\css"; Flags: IgnoreVersion; Components: main
Source: "ddns\*"; DestDir: "{app}\ddns"; Flags: IgnoreVersion; Components: main
Source: "images\*"; DestDir: "{app}\images"; Flags: IgnoreVersion; Components: main
Source: "fonts\*"; DestDir: "{app}\fonts"; Flags: IgnoreVersion; Components: main
Source: "js\*"; DestDir: "{app}\js"; Flags: IgnoreVersion; Components: main
Source: "cms\*"; DestDir: "{app}\cms"; Flags: IgnoreVersion; Components: main
Source: "video-js\*"; DestDir: "{app}\js"; Flags: IgnoreVersion; Components: main
Source: "modules\*"; DestDir: "{app}\modules"; Flags: IgnoreVersion; Components: main
Source: "notfoundrequest\*.php"; DestDir: "{app}\notfoundrequest"; Flags: IgnoreVersion; Components: main
Source: "ニコカラコメントPlayer2.exe"; DestDir: "{app}"; Flags: IgnoreVersion; Components: main
Source: "ini.ini"; DestDir: "{app}"; Flags: onlyifdoesntexist; Components: main
Source: "foobar2000\*"; DestDir: "{app}\foobar2000";Flags: IgnoreVersion recursesubdirs; Components: main
Source: "commentPlayer\*"; DestDir: "{app}\commentPlayer";Flags: IgnoreVersion recursesubdirs; Components: main
Source: "krw.ico"; DestDir: "{app}"; Flags: IgnoreVersion; Components: main
Source: "favicon.ico"; DestDir: "{app}"; Flags: IgnoreVersion; Components: main
Source: ".htaccess"; DestDir: "{app}"; Flags: IgnoreVersion; Components: main
Source: "gitbase\.git\*"; DestDir: "{app}\.git"; Flags: IgnoreVersion recursesubdirs createallsubdirs; Components: main
Source: "gitcmd\*"; DestDir: "{app}\gitcmd"; Flags: IgnoreVersion recursesubdirs createallsubdirs; Components: main
Source: "l-smash\*"; DestDir: "{app}\gitcmd"; Flags: IgnoreVersion recursesubdirs createallsubdirs; Components: main
Source: "pfwd_forykr\*"; DestDir: "{app}\pfwd_forykr"; Flags: IgnoreVersion; Components: main

[Run]
;;Filename: "{tmp}\xampp-win32-5.6.12-0-VC11-installer.exe";  StatusMsg: "Installing xampp..."

[Components]
Name: main;  Description: main files ; Types: full compact custom; Flags: fixed
;;Name: xampp; Description: xampp setup


[Icons]
Name: "{group}\KaraokeRequestorWeb for xampp"; Filename: "{app}\krw.ico"
