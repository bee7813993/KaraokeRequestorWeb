---
name: new-bs5-page
description: 新しい PHP ページ(Bootstrap 5)を作成・追加する際の手順書。「新しいページを作って」「BS5 で〇〇ページを追加して」「〇〇画面を新規作成して」などの依頼で使用。print_bs5_search_head / navbar / print_bg_style_block の正しい呼び出し順序と header-before-output ルール違反の防止手順を含む。
---

# 新規 BS5 ページ作成手順

このプロジェクトは Bootstrap 3(レガシー)と Bootstrap 5(新規)が混在している。
**新規ページは必ず BS5 で作成する。BS3 と BS5 の CSS/JS を同一ページに混在させてはならない。**

## 手順

### 1. 出力前処理をファイル先頭にまとめる(最重要)

`setcookie()` / `header()` は **HTML 出力より前** に呼ぶこと。違反すると
"headers already sent" エラーになる(過去に mypage 系で実際に発生したバグ)。

先頭ブロックに置くもの(この順序):

1. `require_once 'commonfunc.php';` — DB (`$db`)・`$config_ini`・ヘルパーがロードされる
2. EasyAuth チェック(一般ページで認証が必要な場合)
3. すべての `setcookie()` / `header()`(リダイレクト含む)
4. `MypageUser` などクラスの生成 — **内部で setcookie を呼ぶものはここまでに**

`?>` の後や echo/print の後にこれらを書かないこと。閉じタグ `?>` より前に
空白や BOM を出力しないことにも注意。

### 2. ページテンプレート

```php
<?php
require_once 'commonfunc.php';

// 認証(必要な場合)
$easyauth = new EasyAuth();
$easyauth->do_eashauthcheck();   // 未認証なら 401 で die

// ここに setcookie() / header() / MypageUser 生成など、出力前処理をすべて置く
?>
<!DOCTYPE html>
<html>
<?php print_bs5_search_head(); ?>   <!-- BS5用<head>一式。ダークモード初期化スクリプト込み(FOUC防止のためCSSより先に実行される) -->
<body>
<?php shownavigatioinbar_bs5('pagename'); ?>   <!-- BS5ナビバー。'pagename'は現在ページの識別子 -->

<!-- ここにページコンテンツ (container / card レイアウト) -->

<?php print_bg_style_block(true); ?>   <!-- 背景画像CSS注入。BS5ページなので必ず true を渡す -->
</body>
</html>
```

呼び出し順序の要点:

| 順序 | 呼び出し | 備考 |
|------|----------|------|
| 1 | `require_once 'commonfunc.php'` | kara_config.php / prioritydb_func.php も一緒にロードされる |
| 2 | (出力前処理) | setcookie / header / 認証 |
| 3 | `print_bs5_search_head($extra_css)` | `<html>` 直後。独自 CSS は引数で追加 |
| 4 | `shownavigatioinbar_bs5($page, $prefix)` | `<body>` 直後 |
| 5 | `print_bg_style_block(true)` | `</body>` 直前。**BS5 ページのみ true**(BS3 は対象外) |

### 3. コーディング規約

- **DB アクセスは必ずプリペアドステートメント**。ユーザー入力を SQL に文字列連結しない:
  ```php
  $stmt = $db->prepare("SELECT * FROM requesttable WHERE id = :id");
  $stmt->bindValue(':id', $id, PDO::PARAM_INT);
  $stmt->execute();
  ```
- 設定値の bool 読み取りは `configbool($keyword, $defaultvalue)` を使う(config.ini の値は URL エンコードされている)
- サーバー IP から URL を組み立てる場合は IPv6 対応の `addipv6blanket()` を使う
- 曲種別ドロップダウンは `selectrequestkind_bs5_dd($prefix, $id)`、検索/予約タブは `build_reservation_tabs()` を使う
- ナビバー高さは 56px 固定。sticky navbar 前提でレイアウトする

### 4. 作成後のチェックリスト

- [ ] BS3 の CSS/JS (`/css/bootstrap.min.css`, jQuery 1.x) を読み込んでいないか
- [ ] `setcookie()` / `header()` がすべて HTML 出力より前にあるか
- [ ] `print_bg_style_block(true)` を `</body>` 直前で呼んでいるか
- [ ] SQL がすべてパラメータ化されているか
- [ ] `php -l <file>` で構文チェック
- [ ] 可能なら web-test スキル(Playwright)で実ブラウザ確認(ライト/ダーク両テーマ)
