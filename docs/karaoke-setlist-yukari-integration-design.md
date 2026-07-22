# karaoke_setlist 集計ビューのゆかり統合設計案

## 目的

公開ビューア `https://hachi515.github.io/karaoke_setlist/viewer.html` の「クール集計」「ランキング」を、ゆかりの BS5 画面に自然に取り込み、ゆかりすたーDB検索へつなげる。

今回の前提は設計のみ。マイリスト機能、歌唱者選択、機材係選択、`Karaoke Viewer` の見出しは移植対象外とする。

## 現状確認

公開ビューアは単一 HTML に CSS、集計データ、描画 JS、マイリスト処理が同居している。画面要素としてはタブ、検索欄、プルダウン、クール集計カード、ランキング、推移、マイリストがある。

ゆかり側は `search_bs5.php` が検索トップで、上部の予約方法タブは `commonfunc.php` の `build_reservation_tabs()` で生成している。BS5 画面のテーマ、背景、外観プリセットは `print_bs5_search_head()` / `print_bs5_head_core()` に集約されている。

ゆかりすたーDB検索は `search_listerdb_*_bs5.php` 系に分かれており、作品名・曲名・歌手名・ファイル名検索は既存の GET パラメータと画面遷移で成立している。新機能側で検索ルールを再実装せず、既存ページへリンクまたはフォーム送信するのが安全。

## 統合時の基本方針

- `search_bs5.php` 本体には集計ロジックを混ぜない。
- 新規ページは `setlist_cool_bs5.php` のように独立させる。
- 上部タブには「クール一覧」を追加し、既存の `build_reservation_tabs()` に最小差分で足す。
- 公開ビューアの見た目は CSS 変数へ寄せ、ゆかりの背景・ダークモード・外観プリセットと衝突しない形に落とし込む。
- 外部データ取得は必ずキャッシュ層を持ち、表示のたびに 7MB 級の HTML を解析しない。
- 集計カードからの検索導線は、ゆかりすたーDBの既存検索へ委譲する。

## 案A: 低リスク・リンク寄せ統合

概要: ゆかりに `setlist_cool_bs5.php` を追加し、公開ビューアの必要箇所を軽く整形して表示する。データは静的 JSON 化されたもの、または事前に切り出したローカルスナップショットを読む。

追加候補:

- `setlist_cool_bs5.php`
- `css/themes/setlist.css`
- `js/setlist_cool.js`
- `data/karaoke_setlist_snapshot.json` または `cache/karaoke_setlist/latest.json`

表示:

- 上部タブに「クール一覧」を追加
- ページ内タブは「クール集計」「ランキング」のみ
- 歌唱者・機材係・Karaoke Viewer・マイリストを削除
- 検索欄はゆかりすたーDB検索フォームとして扱う

検索導線:

- 作品カード: `search_listerdb_songlist_bs5.php?program_name=...`
- 曲行: `search_listerdb_filelist_bs5.php?program_name=...&song_name=...&song_artist=...`
- 検索ボックス: 既存の `search_listerdb_filelist_bs5.php` または `search_listerdb_songlist_bs5.php` に送る

メリット:

- ゆかり本体への変更が少ない
- 本家に出しやすい
- 失敗しても検索機能へ影響しにくい

リスク:

- 公開ビューア側のデータ形式が変わると追従が必要
- 自動更新をどうするかは別途決める必要がある

適用判断: 初回PRとして最も安全。まずはこの案を推奨。

## 案B: 中リスク・アダプタAPI方式

概要: 外部データを読むための小さなアダプタを PHP 側に置き、ゆかり用の正規化 JSON を返す。表示ページはその JSON だけを見る。

追加候補:

- `setlist_cool_bs5.php`
- `setlist_stats_json.php`
- `function_setlist_stats.php`
- `css/themes/setlist.css`
- `js/setlist_cool.js`
- `cache/karaoke_setlist/`

データ流れ:

1. `setlist_stats_json.php` がキャッシュを確認
2. キャッシュが新しければ即返却
3. 古ければ公開データを取得
4. `cool_groups`、`rankings`、`updated_at` へ正規化
5. 表示 JS は正規化済み JSON のみ描画

推奨データ契約:

```json
{
  "updated_at": "2026-07-07T00:00:00+09:00",
  "cool_groups": [
    {
      "cool": "2026春",
      "work": "作品名",
      "song_count": 12,
      "singer_count": 5,
      "songs": [
        {
          "song_name": "曲名",
          "song_artist": "歌手名",
          "song_op_ed": "OP",
          "request_count": 3
        }
      ]
    }
  ],
  "rankings": [
    {
      "rank": 1,
      "work": "作品名",
      "song_count": 20,
      "singer_count": 8
    }
  ]
}
```

メリット:

- 表示とデータ取得を分離できる
- 将来、公開ビューアが JSON を直接公開した場合に差し替えやすい
- キャッシュ・タイムアウト・フォールバックを一箇所に集約できる

リスク:

- PHP 側の外部通信とキャッシュ管理が増える
- HTMLからの抽出に頼る場合は壊れやすい

適用判断: 本家採用を狙うなら、公開側に JSON を出してもらえる前提でこの案が最もきれい。HTMLスクレイピング前提なら案Aよりリスクが上がる。

## 案C: 高リスク・ローカルDB取り込み方式

概要: karaoke_setlist の集計データをローカル SQLite に取り込み、ゆかりすたーDB `t_found` と照合して表示する。

追加候補:

- `setlist_import.php`
- `setlist_stats_json.php`
- `function_setlist_stats.php`
- `setlist_cool_bs5.php`
- SQLite テーブル `setlist_work_stats`, `setlist_song_stats`

できること:

- 作品名・曲名のゆかりすたーDB照合精度を上げる
- 検索結果カードに「ランキング入り」「クール集計あり」バッジを出せる
- オフラインでも直近データを使える

メリット:

- 将来性が高い
- 検索結果との融合が強い
- 表示速度を安定させやすい

リスク:

- DBマイグレーション、更新タイミング、照合ロジックが増える
- 作品名揺れ、曲名揺れ、歌手名表記揺れの吸収が必要
- 初回PRとしては大きすぎる

適用判断: まず案Aまたは案Bで画面を成立させ、利用価値が確認できた後の第2段階向け。

## 案D: 非推奨・ビューア丸ごと移植

概要: 公開ビューアの HTML/CSS/JS をゆかりに丸ごと移す。

メリット:

- 見た目の再現は最短

リスク:

- 7MB級の単一HTML構造を引き継ぐことになり、保守性が低い
- マイリストなど不要機能の削除が難しい
- ゆかりのテーマ、背景、認証、検索導線と衝突しやすい
- 本家PRとして通りにくい

適用判断: 採用しない。

## 推奨構成

初回は案Aと案Bの中間を推奨する。

- ページは `setlist_cool_bs5.php` として独立
- 表示 JS は正規化済み JSON だけを受け取る
- データ取得は当面ローカルスナップショットまたは軽量 JSON URL を前提にする
- 公開ビューアHTMLのスクレイピングは本実装に入れない
- 将来 `setlist_stats_json.php` を追加できるよう、JS側の入力形式を固定する

この形なら、最初のPRは「ページ追加 + タブ追加 + JSON表示」程度に収まり、後からデータ供給方法だけを差し替えられる。

## UI設計

### 上部タブ

`build_reservation_tabs()` に `cool` を追加する。

表示名候補:

- クール一覧
- 集計
- クール集計

推奨は「クール一覧」。検索/予約系タブの横に置いても意味が伝わりやすい。

### ページ内構成

1. ゆかり共通ナビ
2. 予約方法タブ
3. 検索ヒーロー
4. 集計タブ
5. クール集計カード
6. ランキング一覧

検索ヒーローは公開ビューアの `search-pill` の見た目をベースにしつつ、送信先はゆかりすたー検索にする。

不要なもの:

- Karaoke Viewer ロゴ
- 歌唱者選択
- 機材係選択
- CSV/同期/マイリスト関連
- GAS連携

### デザイン方針

公開ビューアのよい点:

- 余白が整ったカード
- 数値バッジが見やすい
- ランキング上位の視認性
- モバイルで横幅が破綻しにくい

ゆかりに合わせる点:

- Bootstrap 5 と既存CSS変数を使う
- `--bg-page`, `--bg-card-rgb`, `--bg-card-alpha`, `--color-text` 系に寄せる
- 外観プリセットが有効な場合はカード背景や枠線が自然に追従する
- Font Awesome 依存は避け、既存方針に合わせてSVGまたはBootstrap Icons相当の軽い表現にする

## ゆかりすたーDB検索との紐づけ

新機能側では検索ルールを持たない。クリックや検索フォームを既存ページへ渡す。

代表リンク:

```text
作品から探す:
search_listerdb_songlist_bs5.php?program_name={work}

曲から探す:
search_listerdb_filelist_bs5.php?program_name={work}&song_name={song_name}&song_artist={song_artist}

歌手から探す:
search_listerdb_column_list_bs5.php?target=song_artist&word={song_artist}
```

表記揺れ対策:

- 初期実装では部分一致検索に委譲
- 正規化JSONに `work_aliases` を追加できる余地を残す
- 高リスク案ではローカルDB取り込み時に照合テーブルを持つ

## 環境設定案

最小限なら設定なしでもよい。設定を入れる場合は以下に限定する。

- `use_setlist_cool`: クール一覧タブを表示する
- `setlist_stats_url`: 正規化JSONのURL
- `setlist_cache_ttl`: キャッシュ有効秒数

初回PRでは `use_setlist_cool` のみでもよい。URLやTTLはデータ取得方式が決まってから追加する。

## セキュリティ・安定性

- 外部HTMLを毎回PHPで解析しない
- 外部取得はタイムアウトを短くする
- キャッシュがあれば通信失敗時も表示する
- JSON schema を緩く検証し、壊れたデータは表示しない
- 表示文字列は必ず HTML エスケープする
- URL生成時は `rawurlencode()` を使う
- `connectinternet=0` の場合は外部取得せず、キャッシュのみ表示する

## 実装ステップ案

### Step 1: 設計・データ契約確定

- この設計をベースに採用案を決める
- 公開側で正規化JSONを出せるか確認する
- 画面に出す項目を「クール集計」「ランキング」に絞る

### Step 2: 低リスクPR

- `setlist_cool_bs5.php` を追加
- `css/themes/setlist.css` を追加
- `js/setlist_cool.js` を追加
- `build_reservation_tabs()` に `cool` を追加
- ローカルまたは固定URLの正規化JSONを描画

### Step 3: データ取得の自動化

- `setlist_stats_json.php` を追加
- キャッシュ読み書きを追加
- 通信失敗時のフォールバックを追加

### Step 4: 検索結果との融合

- 集計カードからゆかりすたーDB検索へ遷移
- 必要なら検索結果側に「ランキング情報あり」程度の軽いバッジを追加
- DB照合はこの段階まで持ち越す

## 採用判断まとめ

| 案 | リスク | 本家PR適性 | 実装量 | 推奨度 |
| --- | --- | --- | --- | --- |
| 案A: 独立ページ + 正規化JSON | 低 | 高 | 小 | 高 |
| 案B: アダプタAPI + キャッシュ | 中 | 高 | 中 | 高 |
| 案C: ローカルDB取り込み | 高 | 中 | 大 | 中 |
| 案D: 丸ごと移植 | 高 | 低 | 大 | 非推奨 |

初手は案Aで画面の価値を出し、データ供給を案Bへ進化させるのが最もスマート。案Cは検索結果への深い統合が必要になった段階で検討する。
