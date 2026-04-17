<?php

require_once('../commonfunc.php');
require_once('../function_moveitem.php');

$list = new MoveItem;
$list->getturnlist($db);

$id = 0;
if (array_key_exists("id", $_REQUEST)) {
    $id = (int)$_REQUEST["id"];
}

echo "<pre>\n=== 現在のターン構成 ===\n";
foreach ($list->turnlist as $ti => $turn) {
    echo "ターン[$ti]:\n";
    foreach ($turn as $r) {
        echo "  reqorder={$r['reqorder']} id={$r['id']} singer={$r['singer']} status={$r['nowplaying']}\n";
    }
}
echo "</pre>\n";

if ($id > 0) {
    $newreq = $list->get_new_reqorder($id);
    echo "<pre>id=$id の新しいreqorder: $newreq</pre>\n";
    $list->insertreqorder($id, $newreq);
    $list->save_allrequest($db);

    // 再読み込みして確認
    $list2 = new MoveItem;
    $list2->getturnlist($db);
    echo "<pre>\n=== 移動後のターン構成 ===\n";
    foreach ($list2->turnlist as $ti => $turn) {
        echo "ターン[$ti]:\n";
        foreach ($turn as $r) {
            echo "  reqorder={$r['reqorder']} id={$r['id']} singer={$r['singer']} status={$r['nowplaying']}\n";
        }
    }
    echo "</pre>\n";
}

?>
