# ゆかナビ 設計書 (v0.2)

「ゆかり」(KaraokeRequestorWeb) のネイティブアプリ版**「ゆかナビ」**。名前はカラオケ機の端末(キョクナビ)に
由来し、「曲を探してナビゲートする端末」を表す。既存の `/api/` JSON ファサード層をバックエンドとして使い、
Android / Windows / iOS で共通のリッチな UI を提供する(Android / Windows 先行、iOS は M4)。

- 検証済みの API 面: `/v2/`(純粋 API クライアントの静的ページ)で動くこと = アプリで実現できること
- API の正確な入出力は **API 仕様書 [api/README.md](api/README.md)** を参照
- 本書はアプリ側の設計と、サーバー側(このリポジトリ)に追加が必要な作業の両方を扱う

---

## 1. 目的と方針

| 項目 | 方針 |
|------|------|
| 対象プラットフォーム | Android / Windows 先行、iOS は M4 で追加(1コードベース) |
| 見た目 | ソシャゲ品質のリッチなホーム画面(リンクラ/プリコネ参考) |
| 操作動線 | カラオケ機の端末(デンモク/キョクナビ)参考。ただしメイン検索は**キーワード検索** |
| キャラクター | デフォルトはマスコットの「ゆかり」ちゃん(`images/マスコット/`)。Live2D・動画・画像でユーザーカスタマイズ可能 |
| サーバー | 既存のゆかり(PHP)。アプリは純粋な API クライアントで、サーバー改修は最小限 |
| 開発方針 | 小さく作って段階的に。MVP は既存 API だけで成立させる(→ §8 マイルストーン) |

## 2. 技術選定

**採用: Unity 6(最新 LTS 系)**

| 観点 | Unity | Flutter | Godot | .NET MAUI |
|------|-------|---------|-------|-----------|
| Live2D 公式 SDK | ◎ Cubism SDK for Unity | ✕ | △ 非公式 (GDCubism) | ✕ |
| リッチ演出(パーティクル・Tween・シェーダー) | ◎ | ○ | ○ | △ |
| Android / iOS / Windows | ◎ | ◎ | ○ | ○ |
| 動画背景再生 | ◎ VideoPlayer 内蔵 | ○ プラグイン | △ | △ |
| 情報量・アセットストア | ◎ | ○ | △ | △ |

**決定打は Live2D**。「背景やキャラに Live2D を置ける」要件を公式サポートで満たせるのは実質 Unity のみ。
要件の「Unity のような」もそのまま Unity 採用で解決する。

### Unity 内の主要ライブラリ

| 用途 | 採用 |
|------|------|
| UI | UGUI + DOTween(演出 Tween)。ソシャゲ風 UI の実績が最も厚い |
| JSON | Newtonsoft Json.NET(Unity 公式パッケージ `com.unity.nuget.newtonsoft-json`) |
| HTTP | UnityWebRequest(標準)。薄い ApiClient クラスでラップ |
| Live2D | Cubism SDK for Unity |
| 動画 | VideoPlayer(標準) |
| QR 読み取り | ZXing.Net(接続設定用) |

> **Live2D SDK ライセンス注意**: Cubism SDK は再配布不可。Unity プロジェクトのリポジトリには含めず、
> 各自ダウンロードして導入する運用(README に手順記載)。個人・小規模(売上1000万円未満)は出版許諾契約が無償なので、
> ゆかりの配布形態では問題なし。

### リポジトリ

Unity プロジェクトは**別リポジトリ**(例: `YukaNavi`)。本リポジトリ(PHP サーバー)には
API 追加とドキュメントのみが入る。

## 3. 全体アーキテクチャ

```
[Unity アプリ]                          [ゆかりサーバー (XAMPP)]
 ホーム/検索/予約/リモコン  ── HTTP ──▶  /api/*.php (JSON エンベロープ {ok, data|error})
 ApiClient (UnityWebRequest)            既存 JSON (requestlist_swipe_json.php ほか)
 ポーリング (2〜3秒間隔)                 exec.php (XHR モード)
 スキン読み込み (persistentDataPath)     SQLite request.db
```

- **MVP はポーリング**(予約一覧+再生中情報を数秒間隔で取得)。サーバーには既存の SSE
  (`player_event.php` / `requestlist_event.php`)による更新通知があるため、M2 以降で受信して
  ポーリングを削減できる(Unity 側は UnityWebRequest のストリーミング受信で対応)。
- **接続設定**: サーバー URL を初回起動時に設定。手入力に加えて **QR コード読み取り**
  (サーバー側は既存の `qrcode_php/` で部屋 URL の QR を表示済み → それを読むだけ)。
- **認証**: easyauth 有効時はパスワード入力 → Cookie `YkariEasyPass` を UnityWebRequest で保持。
  `_common.php` に予告されているトークン認証への移行は Phase 3。
- **ユーザー識別**: Web 版と同じく `YkariUsername` / `YkariUserID` Cookie 相当の値をアプリ内に保存して送出。
  これにより Web 版とマイページデータを共有できる。

## 4. 画面設計

### 4.1 画面一覧と遷移

```
[初回のみ] 接続設定 (URL入力 / QRスキャン / easyauthパスワード)
     │
     ▼
┌─ ホーム (リンクラ風) ─────────────────────────┐
│  背景(画像/動画/Live2D) + マスコット            │
│  上部: 接続先バッジ / 再生中の曲ティッカー       │
│  下部: メインメニュー                           │
└──┬──────┬──────┬──────┬──────┬──┘
   ▼      ▼      ▼      ▼      ▼
  検索   予約一覧  リモコン  マイページ  設定
   │                        (Phase 3)
   ▼
  検索結果リスト
   │ タップ
   ▼
  予約確認 (デンモク風モーダル: 名前/コメント/オプション)
   │ 「予約する」大ボタン
   ▼
  予約完了演出 (マスコットがリアクション) → 予約一覧 or 検索に戻る
```

### 4.2 ホーム画面(リンクラ/プリコネ参考)

- 画面全体に背景(デフォルト: 静止画 + パーティクル演出。カスタム: 画像/動画/Live2D)
- マスコットキャラを中央〜右に配置。**タップでリアクション**(セリフ吹き出し + アニメ)
- 下部に横並びの大きめメニューボタン(検索 / 予約一覧 / リモコン / マイページ / 設定)
- 上部ステータスバー: 接続先の部屋名、現在再生中の曲がスクロール表示(ティッカー)
- 自分の予約の順番が近づいたら「そろそろ出番!」通知バナー

### 4.3 検索(デンモク参考 + キーワード検索メイン)

- 画面上部に大きな検索ボックス(デンモクの「あいまい検索」に相当する位置づけをメイン動線に)
- 検索履歴・お気に入りキーワードをチップ表示(Phase 3 でマイページ API と連携)
- 結果はカード式リスト(曲名 / パス / サイズ)。タップで予約確認モーダル
- 予約確認モーダル: 曲名確認 + 名前(前回値を記憶)+ コメント + オプション
  (キー変更・シークレット等は `capabilities` の機能フラグで出し分け)
- 予約成功でマスコットの完了演出(デンモクの「予約しました」画面のオマージュ)

### 4.4 予約一覧

- 再生中の曲をハイライト、以降の待ち行列をカード表示
- 自分の予約(`YkariUserID` 一致)にのみ操作ボタン: 削除 / 上へ / 下へ / 次に再生(warikomi)
- プルダウン更新 + 自動ポーリング

### 4.5 リモコン

- `/api/player.php` の action をそのまま UI 化(再生/一時停止/次へ/シーク/音量/キー変更…)
- foobar2000 非対応の action は `capabilities` + `player.mode` で自動的に非表示
- **全ユーザーがデフォルトで利用できる**。一般のカラオケ端末でも音量変更や演奏中の曲の
  再生し直しは誰でもできる、という慣習に合わせる
- 見せたくない運用者向けのサーバー側非表示フラグは将来対応(§5.2 の低優先 TODO)

## 5. API マッピング

### 5.1 既存でそのまま使えるもの

| アプリ機能 | エンドポイント | 備考 |
|-----------|---------------|------|
| 起動時の機能フラグ | `/api/capabilities.php` | UI 出し分けの基準 |
| キーワード検索 | `/api/search.php?keyword=` | 生データ (name/path/fullpath/size) |
| 予約投稿 | `exec.php` (XHR モード) | `X-Requested-With: XMLHttpRequest` + form POST → `{"newid":N}` |
| 予約一覧 | `requestlist_swipe_json.php` | エンベロープなし既存 JSON |
| 再生中情報 | `get_playingstatus_json.php` | 停止中は空文字を返す点に注意 |
| 予約1件詳細/変更 | `change.php?format=json&id=N` | `fullpath` 含む全カラムを返す |
| 予約の削除/移動 | `/api/request_delete.php` / `/api/request_move.php` | |
| 一括並び替え | `requestlist_reorder.php` | POST JSON `{"ids":[...]}`。未再生のみ対象(ドラッグ&ドロップ UI 用) |
| 再生状況変更 | `/api/playstatus.php` | |
| プレイヤー操作 | `/api/player.php` | MPC-BE / foobar2000 の差を吸収済み |
| マイページ操作 | `mypage_api.php?action=...` | あとで歌う/お気に入り曲/キーワードの追加・削除 |
| ListerDB 検索 | `search_listerdb_*_json.php` | アニソン DB 検索(8種の既存 JSON) |
| 歌う人/トラック一覧 | `getsingerlist_json.php` / `gettracklist_json.php` | 予約オプション UI 用 |
| 更新通知 (SSE) | `player_event.php` / `requestlist_event.php` | M2 以降でポーリング削減に活用 |

### 5.2 サーバー側に追加したいもの(このリポジトリの TODO)

| 優先 | エンドポイント案 | 内容 |
|------|-----------------|------|
| 高 | `/api/requests.php` | 予約一覧のエンベロープ版(requestlist_swipe_json の /api/ 化)。アプリのポーリング基点 |
| 高 | `/api/request_add.php` | 予約投稿のエンベロープ版(exec.php の XHR モードを内部利用 or 共通化) |
| 高 | `/api/nowplaying.php` | 再生中情報のエンベロープ版(空文字問題の解消込み) |
| 中 | `/api/server_info.php` | 部屋名・バージョン・easyauth 要否を**認証前に**返す(接続設定画面用) |
| 中 | QR 接続ペイロード | 既存 QR の URL に加え、アプリ用スキーム(例: `yukari://connect?url=...`)の QR を init.php に追加 |
| 低 | マイページ一覧取得 API | 追加・削除は `mypage_api.php` に既存。履歴・お気に入り等の「一覧を JSON で返す」取得系のみ未整備(Phase 3) |
| 低 | トークン認証 | `_common.php` に予告済みの easyauth 置き換え(Phase 3) |
| 低 | リモコン非表示フラグ | config.ini + `capabilities` に追加。リモコンを見せたくない運用者向け(既定は表示) |

※「代用可」の既存 JSON があるため、**高の3本がなくても MVP は開発開始できる**。
エンベロープ統一はアプリ側コードの簡素化のためであり、後追いで差し替え可能。

## 6. 背景・キャラクターのカスタマイズ設計

### 6.1 スキンフォルダ構成

`persistentDataPath/skins/<スキン名>/` にユーザーが手持ちファイルを置き、アプリ内設定で選択する。

```
skins/
└── mycustom/
    ├── skin.json          # マニフェスト(下記)
    ├── bg.mp4             # 背景動画 (または bg.png / Live2D 一式)
    └── character/         # Live2D モデル一式 (*.model3.json ほか) または PNG
```

```jsonc
// skin.json
{
  "name": "マイカスタム",
  "background": { "type": "video", "file": "bg.mp4" },        // image | video | live2d
  "character":  { "type": "live2d", "file": "character/chara.model3.json",
                  "scale": 1.0, "position": [0.6, 0.0] },     // image | live2d | none
  "theme": { "primary": "#7b5cd6", "accent": "#f0a5c0" }      // UI テーマ色 (任意)
}
```

- 背景とキャラは独立して差し替え可能(背景だけ動画、キャラはデフォルト等)
- Live2D はモーション(idle / tap リアクション)を model3.json の定義から自動再生
- 不正なファイルはデフォルトにフォールバック(起動不能を防ぐ)

### 6.2 デフォルト素材

マスコットの名前は **「ゆかり」ちゃん**。デフォルトスキンは彼女で統一する。

- キャラ: `images/マスコット/yukari_mascot_transparent.png`(透過版)を使用
- デフォルトでもリッチに見せる工夫(静止画でも「生きてる」感):
  - ゆっくり上下する浮遊 Tween + タップ時のスクイーズ&セリフ吹き出し
  - 背景に音符パーティクル + グラデーションアニメ(ゆかりちゃんの世界観に合わせた紫系)
- 将来: ゆかりちゃんの Live2D モデル化(M4 以降、外注 or 自作を別途判断)

#### 素材制作リスト(優先順・仕様付き)

| 優先 | 素材 | 仕様 | 使いどころ |
|------|------|------|-----------|
| 高 | 表情差分 PNG 2〜3種(笑顔・驚き・ウィンク等) | 透過 PNG。既存立ち絵と同ポーズ・同解像度(1024×1536)で顔だけ違うと理想(アプリ側でクロスフェード切替) | ホームのタップリアクション |
| 高 | 予約完了ポーズ 1枚(ガッツポーズ・マイクを掲げる等) | 透過 PNG。全身、既存立ち絵と同スケール | 予約完了演出 |
| 高 | ホーム用背景イラスト | 縦 1080×1920 基準。四辺に見切れてよい余白を持たせる(縦持ちスマホ/横持ち Windows 両対応のセーフエリア) | ホーム画面 |
| 中 | 「ゆかナビ」タイトルロゴ | 透過 PNG(横長) | スプラッシュ/ホーム上部 |
| 中 | アプリアイコン | 1024×1024 PNG(角丸なし・周囲に余白)。既存 `yukari_icon.png` の流用可否を M0 で確認 | OS ホーム画面/ストア |
| 中 | SE 2種(タップ音・予約完了ジングル) | wav / ogg、短尺(0.1〜2秒) | UI 操作音 |
| 低 | ホーム BGM(任意) | ogg、自然ループするもの | ホーム画面 |

## 7. アプリ内部構成(Unity)

```
Assets/
├── Scripts/
│   ├── Api/            # ApiClient, DTO 群 (capabilities, search, requests...)
│   ├── Core/           # 接続設定, ポーリングサービス, スキンローダー
│   ├── UI/             # 画面ごとの Presenter (Home, Search, Queue, Remote, Settings)
│   └── Character/      # マスコット制御 (静止画演出 / Live2D 切替の共通インターフェース)
├── Scenes/             # Boot(接続) → Main(単一シーン + 画面はプレハブ切替)
└── Prefabs/
```

- 画面は**単一シーン + パネル切替**(遷移演出を DOTween で統一、ソシャゲの標準構成)
- `ICharacterView` インターフェースで静止画/Live2D を差し替え可能に(§6 の type に対応)
- サーバー由来の機能フラグ (`capabilities`) は起動時に1回取得してシングルトンに保持

## 8. 開発マイルストーン

| フェーズ | 内容 | サーバー側作業 |
|---------|------|---------------|
| **M0: 技術検証** | Unity プロジェクト作成、API 疎通、Live2D サンプル表示、QR 読み取り、Android / Windows でビルド確認 | なし |
| **M1: MVP** | 接続設定 / キーワード検索 / 予約投稿 / 予約一覧(削除・移動込み)/ 静止画マスコット + 最小演出 | なし(既存 API で成立) |
| **M2: リッチ化** | ホーム画面完成(演出・ティッカー・通知バナー)、リモコン画面、エンベロープ版 API へ移行 | `/api/requests.php`, `request_add.php`, `nowplaying.php`, `server_info.php` |
| **M3: カスタマイズ + マイページ** | スキン機構(画像/動画/Live2D)、履歴・お気に入り、ListerDB 検索 | マイページ API, ListerDB API, トークン認証 |
| **M4: 磨き込み + iOS** | ゆかりちゃん Live2D 化、iOS 対応(Apple Developer Program 取得 → TestFlight 配布)、ストア対応判断 | — |

### 配布の見通し

- **Windows**: zip 配布(既存のゆかり配布と同梱も可)
- **Android**: APK 直配布から開始(Play ストアは審査・手数料を考えて後日判断)
- **iOS**: Apple Developer Program(年 $99)+ TestFlight から。取得自体は問題ないが、まず Android / Windows で全体が動くものを作る方針のため M4 扱い

## 9. 決定事項(2026-07-05)

1. **アプリ名**: 「ゆかナビ」。カラオケ機の端末(キョクナビ)に由来
2. **リモコン画面**: 全ユーザーがデフォルトで利用可能。一般のカラオケ端末で誰でも音量変更や
   再生し直しができるのと同じ考え方。サーバー側の非表示フラグは将来の低優先 TODO(§5.2)
3. **マスコット名**: 「ゆかり」ちゃん。追加素材は §6.2 の制作リストに沿って順次制作
4. **Unity バージョン**: 最新の Unity 6 を採用
5. **プラットフォーム順序**: Android / Windows 先行で全体を動くものにする。iOS(Developer
   Program 取得 + TestFlight)は M4
