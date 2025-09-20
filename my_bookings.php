<?php
//========================================
// 概要   : 予約履歴表示・管理
// 機能   : 予約一覧表示、キャンセル処理
// 作成日 : 2025年
//========================================

require_once 'config.php';

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 予約キャンセル処理
if (isset($_POST['cancel_booking'])) {
    $intBookingId = $_POST['booking_id'];
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ?");
    $stmt->execute([$intBookingId, $_SESSION['user_id']]);
}

// ユーザーの予約一覧を取得（部屋情報も結合）
$stmt = $pdo->prepare("
    SELECT b.*, r.name as room_name, r.description as room_description 
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.id 
    WHERE b.user_id = ? 
    ORDER BY b.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$arrBookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>予約履歴 - ホテル予約システム</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="nav">
                <h1>ホテル予約システム</h1>
                <div>
                    <a href="index.php">ホーム</a>
                    <a href="logout.php">ログアウト</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <h2>予約履歴</h2>
        
        <?php if (empty($arrBookings)): ?>
            <p>予約履歴がありません。</p>
            <a href="index.php" class="btn">予約する</a>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>部屋</th>
                        <th>チェックイン</th>
                        <th>チェックアウト</th>
                        <th>宿泊人数</th>
                        <th>合計料金</th>
                        <th>ステータス</th>
                        <th>予約日</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($arrBookings as $objBooking): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($objBooking['room_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($objBooking['room_description']); ?></small>
                            </td>
                            <td><?php echo date('Y/m/d', strtotime($objBooking['check_in'])); ?></td>
                            <td><?php echo date('Y/m/d', strtotime($objBooking['check_out'])); ?></td>
                            <td><?php echo $objBooking['guests']; ?>名</td>
                            <td>¥<?php echo number_format($objBooking['total_price']); ?></td>
                            <td>
                                <?php
                                // ステータス表示用の配列
                                $arrStatusText = [
                                    'pending' => '予約中',
                                    'confirmed' => '確定',
                                    'cancelled' => 'キャンセル'
                                ];
                                echo $arrStatusText[$objBooking['status']] ?? $objBooking['status'];
                                ?>
                            </td>
                            <td><?php echo date('Y/m/d H:i', strtotime($objBooking['created_at'])); ?></td>
                            <td>
                                <?php if ($objBooking['status'] == 'pending' && strtotime($objBooking['check_in']) > time()): ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('本当にキャンセルしますか？')">
                                        <input type="hidden" name="booking_id" value="<?php echo $objBooking['id']; ?>">
                                        <button type="submit" name="cancel_booking" class="btn btn-danger" style="font-size: 12px; padding: 5px 10px;">キャンセル</button>
                                    </form>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>