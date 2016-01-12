<?php
//
// ニコニコ動画ダウンロードクラス
//
class NicoDownload
{
    //
    // プロパティ
    //
 
    // ニコニコ動画アカウントのメールアドレス
    public $LoginEmail = null;
 
    // ニコニコ動画アカウントのパスワード
    public $LoginPassword = null;
 
    // ダウンロードディレクトリ（要書き込み権限）
    public $DownloadDir = './';
 
    // 作業用ディレクトリ（要書き込み権限）
    public $WorkDir = './';
    
    public $DownloadStatus = array();
 
    //
    // ニコニコ動画ダウンロード
    //
    // [params]
    //  videoId : 動画ID
    //  fileName : ダウンロードファイルパス（未指定時は"動画ID_タイトル.拡張子"）
    // [return] 動画情報、ただし失敗時はfalse
    //  filePath : ダウンロードファイルパス
    //  title : タイトル
    //  description : 説明
    //  tags : タグ
    public function Download($videoId, $fileName = null)
    {
    if (setlocale(LC_ALL, 'ja_JP.UTF-8','Japanese_Japan.932') === false) {
    print('Locale not found: ja_JP.UTF-8');
    exit(1);
}
        // パラメータチェック
        
        if (!$this->CheckProperty()) return false;
        if (empty($videoId)) return false;
        
        $videoId = $this->CheckVideoID($videoId);
        if($videoId === false ) return false;
 
 
        // ヘッダーを作成（基本的には不要らしい）
        $headers = array(
            'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.1) Gecko/2008070208 Firefox/3.0.1',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-us,en;q=0.8,de;q=0.6,ja;q=0.4,id;q=0.2',
            'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'Keep-Alive: 300',
            'Connection: keep-alive',
            'Referer: http://www.nicovideo.jp/'
        );
 
        // 作業用ファイル変数の宣言
        $filePathCookie = $this->WorkDir . 'cookie';
        $filePathInfo = $this->WorkDir . md5(uniqid(rand(), true));
        $filePathDL = null;
 
        // curl の初期化
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $filePathCookie);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $filePathCookie);
 
        // 動画を指定して、ログイン処理ログインする
        // curl_execによる標準出力は表示せずにクリアする
        curl_setopt($ch, CURLOPT_URL, "https://secure.nicovideo.jp/secure/login?site=niconico");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array(
            'mail' => $this->LoginEmail,
            'password' => $this->LoginPassword,
            'next_url' => 'http://www.nicovideo.jp/watch/' . $videoId)
        );
        $response = curl_exec($ch);
        if($response === false){
          print curl_error($ch);
        }
        
        
        // 動画情報の取得
        
//        $info = $this->GetFileInfo($response);
        $info = $this->GetFileInfo2($videoId);
        if (!$info) {
            $info = array(
                'title' => '',
                'description' => '',
                'tags' => array(),
            );
        }
// var_dump($info);
 
        // セッションを持続させるため、curlのハンドラーを再利用して、APIのURLを呼び出す
        curl_setopt($ch, CURLOPT_URL, 'http://flapi.nicovideo.jp/api/getflv');
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array(
            'v' => $videoId
        ));
 
        $response = curl_exec($ch);
        if($response === false){
          print curl_error($ch);
        }
 
        // ビデオのダウンロードURLを割り出す
        $movieurl = $this->GetdownloadURL($response);
        if (empty($movieurl)) {
              if (file_exists($filePathCookie)) unlink($filePathCookie);
            curl_close($ch);
            return false;
        }
//        var_dump($this);
 
        // 次のステップの為、HTTPメソッドとパラメータをリセットする
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array());
 
        // 作業用ディレクトリにビデオをダウンロードする
        // データはCURLOPT_FILEで指定したファイルへ書き出す
        // さらに、CURLOPT_VERBOSEを有効にして詳細情報を、CURLOPT_STDERRで指定したファイルへ書きだす
 
        curl_setopt($ch, CURLOPT_URL, $movieurl);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        $fpTmp = @fopen($filePathInfo, 'w');
        $rtValue = false;
 
        if ($fpTmp) {
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_STDERR, $fpTmp);
 
            // 作業用ディレクトリにビデオファイルを保存する
            $filePathDL = mb_convert_encoding($this->WorkDir . $videoId,'SJIS-win','UTF-8');
            $fp = @fopen($filePathDL, 'wb');
            if ($fp) {
                set_time_limit (900);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT_MS, 600000);
                curl_setopt($ch, CURLOPT_FILE, $fp);
 
                if (curl_exec($ch)) {
                    $rtValue = true;
                }else{
                    print curl_error($ch);
                }
                $this->DownloadStatus = curl_getinfo($ch);
 
                // CURLOPT_FILEを使用した場合、ファイルハンドルを閉じる前にcloseする
                curl_close($ch);
 
                fclose($fp);
 
                $rtValue = true;
            }
            fclose($fpTmp);
        }
 
        // 失敗時は、curlが閉じられていないので、closeする
        if (!$rtValue) {
            curl_close($ch);
        }
        else {
            // ダウンロードしたビデオファイルのContent-Typeを取得
            $response = file_get_contents($filePathInfo);
            $contentType = null;
            $posKey = '< Content-Type:';
            $posSt = stripos($response, $posKey);
            if ($posSt !== false) {
                $posSt += strlen($posKey);
                $posEd = strpos($response, "\n", $posSt);
                if ($posEd === false) $posEd = strlen($response);
                $contentType = substr($response, $posSt, $posEd - $posSt);
                $contentType = strtolower(trim($contentType));
            }
 
            // ビデオの拡張子を取得
            $fileExtension = 'flv';
            switch ($contentType) {
                case 'video/3gpp':
                    $fileExtension = '3gp';
                    break;
                case 'video/mp4':
                    $fileExtension = 'mp4';
                    break;
                case 'video/x-flv':
                default:
                    break;
            }
 
            // ダウンロードしたビデオに名前をつけて移動
            $filePath = $this->DownloadDir;
            if (!empty($fileName)) {
                $filePath .= $fileName;
            }
            else {
                $title=  str_replace(array('/','\\', '?', ':', '*', '\"', '>', '<', '|'),array('／','￥','？','：','＊','”','＞','＜','｜'), $info['title']);
                $filePath .= $videoId . '_' . $title . '.' . $fileExtension;
            }
 
            rename($filePathDL, mb_convert_encoding($filePath,'SJIS-win','UTF-8'));
 
            // ダウンロードしたファイルの動画情報を返すようにする
            $rtValue = $info;
            $rtValue['filePath'] = $filePath;
        }
 
        // 作業用ファイルの削除
        if (file_exists($filePathCookie)) unlink($filePathCookie);
        if (file_exists($filePathInfo)) unlink($filePathInfo);
        if (file_exists($filePathDL)) unlink($filePathDL);
 
        return $rtValue;
    }
 
    //
    // プロパティチェック
    //
    private function CheckProperty()
    {
        if (empty($this->LoginEmail)) return false;
        if (empty($this->LoginPassword)) return false;
 
        return true;
    }
 
    //
    // 動画情報の解析
    //
    // [params]
    //  data : 解析データ（http://www.nicovideo.jp/watch/XXXXのページデータ）
    // [return] 動画情報、ただし失敗時はfalse
    //  title : タイトル
    //  description : 説明
    //  tag : タグ
    private function GetFileInfo($data)
    {
        // タイトルの取得
        $title = '';
        $posKey = '<p class="video_title"';
        $posSt = strpos($data, $posKey);
        if ($posSt !== false) {
            $posSt += strlen($posKey);
            $posEd = strpos($data, '</p>', $posSt);
            if ($posEd === false) $posEd = strlen($data);
            $wk = substr($data, $posSt, $posEd - $posSt);
 
            $posKey = '<!-- google_ad_section_start -->';
            $posSt = strpos($wk, $posKey);
            if ($posSt !== false) {
                $posSt += strlen($posKey);
                $posEd = strpos($wk, '<!-- google_ad_section_end -->', $posSt);
                if ($posEd === false) $posEd = strlen($wk);
                $title = substr($wk, $posSt, $posEd - $posSt);
                $title = str_replace(array("\r\n", "\r", "\n"), '', $title);
            }
        }
 
        // 説明の取得
        $description = '';
        $posKey = '<div id="itab_description"';
        $posSt = strpos($data, $posKey);
        if ($posSt !== false) {
            $posSt += strlen($posKey);
            $posEd = strpos($data, '</div>', $posSt);
            if ($posEd === false) $posEd = strlen($data);
            $wk = substr($data, $posSt, $posEd - $posSt);
 
            $posKey = '<!-- google_ad_section_start -->';
            $posSt = strpos($wk, $posKey);
            if ($posSt !== false) {
                $posSt += strlen($posKey);
                $posEd = strpos($wk, '<!-- google_ad_section_end -->', $posSt);
                if ($posEd === false) $posEd = strlen($wk);
                $description = substr($wk, $posSt, $posEd - $posSt);
                $description = str_replace(array("\r\n", "\r", "\n"), '', $description);
            }
        }
 
        // タグの取得
        $tags = array();
        $posKey = '<div id="video_tags"';
        $posSt = strpos($data, $posKey);
        if ($posSt !== false) {
            $posSt += strlen($posKey);
            $posEd = strpos($data, '</div>', $posSt);
            if ($posEd === false) $posEd = strlen($data);
            $wk = substr($data, $posSt, $posEd - $posSt);
 
            $posKey = '<!-- google_ad_section_start -->';
            $posSt = strpos($wk, $posKey);
            if ($posSt !== false) {
                $posSt += strlen($posKey);
                $posEd = strpos($wk, '<!-- google_ad_section_end -->', $posSt);
                if ($posEd === false) $posEd = strlen($wk);
                $wk = substr($wk, $posSt, $posEd - $posSt);
 
                $posSt = 0;
                for ($i = 0; $i < 20; $i++) {
                    $posKey = '<a ';
                    $posSt = strpos($wk, $posKey, $posSt);
                    if ($posSt === false) break;
                    $posSt += strlen($posKey);
 
                    $posKey = '>';
                    $posSt = strpos($wk, $posKey, $posSt);
                    if ($posSt === false) break;
                    $posSt += strlen($posKey);
 
                    $posEd = strpos($wk, '</a>', $posSt);
                    if ($posSt === false) break;
                    $tags[] = substr($wk, $posSt, $posEd - $posSt);
                }
 
                foreach ($tags as $k => $tag) {
                    $tag = str_replace(array("\r\n", "\r", "\n"), '', $tag);
                    $tag = trim($tag);
                    $tags[$k] = $tag;
                }
            }
        }
 
        // 結果を作成して返却
        return array(
            'title' => $title,
            'description' => $description,
            'tags' => $tags,
        );
    }
    private function GetFileInfo2($VideoID)
    {
        $headers = array(
            'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.1) Gecko/2008070208 Firefox/3.0.1',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-us,en;q=0.8,de;q=0.6,ja;q=0.4,id;q=0.2',
            'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'Keep-Alive: 300',
            'Connection: keep-alive',
            'Referer: http://www.nicovideo.jp/'
        );
        $filePathCookie = $this->WorkDir . 'cookie';

        $chinfo = curl_init();
        curl_setopt($chinfo, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chinfo, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($chinfo, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($chinfo, CURLOPT_COOKIEJAR, $filePathCookie);
        curl_setopt($chinfo, CURLOPT_COOKIEFILE, $filePathCookie);
        
        curl_setopt($chinfo, CURLOPT_URL, "http://ext.nicovideo.jp/api/getthumbinfo/$VideoID");
        curl_setopt($chinfo, CURLOPT_POST, false);
        curl_setopt($chinfo, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($chinfo, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($chinfo);
        if($response === false){
          print curl_error($chinfo);
        }
        $title = '';
        $description = '';
        $tags = array();
        
                
        $nicoinfo = simplexml_load_string($response);

        $title = $nicoinfo->thumb->title;
        $description = $nicoinfo->thumb->description;
        $tags = $nicoinfo->thumb->tags;
        
        // 結果を作成して返却
        return array(
            'title' => $title,
            'description' => $description,
            'tags' => $tags,
        );        
        
        
    }

    private function CheckVideoID($VideoID)
    {
      if(strpos($VideoID,'/') !== false )
        $VideoID = basename($VideoID);
        
      
      if(ctype_alpha(substr($VideoID,0,2))){
        if(ctype_digit(substr($VideoID,2))){
          return $VideoID;
        }
      }
      print $VideoID;
      return false;
      
    }
    
    private function GetdownloadURL($data){

      parse_str(urldecode($data));
      return $url;

      //old
      preg_match("'url=(.*?)&ms'", urldecode($data), $match);
      return $match[1];
    }

    //********************************************************************************************
    /**
     * @brief       検索
     */
    //********************************************************************************************
     
    private function search($query){
     
        //期間(yyyy-MM-dd HH:mm:ss)
        $from = date('Y-m-d H:i:s', strtotime('-2 day'));//今から2日前
        $to   = date('Y-m-d H:i:s');//今
     
         
        //json生成
        $json = array(
            'query'     => $query,
            'service'   => array('video'),
            'search'    => array('title', 'description', 'tags'),
            'join'      => array('cmsid', 'title', 'start_time'),//ここはいろいろ情報が取れるのでドキュメント参照
            'filters'   => array(),
            'sort_by'   => 'start_time',
            'order' => 'desc',
            'from'      => 0,
            'size'      => 100,
            'issuer'    => 'KaraokeRequestorWeb'//ここは開発するアプリケーションの名前に変えて下しあ
        );
         
        //jsonエンコード
        $data = json_encode($json);
     
        //POST
        $url = 'http://api.search.nicovideo.jp/api/snapshot/';//スナップショット検索APIエンドポイント
        $headers = array('Content-type: application/json');

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        curl_close($curl);
         
        return $response;
    }
        

}