---
name: web-test
description: このアプリを実ブラウザでテスト・動作確認する手順。「ブラウザでテストして」「動作確認して」「画面を確認して」「スクリーンショットを撮って」「実際に動かして確認して」などの依頼で使用。Playwright MCP で試験サーバー (ykr.moe) にアクセスし、UI 操作・表示・コンソールエラーを検証する。
---

# Web テスト (Playwright MCP)

## 概要

Playwright MCP がグローバル設定に登録済みのため、どのセッションでも Web テストが可能。

**MCP 設定ファイル**: `C:\Users\bee78\AppData\Roaming\Claude\claude_desktop_config.json`

```json
"mcpServers": {
  "playwright": {
    "command": "C:\\Program Files\\nodejs\\npx.cmd",
    "args": ["@playwright/mcp@latest"]
  }
}
```

## 試験用サーバー URL

- **メイン**: `http://ykr.moe:11004/`
- **サブ**: `http://ykr.moe:11002/`

メインが応答しない場合はサブを試すこと。

## セッション開始時の手順(必須)

新しいセッションでは Playwright ツールが**未ロード(deferred)状態**のため、使用前に必ず ToolSearch でロードする。個別に何度も呼ばず、**1 回の ToolSearch でまとめてロード**すること:

```
ToolSearch: "select:mcp__playwright__browser_navigate,mcp__playwright__browser_snapshot,mcp__playwright__browser_take_screenshot,mcp__playwright__browser_click,mcp__playwright__browser_fill_form,mcp__playwright__browser_console_messages"
```

(必要に応じてキーワード検索 `"playwright"` で他のツールも追加ロード)

## 主なツール

| ツール名 | 用途 |
|----------|------|
| `mcp__playwright__browser_navigate` | URL に移動 |
| `mcp__playwright__browser_take_screenshot` | スクリーンショット取得 |
| `mcp__playwright__browser_snapshot` | DOM スナップショット(操作用) |
| `mcp__playwright__browser_click` | 要素をクリック |
| `mcp__playwright__browser_fill_form` | フォーム入力 |
| `mcp__playwright__browser_type` | テキスト入力 |
| `mcp__playwright__browser_select_option` | セレクトボックス選択 |
| `mcp__playwright__browser_press_key` | キー操作 |
| `mcp__playwright__browser_wait_for` | 要素/条件の待機 |
| `mcp__playwright__browser_console_messages` | コンソールログ取得 |
| `mcp__playwright__browser_network_requests` | ネットワークリクエスト確認 |

## 典型的なテストフロー

1. ツールをロード(上記 ToolSearch)
2. `browser_navigate` で対象ページへ移動(例: `http://ykr.moe:11004/search_bs5.php`)
3. `browser_snapshot` で構造・テキストを確認(テキスト検証はスクリーンショットより snapshot を優先)
4. `browser_click` / `browser_fill_form` で操作
5. `browser_console_messages` で JS エラーがないか確認
6. レイアウト確認が必要な場合のみ `browser_take_screenshot`

## Chrome MCP (claude-in-chrome) も使用可能

Playwright の代わりに、ユーザーの実 Chrome ブラウザを操作する Chrome MCP (`mcp__claude-in-chrome__*`) も Web 試験に使用できる。ログイン状態や実環境の Cookie(`YkariUsername` / `YkariUserID` / `YkariEasyPass`)をそのまま使ってテストしたい場合に有効。

こちらも deferred 状態のため、使用前に 1 回の ToolSearch でまとめてロードする:

```
ToolSearch: "select:mcp__claude-in-chrome__tabs_context_mcp,mcp__claude-in-chrome__navigate,mcp__claude-in-chrome__computer,mcp__claude-in-chrome__read_page,mcp__claude-in-chrome__tabs_create_mcp"
```

| ツール名 | 用途 |
|----------|------|
| `mcp__claude-in-chrome__navigate` | URL に移動 |
| `mcp__claude-in-chrome__read_page` / `get_page_text` | ページ内容の読み取り |
| `mcp__claude-in-chrome__computer` | クリック・入力・スクリーンショット |
| `mcp__claude-in-chrome__find` | 要素検索 |
| `mcp__claude-in-chrome__form_input` | フォーム入力 |
| `mcp__claude-in-chrome__read_console_messages` | コンソールログ取得 |

使い分けの目安:
- **Playwright**: クリーンな状態からの再現テスト・自動操作向き(独立ブラウザ)
- **Chrome MCP**: ユーザーの Chrome 上での実環境確認・Cookie 依存機能(mypage 等)の確認向き

## 注意

- ダークモード切替 (`localStorage: ykari-theme`) やフォントサイズ (`ykari-fontsize`) の検証は `browser_evaluate` で localStorage を操作してからリロードする
- BS3 ページと BS5 ページで見た目が大きく異なるのは仕様(CLAUDE.md「Bootstrap 3 vs Bootstrap 5 Strategy」参照)
