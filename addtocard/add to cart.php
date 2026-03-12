<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['email'] == "") {
    header("Location: signin.php");
    exit();
}
$message = isset($_GET['message']) ? $_GET['message'] : '';

$conn = mysqli_connect('sql106.ezyro.com', 'ezyro_41226653', 'examapp1234567890', 'ezyro_41226653_bgnupdvt_data_base');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$email = $_SESSION['email'];
$qry = "SELECT id FROM data1 WHERE email = '$email'";
$result = mysqli_query($conn, $qry);
$row = mysqli_fetch_assoc($result);
$user_id = $row['id'];

// ── AJAX: live quantity update ──
if (isset($_POST['ajax_update'])) {
    $pid      = mysqli_real_escape_string($conn, $_POST['pid']);
    $quantity = max(1, (int) $_POST['quantity']);

    $pr = mysqli_fetch_assoc(mysqli_query($conn, "SELECT pprice FROM product WHERE id='$pid'"));
    $unit_price = $pr['pprice'];
    $new_price  = $unit_price * $quantity;

    mysqli_query($conn, "UPDATE mycard SET quantity='$quantity', price='$new_price' WHERE pid='$pid' AND user_id='$user_id'");

    $total_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(price) AS grand FROM mycard WHERE user_id='$user_id'"));
    echo json_encode([
        'subtotal'    => number_format($new_price, 2),
        'grand_total' => number_format($total_row['grand'], 2)
    ]);
    exit();
}

// ── Normal actions ──
if (isset($_REQUEST['action'])) {
    if ($_REQUEST['action'] == 'add' && isset($_POST['pid'])) {
        $pid = mysqli_real_escape_string($conn, $_POST['pid']);
        $pr  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT pprice FROM product WHERE id='$pid'"));
        $product_price = $pr['pprice'];
        $check = mysqli_query($conn, "SELECT * FROM mycard WHERE pid='$pid' AND user_id='$user_id'");
        if (mysqli_num_rows($check) > 0) {
            mysqli_query($conn, "UPDATE mycard SET quantity=quantity+1, price=price+'$product_price' WHERE pid='$pid' AND user_id='$user_id'");
        } else {
            mysqli_query($conn, "INSERT INTO mycard (pid, user_id, quantity, price) VALUES ('$pid','$user_id',1,'$product_price')");
        }
    } elseif ($_REQUEST['action'] == 'delete' && isset($_GET['id'])) {
        $id = mysqli_real_escape_string($conn, $_GET['id']);
        mysqli_query($conn, "DELETE FROM mycard WHERE pid='$id' AND user_id='$user_id'");
    }
}

$cart_query = "SELECT product.id, product.pname, product.pprice, product.pfile,
                      mycard.pid, mycard.quantity, mycard.price
               FROM product
               INNER JOIN mycard ON product.id = mycard.pid
               WHERE mycard.user_id = '$user_id'";
$cart_result = mysqli_query($conn, $cart_query);
$grand_total = 0;
$cart_empty  = mysqli_num_rows($cart_result) == 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart | M-Shopping</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #ff6b6b;
            --accent-color:  #ffbe76;
            --dark-color:    #2d3436;
            --light-color:   #f5f6fa;
            --success-color: #00b894;
            --danger-color:  #d63031;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: var(--dark-color); min-height: 100vh;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white; padding: 1rem 2rem;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            position: sticky; top: 0; z-index: 1000;
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

        /* Layout */
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        .cart-title { text-align: center; margin-bottom: 2rem; position: relative; }
        .cart-title h2 { font-size: 2rem; color: var(--dark-color); display: inline-block; padding-bottom: 0.5rem; }
        .cart-title h2::after {
            content: ''; position: absolute; width: 80px; height: 3px;
            background: var(--primary-color); bottom: 0; left: 50%; transform: translateX(-50%);
        }

        /* Table */
        .cart-table {
            width: 100%; border-collapse: collapse; margin-bottom: 2rem;
            background: white; border-radius: 10px; overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .cart-table th { background: var(--dark-color); color: white; padding: 1rem; text-align: left; }
        .cart-table td { padding: 1rem; border-bottom: 1px solid #eee; vertical-align: middle; }
        .cart-table tr:last-child td { border-bottom: none; }

        .product-image { width: 80px; height: 80px; border-radius: 8px; overflow: hidden; flex-shrink: 0; }
        .product-image img { width: 100%; height: 100%; object-fit: cover; }
        .product-name { font-weight: 500; color: var(--dark-color); }
        .unit-price { font-weight: 600; color: var(--primary-color); }
        .subtotal-cell { font-weight: 700; color: var(--dark-color); min-width: 110px; }

        /* Quantity +/- controls */
        .qty-wrap {
            display: inline-flex; align-items: center;
            border: 2px solid #ddd; border-radius: 8px; overflow: hidden;
        }
        .qty-btn {
            width: 36px; height: 38px; border: none;
            background: var(--light-color); cursor: pointer;
            font-size: 1.2rem; font-weight: 700; color: var(--dark-color);
            transition: all 0.2s ease;
            display: flex; align-items: center; justify-content: center;
        }
        .qty-btn.plus:hover  { background: var(--success-color); color: white; }
        .qty-btn.minus:hover { background: var(--danger-color);  color: white; }
        .qty-number {
            width: 50px; height: 38px; border: none;
            border-left: 1px solid #ddd; border-right: 1px solid #ddd;
            text-align: center; font-size: 1rem; font-weight: 600;
            font-family: 'Poppins', sans-serif; color: var(--dark-color);
            background: white; outline: none;
        }
        .qty-number::-webkit-inner-spin-button,
        .qty-number::-webkit-outer-spin-button { -webkit-appearance: none; }
        .qty-number[type=number] { -moz-appearance: textfield; }

        /* Saving dot */
        .saving-dot {
            display: none; width: 8px; height: 8px; border-radius: 50%;
            background: var(--success-color); margin-left: 8px;
            animation: pulse 0.8s infinite;
        }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.2} }

        /* Delete */
        .delete-btn {
            padding: 0.5rem 1rem; border: none; border-radius: 5px;
            cursor: pointer; background: var(--danger-color); color: white;
            display: inline-flex; align-items: center; gap: 5px;
            font-size: 0.9rem; transition: all 0.3s ease; text-decoration: none;
        }
        .delete-btn:hover { background: #c0392b; }

        /* Summary */
        .cart-summary {
            display: flex; justify-content: space-between; align-items: center;
            padding: 1.5rem; background: white;
            border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .total-amount { font-size: 1.4rem; font-weight: 700; color: var(--dark-color); }
        .total-amount span { color: var(--primary-color); transition: all 0.3s ease; }
        .cart-actions { display: flex; gap: 1rem; }
        .continue-btn, .checkout-btn {
            padding: 0.8rem 1.5rem; border: none; border-radius: 30px;
            font-weight: 500; cursor: pointer; transition: all 0.3s ease;
            text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem;
            font-family: 'Poppins', sans-serif; font-size: 0.95rem;
        }
        .continue-btn { background: var(--light-color); color: var(--dark-color); }
        .continue-btn:hover { background: #dfe6e9; }
        .checkout-btn { background: var(--primary-color); color: white; }
        .checkout-btn:hover { background: #ff5252; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }

        /* Empty / success */
        .empty-cart { text-align: center; padding: 3rem; background: white; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .empty-cart > i { font-size: 3rem; color: var(--accent-color); margin-bottom: 1rem; display: block; }
        .empty-cart h3 { font-size: 1.5rem; margin-bottom: 1rem; }
        .empty-cart p { color: #7f8c8d; margin-bottom: 1.5rem; }
        .shop-btn {
            padding: 0.8rem 1.5rem; background: var(--primary-color); color: white;
            border-radius: 30px; font-weight: 500; text-decoration: none;
            display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.3s;
        }
        .shop-btn:hover { background: #ff5252; transform: translateY(-3px); }
        .success-message {
            background: rgba(0,184,148,0.1); color: var(--success-color);
            padding: 1rem; border-radius: 5px; margin-bottom: 1.5rem;
            display: flex; align-items: center; border-left: 4px solid var(--success-color);
        }
        .success-message i { margin-right: 10px; }

        @media (max-width: 768px) {
            .cart-table { display: block; overflow-x: auto; }
            .cart-summary { flex-direction: column; gap: 1.5rem; align-items: flex-start; }
            .cart-actions { width: 100%; justify-content: space-between; }
            .header { flex-direction: column; padding: 1rem; }
            .logo { margin-bottom: 1rem; }
            .user-actions { width: 100%; justify-content: space-around; margin-top: 1rem; }
            .logout-btn { position: static; margin: 1rem auto; display: block; width: fit-content; }
        }
    </style>
</head>
<body>

<header class="header">
    <div class="logo"><i class="fas fa-shopping-bag"></i><span>M-Shopping</span></div>
    <div class="user-actions">
        <a href="index1.php"><i class="fas fa-home"></i><span>Home</span></a>
        <a href="add to cart.php"><i class="fas fa-shopping-cart"></i><span>Cart</span></a>
        <a href="wishlist.php"><i class="fas fa-heart"></i><span>Wishlist</span></a>
        <a href="profile.php"><i class="fas fa-user"></i><span>Profile</span></a>
    </div>
</header>



<div class="container">
    <div class="cart-title"><h2>Your Shopping Cart</h2></div>

    <?php if ($message): ?>
        <div class="success-message"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($cart_empty): ?>
        <div class="empty-cart">
            <i class="fas fa-shopping-cart"></i>
            <h3>Your cart is empty</h3>
            <p>Looks like you haven't added any items yet.</p>
            <a href="index1.php" class="shop-btn"><i class="fas fa-arrow-left"></i> Continue Shopping</a>
        </div>
    <?php else: ?>

        <table class="cart-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Unit Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                    <th>Remove</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($cart_result)):
                    $grand_total += $row['price'];
                ?>
                <tr data-pid="<?= $row['pid'] ?>" data-unit="<?= $row['pprice'] ?>">
                    <td>
                        <div style="display:flex;align-items:center;gap:1rem;">
                            <div class="product-image">
                                <img src="UPLOADS/<?= htmlspecialchars($row['pfile']) ?>"
                                     alt="<?= htmlspecialchars($row['pname']) ?>"
                                     onerror="this.src='https://via.placeholder.com/80x80?text=No+Img'">
                            </div>
                            <div class="product-name"><?= htmlspecialchars($row['pname']) ?></div>
                        </div>
                    </td>
                    <td class="unit-price"><?= number_format($row['pprice'], 2) ?> PKR</td>
                    <td>
                        <div style="display:flex;align-items:center;">
                            <div class="qty-wrap">
                                <button type="button" class="qty-btn minus" onclick="changeQty(this, -1)">−</button>
                                <input type="number" class="qty-number"
                                       value="<?= $row['quantity'] ?>" min="1"
                                       onchange="saveQty(this)">
                                <button type="button" class="qty-btn plus" onclick="changeQty(this, 1)">+</button>
                            </div>
                            <span class="saving-dot"></span>
                        </div>
                    </td>
                    <td class="subtotal-cell" id="sub_<?= $row['pid'] ?>">
                        <?= number_format($row['price'], 2) ?> PKR
                    </td>
                    <td>
                        <a href="add to cart.php?action=delete&id=<?= $row['pid'] ?>"
                           class="delete-btn"
                           onclick="return confirm('Remove this item from cart?')">
                            <i class="fas fa-trash-alt"></i> Remove
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="cart-summary">
            <div class="total-amount">
                Total: <span id="grand-total"><?= number_format($grand_total, 2) ?> PKR</span>
            </div>
            <div class="cart-actions">
                <a href="index1.php" class="continue-btn"><i class="fas fa-arrow-left"></i> Continue Shopping</a>
                <a href="payment.php" class="checkout-btn"><i class="fas fa-credit-card"></i> Proceed to Checkout</a>
            </div>
        </div>

    <?php endif; ?>
</div>

<script>
    // + / - button click
    function changeQty(btn, step) {
        const input = btn.closest('.qty-wrap').querySelector('.qty-number');
        let val = parseInt(input.value) + step;
        if (val < 1) val = 1;
        input.value = val;
        saveQty(input);
    }

    // Debounce timer so rapid clicks don't spam server
    const timers = {};

    function saveQty(input) {
        const row  = input.closest('tr');
        const pid  = row.dataset.pid;
        const unit = parseFloat(row.dataset.unit);
        const qty  = parseInt(input.value);
        const dot  = row.querySelector('.saving-dot');

        if (isNaN(qty) || qty < 1) { input.value = 1; return; }

        // Instant UI update
        const newSub = (unit * qty).toFixed(2);
        document.getElementById('sub_' + pid).textContent = fmtPKR(newSub) + ' PKR';
        updateGrandTotal();

        // Show saving dot
        dot.style.display = 'inline-block';

        // Debounce: wait 400ms after last change before sending
        clearTimeout(timers[pid]);
        timers[pid] = setTimeout(() => {
            const fd = new FormData();
            fd.append('ajax_update', '1');
            fd.append('pid', pid);
            fd.append('quantity', qty);

            fetch('add to cart.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    document.getElementById('sub_' + pid).textContent = data.subtotal + ' PKR';
                    document.getElementById('grand-total').textContent = data.grand_total + ' PKR';
                    dot.style.display = 'none';
                })
                .catch(() => { dot.style.display = 'none'; });
        }, 400);
    }

    // Recalculate grand total from all subtotal cells
    function updateGrandTotal() {
        let total = 0;
        document.querySelectorAll('.subtotal-cell').forEach(cell => {
            const v = parseFloat(cell.textContent.replace(/,/g, ''));
            if (!isNaN(v)) total += v;
        });
        document.getElementById('grand-total').textContent = fmtPKR(total.toFixed(2)) + ' PKR';
    }

    // Format number with commas
    function fmtPKR(num) {
        return parseFloat(num).toLocaleString('en-US', {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        });
    }
</script>

</body>
</html>
<?php mysqli_close($conn); ?>