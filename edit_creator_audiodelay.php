<?php
require_once 'commonfunc.php';

$delay_file = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'creator_audiodelay.json';
$auth_file  = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'creator_audiodelay_auth.json';

function load_creator_delays($file) {
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        if (is_array($data)) return $data;
    }
    return [];
}

function save_creator_delays($file, $rules) {
    file_put_contents($file, json_encode($rules, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function load_delay_auth($file) {
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        if (is_array($data)) return $data;
    }
    return [];
}

function save_delay_auth($file, $auth_data) {
    file_put_contents($file, json_encode($auth_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function is_local_access_cd() {
    $remote = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    $server = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';
    return in_array($remote, ['127.0.0.1', '::1', $server], true);
}

$auth_data     = load_delay_auth($auth_file);
$password_set  = !empty($auth_data['password_hash']);
$authenticated = false;
$auth_error    = false;

if (is_local_access_cd() || !$password_set) {
    $authenticated = true;
} else {
    $input_pass = '';
    if (!empty($_POST['delay_auth_pass'])) {
        $input_pass = $_POST['delay_auth_pass'];
    } elseif (isset($_COOKIE['CreatorDelayPass'])) {
        $input_pass = base64_decode($_COOKIE['CreatorDelayPass']);
    }
    if ($input_pass !== '' && password_verify($input_pass, $auth_data['password_hash'])) {
        setcookie('CreatorDelayPass', base64_encode($input_pass), 0);
        $authenticated = true;
    } elseif (!empty($_POST['delay_auth_pass'])) {
        $auth_error = true;
    }
}

if (!$authenticated) {
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>制作者別音ズレ初期値設定 - 認証</title>
  <link href="css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container" style="max-width:400px; margin-top:80px;">
  <h2>制作者別音ズレ初期値設定</h2>
  <p>このページにアクセスするにはパスワードが必要です。</p>
  <?php if ($auth_error): ?>
  <div class="alert alert-danger">パスワードが違います。</div>
  <?php endif; ?>
  <form method="post" action="edit_creator_audiodelay.php">
    <div class="form-group">
      <label for="delay_auth_pass">パスワード</label>
      <input type="password" name="delay_auth_pass" id="delay_auth_pass" class="form-control" autofocus>
    </div>
    <button type="submit" class="btn btn-primary">ログイン</button>
    <a href="init.php" class="btn btn-default">設定画面に戻る</a>
  </form>
</div>
<script src="js/jquery.js"></script>
<script src="js/bootstrap.min.js"></script>
</body>
</html>
<?php
    die();
}

$message      = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $rules  = load_creator_delays($delay_file);

    if ($action === 'add') {
        $keyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : '';
        $delay   = isset($_POST['delay'])   ? intval($_POST['delay']) : 0;
        $fps     = isset($_POST['fps'])     ? trim($_POST['fps'])     : '';
        if ($keyword === '') {
            $message      = 'キーワード（制作者名）を入力してください';
            $message_type = 'danger';
        } elseif ($delay % 100 !== 0) {
            $message      = '音ズレは100ms単位で指定してください';
            $message_type = 'danger';
        } elseif ($delay < -9900 || $delay > 9900) {
            $message      = '音ズレは -9900ms ～ +9900ms の範囲で指定してください';
            $message_type = 'danger';
        } else {
            $rule = ['keyword' => $keyword, 'delay' => $delay];
            if ($fps !== '' && is_numeric($fps)) {
                $rule['fps'] = floatval($fps);
            }
            $rules[] = $rule;
            save_creator_delays($delay_file, $rules);
            $message = '追加しました';
        }

    } elseif ($action === 'delete') {
        $index = isset($_POST['index']) ? intval($_POST['index']) : -1;
        if ($index >= 0 && $index < count($rules)) {
            array_splice($rules, $index, 1);
            save_creator_delays($delay_file, $rules);
            $message = '削除しました';
        }

    } elseif ($action === 'clear') {
        save_creator_delays($delay_file, []);
        $message = '全ルールを削除しました';

    } elseif ($action === 'set_password') {
        $new_pass  = isset($_POST['new_password'])  ? $_POST['new_password']  : '';
        $new_pass2 = isset($_POST['new_password2']) ? $_POST['new_password2'] : '';
        if ($new_pass === '') {
            $message = 'パスワードを入力してください';
            $message_type = 'danger';
        } elseif ($new_pass !== $new_pass2) {
            $message = 'パスワードが一致しません';
            $message_type = 'danger';
        } else {
            $auth_data['password_hash'] = password_hash($new_pass, PASSWORD_DEFAULT);
            save_delay_auth($auth_file, $auth_data);
            setcookie('CreatorDelayPass', base64_encode($new_pass), 0);
            $password_set = true;
            $message = 'パスワードを設定しました';
        }

    } elseif ($action === 'remove_password') {
        $auth_data = [];
        save_delay_auth($auth_file, $auth_data);
        setcookie('CreatorDelayPass', '', time() - 3600);
        $password_set = false;
        $message = 'パスワードを削除しました';
    }
}

$rules = load_creator_delays($delay_file);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<?php print_meta_header(); ?>
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="expires" content="0">
<title>制作者別音ズレ初期値設定</title>
<link href="css/bootstrap.min.css" rel="stylesheet">
<link type="text/css" rel="stylesheet" href="css/style.css" />
<script type="text/javascript" charset="utf8" src="js/jquery.js"></script>
<script src="js/bootstrap.min.js"></script>
</head>
<body>
<?php shownavigatioinbar(); ?>
<div class="container">
  <h1>制作者別音ズレ初期値設定</h1>

  <?php if (!empty($message)): ?>
  <div class="alert alert-<?php echo htmlspecialchars($message_type, ENT_QUOTES, 'UTF-8'); ?>">
    <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
  </div>
  <?php endif; ?>

  <h3>現在の設定ルール</h3>
  <table class="table table-striped table-bordered">
    <thead>
      <tr>
        <th class="col-xs-5">制作者名（found_worker）</th>
        <th class="col-xs-2">FPS条件</th>
        <th class="col-xs-3">音ズレ初期値</th>
        <th class="col-xs-2">操作</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rules as $i => $rule): ?>
      <tr>
        <td><?php echo htmlspecialchars($rule['keyword'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo isset($rule['fps']) ? htmlspecialchars($rule['fps'], ENT_QUOTES, 'UTF-8').' fps' : '<span class="text-muted">指定なし</span>'; ?></td>
        <td><?php echo htmlspecialchars($rule['delay'], ENT_QUOTES, 'UTF-8'); ?> ms</td>
        <td>
          <form method="post" action="edit_creator_audiodelay.php" style="margin:0;">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="index" value="<?php echo $i; ?>">
            <button type="submit" class="btn btn-danger btn-xs">削除</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($rules)): ?>
      <tr>
        <td colspan="4" class="text-center text-muted">ルールが設定されていません</td>
      </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <h3>ルール追加</h3>
  <form method="post" action="edit_creator_audiodelay.php">
    <input type="hidden" name="action" value="add">
    <div class="form-group">
      <label>制作者名（found_worker）</label>
      <input type="text" name="keyword" class="form-control" placeholder="ListerDB の found_worker の値" style="max-width:400px;">
      <p class="help-block">りすたーDB（ListerDB）の <code>found_worker</code> フィールドと<strong>完全一致</strong>で判定されます。検索画面の制作者名リンクに表示される名前を入力してください。</p>
    </div>
    <div class="form-group">
      <label>音ズレ初期値 (ms)</label>
      <div class="input-group" style="max-width:200px;">
        <input type="number" name="delay" class="form-control" value="0" step="100" min="-9900" max="9900">
        <span class="input-group-addon">ms</span>
      </div>
      <p class="help-block">100ms単位で指定。正の値で映像を遅らせる（音が早い場合）、負の値で音を遅らせる（映像が早い場合）。</p>
    </div>
    <div class="form-group">
      <label>FPS条件 <small class="text-muted">（任意）</small></label>
      <div class="input-group" style="max-width:200px;">
        <input type="number" name="fps" class="form-control" placeholder="例: 29.97" step="0.01" min="0">
        <span class="input-group-addon">fps</span>
      </div>
      <p class="help-block">入力した場合、動画のFPSが一致する場合のみ適用されます。空欄にするとFPSに関わらず適用。</p>
    </div>
    <button type="submit" class="btn btn-primary">追加</button>
  </form>

  <hr>

  <div class="panel panel-default">
    <div class="panel-heading"><strong>この画面の使い方</strong></div>
    <div class="panel-body">
      <ol>
        <li>制作者名はりすたーDB（ListerDB）の <code>found_worker</code> フィールドと<strong>完全一致</strong>で判定されます。検索画面の制作者名リンクに表示される名前を入力してください。</li>
        <li>ListerDB を使用していない環境ではこの機能は動作しません（初期値は 0ms になります）。</li>
        <li>複数のルールが一致する場合、リストの上から順に最初に一致したルールが使用されます。</li>
        <li>FPS条件を指定すると、動画のフレームレートが一致する場合のみ適用されます（±0.5fps 以内で一致判定）。</li>
        <li>設定した音ズレ値は予約確認画面の初期値として表示されます。ユーザーが変更することもできます。</li>
        <li>音ズレ値は 100ms 単位で、-9900ms ～ +9900ms の範囲で設定できます。</li>
      </ol>
    </div>
  </div>

  <form method="post" action="edit_creator_audiodelay.php"
        onsubmit="return confirm('全ルールを削除しますか？');">
    <input type="hidden" name="action" value="clear">
    <button type="submit" class="btn btn-warning">全ルールを削除</button>
  </form>

  <hr>

  <div class="panel panel-default">
    <div class="panel-heading"><strong>このページのパスワード設定</strong></div>
    <div class="panel-body">
      <p>
        現在の状態：
        <?php if ($password_set): ?>
          <span class="label label-warning">パスワードあり</span>
          <small class="text-muted">（サーバーと同じ機器からのアクセスはスキップされます）</small>
        <?php else: ?>
          <span class="label label-default">パスワードなし（誰でもアクセス可）</span>
        <?php endif; ?>
      </p>
      <form method="post" action="edit_creator_audiodelay.php">
        <input type="hidden" name="action" value="set_password">
        <div class="form-group">
          <label>新しいパスワード</label>
          <input type="password" name="new_password" class="form-control" style="max-width:300px;">
        </div>
        <div class="form-group">
          <label>確認（もう一度入力）</label>
          <input type="password" name="new_password2" class="form-control" style="max-width:300px;">
        </div>
        <button type="submit" class="btn btn-primary">パスワードを設定する</button>
      </form>
      <?php if ($password_set): ?>
      <hr>
      <form method="post" action="edit_creator_audiodelay.php"
            onsubmit="return confirm('パスワードを削除すると誰でもアクセスできるようになります。よろしいですか？');">
        <input type="hidden" name="action" value="remove_password">
        <button type="submit" class="btn btn-danger">パスワードを削除する</button>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <hr>
  <p>
    <a href="init.php" class="btn btn-default">設定画面に戻る</a>
    &nbsp;
    <a href="requestlist_top.php" class="btn btn-default">トップに戻る</a>
  </p>
</div>
</body>
</html>
