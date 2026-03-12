<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $cellno = $_POST['cellno'];
    $password = $_POST['password'];
    $confirm_password = $_POST['cpassword'];

    if ($password != $confirm_password) {
        echo "<script>alert('Passwords do not match.'); window.location.href = 'signup.php';</script>";
        exit();
    }

    // Connect to the database
    $conn = mysqli_connect('sql106.ezyro.com', 'ezyro_41226653', 'examapp1234567890', 'ezyro_41226653_bgnupdvt_data_base');
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // Hash the password before storing it
   

    // Insert user data into the database
    $add = "INSERT INTO data1 (name1, email, cellno, pasword) VALUES ('$username', '$email', '$cellno', '$password')";
    if (mysqli_query($conn, $add)) {
        echo "<script>alert('You are registered successfully.'); window.location.href = 'signin.php';</script>";
    } else {
        echo "<script>alert('Error adding user: " . mysqli_error($conn) . "'); window.location.href = 'signup.php';</script>";
    }

    // Close the database connection
    mysqli_close($conn);
}
?>
