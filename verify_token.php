<?php
// Firebase ID Token検証エンドポイント
require_once 'config.php';

header('Content-Type: application/json');

// POSTリクエストのみ受け付ける
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'method_not_allowed']);
    exit;
}

// リクエストボディを取得
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['idToken'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'missing_token']);
    exit;
}

$idToken = $input['idToken'];

// Firebase REST APIを使用してID Tokenを検証
// https://firebase.google.com/docs/auth/admin/verify-id-tokens#verify_id_tokens_using_a_third-party_jwt_library
$verifyUrl = 'https://www.googleapis.com/identitytoolkit/v3/relyingparty/getAccountInfo?key=' . FIREBASE_API_KEY;

$ch = curl_init($verifyUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['idToken' => $idToken]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'verification_failed']);
    exit;
}

$data = json_decode($response, true);

if (!isset($data['users']) || empty($data['users'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'invalid_token']);
    exit;
}

$user = $data['users'][0];

// ユーザー情報をセッションに保存
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_name'] = $user['displayName'] ?? $user['email'];
$_SESSION['user_picture'] = $user['photoUrl'] ?? '';
$_SESSION['user_uid'] = $user['localId'];

echo json_encode([
    'success' => true,
    'user' => [
        'email' => $user['email'],
        'name' => $user['displayName'] ?? $user['email'],
        'picture' => $user['photoUrl'] ?? ''
    ]
]);
