<?php require_once '../includes/auth.php'; ?>
<?php include '../includes/header.php'; ?>
<?php require_once '../config/db.php'; ?>
<?php
// Lấy thông tin phòng
$roomID = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$roomID) {
    header('Location: rooms.php');
    exit;
}

$sql = "SELECT r.*, rt.TypeName, b.BranchName,
        CASE 
            WHEN EXISTS (
                SELECT 1 FROM bookings 
                WHERE RoomID = r.RoomID 
                AND Status = 'approved'
                AND CURRENT_DATE BETWEEN CheckInDate AND CheckOutDate
            ) THEN 'booked'
            ELSE 'available'
        END as Status
        FROM rooms r
        JOIN roomtypes rt ON r.RoomTypeID = rt.RoomTypeID
        JOIN branches b ON r.BranchID = b.BranchID
        WHERE r.RoomID = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$roomID]);
$room = $stmt->fetch();

if (!$room) {
    header('Location: rooms.php');
    exit;
}

// Lấy tất cả ảnh của phòng
$stmt = $pdo->prepare('SELECT * FROM room_images WHERE RoomID = ? ORDER BY IsMain DESC, ImageID ASC');
$stmt->execute([$roomID]);
$images = $stmt->fetchAll();

$isAvailable = $room['Status'] == 'available';

// Xử lý đặt phòng
$bookingSuccess = false;
$bookingError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_room'])) {
    $userID = $_SESSION['user']['UserID'];
    $checkIn = $_POST['checkin'] ?? '';
    $checkOut = $_POST['checkout'] ?? '';
    $guests = $_POST['guests'] ?? 1;
    // Validate ngày
    if (!$checkIn || !$checkOut || strtotime($checkIn) >= strtotime($checkOut)) {
        $bookingError = 'Vui lòng chọn ngày nhận và trả hợp lệ!';
    } else {
        // Kiểm tra phòng đã được đặt trong khoảng này chưa
        $sql = "SELECT 1 FROM bookings WHERE RoomID = ? AND Status = 'approved' AND ((? BETWEEN CheckInDate AND DATE_SUB(CheckOutDate, INTERVAL 1 DAY)) OR (? BETWEEN DATE_ADD(CheckInDate, INTERVAL 1 DAY) AND CheckOutDate) OR (CheckInDate BETWEEN ? AND ?) OR (CheckOutDate BETWEEN ? AND ?))";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$roomID, $checkIn, $checkOut, $checkIn, $checkOut, $checkIn, $checkOut]);
        if ($stmt->fetch()) {
            $bookingError = 'Phòng đã được đặt trong thời gian này!';
        } else {
            // Thêm booking
            $stmt = $pdo->prepare('INSERT INTO bookings (UserID, RoomID, CheckInDate, CheckOutDate, Guests, Status, CreatedAt) VALUES (?, ?, ?, ?, ?, \'approved\', NOW())');
            if ($stmt->execute([$userID, $roomID, $checkIn, $checkOut, $guests])) {
                $bookingSuccess = true;
                $bookingID = $pdo->lastInsertId();
            } else {
                $bookingError = 'Có lỗi xảy ra, vui lòng thử lại!';
            }
        }
    }
}
?>
<!-- Link Swiper CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<style>
.swiper {
    width: 100%;
    height: 600px;
    margin-bottom: 2rem;
}
.swiper-slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.room-detail {
    max-width: 1400px;
    margin: 2rem auto;
    padding: 0 1rem;
}
.room-info {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-top: 2rem;
}
.room-info h1 {
    color: #1e293b;
    margin-bottom: 1rem;
}
.room-info p {
    color: #64748b;
    margin: 0.5rem 0;
    font-size: 1.1rem;
}
.room-description {
    margin: 1.5rem 0;
    padding: 1.5rem 0;
    border-top: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
}
.room-actions {
    margin-top: 2rem;
    display: flex;
    gap: 1rem;
}
.thumbnail-swiper {
    height: 120px;
    margin-top: 1rem;
}
.thumbnail-swiper .swiper-slide {
    opacity: 0.4;
    cursor: pointer;
}
.thumbnail-swiper .swiper-slide-thumb-active {
    opacity: 1;
}
.booking-section {
  display: flex;
  gap: 2rem;
  align-items: flex-start;
  justify-content: center;
  margin-top: 2.5rem;
  flex-wrap: wrap;
}
.booking-form-box {
  background: #f8fafc;
  border-radius: 18px;
  box-shadow: 0 2px 16px rgba(30,41,59,0.06);
  padding: 2.2rem 2.5rem 2rem 2.5rem;
  min-width: 340px;
  max-width: 420px;
  flex: 1 1 340px;
}
.booking-form-box h2 {
  font-size: 1.5rem;
  font-weight: 800;
  color: #1e293b;
  margin-bottom: 1.5rem;
}
.booking-form-row {
  display: flex;
  gap: 1.2rem;
  flex-wrap: wrap;
  margin-bottom: 1.2rem;
}
.booking-form-row > div {
  flex: 1 1 120px;
  min-width: 120px;
}
.booking-form-box label {
  font-weight: 600;
  color: #334155;
  margin-bottom: 0.4rem;
  display: block;
}
.booking-form-box input[type="date"],
.booking-form-box input[type="number"] {
  width: 100%;
  padding: 0.7rem 0.9rem;
  border: 1.5px solid #cbd5e1;
  border-radius: 8px;
  font-size: 1rem;
  margin-bottom: 0.2rem;
  background: #fff;
  transition: border 0.2s;
}
.booking-form-box input:focus {
  border: 1.5px solid #0ea5e9;
  outline: none;
}
.booking-form-box .btn-cta {
  width: 100%;
  font-size: 1.1rem;
  padding: 0.9rem 0;
  margin-top: 1.2rem;
}
.booking-form-box .error {
  color: #dc2626;
  margin-top: 0.7rem;
  font-weight: 500;
  text-align: center;
}
.booking-back-box {
  background: #64748b;
  color: #fff;
  border-radius: 12px;
  padding: 2.2rem 1.5rem;
  min-width: 180px;
  max-width: 220px;
  text-align: center;
  font-size: 1.1rem;
  font-weight: 700;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
}
.booking-back-box a {
  color: #fff;
  text-decoration: none;
  font-weight: 700;
  margin-top: 0.7rem;
  background: #475569;
  padding: 0.7rem 1.2rem;
  border-radius: 8px;
  transition: background 0.2s;
  display: inline-block;
}
.booking-back-box a:hover {
  background: #334155;
}
@media (max-width: 900px) {
  .booking-section { flex-direction: column; align-items: stretch; }
  .booking-form-box, .booking-back-box { max-width: 100%; min-width: 0; }
  .swiper { height: 450px; }
  .thumbnail-swiper { height: 100px; }
}
@media (max-width: 600px) {
  .swiper { height: 350px; }
  .thumbnail-swiper { height: 80px; }
  .room-detail { padding: 0 0.5rem; }
}
</style>

<main>
    <div class="room-detail">
        <?php if (!empty($images)): ?>
        <!-- Swiper -->
        <div class="swiper main-swiper">
            <div class="swiper-wrapper">
                <?php foreach ($images as $image): ?>
                <div class="swiper-slide">
                    <img src="../assets/images/<?= htmlspecialchars($image['ImagePath']) ?>" 
                         alt="Phòng <?= htmlspecialchars($room['RoomNumber']) ?>">
                </div>
                <?php endforeach; ?>
            </div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
        </div>
        <!-- Thumbnail Swiper -->
        <div class="swiper thumbnail-swiper">
            <div class="swiper-wrapper">
                <?php foreach ($images as $image): ?>
                <div class="swiper-slide">
                    <img src="../assets/images/<?= htmlspecialchars($image['ImagePath']) ?>" 
                         alt="Thumbnail" 
                         style="width:100%; height:100%; object-fit:cover;">
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="room-info">
            <h1><?= htmlspecialchars($room['RoomNumber']) ?> (<?= htmlspecialchars($room['TypeName']) ?>)</h1>
            
            <div class="room-meta">
                <p><strong>Branch:</strong> <?= htmlspecialchars($room['BranchName']) ?></p>
                <p><strong>Capacity:</strong> <?= htmlspecialchars($room['Capacity']) ?> people</p>
                <p><strong>Price:</strong> <?= number_format($room['PricePerNight'], 0, ',', '.') ?>đ/night</p>
                <p>
                    <strong>Status:</strong> 
                    <span style="color:<?= $isAvailable ? '#059669' : '#dc2626' ?>;">
                        <?= $isAvailable ? 'Available' : 'Booked' ?>
                    </span>
                </p>
            </div>

            <?php if (!empty($room['Description'])): ?>
            <div class="room-description">
                <h2>Room Description</h2>
                <p style="white-space: pre-line;"><?= htmlspecialchars($room['Description']) ?></p>
            </div>
            <?php endif; ?>

            <div class="room-actions" style="display:flex; gap:1.2rem; justify-content:center; margin-top:2.5rem;">
              <a href="booking.php?room=<?= $roomID ?>" class="btn btn-cta" style="font-size:1.15rem; padding:1rem 2.5rem;">Book Now</a>
              <a href="rooms.php" class="btn" style="background:#64748b; color:#fff; font-size:1.1rem; padding:1rem 2.2rem;">Back to Room List</a>
            </div>
        </div>
    </div>
</main>

<?php
// Hiện popup thành công, hướng dẫn vào tài khoản
if ($bookingSuccess) {
    echo "<script>setTimeout(function(){ location.href='../pages/profile.php'; }, 5000);</script>";
    echo "<div id='booking-success-popup' style='position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;background:rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;'>
        <div style='background:#fff;padding:2rem 2.5rem;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,0.12);text-align:center;'>
            <h2 style='color:#059669;margin-bottom:1rem;'>Đặt phòng thành công!</h2>
            <p>Bạn có thể kiểm tra phòng đã đặt trong phần <b>Tài khoản</b> của mình.<br>Trang sẽ tự động chuyển về trang tài khoản sau 5 giây.</p>
            <a href='../pages/profile.php' class='btn btn-cta' style='margin-top:1rem;'>Về tài khoản</a>
        </div>
    </div>
    <script>setTimeout(function(){ document.getElementById('booking-success-popup').style.display = 'none'; }, 5000);</script>";
}
?>

<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
var thumbnailSwiper = new Swiper(".thumbnail-swiper", {
    spaceBetween: 10,
    slidesPerView: 4,
    freeMode: true,
    watchSlidesProgress: true,
});
var mainSwiper = new Swiper(".main-swiper", {
    spaceBetween: 10,
    navigation: {
        nextEl: ".swiper-button-next",
        prevEl: ".swiper-button-prev",
    },
    thumbs: {
        swiper: thumbnailSwiper,
    },
});
</script>

<?php include '../includes/footer.php'; ?> 