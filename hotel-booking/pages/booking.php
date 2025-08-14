<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
$roomID = isset($_GET['room']) ? (int)$_GET['room'] : 0;
if (!$roomID) { header('Location: rooms.php'); exit; }
// Lấy thông tin phòng
$stmt = $pdo->prepare('SELECT r.*, rt.TypeName, b.BranchName FROM rooms r JOIN roomtypes rt ON r.RoomTypeID = rt.RoomTypeID JOIN branches b ON r.BranchID = b.BranchID WHERE r.RoomID = ?');
$stmt->execute([$roomID]);
$room = $stmt->fetch();
if (!$room) { header('Location: rooms.php'); exit; }
// Lấy dữ liệu GET nếu có
$checkIn = $_GET['checkin'] ?? '';
$checkOut = $_GET['checkout'] ?? '';
$guests = $_GET['guests'] ?? 1;
// Xử lý đặt phòng
$success = false; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
    // Validate các trường
    $checkIn = $_POST['checkin']; $checkOut = $_POST['checkout']; $guests = $_POST['guests'];
    $firstName = trim($_POST['first_name']); $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']); $country = trim($_POST['country']); $phone = trim($_POST['phone']);
    $special = trim($_POST['special']);
    if (!$checkIn || !$checkOut || strtotime($checkIn) >= strtotime($checkOut)) $error = 'Ngày nhận/trả không hợp lệ!';
    elseif (!$firstName || !$lastName || !$email || !$country || !$phone) $error = 'Vui lòng nhập đầy đủ thông tin khách!';
    else {
        // Kiểm tra phòng đã được đặt chưa
        $sql = "SELECT 1 FROM bookings WHERE RoomID = ? AND Status IN ('pending', 'paid') AND ((? BETWEEN CheckInDate AND DATE_SUB(CheckOutDate, INTERVAL 1 DAY)) OR (? BETWEEN DATE_ADD(CheckInDate, INTERVAL 1 DAY) AND CheckOutDate) OR (CheckInDate BETWEEN ? AND ?) OR (CheckOutDate BETWEEN ? AND ?))";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$roomID, $checkIn, $checkOut, $checkIn, $checkOut, $checkIn, $checkOut]);
        if ($stmt->fetch()) $error = 'Phòng đã được đặt trong thời gian này!';
        else {
            // Lưu booking với status pending
            $stmt = $pdo->prepare('INSERT INTO bookings (UserID, RoomID, CheckInDate, CheckOutDate, Guests, Status, CreatedAt, SpecialRequest, GuestName, GuestEmail, GuestPhone, GuestCountry) VALUES (?, ?, ?, ?, ?, \'pending\', NOW(), ?, ?, ?, ?, ?)');
            $userID = $_SESSION['user']['UserID'];
            $guestName = $firstName.' '.$lastName;
            if ($stmt->execute([$userID, $roomID, $checkIn, $checkOut, $guests, $special, $guestName, $email, $phone, $country])) {
                $bookingID = $pdo->lastInsertId();
                // Redirect đến trang thanh toán
                header("Location: payment.php?booking_id=" . $bookingID);
                exit;
            } else $error = 'Có lỗi xảy ra, vui lòng thử lại!';
        }
    }
}
include '../includes/header.php';
?>
<style>
.booking-main { max-width: 1100px; margin: 2.5rem auto; background: #fff; border-radius: 18px; box-shadow: 0 2px 16px rgba(30,41,59,0.06); padding: 2.5rem 2rem; }
.booking-grid { display: flex; gap: 2.5rem; flex-wrap: wrap; }
.booking-left { flex: 2 1 400px; }
.booking-right { flex: 1 1 280px; background: #f8fafc; border-radius: 12px; padding: 1.5rem 1.2rem; }
.booking-section-title { font-size: 1.2rem; font-weight: 700; color: #1e293b; margin-bottom: 1.2rem; }
.booking-form-row { display: flex; gap: 1rem; margin-bottom: 1.1rem; }
.booking-form-row > div { flex: 1; }
.booking-form label { font-weight: 600; color: #334155; margin-bottom: 0.3rem; display: block; }
.booking-form input, .booking-form select, .booking-form textarea { width: 100%; padding: 0.7rem 0.9rem; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 1rem; margin-bottom: 0.2rem; background: #fff; transition: border 0.2s; }
.booking-form input:focus, .booking-form select:focus, .booking-form textarea:focus { border: 1.5px solid #0ea5e9; outline: none; }
.booking-form textarea { min-height: 60px; }
.booking-form .btn-cta { width: 100%; font-size: 1.1rem; padding: 0.9rem 0; margin-top: 1.2rem; }
.booking-summary { font-size: 1rem; color: #334155; }
.booking-summary .sum-title { font-weight: 700; margin-bottom: 0.7rem; }
.booking-summary .sum-row { display: flex; justify-content: space-between; margin-bottom: 0.3rem; }
.booking-summary .sum-total { font-size: 1.2rem; font-weight: 800; color: #0ea5e9; margin-top: 1rem; }
.booking-policies { margin-top: 1.5rem; font-size: 0.97rem; color: #64748b; }
@media (max-width: 900px) { .booking-grid { flex-direction: column; } .booking-main { padding: 1.2rem 0.5rem; } }
</style>
<main class="booking-main">
  <a href="rooms.php" style="color:#0ea5e9;font-weight:600;display:inline-block;margin-bottom:1.2rem;">&larr; Back to Room List</a>
  <h1 style="font-size:1.7rem;font-weight:800;margin-bottom:2rem;">Booking Information</h1>
  <?php if ($success): ?>
    <div style="background:#e0fce6;color:#059669;padding:1.5rem 1rem;border-radius:10px;text-align:center;font-size:1.15rem;font-weight:600;">Booking successful! You can check your booking in your account.</div>
  <?php else: ?>
  <?php if ($error): ?><div style="color:#dc2626;font-weight:600;margin-bottom:1rem;"> <?= htmlspecialchars($error) ?> </div><?php endif; ?>
  <form method="post" class="booking-form">
    <div class="booking-grid">
      <div class="booking-left">
        <div class="booking-section-title">Room Details</div>
        <div class="booking-form-row">
          <div>
            <label>Check-in Date</label>
            <input type="date" name="checkin" required value="<?= htmlspecialchars($checkIn) ?>" min="<?= date('Y-m-d') ?>">
          </div>
          <div>
            <label>Check-out Date</label>
            <input type="date" name="checkout" required value="<?= htmlspecialchars($checkOut) ?>" min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
          </div>
        </div>
        <div class="booking-form-row">
          <div>
            <label>Number of Guests</label>
            <select name="guests" required>
              <?php for($i=1;$i<=$room['Capacity'];$i++): ?>
                <option value="<?= $i ?>" <?= $guests==$i?'selected':'' ?>><?= $i ?> guest<?= $i>1?'s':'' ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div>
            <label>Room Type</label>
            <input type="text" value="<?= htmlspecialchars($room['TypeName']) ?>" readonly>
          </div>
        </div>
        <div class="booking-section-title">Guest Information</div>
        <div class="booking-form-row">
          <div><label>First Name*</label><input type="text" name="first_name" required></div>
          <div><label>Last Name*</label><input type="text" name="last_name" required></div>
        </div>
        <div class="booking-form-row">
          <div><label>Email*</label><input type="email" name="email" required></div>
          <div><label>Country*</label><input type="text" name="country" required></div>
          <div><label>Phone Number*</label><input type="text" name="phone" required></div>
        </div>
        <div class="booking-section-title">Special Requests</div>
        <textarea name="special" placeholder="Enter any special requests if any..."></textarea>
        <button type="submit" name="confirm_booking" class="btn btn-cta">Continue to Payment</button>
      </div>
      <div class="booking-right">
        <div class="booking-summary">
          <div class="sum-title">Booking Summary</div>
          <div class="sum-row"><span><?= htmlspecialchars($room['TypeName']) ?></span><span><?= number_format($room['PricePerNight'],0,',','.') ?>đ/night</span></div>
          <div class="sum-row"><span>Nights</span><span id="sum-nights"></span></div>
          <div class="sum-row"><span>Guests</span><span><?= htmlspecialchars($guests) ?></span></div>
          <div class="sum-row"><span>Subtotal</span><span id="sum-total"></span></div>
          <div class="sum-total" id="sum-grand"></div>
        </div>
        <div class="booking-policies">
          <b>Hotel Policies:</b><br>
          - Check-in after 2:00 PM, check-out before 12:00 PM<br>
          - No smoking, no pets<br>
          - Free cancellation within 1 hour after booking<br>
        </div>
      </div>
    </div>
  </form>
  <script>
    function calcNights() {
      const inDate = document.querySelector('[name=checkin]').value;
      const outDate = document.querySelector('[name=checkout]').value;
      if (!inDate || !outDate) return {n:0, t:0};
      const d1 = new Date(inDate), d2 = new Date(outDate);
      const nights = (d2-d1)/(1000*60*60*24);
      return {n: nights>0?nights:0, t: nights>0?nights*<?= (int)$room['PricePerNight'] ?>:0};
    }
    function updateSummary() {
      const {n, t} = calcNights();
      document.getElementById('sum-nights').innerText = n;
      document.getElementById('sum-total').innerText = t.toLocaleString('vi-VN')+'đ';
      document.getElementById('sum-grand').innerText = t.toLocaleString('vi-VN')+'đ';
    }
    document.querySelector('[name=checkin]').addEventListener('change', updateSummary);
    document.querySelector('[name=checkout]').addEventListener('change', updateSummary);
    updateSummary();
  </script>
  <?php endif; ?>
</main>
<?php include '../includes/footer.php'; ?> 