<?php
// 認証チェック
// このファイルを各ページの先頭で読み込むことで、ログインチェックを行います

require_once 'config.php';

// ログインページと検証エンドポイントは認証不要
$currentPage = basename($_SERVER['PHP_SELF']);
if ($currentPage === 'login.php' || $currentPage === 'verify_token.php') {
    return;
}

// ログインチェック
if (!isset($_SESSION['user_email'])) {
    // 未ログインの場合はログインページにリダイレクト
    header('Location: login.php');
    exit;
}

// Firebase Authenticationで認証されたユーザーはすべてアクセス可能
// （Firebaseコンソールで管理されたユーザーのみログイン可能）
