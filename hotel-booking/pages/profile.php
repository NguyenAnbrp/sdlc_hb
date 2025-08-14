<?php require_once '../includes/auth.php'; ?>
<?php include '../includes/header.php'; ?>
<?php require_once '../config/db.php'; ?>
<?php
$user = $_SESSION['user'] ?? null;
// Lấy thông tin user từ DB (có thể bổ sung số điện thoại nếu có)
$stmt = $pdo->prepare('SELECT * FROM userr WHERE UserID = ?');
$stmt->execute([$user['UserID']]);
$userInfo = $stmt->fetch();
// Lấy lịch sử đặt phòng
$sql = "SELECT b.*, r.RoomNumber, rt.TypeName FROM bookings b
        JOIN rooms r ON b.RoomID = r.RoomID
        JOIN roomtypes rt ON r.RoomTypeID = rt.RoomTypeID
        WHERE b.UserID = ? ORDER BY b.CreatedAt DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user['UserID']]);
$bookings = $stmt->fetchAll();
function getBookingStatus($status, $checkIn) {
    $today = date('Y-m-d');
    if ($status == 'pending') return ['Pending Payment', 'available'];
    if ($status == 'cancelled') return ['Cancelled', 'booked'];
    if ($status == 'paid') {
        if ($today < $checkIn) return ['Paid - Confirmed', 'available'];
        else return ['Checked In', 'available'];
    }
    if ($status == 'approved') {
        if ($today < $checkIn) return ['Booking Successful', 'available'];
        else return ['Checked In', 'available'];
    }
    return ['Unknown', 'booked'];
}
// Xử lý hủy đặt phòng
$cancelSuccess = '';
if (isset($_POST['cancel_booking_id'])) {
    $cancelID = (int)$_POST['cancel_booking_id'];
    // Kiểm tra quyền và điều kiện hủy
    $stmt = $pdo->prepare('SELECT * FROM bookings WHERE BookingID = ? AND UserID = ?');
    $stmt->execute([$cancelID, $user['UserID']]);
    $booking = $stmt->fetch();
    if ($booking && $booking['Status'] !== 'cancelled') {
        $now = new DateTime();
        $created = new DateTime($booking['CreatedAt']);
        $checkIn = new DateTime($booking['CheckInDate']);
        $diff = $now->getTimestamp() - $created->getTimestamp();
        if ($now < $checkIn && $diff <= 3600) {
            $stmt = $pdo->prepare('UPDATE bookings SET Status = \'cancelled\' WHERE BookingID = ?');
            $stmt->execute([$cancelID]);
            $cancelSuccess = 'Đã hủy đặt phòng thành công!';
            // Reload lại dữ liệu
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user['UserID']]);
            $bookings = $stmt->fetchAll();
        } else {
            $cancelSuccess = 'Không thể hủy: Đã quá thời gian cho phép hoặc đã đến ngày nhận phòng!';
        }
    } else {
        $cancelSuccess = 'Không tìm thấy booking hoặc đã bị hủy!';
    }
}
?>
<main>
  <section class="profile-section">
    <h1>Profile</h1>
    <div class="profile-info">
      <h2>Personal Information</h2>
      <ul>
        <li><strong>Full Name:</strong> <?= htmlspecialchars($userInfo['FullName']) ?></li>
        <li><strong>Username:</strong> <?= htmlspecialchars($userInfo['Username']) ?></li>
        <li><strong>Email:</strong> <?= htmlspecialchars($userInfo['Email']) ?></li>
        <?php if (!empty($userInfo['PhoneNumber'])): ?>
        <li><strong>Phone Number:</strong> <?= htmlspecialchars($userInfo['PhoneNumber']) ?></li>
        <?php endif; ?>
      </ul>
    </div>
    <div class="profile-history">
      <h2>Booking History</h2>
      <?php if ($cancelSuccess): ?>
        <div style="color:#059669; margin-bottom:1rem; font-weight:500;"> <?= htmlspecialchars($cancelSuccess) ?> </div>
      <?php endif; ?>
      <form method="post">
      <table class="history-table">
        <thead>
          <tr>
            <th>Room</th>
            <th>Type</th>
            <th>Check-in</th>
            <th>Check-out</th>
            <th>Status</th>
            <th>Payment Method</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($bookings)): ?>
            <tr><td colspan="7" style="text-align:center; color:#64748b;">No bookings yet.</td></tr>
          <?php else: foreach ($bookings as $b): list($statusText, $statusClass) = getBookingStatus($b['Status'], $b['CheckInDate']); ?>
            <tr>
              <td><?= htmlspecialchars($b['RoomNumber']) ?></td>
              <td><?= htmlspecialchars($b['TypeName']) ?></td>
              <td><?= htmlspecialchars($b['CheckInDate']) ?></td>
              <td><?= htmlspecialchars($b['CheckOutDate']) ?></td>
              <td><span class="room-status <?= $statusClass ?>"><?= $statusText ?></span></td>
              <td>
                <?php if ($b['PaymentMethod']): ?>
                  <span style="color:#0ea5e9; font-weight:600;"><?= ucfirst(str_replace('_', ' ', $b['PaymentMethod'])) ?></span>
                <?php else: ?>
                  <span style="color:#64748b;">-</span>
                <?php endif; ?>
              </td>
              <td>
                <?php
                  $now = new DateTime();
                  $created = new DateTime($b['CreatedAt']);
                  $checkIn = new DateTime($b['CheckInDate']);
                  $diff = $now->getTimestamp() - $created->getTimestamp();
                  if ($b['Status'] === 'pending') {
                ?>
                  <a href="payment.php?booking_id=<?= $b['BookingID'] ?>" class="btn" style="background:#0ea5e9; color:#fff;">Pay Now</a>
                <?php } elseif ($b['Status'] !== 'cancelled' && $now < $checkIn && $diff <= 3600) { ?>
                  <button type="submit" name="cancel_booking_id" value="<?= $b['BookingID'] ?>" class="btn" style="background:#dc2626; color:#fff;">Cancel Booking</button>
                <?php } else { echo '-'; } ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
      </form>
    </div>
  </section>
</main>
<?php include '../includes/footer.php'; ?>
