<?php
// 認証チェック
// このファイルを各ページの先頭で読み込むことで、ログインチェックを行います

require_once 'config.php';

// ログインページは認証不要
$currentPage = basename($_SERVER['PHP_SELF']);
if ($currentPage === 'login.php') {
    return;
}

// ログインチェック
if (!isset($_SESSION['user_email'])) {
    // 未ログインの場合はログインページにリダイレクト
    header('Location: login.php');
    exit;
}

// ユーザーリストに存在するかチェック
if (!isset($GLOBALS['USERS'][$_SESSION['user_email']])) {
    // ユーザーが削除された場合
    session_destroy();
    header('Location: login.php');
    exit;
}
