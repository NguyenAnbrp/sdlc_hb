<?php
include '../includes/header.php';
require_once '../config/db.php';

$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($name === '' || $username === '' || $email === '' || $password === '' || $confirm === '') {
        $error = 'Please enter all required information.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{4,}$/', $username)) {
        $error = 'Username must be at least 4 characters, containing only letters, numbers, and underscores.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif ($password !== $confirm) {
        $error = 'Password confirmation does not match.';
    } else {
        // Check if username already exists
        $stmt = $pdo->prepare('SELECT * FROM userr WHERE Username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = 'Username is already taken.';
        } else {
            // Check if email already exists
            $stmt = $pdo->prepare('SELECT * FROM userr WHERE Email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email is already registered.';
            } else {
                // Hash password
                $hash = password_hash($password, PASSWORD_DEFAULT);
                // Add new user (default RoleID = 2 for regular user)
                $stmt = $pdo->prepare('INSERT INTO userr (FullName, Username, Email, Password, RoleID) VALUES (?, ?, ?, ?, 2)');
                if ($stmt->execute([$name, $username, $email, $hash])) {
                    $success = 'Registration successful! You can now login.';
                } else {
                    $error = 'Registration failed, please try again.';
                }
            }
        }
    }
}
?>
<main>
  <section class="auth-section">
    <h1>Create Account</h1>
    <?php if ($error): ?>
      <div style="color: #dc2626; text-align:center; margin-bottom:1rem;"> <?= htmlspecialchars($error) ?> </div>
    <?php elseif ($success): ?>
      <div style="color: #059669; text-align:center; margin-bottom:1rem;"> <?= htmlspecialchars($success) ?> </div>
    <?php endif; ?>
    <form class="auth-form" action="" method="post">
      <div class="form-group">
        <label for="name">Full Name</label>
        <input type="text" id="name" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>
      <div class="form-group">
        <label for="confirm_password">Confirm Password</label>
        <input type="password" id="confirm_password" name="confirm_password" required>
      </div>
      <button type="submit" class="btn">Register</button>
    </form>
    <div class="auth-link">
      Already have an account? <a href="login.php">Login</a>
    </div>
  </section>
</main>
<?php include '../includes/footer.php'; ?>
