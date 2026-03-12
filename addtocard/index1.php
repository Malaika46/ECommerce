
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Connect to MariaDB database
$conn = mysqli_connect('sql106.ezyro.com', 'ezyro_41226653', 'examapp1234567890', 'ezyro_41226653_bgnupdvt_data_base');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get the email from the session if it exists
$email = isset($_SESSION['email']) ? $_SESSION['email'] : '';

// Initialize error variable
$error = '';

// Handle form submission for adding products
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['P-file'])) {
    if (empty($email)) {
        header("Location: signin.php");
        exit();
    }

    $Pname = mysqli_real_escape_string($conn, $_POST['P-name']);
    $Ptype = mysqli_real_escape_string($conn, $_POST['P-type']);
    $Pprice = mysqli_real_escape_string($conn, $_POST['P-price']);
    $Pquantity = mysqli_real_escape_string($conn, $_POST['P-quantity']);
    $file_name = $_FILES['P-file']['name'];
    $From_path = $_FILES['P-file']['tmp_name'];
    $TO_path = "UPLOADS/";

    if (empty($file_name)) {
        $error = "Please select a file";
    } elseif (!in_array(pathinfo($file_name, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        $error = "Only image files are allowed (JPG, JPEG, PNG, GIF, WEBP)";
    } elseif (filesize($From_path) > 1024 * 1024 * 5) {
        $error = "File size should not exceed 5MB";
    } else {
        $unique_name = uniqid() . '_' . $file_name;
        move_uploaded_file($From_path, $TO_path . $unique_name);
    }

    if (empty($error)) {
        $add = "INSERT INTO product (pname, ptype, pprice, pqty, pfile, email) VALUES ('$Pname', '$Ptype', '$Pprice', '$Pquantity', '$unique_name', '$email')";
        $run = mysqli_query($conn, $add);

        if ($run) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            die("Query failed: " . mysqli_error($conn));
        }
    }
}

// Handle form submission for updating products
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $Pname = mysqli_real_escape_string($conn, $_POST['P-name']);
    $Ptype = mysqli_real_escape_string($conn, $_POST['P-type']);
    $Pprice = mysqli_real_escape_string($conn, $_POST['P-price']);
    $Pquantity = mysqli_real_escape_string($conn, $_POST['P-quantity']);
    $file_name = $_FILES['P-file']['name'];
    $From_path = $_FILES['P-file']['tmp_name'];
    $TO_path = "UPLOADS/";

    if (!empty($file_name)) {
        if (!in_array(pathinfo($file_name, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $error = "Only image files are allowed (JPG, JPEG, PNG, GIF, WEBP)";
        } elseif (filesize($From_path) > 1024 * 1024 * 5) {
            $error = "File size should not exceed 5MB";
        } else {
            // Get old file to delete it
            $old_file_query = "SELECT pfile FROM product WHERE id='$id' AND email='$email'";
            $old_file_result = mysqli_query($conn, $old_file_query);
            $old_file = mysqli_fetch_assoc($old_file_result)['pfile'];
            if (file_exists($TO_path . $old_file)) {
                unlink($TO_path . $old_file);
            }
            
            $unique_name = uniqid() . '_' . $file_name;
            move_uploaded_file($From_path, $TO_path . $unique_name);
            $update = "UPDATE product SET pname='$Pname', ptype='$Ptype', pprice='$Pprice', pqty='$Pquantity', pfile='$unique_name' WHERE id='$id' AND email='$email'";
        }
    } else {
        $update = "UPDATE product SET pname='$Pname', ptype='$Ptype', pprice='$Pprice', pqty='$Pquantity' WHERE id='$id' AND email='$email'";
    }

    if (empty($error)) {
        $run = mysqli_query($conn, $update);

        if ($run) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            die("Query failed: " . mysqli_error($conn));
        }
    }
}

// Handle form submission for deleting products
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    
    // First get the file to delete it from server
    $file_query = "SELECT pfile FROM product WHERE id='$id' AND email='$email'";
    $file_result = mysqli_query($conn, $file_query);
    $file_data = mysqli_fetch_assoc($file_result);
    
    if ($file_data && file_exists("UPLOADS/" . $file_data['pfile'])) {
        unlink("UPLOADS/" . $file_data['pfile']);
    }
    
    $delete = "DELETE FROM product WHERE id='$id' AND email='$email'";
    $run = mysqli_query($conn, $delete);

    if ($run) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        die("Query failed: " . mysqli_error($conn));
    }
}

// Handle wishlist actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['wishlist_action'])) {
    if (empty($email)) {
        header("Location: signin.php");
        exit();
    }

    $product_id = mysqli_real_escape_string($conn, $_POST['product_id']);
    $action = mysqli_real_escape_string($conn, $_POST['wishlist_action']);

    if ($action == 'add') {
        // Check if product is already in wishlist
        $check_query = "SELECT * FROM wishlist WHERE product_id='$product_id' AND user_email='$email'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) == 0) {
            $add_wishlist = "INSERT INTO wishlist (product_id, user_email) VALUES ('$product_id', '$email')";
            mysqli_query($conn, $add_wishlist);
        }
    } elseif ($action == 'remove') {
        $remove_wishlist = "DELETE FROM wishlist WHERE product_id='$product_id' AND user_email='$email'";
        mysqli_query($conn, $remove_wishlist);
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$sort = isset($_GET['sort']) ? mysqli_real_escape_string($conn, $_GET['sort']) : 'id DESC';
$search = isset($_POST['search']) ? mysqli_real_escape_string($conn, $_POST['search']) : '';
$filter = isset($_GET['filter']) ? mysqli_real_escape_string($conn, $_GET['filter']) : '';

// Get wishlist items for current user
$wishlist_items = [];
if (!empty($email)) {
    $wishlist_query = "SELECT product_id FROM wishlist WHERE user_email='$email'";
    $wishlist_result = mysqli_query($conn, $wishlist_query);
    while ($row = mysqli_fetch_assoc($wishlist_result)) {
        $wishlist_items[] = $row['product_id'];
    }
}

$sql = "SELECT * FROM product";
if ($filter == 'my_products' && !empty($email)) {
    $sql .= " WHERE email = '$email'";
    if ($search) {
        $sql .= " AND (pname LIKE '%$search%' OR ptype LIKE '%$search%')";
    }
} else {
    if ($search) {
        $sql .= " WHERE pname LIKE '%$search%' OR ptype LIKE '%$search%'";
    }
}
$sql .= " ORDER BY $sort";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M-Shopping | E-Commerce Platform</title>
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

        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1555529669-e69e7aa0ba9a?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            height: 400px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            padding: 0 2rem;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, 
                          rgba(255,107,107,0.3) 0%, 
                          rgba(78,205,196,0.3) 50%, 
                          rgba(255,190,118,0.3) 100%);
            z-index: 0;
            animation: gradientBG 15s ease infinite;
            background-size: 400% 400%;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .hero-section h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            animation: slideIn 1s ease;
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .hero-section p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            max-width: 700px;
            animation: slideIn 1s ease 0.2s forwards;
            opacity: 0;
            position: relative;
            z-index: 1;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }

        .btn {
            padding: 0.8rem 2rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 30px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            animation: slideIn 1s ease 0.4s forwards;
            opacity: 0;
            position: relative;
            z-index: 1;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, 
                          rgba(255,255,255,0.1) 0%, 
                          rgba(255,255,255,0.3) 50%, 
                          rgba(255,255,255,0.1) 100%);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }

        .btn:hover::after {
            transform: translateX(100%);
        }

        .btn:hover {
            background: #ff5252;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .btn i {
            margin-left: 5px;
            transition: transform 0.3s ease;
        }

        .btn:hover i {
            transform: translateX(5px);
        }

        @keyframes slideIn {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
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
            font-size: 2rem;
            color: var(--dark-color);
            display: inline-block;
            padding-bottom: 0.5rem;
        }

        .section-title h2::after {
            content: '';
            position: absolute;
            width: 80px;
            height: 3px;
            background: var(--primary-color);
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            animation: lineGrow 1s ease-out;
        }

        @keyframes lineGrow {
            from { width: 0; }
            to { width: 80px; }
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
        }

        .product-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .product-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--primary-color);
            color: white;
            padding: 0.3rem 0.6rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            animation: badgePulse 2s infinite;
        }

        @keyframes badgePulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .product-image {
            height: 200px;
            overflow: hidden;
            position: relative;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .product-card:hover .product-image img {
            transform: scale(1.1);
        }

        .product-info {
            padding: 1.5rem;
        }

        .product-title {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
            transition: color 0.3s ease;
        }

        .product-card:hover .product-title {
            color: var(--primary-color);
        }

        .product-category {
            font-size: 0.9rem;
            color: #7f8c8d;
            margin-bottom: 0.5rem;
            display: block;
        }

        .product-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .product-price::before {
            content: '$';
            font-size: 0.8em;
            margin-right: 2px;
        }

        .product-stock {
            font-size: 0.9rem;
            margin-bottom: 1rem;
            color: var(--success-color);
            display: flex;
            align-items: center;
        }

        .product-stock i {
            margin-right: 5px;
        }

        .product-stock.out-of-stock {
            color: var(--danger-color);
        }

        .product-actions {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            flex: 1;
            padding: 0.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            position: relative;
            overflow: hidden;
        }

        .action-btn i {
            transition: transform 0.3s ease;
        }

        .action-btn:hover i {
            transform: scale(1.2);
        }

        .add-to-cart {
            background: var(--primary-color);
            color: white;
        }

        .add-to-cart:hover {
            background: #ff5252;
        }

        .wishlist {
            background: var(--light-color);
            color: var(--dark-color);
        }

        .wishlist:hover {
            background: #dfe6e9;
        }

        .wishlist i {
            color: var(--primary-color);
            animation: heartBeatMini 1.5s infinite;
        }

        @keyframes heartBeatMini {
            0% { transform: scale(1); }
            25% { transform: scale(1.2); }
            50% { transform: scale(1); }
            75% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .quick-view {
            background: var(--secondary-color);
            color: white;
        }

        .quick-view:hover {
            background: #00cec9;
        }

        .product-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 0.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
        }

        .form-group label i {
            margin-right: 8px;
            color: var(--primary-color);
        }

        .form-group input,
        .form-group select {
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255,107,107,0.2);
            outline: none;
        }

        .form-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-update {
            background: var(--success-color);
            color: white;
        }

        .btn-update:hover {
            background: #00a884;
        }

        .btn-delete {
            background: var(--danger-color);
            color: white;
        }

        .btn-delete:hover {
            background: #c0392b;
        }

        .error-message {
            color: var(--danger-color);
            text-align: center;
            padding: 1rem;
            background: #ffecec;
            border-radius: 5px;
            margin: 1rem 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .error-message i {
            margin-right: 10px;
            animation: shakeError 0.5s ease;
        }

        @keyframes shakeError {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }

        .filter-sort-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .filter-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .filter-btn {
            padding: 0.5rem 1rem;
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }

        .filter-btn i {
            margin-right: 5px;
            transition: transform 0.3s ease;
        }

        .filter-btn:hover i {
            transform: rotate(15deg);
        }

        .filter-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .sort-select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
            transition: all 0.3s ease;
        }

        .sort-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255,107,107,0.2);
            outline: none;
        }

        .footer {
            background: var(--dark-color);
            color: white;
            padding: 3rem 2rem;
            margin-top: 3rem;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .footer-column h3 {
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .footer-column h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 40px;
            height: 2px;
            background: var(--primary-color);
            animation: lineGrow 1s ease-out;
        }

        .footer-column ul {
            list-style: none;
        }

        .footer-column ul li {
            margin-bottom: 0.8rem;
            transition: transform 0.3s ease;
        }

        .footer-column ul li:hover {
            transform: translateX(5px);
        }

        .footer-column ul li a {
            color: #b2bec3;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }

        .footer-column ul li a i {
            margin-right: 10px;
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
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-links a {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background: var(--primary-color);
            transform: translateY(-3px) scale(1.1);
        }

        .social-links a i {
            transition: transform 0.3s ease;
        }

        .social-links a:hover i {
            transform: scale(1.2);
        }

        .copyright {
            text-align: center;
            padding-top: 2rem;
            margin-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #b2bec3;
            font-size: 0.9rem;
        }

        /* Loading animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin-right: 10px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Floating cart icon animation */
        .cart-link {
            position: relative;
        }

        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                padding: 1rem;
            }

            .logo {
                margin-bottom: 1rem;
            }

            .search-container {
                width: 100%;
                margin: 1rem 0;
            }

            .user-actions {
                width: 100%;
                justify-content: space-around;
                margin-top: 1rem;
            }

            .hero-section h1 {
                font-size: 2rem;
            }

            .hero-section p {
                font-size: 1rem;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            }

            .filter-sort-container {
                flex-direction: column;
            }
        }

        /* Scroll to top button */
        .scroll-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--primary-color);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            opacity: 0;
            visibility: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 999;
        }

        .scroll-to-top.visible {
            opacity: 1;
            visibility: visible;
        }

        .scroll-to-top:hover {
            background: #ff5252;
            transform: translateY(-5px);
        }

        .scroll-to-top i {
            transition: transform 0.3s ease;
        }

        .scroll-to-top:hover i {
            transform: translateY(-3px);
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
            <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="search-form">
                <input type="search" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>

        <div class="user-actions">
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="add to cart.php" class="cart-link">
                <i class="fas fa-shopping-cart"></i>
                <span>Cart</span>
                <span class="cart-count">0</span>
            </a>
            <a href="wishlist.php" class="wishlist-link">
                <i class="fas fa-heart"></i>
                <span>Wishlist</span>
                <?php if (!empty($wishlist_items)): ?>
                    <span class="cart-count"><?php echo count($wishlist_items); ?></span>
                <?php endif; ?>
            </a>
            <?php if (!empty($email)): ?>
                <div class="dropdown">
                    <a href="#">
                        <i class="fas fa-user-circle"></i>
                        <span>Account</span>
                    </a>
                    <div class="dropdown-content">
                        <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
                        <a href="orders.php"><i class="fas fa-box"></i> My Orders</a>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?filter=my_products"><i class="fas fa-store"></i> My Products</a>
                        <a href="product.php"><i class="fas fa-plus-circle"></i> Add Product</a>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="signin.php">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login</span>
                </a>
            <?php endif; ?>
        </div>
    </header>

    <section class="hero-section">
        <h1>Welcome to M-Shopping</h1>
        <p>Discover amazing products at unbeatable prices. Shop with confidence and enjoy fast delivery to your doorstep.</p>
        <a href="#products" class="btn">Shop Now <i class="fas fa-arrow-right"></i></a>
    </section>

    <div class="container" id="products">
        <div class="section-title">
            <h2>Our Products</h2>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="filter-sort-container">
            <div class="filter-buttons">
                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="filter-btn <?php echo empty($filter) ? 'active' : ''; ?>">
                    <i class="fas fa-th"></i> All Products
                </a>
                <?php if (!empty($email)): ?>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>?filter=my_products" class="filter-btn <?php echo $filter == 'my_products' ? 'active' : ''; ?>">
                        <i class="fas fa-store"></i> My Products
                    </a>
                <?php endif; ?>
            </div>

            <form method="GET" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                <select name="sort" onchange="this.form.submit()" class="sort-select">
                    <option value="id DESC" <?php if ($sort == 'id DESC') echo 'selected'; ?>><i class="fas fa-sort-amount-down"></i> Newest</option>
                    <option value="pname ASC" <?php if ($sort == 'pname ASC') echo 'selected'; ?>><i class="fas fa-sort-alpha-down"></i> Name (A-Z)</option>
                    <option value="pname DESC" <?php if ($sort == 'pname DESC') echo 'selected'; ?>><i class="fas fa-sort-alpha-up"></i> Name (Z-A)</option>
                    <option value="pprice ASC" <?php if ($sort == 'pprice ASC') echo 'selected'; ?>><i class="fas fa-sort-numeric-down"></i> Price (Low to High)</option>
                    <option value="pprice DESC" <?php if ($sort == 'pprice DESC') echo 'selected'; ?>><i class="fas fa-sort-numeric-up"></i> Price (High to Low)</option>
                </select>
                <?php if (!empty($filter)): ?>
                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                <?php endif; ?>
            </form>
        </div>

        <div class="products-grid">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($record = mysqli_fetch_assoc($result)): ?>
                    <div class="product-card">
                        <?php if ($record['pqty'] > 10): ?>
                            <span class="product-badge">In Stock</span>
                        <?php elseif ($record['pqty'] > 0): ?>
                            <span class="product-badge" style="background: var(--warning-color);">Low Stock</span>
                        <?php else: ?>
                            <span class="product-badge" style="background: var(--danger-color);">Out of Stock</span>
                        <?php endif; ?>
                        
                        <div class="product-image">
                            <img src="UPLOADS/<?php echo htmlspecialchars($record['pfile']); ?>" alt="<?php echo htmlspecialchars($record['pname']); ?>">
                        </div>
                        
                        <div class="product-info">
                            <h3 class="product-title"><?php echo htmlspecialchars($record['pname']); ?></h3>
                            <span class="product-category"><?php echo htmlspecialchars($record['ptype']); ?></span>
                            <div class="product-price"><?php echo number_format($record['pprice'], 2); ?></div>
                            <div class="product-stock <?php echo $record['pqty'] == 0 ? 'out-of-stock' : ''; ?>">
                                <i class="fas fa-<?php echo $record['pqty'] == 0 ? 'times-circle' : 'check-circle'; ?>"></i>
                                <?php echo $record['pqty'] == 0 ? 'Out of Stock' : $record['pqty'] . ' available'; ?>
                            </div>
                            
                            <?php if ($filter == 'my_products' && $record['email'] == $email): ?>
                                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data" class="product-form">
                                    <input type="hidden" name="id" value="<?php echo $record['id']; ?>">
                                    
                                    <div class="form-group">
                                        <label for="P-name"><i class="fas fa-tag"></i> Product Name</label>
                                        <input type="text" name="P-name" value="<?php echo htmlspecialchars($record['pname']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="P-type"><i class="fas fa-list"></i> Category</label>
                                        <input type="text" name="P-type" value="<?php echo htmlspecialchars($record['ptype']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="P-price"><i class="fas fa-dollar-sign"></i> Price ($)</label>
                                        <input type="number" step="0.01" name="P-price" value="<?php echo htmlspecialchars($record['pprice']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="P-quantity"><i class="fas fa-boxes"></i> Quantity</label>
                                        <input type="number" name="P-quantity" value="<?php echo htmlspecialchars($record['pqty']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="P-file"><i class="fas fa-image"></i> Update Image</label>
                                        <input type="file" name="P-file" accept=".jpg,.jpeg,.png,.gif,.webp">
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="submit" name="update" class="action-btn btn-update">
                                            <i class="fas fa-save"></i> Update
                                        </button>
                                        <button type="submit" name="delete" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to delete this product?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="product-actions">
                                    <form action="add to cart.php?action=add" method="POST">
                                        <input type="hidden" name="pid" value="<?php echo $record['id']; ?>">
                                        <button type="submit" class="action-btn add-to-cart" <?php echo $record['pqty'] == 0 ? 'disabled' : ''; ?>>
                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                        </button>
                                    </form>
                                    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                                        <input type="hidden" name="product_id" value="<?php echo $record['id']; ?>">
                                        <?php if (in_array($record['id'], $wishlist_items)): ?>
                                            <input type="hidden" name="wishlist_action" value="remove">
                                            <button type="submit" class="action-btn wishlist" style="background: var(--danger-color); color: white;">
                                                <i class="fas fa-heart"></i> In Wishlist
                                            </button>
                                        <?php else: ?>
                                            <input type="hidden" name="wishlist_action" value="add">
                                            <button type="submit" class="action-btn wishlist">
                                                <i class="fas fa-heart"></i> Wishlist
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 2rem;">
                    <i class="fas fa-box-open" style="font-size: 3rem; color: #b2bec3; margin-bottom: 1rem;"></i>
                    <h3>No products found</h3>
                    <p>We couldn't find any products matching your criteria.</p>
                    <?php if (!empty($email) && $filter == 'my_products'): ?>
                        <a href="product.php" class="btn" style="margin-top: 1rem;">
                            <i class="fas fa-plus"></i> Add Your First Product
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-column">
                <h3>About M-Shopping</h3>
                <p>We are committed to providing the best shopping experience with high-quality products and excellent customer service.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            
            <div class="footer-column">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="#"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="#products"><i class="fas fa-shopping-bag"></i> Products</a></li>
                    <li><a href="#"><i class="fas fa-info-circle"></i> About Us</a></li>
                    <li><a href="#"><i class="fas fa-envelope"></i> Contact</a></li>
                    <li><a href="#"><i class="fas fa-question-circle"></i> FAQ</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Customer Service</h3>
                <ul>
                    <li><a href="profile.php"><i class="fas fa-user"></i> My Account</a></li>
                    <li><a href="orders.php"><i class="fas fa-truck"></i> Order Tracking</a></li>
                    <li><a href="wishlist.php"><i class="fas fa-heart"></i> Wishlist</a></li>
                    <li><a href="#"><i class="fas fa-shipping-fast"></i> Shipping Policy</a></li>
                    <li><a href="#"><i class="fas fa-exchange-alt"></i> Returns & Refunds</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Contact Us</h3>
                <ul>
                    <li><i class="fas fa-map-marker-alt"></i> 123 Shopping St, E-Commerce City</li>
                    <li><i class="fas fa-phone"></i> +1 234 567 890</li>
                    <li><i class="fas fa-envelope"></i> info@mshopping.com</li>
                </ul>
            </div>
        </div>
        
        <div class="copyright">
            &copy; <?php echo date('Y'); ?> M-Shopping. All rights reserved.
        </div>
    </footer>

    <a href="#" class="scroll-to-top">
        <i class="fas fa-arrow-up"></i>
    </a>

    <script>
        // Add loading animation to buttons when clicked
        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('button[type="submit"]');
            buttons.forEach(button => {
                button.addEventListener('click', function() {
                    if (!this.querySelector('.loading')) {
                        const loading = document.createElement('span');
                        loading.className = 'loading';
                        this.insertBefore(loading, this.firstChild);
                    }
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

            // Animation for product cards when they come into view
            const productCards = document.querySelectorAll('.product-card');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = 1;
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });

            productCards.forEach(card => {
                card.style.opacity = 0;
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.5s ease';
                observer.observe(card);
            });

            // Update cart count (example - you would replace with your actual cart count)
            const cartCount = document.querySelector('.cart-count');
            // This is just for demo - replace with your actual cart count logic
            const randomCount = Math.floor(Math.random() * 5) + 1;
            cartCount.textContent = randomCount;
        });
    </script>
</body>
</html>
<?php
mysqli_close($conn);
?>