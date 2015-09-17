<?php 
/**
 * Youtube保存用クラス
 * 存在する全クオリティの動画を一括で保存する。
 * 
 * $yt = new youtubeDownloader();
 * $yt->saveDir = '動画保存用ディレクトリ'; // 末端DS無し
 * $yt->tmpDir = '作業用ディレクトリ';　// 末端DS無し
 * $yt->prefix = 'Youtube_'; // 動画保存時のファイル名先頭プレフィックス文字列
 * $res = $yt->download('*******');
 * 
 * ダウンロード成功時はtrueを返却。一つでもこけていればfalseを返却
 * download()の第二引数にtrueを渡すと保存に成功したファイル名リストを取得できる
 * ※途中でこけていればその時点までで成功しているファイル名を返却
 * 
 * @author Owner
 *
 */
class youtubeDownloader {
     
    // 動画保存ディレクトリ（末端スラッシュ無し）
    public $saveDir = '';
     
    // 一時作業ディレクトリ（末端スラッシュ無し）
    public $tmpDir = '';
     
    // 動画保存時のファイル名に付与するプレフィックス文字列
    public $prefix = 'youtube_';
     
    // video id
    protected $vid = '';
     
    // エラーメッセージの入れ物
    protected $errors = array();
     
    // ダウンロードエラー時に返却するDL済み動画リスト
    protected $successes = array();
     
    // 動画情報の取得APIURL
    protected $movieInfoApi = 'http://www.youtube.com/get_video_info?&video_id=';
     
    /**
     * ダウンロードの実行
     * @param string $mid
     * @return multitype:|boolean
     */
    public function download($vid = null, $_return = false) {
        $this->vid = $vid;
        $this->checkParams();
        if(!empty($this->errors)) return $this->errors;
        $baseTmpWorkPath = $this->tmpDir . DIRECTORY_SEPARATOR . md5(microtime());
        $baseHeaderFilePath = $this->tmpDir . DIRECTORY_SEPARATOR . 'h_' . $this->vid;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_URL, $this->movieInfoApi.$this->vid);
        $output = curl_exec($ch);
        curl_close($ch);
        // 動画保存庫を割り出す
        $properties = $this->parseStr($output);
        if(empty($properties)) goto exception;
        // 動画DLセクション
        foreach($properties as $property) {
            $token = md5(microtime().mt_rand());
            $tmpWorkPath = $baseTmpWorkPath.$token;
            $headerFilePath = $baseHeaderFilePath.$token;
            // 作業ファイルの作成
            self::tp($tmpWorkPath);
            self::tp($headerFilePath);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_URL, $property['url']);
            $execResult = false;
            $hdFp = fopen($headerFilePath, 'w');
            if($hdFp) {
                curl_setopt($ch, CURLOPT_WRITEHEADER, $hdFp);
                $twFp = fopen($tmpWorkPath, 'wb');
                if($twFp) {
                    curl_setopt($ch, CURLOPT_FILE, $twFp);
                    $execResult = curl_exec($ch);
                    curl_close($ch);
                    fclose($twFp);
                }
                fclose($hdFp);
            }
            // エラー処理
            if(!$execResult) goto exception;
            // headerFileの解析（ファイルタイプ、拡張子を取得）
            $contentType = self::parseHeaderFile($headerFilePath);
            $ext = self::getFileExtension($contentType);
            // 実ファイルのパスを指定（お名前指定）
            $separator = '_';
            $filePath = $this->saveDir.DIRECTORY_SEPARATOR.$this->prefix.$this->vid.$separator.$property['itag'].$separator.$property['quality'].$ext;
            // ファイル移動
            rename($tmpWorkPath, $filePath);
            // DL済みリストにファイル名を追加
            array_push($this->successes, basename($filePath));
            // 作業ファイル削除
            self::u($tmpWorkPath);
            self::u($headerFilePath);
        }
        return ($_return) ? $this->successes : true;
        if(false) {
            exception:
            self::u($tmpWorkPath);
            self::u($headerFilePath);
            curl_close($ch);
            return ($_return) ? $this->successes : false;
        }   
    }
     
    /**
     * ファイルの存在確認と作成、及び権限の付与
     * @param unknown $path
     */
    protected function tp($path) {
        if(!file_exists($path)) touch($path);
        chmod($path, 0777);
    }
     
    /**
     * ファイルの存在確認と削除
     * @param unknown $path
     */
    protected function u($path) {
        if(file_exists($path)) unlink($path);
    }
 
    /**
     * パラメーターチェック
     */
    protected function checkParams() {
        if(!is_dir($this->saveDir)) array_push($this->errors, 'saveDir must be a directory.');
        if(fileperms($this->saveDir) != '16895') array_push($this->errors, 'saveDir required to be permission 777.');
        if(!is_dir($this->tmpDir)) array_push($this->errors, 'tmpDir must be a directory.');
        if(fileperms($this->tmpDir) != '16895') array_push($this->errors, 'tmpDir required to be permission 777.');
        if(empty($this->vid)) array_push($this->errors, 'vid is required but not set.');
    }
     
    /**
     * APIより取得した文字列をパースして動画保存庫を特定
     * @param unknown $html
     * @return Ambigous <NULL, string>
     */
    protected function parseStr($str) {
        $properties = array();
        parse_str($str);
        if(!isset($url_encoded_fmt_stream_map)) return $properties;
        $formats = explode(',',$url_encoded_fmt_stream_map);
        if(empty($formats)) return $properties;
        $exp = time();
        foreach($formats as $_key => $format) {
            parse_str($format);
            $properties[$_key]['itag'] = $itag;
            $properties[$_key]['quality'] = $quality;
            $properties[$_key]['type'] = reset(explode(';',$type));
            $decodedUrl = urldecode($url);
            parse_str($decodedUrl);
            $properties[$_key]['url'] = $decodedUrl.'&signature='.$sig;
            $properties[$_key]['expires'] = date("G:i:s T", $exp);
        }
        return $properties;     
    }
     
    /**
     * ヘッダーファイルの情報を解析
     * @param unknown $path
     * @return Ambigous <NULL, string>
     */
    protected function parseHeaderFile($path) {
        $hd = file_get_contents($path);
        $ct = null;
        $k = 'Content-Type:';
        $st = stripos($hd, $k);
        if($st !== false) {
            $st += strlen($k);
            $ed = strpos($hd, "\n", $st);
            if($ed === false) $ed = strlen($hd);
            $l = $ed - $st;
            $ct = strtolower(trim(substr($hd, $st, $l)));
        }
        return $ct; 
    }
     
    /**
     * コンテントタイプより動画ファイルの拡張子を分析し返却
     * @param unknown $ct
     * @return string
     */
    protected function getFileExtension($ct) {
        $e = '';
        switch($ct) {
            case 'video/webm':
                $e = '.webm';
                return $e;
            case 'video/3gpp':
                $e = '.3gp';
                return $e;
            case 'video/mp4':
                $e = '.mp4';
                return $e;
            case 'video/x-flv':
            default:
                $e = '.flv';
                return $e;
        }
    }
}