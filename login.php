<?php
//========================================
// 概要   : ユーザーログイン処理
// 機能   : 認証、セッション管理、リダイレクト
// 作成日 : 2025年
//========================================

require_once 'config.php';

// エラーメッセージの初期化
$strError = '';

// POSTリクエストの処理
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $strUsername = trim($_POST['username']);
    $strPassword = $_POST['password'];
    
    // 入力チェック
    if (empty($strUsername) || empty($strPassword)) {
        $strError = 'ユーザー名とパスワードを入力してください。';
    } else {
        try {
            // ユーザー情報取得（ユーザー名またはメールで検索）
            $stmt = $pdo->prepare("SELECT id, username, password, is_admin FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$strUsername, $strUsername]);
            $objUser = $stmt->fetch();
            
            // パスワード認証とセッション設定
            if ($objUser && password_verify($strPassword, $objUser['password'])) {
                $_SESSION['user_id'] = $objUser['id'];
                $_SESSION['username'] = $objUser['username'];
                $_SESSION['is_admin'] = $objUser['is_admin'];
                
                // 管理者か一般ユーザーかでリダイレクト先を分岐
                if ($objUser['is_admin']) {
                    header('Location: admin.php');
                } else {
                    header('Location: index.php');
                }
                exit;
            } else {
                $strError = 'ユーザー名またはパスワードが正しくありません。';
            }
        } catch (PDOException $e) {
            $strError = 'エラーが発生しました。';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン - ホテル予約システム</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="nav">
                <h1>ホテル予約システム</h1>
                <div>
                    <a href="index.php">ホーム</a>
                    <a href="register.php">アカウント作成</a>
                </div>
            </div>
        </div>
    </div>

    <div class="form-container">
        <h2>ログイン</h2>
        
        <?php if ($strError): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($strError); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">ユーザー名またはメールアドレス:</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">パスワード:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn">ログイン</button>
        </form>
        
        <p style="text-align: center; margin-top: 20px;">
            アカウントをお持ちでない方は <a href="register.php">アカウント作成</a>
        </p>
        
        <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 4px;">
            <h4>テスト用アカウント:</h4>
            <p>管理者: admin / admin123</p>
        </div>
    </div>
</body>
</html>