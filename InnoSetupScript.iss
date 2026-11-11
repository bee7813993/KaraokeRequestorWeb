; -- InnoSetupScript.iss --
; KaraokeRequestorWeb インストーラースクリプト

; --- バージョン ---
; バージョンの記載場所は「version」ファイル1箇所のみ。
; アプリ本体（オンラインアップデート画面の現在バージョン表示）もこのファイルを参照するため、
; バージョンを上げるときは version ファイルだけを書き換えればよい。
#define VerHandle FileOpen("version")
#define RawVersion FileRead(VerHandle)
#expr FileClose(VerHandle)
; version ファイルは "v0.10.1" 形式。先頭の "v" を除いたものをインストーラー用に使う
#define AppVer (Copy(RawVersion, 1, 1) == "v") ? Copy(RawVersion, 2) : RawVersion

[Setup]
AppName=「ゆかり」Universal KAraoke REquest Web tool
AppVersion={#AppVer}
DefaultDirName=C:\xampp\htdocs
UsePreviousAppDir=yes
AppendDefaultDirName=no
DefaultGroupName=KaraokeRequestorWeb
Compression=lzma2
SolidCompression=yes
OutputDir=userdocs:Inno Setup Examples Output
SetupIconFile=ykr.ico
OutputBaseFilename=KaraokeRequestorWebSetup-{#AppVer}
AppPublisher=KaraokeRequestorWeb
AppPublisherURL=https://github.com/bee7813993/KaraokeRequestorWeb

[Languages]
Name: japanese; MessagesFile: compiler:Languages\Japanese.isl

[Messages]
WelcomeLabel2=このプログラムはお使いのコンピューターに [name/ver] をインストールします。%n%nこのプログラムの実行には事前にxamppをインストールしておく必要があります。%n(後からxamppをインストールしようとするとxamppのセットアップが先に実行されます)%nhttps://www.apachefriends.org/jp/index.html
SelectDirLabel3=[name] をインストールするXAMPPをインストールしたフォルダ内のhtdocsフォルダを指定して、「次へ」をクリックしてください。

[Files]
; --- ルートファイル ---
Source: "*.php"; DestDir: "{app}"; Flags: IgnoreVersion; Components: main
Source: "*.html"; DestDir: "{app}"; Flags: IgnoreVersion; Components: main
Source: "*.bat"; DestDir: "{app}"; Flags: IgnoreVersion; Components: main
Source: "*.js"; DestDir: "{app}"; Flags: IgnoreVersion; Components: main
Source: "*.vbs"; DestDir: "{app}"; Flags: IgnoreVersion; Components: main
Source: "*.txt"; DestDir: "{app}"; Flags: IgnoreVersion; Components: main
Source: "version"; DestDir: "{app}"; Flags: IgnoreVersion; Components: main
Source: "LICENSE"; DestDir: "{app}"; Flags: IgnoreVersion; Components: main
Source: "README.md"; DestDir: "{app}"; Flags: IgnoreVersion; Components: main
Source: "ykr.ico"; DestDir: "{app}"; Flags: IgnoreVersion; Components: main
Source: "favicon.ico"; DestDir: "{app}"; Flags: IgnoreVersion; Components: main
Source: ".htaccess"; DestDir: "{app}"; Flags: IgnoreVersion; Components: main
Source: "ini.ini"; DestDir: "{app}"; Flags: onlyifdoesntexist; Components: main
Source: "listerdb_config.ini"; DestDir: "{app}"; Flags: onlyifdoesntexist; Components: main
Source: "search_sort_priority.json"; DestDir: "{app}"; Flags: onlyifdoesntexist; Components: main
Source: "search_sort_priority_auth.json"; DestDir: "{app}"; Flags: onlyifdoesntexist; Components: main
Source: "limitlist_sample.json"; DestDir: "{app}"; Flags: IgnoreVersion; Components: main

; --- サブディレクトリ ---
Source: "api\*"; DestDir: "{app}\api"; Flags: IgnoreVersion recursesubdirs createallsubdirs; Components: main
Source: "css\*"; DestDir: "{app}\css"; Flags: IgnoreVersion recursesubdirs createallsubdirs; Components: main
Source: "js\*"; DestDir: "{app}\js"; Flags: IgnoreVersion recursesubdirs createallsubdirs; Components: main
Source: "images\*"; DestDir: "{app}\images"; Excludes: "\マスコット\*"; Flags: IgnoreVersion recursesubdirs createallsubdirs; Components: main
Source: "fonts\*"; DestDir: "{app}\fonts"; Flags: IgnoreVersion recursesubdirs createallsubdirs; Components: main
Source: "modules\*"; DestDir: "{app}\modules"; Flags: IgnoreVersion recursesubdirs createallsubdirs; Components: main
Source: "cms\*"; DestDir: "{app}\cms"; Flags: IgnoreVersion recursesubdirs createallsubdirs; Components: main
Source: "qrcode_php\*"; DestDir: "{app}\qrcode_php"; Flags: IgnoreVersion recursesubdirs createallsubdirs; Components: main
Source: "ddns\*"; DestDir: "{app}\ddns"; Flags: IgnoreVersion recursesubdirs createallsubdirs; Components: main
Source: "cmd\*"; DestDir: "{app}\cmd"; Flags: IgnoreVersion recursesubdirs createallsubdirs; Components: main
Source: "l-smash\*"; DestDir: "{app}\l-smash"; Flags: IgnoreVersion recursesubdirs createallsubdirs; Components: main
Source: "notfoundrequest\*"; DestDir: "{app}\notfoundrequest"; Flags: IgnoreVersion recursesubdirs createallsubdirs; Components: main
Source: "samples\*"; DestDir: "{app}\samples"; Flags: IgnoreVersion recursesubdirs createallsubdirs; Components: main
Source: "fortest\*"; DestDir: "{app}\fortest"; Flags: IgnoreVersion recursesubdirs createallsubdirs; Components: main
Source: "test_race\*"; DestDir: "{app}\test_race"; Flags: IgnoreVersion recursesubdirs createallsubdirs; Components: main
;;Source: "allinone\*"; DestDir: "{app}\allinone"; Flags: IgnoreVersion recursesubdirs createallsubdirs; Components: main

; --- foobar2000（.gitignore除外分は手動で配置が必要）---
Source: "foobar2000\*"; DestDir: "{app}\foobar2000"; Flags: IgnoreVersion recursesubdirs createallsubdirs; Components: main

; --- 別途用意が必要なもの（リポジトリ管理外）---
Source: "pfwd_forykr\*"; DestDir: "{app}\pfwd_forykr"; Flags: IgnoreVersion recursesubdirs createallsubdirs; Components: main
;;Source: "gitcmd\*"; DestDir: "{app}\gitcmd"; Flags: IgnoreVersion recursesubdirs createallsubdirs; Components: main
;;Source: "gitbase\.git\*"; DestDir: "{app}\.git"; Flags: IgnoreVersion recursesubdirs createallsubdirs; Components: main

[Run]
; 必要に応じてここに追加

[Components]
Name: main; Description: main files; Types: full compact custom; Flags: fixed

[Icons]
Name: "{group}\KaraokeRequestorWeb for xampp"; Filename: "{app}\ykr.ico"

[Code]
var
  IsNewInstall: Boolean;

// php.ini の指定 extension を有効化する
// - コメントアウトされていれば解除
// - 記述が一切なければ末尾に追記（PR #175 で追加されたフォールバック）
// PHP 7.x 系: ;extension=php_gd2.dll のような形式
// PHP 8.x 系: ;extension=gd のような形式
procedure EnablePHPExtension(PhpIniPath: string; ExtName: string);
var
  Lines: TStringList;
  I: Integer;
  Line, Trimmed, Content: string;
  SemiPos: Integer;
  Modified, Found: Boolean;
begin
  if not FileExists(PhpIniPath) then
    Exit;

  Modified := False;
  Found    := False;
  Lines := TStringList.Create;
  try
    Lines.LoadFromFile(PhpIniPath);
    for I := 0 to Lines.Count - 1 do
    begin
      Line    := Lines[I];
      Trimmed := Trim(Line);

      // すでに有効な行かチェック
      if (LowerCase(Trimmed) = 'extension=' + LowerCase(ExtName)) or
         (LowerCase(Trimmed) = 'extension=php_' + LowerCase(ExtName) + '.dll') or
         (LowerCase(Trimmed) = 'extension=php_' + LowerCase(ExtName) + '2.dll') then
      begin
        Found := True;
        Continue;
      end;

      // セミコロンで始まる行のみ対象
      if (Length(Trimmed) = 0) or (Trimmed[1] <> ';') then
        Continue;

      // 先頭の ; を取り除いた中身を取得
      Content := Trim(Copy(Trimmed, 2, Length(Trimmed)));

      // 以下いずれかにマッチすれば有効化
      //   extension=gd            (PHP 8.x)
      //   extension=php_gd.dll    (PHP 7.x)
      //   extension=php_gd2.dll   (PHP 7.x 旧形式)
      if (LowerCase(Content) = 'extension=' + LowerCase(ExtName)) or
         (LowerCase(Content) = 'extension=php_' + LowerCase(ExtName) + '.dll') or
         (LowerCase(Content) = 'extension=php_' + LowerCase(ExtName) + '2.dll') then
      begin
        Found := True;
        // 元の行から最初の ; だけを取り除く（インデントを維持）
        SemiPos := Pos(';', Line);
        if SemiPos > 0 then
        begin
          Lines[I] := Copy(Line, 1, SemiPos - 1) + Copy(Line, SemiPos + 1, Length(Line));
          Modified := True;
        end;
      end;
    end;

    // php.ini に記述が一切ない場合は末尾に追記
    if not Found then
    begin
      Lines.Add('extension=' + ExtName);
      Modified := True;
    end;

    if Modified then
      Lines.SaveToFile(PhpIniPath);
  finally
    Lines.Free;
  end;
end;

// Apache から PHP の拡張を読み込むために必要な DLL を apache\bin へ補完する
// PHP の拡張（php\ext\*.dll）が依存する DLL は、php フォルダーではなく
// httpd.exe のあるフォルダー（apache\bin）から検索されるため、
// XAMPP 同梱の apache\bin に無いものは読み込みに失敗する。
// 例: XAMPP 8.5.12 の php_curl.dll は brotlidec.dll を必要とするが、
//     apache\bin には brotlicommon.dll と brotlienc.dll しか入っていないため、
//     curl 拡張が読み込めず curl_init() が未定義になる。
// すでに apache\bin にある場合は上書きしない（Apache 側のものを尊重する）。
function EnsureApacheDll(DllName: string): Boolean;
var
  SrcPath, DestPath: string;
begin
  Result   := False;
  SrcPath  := 'C:\xampp\php\' + DllName;
  DestPath := 'C:\xampp\apache\bin\' + DllName;

  if FileExists(DestPath) then
    Exit;
  if not FileExists(SrcPath) then
    Exit;

  Result := FileCopy(SrcPath, DestPath, True);
end;

function InitializeUninstall(): Boolean;
begin
  Result := MsgBox(
    'アンインストールすると、インストール先フォルダ内のすべてのファイルが削除されます。' + #13#10 +
    ExpandConstant('{app}') + #13#10#13#10 +
    '設定のバックアップは、アンインストール前にブラウザで管理画面（init.php）を開いて' + #13#10 +
    '行うことができます。' + #13#10#13#10 +
    '続行するとすべてのファイルが削除されます。よろしいですか？',
    mbConfirmation, MB_OKCANCEL) = IDOK;
end;

procedure CurUninstallStepChanged(CurUninstallStep: TUninstallStep);
begin
  if CurUninstallStep = usUninstall then
    DelTree(ExpandConstant('{app}'), True, True, True);
end;

procedure CurStepChanged(CurStep: TSetupStep);
var
  PhpIniPath: string;
  Msg: string;
  ConfigPath: string;
  CopiedBrotli: Boolean;
begin
  // ファイルコピー開始前に config.ini の有無を記録
  if CurStep = ssInstall then
  begin
    IsNewInstall := not FileExists(ExpandConstant('{app}\config.ini'));
    Exit;
  end;

  if CurStep <> ssPostInstall then
    Exit;

  PhpIniPath := 'C:\xampp\php\php.ini';

  if not FileExists(PhpIniPath) then
  begin
    MsgBox('php.ini が既定の場所に見つかりませんでした。' + #13#10 +
           PhpIniPath + #13#10#13#10 +
           'XAMPPをインストール後、手動で php.ini の gd・zip・curl の extension を有効化してください。',
           mbInformation, MB_OK);
    Exit;
  end;

  EnablePHPExtension(PhpIniPath, 'gd');
  EnablePHPExtension(PhpIniPath, 'zip');
  EnablePHPExtension(PhpIniPath, 'curl');

  // curl 拡張が必要とする DLL を apache\bin へ補完する
  CopiedBrotli := EnsureApacheDll('brotlidec.dll');

  // 新規インストール時のみ UI v2 をデフォルト有効化
  if IsNewInstall then
  begin
    ConfigPath := ExpandConstant('{app}\config.ini');
    SetIniString('', 'usenewsearchui',    '1', ConfigPath);
    SetIniString('', 'usenewrequestlist', '1', ConfigPath);
  end;

  Msg := 'php.ini の設定を確認しました。' + #13#10 + PhpIniPath + #13#10#13#10 +
         '・extension=gd   → 有効' + #13#10 +
         '・extension=zip  → 有効' + #13#10 +
         '・extension=curl → 有効' + #13#10#13#10 +
         '※ すでに有効だった場合は変更しません。';

  if CopiedBrotli then
    Msg := Msg + #13#10#13#10 +
           'curl 拡張が必要とする brotlidec.dll を' + #13#10 +
           'C:\xampp\apache\bin にコピーしました。';

  Msg := Msg + #13#10#13#10 +
         'php.ini と apache\bin の変更を反映するには、xamppのコントロールパネルで' + #13#10 +
         'Apache を Stop してから Start してください。';

  MsgBox(Msg, mbInformation, MB_OK);
end;
