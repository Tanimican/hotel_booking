<?php
//========================================
// 概要   : ユーザー登録処理
// 機能   : アカウント作成、入力チェック、重複チェック
// 作成日 : 2025年
//========================================

require_once 'config.php';

// エラーメッセージと成功メッセージの初期化
$strError = '';
$strSuccess = '';

// POSTリクエストの処理
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $strUsername = trim($_POST['username']);
    $strEmail = trim($_POST['email']);
    $strPassword = $_POST['password'];
    $strConfirmPassword = $_POST['confirm_password'];
    
    // 入力チェック
    if (empty($strUsername) || empty($strEmail) || empty($strPassword)) {
        $strError = 'すべてのフィールドを入力してください。';
    } elseif ($strPassword !== $strConfirmPassword) {
        $strError = 'パスワードが一致しません。';
    } else {
        try {
            // 重複チェック
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$strUsername, $strEmail]);
            
            if ($stmt->rowCount() > 0) {
                $strError = 'ユーザー名またはメールアドレスが既に使用されています。';
            } else {
                // パスワードハッシュ化とユーザー登録
                $strHashedPassword = password_hash($strPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$strUsername, $strEmail, $strHashedPassword]);
                $strSuccess = 'アカウントが作成されました。ログインしてください。';
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
    <title>アカウント作成 - ホテル予約システム</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="nav">
                <h1>ホテル予約システム</h1>
                <div>
                    <a href="index.php">ホーム</a>
                    <a href="login.php">ログイン</a>
                </div>
            </div>
        </div>
    </div>

    <div class="form-container">
        <h2>アカウント作成</h2>
        
        <?php if ($strError): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($strError); ?></div>
        <?php endif; ?>
        
        <?php if ($strSuccess): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($strSuccess); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">ユーザー名:</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="email">メールアドレス:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">パスワード:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">パスワード確認:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn">アカウント作成</button>
        </form>
        
        <p style="text-align: center; margin-top: 20px;">
            既にアカウントをお持ちですか？ <a href="login.php">ログイン</a>
        </p>
    </div>
</body>
</html>