<?php
//========================================
// 概要   : 予約フォーム処理
// 機能   : 部屋予約、日程チェック、料金計算
// 作成日 : 2025年
//========================================

require_once 'config.php';

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// パラメータ取得と初期化
$intRoomId = $_GET['room_id'] ?? 0;
$strError = '';
$strSuccess = '';

// 部屋情報を取得
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->execute([$intRoomId]);
$objRoom = $stmt->fetch();

// 部屋が存在しない場合はメインページへリダイレクト
if (!$objRoom) {
    header('Location: index.php');
    exit;
}

// POSTリクエストの処理
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $strCheckIn = $_POST['check_in'];
    $strCheckOut = $_POST['check_out'];
    $intGuests = (int)$_POST['guests'];
    
    // 入力チェック
    if (empty($strCheckIn) || empty($strCheckOut) || $intGuests < 1) {
        $strError = 'すべてのフィールドを入力してください。';
    } elseif ($strCheckIn >= $strCheckOut) {
        $strError = 'チェックアウト日はチェックイン日より後の日付を選択してください。';
    } elseif ($intGuests > $objRoom['max_guests']) {
        $strError = '宿泊人数が上限を超えています。';
    } else {
        // 予約の重複チェック
        $stmt = $pdo->prepare("SELECT id FROM bookings WHERE room_id = ? AND status != 'cancelled' AND ((check_in <= ? AND check_out > ?) OR (check_in < ? AND check_out >= ?))");
        $stmt->execute([$intRoomId, $strCheckIn, $strCheckIn, $strCheckOut, $strCheckOut]);
        
        if ($stmt->rowCount() > 0) {
            $strError = '選択した日程は既に予約されています。';
        } else {
            // 宿泊日数と料金計算
            $dtmCheckIn = new DateTime($strCheckIn);
            $dtmCheckOut = new DateTime($strCheckOut);
            $intNights = $dtmCheckIn->diff($dtmCheckOut)->days;
            $dblTotalPrice = $intNights * $objRoom['price'];
            
            try {
                // 予約情報をデータベースに登録
                $stmt = $pdo->prepare("INSERT INTO bookings (user_id, room_id, check_in, check_out, guests, total_price) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $intRoomId, $strCheckIn, $strCheckOut, $intGuests, $dblTotalPrice]);
                $strSuccess = '予約が完了しました。';
            } catch (PDOException $e) {
                $strError = 'エラーが発生しました。';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>予約 - <?php echo htmlspecialchars($room['name']); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="nav">
                <h1>ホテル予約システム</h1>
                <div>
                    <a href="index.php">ホーム</a>
                    <a href="my_bookings.php">予約履歴</a>
                    <a href="logout.php">ログアウト</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="form-container" style="max-width: 600px;">
            <h2>予約 - <?php echo htmlspecialchars($objRoom['name']); ?></h2>
            
            <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                <p><strong>部屋:</strong> <?php echo htmlspecialchars($objRoom['name']); ?></p>
                <p><strong>説明:</strong> <?php echo htmlspecialchars($objRoom['description']); ?></p>
                <p><strong>料金:</strong> ¥<?php echo number_format($objRoom['price']); ?>/泊</p>
                <p><strong>最大宿泊人数:</strong> <?php echo $objRoom['max_guests']; ?>名</p>
            </div>
            
            <?php if ($strError): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($strError); ?></div>
            <?php endif; ?>
            
            <?php if ($strSuccess): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($strSuccess); ?>
                    <br><a href="my_bookings.php">予約履歴を確認</a>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="check_in">チェックイン日:</label>
                    <input type="date" id="check_in" name="check_in" min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="check_out">チェックアウト日:</label>
                    <input type="date" id="check_out" name="check_out" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="guests">宿泊人数:</label>
                    <select id="guests" name="guests" required>
                        <?php for ($i = 1; $i <= $objRoom['max_guests']; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?>名</option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div id="price-info" style="background: #e9ecef; padding: 15px; border-radius: 4px; margin: 20px 0; display: none;">
                    <p><strong>宿泊日数:</strong> <span id="nights">-</span>泊</p>
                    <p><strong>合計料金:</strong> ¥<span id="total-price">0</span></p>
                </div>
                
                <button type="submit" class="btn">予約する</button>
                <a href="index.php?room_id=<?php echo $intRoomId; ?>" class="btn" style="background: #6c757d; margin-left: 10px;">戻る</a>
            </form>
        </div>
    </div>

    <script>
        const checkInInput = document.getElementById('check_in');
        const checkOutInput = document.getElementById('check_out');
        const priceInfo = document.getElementById('price-info');
        const nightsSpan = document.getElementById('nights');
        const totalPriceSpan = document.getElementById('total-price');
        const roomPrice = <?php echo $objRoom['price']; ?>;

        function calculatePrice() {
            const checkIn = new Date(checkInInput.value);
            const checkOut = new Date(checkOutInput.value);
            
            if (checkIn && checkOut && checkOut > checkIn) {
                const nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
                const totalPrice = nights * roomPrice;
                
                nightsSpan.textContent = nights;
                totalPriceSpan.textContent = totalPrice.toLocaleString();
                priceInfo.style.display = 'block';
            } else {
                priceInfo.style.display = 'none';
            }
        }

        checkInInput.addEventListener('change', function() {
            const minCheckOut = new Date(this.value);
            minCheckOut.setDate(minCheckOut.getDate() + 1);
            checkOutInput.min = minCheckOut.toISOString().split('T')[0];
            calculatePrice();
        });

        checkOutInput.addEventListener('change', calculatePrice);
    </script>
</body>
</html>