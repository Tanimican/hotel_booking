<?php
//========================================
// 概要   : ログインデバッグ用スクリプト
// 機能   : 管理者アカウントの存在確認とパスワード検証
// 作成日 : 2024年
//========================================

require_once 'config.php';

echo "<h2>管理者アカウントデバッグ</h2>";

// 管理者アカウントの存在確認
$stmt = $pdo->prepare("SELECT id, username, email, password, is_admin FROM users WHERE username = 'admin' OR email = 'admin@hotel.com'");
$stmt->execute();
$users = $stmt->fetchAll();

echo "<h3>データベース内のadminアカウント:</h3>";
if (empty($users)) {
    echo "<p style='color: red;'>❌ 管理者アカウントが見つかりません</p>";
} else {
    foreach ($users as $user) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
        echo "<p><strong>ID:</strong> " . $user['id'] . "</p>";
        echo "<p><strong>ユーザー名:</strong> " . htmlspecialchars($user['username']) . "</p>";
        echo "<p><strong>メール:</strong> " . htmlspecialchars($user['email']) . "</p>";
        echo "<p><strong>管理者フラグ:</strong> " . ($user['is_admin'] ? 'TRUE' : 'FALSE') . "</p>";
        echo "<p><strong>パスワードハッシュ:</strong> " . htmlspecialchars($user['password']) . "</p>";
        
        // パスワード検証
        $testPassword = 'admin123';
        if (password_verify($testPassword, $user['password'])) {
            echo "<p style='color: green;'>✅ パスワード 'admin123' は正しいです</p>";
        } else {
            echo "<p style='color: red;'>❌ パスワード 'admin123' が一致しません</p>";
        }
        echo "</div>";
    }
}

// 新しいハッシュを生成
echo "<h3>新しいパスワードハッシュ:</h3>";
$newHash = password_hash('admin123', PASSWORD_DEFAULT);
echo "<p><strong>新しいハッシュ:</strong> " . htmlspecialchars($newHash) . "</p>";
echo "<p><strong>更新SQL:</strong></p>";
echo "<code>UPDATE users SET password = '" . htmlspecialchars($newHash) . "' WHERE username = 'admin';</code>";

// 全ユーザー一覧
echo "<h3>全ユーザー一覧:</h3>";
$stmt = $pdo->query("SELECT id, username, email, is_admin FROM users");
$allUsers = $stmt->fetchAll();
foreach ($allUsers as $user) {
    echo "<p>ID: {$user['id']}, ユーザー名: {$user['username']}, メール: {$user['email']}, 管理者: " . ($user['is_admin'] ? 'YES' : 'NO') . "</p>";
}
?>