<?php
session_start();
include '../includes/header.php';
require_once '../config/db.php';

$error = '';
// Display message if redirected from auth.php
$auth_message = $_SESSION['auth_message'] ?? '';
unset($_SESSION['auth_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === '' || $password === '') {
        $error = 'Please enter all required information.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM userr WHERE Username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['Password'])) {
            // Save user session
            $_SESSION['user'] = [
                'UserID' => $user['UserID'],
                'FullName' => $user['FullName'],
                'Username' => $user['Username'],
                'Email' => $user['Email'],
                'RoleID' => $user['RoleID']
            ];
            header('Location: ../index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<main>
  <section class="auth-section">
    <h1>Login</h1>
    <?php if ($auth_message): ?>
      <div style="color: #dc2626; text-align:center; margin-bottom:1rem;"> <?= htmlspecialchars($auth_message) ?> </div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div style="color: #dc2626; text-align:center; margin-bottom:1rem;"> <?= htmlspecialchars($error) ?> </div>
    <?php endif; ?>
    <form class="auth-form" action="" method="post">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="btn">Login</button>
    </form>
    <div class="auth-link">
      Don't have an account? <a href="register.php">Register now</a>
    </div>
  </section>
</main>
<?php include '../includes/footer.php'; ?>
