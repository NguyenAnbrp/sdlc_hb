<?php require_once '../includes/auth.php'; ?>
<?php
if ($_SESSION['user']['RoleID'] != 1) {
    $_SESSION['auth_message'] = 'Bạn không có quyền truy cập trang này.';
    header('Location: /hotel-booking/index.php');
    exit;
}
?>
<?php include '../includes/header.php'; ?>
<?php require_once '../config/db.php'; ?>
<?php
// Thống kê
$totalRooms = $pdo->query('SELECT COUNT(*) FROM rooms')->fetchColumn();
$totalBookings = $pdo->query('SELECT COUNT(*) FROM bookings WHERE Status != "cancelled"')->fetchColumn();
$totalRevenue = $pdo->query('SELECT SUM(r.PricePerNight) FROM rooms r JOIN bookings b ON r.RoomID = b.RoomID WHERE b.Status IN ("paid", "approved")')->fetchColumn();
$totalUsers = $pdo->query('SELECT COUNT(*) FROM userr WHERE RoleID = 2')->fetchColumn();

// Xử lý hủy booking của khách hàng
$cancelBookingSuccess = $cancelBookingError = '';
if (isset($_GET['cancel_booking'])) {
    $bookingID = (int)$_GET['cancel_booking'];
    try {
        $stmt = $pdo->prepare('UPDATE bookings SET Status = "cancelled" WHERE BookingID = ?');
        if ($stmt->execute([$bookingID])) {
            $cancelBookingSuccess = 'Đã hủy đặt phòng thành công!';
        } else {
            $cancelBookingError = 'Không thể hủy đặt phòng.';
        }
    } catch (Exception $e) {
        $cancelBookingError = 'Có lỗi xảy ra: ' . $e->getMessage();
    }
}

// Xử lý hủy booking của user trong booking history
$cancelUserBookingSuccess = $cancelUserBookingError = '';
if (isset($_GET['cancel_user_booking'])) {
    $bookingID = (int)$_GET['cancel_user_booking'];
    try {
        $stmt = $pdo->prepare('UPDATE bookings SET Status = "cancelled" WHERE BookingID = ?');
        if ($stmt->execute([$bookingID])) {
            $cancelUserBookingSuccess = 'Đã hủy đặt phòng thành công!';
        } else {
            $cancelUserBookingError = 'Không thể hủy đặt phòng.';
        }
    } catch (Exception $e) {
        $cancelUserBookingError = 'Có lỗi xảy ra: ' . $e->getMessage();
    }
}

// Xử lý xóa tài khoản khách hàng
$deleteUserSuccess = $deleteUserError = '';
if (isset($_GET['delete_user'])) {
    $userID = (int)$_GET['delete_user'];
    // Kiểm tra không xóa admin và không xóa chính mình
    $stmt = $pdo->prepare('SELECT RoleID FROM userr WHERE UserID = ?');
    $stmt->execute([$userID]);
    $user = $stmt->fetch();
    
    if ($user && $user['RoleID'] != 1 && $userID != $_SESSION['user']['UserID']) {
        try {
            $pdo->beginTransaction();
            
            // Xóa tất cả booking của user này
            $stmt = $pdo->prepare('DELETE FROM bookings WHERE UserID = ?');
            $stmt->execute([$userID]);
            
            // Xóa user
            $stmt = $pdo->prepare('DELETE FROM userr WHERE UserID = ?');
            if ($stmt->execute([$userID])) {
                $pdo->commit();
                $deleteUserSuccess = 'Đã xóa tài khoản khách hàng thành công!';
            } else {
                $pdo->rollBack();
                $deleteUserError = 'Không thể xóa tài khoản.';
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $deleteUserError = 'Có lỗi xảy ra: ' . $e->getMessage();
        }
    } else {
        $deleteUserError = 'Không thể xóa tài khoản admin hoặc tài khoản của chính mình!';
    }
}

// Thêm phòng mới
$addSuccess = $addError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_room'])) {
    $roomNumber = trim($_POST['room_number'] ?? '');
    $roomType = $_POST['room_type'] ?? '';
    $branch = $_POST['branch'] ?? '';
    $capacity = $_POST['capacity'] ?? '';
    $price = $_POST['price'] ?? '';
    $description = trim($_POST['description'] ?? '');
    
    // Xử lý upload nhiều ảnh
    $uploadedImages = [];
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        // Tạo thư mục nếu chưa có
        if (!is_dir('../assets/images/rooms')) {
            mkdir('../assets/images/rooms', 0777, true);
        }
        
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] == 0) {
                $filename = $_FILES['images']['name'][$key];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (!in_array($ext, $allowed)) {
                    $addError = 'Chỉ chấp nhận file ảnh (jpg, jpeg, png, gif)';
                    break;
                }
                
                // Tạo tên file mới để tránh trùng
                $newName = uniqid() . '_' . $key . '.' . $ext;
                $uploadPath = '../assets/images/rooms/' . $newName;
                
                if (move_uploaded_file($tmp_name, $uploadPath)) {
                    $uploadedImages[] = 'rooms/' . $newName;
                } else {
                    $addError = 'Không thể upload ảnh, vui lòng thử lại.';
                    break;
                }
            }
        }
    }

    if ($roomNumber === '' || $roomType === '' || $branch === '' || $capacity === '' || $price === '') {
        $addError = 'Vui lòng nhập đầy đủ thông tin phòng.';
    } elseif (empty($uploadedImages)) {
        $addError = 'Vui lòng chọn ít nhất một ảnh cho phòng.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Thêm phòng
            $stmt = $pdo->prepare('INSERT INTO rooms (RoomNumber, RoomTypeID, BranchID, Capacity, PricePerNight, Description) VALUES (?, ?, ?, ?, ?, ?)');
            if ($stmt->execute([$roomNumber, $roomType, $branch, $capacity, $price, $description])) {
                $roomID = $pdo->lastInsertId();
                
                // Thêm ảnh cho phòng
                $stmt = $pdo->prepare('INSERT INTO room_images (RoomID, ImagePath, IsMain) VALUES (?, ?, ?)');
                foreach ($uploadedImages as $index => $imagePath) {
                    $isMain = ($index === 0); // Ảnh đầu tiên làm ảnh chính
                    $stmt->execute([$roomID, $imagePath, $isMain]);
                }
                
                $pdo->commit();
                $addSuccess = 'Thêm phòng mới thành công với ' . count($uploadedImages) . ' ảnh!';
            } else {
                $pdo->rollBack();
                $addError = 'Thêm phòng thất bại.';
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $addError = 'Có lỗi xảy ra: ' . $e->getMessage();
        }
    }
}

// Lấy danh sách phòng với trạng thái hiện tại
$sql = "SELECT r.*, rt.TypeName, b.BranchName,
        CASE 
            WHEN EXISTS (
                SELECT 1 FROM bookings 
                WHERE RoomID = r.RoomID 
                AND Status = 'approved'
                AND CURRENT_DATE BETWEEN CheckInDate AND CheckOutDate
            ) THEN 'booked'
            ELSE 'available'
        END as CurrentStatus,
        (SELECT ImagePath FROM room_images WHERE RoomID = r.RoomID AND IsMain = 1 LIMIT 1) as MainImage
        FROM rooms r
        JOIN roomtypes rt ON r.RoomTypeID = rt.RoomTypeID
        JOIN branches b ON r.BranchID = b.BranchID
        ORDER BY r.RoomID DESC";
$roomList = $pdo->query($sql)->fetchAll();

// Lấy đơn đặt phòng gần đây
$sql = "SELECT b.*, u.FullName, r.RoomNumber, rt.TypeName 
        FROM bookings b
        JOIN userr u ON b.UserID = u.UserID
        JOIN rooms r ON b.RoomID = r.RoomID
        JOIN roomtypes rt ON r.RoomTypeID = rt.RoomTypeID
        ORDER BY b.CreatedAt DESC LIMIT 10";
$recentBookings = $pdo->query($sql)->fetchAll();

// Hàm lấy trạng thái đơn đặt phòng
function getBookingStatus($status) {
    switch($status) {
        case 'pending': return ['Pending', 'available'];
        case 'approved': return ['Approved', 'available'];
        case 'cancelled': return ['Cancelled', 'booked'];
        default: return [$status, 'available'];
    }
}

// Quản lý đơn đặt: duyệt/hủy
$orderSuccess = $orderError = '';
if (isset($_GET['approve_booking'])) {
    $id = (int)$_GET['approve_booking'];
    $stmt = $pdo->prepare('UPDATE bookings SET Status = "approved" WHERE BookingID = ?');
    if ($stmt->execute([$id])) {
        $orderSuccess = 'Duyệt đơn thành công!';
    } else {
        $orderError = 'Duyệt đơn thất bại.';
    }
}
if (isset($_GET['cancel_booking'])) {
    $id = (int)$_GET['cancel_booking'];
    $stmt = $pdo->prepare('UPDATE bookings SET Status = "cancelled" WHERE BookingID = ?');
    if ($stmt->execute([$id])) {
        $orderSuccess = 'Hủy đơn thành công!';
    } else {
        $orderError = 'Hủy đơn thất bại.';
    }
}

// Lấy loại phòng, chi nhánh cho form thêm phòng
$roomTypes = $pdo->query('SELECT * FROM roomtypes')->fetchAll();
$branches = $pdo->query('SELECT * FROM branches')->fetchAll();
// Sửa phòng
$editSuccess = $editError = '';
$editRoom = null;
$editRoomImages = [];
if (isset($_GET['edit_room'])) {
    $editID = (int)$_GET['edit_room'];
    $stmt = $pdo->prepare('SELECT * FROM rooms WHERE RoomID = ?');
    $stmt->execute([$editID]);
    $editRoom = $stmt->fetch();
    if (!$editRoom) {
        $editError = 'Không tìm thấy phòng với ID: ' . $editID;
    } else {
        // Lấy ảnh của phòng
        $stmt = $pdo->prepare('SELECT * FROM room_images WHERE RoomID = ? ORDER BY IsMain DESC, ImageID ASC');
        $stmt->execute([$editID]);
        $editRoomImages = $stmt->fetchAll();
    }
    
    // Xử lý cập nhật
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_room'])) {
        $roomNumber = trim($_POST['room_number'] ?? '');
        $roomType = $_POST['room_type'] ?? '';
        $branch = $_POST['branch'] ?? '';
        $capacity = $_POST['capacity'] ?? '';
        $price = $_POST['price'] ?? '';
        $description = trim($_POST['description'] ?? '');
        
        if ($roomNumber === '' || $roomType === '' || $branch === '' || $capacity === '' || $price === '') {
            $editError = 'Vui lòng nhập đầy đủ thông tin.';
        } else {
            try {
                $pdo->beginTransaction();
                
                // Cập nhật thông tin phòng
                $stmt = $pdo->prepare('UPDATE rooms SET RoomNumber=?, RoomTypeID=?, BranchID=?, Capacity=?, PricePerNight=?, Description=? WHERE RoomID=?');
                if ($stmt->execute([$roomNumber, $roomType, $branch, $capacity, $price, $description, $editID])) {
                    
                    // Xử lý thêm ảnh mới nếu có
                    if (isset($_FILES['new_images']) && !empty($_FILES['new_images']['name'][0])) {
                        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                        
                        if (!is_dir('../assets/images/rooms')) {
                            mkdir('../assets/images/rooms', 0777, true);
                        }
                        
                        foreach ($_FILES['new_images']['tmp_name'] as $key => $tmp_name) {
                            if ($_FILES['new_images']['error'][$key] == 0) {
                                $filename = $_FILES['new_images']['name'][$key];
                                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                                
                                if (in_array($ext, $allowed)) {
                                    $newName = uniqid() . '_edit_' . $key . '.' . $ext;
                                    $uploadPath = '../assets/images/rooms/' . $newName;
                                    
                                    if (move_uploaded_file($tmp_name, $uploadPath)) {
                                        $imagePath = 'rooms/' . $newName;
                                        $isMain = (count($editRoomImages) === 0); // Nếu chưa có ảnh nào thì ảnh đầu tiên làm ảnh chính
                                        $stmt = $pdo->prepare('INSERT INTO room_images (RoomID, ImagePath, IsMain) VALUES (?, ?, ?)');
                                        $stmt->execute([$editID, $imagePath, $isMain]);
                                    }
                                }
                            }
                        }
                    }
                    
                    $pdo->commit();
                    $editSuccess = 'Cập nhật phòng thành công!';
                    
                    // Refresh dữ liệu
                    $stmt = $pdo->prepare('SELECT * FROM rooms WHERE RoomID = ?');
                    $stmt->execute([$editID]);
                    $editRoom = $stmt->fetch();
                    
                    $stmt = $pdo->prepare('SELECT * FROM room_images WHERE RoomID = ? ORDER BY IsMain DESC, ImageID ASC');
                    $stmt->execute([$editID]);
                    $editRoomImages = $stmt->fetchAll();
                    
                } else {
                    $pdo->rollBack();
                    $editError = 'Cập nhật thất bại.';
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                $editError = 'Có lỗi xảy ra: ' . $e->getMessage();
            }
        }
    }
}

// Xóa phòng
$deleteSuccess = $deleteError = '';
if (isset($_GET['delete_room'])) {
    $deleteID = (int)$_GET['delete_room'];
    // Kiểm tra phòng có đơn đặt chưa (không tính booking đã cancel)
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE RoomID = ? AND Status != "cancelled"');
    $stmt->execute([$deleteID]);
    $hasBooking = $stmt->fetchColumn();
    if ($hasBooking > 0) {
        $deleteError = 'Không thể xóa phòng đã có đơn đặt.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Lấy danh sách ảnh để xóa file
            $stmt = $pdo->prepare('SELECT ImagePath FROM room_images WHERE RoomID = ?');
            $stmt->execute([$deleteID]);
            $images = $stmt->fetchAll();
            
            // Xóa phòng (sẽ tự động xóa ảnh do FOREIGN KEY CASCADE)
            $stmt = $pdo->prepare('DELETE FROM rooms WHERE RoomID = ?');
            if ($stmt->execute([$deleteID])) {
                // Xóa file ảnh
                foreach ($images as $img) {
                    $filePath = '../assets/images/' . $img['ImagePath'];
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
                
                $pdo->commit();
                $deleteSuccess = 'Xóa phòng thành công!';
            } else {
                $pdo->rollBack();
                $deleteError = 'Xóa phòng thất bại.';
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $deleteError = 'Có lỗi xảy ra: ' . $e->getMessage();
        }
    }
}

// Lấy tất cả đơn đặt phòng (không tính booking đã cancel)
$sql = "SELECT b.*, u.FullName, r.RoomNumber, rt.TypeName FROM bookings b
        JOIN userr u ON b.UserID = u.UserID
        JOIN rooms r ON b.RoomID = r.RoomID
        JOIN roomtypes rt ON r.RoomTypeID = rt.RoomTypeID
        WHERE b.Status != 'cancelled'
        ORDER BY b.CreatedAt DESC LIMIT 30";
$allBookings = $pdo->query($sql)->fetchAll();

// Quản lý khách hàng
$users = $pdo->query('SELECT u.*, (SELECT COUNT(*) FROM bookings b WHERE b.UserID = u.UserID AND b.Status != "cancelled") AS BookingCount FROM userr u ORDER BY u.UserID DESC')->fetchAll();
// Quản lý loại phòng
$typeSuccess = $typeError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_roomtype'])) {
    $typeName = trim($_POST['type_name'] ?? '');
    if ($typeName === '') {
        $typeError = 'Vui lòng nhập tên loại phòng.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO roomtypes (TypeName) VALUES (?)');
        if ($stmt->execute([$typeName])) {
            $typeSuccess = 'Thêm loại phòng thành công!';
        } else {
            $typeError = 'Thêm loại phòng thất bại.';
        }
    }
}
if (isset($_GET['delete_roomtype'])) {
    $id = (int)$_GET['delete_roomtype'];
    // Kiểm tra có phòng liên kết không
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM rooms WHERE RoomTypeID = ?');
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        $typeError = 'Không thể xóa loại phòng đã có phòng liên kết.';
    } else {
        $stmt = $pdo->prepare('DELETE FROM roomtypes WHERE RoomTypeID = ?');
        if ($stmt->execute([$id])) {
            $typeSuccess = 'Xóa loại phòng thành công!';
        } else {
            $typeError = 'Xóa loại phòng thất bại.';
        }
    }
}
$roomTypes = $pdo->query('SELECT * FROM roomtypes')->fetchAll();
// Quản lý chi nhánh
$branchSuccess = $branchError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_branch'])) {
    $branchName = trim($_POST['branch_name'] ?? '');
    if ($branchName === '') {
        $branchError = 'Vui lòng nhập tên chi nhánh.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO branches (BranchName) VALUES (?)');
        if ($stmt->execute([$branchName])) {
            $branchSuccess = 'Thêm chi nhánh thành công!';
        } else {
            $branchError = 'Thêm chi nhánh thất bại.';
        }
    }
}
if (isset($_GET['delete_branch'])) {
    $id = (int)$_GET['delete_branch'];
    // Kiểm tra có phòng liên kết không
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM rooms WHERE BranchID = ?');
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        $branchError = 'Không thể xóa chi nhánh đã có phòng liên kết.';
    } else {
        $stmt = $pdo->prepare('DELETE FROM branches WHERE BranchID = ?');
        if ($stmt->execute([$id])) {
            $branchSuccess = 'Xóa chi nhánh thành công!';
        } else {
            $branchError = 'Xóa chi nhánh thất bại.';
        }
    }
}
$branches = $pdo->query('SELECT * FROM branches')->fetchAll();
// Thống kê nâng cao: doanh thu, số lượng đặt phòng theo tháng
$stats = $pdo->query('
  SELECT 
    DATE_FORMAT(b.CreatedAt, "%Y-%m") AS month,
    COUNT(*) AS booking_count,
    SUM(r.PricePerNight) AS revenue
  FROM bookings b
  JOIN rooms r ON b.RoomID = r.RoomID
  WHERE b.Status != "cancelled"
  GROUP BY month
  ORDER BY month DESC
  LIMIT 12
')->fetchAll();
$stats = array_reverse($stats); // Để tháng cũ lên trước
$months = array_map(fn($s) => $s['month'], $stats);
$bookingCounts = array_map(fn($s) => (int)$s['booking_count'], $stats);
$revenues = array_map(fn($s) => (int)$s['revenue'], $stats);
// Quản lý admin/phân quyền
$adminSuccess = $adminError = '';
if (isset($_POST['make_admin'])) {
    $userID = (int)$_POST['user_id'];
    $stmt = $pdo->prepare('UPDATE userr SET RoleID = 1 WHERE UserID = ?');
    if ($stmt->execute([$userID])) {
        $adminSuccess = 'Cấp quyền admin thành công!';
    } else {
        $adminError = 'Cấp quyền admin thất bại.';
    }
}
if (isset($_GET['remove_admin'])) {
    $userID = (int)$_GET['remove_admin'];
    // Không cho phép tự hạ quyền chính mình
    if ($userID == $_SESSION['user']['UserID']) {
        $adminError = 'Không thể tự hạ quyền chính mình.';
    } else {
        $stmt = $pdo->prepare('UPDATE userr SET RoleID = 2 WHERE UserID = ?');
        if ($stmt->execute([$userID])) {
            $adminSuccess = 'Hạ quyền admin thành công!';
        } else {
            $adminError = 'Hạ quyền admin thất bại.';
        }
    }
}
$admins = $pdo->query('SELECT * FROM userr WHERE RoleID = 1 ORDER BY UserID DESC')->fetchAll();
$usersForAdmin = $pdo->query('SELECT * FROM userr WHERE RoleID = 2 ORDER BY UserID DESC')->fetchAll();
?>
<main>
  <section class="admin-dashboard">
    <h1>Admin Dashboard</h1>
    <div class="admin-stats" style="display:flex; gap:2.5rem; margin:2.5rem 0; flex-wrap:wrap;">
      <div class="stat-box" style="flex:1; min-width:160px; background:#fff; border-radius:14px; box-shadow:0 2px 12px rgba(30,41,59,0.07); padding:2rem 1.2rem; text-align:center;">
        <div style="font-size:2.5rem; color:#0ea5e9; margin-bottom:0.5rem;"><i class="fas fa-bed"></i></div>
        <h2 style="font-size:2.2rem; font-weight:800; margin:0; color:#1e293b;"><?= $totalRooms ?></h2>
        <p style="color:#64748b; font-size:1.1rem; margin:0.3rem 0 0 0;">Rooms</p>
      </div>
      <div class="stat-box" style="flex:1; min-width:160px; background:#fff; border-radius:14px; box-shadow:0 2px 12px rgba(30,41,59,0.07); padding:2rem 1.2rem; text-align:center;">
        <div style="font-size:2.5rem; color:#f59e42; margin-bottom:0.5rem;"><i class="fas fa-calendar-check"></i></div>
        <h2 style="font-size:2.2rem; font-weight:800; margin:0; color:#1e293b;"><?= $totalBookings ?></h2>
        <p style="color:#64748b; font-size:1.1rem; margin:0.3rem 0 0 0;">Bookings</p>
      </div>
      <div class="stat-box" style="flex:1; min-width:160px; background:#fff; border-radius:14px; box-shadow:0 2px 12px rgba(30,41,59,0.07); padding:2rem 1.2rem; text-align:center;">
        <div style="font-size:2.5rem; color:#22c55e; margin-bottom:0.5rem;"><i class="fas fa-coins"></i></div>
        <h2 style="font-size:2.2rem; font-weight:800; margin:0; color:#1e293b;"><?= number_format($totalRevenue, 0, ',', '.') ?>đ</h2>
        <p style="color:#64748b; font-size:1.1rem; margin:0.3rem 0 0 0;">Revenue</p>
      </div>
      <div class="stat-box" style="flex:1; min-width:160px; background:#fff; border-radius:14px; box-shadow:0 2px 12px rgba(30,41,59,0.07); padding:2rem 1.2rem; text-align:center;">
        <div style="font-size:2.5rem; color:#2563eb; margin-bottom:0.5rem;"><i class="fas fa-users"></i></div>
        <h2 style="font-size:2.2rem; font-weight:800; margin:0; color:#1e293b;"><?= $totalUsers ?></h2>
        <p style="color:#64748b; font-size:1.1rem; margin:0.3rem 0 0 0;">Customers</p>
      </div>
    </div>

    <!-- Form thêm phòng -->
    <div class="admin-add-room" style="margin:2.5rem 0;">
      <h2>Add New Room</h2>
      <?php if ($addError): ?>
        <div style="color:#dc2626; text-align:center; margin-bottom:1rem;"> <?= htmlspecialchars($addError) ?> </div>
      <?php elseif ($addSuccess): ?>
        <div style="color:#059669; text-align:center; margin-bottom:1rem;"> <?= htmlspecialchars($addSuccess) ?> </div>
      <?php endif; ?>
      <form method="post" class="booking-form" style="max-width:600px; margin:0 auto;" enctype="multipart/form-data">
        <input type="hidden" name="add_room" value="1">
        <div class="form-group">
                                  <label for="room_number">Room Name</label>
                        <input type="text" id="room_number" name="room_number" required>
        </div>
        <div class="form-group">
          <label for="room_type">Room Type</label>
          <select id="room_type" name="room_type" required>
            <option value="">-- Select Room Type --</option>
            <?php foreach ($roomTypes as $rt): ?>
              <option value="<?= $rt['RoomTypeID'] ?>"><?= htmlspecialchars($rt['TypeName']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="branch">Branch</label>
          <select id="branch" name="branch" required>
            <option value="">-- Select Branch --</option>
            <?php foreach ($branches as $b): ?>
              <option value="<?= $b['BranchID'] ?>"><?= htmlspecialchars($b['BranchName']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="capacity">Capacity</label>
          <input type="number" id="capacity" name="capacity" min="1" required>
        </div>
        <div class="form-group">
          <label for="price">Price/Night</label>
          <input type="number" id="price" name="price" min="0" required>
        </div>
        <div class="form-group">
          <label for="description">Room Description</label>
          <textarea id="description" name="description" rows="4" style="width:100%; padding:0.5rem; border:1px solid #cbd5e1; border-radius:5px; resize:vertical;"></textarea>
        </div>
        <div class="form-group">
          <label for="images">Room Images (can choose multiple)</label>
          <input type="file" id="images" name="images[]" accept="image/*" multiple required style="width:100%; padding:0.5rem; border:1px solid #cbd5e1; border-radius:5px;">
          <small style="color:#64748b; margin-top:0.2rem;">Acceptable files: JPG, JPEG, PNG, GIF</small>
        </div>
        <button type="submit" class="btn">Add Room</button>
      </form>
    </div>

               <!-- Danh sách phòng -->
           <div class="admin-room-list" style="margin:2.5rem 0;">
             <h2>Room List</h2>
             <div class="rooms-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:1.5rem;">
               <?php foreach ($roomList as $room): ?>
               <div class="room-card" style="border:1px solid #e2e8f0; border-radius:8px; padding:1rem; background:white;">
                 <div style="height:200px; overflow:hidden; border-radius:5px; margin-bottom:1rem;">
                   <?php if ($room['MainImage']): ?>
                     <img src="../assets/images/<?= htmlspecialchars($room['MainImage']) ?>" alt="Room <?= htmlspecialchars($room['RoomNumber']) ?>" style="width:100%; height:100%; object-fit:cover;">
                   <?php else: ?>
                     <div style="width:100%; height:100%; background:#f1f5f9; display:flex; align-items:center; justify-content:center; color:#64748b;">No Image</div>
                   <?php endif; ?>
                 </div>
                                         <h3 style="margin:0 0 0.5rem 0; color:#1e293b;"><?= htmlspecialchars($room['RoomNumber']) ?></h3>
                 <p style="margin:0.2rem 0; color:#64748b;"><strong>Type:</strong> <?= htmlspecialchars($room['TypeName']) ?></p>
                 <p style="margin:0.2rem 0; color:#64748b;"><strong>Branch:</strong> <?= htmlspecialchars($room['BranchName']) ?></p>
                 <p style="margin:0.2rem 0; color:#64748b;"><strong>Capacity:</strong> <?= htmlspecialchars($room['Capacity']) ?> people</p>
                 <p style="margin:0.2rem 0; color:#64748b;"><strong>Price:</strong> <?= number_format($room['PricePerNight'], 0, ',', '.') ?>đ/night</p>
                 <p style="margin:0.2rem 0; color:#64748b;"><strong>Status:</strong> 
                   <span style="color:<?= $room['CurrentStatus'] == 'available' ? '#059669' : '#dc2626' ?>;">
                     <?= $room['CurrentStatus'] == 'available' ? 'Available' : 'Booked' ?>
                   </span>
                 </p>
                 <?php if (!empty($room['Description'])): ?>
                   <p style="margin:0.5rem 0; color:#64748b; font-size:0.9rem;"><?= htmlspecialchars(substr($room['Description'], 0, 100)) ?><?= strlen($room['Description']) > 100 ? '...' : '' ?></p>
                 <?php endif; ?>
                 <div style="margin-top:1rem; display:flex; gap:0.5rem;">
                   <a href="?edit_room=<?= $room['RoomID'] ?>" class="btn" style="padding:0.3rem 0.7rem; font-size:0.95rem;">Edit</a>
                   <a href="?delete_room=<?= $room['RoomID'] ?>" class="btn" style="background:#dc2626; padding:0.3rem 0.7rem; font-size:0.95rem;" onclick="return confirm('Are you sure you want to delete this room?');">Delete</a>
                 </div>
               </div>
               <?php endforeach; ?>
             </div>
           </div>

    <!-- Đơn đặt phòng gần đây -->
    <div class="admin-recent">
      <h2>Recent Bookings</h2>
      <?php if ($orderError): ?>
        <div style="color:#dc2626; text-align:center; margin-bottom:1rem;"> <?= htmlspecialchars($orderError) ?> </div>
      <?php elseif ($orderSuccess): ?>
        <div style="color:#059669; text-align:center; margin-bottom:1rem;"> <?= htmlspecialchars($orderSuccess) ?> </div>
      <?php endif; ?>
      <?php if ($cancelBookingError): ?>
        <div style="color:#dc2626; text-align:center; margin-bottom:1rem;"> <?= htmlspecialchars($cancelBookingError) ?> </div>
      <?php elseif ($cancelBookingSuccess): ?>
        <div style="color:#059669; text-align:center; margin-bottom:1rem;"> <?= htmlspecialchars($cancelBookingSuccess) ?> </div>
      <?php endif; ?>
      <table class="history-table">
        <thead>
          <tr>
            <th>Customer</th>
            <th>Room</th>
            <th>Type</th>
            <th>Check-in Date</th>
            <th>Check-out Date</th>
            <th>Status</th>
            <th>Payment Method</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentBookings as $b): list($statusText, $statusClass) = getBookingStatus($b['Status']); ?>
          <tr>
            <td><?= htmlspecialchars($b['FullName']) ?></td>
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
              <?php if ($b['Status'] == 'pending'): ?>
                <a href="?approve_booking=<?= $b['BookingID'] ?>" class="btn" style="padding:0.3rem 0.7rem; font-size:0.95rem;">Approve</a>
              <?php endif; ?>
              <?php if ($b['Status'] != 'cancelled'): ?>
                <a href="?cancel_booking=<?= $b['BookingID'] ?>" class="btn" style="background:#dc2626; padding:0.3rem 0.7rem; font-size:0.95rem;" onclick="return confirm('Are you sure you want to cancel this booking?');">Cancel</a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Sửa phòng -->
    <?php if ($editRoom): ?>
    <div class="admin-edit-room" id="edit-room-form" style="max-width:600px; margin:2rem auto; background:#f1f5f9; padding:2rem; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.04);">
      <h2>Edit Room #<?= $editRoom['RoomID'] ?></h2>
      <?php if ($editError): ?>
        <div style="color:#dc2626; text-align:center; margin-bottom:1rem;"> <?= htmlspecialchars($editError) ?> </div>
      <?php elseif ($editSuccess): ?>
        <div style="color:#059669; text-align:center; margin-bottom:1rem;"> <?= htmlspecialchars($editSuccess) ?> </div>
      <?php endif; ?>
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="update_room" value="1">
        <div class="form-group">
          <label for="room_number">Room Name</label>
          <input type="text" id="room_number" name="room_number" required value="<?= htmlspecialchars($editRoom['RoomNumber']) ?>">
        </div>
        <div class="form-group">
          <label for="room_type">Room Type</label>
          <select id="room_type" name="room_type" required>
            <?php foreach ($roomTypes as $rt): ?>
              <option value="<?= $rt['RoomTypeID'] ?>" <?= $editRoom['RoomTypeID'] == $rt['RoomTypeID'] ? 'selected' : '' ?>><?= htmlspecialchars($rt['TypeName']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="branch">Branch</label>
          <select id="branch" name="branch" required>
            <?php foreach ($branches as $b): ?>
              <option value="<?= $b['BranchID'] ?>" <?= $editRoom['BranchID'] == $b['BranchID'] ? 'selected' : '' ?>><?= htmlspecialchars($b['BranchName']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="capacity">Capacity</label>
          <input type="number" id="capacity" name="capacity" min="1" required value="<?= htmlspecialchars($editRoom['Capacity']) ?>">
        </div>
        <div class="form-group">
          <label for="price">Price/Night</label>
          <input type="number" id="price" name="price" min="0" required value="<?= htmlspecialchars($editRoom['PricePerNight']) ?>">
        </div>
        <div class="form-group">
          <label for="description">Room Description</label>
          <textarea id="description" name="description" rows="4"><?= htmlspecialchars($editRoom['Description'] ?? '') ?></textarea>
        </div>
        
        <!-- Hiển thị ảnh hiện tại -->
        <?php if (!empty($editRoomImages)): ?>
        <div class="form-group">
          <label>Current Images</label>
          <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(150px, 1fr)); gap:1rem; margin-top:0.5rem;">
            <?php foreach ($editRoomImages as $img): ?>
            <div style="position:relative; border:1px solid #e2e8f0; border-radius:5px; overflow:hidden;">
              <img src="../assets/images/<?= htmlspecialchars($img['ImagePath']) ?>" alt="Room Image" style="width:100%; height:120px; object-fit:cover;">
              <div style="position:absolute; top:5px; right:5px; background:rgba(0,0,0,0.7); color:white; padding:0.2rem 0.5rem; border-radius:3px; font-size:0.8rem;">
                <?= $img['IsMain'] ? 'Main' : 'Secondary' ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
        
        <div class="form-group">
          <label for="new_images">Add New Images (can choose multiple)</label>
          <input type="file" id="new_images" name="new_images[]" accept="image/*" multiple>
          <small style="color:#64748b; margin-top:0.2rem;">Acceptable files: JPG, JPEG, PNG, GIF</small>
        </div>
        
        <button type="submit" class="btn">Save Changes</button>
        <a href="admin.php" class="btn" style="background:#64748b; margin-left:1rem;">Cancel</a>
      </form>
    </div>
    <?php endif; ?>

    <!-- Xóa phòng -->
    <?php if ($deleteError): ?>
      <div style="color:#dc2626; text-align:center; margin-bottom:1rem;"> <?= htmlspecialchars($deleteError) ?> </div>
    <?php elseif ($deleteSuccess): ?>
      <div style="color:#059669; text-align:center; margin-bottom:1rem;"> <?= htmlspecialchars($deleteSuccess) ?> </div>
    <?php endif; ?>

    <!-- Quản lý khách hàng -->
    <div class="admin-user-list" style="margin:2.5rem 0;">
      <h2>Customer List</h2>
      <?php if ($deleteUserError): ?>
        <div style="color:#dc2626; text-align:center; margin-bottom:1rem;"> <?= htmlspecialchars($deleteUserError) ?> </div>
      <?php elseif ($deleteUserSuccess): ?>
        <div style="color:#059669; text-align:center; margin-bottom:1rem;"> <?= htmlspecialchars($deleteUserSuccess) ?> </div>
      <?php endif; ?>
      <table class="history-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Username</th>
            <th>Email</th>
            <th>Phone Number</th>
            <th>Booking Count</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr>
            <td><?= $u['UserID'] ?></td>
            <td><?= htmlspecialchars($u['FullName']) ?></td>
            <td><?= htmlspecialchars($u['Username']) ?></td>
            <td><?= htmlspecialchars($u['Email']) ?></td>
            <td><?= htmlspecialchars($u['PhoneNumber']) ?></td>
            <td><?= $u['BookingCount'] ?></td>
            <td><?= (empty($u['Status']) || $u['Status'] == 'active') ? 'Active' : 'Inactive' ?></td>
            <td>
              <a href="?view_user=<?= $u['UserID'] ?>" class="btn" style="padding:0.3rem 0.7rem; font-size:0.95rem;">Booking History</a>
              <?php if ($u['RoleID'] != 1): ?>
                <a href="?delete_user=<?= $u['UserID'] ?>" class="btn" style="background:#dc2626; padding:0.3rem 0.7rem; font-size:0.95rem;" onclick="return confirm('Are you sure you want to delete this customer account? This will also delete all their bookings.');">Delete Account</a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if (isset($_GET['view_user'])): 
      $viewID = (int)$_GET['view_user'];
      $stmt = $pdo->prepare('SELECT * FROM userr WHERE UserID = ?');
      $stmt->execute([$viewID]);
      $viewUser = $stmt->fetch();
      $stmt = $pdo->prepare('SELECT b.*, r.RoomNumber, rt.TypeName FROM bookings b JOIN rooms r ON b.RoomID = r.RoomID JOIN roomtypes rt ON r.RoomTypeID = rt.RoomTypeID WHERE b.UserID = ? ORDER BY b.CreatedAt DESC');
      $stmt->execute([$viewID]);
      $userBookings = $stmt->fetchAll();
    ?>
    <div class="admin-user-history" style="max-width:700px; margin:2rem auto; background:#f1f5f9; padding:2rem; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.04);">
      <h3>Booking History for <?= htmlspecialchars($viewUser['FullName']) ?> (<?= htmlspecialchars($viewUser['Username']) ?>)</h3>
      <?php if ($cancelUserBookingError): ?>
        <div style="color:#dc2626; text-align:center; margin-bottom:1rem;"> <?= htmlspecialchars($cancelUserBookingError) ?> </div>
      <?php elseif ($cancelUserBookingSuccess): ?>
        <div style="color:#059669; text-align:center; margin-bottom:1rem;"> <?= htmlspecialchars($cancelUserBookingSuccess) ?> </div>
      <?php endif; ?>
      <table class="history-table">
        <thead>
          <tr>
            <th>Room</th>
            <th>Type</th>
            <th>Check-in Date</th>
            <th>Check-out Date</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($userBookings)): ?>
            <tr><td colspan="6" style="text-align:center; color:#64748b;">No booking history found.</td></tr>
          <?php else: foreach ($userBookings as $b): list($statusText, $statusClass) = getBookingStatus($b['Status']); ?>
            <tr>
              <td><?= htmlspecialchars($b['RoomNumber']) ?></td>
              <td><?= htmlspecialchars($b['TypeName']) ?></td>
              <td><?= htmlspecialchars($b['CheckInDate']) ?></td>
              <td><?= htmlspecialchars($b['CheckOutDate']) ?></td>
              <td><span class="room-status <?= $statusClass ?>"><?= $statusText ?></span></td>
              <td>
                <?php if ($b['Status'] != 'cancelled'): ?>
                  <a href="?cancel_user_booking=<?= $b['BookingID'] ?>&view_user=<?= $viewUser['UserID'] ?>" class="btn" style="background:#dc2626; padding:0.3rem 0.7rem; font-size:0.95rem;" onclick="return confirm('Are you sure you want to cancel this booking?');">Cancel</a>
                <?php else: ?>
                  <span style="color:#64748b;">Already cancelled</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
      <a href="admin.php" class="btn" style="background:#64748b; margin-top:1rem;">Close</a>
    </div>
    <?php endif; ?>

    <!-- Quản lý loại phòng -->
    <div class="admin-roomtype-list" style="margin:2.5rem 0;">
      <h2>Manage Room Types</h2>
      <?php if ($typeError): ?>
        <div style="color:#dc2626; text-align:center; margin-bottom:1rem;"> <?= htmlspecialchars($typeError) ?> </div>
      <?php elseif ($typeSuccess): ?>
        <div style="color:#059669; text-align:center; margin-bottom:1rem;"> <?= htmlspecialchars($typeSuccess) ?> </div>
      <?php endif; ?>
      <form method="post" style="display:flex; gap:1rem; margin-bottom:1rem;">
        <input type="text" name="type_name" placeholder="Room Type Name" required style="flex:1;">
        <button type="submit" name="add_roomtype" class="btn">Add</button>
      </form>
      <table class="history-table">
        <thead>
          <tr><th>ID</th><th>Room Type Name</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach ($roomTypes as $rt): ?>
          <tr>
            <td><?= $rt['RoomTypeID'] ?></td>
            <td><?= htmlspecialchars($rt['TypeName']) ?></td>
            <td>
              <a href="?delete_roomtype=<?= $rt['RoomTypeID'] ?>" class="btn" style="background:#dc2626; padding:0.3rem 0.7rem; font-size:0.95rem;" onclick="return confirm('Are you sure you want to delete this room type?');">Delete</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Quản lý chi nhánh -->
    <div class="admin-branch-list" style="margin:2.5rem 0;">
      <h2>Manage Branches</h2>
      <?php if ($branchError): ?>
        <div style="color:#dc2626; text-align:center; margin-bottom:1rem;"> <?= htmlspecialchars($branchError) ?> </div>
      <?php elseif ($branchSuccess): ?>
        <div style="color:#059669; text-align:center; margin-bottom:1rem;"> <?= htmlspecialchars($branchSuccess) ?> </div>
      <?php endif; ?>
      <form method="post" style="display:flex; gap:1rem; margin-bottom:1rem;">
        <input type="text" name="branch_name" placeholder="Branch Name" required style="flex:1;">
        <button type="submit" name="add_branch" class="btn">Add</button>
      </form>
      <table class="history-table">
        <thead>
          <tr><th>ID</th><th>Branch Name</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach ($branches as $b): ?>
          <tr>
            <td><?= $b['BranchID'] ?></td>
            <td><?= htmlspecialchars($b['BranchName']) ?></td>
            <td>
              <a href="?delete_branch=<?= $b['BranchID'] ?>" class="btn" style="background:#dc2626; padding:0.3rem 0.7rem; font-size:0.95rem;" onclick="return confirm('Are you sure you want to delete this branch?');">Delete</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Thống kê nâng cao -->
    <div class="admin-stats-advanced" style="margin:2.5rem 0;">
      <h2>Advanced Statistics: Revenue & Booking Count by Month</h2>
      <canvas id="bookingRevenueChart" height="80"></canvas>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
      const ctx = document.getElementById('bookingRevenueChart').getContext('2d');
      const bookingRevenueChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: <?= json_encode($months) ?>,
          datasets: [
            {
              label: 'Booking Count',
              data: <?= json_encode($bookingCounts) ?>,
              backgroundColor: 'rgba(14, 165, 233, 0.6)',
              borderColor: 'rgba(14, 165, 233, 1)',
              borderWidth: 1
            },
            {
              label: 'Revenue (VND)',
              data: <?= json_encode($revenues) ?>,
              backgroundColor: 'rgba(34, 197, 94, 0.5)',
              borderColor: 'rgba(34, 197, 94, 1)',
              type: 'line',
              yAxisID: 'y1',
              tension: 0.3
            }
          ]
        },
        options: {
          responsive: true,
          interaction: { mode: 'index', intersect: false },
          stacked: false,
          plugins: {
            legend: { position: 'top' },
            title: { display: false }
          },
          scales: {
            y: {
              type: 'linear',
              display: true,
              position: 'left',
              title: { display: true, text: 'Booking Count' }
            },
            y1: {
              type: 'linear',
              display: true,
              position: 'right',
              grid: { drawOnChartArea: false },
              title: { display: true, text: 'Revenue (VND)' }
            }
          }
        }
      });
    </script>

    <!-- Quản lý admin -->
    <div class="admin-admin-list" style="margin:2.5rem 0;">
      <h2>Manage Admins</h2>
      <?php if ($adminError): ?>
        <div style="color:#dc2626; text-align:center; margin-bottom:1rem;"> <?= htmlspecialchars($adminError) ?> </div>
      <?php elseif ($adminSuccess): ?>
        <div style="color:#059669; text-align:center; margin-bottom:1rem;"> <?= htmlspecialchars($adminSuccess) ?> </div>
      <?php endif; ?>
      <h3>Admin List</h3>
      <table class="history-table">
        <thead>
          <tr><th>ID</th><th>Full Name</th><th>Username</th><th>Email</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach ($admins as $a): ?>
          <tr>
            <td><?= $a['UserID'] ?></td>
            <td><?= htmlspecialchars($a['FullName']) ?></td>
            <td><?= htmlspecialchars($a['Username']) ?></td>
            <td><?= htmlspecialchars($a['Email']) ?></td>
            <td>
              <?php if ($a['UserID'] != $_SESSION['user']['UserID']): ?>
                <a href="?remove_admin=<?= $a['UserID'] ?>" class="btn" style="background:#dc2626; padding:0.3rem 0.7rem; font-size:0.95rem;" onclick="return confirm('Are you sure you want to demote this admin?');">Demote</a>
              <?php else: ?>
                <span style="color:#64748b;">(You)</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <h3>Add New Admin</h3>
      <form method="post" style="display:flex; gap:1rem; max-width:500px; margin-bottom:1rem;">
        <select name="user_id" required style="flex:1;">
          <option value="">-- Select user to promote to admin --</option>
          <?php foreach ($usersForAdmin as $u): ?>
            <option value="<?= $u['UserID'] ?>"><?= htmlspecialchars($u['FullName']) ?> (<?= htmlspecialchars($u['Username']) ?>)</option>
          <?php endforeach; ?>
        </select>
        <button type="submit" name="make_admin" class="btn">Promote</button>
      </form>
    </div>
  </section>
</main>

<script>
// Scroll to edit form when edit_room parameter is present
if (window.location.search.includes('edit_room')) {
    setTimeout(function() {
        const editForm = document.getElementById('edit-room-form');
        if (editForm) {
            editForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }, 100);
}
</script>

<?php include '../includes/footer.php'; ?>
