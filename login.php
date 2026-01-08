<?php
require_once 'config.php';

// エラーメッセージ
$error = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'not_authorized') {
        $error = 'このアカウントはアクセスが許可されていません。';
    } else {
        $error = 'ログインに失敗しました。もう一度お試しください。';
    }
}

// すでにログイン済みの場合はトップページにリダイレクト
if (isset($_SESSION['user_email']) && in_array($_SESSION['user_email'], $WHITELIST)) {
    header('Location: index.php');
    exit;
}

// Google OAuth 2.0 認証URL生成
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'email profile',
    'state' => $state,
    'access_type' => 'online',
]);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>ログイン - 現場トラブル管理システム</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Hiragino Sans', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 3rem;
            max-width: 400px;
            width: 90%;
            text-align: center;
        }

        .logo {
            font-size: 2rem;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            color: #6b7280;
            margin-bottom: 2rem;
        }

        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }

        .google-login-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            background: white;
            border: 2px solid #e5e7eb;
            color: #374151;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            width: 100%;
        }

        .google-login-btn:hover {
            border-color: #2563eb;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .google-icon {
            width: 20px;
            height: 20px;
        }

        .setup-notice {
            margin-top: 2rem;
            padding: 1rem;
            background: #fef3c7;
            border-radius: 8px;
            font-size: 0.875rem;
            color: #92400e;
            text-align: left;
        }

        .setup-notice strong {
            display: block;
            margin-bottom: 0.5rem;
        }

        .setup-notice ol {
            margin-left: 1.5rem;
            margin-top: 0.5rem;
        }

        .setup-notice li {
            margin-bottom: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">現場トラブル管理</div>
        <div class="subtitle">Googleアカウントでログイン</div>

        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (empty(GOOGLE_CLIENT_ID)): ?>
            <div class="setup-notice">
                <strong>初期設定が必要です</strong>
                <ol>
                    <li>Google Cloud Consoleでプロジェクトを作成</li>
                    <li>OAuth 2.0クライアントIDを取得</li>
                    <li>config.phpに設定を記入</li>
                    <li>ホワイトリストにメールアドレスを追加</li>
                </ol>
            </div>
        <?php else: ?>
            <a href="<?= htmlspecialchars($authUrl) ?>" class="google-login-btn">
                <svg class="google-icon" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Googleでログイン
            </a>
        <?php endif; ?>
    </div>
</body>
</html>
