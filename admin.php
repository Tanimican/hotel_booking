<?php
//========================================
// 概要   : 管理者画面
// 機能   : 統計表示、部屋管理、予約管理
// 作成日 : 2025年
//========================================

require_once 'config.php';

// 管理者権限チェック
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit;
}

// メッセージの初期化
$strMessage = '';

// 予約ステータス更新処理
if (isset($_POST['update_status'])) {
    $intBookingId = $_POST['booking_id'];
    $strStatus = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->execute([$strStatus, $intBookingId]);
    $strMessage = '予約ステータスを更新しました。';
}

// 部屋追加処理
if (isset($_POST['add_room'])) {
    $strName = trim($_POST['room_name']);
    $strDescription = trim($_POST['room_description']);
    $dblPrice = (float)$_POST['room_price'];
    $intMaxGuests = (int)$_POST['max_guests'];
    
    // 入力チェックと部屋登録
    if (!empty($strName) && $dblPrice > 0 && $intMaxGuests > 0) {
        $stmt = $pdo->prepare("INSERT INTO rooms (name, description, price, max_guests) VALUES (?, ?, ?, ?)");
        $stmt->execute([$strName, $strDescription, $dblPrice, $intMaxGuests]);
        $strMessage = '部屋を追加しました。';
    }
}

// 統計データ取得
$arrStats = [];
$stmt = $pdo->query("SELECT COUNT(*) as total_bookings FROM bookings");
$arrStats['total_bookings'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE is_admin = FALSE");
$arrStats['total_users'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as total_rooms FROM rooms");
$arrStats['total_rooms'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT SUM(total_price) as total_revenue FROM bookings WHERE status = 'confirmed'");
$arrStats['total_revenue'] = $stmt->fetchColumn() ?: 0;

// 全予約一覧を取得（ユーザー名、部屋名も結合）
$stmt = $pdo->query("
    SELECT b.*, r.name as room_name, u.username 
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.id 
    JOIN users u ON b.user_id = u.id 
    ORDER BY b.created_at DESC
");
$arrAllBookings = $stmt->fetchAll();

// 部屋一覧を取得（料金順でソート）
$stmt = $pdo->query("SELECT * FROM rooms ORDER BY price");
$arrRooms = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理画面 - ホテル予約システム</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="nav">
                <h1>管理画面</h1>
                <div>
                    <a href="index.php">ホーム</a>
                    <a href="logout.php">ログアウト</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($strMessage): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($strMessage); ?></div>
        <?php endif; ?>

        <!-- 統計情報 -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
                <h3>総予約数</h3>
                <p style="font-size: 24px; font-weight: bold; color: #007bff;"><?php echo $arrStats['total_bookings']; ?></p>
            </div>
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
                <h3>登録ユーザー数</h3>
                <p style="font-size: 24px; font-weight: bold; color: #28a745;"><?php echo $arrStats['total_users']; ?></p>
            </div>
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
                <h3>部屋数</h3>
                <p style="font-size: 24px; font-weight: bold; color: #ffc107;"><?php echo $arrStats['total_rooms']; ?></p>
            </div>
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
                <h3>総売上</h3>
                <p style="font-size: 24px; font-weight: bold; color: #dc3545;">¥<?php echo number_format($arrStats['total_revenue']); ?></p>
            </div>
        </div>

        <!-- 部屋追加フォーム -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin: 20px 0;">
            <h3>新しい部屋を追加</h3>
            <form method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
                <div class="form-group">
                    <label for="room_name">部屋名:</label>
                    <input type="text" id="room_name" name="room_name" required>
                </div>
                <div class="form-group">
                    <label for="room_description">説明:</label>
                    <input type="text" id="room_description" name="room_description">
                </div>
                <div class="form-group">
                    <label for="room_price">料金（1泊）:</label>
                    <input type="number" id="room_price" name="room_price" min="0" step="100" required>
                </div>
                <div class="form-group">
                    <label for="max_guests">最大宿泊人数:</label>
                    <input type="number" id="max_guests" name="max_guests" min="1" max="10" required>
                </div>
                <button type="submit" name="add_room" class="btn">追加</button>
            </form>
        </div>

        <!-- 部屋一覧 -->
        <h3>部屋一覧</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>部屋名</th>
                    <th>説明</th>
                    <th>料金</th>
                    <th>最大宿泊人数</th>
                    <th>作成日</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($arrRooms as $objRoom): ?>
                    <tr>
                        <td><?php echo $objRoom['id']; ?></td>
                        <td><?php echo htmlspecialchars($objRoom['name']); ?></td>
                        <td><?php echo htmlspecialchars($objRoom['description']); ?></td>
                        <td>¥<?php echo number_format($objRoom['price']); ?></td>
                        <td><?php echo $objRoom['max_guests']; ?>名</td>
                        <td><?php echo date('Y/m/d', strtotime($objRoom['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- 予約管理 -->
        <h3>予約管理</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ユーザー</th>
                    <th>部屋</th>
                    <th>チェックイン</th>
                    <th>チェックアウト</th>
                    <th>宿泊人数</th>
                    <th>料金</th>
                    <th>ステータス</th>
                    <th>予約日</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($arrAllBookings as $objBooking): ?>
                    <tr>
                        <td><?php echo $objBooking['id']; ?></td>
                        <td><?php echo htmlspecialchars($objBooking['username']); ?></td>
                        <td><?php echo htmlspecialchars($objBooking['room_name']); ?></td>
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
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="booking_id" value="<?php echo $objBooking['id']; ?>">
                                <select name="status" onchange="this.form.submit()" style="font-size: 12px;">
                                    <option value="pending" <?php echo $objBooking['status'] == 'pending' ? 'selected' : ''; ?>>予約中</option>
                                    <option value="confirmed" <?php echo $objBooking['status'] == 'confirmed' ? 'selected' : ''; ?>>確定</option>
                                    <option value="cancelled" <?php echo $objBooking['status'] == 'cancelled' ? 'selected' : ''; ?>>キャンセル</option>
                                </select>
                                <input type="hidden" name="update_status" value="1">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>