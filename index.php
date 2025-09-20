<?php
//========================================
// 概要   : ホテル予約システム メインページ
// 機能   : 部屋一覧表示、カレンダー表示、予約状況確認
// 作成日 : 2025年
//========================================

require_once 'config.php';

// 部屋一覧を取得（料金順でソート）
$stmt = $pdo->query("SELECT * FROM rooms ORDER BY price");
$rooms = $stmt->fetchAll();

// 選択された部屋の予約済み日付を取得
$booked_dates = [];
if (isset($_GET['room_id'])) {
    $room_id = $_GET['room_id'];
    $stmt = $pdo->prepare("SELECT check_in, check_out FROM bookings WHERE room_id = ? AND status != 'cancelled'");
    $stmt->execute([$room_id]);
    $bookings = $stmt->fetchAll();
    
    // 予約期間の各日付を配列に追加
    foreach ($bookings as $booking) {
        $start = new DateTime($booking['check_in']);
        $end = new DateTime($booking['check_out']);
        
        while ($start < $end) {
            $booked_dates[] = $start->format('Y-m-d');
            $start->add(new DateInterval('P1D'));
        }
    }
}

// カレンダー表示用の月を設定（デフォルトは現在月）
$current_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ホテル予約システム</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="nav">
                <h1>ホテル予約システム</h1>
                <div>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span>こんにちは、<?php echo htmlspecialchars($_SESSION['username']); ?>さん</span>
                        <a href="my_bookings.php">予約履歴</a>
                        <?php if ($_SESSION['is_admin']): ?>
                            <a href="admin.php">管理画面</a>
                        <?php endif; ?>
                        <a href="logout.php">ログアウト</a>
                    <?php else: ?>
                        <a href="login.php">ログイン</a>
                        <a href="register.php">アカウント作成</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <h2>客室一覧</h2>
        
        <?php foreach ($rooms as $room): ?>
            <div class="room-card">
                <h3><?php echo htmlspecialchars($room['name']); ?></h3>
                <p><?php echo htmlspecialchars($room['description']); ?></p>
                <p><strong>料金:</strong> ¥<?php echo number_format($room['price']); ?>/泊</p>
                <p><strong>最大宿泊人数:</strong> <?php echo $room['max_guests']; ?>名</p>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="?room_id=<?php echo $room['id']; ?>" class="btn">予約カレンダーを表示</a>
                    <?php if (isset($_GET['room_id']) && $_GET['room_id'] == $room['id']): ?>
                        <a href="booking.php?room_id=<?php echo $room['id']; ?>" class="btn">予約する</a>
                    <?php endif; ?>
                <?php else: ?>
                    <p><a href="login.php">ログイン</a>して予約してください。</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <?php if (isset($_GET['room_id'])): ?>
            <div class="calendar">
                <div class="calendar-header">
                    <h3>予約カレンダー - <?php echo date('Y年m月', strtotime($current_month . '-01')); ?></h3>
                    <div>
                        <a href="?room_id=<?php echo $_GET['room_id']; ?>&month=<?php echo date('Y-m', strtotime($current_month . '-01 -1 month')); ?>" class="btn">前月</a>
                        <a href="?room_id=<?php echo $_GET['room_id']; ?>&month=<?php echo date('Y-m', strtotime($current_month . '-01 +1 month')); ?>" class="btn">次月</a>
                    </div>
                </div>
                
                <div class="calendar-grid">
                    <div class="calendar-day"><strong>日</strong></div>
                    <div class="calendar-day"><strong>月</strong></div>
                    <div class="calendar-day"><strong>火</strong></div>
                    <div class="calendar-day"><strong>水</strong></div>
                    <div class="calendar-day"><strong>木</strong></div>
                    <div class="calendar-day"><strong>金</strong></div>
                    <div class="calendar-day"><strong>土</strong></div>
                    
                    <?php
                    // カレンダー表示用の日付計算
                    $first_day = new DateTime($current_month . '-01');
                    $last_day = new DateTime($first_day->format('Y-m-t'));
                    $start_day = clone $first_day;
                    $start_day->modify('last sunday');
                    
                    $current_day = clone $start_day;
                    // カレンダーの各日付を表示
                    while ($current_day <= $last_day || $current_day->format('w') != 0) {
                        $date_str = $current_day->format('Y-m-d');
                        $is_current_month = $current_day->format('Y-m') == $current_month;
                        $is_booked = in_array($date_str, $booked_dates);
                        $is_past = $current_day < new DateTime('today');
                        
                        // CSSクラスを設定
                        $class = 'calendar-day';
                        if ($is_booked || $is_past) $class .= ' booked';
                        if (!$is_current_month) $class .= ' other-month';
                        
                        echo '<div class="' . $class . '">';
                        if ($is_current_month) {
                            echo $current_day->format('j');
                            if ($is_booked) echo '<br><small>予約済</small>';
                        }
                        echo '</div>';
                        
                        $current_day->add(new DateInterval('P1D'));
                        if ($current_day->format('w') == 0 && $current_day > $last_day) break;
                    }
                    ?>
                </div>
                
                <div style="margin-top: 20px;">
                    <p><span style="background: #ffcccc; padding: 2px 8px;">■</span> 予約済み・予約不可</p>
                    <p><span style="background: white; border: 1px solid #ddd; padding: 2px 8px;">■</span> 予約可能</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>