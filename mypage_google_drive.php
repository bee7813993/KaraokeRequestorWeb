<?php

class GoogleDriveHelper {
    private $access_token;
    private $refresh_token;
    private $token_expires_at;
    private $relay_url;
    private $relay_secret;
    private $token_refreshed = false;

    public function __construct($access_token, $refresh_token, $token_expires_at, $relay_url, $relay_secret) {
        $this->access_token    = $access_token;
        $this->refresh_token   = $refresh_token;
        $this->token_expires_at = $token_expires_at;
        $this->relay_url       = $relay_url;
        $this->relay_secret    = $relay_secret;
    }

    // 最新トークンを返す（refreshした場合は新しい値）
    public function getNewTokens() {
        return [$this->access_token, $this->token_expires_at, $this->token_refreshed];
    }

    private function ensureValidToken() {
        if (time() >= $this->token_expires_at - 60) {
            $this->refreshToken();
        }
    }

    private function refreshToken() {
        global $config_ini;
        $client_secret = $config_ini['google_client_secret'] ?? '';

        if (!empty($client_secret)) {
            // 中継サーバー自身: 直接リフレッシュ
            $client_id = $config_ini['google_client_id'] ?? '';
            $resp = $this->httpPost('https://oauth2.googleapis.com/token', [
                'client_id'     => $client_id,
                'client_secret' => $client_secret,
                'refresh_token' => $this->refresh_token,
                'grant_type'    => 'refresh_token',
            ]);
        } else {
            // ローカルサーバー: 中継経由でリフレッシュ
            $hmac = hash_hmac('sha256', $this->refresh_token, $this->relay_secret);
            $resp = $this->httpPost(
                $this->relay_url . '?action=refresh',
                json_encode(['refresh_token' => $this->refresh_token, 'hmac' => $hmac]),
                ['Content-Type: application/json']
            );
        }

        $data = json_decode($resp, true);
        if (!empty($data['access_token'])) {
            $this->access_token    = $data['access_token'];
            $this->token_expires_at = time() + (int)($data['expires_in'] ?? 3600);
            $this->token_refreshed = true;
        }
    }

    // Drive appdata から mypage_data.json のファイルIDを検索
    public function findFileId() {
        $url = 'https://www.googleapis.com/drive/v3/files?' . http_build_query([
            'spaces' => 'appDataFolder',
            'q'      => "name='mypage_data.json'",
            'fields' => 'files(id)',
        ]);
        $resp = $this->request('GET', $url);
        $data = json_decode($resp, true);
        return $data['files'][0]['id'] ?? null;
    }

    // Drive からデータを読み込む
    public function readData() {
        $file_id = $this->findFileId();
        if (!$file_id) return null;
        $resp = $this->request('GET', 'https://www.googleapis.com/drive/v3/files/' . urlencode($file_id) . '?alt=media');
        if (!$resp) return null;
        return json_decode($resp, true);
    }

    // Drive にデータを書き込む
    public function writeData($data) {
        $json    = json_encode($data, JSON_UNESCAPED_UNICODE);
        $file_id = $this->findFileId();

        if ($file_id) {
            // 既存ファイルを更新
            $resp = $this->request(
                'PATCH',
                'https://www.googleapis.com/upload/drive/v3/files/' . urlencode($file_id) . '?uploadType=media',
                $json,
                ['Content-Type: application/json']
            );
        } else {
            // 新規作成（multipart）
            $boundary = 'mpboundary_' . bin2hex(random_bytes(8));
            $meta     = json_encode(['name' => 'mypage_data.json', 'parents' => ['appDataFolder']]);
            $body     = "--{$boundary}\r\nContent-Type: application/json; charset=UTF-8\r\n\r\n{$meta}\r\n"
                      . "--{$boundary}\r\nContent-Type: application/json\r\n\r\n{$json}\r\n"
                      . "--{$boundary}--";
            $resp = $this->request(
                'POST',
                'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart',
                $body,
                ["Content-Type: multipart/related; boundary={$boundary}"]
            );
        }
        $decoded = json_decode($resp, true);
        return !empty($decoded['id']) || (isset($decoded['size']) );
    }

    private function request($method, $url, $body = null, $extra_headers = []) {
        $this->ensureValidToken();
        $headers = array_merge(
            ['Authorization: Bearer ' . $this->access_token],
            $extra_headers
        );
        $opts = [
            'http' => [
                'method'        => $method,
                'header'        => implode("\r\n", $headers),
                'ignore_errors' => true,
                'timeout'       => 15,
            ],
            'ssl' => ['verify_peer' => true],
        ];
        if ($body !== null) {
            $opts['http']['content'] = $body;
        }
        return @file_get_contents($url, false, stream_context_create($opts));
    }

    private function httpPost($url, $data, $extra_headers = []) {
        $is_json    = in_array('Content-Type: application/json', $extra_headers);
        $content    = $is_json ? $data : http_build_query($data);
        $def_header = $is_json ? 'Content-Type: application/json' : 'Content-Type: application/x-www-form-urlencoded';
        $headers    = array_merge([$def_header], $extra_headers);
        $headers    = array_unique($headers);
        $opts = [
            'http' => [
                'method'        => 'POST',
                'header'        => implode("\r\n", $headers),
                'content'       => $content,
                'ignore_errors' => true,
                'timeout'       => 10,
            ],
            'ssl' => ['verify_peer' => true],
        ];
        return @file_get_contents($url, false, stream_context_create($opts));
    }
}
