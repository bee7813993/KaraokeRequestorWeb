# KaraokeRequestorWeb — Development Reference

## Project Overview

PHP-based karaoke request web application. Users submit song requests via browser; a host runs the player software (MPC-BE or foobar2000) on a local PC that picks them up automatically. Bootstrap 3 (legacy) and Bootstrap 5 (new) pages coexist in the same repo.

**Target environment**: XAMPP on Windows, PHP 7.x–8.x, SQLite DB.

---

## Directory Structure

```
/                       # Root: all PHP pages (130+ files)
├── commonfunc.php      # Central utility library (~2600 lines, 80+ functions)
├── kara_config.php     # Config loader + SQLite DB initializer
├── config.ini          # Runtime settings (INI format, URL-encoded values)
├── request.db          # SQLite database (default name, configurable)
├── css/
│   ├── bootstrap*.css  # Bootstrap 3 (legacy pages)
│   ├── bootstrap5/     # Bootstrap 5 assets
│   ├── themes/
│   │   ├── _variables.css    # CSS custom properties (dark/light theme)
│   │   ├── search.css
│   │   ├── player.css
│   │   └── theme-toggle.css
│   └── style.css       # Legacy global styles
├── js/
│   ├── jquery.min.js   # jQuery 1.x (BS3 pages)
│   ├── jquery.js       # jQuery 3.x
│   ├── bootstrap5/     # Bootstrap 5 JS bundle
│   ├── theme-toggle.js # Dark/light mode switcher
│   ├── player_bs5.js   # BS5 player controls
│   ├── requsetlist_ctrl.js
│   └── Sortable.min.js # Drag-drop reorder
├── images/
│   └── bg/             # Uploaded background images
├── modules/            # getid3, simple_html_dom.php
├── foobar2000/         # foobar2000 player integration
├── qrcode_php/         # QR code generation
└── cms/                # Nico Nico-style live comment system
```

---

## Bootstrap 3 vs Bootstrap 5 Strategy

**Bootstrap 3 (legacy)** — most pages default to BS3:
- `request.php`, `search.php`, `mpcctrl.php`, `foobarctl.php`, `bingo_*.php`, `commentedit.php`, `delete.php`
- CSS: `/css/bootstrap.min.css` + `/css/bootstrap-theme.css`
- jQuery 1.x, DataTables with BS3 styling

**Bootstrap 5 (modern)** — new pages and admin:
- `search_bs5.php`, `request_confirm_bs5.php`, `mpcctrl_bs5.php`, `foobarctl_bs5.php`
- `mypage*.php` (all BS5), `init.php` (admin, BS5)
- CSS: `/css/bootstrap5/bootstrap.min.css`
- Feature: sticky navbar, card layouts, breadcrumbs, form-switch toggles
- Auto-redirect: if `config_ini['usenewsearchui'] == 1`, `search.php` redirects to `search_bs5.php`

**Never mix BS3 and BS5 CSS/JS on the same page.** Each page loads one or the other.

Navbar height is standardized to **56px** across both BS3 and BS5 for consistent layout.

---

## Key PHP Files

| File | Purpose |
|------|---------|
| `kara_config.php` | Parses `config.ini`, initializes `$db` (PDO/SQLite), sets all `$config_ini` defaults |
| `commonfunc.php` | Central utility library; `require_once` this to get DB access + helpers |
| `init.php` | Admin settings page (BS5); protected by HTTP Basic Auth |
| `exec.php` | Request submission endpoint; validates and inserts into `requesttable` |
| `search.php` | Legacy BS3 search; auto-redirects to `search_bs5.php` if new UI enabled |
| `search_bs5.php` | Modern BS5 search interface |
| `requestlist_swipe.php` | Touch/swipe-friendly request queue (44KB, complex) |
| `requestlist_table_json.php` | JSON API for DataTables request list |
| `requestlist_reorder.php` | Drag-drop reordering via Sortable.js |
| `mpcctrl.php` / `mpcctrl_bs5.php` | MPC-BE player control UI |
| `foobarctl.php` / `foobarctl_bs5.php` | foobar2000 player control UI |
| `autoplayctrl.php` | Auto-play next queued song |
| `mypage_class.php` | MypageUser class — history, favorites, Google sync |
| `mypage_google_sync.php` | Google Drive bidirectional data sync |
| `configauth_class.php` | HTTP Basic Auth for admin (`init.php`) |
| `easyauth_class.php` | Cookie-based simple password for general access |
| `binngo_func.php` | SongBingo class for karaoke bingo game |
| `comment.php` | Nico Nico-style live comment overlay |
| `function_moveitem.php` | MoveItem class for queue reordering |
| `prioritydb_func.php` | Search result priority/sort customization |

### ListerDB Integration (anime song database)
- `search_listerdb*.php` — multiple search views (artist, program, filename, column)
- `search_listerdb_commonfunc.php` / `search_listerdb_commonfunc_bs5.php` — shared logic
- BS3 and BS5 variants exist for each view

---

## Database Schema

**Primary DB**: SQLite, path from `config_ini['dbname']` (default: `request.db`)

### `requesttable` — main request queue
```sql
id           INTEGER PRIMARY KEY
songfile     VARCHAR(1024)        -- display filename / song title
singer       VARCHAR(512)         -- requester name
comment      TEXT                 -- user comment
kind         TEXT                 -- song kind/category
reqorder     INTEGER              -- play order position
fullpath     TEXT                 -- full file path on server
nowplaying   TEXT                 -- 'play' | ''
status       TEXT                 -- request status
clientip     TEXT                 -- requester IP
clientua     TEXT                 -- requester User-Agent
playtimes    INTEGER              -- how many times played
secret       INTEGER              -- 1 = hide title until played
loop         INTEGER              -- 1 = loop this song
keychange    INTEGER DEFAULT 0    -- pitch shift semitones
track        INTEGER DEFAULT 0    -- audio track index
pause        INTEGER DEFAULT 0    -- 1 = auto-pause after play
audiodelay   INTEGER DEFAULT 0    -- lip-sync delay ms
duration     INTEGER DEFAULT 0    -- song duration seconds
volume       INTEGER DEFAULT 0    -- volume override
song_name    TEXT DEFAULT ''      -- metadata song name
lister_artist TEXT DEFAULT ''     -- ListerDB artist
lister_work   TEXT DEFAULT ''     -- ListerDB work/anime title
lister_op_ed  TEXT DEFAULT ''     -- OP/ED designation
lister_comment TEXT DEFAULT ''    -- ListerDB comment
```

### Mypage tables (auto-created in same DB)
- `mypage_user` — user profiles (UUID cookie-based identity)
- `mypage_history` — song request history per user
- `mypage_later` — "sing later" wishlist
- `mypage_favorite_song` — saved favorite songs
- `mypage_favorite_keyword` — saved search keywords
- `mypage_pair_code` — device pairing codes (5-minute TTL)
- `mypage_google_link` — Google OAuth tokens per user

---

## Configuration System

`config.ini` is read by `kara_config.php` into `$config_ini` global array. **Values are URL-encoded** in the INI file; always call `urldecode()` on string values when reading, or use `configbool()` helper for boolean flags.

### Key config.ini parameters

| Key | Type | Default | Purpose |
|-----|------|---------|---------|
| `dbname` | string | `request.db` | SQLite DB filename |
| `playmode` | int | `3` | Player mode (1=MPC, 2=foobar, 3=auto) |
| `playerpath_select` | path | MPC-BE path | Selected player executable |
| `foobarpath` | path | `.\foobar2000\foobar2000.exe` | foobar2000 path |
| `usenewsearchui` | bool | `0` | Redirect search.php → search_bs5.php |
| `usenewrequestlist` | bool | `0` | Use swipe-based request list |
| `usemypage` | bool | `0` | Enable mypage/user profile feature |
| `usebingo` | bool | `0` | Enable karaoke bingo mode |
| `usevideocapture` | bool | `0` | Enable playback screenshot capture |
| `usebgv` | bool | `0` | Enable background video mode |
| `nonamerequest` | bool | `0` | Allow anonymous requests |
| `nonameusername` | string | — | Default name for anonymous requests |
| `connectinternet` | bool | `1` | Allow internet search integrations |
| `historylog` | bool | `0` | Enable history logging |
| `bgimage` | string | — | Active background image filename |
| `bg_card_opacity` | int | — | Card background opacity 0–100 |
| `bg_overlay_opacity` | int | — | Page overlay opacity 0–100 |
| `commenturl_base` | URL | localhost/cms/r.php | Comment system endpoint |
| `commentroom` | string | `1000` | Comment room ID |
| `helpurl` | URL | — | Help page URL shown in navbar |
| `google_client_id` | string | — | Google OAuth client ID |
| `google_relay_url` | URL | — | Google Drive relay server URL |
| `secret_display_text` | string | — | Text shown for secret reserves |
| `max_filesize` | int | — | Max upload size (bytes) |
| `downloadfolder` | path | — | Folder for downloaded files |
| `autoplay_exec` | bool | `0` | Enable autoplay execution |

Use `configbool($keyword, $defaultvalue)` (in `commonfunc.php`) to safely read boolean config values.

---

## Authentication System

**Admin (init.php)**: HTTP Basic Auth via `configauth_class.php`
```php
if ($_SERVER['PHP_AUTH_USER'] !== 'admin') { /* 401 */ }
if (!$configauth->check_auth($_SERVER['PHP_AUTH_PW'])) { /* 401 */ }
```
Password stored bcrypt-hashed. Master password separate from user password.

**Easy Auth**: Cookie-based simple password for general access via `easyauth_class.php`
```php
$easyauth = new EasyAuth();
$easyauth->do_eashauthcheck(); // dies with 401 if not authenticated
```
Cookie name: `YkariEasyPass`

**User identity** (no PHP sessions):
- Username: `YkariUsername` cookie, persistent 60 days
- Mypage UUID: `YkariUserID` cookie, persistent 365 days, randomly generated UUID

---

## CSS / Theming System

### CSS Variables (`css/themes/_variables.css`)
All theme colors are defined as CSS custom properties:
```css
--bg-page           /* page background color */
--bg-card-rgb       /* card background as RGB triplet e.g. "255,255,255" */
--bg-card-alpha     /* card background alpha (set by PHP for transparency) */
--bg-overlay-color  /* overlay color for background image */
```

Card color pattern with BS3 fallback:
```css
background-color: rgba(var(--bg-card-rgb, 248, 236, 224), var(--bg-card-alpha, 1));
```

### Dark Mode
- Toggle via `js/theme-toggle.js`
- Stored in `localStorage` key `ykari-theme` (values: `"light"` / `"dark"`)
- Font size stored in `localStorage` key `ykari-fontsize`
- Theme init script must be in `<head>` **before** CSS loads to prevent FOUC
- Use `print_bs5_search_head()` from `commonfunc.php` which includes the correct init script

### Background Image Feature (BS5 pages only)
- Admin uploads via `init.php` → stored in `images/bg/` (timestamp + random name)
- Two opacity axes: card opacity (`bg_card_opacity`) + page overlay (`bg_overlay_opacity`)
- Implementation: `print_bg_style_block($is_bs5)` in `commonfunc.php` injects inline CSS
- Dark mode handling: `body` gets the image, `body::before` pseudo-element gets overlay color, separated to prevent dark mode `filter` from dimming the image (`filter: none !important`)
- **BS3 pages are excluded**; pass `$is_bs5 = true` only on BS5 pages

---

## Key Helper Functions (commonfunc.php)

Always `require_once 'commonfunc.php'` — it also loads `kara_config.php` and `prioritydb_func.php`.

| Function | Purpose |
|----------|---------|
| `getcurrentplayer()` | Get currently playing song info |
| `getcurrentid()` | Get ID of currently playing request |
| `countafterplayingitem()` | Count songs queued after current |
| `getallrequest_array()` | Fetch all requests from DB |
| `searchlocalfilename($keywords, &$result, $order, $path)` | Search local files by keyword |
| `PrintLocalFileListfromkeyword_ajax($word, ...)` | Output AJAX search results HTML |
| `searchresultcount_fromkeyword($word)` | Count search results |
| `print_meta_header()` | Standard HTML `<head>` boilerplate |
| `print_bg_style_block($is_bs5)` | Inject background image CSS |
| `print_bs5_search_head($extra_css)` | Full BS5 `<head>` with theme init |
| `shownavigatioinbar_bs5($page, $prefix)` | BS5 navbar HTML |
| `shownavigatioinbar($page, $prefix)` | BS3 navbar HTML |
| `build_reservation_tabs($selectid, $current, $prefix)` | BS5 search/reserve tab bar |
| `selectrequestkind_bs5_dd($prefix, $id)` | Song kind dropdown (BS5) |
| `selectrequestkind($kind, $prefix, $id)` | Song kind selector (BS3) |
| `configbool($keyword, $defaultvalue)` | Safe boolean config read |
| `makesongnamefromfilename($filename)` | Strip path/extension for display |
| `returnusername($rt)` | Get singer name from request row |
| `returnusername_self()` | Get current user's name from cookie |
| `singerfromip($rt)` | Check if request is from current user |
| `hex_to_rgb_triplet($hex, $fallback)` | Convert `#RRGGBB` to `"R,G,B"` string |
| `writeconfig2ini($config_ini, $configfile)` | Save config array back to INI |
| `commentpost_v4($cmd, $msg, $commenturl)` | Post to live comment system |
| `file_get_html_with_retry($url, ...)` | Robust HTTP GET with retries |
| `mypage_action_links($fullpath, $songfile, $kind)` | Generate mypage save/later buttons |
| `mypage_save_keyword_link($keyword, $search_type, $params)` | Generate save-keyword button |
| `get_version()` | Get app version string |

---

## PHP Coding Conventions

### CRITICAL: Header-before-output rule
`setcookie()` and `header()` **must** be called before any HTML output. Violations cause "headers already sent" errors. This has caused bugs in mypage files in the past. 詳細な配置ルールとテンプレートは `.claude/skills/new-bs5-page/SKILL.md` を参照。

### Database access
Always use parameterized queries. Never interpolate user input into SQL.
```php
$stmt = $db->prepare("SELECT * FROM requesttable WHERE id = :id");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
```

### Common page structure (BS5)
新規 BS5 ページの作成手順・テンプレート・呼び出し順序・チェックリストは `.claude/skills/new-bs5-page/SKILL.md` を参照。

### IPv6 handling
Server addresses may be IPv6. Use `addipv6blanket()` when constructing URLs with server IP.

### Config values are URL-encoded
String values in `config.ini` are stored URL-encoded. The loader in `kara_config.php` decodes them on read. Do not double-encode when writing.

---

## Request Flow

1. User visits `search.php` (or `search_bs5.php`) → searches local files
2. Selects a song → `request.php` form (or `request_confirm_bs5.php` for inline confirm)
3. Submits → `exec.php` validates + inserts row into `requesttable`
4. `autoplayctrl.php` polls DB; when queue has items and player is free, sends play command
5. Player control via HTTP to MPC-BE (`mpcctrl.php`) or foobar2000 (`foobarctl.php`)
6. Request list updated live via AJAX from `requestlist_table_json.php`

---

## Player Integration

**MPC-BE**: Controlled via HTTP API on `localhost:13579`
- `mpcctrl.php` / `mpcctrl_bs5.php` — UI
- `mpcctrl_func.php` — low-level API calls

**foobar2000**: Controlled via HTTP API
- `foobarctl.php` / `foobarctl_bs5.php` — UI
- `foobar_func.php` — low-level API calls

**Portal**: `playerctrl_portal.php` / `playerctrl_portal_bs5.php` — auto-selects player

---

## Search Systems

### Local file search
- `commonfunc.php`: `searchlocalfilename()`, `searchlocalfilename_part()`
- Supports priority weighting via `prioritydb_func.php`

### ListerDB (anime song database)
- External SQLite DB with anime/visual novel song metadata
- Views: artist, program/work, filename, column-based
- `search_listerdb_commonfunc.php` — shared BS3 logic
- `search_listerdb_commonfunc_bs5.php` — shared BS5 logic

### External search integrations (require `connectinternet=1`)
- **anison.info**: `search_anisoninfo*.php` — anime song metadata
- **Bandit**: `searchbandit.php` / `searchbandit_bs5.php` — eroge songs

---

## Mypage / User Profile System

Enabled via `config_ini['usemypage'] == 1`. Identity tracked by UUID cookie (`YkariUserID`).

Key pages: `mypage.php` (hub), `mypage_history.php`, `mypage_favorite_song.php`, `mypage_later.php`, `mypage_favorite_keyword.php`

**Google Drive sync**: `mypage_google_sync.php` — bidirectional sync of user data. Requires `google_client_id`, `google_client_secret`, and relay server config.

**Device pairing**: `mypage_link_device.php` — 5-minute TTL code to link user accounts across browsers.

---

## Special Features

- **Bingo mode** (`usebingo=1`): Karaoke bingo — `binngo_func.php`, `bingo_input.php`, `bingo_showresult.php`
- **Secret reserves** (`secret=1`): Title hidden until song plays; display text configurable via `secret_display_text`
- **Key change**: Pitch adjustment in semitones, passed to MPC-BE
- **Audio delay**: Lip-sync offset in ms
- **Autoplay** (`autoplay_exec=1`): Automatic play of next song in queue
- **Video capture** (`usevideocapture=1`): Screenshot during playback
- **Live comments**: Nico Nico-style overlay via `comment.php` / `cms/` subsystem
- **Swipe UI** (`usenewrequestlist=1`): Touch-friendly request list (`requestlist_swipe.php`)
- **YouTube/Niconico download**: `nicodownload_recv.php`, `youtube_download.php`

---

## Git / Update System

- `get_version()` and `get_git_version()` in `commonfunc.php` read version from git
- `online_update.php` / `update.php` — in-app git-based update
- `gitcommandpath` config key sets path to git executable

---

## Web テスト (Playwright MCP / Chrome MCP)

実ブラウザでの動作確認手順(試験サーバー URL: `http://ykr.moe:11004/` / `http://ykr.moe:11002/`、ToolSearch によるツールのロード方法、Playwright と Chrome MCP の使い分け、主なツール一覧)は `.claude/skills/web-test/SKILL.md` を参照。

---

## Future Planned Work (init.php UI)

The admin settings page (`init.php`) UI improvements are planned as a separate effort:
1. **Scroll-spy TOC nav** — sidebar highlighting current section
2. **Section cards** — visual grouping per section
3. **Accordion/tab layout** — collapsible long setting groups
4. **Form-switch for booleans** — replace radio "use/don't use" with toggle switches
5. **Status visualization** — enabled/disabled badges, current value summaries
6. **Danger zone styling** — distinct visual treatment for destructive operations
