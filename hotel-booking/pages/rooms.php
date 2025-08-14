<?php require_once '../includes/auth.php'; ?>
<?php include '../includes/header.php'; ?>
<?php require_once '../config/db.php'; ?>
<?php
// Lấy danh sách chi nhánh và loại phòng cho filter
$branches = $pdo->query('SELECT * FROM branches ORDER BY BranchName')->fetchAll();
$roomTypes = $pdo->query('SELECT * FROM roomtypes ORDER BY TypeName')->fetchAll();

// Xử lý filter
$selectedBranch = $_GET['branch'] ?? '';
$selectedType = $_GET['type'] ?? '';
$minPrice = $_GET['min_price'] ?? '';
$maxPrice = $_GET['max_price'] ?? '';
$minCapacity = $_GET['min_capacity'] ?? '';
$maxCapacity = $_GET['max_capacity'] ?? '';

// Tạo query với filter
$sql = "SELECT r.*, rt.TypeName, b.BranchName,
        CASE 
            WHEN EXISTS (
                SELECT 1 FROM bookings 
                WHERE RoomID = r.RoomID 
                AND Status = 'approved'
                AND CURRENT_DATE BETWEEN CheckInDate AND CheckOutDate
            ) THEN 'booked'
            ELSE 'available'
        END as Status,
        (SELECT ImagePath FROM room_images WHERE RoomID = r.RoomID AND IsMain = 1 LIMIT 1) as MainImage
        FROM rooms r
        JOIN roomtypes rt ON r.RoomTypeID = rt.RoomTypeID
        JOIN branches b ON r.BranchID = b.BranchID
        WHERE 1=1";

$params = [];

if ($selectedBranch) {
    $sql .= " AND r.BranchID = ?";
    $params[] = $selectedBranch;
}

if ($selectedType) {
    $sql .= " AND r.RoomTypeID = ?";
    $params[] = $selectedType;
}

if ($minPrice) {
    $sql .= " AND r.PricePerNight >= ?";
    $params[] = $minPrice;
}

if ($maxPrice) {
    $sql .= " AND r.PricePerNight <= ?";
    $params[] = $maxPrice;
}

if ($minCapacity) {
    $sql .= " AND r.Capacity >= ?";
    $params[] = $minCapacity;
}

if ($maxCapacity) {
    $sql .= " AND r.Capacity <= ?";
    $params[] = $maxCapacity;
}

$sql .= " ORDER BY r.RoomID DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll();
?>
<style>
.filter-section {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}
.filter-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    align-items: end;
}
.filter-group {
    display: flex;
    flex-direction: column;
}
.filter-group label {
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #374151;
}
.filter-group input,
.filter-group select {
    padding: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    font-size: 0.9rem;
}
.filter-actions {
    display: flex;
    gap: 1rem;
    align-items: end;
}
.filter-actions .btn {
    padding: 0.5rem 1rem;
    height: fit-content;
}
.price-range,
.capacity-range {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}
.price-range input,
.capacity-range input {
    width: 100px;
}
.rooms-count {
    text-align: center;
    margin: 1rem 0;
    color: #64748b;
    font-size: 1.1rem;
}
.btn-cta {
  background: linear-gradient(90deg, #fbbf24 0%, #f59e42 100%) !important;
  color: #1e293b !important;
  font-weight: 700;
  border: none;
  outline: none;
  box-shadow: 0 2px 12px rgba(251,191,36,0.15);
  transition: background 0.2s, color 0.2s, transform 0.15s;
}
.btn-cta:hover:not(.disabled) {
  background: linear-gradient(90deg, #f59e42 0%, #fbbf24 100%) !important;
  color: #fff !important;
  transform: translateY(-2px) scale(1.04);
}
.btn-cta.disabled {
  opacity: 0.6;
  pointer-events: none;
}
</style>

<main>
  <section class="rooms-list">
    <h1>Room List</h1>
    
    <!-- Filter Section -->
    <div class="filter-section">
      <h2 style="margin-bottom: 1rem; color: #1e293b;">Filter Rooms</h2>
      <form method="get" class="filter-form">
        <div class="filter-group">
          <label for="branch">Branch</label>
          <select id="branch" name="branch">
            <option value="">All Branches</option>
            <?php foreach ($branches as $branch): ?>
              <option value="<?= $branch['BranchID'] ?>" <?= $selectedBranch == $branch['BranchID'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($branch['BranchName']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="filter-group">
          <label for="type">Room Type</label>
          <select id="type" name="type">
            <option value="">All Room Types</option>
            <?php foreach ($roomTypes as $type): ?>
              <option value="<?= $type['RoomTypeID'] ?>" <?= $selectedType == $type['RoomTypeID'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($type['TypeName']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="filter-group">
          <label>Price (VND)</label>
          <div class="price-range">
            <input type="number" name="min_price" placeholder="From" value="<?= htmlspecialchars($minPrice) ?>" min="0">
            <span>-</span>
            <input type="number" name="max_price" placeholder="To" value="<?= htmlspecialchars($maxPrice) ?>" min="0">
          </div>
        </div>
        
        <div class="filter-group">
          <label>Capacity (people)</label>
          <div class="capacity-range">
            <input type="number" name="min_capacity" placeholder="From" value="<?= htmlspecialchars($minCapacity) ?>" min="1">
            <span>-</span>
            <input type="number" name="max_capacity" placeholder="To" value="<?= htmlspecialchars($maxCapacity) ?>" min="1">
          </div>
        </div>
        
        <div class="filter-actions">
          <button type="submit" class="btn">Filter</button>
          <a href="rooms.php" class="btn" style="background:#64748b;">Clear Filters</a>
        </div>
      </form>
    </div>
    
    <!-- Search Results -->
    <div class="rooms-count">
      Found <?= count($rooms) ?> rooms
      <?php if ($selectedBranch || $selectedType || $minPrice || $maxPrice || $minCapacity || $maxCapacity): ?>
        with selected filters
      <?php endif; ?>
    </div>
    
    <div class="rooms-grid">
      <?php foreach ($rooms as $room): 
        $isAvailable = $room['Status'] == 'available';
      ?>
      <div class="room-card">
        <a href="room_detail.php?id=<?= $room['RoomID'] ?>" style="text-decoration:none; color:inherit;">
          <div style="height:200px; overflow:hidden; border-radius:5px; margin-bottom:1rem;">
            <?php if ($room['MainImage']): ?>
              <img src="../assets/images/<?= htmlspecialchars($room['MainImage']) ?>" alt="Room <?= htmlspecialchars($room['RoomNumber']) ?>" style="width:100%; height:100%; object-fit:cover;">
            <?php else: ?>
              <img src="../assets/images/room<?= htmlspecialchars($room['RoomID']) ?>.jpg" alt="Room <?= htmlspecialchars($room['TypeName']) ?>" style="width:100%; height:100%; object-fit:cover;">
            <?php endif; ?>
          </div>
                          <h3><?= htmlspecialchars($room['RoomNumber']) ?> (<?= htmlspecialchars($room['TypeName']) ?>)</h3>
          <p>Branch: <?= htmlspecialchars($room['BranchName']) ?></p>
          <p>Capacity: <?= htmlspecialchars($room['Capacity']) ?> people</p>
          <p>Price: <?= number_format($room['PricePerNight'], 0, ',', '.') ?>đ/night</p>
          <?php if (!empty($room['Description'])): ?>
            <p style="color:#64748b; font-size:0.9rem; margin:0.5rem 0;"><?= htmlspecialchars(substr($room['Description'], 0, 100)) ?><?= strlen($room['Description']) > 100 ? '...' : '' ?></p>
          <?php endif; ?>
          <span class="room-status <?= $isAvailable ? 'available' : 'booked' ?>"><?= $isAvailable ? 'Available' : 'Booked' ?></span>
        </a>
        <div style="margin-top:1rem; display:flex; gap:0.5rem; justify-content:center;">
          <?php if ($isAvailable): ?>
            <a href="booking.php?room=<?= $room['RoomID'] ?>" class="btn btn-cta">Book Now</a>
          <?php else: ?>
            <a href="#" class="btn btn-cta disabled" onclick="return false;" style="opacity:0.6; pointer-events:none;">Book Now</a>
          <?php endif; ?>
          <a href="room_detail.php?id=<?= $room['RoomID'] ?>" class="btn" style="background:#64748b;">View Details</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    
    <?php if (empty($rooms)): ?>
    <div style="text-align:center; padding:3rem; color:#64748b;">
      <h3>No rooms found</h3>
      <p>Try changing the filters to find suitable rooms</p>
      <a href="rooms.php" class="btn" style="margin-top:1rem;">View all rooms</a>
    </div>
    <?php endif; ?>
  </section>
</main>
<?php include '../includes/footer.php'; ?>
