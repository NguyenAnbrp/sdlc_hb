<?php include 'includes/header.php'; ?>
<?php require_once 'config/db.php'; ?>
<!-- Swiper CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<script>
// Scroll to top when page loads
window.onload = function() {
  window.scrollTo(0, 0);
};
</script>
<style>
body, .home-banner-content, .home-banner-content h1, .home-banner-content p, .home-banner-content .btn-cta {
  font-family: 'Poppins', Arial, Helvetica, sans-serif !important;
}

/* Hero Section with Slideshow */
.home-banner-slider {
  position: relative;
  width: 100%;
  max-width: 1100px;
  margin: 1rem auto 1.5rem auto;
  border-radius: 2rem;
  overflow: hidden;
  box-shadow: 0 4px 24px rgba(14,165,233,0.10);
  opacity: 0;
  animation: fadeIn 0.5s ease-in-out forwards;
}
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}
.home-banner-slider .swiper-slide img {
  width: 100%;
  height: 480px;
  object-fit: cover;
  display: block;
}
.home-banner-content {
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #fff;
  background: none !important;
  z-index: 2;
  text-align: center;
  padding: 2.5rem 1rem 2rem 1rem;
  pointer-events: none;
}
.home-banner-content h1, .home-banner-content p, .home-banner-content .btn-cta {
  pointer-events: auto;
}
.home-banner-content h1 {
  font-size: 2.3rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
  letter-spacing: 0.5px;
  color: #fff;
  text-shadow: 0 6px 24px rgba(0,0,0,0.38), 0 2px 8px rgba(30,41,59,0.13);
}
.home-banner-content p {
  font-size: 1.18rem;
  margin-bottom: 2.2rem;
  color: #f3f4f6;
  font-weight: 400;
  text-shadow: 0 2px 8px rgba(0,0,0,0.22);
}
.home-banner-content .btn-cta {
  font-size: 1.18rem;
  padding: 1.1rem 2.8rem;
  font-weight: 700;
  border-radius: 18px;
  background: linear-gradient(90deg, #ff9800 0%, #ff5722 100%);
  color: #fff;
  border: 2.5px solid rgba(255,255,255,0.35);
  box-shadow: 0 4px 24px rgba(255,152,0,0.18), 0 2px 12px rgba(0,0,0,0.10);
  outline: none;
  transition: background 0.2s, color 0.2s, transform 0.15s, box-shadow 0.2s;
  cursor: pointer;
  text-decoration: none;
  display: inline-block;
  text-shadow: 0 2px 8px rgba(0,0,0,0.18);
}
.home-banner-content .btn-cta:hover {
  background: linear-gradient(90deg, #ffb300 0%, #ff7043 100%);
  color: #fff;
  transform: translateY(-2px) scale(1.05);
  box-shadow: 0 8px 32px rgba(255,152,0,0.22), 0 2px 12px rgba(0,0,0,0.13);
}

/* Popular Destinations Section */
.popular-destinations {
  margin: 2rem 0;
}
.section-title {
  text-align: center;
  margin-bottom: 3rem;
}
.section-title h2 {
  font-size: 2.5rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 1rem;
}
.section-title p {
  font-size: 1.1rem;
  color: #64748b;
  max-width: 600px;
  margin: 0 auto;
}
.destinations-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}
.destination-card {
  background: #fff;
  border-radius: 15px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
  transition: transform 0.3s, box-shadow 0.3s;
}
.destination-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}
.destination-image {
  height: 200px;
  overflow: hidden;
}
.destination-info {
  padding: 1.5rem;
  text-align: center;
}
.destination-info h3 {
  font-size: 1.3rem;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 0.5rem;
}
.destination-info p {
  color: #64748b;
  font-size: 0.95rem;
}

/* Featured Hotels Section */
.featured-hotels {
  margin: 4rem 0;
  background: #f8fafc;
  padding: 4rem 0;
}
.hotels-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}
.hotel-card {
  background: #fff;
  border-radius: 15px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
  transition: transform 0.3s, box-shadow 0.3s;
}
.hotel-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}
.hotel-image {
  height: 220px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
  font-size: 1.1rem;
}
.hotel-info {
  padding: 1.5rem;
}
.hotel-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.5rem;
}
.hotel-name {
  font-size: 1.2rem;
  font-weight: 600;
  color: #1e293b;
}
.hotel-rating {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  color: #f59e0b;
  font-weight: 600;
}
.hotel-location {
  color: #64748b;
  font-size: 0.95rem;
  margin-bottom: 1rem;
}
.hotel-price {
  display: flex;
  align-items: baseline;
  gap: 0.3rem;
}
.price-amount {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
}
.price-text {
  color: #64748b;
  font-size: 0.9rem;
}

/* Why Choose Us Section */
.why-choose-us {
  margin: 4rem 0;
}
.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2.5rem;
  max-width: 1200px;
  margin: 0 auto;
}
.feature-card {
  text-align: center;
  padding: 2rem 1rem;
}
.feature-icon {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #0ea5e9 0%, #3b82f6 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem auto;
  color: white;
  font-size: 2rem;
}
.feature-card h3 {
  font-size: 1.3rem;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 1rem;
}
.feature-card p {
  color: #64748b;
  line-height: 1.6;
  font-size: 1rem;
}

/* Responsive Design */
@media (max-width: 900px) {
  .home-banner-slider .swiper-slide img { height: 240px; }
  .home-banner-content h1 { font-size: 1.5rem; }
  .section-title h2 { font-size: 2rem; }
  .destinations-grid { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); }
  .hotels-grid { grid-template-columns: 1fr; }
  .features-grid { grid-template-columns: 1fr; }
}
@media (max-width: 600px) {
  .home-banner-slider .swiper-slide img { height: 160px; }
  .section-title h2 { font-size: 1.8rem; }
  .destinations-grid { grid-template-columns: 1fr; }
}
</style>

<div class="home-banner-slider">
  <div class="swiper home-swiper">
    <div class="swiper-wrapper">
      <div class="swiper-slide"><img src="assets/images/imghome/1.png" alt="Banner 1"></div>
      <div class="swiper-slide"><img src="assets/images/imghome/2.png" alt="Banner 2"></div>
      <div class="swiper-slide"><img src="assets/images/imghome/3.png" alt="Banner 3"></div>
      <div class="swiper-slide"><img src="assets/images/imghome/4.png" alt="Banner 4"></div>
    </div>
  </div>
  <div class="home-banner-content">
    <h1>Find Your Perfect Stay</h1>
    <p>Discover amazing hotels, resorts, and accommodations worldwide at the best prices</p>
    <a href="/hotel-booking/pages/rooms.php" class="btn-cta">Search Hotels</a>
  </div>
</div>

<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
var homeSwiper = new Swiper('.home-swiper', {
  loop: true,
  autoplay: { delay: 3500, disableOnInteraction: false },
  speed: 900,
  effect: 'fade',
});
</script>

<main>
  <!-- Popular Destinations Section -->
  <section class="popular-destinations">
    <div class="section-title">
      <h2>Popular Destinations</h2>
      <p>Explore our most booked destinations worldwide</p>
    </div>
         <div class="destinations-grid">
       <div class="destination-card">
         <div class="destination-image">
           <img src="assets/images/imghome/1.png" alt="Paris, France" style="width:100%; height:100%; object-fit:cover;">
         </div>
         <div class="destination-info">
           <h3>Paris, France</h3>
           <p>1,247 hotels</p>
         </div>
       </div>
       <div class="destination-card">
         <div class="destination-image">
           <img src="assets/images/imghome/2.png" alt="Tokyo, Japan" style="width:100%; height:100%; object-fit:cover;">
         </div>
         <div class="destination-info">
           <h3>Tokyo, Japan</h3>
           <p>892 hotels</p>
         </div>
       </div>
       <div class="destination-card">
         <div class="destination-image">
           <img src="assets/images/imghome/3.png" alt="New York, USA" style="width:100%; height:100%; object-fit:cover;">
         </div>
         <div class="destination-info">
           <h3>New York, USA</h3>
           <p>1,150 hotels</p>
         </div>
       </div>
       <div class="destination-card">
         <div class="destination-image">
           <img src="assets/images/imghome/4.png" alt="London, UK" style="width:100%; height:100%; object-fit:cover;">
         </div>
         <div class="destination-info">
           <h3>London, UK</h3>
           <p>934 hotels</p>
         </div>
       </div>
     </div>
  </section>

  <!-- Featured Hotels Section -->
  <section class="featured-hotels">
    <div class="section-title">
      <h2>Featured Hotels</h2>
      <p>Hand-picked hotels with exceptional ratings</p>
    </div>
    <div class="hotels-grid">
      <?php
      $sql = "SELECT r.*, rt.TypeName, b.BranchName, (SELECT ImagePath FROM room_images WHERE RoomID = r.RoomID AND IsMain = 1 LIMIT 1) as MainImage
              FROM rooms r
              JOIN roomtypes rt ON r.RoomTypeID = rt.RoomTypeID
              JOIN branches b ON r.BranchID = b.BranchID
              ORDER BY r.RoomID DESC LIMIT 3";
      $rooms = $pdo->query($sql)->fetchAll();
      foreach ($rooms as $room): ?>
      <a href="/hotel-booking/pages/room_detail.php?id=<?= $room['RoomID'] ?>" style="text-decoration:none; color:inherit;">
        <div class="hotel-card">
          <div class="hotel-image">
            <?php if ($room['MainImage']): ?>
              <img src="assets/images/<?= htmlspecialchars($room['MainImage']) ?>" alt="<?= htmlspecialchars($room['RoomNumber']) ?>" style="width:100%; height:100%; object-fit:cover;">
            <?php else: ?>
              <div style="display:flex; align-items:center; justify-content:center; width:100%; height:100%; background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white; font-weight:600; font-size:1.1rem;">
                <?= htmlspecialchars($room['RoomNumber']) ?> - <?= htmlspecialchars($room['TypeName']) ?>
              </div>
            <?php endif; ?>
          </div>
          <div class="hotel-info">
            <div class="hotel-header">
              <div class="hotel-name"><?= htmlspecialchars($room['RoomNumber']) ?></div>
              <div class="hotel-rating">
                <i class="fas fa-star"></i>
                <span>4.9</span>
              </div>
            </div>
            <div class="hotel-location"><?= htmlspecialchars($room['BranchName']) ?></div>
            <div class="hotel-price">
              <span class="price-amount"><?= number_format($room['PricePerNight'], 0, ',', '.') ?>đ</span>
              <span class="price-text">per night</span>
            </div>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Why Choose Us Section -->
  <section class="why-choose-us">
    <div class="section-title">
      <h2>Why Choose Hotel Booking?</h2>
      <p>We make hotel booking simple, secure, and rewarding</p>
    </div>
    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon">
          <i class="fas fa-shield-alt"></i>
        </div>
        <h3>Secure Booking</h3>
        <p>Your personal and payment information is always protected with bank-level security</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">
          <i class="fas fa-clock"></i>
        </div>
        <h3>24/7 Support</h3>
        <p>Our customer service team is available around the clock to help you</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">
          <i class="fas fa-medal"></i>
        </div>
        <h3>Best Price Guarantee</h3>
        <p>Find a lower price elsewhere? We'll match it and give you an extra discount</p>
      </div>
    </div>
  </section>
</main>

<?php include 'includes/footer.php'; ?>
