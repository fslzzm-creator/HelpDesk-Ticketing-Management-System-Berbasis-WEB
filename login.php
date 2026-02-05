<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new Auth();

if ($auth->isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = $auth->login($username, $password);
    
    if ($result === true) {
        header("Location: dashboard.php");
        exit();
    } else {
        $error = $result;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Ticketing System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h2>Login to Ticketing System</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-block">Login</button>
            </form>
            
            <p class="text-center mt-3">
                Don't have an account? <a href="register.php">Register here</a>
            </p>
            
            <p class="text-center mt-1">
            </p>
        </div>
    </div>
</body>
</html>
