<?php
session_start(); // admin account: username (admin), password (admin123)
include 'includes/db.php';
$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST["username"]);
    $password = mysqli_real_escape_string($conn, $_POST["password"]);
    $sql = "SELECT * FROM users
            WHERE username='$username'
            AND password='$password'
            AND role='admin'";

    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $_SESSION["admin"] = $username;
        header("Location: index.html");  
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<?php include 'includes/header.php'; ?>
<div class="login-container">
    <h2 class="login-title">Admin Login</h2>
    <div class="login-card">
        <?php if($error != "") { ?>
            <p style="color:red;text-align:center;margin-bottom:20px;">
                <?php echo $error; ?>
            </p>
        <?php } ?>
        <form method="POST">
            <label>Username</label>
            <input
                type="text"
                name="username"
                placeholder="Enter username"
                required
            >
            <label>Password</label>
            <input
                type="password"
                name="password"
                placeholder="Enter password"
                required
            >
            <button type="submit" class="login-btn">
                Login
            </button>
        </form>
    </div>
</div>
<?php include 'includes/footer.php'; ?>