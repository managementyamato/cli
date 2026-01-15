<?php
/**
 * マネーフォワード クラウド会計 設定画面
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mf-accounting-api.php';

// 認証チェック
if (!isAdmin()) {
    header('Location: index.php');
    exit;
}

$message = '';
$messageType = '';

// Client ID/Secret保存処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_credentials'])) {
    $clientId = trim($_POST['client_id'] ?? '');
    $clientSecret = trim($_POST['client_secret'] ?? '');

    if (!empty($clientId) && !empty($clientSecret)) {
        if (MFAccountingApiClient::saveCredentials($clientId, $clientSecret)) {
            $message = 'Client IDとClient Secretを保存しました。次にOAuth認証を行ってください。';
            $messageType = 'success';
        } else {
            $message = '保存に失敗しました。';
            $messageType = 'error';
        }
    } else {
        $message = 'Client IDとClient Secretを両方入力してください。';
        $messageType = 'error';
    }
}

// 現在の設定を取得
$config = array();
$configFile = __DIR__ . '/mf-accounting-config.json';
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true) ?: array();
}

$isConfigured = MFAccountingApiClient::isConfigured();
$hasCredentials = !empty($config['client_id']) && !empty($config['client_secret']);

require_once __DIR__ . '/header.php';
?>

<div class="container">
    <h1>マネーフォワード クラウド会計 連携設定</h1>

    <?php if ($message): ?>
        <div class="message <?php echo htmlspecialchars($messageType); ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="settings-status">
        <h2>連携状態</h2>
        <p>
            <?php if ($isConfigured): ?>
                <span class="status-badge success">✓ 認証済み</span>
            <?php elseif ($hasCredentials): ?>
                <span class="status-badge warning">認証情報設定済み（OAuth認証が必要）</span>
            <?php else: ?>
                <span class="status-badge warning">未設定</span>
            <?php endif; ?>
        </p>
        <?php if (!empty($config['updated_at'])): ?>
            <p class="last-updated">最終更新: <?php echo htmlspecialchars($config['updated_at']); ?></p>
        <?php endif; ?>
    </div>

    <!-- Step 1: Client ID/Secret -->
    <div class="settings-form">
        <h2>ステップ1: OAuth認証情報の設定</h2>

        <div class="instruction">
            <h3>Client IDとClient Secretの取得方法</h3>
            <ol>
                <li>マネーフォワード クラウド会計の管理画面にログイン</li>
                <li>「設定」→「API連携」を開く</li>
                <li>新しいアプリケーションを登録</li>
                <li><strong>Redirect URI</strong>に以下を設定:<br>
                    <code><?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/mf-accounting-callback.php'; ?></code>
                </li>
                <li>発行された<strong>Client ID</strong>と<strong>Client Secret</strong>をコピー</li>
            </ol>
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label for="client_id">Client ID <span style="color: red;">*</span></label>
                <input
                    type="text"
                    id="client_id"
                    name="client_id"
                    class="form-control"
                    value="<?php echo htmlspecialchars($config['client_id'] ?? ''); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="client_secret">Client Secret <span style="color: red;">*</span></label>
                <input
                    type="text"
                    id="client_secret"
                    name="client_secret"
                    class="form-control"
                    value="<?php echo htmlspecialchars($config['client_secret'] ?? ''); ?>"
                    required
                >
            </div>

            <div class="form-actions">
                <button type="submit" name="save_credentials" class="btn btn-primary">保存</button>
                <a href="settings.php" class="btn btn-secondary">戻る</a>
            </div>
        </form>
    </div>

    <!-- Step 2: OAuth認証 -->
    <?php if ($hasCredentials): ?>
        <div class="settings-form">
            <h2>ステップ2: OAuth認証</h2>
            <p>Client IDとClient Secretを保存したら、OAuth認証を行ってください。</p>

            <?php if ($isConfigured): ?>
                <p class="success-message">✓ OAuth認証が完了しています。</p>
                <div class="form-actions">
                    <a href="mf-accounting-callback.php?reauth=1" class="btn btn-warning">再認証</a>
                </div>
            <?php else: ?>
                <div class="form-actions">
                    <a href="mf-accounting-callback.php" class="btn btn-primary">OAuth認証を開始</a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.settings-status {
    background: #f5f5f5;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.status-badge {
    display: inline-block;
    padding: 5px 15px;
    border-radius: 4px;
    font-weight: bold;
}

.status-badge.success {
    background: #4CAF50;
    color: white;
}

.status-badge.warning {
    background: #FF9800;
    color: white;
}

.last-updated {
    color: #666;
    font-size: 0.9em;
    margin-top: 10px;
}

.settings-form {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.instruction {
    background: #e3f2fd;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
    border-left: 4px solid #2196F3;
}

.instruction h3 {
    margin-top: 0;
    color: #1976D2;
}

.instruction ol {
    margin: 15px 0;
    padding-left: 25px;
}

.instruction li {
    margin: 10px 0;
    line-height: 1.6;
}

.instruction code {
    background: #fff;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
    color: #d32f2f;
    word-break: break-all;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-control:focus {
    outline: none;
    border-color: #2196F3;
    box-shadow: 0 0 0 2px rgba(33, 150, 243, 0.1);
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 30px;
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

.message {
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.message.success {
    background: #C8E6C9;
    color: #2E7D32;
    border: 1px solid #81C784;
}

.message.error {
    background: #FFCDD2;
    color: #C62828;
    border: 1px solid #E57373;
}

.success-message {
    color: #2E7D32;
    font-weight: bold;
}
</style>

<?php require_once __DIR__ . '/footer.php'; ?>
