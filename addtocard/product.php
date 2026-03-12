<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Redirect if not logged in
if (!isset($_SESSION['email'])) {
    header("Location: signin.php");
    exit();
}

// Database connection
$conn = mysqli_connect('sql106.ezyro.com', 'ezyro_41226653', 'examapp1234567890', 'ezyro_41226653_bgnupdvt_data_base');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Initialize variables
$success = $error = '';
$Pname = $Ptype = $Pprice = $Pquantity = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate inputs
        $Pname = mysqli_real_escape_string($conn, $_POST['P-name'] ?? '');
        $Ptype = mysqli_real_escape_string($conn, $_POST['P-type'] ?? '');
        $Pprice = mysqli_real_escape_string($conn, $_POST['P-price'] ?? '');
        $Pquantity = mysqli_real_escape_string($conn, $_POST['P-quantity'] ?? '');
        $email = $_SESSION['email'];

        // Validate required fields
        if (empty($Pname) || empty($Ptype) || empty($Pprice) || empty($Pquantity)) {
            throw new Exception("All fields are required");
        }

        // File upload handling
        if (!isset($_FILES['P-file']) || $_FILES['P-file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Please select a valid image file");
        }

        $file_name = $_FILES['P-file']['name'];
        $file_tmp = $_FILES['P-file']['tmp_name'];
        $file_size = $_FILES['P-file']['size'];
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . "/E-Commerce/addtocard/UPLOADS/";

        // Create upload directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                throw new Exception("Failed to create upload directory");
            }
        }

        // Validate file size (5MB max)
        if ($file_size > 5242880) {
            throw new Exception("File size exceeds 5MB limit");
        }

        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($file_tmp);
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception("Only JPG, PNG, and GIF images are allowed");
        }

        // Generate unique filename while preserving original name
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        $file_base = pathinfo($file_name, PATHINFO_FILENAME);
        $file_base = preg_replace("/[^a-zA-Z0-9_-]/", "", $file_base);
        $unique_name = $file_base . '_' . time() . '.' . $file_ext;
        $upload_path = $upload_dir . $unique_name;

        // Move uploaded file
        if (!move_uploaded_file($file_tmp, $upload_path)) {
            throw new Exception("Failed to save uploaded file");
        }

        // Insert into database
        $query = "INSERT INTO product (pname, ptype, pprice, pqty, pfile, email) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssdiss", $Pname, $Ptype, $Pprice, $Pquantity, $unique_name, $email);
        
        if (!mysqli_stmt_execute($stmt)) {
            unlink($upload_path); // Remove the uploaded file if DB insert fails
            throw new Exception("Database error: " . mysqli_error($conn));
        }

        // Set success message and redirect to clear form
        $_SESSION['success'] = "Product added successfully!";
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Check for success message from redirect
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product | M-Shopping</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #ff6b6b;
            --primary-light: #ff8e8e;
            --secondary: #4ecdc4;
            --dark: #2d3436;
            --light: #f5f6fa;
            --success: #00b894;
            --error: #d63031;
            --gray: #dfe6e9;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f5f7fa;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 800px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header {
            padding: 25px;
            text-align: center;
            background: white;
            border-bottom: 1px solid var(--gray);
            position: relative;
        }

        .home-btn {
            position: absolute;
            left: 20px;
            top: 20px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--light);
            color: var(--dark);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            z-index: 10;
        }

        .home-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px) scale(1.1);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
        }

        .header h1 {
            color: var(--primary);
            margin-bottom: 5px;
            font-size: 1.8rem;
            position: relative;
            display: inline-block;
        }

        .header h1::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: var(--primary);
            border-radius: 3px;
        }

        .form-container {
            padding: 25px;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
            transition: all 0.3s;
        }

        .form-group:hover label {
            color: var(--primary);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--gray);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
            outline: none;
        }

        .file-upload {
            border: 2px dashed var(--gray);
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            cursor: pointer;
            margin-bottom: 15px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .file-upload:hover {
            border-color: var(--primary);
            background: rgba(255, 107, 107, 0.05);
        }

        .file-upload i {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 10px;
            transition: all 0.3s;
        }

        .file-upload:hover i {
            transform: translateY(-5px);
            animation: bounce 1s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(-5px); }
            50% { transform: translateY(0); }
        }

        .file-name {
            margin-top: 10px;
            font-weight: 500;
            color: var(--dark);
        }

        .preview-container {
            margin-top: 15px;
            text-align: center;
            transition: all 0.3s;
        }

        .preview-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            display: none;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 107, 107, 0.4);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .submit-btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }

        .submit-btn:hover::after {
            left: 100%;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .alert-success {
            background: rgba(0, 184, 148, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .alert-error {
            background: rgba(214, 48, 49, 0.1);
            color: var(--error);
            border-left: 4px solid var(--error);
        }

        .alert i {
            margin-right: 10px;
            font-size: 1.5rem;
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
            }
            
            .header, .form-container {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="index1.php" class="home-btn">
                <i class="fas fa-home"></i>
            </a>
            <h1>Add New Product</h1>
        </div>

        <div class="form-container">
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div><?= $success ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?= $error ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="product-form">
                <div class="form-group">
                    <label for="P-name">Product Name</label>
                    <input type="text" id="P-name" name="P-name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="P-type">Product Category</label>
                    <input type="text" id="P-type" name="P-type" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="P-price">Product Price</label>
                    <input type="number" id="P-price" name="P-price" class="form-control" min="0" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="P-quantity">Available Quantity</label>
                    <input type="number" id="P-quantity" name="P-quantity" class="form-control" min="1" required>
                </div>

                <div class="form-group">
                    <label>Product Image</label>
                    <div class="file-upload" id="file-upload-area">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <div>Click to upload product image</div>
                        <div class="file-name" id="file-name-display"></div>
                        <input type="file" id="P-file" name="P-file" style="display: none;" required>
                    </div>
                    <div class="preview-container">
                        <img id="image-preview" class="preview-image" src="#" alt="Preview">
                    </div>
                </div>

                <button type="submit" class="submit-btn" id="submit-btn">
                    <i class="fas fa-plus"></i> Add Product
                </button>
            </form>
        </div>
    </div>

    <script>
        // File upload handling with enhanced UI
        const fileUploadArea = document.getElementById('file-upload-area');
        const fileInput = document.getElementById('P-file');
        const fileNameDisplay = document.getElementById('file-name-display');
        const imagePreview = document.getElementById('image-preview');
        const productForm = document.getElementById('product-form');
        const submitBtn = document.getElementById('submit-btn');

        fileUploadArea.addEventListener('click', () => fileInput.click());

        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                const file = this.files[0];
                fileNameDisplay.textContent = file.name;
                
                // Show image preview with animation
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                    imagePreview.style.animation = 'fadeIn 0.5s ease';
                }
                reader.readAsDataURL(file);
            } else {
                fileNameDisplay.textContent = '';
                imagePreview.style.display = 'none';
            }
        });

        // Form submission handling
        productForm.addEventListener('submit', function(e) {
            // Prevent default form submission
            e.preventDefault();
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding Product...';
            submitBtn.disabled = true;
            
            // Submit form programmatically after a small delay
            setTimeout(() => {
                this.submit();
            }, 300);
        });

        // Add hover effect to form controls
        document.querySelectorAll('.form-control').forEach(control => {
            control.addEventListener('focus', () => {
                control.parentElement.querySelector('label').style.color = 'var(--primary)';
            });
            control.addEventListener('blur', () => {
                control.parentElement.querySelector('label').style.color = 'var(--dark)';
            });
        });
    </script>
</body>
</html>
<?php
mysqli_close($conn);
?>