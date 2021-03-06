![ゆかりフレンズ](images/yukarifriends_logo.png "ゆかりフレンズ")

ゆかり
==============================================

「ゆかり」(Universal KAraoke REquest Web tool) は  
持ち込みカラオケを行う際に、カラオケ動画をブラウザ上でリクエストをするためツールです。

# 目的
持ち込みカラオケをする際に、参加者が手持ちのスマホ等でカラオケのデンモクのように曲を検索して予約できるようにする  
その他参加者が曲選択できるイベントとかでも

# できること

## 持ち込み曲関連
- 事前に登録作業など不要で、動画ファイルを入れたディスクを持ち寄って使用可能(ファイル名に少なくとも曲名が付いていること)
- 動画ファイルだけでなくmp3+タイムタグ付き歌詞ファイルも使用可能
- 参加者の手持ちの端末内の動画ファイルをアップロードしてリクエスト予約可能
- youtubeのURLを指定してリクエスト予約可能 ※

## リクエスト予約関連
- 参加者が手持ちの端末のブラウザから動画を検索しリクエストとして予約
- アニソンに関しては、作品名、歌手名(その他製作者も)、ブランド名から検索可能(anison.infoのサイトの情報を使用) ※
- 参加者がカラオケ配信曲を歌いたい場合は、「配信曲を歌う」とリクエストしておくと自分の番になるとカラオケ配信の画面＆音声に自動切替
- リクエスト予約された曲を順番の入れ替え可能

## 再生関連
- リクエスト予約された曲を順番に自動再生
- 参加者が手持ちの端末から、再生中のPlayerの音量調節、再生停止再開、音声トラック変更(OnVocal/OffVocal等)等の操作可能
- 参加者が手持ちの端末から、ニコ生風にコメントを画面に流すことが可能 ※

## カラオケ配信曲中BGV(バックグラウンド動画)機能
- 曲中BGVを入力可能なカラオケ機の場合、配信曲中の映像のバックに持ち込んだ動画を流すことが可能
- 曲中BGVをブルーバックにできるカラオケ機の場合、HDMIミキサー「Roland V-HD1」の映像合成機能により配信のカラオケ字幕の後ろに持ち込んだ動画を流すことが可能
(この場合カラオケ動画も配信もフルHDで表示可能)

## その他
- 機材係は動作中は一切持ち込み用PCを操作する必要なし

※の機能はインターネット接続環境が必要

# 必要な環境

## 機材
- Windows PC
TV出力(HDMI出力)ができて、セカンダリモニタで動画が問題なく再生できる性能のあるもの
Atom系CPUのPCじゃなければ大抵大丈夫 (それで動かなかったらWindowsをクリーンインストール推奨)
- Wifiアクセスポイント (少人数の場合スマホテザリングでも可能)

(あれば便利なもの)
- HDMIミキサー Roland V-HD1 (配信とカラオケ動画の切り替えに使用し、配信時に任意の動画をBGV(バックグラウンド動画)として使用できる)

## ソフトウェア
- ファイル検索ソフト [Everything File Search](http://www.voidtools.com/,"everything")
- Web Server [xampp](https://www.apachefriends.org/jp/index.html)
- PHP実行環境 ( xamppに同梱 )
- 動画Player [MediaPlayerClassic BE](http://sourceforge.net/projects/mpcbe/)
- 音楽Player [foobar2000](https://www.foobar2000.org/) with foo_httpcontrol [plugin](https://bitbucket.org/oblikoamorale/foo_httpcontrol/wiki/Home)


使用方法は
http://bee7813993.github.io/KaraokeRequestorWeb/
を参照

最初のセットアップがそこそこ面倒なことが分かったので、
簡単にセットアップツールができるまで、Windowsのリモートアシスタンスでセットアップのお手伝いをしようと思います。
何らかの方法(twitter,mixi,facebookなど)で私に連絡を取ってもらえればお手伝いしますのでご連絡お待ちしています。


# 更新履歴

## 今後実装予定
縛りオフ用曲リスト  
再生終了ボタンを押した際音をフェードアウト  
セトリまとめ用曲情報表示  
anison.info検索時、項目リンク(歌手名検索の結果から作品名検索ができるように等)  
通常予約音源差し替え

## 2017年2月16日
ビンゴ機能：歌われた曲に応じて番号を開けるビンゴの結果を表示

## 2017年1月18日
オンライン接続対応：参加者が機材Wifiに接続しなくても使用できるようにした

## 2017年1月16日
検索ボタンを移動：ファイル検索画面に入るためのボタンを上部メニューに常駐
ユーザー向けセトリダウンロード機能：予約一覧画面にCSV形式でセットリストをダウンロードできるボタンを追加

## 2016年12月19日
トップページメッセージ対応：予約一覧画面と検索トップ画面に任意のメッセージをHTML形式で記載できるようにした。曲リストとかを作って探しやすくすることができる。
ピックアップ曲リスト２対応：外部Webサーバー（ゆかり上のhtdocs以下でもOK）に曲リストを置いて、曲の検索画面に戻ってくる機能を作成。戻り先のURLはWebのリクエスト変数でわたし、遷移先で戻りのリンクを作成する

## 2016年11月11日
再生中曲差し替え対応：動作が再生中に曲の差し替えを行うと新しいファイルを最初から再生できるようにした
anisoninfo検索、関連検索：anison.info検索で関連曲を検索できるようにした。曲を検索してからその歌手で検索とか、作品名で検索とかできる

## 2016年8月8日
別プレーヤー指定予約：曲によって、別のPlayerで再生したい場合、予約時に別Playerを使うように指定できるようにした

## 2016年8月3日
MPCプレイヤーコントローラーに機能追加：フェードアウト、速度調整、画面サイズ調整、音ズレ修正、D3Dフルスクリーン有効の機能を追加
曲終了時フェードアウト：曲終了ボタンを押すと音量をフェードアウトするように変更

## 2016年7月27日
ピックアップ曲リスト１対応：特定のファイルをピックアップして表示するリストに対応。１ではjsonで定義する。

## 2016年5月25日
V-HD1対応：配信、動画の切り替えにHDMIミキサー「V-HD1」に対応  
曲差し替え対応：一度予約した曲の差し替えに対応。差し替え時コメントを予約者などは変わらない  
接続情報QRコード対応：接続URLのQRコードとして表示  
リクエスト時順番ピッタリ移動：予約時に一番後ろではなく、今までの順番を考慮した順番に登録されるようになる  
検索の高速化：検索ヒット件数が多い場合でもすぐに結果を表示  
予約一覧ダウンロードSJIS対応：Excelでそのまま読めるようにSJISでダウンロードするボタンを追加

## 2016年2月29日
BGVモード：曲中BGVとして持ち込みカラオケを使う場合の動作設定  
動画ファイル最大容量を設定：検索される動画の最大サイズを指定  
カラオケ配信用プレビューキャプチャソフト：カラオケ配信用プレビューキャプチャソフトにMPC-BE以外のアプリを指定可能に  
リクエスト一覧画面をシンプル化  
anison.info検索、検索結果を詳細化

## 2016年1月12日
ニコニコ動画ダウンロード予約：ニコニコ動画をダウンロードして曲予約  
複数部屋対応：複数の部屋で開催時に別部屋へのリンク  
コメントサーバーローカル化：インターネット接続なしでコメント機能を使用可能に  
バージョン番号表示：Help等のメニュー内にバージョン番号を表示  
オンラインアップデート画面：画面操作でオンラインアップデート可能に(要直リンク)

## 2015年12月7日
曲中ジャンプ：MPCで再生中の曲をだいたい5秒もしくは20秒前後にジャンプ可能に  
オンラインアップデート対応：コマンドプロンプトからのコマンド実行でオンラインアップデートを実行可能に

## 2015年11月19日
アップロード予約：手元の端末から動画をアップロードして予約可能に。  
予約削除時の確認ダイアログ：削除時に確認画面表示  
リクエスト者名入力必須化：名前を入れずにリクエストできないようにする設定追加  
予約一覧の自動更新：予約一覧の自動更新するチェックボックスおよび再生中の曲の部分に自動的に移動するチェックボックス追加


## 2015年10月20日
Webサーバーをnginxからxamppに切り替え→動作安定化  
インストーラー対応  
上部メニュー表示：機能ごとにページを分離  
URLリクエスト：yourubeや動画を直接アクセスできるURLでリクエストする。  
自動再生開始＆停止onWeb:Web画面上から自動再生を開始＆停止可能に。  
anisoninfo検索の表示変更：検索中に表示される作品名、ブランド名、人物名の詳細を確認できるリンク追加。複数の曲が見つかった場合検索結果を同じページに表示していたものを曲ごとに別ページで表示


## 2015年8月27日
カラオケ配信自動切替：「カラオケ配信曲を歌う」とリクエストすると、その番で自動的に映像と音声がカラオケ配信に切り替わる。  
ニコ生風コメント表示：リクエスト時のコメントを曲再生開始時に、コメント画面からのコメントは即時、画面にニコ生風に表示する。  
機材係のおすすめ順：キーワードを指定して検索結果に優先的に表示させる。

## 2015年6月29日
DDNS登録(mydns＆pcgame-r18.jp): 参加者が毎回同じURLでアクセスできるようにIPをDDNSに登録する機能を追加。  
コメント編集＆レス付け：リクエスト時に入力したコメントを編集できるようにした。また人のコメントにレスを付けることができるようにした。  
表のソート機能：予約一覧と曲の検索結果一覧を表示後に並び替えることができるようにした。  
Tweet機能：うたった人と曲名を入れたTwitterの投稿画面に飛ぶリンクを追加

## 2015年6月10日
曲検索依頼：曲を見つけられなかったとき誰かに検索方法を聞く画面  
検索履歴保存機能：検索ワードで使用された文字を保存しておく。機材係が後で検索したけどなかったと思われる曲を確認するため。

## 2015年5月19日
管理者ログイン：設定画面に入るときにidだけ入力するダイアログ表示。  
割り込みリクエスト：曲を次に再生するところまで移動する「次に再生」ボタンを追加(配信カラオケにある機能実装)  
シークレット予約：曲再生まで曲名を表示しないようにリクエスト可能(カラ鉄にあった機能を実装)


## 2015年 4月22日
anison.info 連携検索に対応  
BGMモード実装  
リクエストリストのエクスポート、インポート対応


## 2015年 4月6日
スマホ用画面デザイン作成  
mp3＋カラオケタイムタグファイル形式に対応。foobar2000で再生。

## 2015年 3月
スタイルシート分離(デザインをスタイルシートの変更で変えることができるようになった)  
動作安定化(時々Webサーバが落ちてしまうことがあったのでダウンしたら自動的に再起動するようにした)

## 2015年 2月
自動再生機能実装。  
プレイヤーコントローラ実装（手元の端末から音量、OnOffボーカル切り替え、曲終了などの再生中のプレイヤー操作を可能にした)  
banditの隠れ家と連携して、歌手名、作品名、ブランド名から曲検索できる仕組みを作成(エロゲソング専用)

## 2015年 1月
GitHubにsource公開。
「エロゲソングが好き」コミュニティのカラオケオフで使用。自分主催以外のカラオケオフでの初デビュー。

## 2014年12月
ファイル検索をWeb画面の機能の中に搭載。これによりノートPCだけでなく、スマホとかWifiアクセスできる端末のブラウザで曲検索と予約リクエストが行えるようになった。自動再生機能はまだない。

## 2014年11月
「Everything file Search」で見つけたファイルをドラッグ＆ドロップして曲リクエストをするWeb画面を作成。機材係とは別の参加者のWindowsノートPCで曲検索からリクエストまでできるようになった。この時点ではまだ、自動的に再生する機能はないので、リクエストされた画面を見て機材係が手動でプレイリストに登録する仕組み。  
ここまで既存ソフトを使って何とかしていたところ。

## 2014年10月
曲ファイル検索に「Everything file Search」を使用するようにした。リモート検索機能で手元のWindowsノートPCにインストールした「Everything file Search」で曲ファイル検索ができるようになった。（が、誰も使ってくれなかった(笑)）


## 2011年ごろ
参加者が各自USBメモリなどでカラオケ動画を持ち寄って機材PCに接続して、機材のPCを操作して曲ファイルを検索して動画プレイヤーのプレイリストに追加するという持ち込みカラオケリクエストの仕組みが確立する。この時点では機材PCを操作しているときしか曲の検索ができないので、もっとゆっくり探せるようにできないかと機材係的に思っていた。


上のロゴは
https://aratama.github.io/kemonogen/
を使用して、「楽シャア」さんが作ってくれました。
