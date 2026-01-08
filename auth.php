<?php
// 認証チェック
// このファイルを各ページの先頭で読み込むことで、ログインチェックを行います

require_once 'config.php';

// ログインページとコールバックページは認証不要
$currentPage = basename($_SERVER['PHP_SELF']);
if ($currentPage === 'login.php' || $currentPage === 'callback.php') {
    return;
}

// ログインチェック
if (!isset($_SESSION['user_email'])) {
    // 未ログインの場合はログインページにリダイレクト
    header('Location: login.php');
    exit;
}

// ホワイトリストチェック
if (!in_array($_SESSION['user_email'], $GLOBALS['WHITELIST'])) {
    // ホワイトリストに登録されていない場合
    session_destroy();
    header('Location: login.php?error=not_authorized');
    exit;
}

// ログイン済みかつホワイトリストに登録されている場合は処理を続行
