<?php
// 設定ファイル

// データファイルのパス
define('DATA_FILE', __DIR__ . '/data.json');

// Google OAuth 2.0 設定
// Google Cloud Console で取得したクライアントIDとシークレットを設定してください
// https://console.cloud.google.com/apis/credentials
define('GOOGLE_CLIENT_ID', ''); // ここにGoogle Client IDを設定
define('GOOGLE_CLIENT_SECRET', ''); // ここにGoogle Client Secretを設定
define('GOOGLE_REDIRECT_URI', 'http://yoursite.com/callback.php'); // 本番環境のURLに変更してください

// ホワイトリスト（アクセスを許可するGoogleアカウントのメールアドレス）
// 例: array('user@example.com', 'admin@company.com')
$WHITELIST = array(
    // ここに許可するメールアドレスを追加してください
    // 例: 'your-email@gmail.com',
);

// 初期データ
function getInitialData() {
    return array(
        'projects' => array(),
        'assignees' => array(),
        'troubles' => array(),
        'customers' => array(),
        'partners' => array(),
        'employees' => array(),
        'productCategories' => array(),
        'settings' => array(
            'spreadsheet_url' => ''
        )
    );
}

// データ読み込み
function getData() {
    if (file_exists(DATA_FILE)) {
        $json = file_get_contents(DATA_FILE);
        $data = json_decode($json, true);
        if ($data) {
            // 設定が存在しない場合は初期化
            if (!isset($data['settings'])) {
                $data['settings'] = array(
                    'spreadsheet_url' => ''
                );
            }
            // 新しいマスタが存在しない場合は初期化
            if (!isset($data['customers'])) $data['customers'] = array();
            if (!isset($data['partners'])) $data['partners'] = array();
            if (!isset($data['employees'])) $data['employees'] = array();
            if (!isset($data['productCategories'])) $data['productCategories'] = array();
            return $data;
        }
    }
    return getInitialData();
}

// データ保存
function saveData($data) {
    file_put_contents(DATA_FILE, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

// セッション開始
session_start();
