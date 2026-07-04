<?php
/**
 * function_requestops.php
 *
 * リクエスト行の個別移動処理 (上へ / 下へ / 次に再生)。
 * もともと delete.php 内に定義されていた dbup / dbdown / warikomi を、
 * /api/request_move.php からも再利用できるよう無改修で切り出したもの。
 *
 * 注意: 各関数は結果メッセージを print で出力する (delete.php の画面表示用)。
 *       JSON エンドポイントから呼ぶ場合は ob_start()/ob_get_clean() で捕捉すること。
 */

$tmpid=9999;
/**
 * 行を上に移動
 * @param integer $id
 * @param PDO $db
 */
function dbup($id, $db)
{
    global $tmpid;
    // 対象のreqorderを取得
    $stmt = $db->prepare("SELECT * FROM requesttable WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        print("id={$id} のレコードが見つかりません。<br>");
        return;
    }
    $targetorder = (int)$row['reqorder'];

    // reqorder が自分より大きい（表示上ひとつ上）の行を探す
    // ±1固定ではなく ORDER BY で隣を特定することで、10倍間隔や連番でない場合でも正しく動作する
    $stmt = $db->prepare(
        "SELECT * FROM requesttable WHERE reqorder > :targetorder ORDER BY reqorder ASC LIMIT 1"
    );
    $stmt->bindValue(':targetorder', $targetorder, PDO::PARAM_INT);
    $stmt->execute();
    $neighbor = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($neighbor !== false) {
        $neighbororder = (int)$neighbor['reqorder'];
        // 3ステップスワップ: target と neighbor の reqorder を交換
        $db->beginTransaction();
        // ステップ1: neighbor を一時値に退避
        $stmt = $db->prepare("UPDATE requesttable SET reqorder = :tmpid WHERE id = :nid");
        $stmt->bindValue(':tmpid', $tmpid, PDO::PARAM_INT);
        $stmt->bindValue(':nid', (int)$neighbor['id'], PDO::PARAM_INT);
        $ret = $stmt->execute();
        if (!$ret) { $db->rollBack(); print("一時退避に失敗しました。<br>"); die(); }
        // ステップ2: target を neighbororder へ
        $stmt = $db->prepare("UPDATE requesttable SET reqorder = :neighbororder WHERE id = :id");
        $stmt->bindValue(':neighbororder', $neighbororder, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $ret = $stmt->execute();
        if (!$ret) { $db->rollBack(); print("{$id} の上への移動に失敗しました。<br>"); die(); }
        // ステップ3: 一時値を targetorder へ
        $stmt = $db->prepare("UPDATE requesttable SET reqorder = :targetorder WHERE reqorder = :tmpid");
        $stmt->bindValue(':targetorder', $targetorder, PDO::PARAM_INT);
        $stmt->bindValue(':tmpid', $tmpid, PDO::PARAM_INT);
        $ret = $stmt->execute();
        if (!$ret) { $db->rollBack(); print("復元に失敗しました。<br>"); die(); }
        $db->commit();
    } else {
        print("すでに一番上です。<br>");
    }
}

/**
 * 行を下に移動
 * @param integer $id
 * @param PDO $db
 */
function dbdown($id, $db)
{
    global $tmpid;
    // 対象のreqorderを取得
    $stmt = $db->prepare("SELECT * FROM requesttable WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        print("id={$id} のレコードが見つかりません。<br>");
        return;
    }
    $targetorder = (int)$row['reqorder'];

    // reqorder が自分より小さい（表示上ひとつ下）の行を探す
    // ±1固定ではなく ORDER BY で隣を特定することで、10倍間隔や連番でない場合でも正しく動作する
    $stmt = $db->prepare(
        "SELECT * FROM requesttable WHERE reqorder < :targetorder ORDER BY reqorder DESC LIMIT 1"
    );
    $stmt->bindValue(':targetorder', $targetorder, PDO::PARAM_INT);
    $stmt->execute();
    $neighbor = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($neighbor !== false) {
        $neighbororder = (int)$neighbor['reqorder'];
        // 3ステップスワップ: target と neighbor の reqorder を交換
        $db->beginTransaction();
        // ステップ1: neighbor を一時値に退避
        $stmt = $db->prepare("UPDATE requesttable SET reqorder = :tmpid WHERE id = :nid");
        $stmt->bindValue(':tmpid', $tmpid, PDO::PARAM_INT);
        $stmt->bindValue(':nid', (int)$neighbor['id'], PDO::PARAM_INT);
        $ret = $stmt->execute();
        if (!$ret) { $db->rollBack(); print("一時退避に失敗しました。<br>"); return false; }
        // ステップ2: target を neighbororder へ
        $stmt = $db->prepare("UPDATE requesttable SET reqorder = :neighbororder WHERE id = :id");
        $stmt->bindValue(':neighbororder', $neighbororder, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $ret = $stmt->execute();
        if (!$ret) { $db->rollBack(); print("{$id} の下への移動に失敗しました。<br>"); return false; }
        // ステップ3: 一時値を targetorder へ
        $stmt = $db->prepare("UPDATE requesttable SET reqorder = :targetorder WHERE reqorder = :tmpid");
        $stmt->bindValue(':targetorder', $targetorder, PDO::PARAM_INT);
        $stmt->bindValue(':tmpid', $tmpid, PDO::PARAM_INT);
        $ret = $stmt->execute();
        if (!$ret) { $db->rollBack(); print("復元に失敗しました。<br>"); return false; }
        $db->commit();
    } else {
        print("すでに一番下です。<br>");
    }
}

/**
 * 未再生の直後まで移動
 * @param integer $id
 * @param PDO $db
 */
function warikomi($id, $db)
{
    global $tmpid;
    $ret = true;
    while($ret){
        // 対象のreqorderを取得
        $stmt = $db->prepare("SELECT * FROM requesttable WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            print("id={$id} のレコードが見つかりません。<br>");
            return;
        }
        $targetorder = $row['reqorder'];
        $stmt->closeCursor();

        // 自分より優先が早いリクエストを2つ取得する
        $select = $db->prepare("SELECT * FROM requesttable WHERE reqorder < :targetorder ORDER BY reqorder DESC");
        $select->bindValue(':targetorder', $targetorder, PDO::PARAM_INT);
        $select->execute();
        while($ret){
            $row = $select->fetch(PDO::FETCH_ASSOC);
            if($row === FALSE){
                //現在自分が最優先
                // print 'DEBUG : 現在自分が最優先'.$row['reqorder'].'<br>';
                $select->closeCursor();
                $ret = false;
                break;
            }
            if( $row['nowplaying'] === '再生中'){
                //ちょうど次の番
                // print 'DEBUG : ちょうど次回再生[再生中]'.$row['reqorder'].'<br>';
                $select->closeCursor();
                $ret = false;
                break;
            }
            if($row['nowplaying'] === '未再生' ){
                //未再生を発見
                // print 'DEBUG : 1つ目の未再生を見つけた'.$row['reqorder'].'<br>';
                // print 'DEBUG : call dbdown from warikomi'.$row['reqorder'].'<br>';
                dbdown($id, $db);  // 1つずらす
                $ret = 'continue';
                break;
            }
        }
        if($ret === 'continue') {
            $ret = true;
            continue;
        }
        if($ret === false) break;

        //2つ目を探す
        while($ret){
            $row = $select->fetch(PDO::FETCH_ASSOC);
            if($row === FALSE){
                //ちょうど次の番
                // print 'DEBUG : ちょうど次回再生'.$row['reqorder'].'<br>';
                $select->closeCursor();
                $ret = false;
                break;
            }
            if($row['nowplaying'] === '未再生' || $row['nowplaying'] === '再生中'){
                // 2つ未再生があるので移動する
                $select->closeCursor();
                // print 'DEBUG : call dbdown from warikomi'.$row['reqorder'].'<br>';
                dbdown($id, $db);  // 1つずらす
                break;
            }
        }



    }

}
