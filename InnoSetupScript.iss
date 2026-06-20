; -- Example1.iss --
; Demonstrates copying 3 files and creating an icon.

; SEE THE DOCUMENTATION FOR DETAILS ON CREATING .ISS SCRIPT FILES!


[Setup]
AppName=�u�䂩��vUniversal KAraoke REquest Web tool
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
WelcomeLabel2=���̃v���O�����͂��g�p�̃R���s���[�^�[�� [name/ver] ���C���X�g�[�����܂��B%n%n���̃v���O�����̎��s�ɂ͎��O��xampp���C���X�g�[�����Ă����K�v������܂��B%n(�ォ��xampp���C���X�g�[�����悤�Ƃ����xampp�̃Z�b�g�A�b�v�����s���܂�)%nhttps://www.apachefriends.org/jp/index.html
SelectDirLabel3=[name] ���C���X�g�[������XAMPP���C���X�g�[�������t�H���_����htdocs�t�H���_���w�肵�āA�u���ցv���N���b�N���Ă��������B

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
Source: "�j�R�J���R�����gPlayer2.exe"; DestDir: "{app}"; Flags: IgnoreVersion; Components: main
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
Source: "fonts\*"; DestDir: "{app}\fonts"; Flags: IgnoreVersion; Components: main
Source: "qrcode_php\*"; DestDir: "{app}\qrcode_php"; Flags: IgnoreVersion; Components: main
Source: "l-smash\*"; DestDir: "{app}\l-smash"; Flags: IgnoreVersion; Components: main
Source: "cmd\*"; DestDir: "{app}\cmd"; Flags: IgnoreVersion; Components: main
Source: "ignorecharlist.txt"; DestDir: "{app}"; Flags: IgnoreVersion; Components: main

[Run]
;;Filename: "{tmp}\xampp-win32-5.6.12-0-VC11-installer.exe";  StatusMsg: "Installing xampp..."

[Components]
Name: main;  Description: main files ; Types: full compact custom; Flags: fixed
;;Name: xampp; Description: xampp setup


[Icons]
Name: "{group}\KaraokeRequestorWeb for xampp"; Filename: "{app}\krw.ico"

[code]
procedure CurStepChanged(CurStep: TSetupStep);
var
  phpIni: String;
  content: AnsiString;
begin
  if CurStep = ssPostInstall then
  begin
    phpIni := ExpandConstant('C:\xampp\php\php.ini');
    if FileExists(phpIni) then
    begin
      if LoadStringFromFile(phpIni, content) then
      begin
        // Enable the php zip extension (required by the ZIP update method).
        // Idempotent: append only when missing, otherwise uncomment.
        if Pos('extension=zip', content) = 0 then
          content := content + #13#10 + 'extension=zip' + #13#10
        else
          StringChangeEx(content, ';extension=zip', 'extension=zip', True);
        SaveStringToFile(phpIni, content, False);
      end;
    end;
  end;
end;
