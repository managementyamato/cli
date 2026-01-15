<?php
/**
 * マネーフォワード クラウド会計 デバッグページ
 * OAuth認証フローのテストと確認
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mf-accounting-api.php';

// 認証チェック
if (!isAdmin()) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/header.php';

$client = new MFAccountingApiClient();

// 設定ファイルの読み込み
$configFile = __DIR__ . '/mf-accounting-config.json';
$config = array();
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true) ?: array();
}

// 現在のリダイレクトURI
$redirectUri = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') .
               '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/mf-accounting-callback.php';

// テスト用のstate
$testState = 'test_' . bin2hex(random_bytes(8));

// OAuth認証URL生成のテスト
$authUrl = '';
try {
    $authUrl = $client->getAuthorizationUrl($redirectUri, $testState);
} catch (Exception $e) {
    $authUrlError = $e->getMessage();
}
?>

<style>
.debug-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
}

.debug-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.debug-section h2 {
    margin-top: 0;
    color: #1976D2;
    border-bottom: 2px solid #1976D2;
    padding-bottom: 10px;
}

.debug-item {
    margin: 15px 0;
    padding: 10px;
    background: #f5f5f5;
    border-radius: 4px;
}

.debug-item strong {
    display: inline-block;
    width: 200px;
    color: #333;
}

.debug-item code {
    background: #e0e0e0;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
    word-break: break-all;
}

.status-ok {
    color: #2E7D32;
    font-weight: bold;
}

.status-error {
    color: #C62828;
    font-weight: bold;
}

.status-warning {
    color: #F57C00;
    font-weight: bold;
}

.url-display {
    background: #fff;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    word-break: break-all;
    font-family: monospace;
    font-size: 12px;
    margin-top: 10px;
}

.btn {
    display: inline-block;
    padding: 10px 20px;
    margin: 5px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    font-size: 14px;
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

.btn-success {
    background: #4CAF50;
    color: white;
}

.btn-success:hover {
    background: #388E3C;
}
</style>

<div class="debug-container">
    <h1>MFクラウド会計 デバッグ情報</h1>

    <!-- 設定ファイルの状態 -->
    <div class="debug-section">
        <h2>1. 設定ファイルの状態</h2>

        <div class="debug-item">
            <strong>設定ファイル:</strong>
            <?php if (file_exists($configFile)): ?>
                <span class="status-ok">✓ 存在する</span>
            <?php else: ?>
                <span class="status-error">✗ 存在しない</span>
            <?php endif; ?>
        </div>

        <div class="debug-item">
            <strong>Client ID:</strong>
            <?php if (!empty($config['client_id'])): ?>
                <span class="status-ok">✓ 設定済み</span>
                <code><?php echo substr($config['client_id'], 0, 10) . '...'; ?></code>
            <?php else: ?>
                <span class="status-error">✗ 未設定</span>
            <?php endif; ?>
        </div>

        <div class="debug-item">
            <strong>Client Secret:</strong>
            <?php if (!empty($config['client_secret'])): ?>
                <span class="status-ok">✓ 設定済み</span>
                <code>*********************</code>
            <?php else: ?>
                <span class="status-error">✗ 未設定</span>
            <?php endif; ?>
        </div>

        <div class="debug-item">
            <strong>Access Token:</strong>
            <?php if (!empty($config['access_token'])): ?>
                <span class="status-ok">✓ 取得済み</span>
                <code><?php echo substr($config['access_token'], 0, 20) . '...'; ?></code>
            <?php else: ?>
                <span class="status-warning">未取得</span>
            <?php endif; ?>
        </div>

        <div class="debug-item">
            <strong>Refresh Token:</strong>
            <?php if (!empty($config['refresh_token'])): ?>
                <span class="status-ok">✓ 取得済み</span>
            <?php else: ?>
                <span class="status-warning">未取得</span>
            <?php endif; ?>
        </div>

        <?php if (!empty($config['updated_at'])): ?>
        <div class="debug-item">
            <strong>最終更新:</strong>
            <code><?php echo htmlspecialchars($config['updated_at']); ?></code>
        </div>
        <?php endif; ?>
    </div>

    <!-- OAuth設定の確認 -->
    <div class="debug-section">
        <h2>2. OAuth設定の確認</h2>

        <div class="debug-item">
            <strong>認証エンドポイント:</strong>
            <code>https://api.biz.moneyforward.com/authorize</code>
        </div>

        <div class="debug-item">
            <strong>トークンエンドポイント:</strong>
            <code>https://api.biz.moneyforward.com/token</code>
        </div>

        <div class="debug-item">
            <strong>APIエンドポイント:</strong>
            <code>https://accounting.moneyforward.com/api/external/v1</code>
        </div>

        <div class="debug-item">
            <strong>リダイレクトURI:</strong>
            <div class="url-display"><?php echo htmlspecialchars($redirectUri); ?></div>
            <small style="color: #666;">※ MF側の設定と完全一致している必要があります</small>
        </div>

        <div class="debug-item">
            <strong>Scope:</strong>
            <code>office.accounting.read office.accounting.write</code>
        </div>
    </div>

    <!-- 生成されたOAuth URL -->
    <div class="debug-section">
        <h2>3. 生成されたOAuth認証URL</h2>

        <?php if (!empty($authUrl)): ?>
            <div class="debug-item">
                <strong>完全なURL:</strong>
                <div class="url-display"><?php echo htmlspecialchars($authUrl); ?></div>
            </div>

            <div class="debug-item">
                <strong>URLパラメータ:</strong>
                <?php
                $parsedUrl = parse_url($authUrl);
                parse_str($parsedUrl['query'] ?? '', $params);
                ?>
                <ul>
                    <?php foreach ($params as $key => $value): ?>
                        <li><strong><?php echo htmlspecialchars($key); ?>:</strong>
                            <code><?php echo htmlspecialchars($value); ?></code>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div style="margin-top: 20px;">
                <a href="<?php echo htmlspecialchars($authUrl); ?>" class="btn btn-success" target="_blank">
                    このURLでOAuth認証をテスト
                </a>
            </div>
        <?php elseif (!empty($authUrlError)): ?>
            <div class="debug-item">
                <span class="status-error">✗ URL生成エラー:</span>
                <code><?php echo htmlspecialchars($authUrlError); ?></code>
            </div>
        <?php endif; ?>
    </div>

    <!-- APIリクエストテスト -->
    <?php if (!empty($config['access_token'])): ?>
    <div class="debug-section">
        <h2>4. API接続テスト</h2>

        <div class="debug-item">
            <strong>アクセストークン:</strong>
            <span class="status-ok">✓ 利用可能</span>
        </div>

        <?php
        $testResults = array();

        // 事業所一覧の取得テスト
        try {
            $offices = $client->getOffices();
            $testResults['offices'] = array(
                'status' => 'success',
                'message' => '事業所一覧の取得に成功しました',
                'data' => $offices
            );
        } catch (Exception $e) {
            $testResults['offices'] = array(
                'status' => 'error',
                'message' => $e->getMessage()
            );
        }
        ?>

        <div class="debug-item">
            <strong>事業所一覧の取得:</strong>
            <?php if ($testResults['offices']['status'] === 'success'): ?>
                <span class="status-ok">✓ 成功</span>
                <pre style="margin-top: 10px; background: #fff; padding: 10px; border: 1px solid #ddd; border-radius: 4px; overflow-x: auto;"><?php echo htmlspecialchars(json_encode($testResults['offices']['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
            <?php else: ?>
                <span class="status-error">✗ 失敗</span>
                <code><?php echo htmlspecialchars($testResults['offices']['message']); ?></code>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- アクション -->
    <div class="debug-section">
        <h2>アクション</h2>
        <a href="mf-accounting-settings.php" class="btn btn-primary">設定画面に戻る</a>
        <a href="mf-accounting-callback.php" class="btn btn-secondary">OAuth認証を開始</a>
        <a href="settings.php" class="btn btn-secondary">メインメニュー</a>
        <?php if (file_exists($configFile)): ?>
            <a href="?action=reload" class="btn btn-secondary">このページを再読み込み</a>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
