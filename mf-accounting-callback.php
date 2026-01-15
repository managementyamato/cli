<?php
/**
 * マネーフォワード クラウド会計 OAuth コールバック
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mf-accounting-api.php';

// 認証チェック
if (!isAdmin()) {
    header('Location: index.php');
    exit;
}

$client = new MFAccountingApiClient();

// 再認証フラグ
$reauth = isset($_GET['reauth']) && $_GET['reauth'] === '1';

// Step 1: 認証コードがない場合は、認証URLにリダイレクト
if (!isset($_GET['code'])) {
    $redirectUri = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') .
                   '://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

    $state = bin2hex(random_bytes(16));
    $_SESSION['mf_accounting_oauth_state'] = $state;

    $authUrl = $client->getAuthorizationUrl($redirectUri, $state);
    header('Location: ' . $authUrl);
    exit;
}

// Step 2: 認証コードを受け取った場合、アクセストークンを取得
$error = null;
$success = false;

try {
    // stateパラメータの検証
    if (!isset($_SESSION['mf_accounting_oauth_state']) ||
        !isset($_GET['state']) ||
        $_GET['state'] !== $_SESSION['mf_accounting_oauth_state']) {
        throw new Exception('不正なリクエストです（state不一致）');
    }

    $code = $_GET['code'];
    $redirectUri = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') .
                   '://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

    $tokenData = $client->handleCallback($code, $redirectUri);
    $success = true;

    // stateをクリア
    unset($_SESSION['mf_accounting_oauth_state']);

} catch (Exception $e) {
    $error = $e->getMessage();
}

require_once __DIR__ . '/header.php';
?>

<div class="container">
    <h1>マネーフォワード クラウド会計 OAuth認証</h1>

    <?php if ($success): ?>
        <div class="result-box success">
            <h2>✓ 認証成功</h2>
            <p>マネーフォワード クラウド会計との連携が完了しました。</p>
            <p>アクセストークンを取得し、保存しました。</p>
        </div>

        <div class="actions">
            <a href="mf-accounting-settings.php" class="btn btn-primary">設定画面に戻る</a>
            <a href="settings.php" class="btn btn-secondary">メインメニュー</a>
        </div>
    <?php elseif ($error): ?>
        <div class="result-box error">
            <h2>✗ 認証失敗</h2>
            <p>OAuth認証中にエラーが発生しました。</p>
            <pre><?php echo htmlspecialchars($error); ?></pre>
        </div>

        <div class="actions">
            <a href="mf-accounting-settings.php" class="btn btn-primary">設定画面に戻る</a>
            <a href="mf-accounting-callback.php" class="btn btn-warning">再試行</a>
        </div>
    <?php endif; ?>
</div>

<style>
.container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.result-box {
    padding: 30px;
    border-radius: 8px;
    margin-bottom: 30px;
    text-align: center;
}

.result-box.success {
    background: #C8E6C9;
    border: 2px solid #4CAF50;
}

.result-box.error {
    background: #FFCDD2;
    border: 2px solid #F44336;
}

.result-box h2 {
    margin: 0 0 15px 0;
}

.result-box.success h2 {
    color: #2E7D32;
}

.result-box.error h2 {
    color: #C62828;
}

.result-box pre {
    background: #fff;
    padding: 15px;
    border-radius: 4px;
    text-align: left;
    overflow-x: auto;
    margin-top: 15px;
}

.actions {
    display: flex;
    gap: 10px;
    justify-content: center;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: #2196F3;
    color: white;
}

.btn-primary:hover {
    background: #1976D2;
}

.btn-secondary {
    background: #757575;
    color: white;
}

.btn-secondary:hover {
    background: #616161;
}

.btn-warning {
    background: #FF9800;
    color: white;
}

.btn-warning:hover {
    background: #F57C00;
}
</style>

<?php require_once __DIR__ . '/footer.php'; ?>
