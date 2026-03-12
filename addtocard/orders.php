<?php
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connect to database
$conn = mysqli_connect('sql106.ezyro.com', 'ezyro_41226653', 'examapp1234567890', 'ezyro_41226653_bgnupdvt_data_base');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$email = isset($_SESSION['email']) ? $_SESSION['email'] : '';

if (empty($email)) {
    header("Location: signin.php");
    exit();
}

// Fetch orders
$orders_query = "SELECT * FROM orders WHERE email='$email' ORDER BY order_date DESC";
$orders_result = mysqli_query($conn, $orders_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | M-Shopping</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --accent: #4cc9f0;
            --dark: #212529;
            --light: #f8f9fa;
            --success: #4caf50;
            --danger: #f72585;
            --warning: #ff9f1c;
            --info: #4895ef;
            --text: #2b2d42;
            --bg-gradient: linear-gradient(135deg, #4361ee, #3f37c9);
            --card-shadow: 0 10px 20px rgba(0,0,0,0.1);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        body {
            background-color: #f5f7ff;
            font-family: 'Poppins', sans-serif;
            color: var(--text);
        }

        .header {
            background: var(--bg-gradient);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* Copy all header styles from index1.php */

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .orders-container {
            background: white;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .order-card {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            transition: var(--transition);
        }

        .order-card:hover {
            background: #f9f9ff;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .order-id {
            font-weight: 600;
            color: var(--primary);
        }

        .order-date {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .order-status {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-processing {
            background: #fff3cd;
            color: #856404;
        }

        .status-shipped {
            background: #cce5ff;
            color: #004085;
        }

        .status-delivered {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .order-items {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .order-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.5rem;
            border-radius: 10px;
            transition: var(--transition);
        }

        .order-item:hover {
            background: #f1f3ff;
        }

        .item-image {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            margin-bottom: 0.3rem;
        }

        .item-price {
            color: var(--primary);
            font-weight: 600;
        }

        .item-quantity {
            color: #6c757d;
            font-size: 0.85rem;
        }

        .order-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px dashed #dee2e6;
        }

        .order-total {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .order-total span {
            color: var(--primary);
        }

        .order-actions {
            display: flex;
            gap: 0.8rem;
        }

        .btn {
            padding: 0.7rem 1.3rem;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid #dee2e6;
            color: var(--text);
        }

        .btn-outline:hover {
            background: #f1f3ff;
        }

        .empty-orders {
            text-align: center;
            padding: 3rem;
        }

        .empty-orders i {
            font-size: 3.5rem;
            color: #adb5bd;
            margin-bottom: 1.5rem;
        }

        .empty-orders h3 {
            margin-bottom: 0.5rem;
            color: var(--text);
        }

        .empty-orders p {
            color: #6c757d;
            margin-bottom: 1.5rem;
        }

        @media (max-width: 768px) {
            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            .order-status {
                align-self: flex-start;
            }
            .order-summary {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            .order-actions {
                width: 100%;
            }
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Copy header from index1.php -->

    <div class="container">
        <h1 style="margin-bottom: 1.5rem; color: var(--primary);">My Orders</h1>
        
        <div class="orders-container">
            <?php if (mysqli_num_rows($orders_result) > 0): ?>
                <?php while ($order = mysqli_fetch_assoc($orders_result)): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <span class="order-id">Order #<?php echo $order['order_id']; ?></span>
                                <span class="order-date">Placed on <?php echo date('F j, Y', strtotime($order['order_date'])); ?></span>
                            </div>
                            <span class="order-status status-<?php echo strtolower($order['status']); ?>">
                                <?php echo $order['status']; ?>
                            </span>
                        </div>

                        <div class="order-items">
                            <?php
                            $order_id = $order['order_id'];
                            $items_query = "SELECT oi.*, p.pname, p.pfile FROM order_items oi 
                                           JOIN product p ON oi.product_id = p.id 
                                           WHERE oi.order_id = '$order_id'";
                            $items_result = mysqli_query($conn, $items_query);
                            
                            while ($item = mysqli_fetch_assoc($items_result)): ?>
                                <div class="order-item">
                                    <div class="item-image">
                                        <img src="UPLOADS/<?php echo $item['pfile']; ?>" alt="<?php echo $item['pname']; ?>">
                                    </div>
                                    <div class="item-details">
                                        <div class="item-name"><?php echo $item['pname']; ?></div>
                                        <div class="item-price">$<?php echo number_format($item['price'], 2); ?></div>
                                        <div class="item-quantity">Qty: <?php echo $item['quantity']; ?></div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>

                        <div class="order-summary">
                            <div class="order-total">
                                Total: <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                            </div>
                            <div class="order-actions">
                                <button class="btn btn-primary">
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                                <?php if ($order['status'] == 'Processing'): ?>
                                    <button class="btn btn-outline">
                                        <i class="fas fa-times"></i> Cancel Order
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-orders">
                    <i class="fas fa-box-open"></i>
                    <h3>No Orders Found</h3>
                    <p>You haven't placed any orders yet</p>
                    <a href="index1.php" class="btn btn-primary">
                        <i class="fas fa-shopping-bag"></i> Start Shopping
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Copy footer from index1.php -->
</body>
</html>
<?php mysqli_close($conn); ?>