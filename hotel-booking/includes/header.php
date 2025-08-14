<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Booking</title>
    <!-- Link CSS -->
    <link rel="stylesheet" href="/hotel-booking/assets/style.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<header>
  <nav>
    <div class="nav-container">
      <div class="nav-brand">
                 <a href="/hotel-booking/">
           <img src="/hotel-booking/assets/images/imghome/logohotel.jpg" alt="Hotel Logo" class="nav-logo">
           <span class="nav-brand-text">Hotel Booking</span>
         </a>
      </div>
      <button class="nav-toggle" id="navToggle" aria-label="Open menu"><i class="fas fa-bars"></i></button>
      <ul class="nav-menu" id="navMenu">
        <li><a href="/hotel-booking/">Home</a></li>
        <li><a href="/hotel-booking/pages/rooms.php">Room List</a></li>
        <?php if (isset($_SESSION['user'])): ?>
          <li><a href="/hotel-booking/pages/profile.php">Account</a></li>
          <?php if ($_SESSION['user']['RoleID'] == 1): ?>
            <li><a href="/hotel-booking/pages/admin.php">Admin</a></li>
          <?php endif; ?>
          <li><a href="/hotel-booking/auth/logout.php">Logout</a></li>
        <?php else: ?>
          <li><a href="/hotel-booking/auth/login.php">Login</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </nav>
</header>
<script>
const navToggle = document.getElementById('navToggle');
const navMenu = document.getElementById('navMenu');
function setNavDisplay() {
  if (window.innerWidth > 900) {
    navMenu.classList.remove('nav-menu-mobile');
    navMenu.classList.remove('nav-menu-open');
    navToggle.style.display = 'none';
    navMenu.style.display = 'flex';
  } else {
    navMenu.classList.add('nav-menu-mobile');
    navMenu.classList.remove('nav-menu-open');
    navToggle.style.display = 'block';
    navMenu.style.display = 'none';
  }
}
setNavDisplay();
window.addEventListener('resize', setNavDisplay);
navToggle.addEventListener('click', function(e) {
  if (navMenu.style.display === 'block') {
    navMenu.style.display = 'none';
    navMenu.classList.remove('nav-menu-open');
    document.body.classList.remove('nav-menu-overlay');
  } else {
    navMenu.style.display = 'block';
    navMenu.classList.add('nav-menu-open');
    document.body.classList.add('nav-menu-overlay');
    // Position menu below the button
    const rect = navToggle.getBoundingClientRect();
    navMenu.style.top = (rect.bottom + 8 + window.scrollY) + 'px';
    navMenu.style.right = (window.innerWidth - rect.right - 8) + 'px';
  }
});
document.addEventListener('click', function(e) {
  if (window.innerWidth <= 900 && navMenu.style.display === 'block' && !navMenu.contains(e.target) && !navToggle.contains(e.target)) {
    navMenu.style.display = 'none';
    navMenu.classList.remove('nav-menu-open');
    document.body.classList.remove('nav-menu-overlay');
  }
});
const navLinks = navMenu.querySelectorAll('a');
navLinks.forEach(link => link.addEventListener('click', () => {
  if (window.innerWidth <= 900) {
    navMenu.style.display = 'none';
    navMenu.classList.remove('nav-menu-open');
    document.body.classList.remove('nav-menu-overlay');
  }
}));
</script>
