<?php
session_start();
require_once __DIR__ . '/../helpers/db_queries.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user info
$sqlUser = "SELECT name, email, phone FROM users WHERE user_id = ?";
$resultUser = selectQuery($conn, $sqlUser, "i", [$user_id]);
$user = $resultUser->fetch_assoc();

// Get Cart Items
$sqlCart = "SELECT cart.cart_id, cart.quantity, books.book_id, books.book_name, books.price 
            FROM cart 
            JOIN books ON cart.book_id = books.book_id
            WHERE cart.user_id = ?";

$resultCart = selectQuery($conn, $sqlCart, "i", [$user_id]);

// Calculate totals
$subtotal = 0;
$cartItems = [];
if ($resultCart->num_rows > 0) {
    while ($row = $resultCart->fetch_assoc()) {
        $cartItems[] = $row;
        $subtotal += $row['price'] * $row['quantity'];
    }
}

// Redirect if cart is empty
if (count($cartItems) === 0) {
    $_SESSION['error_message'] = "Your cart is empty!";
    header("Location: cart.php");
    exit();
}

$shipping = 0;
$total = $subtotal + $shipping;

// Handle Place Order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['placeOrder'])) {
    $fullName = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $city = trim($_POST['city']);
    $address = trim($_POST['address']);
    $paymentMethod = $_POST['payment_method'];
    $notes = trim($_POST['notes'] ?? '');

    // Validation
    if (empty($fullName) || empty($phone) || empty($email) || empty($city) || empty($address)) {
        $_SESSION['error_message'] = "Please fill in all required fields!";
    } else {
        // Insert order
        $sqlOrder = "INSERT INTO orders (user_id, total_amount, shipping_address, city, phone, payment_method, notes, status, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())";

        $resultOrder = executeQuery($conn, $sqlOrder, "idsssss", [
            $user_id,
            $total,
            $address,
            $city,
            $phone,
            $paymentMethod,
            $notes
        ]);

        if ($resultOrder) {
            $order_id = $conn->insert_id;

            // Insert order items
            foreach ($cartItems as $item) {
                $sqlOrderItem = "INSERT INTO order_items (order_id, book_id, quantity, price) VALUES (?, ?, ?, ?)";
                executeQuery($conn, $sqlOrderItem, "iiid", [
                    $order_id,
                    $item['book_id'],
                    $item['quantity'],
                    $item['price']
                ]);
            }

            // Clear cart
            $sqlClearCart = "DELETE FROM cart WHERE user_id = ?";
            executeQuery($conn, $sqlClearCart, "i", [$user_id]);

            $_SESSION['success_message'] = "Order placed successfully! Order #$order_id";
            header("Location: orders.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Failed to place order. Please try again.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Checkout | BookSphere</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/LineIcons.3.0.css">
    <link rel="stylesheet" href="assets/css/main.css">
</head>

<body>

    <!-- Header -->
    <div class="border-bottom py-3">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <a href="shop.php" class="text-decoration-none">
                    <h4 class="mb-0">BookSphere</h4>
                </a>

                <div class="d-flex gap-3 align-items-center">
                    <a href="shop.php" class="text-decoration-none">Shop</a>
                    <a href="profile.php" class="text-decoration-none">Account</a>
                    <a href="wishlist.php" class="text-decoration-none"><i class="lni lni-heart"></i> Wishlist</a>
                    <a href="cart.php" class="text-decoration-none"><i class="lni lni-cart"></i> Cart</a>
                    <a href="orders.php" class="text-decoration-none">Orders</a>
                    <a href="logout.php" class="text-decoration-none text-danger">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-4">

        <!-- Error Messages -->
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="mb-3">
            <h3 class="mb-0">Checkout</h3>
            <p class="text-muted mb-0">Fill your shipping info and confirm your order.</p>
        </div>

        <form method="POST" action="checkout.php">
            <div class="row g-4">

                <!-- Shipping Form -->
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-body">

                            <h5 class="mb-3">Shipping Information</h5>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" name="full_name"
                                        value="<?= htmlspecialchars($user['name']) ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Phone *</label>
                                    <input type="text" class="form-control" name="phone"
                                        value="<?= htmlspecialchars($user['phone']) ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-control" name="email"
                                        value="<?= htmlspecialchars($user['email']) ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">City *</label>
                                    <input type="text" class="form-control" name="city"
                                        placeholder="City" required>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Address *</label>
                                    <input type="text" class="form-control" name="address"
                                        placeholder="Street, building, etc." required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Payment Method *</label>
                                    <select class="form-select" name="payment_method" required>
                                        <option value="Cash on Delivery">Cash on Delivery</option>
                                        <option value="Credit Card">Credit Card (Coming Soon)</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Notes (optional)</label>
                                    <input type="text" class="form-control" name="notes"
                                        placeholder="Any notes?">
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="col-lg-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="mb-3">Order Summary</h5>

                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Items</span>
                                <strong><?= count($cartItems) ?></strong>
                            </div>

                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Subtotal</span>
                                <strong>$<?= number_format($subtotal, 2) ?></strong>
                            </div>

                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Shipping</span>
                                <strong>$<?= number_format($shipping, 2) ?></strong>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between mb-3">
                                <span>Total</span>
                                <strong>$<?= number_format($total, 2) ?></strong>
                            </div>

                            <button type="submit" name="placeOrder" class="btn btn-primary w-100">
                                Place Order
                            </button>
                        </div>
                    </div>

                    <!-- Order Items Preview -->
                    <div class="card shadow-sm mt-3">
                        <div class="card-body">
                            <h6 class="mb-3">Items in Order</h6>
                            <?php foreach ($cartItems as $item): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <small><?= htmlspecialchars($item['book_name']) ?> (x<?= $item['quantity'] ?>)</small>
                                    <small>$<?= number_format($item['price'] * $item['quantity'], 2) ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <script src="assets/js/bootstrap.min.js"></script>
</body>

</html>