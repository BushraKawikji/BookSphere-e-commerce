<?php
session_start();
?>
<?php


require_once '../config/db.php';


if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    if (
        !empty($name) &&
        !empty($email) &&
        !empty($phone) &&
        !empty($password) &&
        !empty($confirmPassword)
    ) {
        if ($password === $confirmPassword) {
            // Insert user into database
            $stmt = $conn->prepare("INSERT INTO users (name, email, phone, pass) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $phone, $password);
            $insertResult = $stmt->execute();
            // check the role to redirect
            $userId = $conn->insert_id;
            $stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $role = $row['role'];
            if ($insertResult) {
                if ($role === 'user') {
                    header("location: ../index.php");
                    exit();
                } else {
                    header("location: admin.php");
                    exit();
                }
            }
            $_SESSION['user_id'] = $userId;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Register | BookSphere</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <!-- Main Template CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

</head>

<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8 col-sm-10">

                <div class="card shadow mt-5">
                    <div class="card-body">
                        <h3 class="text-center mb-4">Create Account</h3>

                        <form action="<?= $_SERVER['PHP_SELF'] ?>" method="POST" id="registrationForm">
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" placeholder="+962 7XX XXX XXX">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirmPassword" name="confirmPassword">
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                Register
                            </button>
                        </form>

                        <p class="text-center mt-3">
                            Already have an account?
                            <a href="login.php">Sign In</a>
                        </p>

                    </div>
                </div>

            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/main.js"></script>
</body>

</html>