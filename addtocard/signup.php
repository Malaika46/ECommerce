<?php
session_start();

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class Database {
    private $host = 'sql106.ezyro.com';
    private $user = 'ezyro_41226653';
    private $pass = 'examapp1234567890';
    private $dbname = 'ezyro_41226653_bgnupdvt_data_base';
    private $conn;
    public function __construct() {
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);
        
        if ($this->conn->connect_error) {
            throw new Exception("Database connection failed: " . $this->conn->connect_error);
        }
    }

    public function emailExists($email) {
        $stmt = $this->conn->prepare("SELECT id FROM data1 WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }

    public function registerUser($username, $email, $phone, $password) {
        $stmt = $this->conn->prepare("INSERT INTO data1 (name1, email, cellno, pasword) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $phone, $password);
        return $stmt->execute();
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

class Validator {
    public static function validateUsername($username) {
        if (empty(trim($username))) {
            return 'Username is required';
        }
        if (strlen($username) < 3 || strlen($username) > 20) {
            return 'Username must be 3-20 characters';
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return 'Only letters, numbers and underscores allowed';
        }
        return null;
    }

    public static function validateEmail($email) {
        if (empty(trim($email))) {
            return 'Email is required';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Invalid email format';
        }
        return null;
    }

    public static function validatePhone($phone) {
        if (empty(trim($phone))) {
            return 'Phone number is required';
        }
        if (!preg_match('/^[0-9]{10,15}$/', $phone)) {
            return 'Phone must be 10-15 digits';
        }
        return null;
    }

    public static function validatePassword($password, $confirm_password) {
        if (empty($password)) {
            return ['Password is required', null];
        }
        if (strlen($password) < 8) {
            return ['Password must be at least 8 characters', null];
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return ['Password must contain at least one uppercase letter', null];
        }
        if (!preg_match('/[0-9]/', $password)) {
            return ['Password must contain at least one number', null];
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return ['Password must contain at least one special character', null];
        }
        if ($password !== $confirm_password) {
            return ['Passwords do not match', null];
        }
        return [null, password_hash($password, PASSWORD_BCRYPT)];
    }
}

// Initialize variables
$errors = [];
$formData = [
    'username' => '',
    'email' => '',
    'cellno' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $formData['username'] = htmlspecialchars(trim($_POST['username'] ?? ''));
    $formData['email'] = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $formData['cellno'] = preg_replace('/[^0-9]/', '', trim($_POST['cellno'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['cpassword'] ?? '';

    // Validate inputs
    $errors['username'] = Validator::validateUsername($formData['username']);
    $errors['email'] = Validator::validateEmail($formData['email']);
    $errors['cellno'] = Validator::validatePhone($formData['cellno']);
    list($errors['password'], $hashed_password) = Validator::validatePassword($password, $confirm_password);

    // Remove null errors
    $errors = array_filter($errors);

    // If no errors, proceed with registration
    if (empty($errors)) {
        try {
            $db = new Database();
            
            if ($db->emailExists($formData['email'])) {
                $errors['email'] = 'Email already registered. <a href="signin.php">Sign in instead?</a>';
            } else {
                if ($db->registerUser($formData['username'], $formData['email'], $formData['cellno'], $hashed_password)) {
                    $_SESSION['registration_success'] = true;
                    $_SESSION['success_message'] = 'Registration successful! Please login.';
                    $_SESSION['temp_email'] = $formData['email'];
                    header("Location: signin.php");
                    exit();
                } else {
                    throw new Exception("Registration failed");
                }
            }
        } catch (Exception $e) {
            error_log("Registration Error: " . $e->getMessage());
            $errors['database'] = 'System error. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | M-Shopping</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #4f46e5;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray: #64748b;
            --light-gray: #e2e8f0;
            --danger: #dc2626;
            --success: #16a34a;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f1f5f9;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--dark);
            line-height: 1.6;
        }

        .signup-container {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin: 1rem;
        }

        .signup-header {
            padding: 2rem;
            text-align: center;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
        }

        .signup-header h1 {
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
        }

        .signup-header p {
            opacity: 0.9;
        }

        .logo {
            margin-bottom: 1rem;
        }

        .logo i {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .signup-form {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--light-gray);
            border-radius: 0.375rem;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 0.375rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn:hover {
            background: linear-gradient(to right, var(--primary-dark), var(--secondary));
        }

        .error-message {
            color: var(--danger);
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .error-message i {
            font-size: 0.75rem;
        }

        .signup-footer {
            padding: 1.5rem;
            text-align: center;
            border-top: 1px solid var(--light-gray);
            color: var(--gray);
        }

        .signup-footer a {
            color: var(--primary);
            font-weight: 500;
            text-decoration: none;
        }

        .signup-footer a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0.375rem;
            background-color: #fee2e2;
            color: var(--danger);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert i {
            font-size: 1.25rem;
        }

        @media (max-width: 640px) {
            .signup-container {
                margin: 0.5rem;
            }
            
            .signup-header, .signup-form {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <div class="signup-header">
            <div class="logo">
                <i class="fas fa-shopping-bag"></i>
                <h2>M-Shopping</h2>
            </div>
            <h1>Create Your Account</h1>
            <p>Join us to start shopping</p>
        </div>

        <div class="signup-form">
            <?php if (isset($errors['database'])): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $errors['database']; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?php echo htmlspecialchars($formData['username']); ?>" required>
                    <?php if (isset($errors['username'])): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?php echo $errors['username']; ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($formData['email']); ?>" required>
                    <?php if (isset($errors['email'])): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?php echo $errors['email']; ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="cellno">Phone Number</label>
                    <input type="tel" class="form-control" id="cellno" name="cellno" 
                           value="<?php echo htmlspecialchars($formData['cellno']); ?>" required>
                    <?php if (isset($errors['cellno'])): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?php echo $errors['cellno']; ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <?php if (isset($errors['password'])): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?php echo $errors['password']; ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="cpassword">Confirm Password</label>
                    <input type="password" class="form-control" id="cpassword" name="cpassword" required>
                </div>

                <button type="submit" class="btn">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
        </div>

        <div class="signup-footer">
            Already have an account? <a href="signin.php">Sign in</a>
        </div>
    </div>

    <script>
        // Simple password visibility toggle
        document.querySelectorAll('.form-control[type="password"]').forEach(input => {
            const eyeIcon = document.createElement('i');
            eyeIcon.className = 'fas fa-eye';
            eyeIcon.style.position = 'absolute';
            eyeIcon.style.right = '10px';
            eyeIcon.style.top = '50%';
            eyeIcon.style.transform = 'translateY(-50%)';
            eyeIcon.style.cursor = 'pointer';
            
            const wrapper = document.createElement('div');
            wrapper.style.position = 'relative';
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);
            wrapper.appendChild(eyeIcon);
            
            eyeIcon.addEventListener('click', () => {
                if (input.type === 'password') {
                    input.type = 'text';
                    eyeIcon.className = 'fas fa-eye-slash';
                } else {
                    input.type = 'password';
                    eyeIcon.className = 'fas fa-eye';
                }
            });
        });
    </script>
</body>
</html>