<?php
require_once 'config.php';

// すでにログイン済みの場合はトップページにリダイレクト
if (isset($_SESSION['user_email'])) {
    header('Location: index.php');
    exit;
}

// エラーメッセージ
$error = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'verification_failed') {
        $error = '認証に失敗しました。もう一度お試しください。';
    } else if ($_GET['error'] === 'not_authorized') {
        $error = 'このアカウントはアクセスが許可されていません。Firebaseコンソールでユーザーを追加してください。';
    } else {
        $error = 'ログインに失敗しました。もう一度お試しください。';
    }
}
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

        .google-login-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
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

        .loading {
            display: none;
            margin-top: 1rem;
            color: #6b7280;
            font-size: 0.875rem;
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

        <?php if (empty(FIREBASE_API_KEY)): ?>
            <div class="setup-notice">
                <strong>Firebase設定が必要です</strong>
                <ol>
                    <li>Firebase Console (console.firebase.google.com) でプロジェクトを作成</li>
                    <li>Authentication > Sign-in method で Google を有効化</li>
                    <li>プロジェクト設定から API Key, Auth Domain, Project ID を取得</li>
                    <li>config.php に Firebase 設定を追加</li>
                </ol>
            </div>
        <?php else: ?>
            <button id="googleLoginBtn" class="google-login-btn">
                <svg class="google-icon" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Googleでログイン
            </button>
            <div class="loading" id="loading">ログイン中...</div>
        <?php endif; ?>
    </div>

    <?php if (!empty(FIREBASE_API_KEY)): ?>
    <!-- Firebase JavaScript SDK -->
    <script type="module">
        import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';
        import { getAuth, signInWithPopup, GoogleAuthProvider } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';

        // Firebase設定
        const firebaseConfig = {
            apiKey: "<?= FIREBASE_API_KEY ?>",
            authDomain: "<?= FIREBASE_AUTH_DOMAIN ?>",
            projectId: "<?= FIREBASE_PROJECT_ID ?>"
        };

        // Firebase初期化
        const app = initializeApp(firebaseConfig);
        const auth = getAuth(app);
        const provider = new GoogleAuthProvider();

        // ログインボタン
        const loginBtn = document.getElementById('googleLoginBtn');
        const loading = document.getElementById('loading');

        loginBtn.addEventListener('click', async () => {
            loginBtn.disabled = true;
            loading.style.display = 'block';

            try {
                // Googleログインポップアップ表示
                const result = await signInWithPopup(auth, provider);
                const user = result.user;

                // ID Tokenを取得
                const idToken = await user.getIdToken();

                // サーバーにID Tokenを送信して検証
                const response = await fetch('verify_token.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ idToken: idToken })
                });

                const data = await response.json();

                if (data.success) {
                    // ログイン成功
                    window.location.href = 'index.php';
                } else {
                    // ログイン失敗
                    window.location.href = 'login.php?error=' + (data.error || 'unknown');
                }
            } catch (error) {
                console.error('ログインエラー:', error);
                loginBtn.disabled = false;
                loading.style.display = 'none';
                alert('ログインに失敗しました: ' + error.message);
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
