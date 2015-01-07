KaraokeRequestorWeb
===================

持ち込みカラオケを行う際に、カラオケ動画をブラウザ上でリクエストをするシステム。

目的
持ち込みカラオケを行う際に動画ファイルを入れたディスクを持ち寄ってカラオケ動画再生用PCに接続。
参加者が手持ちの端末のブラウザから動画ファイルを検索しリクエストとして登録する。
機材係がリクエストされた内容を確認できるようにする。


必要な環境
- Everything Search Engine ( http://www.voidtools.com/ )
- Web Server (nginx http://nginx.org/en/index.html 等)
- PHP実行環境 ( http://php.net/ )

使用するための準備(機材係編)

1. 「Everything Serach Engine」をセットアップ。
対象となる動画ファイルだけを検索できるようにしておく。
　　「検索データ->フォルダ」に対象となるドライブorフォルダ名を登録。(途中でディスクを追加した時もここに登録する)
　　「検索データ->除外」の「検索対象を以下のファイルのみに制限する(I)」に動画ファイルの拡張子を登録して
　　　動画ファイルのみが検索結果に表示されるようにする。
　　　　設定例）*.mp4; *.avi; *.mkv; *.flv; *.mpg
    HTTPサーバ「HTTPサーバを有効」にチェック。
    HTTPサーバポート(H) に[81]をしてする。、
2. PHPが動作するWebサーバーをセットアップする。
　windows用nginxとWindows用phpを使用する場合。
  Point 
    php.ini 内の extension=php_pdo_sqlite.dll のコメントを外す。
　　nginx.conf内に以下の設定をする。
---- ( start ) -----
        location ~ \.php$ {
            fastcgi_pass   127.0.0.1:9123;
            fastcgi_index  index.php;
            fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
            include        fastcgi_params;
        }　　　
---- ( end ) -----
  　以下のようなbatファイルで起動する。
---- ( start ) -----
cd php\
start /b php-cgi.exe -b 127.0.0.1:9123 -c php.ini
cd ..\nginx\
start nginx.exe
---- ( end ) -----
3. 本プロジェクトのphpファイルなど一式をhtmlルートに設置する。
4. 会場ではモバイルルータなどのネットワークをプライバシーセパレーターOFFの状態で公開し、
　 参加者の端末と同じネットワークに接続する。

2～3まで設定済みのものを「allinone」ディレクトリ内にzipで固めて置いています。
展開してstart.batを実行するとサーバの起動ができます。
phpとかは古いかもしれないので最新のものを取ってきて上書き推奨。

使用方法(参加者編)

手持ちの端末(WindowsでもAndoroidでもiPhoneでもOK)から、
http://<機材PCのIP>/request.php
にアクセスして使用する。
