<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Database connection
$conn = mysqli_connect('sql106.ezyro.com', 'ezyro_41226653', 'examapp1234567890', 'ezyro_41226653_bgnupdvt_data_base');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get user email from session
$email = isset($_SESSION['email']) ? $_SESSION['email'] : '';

// Redirect if not logged in
if (empty($email)) {
    header("Location: signin.php");
    exit();
}

// Initialize message variables
$message = '';
$message_type = '';

// Handle adding to wishlist
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_wishlist'])) {
    $product_id = mysqli_real_escape_string($conn, $_POST['product_id']);
    
    // Check if product already in wishlist
    $check_sql = "SELECT * FROM wishlist WHERE user_email='$email' AND product_id='$product_id'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (!$check_result) {
        $message = "Database error: " . mysqli_error($conn);
        $message_type = "danger";
    } elseif (mysqli_num_rows($check_result) > 0) {
        $message = "Product is already in your wishlist!";
        $message_type = "info";
    } else {
        // Add to wishlist
        $insert_sql = "INSERT INTO wishlist (user_email, product_id) VALUES ('$email', '$product_id')";
        
        if (mysqli_query($conn, $insert_sql)) {
            $message = "Product added to wishlist successfully!";
            $message_type = "success";
        } else {
            $message = "Error adding to wishlist: " . mysqli_error($conn);
            $message_type = "danger";
        }
    }
}

// Handle removing from wishlist
if (isset($_GET['remove'])) {
    $product_id = mysqli_real_escape_string($conn, $_GET['remove']);
    $delete_sql = "DELETE FROM wishlist WHERE user_email='$email' AND product_id='$product_id'";
    
    if (mysqli_query($conn, $delete_sql)) {
        $message = "Product removed from wishlist!";
        $message_type = "success";
    } else {
        $message = "Error removing from wishlist: " . mysqli_error($conn);
        $message_type = "danger";
    }
}

// Get wishlist items with product details
$wishlist_sql = "SELECT p.* FROM wishlist w 
                JOIN product p ON w.product_id = p.id 
                WHERE w.user_email='$email'";
$wishlist_result = mysqli_query($conn, $wishlist_sql);

if (!$wishlist_result) {
    die("Database error: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist | M-Shopping</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #ff6b6b;
            --secondary-color: #4ecdc4;
            --accent-color: #ffbe76;
            --dark-color: #2d3436;
            --light-color: #f5f6fa;
            --success-color: #00b894;
            --danger-color: #d63031;
            --warning-color: #fdcb6e;
            --info-color: #0984e3;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: var(--dark-color);
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
        }

        .logo i {
            margin-right: 0.5rem;
            color: var(--light-color);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .logo span {
            color: var(--light-color);
            font-weight: 300;
        }

        .search-container {
            display: flex;
            flex-grow: 1;
            max-width: 600px;
            margin: 0 2rem;
        }

        .search-container input[type="search"] {
            flex-grow: 1;
            padding: 0.8rem 1rem;
            border: none;
            border-radius: 30px 0 0 30px;
            font-size: 1rem;
            outline: none;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .search-container input[type="search"]:focus {
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.2), 0 0 0 2px var(--accent-color);
        }

        .search-container button {
            padding: 0 1.5rem;
            background: var(--dark-color);
            color: white;
            border: none;
            border-radius: 0 30px 30px 0;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-container button:hover {
            background: #1e272e;
            transform: scale(1.05);
        }

        .search-container button i {
            transition: transform 0.3s ease;
        }

        .search-container button:hover i {
            transform: rotate(10deg);
        }

        .user-actions {
            display: flex;
            align-items: center;
        }

        .user-actions a {
            color: white;
            text-decoration: none;
            margin-left: 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .user-actions a:hover {
            transform: translateY(-3px);
        }

        .user-actions a i {
            font-size: 1.3rem;
            margin-bottom: 0.3rem;
            transition: all 0.3s ease;
        }

        .user-actions a:hover i {
            transform: scale(1.2);
        }

        .user-actions a.wishlist-link i {
            color: #ff6b6b;
            animation: heartBeat 1.5s infinite;
        }

        @keyframes heartBeat {
            0% { transform: scale(1); }
            14% { transform: scale(1.3); }
            28% { transform: scale(1); }
            42% { transform: scale(1.3); }
            70% { transform: scale(1); }
        }

        .user-actions a.cart-link i {
            animation: shake 2s infinite;
        }

        @keyframes shake {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(5deg); }
            75% { transform: rotate(-5deg); }
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 200px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            z-index: 1;
            border-radius: 8px;
            overflow: hidden;
            transform-origin: top right;
            animation: growDown 0.3s ease forwards;
        }

        @keyframes growDown {
            0% { transform: scaleY(0); opacity: 0; }
            80% { transform: scaleY(1.1); opacity: 1; }
            100% { transform: scaleY(1); opacity: 1; }
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        .dropdown-content a {
            color: var(--dark-color);
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            transition: all 0.3s ease;
        }

        .dropdown-content a:hover {
            background-color: var(--light-color);
            padding-left: 20px;
        }

        .dropdown-content i {
            margin-right: 10px;
            color: var(--primary-color);
            transition: all 0.3s ease;
        }

        .dropdown-content a:hover i {
            transform: translateX(5px);
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .section-title {
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
        }

        .section-title h2 {
            font-size: 2.5rem;
            color: var(--dark-color);
            display: inline-block;
            padding-bottom: 0.5rem;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: fadeIn 1s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .section-title h2::after {
            content: '';
            position: absolute;
            width: 80px;
            height: 4px;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            animation: lineGrow 1s ease-out;
        }

        @keyframes lineGrow {
            from { width: 0; }
            to { width: 80px; }
        }

        .wishlist-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .wishlist-container:hover {
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            transform: translateY(-5px);
        }

        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
        }

        .wishlist-item {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            border: none;
        }

        .wishlist-item:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .wishlist-image {
            height: 220px;
            overflow: hidden;
            position: relative;
        }

        .wishlist-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.7s ease;
        }

        .wishlist-item:hover .wishlist-image img {
            transform: scale(1.15);
        }

        .wishlist-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--primary-color);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            z-index: 2;
            animation: badgePulse 2s infinite;
            box-shadow: 0 4px 10px rgba(255, 107, 107, 0.3);
        }

        @keyframes badgePulse {
            0% { transform: scale(1); box-shadow: 0 4px 10px rgba(255, 107, 107, 0.3); }
            50% { transform: scale(1.1); box-shadow: 0 6px 15px rgba(255, 107, 107, 0.4); }
            100% { transform: scale(1); box-shadow: 0 4px 10px rgba(255, 107, 107, 0.3); }
        }

        .wishlist-details {
            padding: 1.8rem;
        }

        .wishlist-title {
            font-size: 1.2rem;
            margin-bottom: 0.6rem;
            color: var(--dark-color);
            transition: color 0.3s ease;
            font-weight: 600;
        }

        .wishlist-item:hover .wishlist-title {
            color: var(--primary-color);
        }

        .wishlist-category {
            font-size: 0.95rem;
            color: #7f8c8d;
            margin-bottom: 0.6rem;
            display: block;
        }

        .wishlist-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1.2rem;
            display: flex;
            align-items: center;
        }

        .wishlist-price::before {
            content: '$';
            font-size: 0.9em;
            margin-right: 2px;
        }

        .wishlist-stock {
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
            color: var(--success-color);
            display: flex;
            align-items: center;
        }

        .wishlist-stock i {
            margin-right: 8px;
            font-size: 1.1rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-5px); }
            60% { transform: translateY(-3px); }
        }

        .wishlist-stock.out-of-stock {
            color: var(--danger-color);
        }

        .wishlist-actions {
            display: flex;
            gap: 0.8rem;
        }

        .wishlist-btn {
            flex: 1;
            padding: 0.7rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.4s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .wishlist-btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.3) 50%, rgba(255,255,255,0.1) 100%);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }

        .wishlist-btn:hover::after {
            transform: translateX(100%);
        }

        .wishlist-btn i {
            transition: transform 0.3s ease;
            margin-right: 8px;
        }

        .wishlist-btn:hover i {
            transform: scale(1.2);
        }

        .add-to-cart {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }

        .add-to-cart:hover {
            background: #ff5252;
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
            transform: translateY(-3px);
        }

        .remove-from-wishlist {
            background: var(--danger-color);
            color: white;
            box-shadow: 0 4px 15px rgba(214, 48, 49, 0.3);
        }

        .remove-from-wishlist:hover {
            background: #c0392b;
            box-shadow: 0 6px 20px rgba(214, 48, 49, 0.4);
            transform: translateY(-3px);
        }

        .empty-wishlist {
            text-align: center;
            padding: 4rem 2rem;
            grid-column: 1 / -1;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .empty-wishlist i {
            font-size: 6rem;
            color: #ff6b6b;
            margin-bottom: 1.5rem;
            animation: pulse 2s infinite, float 3s ease-in-out infinite;
            display: inline-block;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }

        .empty-wishlist h3 {
            font-size: 1.8rem;
            margin-bottom: 1rem;
            color: var(--dark-color);
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .empty-wishlist p {
            color: #7f8c8d;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }

        .btn {
            padding: 0.9rem 2.2rem;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            color: white;
            border: none;
            border-radius: 30px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s ease;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.4);
        }

        .btn i {
            margin-left: 8px;
            transition: transform 0.3s ease;
        }

        .btn:hover i {
            transform: translateX(5px);
        }

        .floating-hearts {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 100;
        }

        .heart {
            position: absolute;
            font-size: 1.8rem;
            color: var(--primary-color);
            animation: float-up 4s ease-in-out forwards;
            opacity: 0;
            filter: drop-shadow(0 2px 5px rgba(255, 107, 107, 0.3));
        }

        @keyframes float-up {
            0% {
                transform: translateY(0) rotate(0deg) scale(0.8);
                opacity: 0.8;
            }
            100% {
                transform: translateY(-100vh) rotate(360deg) scale(1.2);
                opacity: 0;
            }
        }

        .alert {
            padding: 1.2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            animation: slideDown 0.5s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-left: 5px solid;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left-color: #28a745;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left-color: #dc3545;
        }

        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border-left-color: #17a2b8;
        }

        .alert i {
            margin-right: 12px;
            font-size: 1.5rem;
            animation: bounce 2s infinite;
        }

        .footer {
            background: var(--dark-color);
            color: white;
            padding: 4rem 2rem 2rem;
            margin-top: 4rem;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2.5rem;
        }

        .footer-column h3 {
            font-size: 1.4rem;
            margin-bottom: 1.8rem;
            position: relative;
            padding-bottom: 0.8rem;
        }

        .footer-column h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            animation: lineGrow 1s ease-out;
        }

        .footer-column ul {
            list-style: none;
        }

        .footer-column ul li {
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
        }

        .footer-column ul li:hover {
            transform: translateX(8px);
        }

        .footer-column ul li a {
            color: #b2bec3;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }

        .footer-column ul li a i {
            margin-right: 12px;
            color: var(--primary-color);
            transition: transform 0.3s ease;
        }

        .footer-column ul li a:hover {
            color: white;
        }

        .footer-column ul li a:hover i {
            transform: rotate(360deg);
        }

        .social-links {
            display: flex;
            gap: 1.2rem;
            margin-top: 1.5rem;
        }

        .social-links a {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .social-links a:hover {
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            transform: translateY(-5px) scale(1.1);
        }

        .social-links a i {
            transition: transform 0.3s ease;
        }

        .social-links a:hover i {
            transform: scale(1.2);
        }

        .copyright {
            text-align: center;
            padding-top: 3rem;
            margin-top: 3rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #b2bec3;
            font-size: 0.95rem;
        }

        .scroll-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            color: white;
            width: 55px;
            height: 55px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.4s ease;
            opacity: 0;
            visibility: hidden;
            box-shadow: 0 5px 20px rgba(255, 107, 107, 0.3);
            z-index: 999;
        }

        .scroll-to-top.visible {
            opacity: 1;
            visibility: visible;
        }

        .scroll-to-top:hover {
            transform: translateY(-5px) scale(1.1);
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.4);
        }

        .scroll-to-top i {
            transition: transform 0.3s ease;
        }

        .scroll-to-top:hover i {
            transform: translateY(-3px);
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                padding: 1.2rem;
            }

            .logo {
                margin-bottom: 1.2rem;
            }

            .search-container {
                width: 100%;
                margin: 1.2rem 0;
            }

            .user-actions {
                width: 100%;
                justify-content: space-around;
                margin-top: 1.2rem;
            }

            .section-title h2 {
                font-size: 2rem;
            }

            .wishlist-grid {
                grid-template-columns: 1fr;
            }

            .wishlist-item {
                max-width: 350px;
                margin: 0 auto;
            }

            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .footer-column h3::after {
                left: 50%;
                transform: translateX(-50%);
            }

            .footer-column ul li {
                justify-content: center;
            }

            .social-links {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">
            <i class="fas fa-shopping-bag"></i>
            <span>M-Shopping</span>
        </div>

        <div class="search-container">
            <form method="GET" action="index.php">
                <input type="search" name="search" placeholder="Search products...">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>

        <div class="user-actions">
            <a href="index1.php">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="add to cart.php" class="cart-link">
                <i class="fas fa-shopping-cart"></i>
                <span>Cart</span>
            </a>
            <a href="wishlist.php" class="wishlist-link active">
                <i class="fas fa-heart"></i>
                <span>Wishlist</span>
            </a>
            <div class="dropdown">
                <a href="#">
                    <i class="fas fa-user-circle"></i>
                    <span>Account</span>
                </a>
                <div class="dropdown-content">
                    <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
                    <a href="orders.php"><i class="fas fa-box"></i> My Orders</a>
                    <a href="my_products.php"><i class="fas fa-store"></i> My Products</a>
                    <a href="add_product.php"><i class="fas fa-plus-circle"></i> Add Product</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="section-title">
            <h2>My Wishlist</h2>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php 
                    echo $message_type == 'success' ? 'check-circle' : 
                         ($message_type == 'danger' ? 'times-circle' : 'info-circle');
                ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="wishlist-container">
            <?php if (mysqli_num_rows($wishlist_result) > 0): ?>
                <div class="wishlist-grid">
                    <?php while ($item = mysqli_fetch_assoc($wishlist_result)): ?>
                        <div class="wishlist-item">
                            <div class="wishlist-image">
                                <img src="UPLOADS/<?php echo htmlspecialchars($item['pfile']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['pname']); ?>">
                                <span class="wishlist-badge">
                                    <i class="fas fa-heart"></i> Saved
                                </span>
                            </div>
                            
                            <div class="wishlist-details">
                                <h3 class="wishlist-title"><?php echo htmlspecialchars($item['pname']); ?></h3>
                                <span class="wishlist-category"><?php echo htmlspecialchars($item['ptype']); ?></span>
                                <div class="wishlist-price"><?php echo number_format($item['pprice'], 2); ?></div>
                                
                                <div class="wishlist-stock <?php echo $item['pqty'] <= 0 ? 'out-of-stock' : ''; ?>">
                                    <i class="fas fa-<?php echo $item['pqty'] <= 0 ? 'times-circle' : 'check-circle'; ?>"></i>
                                    <?php echo $item['pqty'] <= 0 ? 'Out of Stock' : $item['pqty'] . ' available'; ?>
                                </div>
                                
                                <div class="wishlist-actions">
                                    <?php if ($item['pqty'] > 0): ?>
                                        <form action="add to cart.php" method="POST" style="flex:1;">
                                            <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" name="add_to_cart" class="wishlist-btn add-to-cart">
                                                <i class="fas fa-cart-plus"></i> Add to Cart
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <a href="wishlist.php?remove=<?php echo $item['id']; ?>" 
                                       class="wishlist-btn remove-from-wishlist"
                                       onclick="return confirm('Are you sure you want to remove this item from your wishlist?')">
                                        <i class="fas fa-trash"></i> Remove
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-wishlist">
                    <i class="fas fa-heart-broken"></i>
                    <h3>Your Wishlist is Empty</h3>
                    <p>You haven't saved any items to your wishlist yet. Start shopping to add your favorite products!</p>
                    <a href="index1.php" class="btn">
                        <i class="fas fa-arrow-right"></i> Browse Products
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="floating-hearts" id="floatingHearts"></div>

    <a href="#" class="scroll-to-top">
        <i class="fas fa-arrow-up"></i>
    </a>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-column">
                <h3>About M-Shopping</h3>
                <p>Your favorite online shopping destination with the best products at amazing prices.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-pinterest"></i></a>
                </div>
            </div>
            
            <div class="footer-column">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="products.php"><i class="fas fa-shopping-bag"></i> Products</a></li>
                    <li><a href="about.php"><i class="fas fa-info-circle"></i> About Us</a></li>
                    <li><a href="contact.php"><i class="fas fa-envelope"></i> Contact</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Customer Service</h3>
                <ul>
                    <li><a href="faq.php"><i class="fas fa-question-circle"></i> FAQ</a></li>
                    <li><a href="shipping.php"><i class="fas fa-truck"></i> Shipping Policy</a></li>
                    <li><a href="returns.php"><i class="fas fa-exchange-alt"></i> Returns & Refunds</a></li>
                    <li><a href="privacy.php"><i class="fas fa-lock"></i> Privacy Policy</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Contact Us</h3>
                <ul>
                    <li><i class="fas fa-map-marker-alt"></i> 123 Shopping Street, E-Commerce City</li>
                    <li><i class="fas fa-phone"></i> +1 (234) 567-8901</li>
                    <li><i class="fas fa-envelope"></i> support@mshopping.com</li>
                </ul>
            </div>
        </div>
        
        <div class="copyright">
            &copy; <?php echo date('Y'); ?> M-Shopping. All rights reserved.
        </div>
    </footer>

    <script>
        // Floating hearts animation
        function createHeart() {
            const heart = document.createElement('div');
            heart.className = 'heart';
            heart.innerHTML = '❤️';
            heart.style.left = Math.random() * 100 + 'vw';
            heart.style.animationDuration = Math.random() * 3 + 2 + 's';
            document.getElementById('floatingHearts').appendChild(heart);
            
            setTimeout(() => {
                heart.remove();
            }, 5000);
        }

        <?php if (mysqli_num_rows($wishlist_result) > 0): ?>
            setInterval(createHeart, 300);
        <?php endif; ?>

        // Scroll to top button
        const scrollToTopBtn = document.querySelector('.scroll-to-top');
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                scrollToTopBtn.classList.add('visible');
            } else {
                scrollToTopBtn.classList.remove('visible');
            }
        });

        scrollToTopBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Animation for wishlist items
        const wishlistItems = document.querySelectorAll('.wishlist-item');
        wishlistItems.forEach((item, index) => {
            item.style.opacity = '0';
            item.style.transform = 'translateY(20px)';
            item.style.transition = `all 0.5s ease ${index * 0.1}s`;
            
            setTimeout(() => {
                item.style.opacity = '1';
                item.style.transform = 'translateY(0)';
            }, 100);
        });

        // Add loading animation to buttons
        document.querySelectorAll('button[type="submit"], a.wishlist-btn').forEach(button => {
            button.addEventListener('click', function() {
                if (!this.querySelector('.loading')) {
                    const loading = document.createElement('span');
                    loading.className = 'loading';
                    this.insertBefore(loading, this.firstChild);
                }
            });
        });
    </script>
</body>
</html>
<?php
mysqli_close($conn);
?>