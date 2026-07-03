---
name: commit-message
description: このプロジェクトの commit メッセージ規約。「コミットして」「変更をコミットして」「PR を作って」など、git commit を行うすべての場面で使用。Conventional Commits プレフィックス + 日本語要約の形式を定める。
---

# Commit メッセージ規約

## 形式

```
<type>: <日本語での変更内容の要約>
```

例(実際の履歴より):

```
fix: ゆかりすたーDB未構築時に曲名が「未分類」ではなくファイル名を表示するよう修正
feat: 設定バックアップ/リストア機能を追加
docs: CHANGELOGに設定バックアップ/リストア機能を追記
refactor: バックアップ機能を統合ページに集約
```

## type 一覧

| type | 用途 |
|------|------|
| `feat` | 新機能・機能追加・UI の新レイアウト |
| `fix` | バグ修正・表示崩れ・挙動の是正 |
| `docs` | README / CHANGELOG / CLAUDE.md などドキュメントのみの変更 |
| `refactor` | 挙動を変えないコード整理・共通化 |
| `chore` | ビルド・インストーラー・設定ファイル等の雑務(必要な場合) |

## ルール

1. **要約は日本語**で書く。技術用語・ファイル名・関数名は原文のまま(例: `COLLATE NOCASE`、`ipconfig.php`)
2. 1 行目は 50〜70 文字程度を目安に、**何をどう変えたか**が読み取れるようにする
3. 「〜を修正」「〜を追加」「〜に変更」「〜に統一」など、動詞で終える体言止めスタイル
4. 詳細が必要な場合は 1 行空けて本文に箇条書きで補足
5. 末尾に以下を付ける:
   ```
   Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>
   ```
6. ブランチ名は `claude/<説明>` または `fix/<説明>` 形式
7. master へ直接コミットしない。ブランチ → PR → マージの流れを守る(マージ先は `master`)

## 悪い例

- `Update files` — 何を変えたか分からない
- `fix: bug fix` — 内容がない
- `feat: Added new backup feature` — 要約は日本語で書く
