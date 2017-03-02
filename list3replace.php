<?php
/*** 
    外部リストその３
    なんでもいいのでURLから外部のHTMLを取得。
    TDタグの中が動画ファイルの拡張子で終わっているものをゆかりの検索結果かリクエスト確認画面へのリンクに変える
    
    引数 
    listurl : リストのあるURL.相対パスでも絶対パスでもhttp://からはじまるフルパスでもいいはず
    backconfirm : 戻り先を検索結果ページにするか、リクエスト確認ページにするか
                  1 : リクエスト確認ページに戻る。（リストのファイル名のファイルがなかった時の動作は保証できない（再生時スキップするはず）
                  1以外(もしくは無指定） : ファイル検索結果ページに戻る
    listurlをリンクで記載する場合は URLendodeすること推奨 (http://urlencode.net/ 等を使って)
    
    指定例 「c:\xampp\htdocs\list\2017年秋アニメ.html」をトップページメニューで指定する場合
    http://urlencode.net/ にて 
    「list/2017年秋アニメ.html」
    は
    「list%2F2017%94N%8FH%83A%83j%83%81.html」
    とエンコードされるので
    
    <a href="list3replace.php?listurl=list%2F2017%94N%8FH%83A%83j%83%81.html&backconfirm=0" > 2017年秋アニメ </a>
    と書けば、検索結果ページに戻ってくる。

    <a href="list3replace.php?listurl=list%2F2017%94N%8FH%83A%83j%83%81.html&backconfirm=1" > 2017年秋アニメ </a>
    と書けば、リクエスト確認ページに戻ってくる。
    
    今後のサポート予定
    ・td以外のタグ（ ddとか）対応
    ・HTML内にリンクがあった場合、その先でも使えるようにする
    など、余裕があれば。。。
***/


mb_internal_encoding('UTF-8');
mb_regex_encoding('utf-8');

function return_searchresultlink($word){
// http://localhost/search.php?searchword=Duca
    return 'search.php?searchword='.urlencode($word);
}

function return_requestconfirmlink($word){
// http://localhost/request_confirm.php?filename=aaa.mp4&fullpath=aaa.mp4
    return 'request_confirm.php?filename='.urlencode($word).'&fullpath='.urlencode($word);
}

$listurl = "";
if(array_key_exists("listurl", $_REQUEST)) {
    $listurl_org = $_REQUEST["listurl"];
    if(strncmp($listurl_org, '/', 1) == 0){
        $listurl = $listurl_org;
    }else if(strncmp($listurl_org, 'http://',7) == 0){
        $listurl = $listurl_org;
    }else if(strncmp($listurl_org, 'https://',8) == 0){
        $listurl = $listurl_org;
    }else{
        $listurl = $listurl_org;
    }
}

$backconfirm = 0; //初期値では検索結果に戻る
if(array_key_exists("backconfirm", $_REQUEST)) {
    $backconfirm = $_REQUEST["backconfirm"];
}

if(empty($listurl)){
    print("リストが指定されていません。ブラウザの戻る操作で戻ります");
    die();
}

$html=file_get_contents($listurl);

$regstr = '/<td.*>.*?<\/td>/ui';
$regstr_in = '/<td.*>(.*?)<\/td>/ui';
$extcheck = '/\.mp4$|\.avi$|\.mkv$|\.mpg$|\.flv$/i';
preg_match_all($regstr,$html,$regs);
foreach($regs[0] as $tdtag){
   if(preg_match($regstr_in,$tdtag,$internaltd)){
       $checkword = Trim($internaltd[1]);
       if(preg_match($extcheck,$checkword,$word)){
           if($backconfirm == 1){
               $returnurl = return_requestconfirmlink($checkword);
           }else{
               $returnurl = return_searchresultlink($checkword);
           }
           $afterstr = '<a href="'.$returnurl.'">'.$checkword.'</a>';
           $html = mb_ereg_replace(preg_quote($checkword),$afterstr,$html);
       }
   }
}
print $html;
?>