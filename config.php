<?php
// 設定ファイル

// データファイルのパス
define('DATA_FILE', __DIR__ . '/data.json');

// ログインユーザー情報
// role: 'admin' (管理者 - すべて可能), 'editor' (編集者 - データ編集可能), 'viewer' (閲覧者 - 閲覧のみ)
$USERS = array(
    // 'admin@example.com' => array(
    //     'password' => password_hash('admin_password', PASSWORD_DEFAULT),
    //     'name' => '管理者',
    //     'role' => 'admin'
    // ),
    // 'editor@example.com' => array(
    //     'password' => password_hash('editor_password', PASSWORD_DEFAULT),
    //     'name' => '編集者',
    //     'role' => 'editor'
    // ),
    // 'viewer@example.com' => array(
    //     'password' => password_hash('viewer_password', PASSWORD_DEFAULT),
    //     'name' => '閲覧者',
    //     'role' => 'viewer'
    // ),
);

// 権限チェック関数
function hasPermission($requiredRole) {
    if (!isset($_SESSION['user_role'])) {
        return false;
    }

    $roleHierarchy = array('viewer' => 1, 'editor' => 2, 'admin' => 3);
    $userLevel = $roleHierarchy[$_SESSION['user_role']] ?? 0;
    $requiredLevel = $roleHierarchy[$requiredRole] ?? 999;

    return $userLevel >= $requiredLevel;
}

// 現在のユーザーが管理者かチェック
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// 現在のユーザーが編集可能かチェック
function canEdit() {
    return hasPermission('editor');
}

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
