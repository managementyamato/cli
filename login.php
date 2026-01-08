<?php
require_once 'config.php';

// すでにログイン済みの場合はトップページにリダイレクト
if (isset($_SESSION['user_email'])) {
    header('Location: index.php');
    exit;
}

// エラーメッセージ
$error = '';

// ログイン処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'メールアドレスとパスワードを入力してください。';
    } else if (!isset($USERS[$email])) {
        $error = 'メールアドレスまたはパスワードが正しくありません。';
    } else if (!password_verify($password, $USERS[$email]['password'])) {
        $error = 'メールアドレスまたはパスワードが正しくありません。';
    } else {
        // ログイン成功
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $USERS[$email]['name'];
        $_SESSION['user_role'] = $USERS[$email]['role'];
        header('Location: index.php');
        exit;
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
        }

        .logo {
            font-size: 2rem;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .subtitle {
            color: #6b7280;
            margin-bottom: 2rem;
            text-align: center;
        }

        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #374151;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #2563eb;
        }

        .login-btn {
            width: 100%;
            padding: 0.75rem;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }

        .login-btn:hover {
            background: #1d4ed8;
        }

        .setup-notice {
            margin-top: 2rem;
            padding: 1rem;
            background: #fef3c7;
            border-radius: 8px;
            font-size: 0.875rem;
            color: #92400e;
        }

        .setup-notice strong {
            display: block;
            margin-bottom: 0.5rem;
        }

        .setup-notice code {
            display: block;
            background: white;
            padding: 0.5rem;
            border-radius: 4px;
            margin-top: 0.5rem;
            font-family: monospace;
            font-size: 0.75rem;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">現場トラブル管理</div>
        <div class="subtitle">ログイン</div>

        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (empty($USERS)): ?>
            <div class="setup-notice">
                <strong>ユーザー設定が必要です</strong>
                config.phpの$USERS配列にユーザーを追加してください。
                <code>$USERS = array(<br>
    'admin@example.com' => array(<br>
        'password' => password_hash('password', PASSWORD_DEFAULT),<br>
        'name' => '管理者',<br>
        'role' => 'admin'<br>
    ),<br>
);</code>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">メールアドレス</label>
                    <input type="email" id="email" name="email" required autocomplete="email">
                </div>

                <div class="form-group">
                    <label for="password">パスワード</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>

                <button type="submit" class="login-btn">ログイン</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
