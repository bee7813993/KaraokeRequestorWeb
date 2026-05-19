# KaraokeRequestorWeb 開発メモ

## プロジェクト概要
PHP製カラオケリクエストWebアプリ。Bootstrap 3（レガシー）とBootstrap 5（新規）のページが混在。

## 主要な設計事項

### 背景画像機能
- 管理者が `init.php` から背景画像をアップロード（`images/bg/` に保存）
- 透過度は2軸：カード透過度 (`bg_card_opacity`) + 背景オーバーレイ透過度 (`bg_overlay_opacity`)
- BS5ページのみ対象。BS3ページは対象外
- 実装：`commonfunc.php` の `print_bg_style_block($is_bs5)` がインラインCSSを注入
- ダークモード対応：`body` に画像、`body::before` にオーバーレイ色を分離（`filter:none !important` で暗化防止）

### CSS変数
- `_variables.css` でテーマ変数定義
- カード色は `rgba(var(--bg-card-rgb, ...), var(--bg-card-alpha, 1))` パターンでBS3互換のフォールバック付き

### PHP注意事項
- `setcookie()` / `header()` を呼ぶ処理は必ずHTML出力前に書く（マイページ系で過去にエラー発生）

## 今後の対応予定

### 設定画面（init.php）のデザイン改善
別セクションとして実施予定。アイデア候補：
1. **スクロールスパイ付きTOCナビ** - 現在表示中のセクションをサイドバーでハイライト
2. **セクションカード化** - section単位で視覚的にまとめてグルーピング
3. **アコーディオン／タブ化** - 長大な設定項目を折りたたみ可能に
4. **boolean項目をform-switchへ** - 「使用する／使用しない」ラジオをトグルスイッチに置換
5. **設定状態のビジュアライズ** - 有効/無効バッジ、現在値のサマリ表示等
6. **危険操作の視覚的区別** - dangerゾーンとして枠線や背景色で区別
