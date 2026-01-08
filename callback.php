<?php
require_once 'config.php';

// stateパラメータの検証
if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    header('Location: login.php?error=invalid_state');
    exit;
}

// 認証コードの取得
if (!isset($_GET['code'])) {
    header('Location: login.php?error=no_code');
    exit;
}

$code = $_GET['code'];

// アクセストークンの取得
$tokenUrl = 'https://oauth2.googleapis.com/token';
$tokenData = [
    'code' => $code,
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code',
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $tokenUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$tokenResponse = json_decode($response, true);

if (!isset($tokenResponse['access_token'])) {
    header('Location: login.php?error=token_failed');
    exit;
}

$accessToken = $tokenResponse['access_token'];

// ユーザー情報の取得
$userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $userInfoUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$userInfoResponse = curl_exec($ch);
curl_close($ch);

$userInfo = json_decode($userInfoResponse, true);

if (!isset($userInfo['email'])) {
    header('Location: login.php?error=no_email');
    exit;
}

$email = $userInfo['email'];
$name = isset($userInfo['name']) ? $userInfo['name'] : '';
$picture = isset($userInfo['picture']) ? $userInfo['picture'] : '';

// ホワイトリストチェック
if (!in_array($email, $WHITELIST)) {
    header('Location: login.php?error=not_authorized');
    exit;
}

// セッションに保存
$_SESSION['user_email'] = $email;
$_SESSION['user_name'] = $name;
$_SESSION['user_picture'] = $picture;

// トップページにリダイレクト
header('Location: index.php');
exit;
