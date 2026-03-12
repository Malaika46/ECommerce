<?php
session_start();

if (empty($_SESSION['email'])) {
    header("Location: signin.php");
    exit();
}

$message = isset($_GET['message']) ? $_GET['message'] : '';

// ✅ FIXED: Correct database connection (same as add to cart.php)
$conn = mysqli_connect('sql106.ezyro.com', 'ezyro_41226653', 'examapp1234567890', 'ezyro_41226653_bgnupdvt_data_base');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$email = $_SESSION['email'];
$user_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM data1 WHERE email = '$email'"));
$user_id = $user_row['id'];

// ✅ FIXED: Cart query with correct join
$cart_query = "SELECT product.id, product.pname, product.pprice, product.pfile, mycard.pid, mycard.quantity, mycard.price 
               FROM product 
               INNER JOIN mycard ON product.id = mycard.pid 
               WHERE mycard.user_id = '$user_id'";
$cart_result = mysqli_query($conn, $cart_query);
$grand_total = 0;

if ($cart_result) {
    while ($row = mysqli_fetch_assoc($cart_result)) {
        $grand_total += $row['price'];
    }
    mysqli_data_seek($cart_result, 0);
}

if (isset($_POST['pay'])) {
    $acc_id    = mysqli_real_escape_string($conn, $_POST['acc_id']);
    $acc_key   = mysqli_real_escape_string($conn, $_POST['acc_key']);
    $acc_pass  = mysqli_real_escape_string($conn, $_POST['acc_pass']);
    $seller_acc_id = mysqli_real_escape_string($conn, $_POST['seller_acc_id']);

    $acc_query = "SELECT * FROM paymentdetails WHERE accid = '$acc_id' AND acckey = '$acc_key' AND accpass = '$acc_pass'";
    $acc_result = mysqli_query($conn, $acc_query);

    if (mysqli_num_rows($acc_result) == 1) {
        $acc_row = mysqli_fetch_assoc($acc_result);

        $seller_query = "SELECT * FROM seller WHERE seller_account_id = '$seller_acc_id'";
        $seller_result = mysqli_query($conn, $seller_query);

        if (mysqli_num_rows($seller_result) == 1) {
            if ($acc_row['balance'] >= $grand_total) {
                mysqli_begin_transaction($conn);
                try {
                    $new_balance = $acc_row['balance'] - $grand_total;
                    mysqli_query($conn, "UPDATE paymentdetails SET balance = '$new_balance' WHERE accid = '$acc_id'");
                    mysqli_query($conn, "UPDATE seller SET seller_balance = seller_balance + '$grand_total' WHERE seller_account_id = '$seller_acc_id'");
                    mysqli_query($conn, "INSERT INTO transactions (buyer_id, seller_id, amount, transaction_date) VALUES ('$user_id', '$seller_acc_id', '$grand_total', NOW())");
                    mysqli_query($conn, "DELETE FROM mycard WHERE user_id = '$user_id'");
                    mysqli_commit($conn);
                    header("Location: payment.php?message=Payment successfully completed!");
                    exit();
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $message = "Transaction failed: " . $e->getMessage();
                }
            } else {
                $message = "Insufficient balance in your account.";
            }
        } else {
            $message = "Invalid seller account ID.";
        }
    } else {
        $message = "Invalid account details. Please check your credentials.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment | M-Shopping</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ✅ SAME color scheme as add to cart.php */
        :root {
            --primary-color: #ff6b6b;
            --accent-color: #ffbe76;
            --dark-color: #2d3436;
            --light-color: #f5f6fa;
            --success-color: #00b894;
            --danger-color: #d63031;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: var(--dark-color);
            min-height: 100vh;
        }

        /* ── Header (identical to cart page) ── */
        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .logo { font-size: 1.8rem; font-weight: 700; display: flex; align-items: center; }
        .logo i { margin-right: 0.5rem; }
        .logo span { font-weight: 300; }
        .user-actions { display: flex; align-items: center; }
        .user-actions a {
            color: white; text-decoration: none; margin-left: 1.5rem;
            display: flex; flex-direction: column; align-items: center;
            font-size: 0.9rem; transition: transform 0.3s ease;
        }
        .user-actions a:hover { transform: translateY(-3px); }
        .user-actions i { font-size: 1.3rem; margin-bottom: 0.3rem; }
        .logout-btn {
            position: fixed; top: 20px; left: 20px; background: var(--danger-color);
            color: white; padding: 0.8rem 1.2rem; border: none; border-radius: 30px;
            font-weight: 500; cursor: pointer; transition: all 0.3s ease;
            text-decoration: none; display: inline-flex; align-items: center;
            z-index: 1001; box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .logout-btn:hover { background: #c0392b; transform: translateY(-3px); }
        .logout-btn i { margin-right: 8px; }

        /* ── Layout ── */
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        .page-title { text-align: center; margin-bottom: 2rem; position: relative; }
        .page-title h2 { font-size: 2rem; color: var(--dark-color); display: inline-block; padding-bottom: 0.5rem; }
        .page-title h2::after {
            content: ''; position: absolute; width: 80px; height: 3px;
            background: var(--primary-color); bottom: 0; left: 50%; transform: translateX(-50%);
        }

        /* ── Alert messages ── */
        .alert {
            padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 1.5rem;
            display: flex; align-items: center; gap: 0.8rem;
        }
        .alert i { font-size: 1.3rem; }
        .alert-success { background: rgba(0,184,148,0.1); color: var(--success-color); border-left: 4px solid var(--success-color); }
        .alert-danger  { background: rgba(214,48,49,0.1);  color: var(--danger-color);  border-left: 4px solid var(--danger-color); }

        /* ── Card (same look as cart summary box) ── */
        .card {
            background: white; border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 2rem; margin-bottom: 2rem;
        }
        .card-header {
            font-size: 1.3rem; font-weight: 600; color: var(--dark-color);
            display: flex; align-items: center; gap: 0.8rem;
            padding-bottom: 1rem; margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--light-color);
        }
        .card-header i { color: var(--primary-color); }

        /* ── Order summary table (same as cart table) ── */
        .cart-table {
            width: 100%; border-collapse: collapse;
            background: white; border-radius: 10px; overflow: hidden;
        }
        .cart-table th { background: var(--dark-color); color: white; padding: 1rem; text-align: left; }
        .cart-table td { padding: 1rem; border-bottom: 1px solid #eee; vertical-align: middle; }
        .cart-table tr:last-child td { border-bottom: none; }
        .product-image { width: 70px; height: 70px; border-radius: 8px; overflow: hidden; flex-shrink: 0; }
        .product-image img { width: 100%; height: 100%; object-fit: cover; }
        .product-name { font-weight: 500; color: var(--dark-color); }
        .product-price { font-weight: 600; color: var(--primary-color); }

        /* ── Total box ── */
        .total-summary { display: flex; justify-content: flex-end; margin-top: 1.5rem; }
        .total-box {
            background: var(--light-color); padding: 1.5rem;
            border-radius: 10px; min-width: 280px;
        }
        .total-row {
            display: flex; justify-content: space-between;
            padding: 0.5rem 0; border-bottom: 1px dashed #ddd;
        }
        .total-row:last-child { border-bottom: none; font-size: 1.1rem; font-weight: 700; color: var(--primary-color); margin-top: 0.5rem; }

        /* ── Payment form ── */
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-control {
            width: 100%; padding: 0.9rem 1rem; border: 1px solid #ddd;
            border-radius: 8px; font-size: 1rem; font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }
        .form-control:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(255,107,107,0.2); }
        .form-control[readonly] { background: var(--light-color); cursor: not-allowed; }
        .password-wrap { position: relative; }
        .password-wrap i {
            position: absolute; right: 15px; top: 50%; transform: translateY(-50%);
            cursor: pointer; color: #999; transition: color 0.3s;
        }
        .password-wrap i:hover { color: var(--primary-color); }

        /* ── Pay button (same style as checkout button) ── */
        .pay-btn {
            width: 100%; padding: 1rem; background: var(--success-color); color: white;
            border: none; border-radius: 30px; font-size: 1rem; font-weight: 600;
            cursor: pointer; transition: all 0.3s ease; display: flex;
            align-items: center; justify-content: center; gap: 0.8rem;
            font-family: 'Poppins', sans-serif;
        }
        .pay-btn:hover { background: #00a884; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }

        .back-btn {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.8rem 1.5rem; background: var(--light-color); color: var(--dark-color);
            border: none; border-radius: 30px; font-weight: 500; cursor: pointer;
            text-decoration: none; transition: all 0.3s ease; margin-bottom: 1.5rem;
            font-family: 'Poppins', sans-serif; font-size: 0.95rem;
        }
        .back-btn:hover { background: #dfe6e9; }

        .secure-note {
            text-align: center; margin-top: 1.5rem; color: #7f8c8d; font-size: 0.85rem;
        }
        .secure-note i { color: var(--success-color); margin-right: 5px; }

        /* ── Empty cart state ── */
        .empty-cart { text-align: center; padding: 3rem; }
        .empty-cart i { font-size: 3rem; color: var(--accent-color); margin-bottom: 1rem; display: block; }
        .empty-cart p { color: #7f8c8d; margin-bottom: 1.5rem; }
        .shop-btn {
            padding: 0.8rem 1.5rem; background: var(--primary-color); color: white;
            border-radius: 30px; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;
        }

        @media (max-width: 768px) {
            .header { flex-direction: column; padding: 1rem; }
            .logo { margin-bottom: 1rem; }
            .user-actions { width: 100%; justify-content: space-around; margin-top: 1rem; }
            .logout-btn { position: static; margin: 1rem auto; display: block; width: fit-content; }
            .cart-table { display: block; overflow-x: auto; }
            .total-box { min-width: 100%; }
        }
    </style>
</head>
<body>

    <header class="header">
        <div class="logo">
            <i class="fas fa-shopping-bag"></i>
            <span>M-Shopping</span>
        </div>
        <div class="user-actions">
            <a href="index1.php"><i class="fas fa-home"></i><span>Home</span></a>
            <a href="add to cart.php"><i class="fas fa-shopping-cart"></i><span>Cart</span></a>
            <a href="wishlist.php"><i class="fas fa-heart"></i><span>Wishlist</span></a>
            <a href="profile.php"><i class="fas fa-user"></i><span>Profile</span></a>
        </div>
    </header>

    

    <div class="container">

        <div class="page-title"><h2>Checkout</h2></div>

        <?php if ($message): ?>
            <div class="alert <?= strpos($message, 'successfully') !== false ? 'alert-success' : 'alert-danger' ?>">
                <i class="fas <?= strpos($message, 'successfully') !== false ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <!-- ORDER SUMMARY -->
        <div class="card">
            <div class="card-header"><i class="fas fa-shopping-bag"></i> Order Summary</div>

            <?php if ($cart_result && mysqli_num_rows($cart_result) > 0): ?>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Unit Price</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php mysqli_data_seek($cart_result, 0); while ($row = mysqli_fetch_assoc($cart_result)): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:1rem;">
                                    <div class="product-image">
                                        <img src="UPLOADS/<?= htmlspecialchars($row['pfile']) ?>" alt="<?= htmlspecialchars($row['pname']) ?>"
                                             onerror="this.src='https://via.placeholder.com/70x70?text=No+Img'">
                                    </div>
                                    <span class="product-name"><?= htmlspecialchars($row['pname']) ?></span>
                                </div>
                            </td>
                            <td class="product-price"><?= number_format($row['pprice'], 2) ?> PKR</td>
                            <td><?= $row['quantity'] ?></td>
                            <td class="product-price"><?= number_format($row['price'], 2) ?> PKR</td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <div class="total-summary">
                    <div class="total-box">
                        <div class="total-row"><span>Subtotal:</span><span><?= number_format($grand_total, 2) ?> PKR</span></div>
                        <div class="total-row"><span>Shipping:</span><span>Free</span></div>
                        <div class="total-row"><span>Grand Total:</span><span><?= number_format($grand_total, 2) ?> PKR</span></div>
                    </div>
                </div>

            <?php else: ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Your cart is empty. Add some products first!</p>
                    <a href="index1.php" class="shop-btn"><i class="fas fa-arrow-left"></i> Go Shopping</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- PAYMENT FORM -->
        <?php if ($cart_result && mysqli_num_rows($cart_result) > 0): mysqli_data_seek($cart_result, 0); ?>
        <div class="card">
            <div class="card-header"><i class="fas fa-credit-card"></i> Payment Information</div>

            <a href="add to cart.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Cart</a>

            <form method="POST" action="">

                <div class="form-group">
                    <label>Total Amount</label>
                    <input type="text" class="form-control" value="<?= number_format($grand_total, 2) ?> PKR" readonly>
                </div>

                <div class="form-group">
                    <label for="acc_id"><i class="fas fa-id-card"></i> Your Account ID</label>
                    <input type="text" id="acc_id" name="acc_id" class="form-control" required placeholder="Enter your account ID">
                </div>

                <div class="form-group">
                    <label for="acc_key"><i class="fas fa-key"></i> Account Key</label>
                    <input type="text" id="acc_key" name="acc_key" class="form-control" required placeholder="Enter your account key">
                </div>

                <div class="form-group">
                    <label for="acc_pass"><i class="fas fa-lock"></i> Account Password</label>
                    <div class="password-wrap">
                        <input type="password" id="acc_pass" name="acc_pass" class="form-control" required placeholder="Enter your account password">
                        <i class="fas fa-eye" onclick="togglePass()"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="seller_acc_id"><i class="fas fa-store"></i> Seller Account ID</label>
                    <input type="text" id="seller_acc_id" name="seller_acc_id" class="form-control" required placeholder="Enter seller's account ID">
                </div>

                <button type="submit" name="pay" class="pay-btn">
                    <i class="fas fa-lock"></i> Complete Secure Payment — <?= number_format($grand_total, 2) ?> PKR
                </button>

                <div class="secure-note">
                    <i class="fas fa-shield-alt"></i> Your payment is protected with SSL encryption. We do not store your banking details.
                </div>

            </form>
        </div>
        <?php endif; ?>

    </div>

    <script>
        function togglePass() {
            const input = document.getElementById('acc_pass');
            const icon  = document.querySelector('.password-wrap i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>