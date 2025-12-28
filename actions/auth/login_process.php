<?php
session_start();

require_once __DIR__ . '/../../helpers/db_queries.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        $sql = "SELECT user_id, name, role, pass FROM users WHERE email = ?";
        $type = "s";
        $param = array($email);
        $result = selectQuery($conn, $sql, $type, $param);
        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();

            if ($password === $row['pass']) {

                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['name']    = $row['name'];
                $_SESSION['role']    = $row['role'];

                if ($row['role'] === 'user') {
                    header("Location: ../../public/shop.php");
                } elseif ($row['role'] === 'admin') {
                    header("Location: ../../admin/index.php");
                }
            } else {
                echo "Email or Password Wrong";
            }
        } else {
            echo "Email doesn't exist";
        }

        $conn->close();
    }
}
