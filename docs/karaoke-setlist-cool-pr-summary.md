# クール一覧統合 Issue / PR メモ

## Issue title

クール一覧データを検索UI v2へ統合し、ゆかりすたー/Everything検索先を設定可能にする

## Issue body

### 背景

公開されている `karaoke_setlist/viewer.html` のクール集計・ランキング情報を、KaraokeRequestorWeb の検索UI v2から参照できるようにしたい。
既存の検索/予約導線を崩さず、ゆかりすたーDB検索に紐づく追加タブとして扱える形が望ましい。

### 対応内容

- 検索/予約タブに「クール一覧」を追加し、`setlist_cool_bs5.php` を新設。
- 公開viewer HTML内の `CATS` / `COOL_DATA` / `RANK_DATA` / `UPDATE_TS` を読み取り、`setlist_stats_json.php` でJSON配信。
- `function_setlist_stats.php` に取得・正規化・キャッシュ処理を集約し、画面側からはJSONを読むだけに分離。
- 環境設定に「クール一覧データ同期」ボタンを追加し、任意タイミングで最新HTMLから再取得できるようにした。
- 環境設定に「クール一覧の検索先」を追加し、クール一覧から開く検索先を `ゆかりすたー` / `Everything` で切り替え可能にした。
- 作成有無 `creation_count` は表示上のカウント集計対象外とし、歌唱数・人数・曲数を中心に表示。
- クール集計/ランキングのカードUIを追加し、各タブ・各作品はデフォルト折りたたみ。
- 曲リンクは作品名+曲名で検索し、検索用キーワードは括弧等を除去して既存viewer寄りの検索しやすい語に正規化。
- BS5の背景画像/カード透過設定に馴染むよう、CSSは `css/themes/setlist.css` に分離。
- スマホ表示で歌唱/人数/曲のメトリクスが折り返しにくいよう、クール集計・展開後曲一覧・ランキングを調整。
- キャッシュ保存先 `cache/` はオンライン更新やgit管理で巻き込まないよう `.gitignore` に追加。

### 本家PR向けの設計観点

- 既存の検索処理本体には手を入れず、クール一覧側は新規ページ+新規JSON API+専用CSS/JSで独立させている。
- データ取得は `function_setlist_stats.php` に集約しており、将来viewer側の構造変更や別データソース対応が必要になっても入口を差し替えやすい。
- 検索先切替は `setlist_search_backend` の設定値とJSONレスポンスで制御し、フロント側は値に応じてURL生成のみを切り替える。
- UIは既存の `print_bs5_search_head()` / `shownavigatioinbar_bs5()` / `build_reservation_tabs()` を使い、BS5ページの既存構成に合わせている。
- 既存の検索画面設定、背景/透過設定、EasyAuth/AdminAuth の流れを流用しており、独自の認証・設定保存機構は追加していない。

### 残リスク

- 公開viewer HTML内の定数名が変わるとパースできなくなるため、その場合は `function_setlist_stats.php` の抽出対象を更新する必要がある。
- Everything検索先は `search_bs5.php?searchword=...` へ遷移するため、Everything検索が有効な環境での利用が前提。

### 確認内容

- `php -l init.php`
- `php -l function_setlist_stats.php`
- `php -l setlist_stats_json.php`
- `php -l setlist_stats_sync.php`
- `php -l setlist_cool_bs5.php`
- `node --check js/setlist_cool.js`
- `http://localhost/setlist_cool_bs5.php` が `200` 応答
- `setlist_stats_json.php` に `search_backend` が含まれることを確認

## PR summary

検索UI v2にクール一覧ページを追加し、公開viewerのクール集計/ランキングをKaraokeRequestorWeb内で閲覧できるようにしました。
データ取得・キャッシュ・JSON配信・表示を分離し、既存の検索処理へ直接依存しすぎない構成にしています。

環境設定から最新データ同期を実行できるほか、クール一覧から曲を開く際の検索先を `ゆかりすたー` / `Everything` で切り替えられるようにしました。
