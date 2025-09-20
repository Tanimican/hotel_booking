<?php
//========================================
// 概要   : パスワードハッシュ生成用スクリプト
// 機能   : admin123のハッシュを生成
// 作成日 : 2024年
//========================================

$strPassword = 'admin123';
$strHash = password_hash($strPassword, PASSWORD_DEFAULT);

echo "<h2>パスワードハッシュ生成結果</h2>";
echo "<p><strong>パスワード:</strong> " . htmlspecialchars($strPassword) . "</p>";
echo "<p><strong>ハッシュ:</strong> " . htmlspecialchars($strHash) . "</p>";
echo "<hr>";
echo "<h3>データベース更新用SQL:</h3>";
echo "<code>UPDATE users SET password = '" . htmlspecialchars($strHash) . "' WHERE username = 'admin';</code>";
echo "<hr>";
echo "<h3>検証:</h3>";
if (password_verify($strPassword, $strHash)) {
    echo "<p style='color: green;'>✓ ハッシュは正常に生成されました</p>";
} else {
    echo "<p style='color: red;'>✗ ハッシュ生成に問題があります</p>";
}
?>