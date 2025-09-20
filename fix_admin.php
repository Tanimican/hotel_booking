<?php
//========================================
// 概要   : 管理者アカウント修正スクリプト
// 機能   : 正しいパスワードハッシュでadminアカウントを更新
// 作成日 : 2024年
//========================================

require_once 'config.php';

// 正しいパスワードハッシュを生成
$strPassword = 'admin123';
$strCorrectHash = password_hash($strPassword, PASSWORD_DEFAULT);

try {
    // 管理者アカウントのパスワードを更新
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $stmt->execute([$strCorrectHash]);
    
    echo "<h2>✅ 管理者アカウントを修正しました</h2>";
    echo "<p><strong>ユーザー名:</strong> admin</p>";
    echo "<p><strong>パスワード:</strong> admin123</p>";
    echo "<p><strong>新しいハッシュ:</strong> " . htmlspecialchars($strCorrectHash) . "</p>";
    
    // 検証
    $stmt = $pdo->prepare("SELECT username, password, is_admin FROM users WHERE username = 'admin'");
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user && password_verify($strPassword, $user['password'])) {
        echo "<p style='color: green;'>✅ パスワード検証成功！ログインできるはずです。</p>";
    } else {
        echo "<p style='color: red;'>❌ まだ問題があります。</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>