# ゆかり API 仕様書

モバイルアプリ等の Web UI 以外のクライアントから「ゆかり」を操作するための API 仕様。

- `/api/` 配下は本仕様のために追加された JSON ファサード層
- 一部は既存の JSON エンドポイント（`/api/` 外）をそのまま使用する
- 動作リファレンス実装: [`/v2/`](../v2/) （純粋な API クライアントとして実装された静的フロントエンド）

## 共通仕様

### ベース URL

ゆかり本体のルート（例: `http://localhost/`）。`/api/xxx.php` と表記されているものはルート直下の `api` ディレクトリ、それ以外はルート直下のファイル。

### リクエスト

- `/api/` 配下と `exec.php` はメソッド **GET / POST どちらも可**（パラメータは `$_REQUEST` で受理）。
  例外: `mypage_api.php` は GET のみ、`requestlist_reorder.php` は POST (JSON ボディ)
- 文字コードは UTF-8。POST は `application/x-www-form-urlencoded`

### 応答エンベロープ（/api/ 配下のみ）

```json
// 成功
{ "ok": true,  "data": { ... } }
// 失敗
{ "ok": false, "error": "エラーメッセージ" }
```

| HTTP | 意味 |
|------|------|
| 200 | 成功 |
| 400 | パラメータ不正 |
| 404 | 対象が存在しない |
| 500 | サーバー内部エラー (DB等) |
| 501 | そのプレイヤーでは非対応の操作 |
| 502 | プレイヤー実機に到達できない |

`/api/` 外の既存エンドポイント（`requestlist_swipe_json.php` 等）はエンベロープなしの素の JSON を返す。

### 認証

既存の easyauth（`useeasyauth=1` 時のみ）に準拠。

- Cookie `YkariEasyPass` または クエリ `easypass=<認証キーワード>` を付与
- `localhost` からのアクセスは認証不要
- 例外: `/api/server_info.php` のみ認証前に呼べる (接続設定画面用)
- トークン認証は将来課題

### nowplaying（再生状況）の値

DB 上は日本語文字列。API 入力は数値も受理する（出力は日本語文字列のまま）。

| 数値 | 文字列 |
|------|--------|
| 1 | 未再生 |
| 2 | 再生中 |
| 3 | 停止中 |
| 4 | 再生済 |
| 5 | 再生済？ |
| 6 | 再生開始待ち |
| 7 | 変更中 |

---

## 取得系

### GET /api/server_info.php — サーバー情報 (認証不要)

接続設定画面が「接続先がゆかりか」「easyauth が必要か」を判定するためのエンドポイント。
唯一 easyauth の認証前に呼べる。機微情報は含まない。

```json
{ "ok":true, "data": { "app":"yukari", "version":"v0.10.0-beta", "easyauth_required":false } }
```

### GET /api/capabilities.php — 機能フラグ

アプリ起動時に呼び、UI の出し分けに使う。機微情報（パスワード類）は含まれない。

```json
{ "ok": true, "data": {
  "features": {
    "mypage": true, "bingo": false, "keychange": false, "secret": true,
    "bgv": false, "userpause": false, "haishin": true, "nonamerequest": false,
    "otherplayer": false, "google_sync": false, "easyauth": false,
    "new_request_list": true, "new_search_ui": true
  },
  "player":  { "mode": 3, "autoplay": false },
  "request": { "noname_username": "名無しさん", "otherplayer_disc": "別プレイヤー再生" },
  "server":  { "room_name": "101",
               "rooms": [ { "name": "101", "url": "http://192.168.1.10/" },
                          { "name": "102", "url": "http://192.168.1.11/" } ] }
}}
```

`player.mode`: 1=MPC-BE / 2=foobar2000 / 3=自動 / 4=その他

`server.room_name`: Web 版で「〇〇部屋」と表示される部屋名（別部屋 URL 設定の先頭の部屋番号）。未設定時は空文字（アプリ側は接続先 URL などを表示する）。

`server.rooms`: 移動できる部屋の一覧。Web 版の部屋ドロップダウンと同じ条件（URL が設定されていて「表示する」チェックが付いたもののみ）。未設定時は空配列。

### GET /api/search.php — 曲検索（生データ）

| パラメータ | 必須 | 説明 |
|---|---|---|
| `keyword` | ○ | 検索ワード |
| `order` | - | Everything 用ソート指定 (既定: `sort=size&ascending=0`) |
| `path` | - | 検索対象パス絞り込み |

```json
{ "ok": true, "data": {
  "keyword": "...", "total": 1600, "count": 1585,
  "items": [
    { "name": "曲名.mp4", "path": "W:\\folder", "fullpath": "W:\\folder\\曲名.mp4",
      "size": 835429538, "priority": 50 }
  ]
}}
```

- `priority` はおすすめ度 (prioritydb による重み。既定 50)
- サイズ 1 バイト以下（フォルダ等）は除外済み

### GET /api/lister_index.php — ListerDB 期別インデックス・完全一致検索

モバイルアプリの「期別リスト」(年 → 期 → 作品 → 曲) と、検索結果からの再検索
（作品名 / 歌手名 / シリーズ名 / 動画制作者の完全一致）が使う。
期の判別は `t_found.song_release_date`（修正ユリウス日。ゆかりすたーがタイアップの
リリース日を優先して書き込むマージ値）。一般向けのみ対象（tie_up_age_limit < 18）。
ListerDB 未設定時は 503。

| mode | パラメータ | 応答 (data) |
|---|---|---|
| `years` | - | `{ "years":[{"year":2026,"songs":471},...], "no_date":N }` |
| `quarters` | `year` | `{ "year":2026, "quarters":[{"q":2,"label":"4月〜6月：春","songs":192,"programs":43},...] }` |
| `programs` | `year`, `quarter` (1〜4) | `{ "year","quarter","label", "programs":[{"program":"作品名","group":"シリーズ名 or null","songs":5},...] }` |
| `songs` | `program` / `artist` / `group` / `worker` のいずれか（複数は AND、完全一致） | `{ "total":N, "items":[{ song_name, song_ruby, song_artist, program_name, tie_up_group_name, song_op_ed, found_worker, found_path, found_file_size, found_comment },...] }` (最大300件) |

### GET /api/requests.php — 予約一覧（エンベロープ版）

下記 `requestlist_swipe_json.php` と同一のデータを `{ "ok":true, "data": {...} }` に包んで返す。
パラメータも同じ (`limit` / `offset`)。モバイルアプリのポーリングはこちらを推奨。

### GET /requestlist_swipe_json.php — 予約一覧（既存）

| パラメータ | 必須 | 説明 |
|---|---|---|
| `limit` / `offset` | - | ページング (省略時は全件) |

応答（素の JSON、エンベロープなし）:

```json
{ "items": [ { "id": 1, "reqorder": 3, "songfile": "...", "display_name": "...",
    "song_name": "", "lister_artist": "", "lister_work": "", "lister_op_ed": "",
    "lister_comment": "", "singer": "...", "comment": "", "kind": "動画",
    "nowplaying": "未再生", "track": 0, "keychange": 0, "audiodelay": 0,
    "duration": 245, "volume": -1, "position": 3 } ],
  "total": 3, "has_more": false,
  "remaining_count": 2, "remaining_seconds": 483 }
```

- シークレット予約は `display_name` が伏せ字テキストになる
- `fullpath` は含まれない → 必要なら下記の1件詳細を使う

### GET /change.php?format=json&id=N — 予約1件詳細

`requesttable` の全カラム（`fullpath` 含む）をそのまま返す。数値カラムは int 型。
`format=json` を付けない場合は従来の HTML 編集画面（変更なし）。

```json
{ "id": 6, "songfile": "...", "singer": "...", "comment": "", "kind": "動画",
  "reqorder": 2, "fullpath": "W:\\...", "nowplaying": "未再生", "status": "OK",
  "clientip": "::1", "clientua": "...", "playtimes": 0, "secret": 0, "loop": 0,
  "keychange": 0, "track": 0, "pause": 0, "audiodelay": 0, "duration": 0,
  "volume": 0, "song_name": null, "lister_artist": null, ... }
```

エラー: 400 `{"error":"invalid id"}` / 404 `{"error":"not found"}` （エンベロープなし・旧形式）

### GET /api/nowplaying.php — 再生状態（エンベロープ版）

下記 `get_playingstatus_json.php` と同じ内容を**常に JSON で**返す（空応答の特別扱いが不要）。

```json
// 停止中
{ "ok":true, "data": { "playing": false } }
// 再生中
{ "ok":true, "data": { "playing": true,
  "status":"...", "playtime":N, "totaltime":N, "playtime_txt":"...", "totaltime_txt":"...",
  "playingtitle":"...", "playingfile":"...", "playingsinger":"...",
  "player":"mpc|foobar|none", "keychange":N,
  "nextsong": { "title":"..", "songfile":"..", "show_file":bool, "singer":"..", "kind":".." } } }
```

`status` は MPC の状態番号 (文字列)。`"2"`=再生中 / `"1"`=一時停止。
`keychange` は再生中の曲の現在キー (半音、キー変更操作時に DB へ反映された値)。

### GET /get_playingstatus_json.php — 再生状態（既存）

再生位置・曲名・次曲情報 (`nextsong`) を返す。
**注意: プレイヤー停止中は空応答（空文字）を返す**。クライアントは「空 = 停止中」として扱うこと。

### その他の既存取得系

| エンドポイント | 内容 |
|---|---|
| `getsingerlist_json.php` | 歌う人一覧 (`singerme` で自分判定) |
| `gettracklist_json.php` | 音声トラック一覧 |
| `search_listerdb_*_json.php` | ListerDB (アニソンDB) 検索各種 |
| `player_event.php` / `requestlist_event.php` | SSE (Server-Sent Events) によるリアルタイム更新通知 |

---

## 予約操作系

### POST /exec.php — リクエスト投稿（既存）

ヘッダ `X-Requested-With: XMLHttpRequest` を付けると JSON `{"newid": N}` を返す（先頭に改行が付くため trim 推奨）。

| パラメータ | 必須 | 説明 |
|---|---|---|
| `filename` | ○ | 表示ファイル名 (2048文字まで) |
| `fullpath` | ○ | フルパス (検索結果の `fullpath` を渡す) |
| `freesinger` | ○ | 歌う人の名前 (256文字まで。`singer` より優先) |
| `singer` | - | 歌う人 (プルダウン選択値、256文字まで) |
| `comment` | - | コメント (512文字まで) |
| `kind` | ○ | `動画` / `カラオケ配信` / `小休止` / `URL指定` / `動画_別プ` のみ |
| `secret` | - | 1 = シークレット予約 |
| `keychange` / `track` / `pause` / `audiodelay` / `duration` / `volume` | - | 各オプション |
| `selectid` | - | 指定すると既存予約 id の差し替え |

補足: 「もう一度リクエスト」は保存済み `songfile` で `/api/search.php` を再検索し、
ヒットした現在の `fullpath` を使って投稿する（フォルダ移動に強い方式）。

### GET /api/request_delete.php — 予約削除

| パラメータ | 必須 |
|---|---|
| `id` | ○ |

`{ "ok": true, "data": { "id": 7, "deleted": true } }`

### GET /api/request_move.php — 個別移動

| パラメータ | 必須 | 説明 |
|---|---|---|
| `id` | ○ | |
| `action` | ○ | `up` / `down` / `warikomi` (次に再生) |

`{ "ok": true, "data": { "id": 6, "action": "up", "message": "" } }`

`message` に「すでに一番上です。」等の情報メッセージが入ることがある（空 = 正常移動）。

### GET /api/playstatus.php — 再生状況変更

| パラメータ | 必須 | 説明 |
|---|---|---|
| `id` | ○ | |
| `nowplaying` | ○ | 数値 1〜7 または日本語文字列 (上の対応表参照) |

`{ "ok": true, "data": { "id": 5, "nowplaying": "再生済" } }`

### POST /requestlist_reorder.php — 全順序一括並び替え（既存）

Body: `{"ids": [3, 1, 2]}` (JSON、表示順 = 先頭が一番上)。未再生のみ並び替え対象。
応答: `{"status":"ok"}`

---

## プレイヤー制御

### GET /api/player.php — 統一プレイヤー制御

MPC-BE / foobar2000 の差を吸収する。プレイヤーは再生中の曲から自動判定
（`playerctrl_portal.php` と同じ）。`player=mpc|foobar` で明示上書き可。

| パラメータ | 必須 | 説明 |
|---|---|---|
| `action` | ○ | 下表参照 |
| `player` | - | `mpc` / `foobar` (省略時は自動判定) |
| `value` | `volume_set`, `command` 時 | 音量 0〜100 / wm_command 番号 |
| `key` | `keychange` 時 | キー変更コマンド (`up` / `down` / 数値) |
| `step` | audiodelay 時 | `100` で ±100ms (既定 ±10ms) |

| action | mpc | foobar | 説明 |
|---|:-:|:-:|---|
| `info` | ○ | ○ | プレイヤー種別・playmode を返す |
| `next` | ○ | ○ | 曲終了 (DB更新) |
| `start` | ○ | ○ | 曲開始 (再生開始待ち→再生中) |
| `play` | ○ | ○ | 再生 (非トグル) |
| `pause` | ○ | - | 一時停止 (非トグル) |
| `playpause` | ○ | ○ | 再生/一時停止トグル |
| `stop` | ○ | - | プレイヤー停止 (DBは触らない) |
| `start_first` | ○ | ○ | 曲頭から再生し直す |
| `seek_back` / `seek_forward` | ○ | - | シーク (中ジャンプ) |
| `seek_back_large` / `seek_forward_large` | ○ | - | シーク (大ジャンプ) |
| `volume_get` | ○ | - | 現在音量取得 → `data.volume` |
| `volume_set` | ○ | - | 音量設定 (value=0〜100) |
| `volume_up` / `volume_down` | ○ | ○ | 音量 ±5 (mpc は `data.volume` で新値を返す) |
| `volume_reset` | ○ | - | 曲開始時の初期音量に戻す (startvolume + 制作者別オフセット) → `data.volume` |
| `mute` | ○ | - | ミュートトグル |
| `fadeout` | ○ | - | フェードアウト |
| `keychange` | ○ | - | キー変更 |
| `audiodelay_up` / `audiodelay_down` | ○ | - | 音ズレ補正 ±10ms (step=100 で ±100ms) |
| `audiotrack_next` | ○ | - | 音声トラック切替 |
| `subtitle_toggle` | ○ | - | 字幕 ON/OFF |
| `fullscreen` / `d3d_fullscreen` | ○ | - | フルスクリーン / D3Dフルスクリーン |
| `speed_down` / `speed_normal` / `speed_up` | ○ | - | 再生スピード |
| `size_small` / `size_normal` / `size_large` | ○ | - | 表示サイズ |
| `mirror` / `show_time` | ○ | - | 左右反転 / 時刻表示 |
| `comp_get` / `comp_up` / `comp_down` / `comp_reset` | ○ | - | 字幕補正 (白飛び対策) → `data.comp_level` |
| `command` | ○ | - | 汎用 wm_command 送出 (value=番号)。名前付きにない操作の逃がし |

非対応の組み合わせは 501 を返す。

応答例:

```json
{ "ok": true, "data": { "player": "mpc", "action": "volume_get", "message": "", "volume": 76 } }
{ "ok": true, "data": { "player": "mpc", "detected": "mpc", "playmode": 4 } }   // info
```

### 字幕補正（明るさ/コントラスト/彩度）

`action=comp_*` で操作できる (上表)。実装は `function_playeradjust.php` を
Web 版 (`mpcctrl_bs5.php?cmd=comp_*` → `{"level": N}`) と共用しており、
レベルは `player_compensation.json` に永続化される。

---

## マイページ（既存 API）

`usemypage=1` 時のみ。ユーザー識別は Cookie `YkariUserID` (UUID)。

```
GET /mypage_api.php?action=<action>&...
→ {"status":"added"|"removed"|"error", "message":"..."}
```

| action | パラメータ |
|---|---|
| `add_later` / `remove_later` | `fullpath`, `songfile`, `kind` |
| `add_favorite_song` / `remove_favorite_song` | 同上 |
| `add_favorite_keyword` | `keyword`, `search_type`, `search_params` |
| `remove_favorite_keyword` | `kw_id` |

---

## マイページ（アプリ連携 API）

```
GET/POST /api/mypage.php?action=<action>&userid=<UUID>&...
→ {"ok":true, "data":{...}} / {"ok":false, "error":"..."}
```

`usemypage=1` 時のみ (無効時は 503)。Web 版が Cookie `YkariUserID` で識別するのに対し、
アプリはクエリ/POST の `userid` で識別する。**userid (UUID) を知っていること自体が認可**
(Web の cookie と同じモデル)。

### デバイスリンク

| action | パラメータ | 応答 data |
|---|---|---|
| `pair_apply` | `code` (Web のデバイスリンクで発行した6文字) | `{userid}`。コードは消費される。無効/期限切れは 404 |
| `pair_generate` | `userid` | `{code}` (5分有効) |

### データ読み書き

| action | パラメータ | 応答 data |
|---|---|---|
| `summary` | `userid` | 表示名・各リスト件数・`google_linked` |
| `history` | `userid`, `sort`, `order` | `{items:[{fullpath,songfile,kind,times,last_requested_at}]}` |
| `history_add` / `history_remove` | `fullpath` (+`songfile`,`kind`) | — |
| `later` / `favorite` | `userid` | `{items:[{fullpath,songfile,kind,added_at}]}` |
| `later_add` / `later_remove` / `favorite_add` / `favorite_remove` | `fullpath` (+`songfile`,`kind`) | — |
| `keyword` | `userid` | `{items:[{id,keyword,search_type,search_params,added_at}]}` |
| `keyword_add` | `keyword`, `search_type`, `search_params` | — |
| `keyword_remove` | `id` **または** `keyword`+`search_type`+`search_params` (条件一致) | — |
| `import` | `data` (POST。Web 版エクスポート形式 version 1 の JSON) | `{counts}`。マージ取り込みで冪等 (履歴は fullpath+日時、他は完全一致の重複をスキップ) |

書き込み系アクションの成功時は、Google 連携済み + 自動同期オンなら Drive へも自動保存する
(Web 版 `mypage_api.php` と同じ挙動。同期失敗しても書き込みの応答は成功のまま)。

### Google 同期

| action | パラメータ | 応答 data / エラー |
|---|---|---|
| `google_status` | `userid` | `{linked, email, auto_sync, last_synced_at}` |
| `google_sync` | `userid`, `direction=to_drive\|from_drive` | `{synced, direction}`。未設定 503 / 未連携 404 / Drive 失敗 502 |
| `google_token_get` | `userid` | トークン一式 + 発行元 `client_id` (アプリの「同期の持ち歩き」用)。未連携 404 |
| `google_register` | POST: `google_sub`, `google_email`, `access_token`, `refresh_token`, `token_expires_at`, `client_id` | この部屋にその Google アカウントのユーザーを用意し (既存は `google_sub` で再利用)、Drive から復元して `{userid, access_token, token_expires_at, refreshed}` を返す |

`google_register` のエラー: Google 同期未設定の部屋は **503** (アプリは静かにスキップ)、
`client_id` がこの部屋の設定と不一致は **409** (別の Google 連携設定 = 持ち歩き対象外)、
トークン無効 (読めず・更新できず・期限切れ) は **401** (アプリは持ち歩きを破棄して再連携を促す)。

---

## 既知の注意点

- `exec.php` の XHR 応答は先頭に改行を含む → パース前に trim すること
- `exec.php` は不正な `kind` や文字数制限超過を `die()` で拒否するため **HTTP 200 + 空応答**になる → `newid` が取れなければ失敗として扱うこと
- `get_playingstatus_json.php` はプレイヤー停止中に空応答 → 「空 = 停止中」として扱う
- CORS ヘッダは未対応 (同一オリジンまたはネイティブアプリの HTTP クライアントから利用する想定)
- `nowplaying` の出力は日本語文字列 (数値コード化は保留中の将来課題)
