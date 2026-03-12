<?php
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: signin.php");
    exit();
}

$conn = mysqli_connect('sql106.ezyro.com', 'ezyro_41226653', 'examapp1234567890', 'ezyro_41226653_bgnupdvt_data_base');
if (!$conn) die("Database connection failed: " . mysqli_connect_error());

$email  = $_SESSION['email'];
$error  = $success = '';

// ── Add profile_pic column if it doesn't exist yet ──
mysqli_query($conn, "ALTER TABLE data1 ADD COLUMN IF NOT EXISTS profile_pic VARCHAR(255) DEFAULT NULL");

// ── Fetch user ──
$stmt = mysqli_prepare($conn, "SELECT name1, email, cellno, profile_pic FROM data1 WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$user_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$user_data) { $error = "User data not found"; }

// ── Handle profile picture upload ──
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {
    $allowed = ['image/jpeg','image/jpg','image/png','image/gif','image/webp'];
    $ftype   = mime_content_type($_FILES['profile_pic']['tmp_name']);

    if (!in_array($ftype, $allowed)) {
        $error = "Only JPG, PNG, GIF, WEBP images are allowed.";
    } elseif ($_FILES['profile_pic']['size'] > 3 * 1024 * 1024) {
        $error = "Image must be under 3MB.";
    } else {
        $ext      = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . md5($email . time()) . '.' . $ext;
        $dest     = 'UPLOADS/' . $filename;

        if (!is_dir('UPLOADS')) mkdir('UPLOADS', 0755, true);

        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $dest)) {
            // Delete old pic
            if (!empty($user_data['profile_pic']) && file_exists('UPLOADS/' . $user_data['profile_pic'])) {
                unlink('UPLOADS/' . $user_data['profile_pic']);
            }
            $stmt = mysqli_prepare($conn, "UPDATE data1 SET profile_pic = ? WHERE email = ?");
            mysqli_stmt_bind_param($stmt, "ss", $filename, $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $user_data['profile_pic'] = $filename;
            $success = "Profile picture updated!";
        } else {
            $error = "Failed to upload image. Please try again.";
        }
    }
}

// ── Handle profile info update ──
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name  = mysqli_real_escape_string($conn, trim($_POST['name']  ?? ''));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone'] ?? ''));

    if (empty($name)) {
        $error = "Name is required";
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE data1 SET name1 = ?, cellno = ? WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "sss", $name, $phone, $email);
        if (mysqli_stmt_execute($stmt)) {
            $success = "Profile updated successfully!";
            $user_data['name1']  = $name;
            $user_data['cellno'] = $phone;
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

mysqli_close($conn);

// Avatar initials fallback
$initials = 'U';
if (!empty($user_data['name1'])) {
    $parts    = explode(' ', trim($user_data['name1']));
    $initials = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) $initials .= strtoupper(substr(end($parts), 0, 1));
}
$has_pic   = !empty($user_data['profile_pic']) && file_exists('UPLOADS/' . $user_data['profile_pic']);
$pic_src   = $has_pic ? 'UPLOADS/' . htmlspecialchars($user_data['profile_pic']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | M-Shopping</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary:   #ff6b6b;
            --primary-d: #ff5252;
            --accent:    #ffbe76;
            --dark:      #2d3436;
            --light:     #f5f6fa;
            --success:   #00b894;
            --danger:    #d63031;
            --grad:      linear-gradient(135deg, #ff6b6b, #ffbe76);
        }
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            color: var(--dark);
        }

        /* ── Header ── */
        .header {
            background: var(--grad);
            color: white; padding: 1rem 2rem;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
            position: sticky; top: 0; z-index: 1000;
        }
        .logo { font-size: 1.8rem; font-weight: 700; display: flex; align-items: center; gap:.5rem; }
        .logo span { font-weight: 300; }
        .user-actions { display: flex; align-items: center; }
        .user-actions a {
            color: white; text-decoration: none; margin-left: 1.5rem;
            display: flex; flex-direction: column; align-items: center;
            font-size: .9rem; transition: transform .3s;
        }
        .user-actions a:hover { transform: translateY(-3px); }
        .user-actions i { font-size: 1.3rem; margin-bottom: .3rem; }

        .logout-btn {
            position: fixed; top: 20px; left: 20px;
            background: var(--danger); color: white;
            padding: .8rem 1.2rem; border: none; border-radius: 30px;
            font-weight: 500; cursor: pointer; transition: all .3s;
            text-decoration: none; display: inline-flex; align-items: center; gap:.5rem;
            z-index: 1001; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            font-family: 'Poppins', sans-serif;
        }
        .logout-btn:hover { background: #c0392b; transform: translateY(-3px); }

        /* ── Bubbles background ── */
        .bubbles { position: fixed; inset:0; pointer-events:none; z-index:0; overflow:hidden; }
        .bubble {
            position: absolute; bottom: -120px; border-radius: 50%;
            background: radial-gradient(circle at 30% 30%, rgba(255,190,118,.3), rgba(255,107,107,.12));
            animation: rise linear infinite;
        }
        .bubble:nth-child(1) { width:55px;  height:55px;  left:4%;   animation-duration:13s; }
        .bubble:nth-child(2) { width:28px;  height:28px;  left:16%;  animation-duration:10s; animation-delay:2s; }
        .bubble:nth-child(3) { width:80px;  height:80px;  left:30%;  animation-duration:17s; animation-delay:4s; }
        .bubble:nth-child(4) { width:40px;  height:40px;  left:45%;  animation-duration:12s; animation-delay:1s; }
        .bubble:nth-child(5) { width:65px;  height:65px;  left:60%;  animation-duration:15s; animation-delay:3s; }
        .bubble:nth-child(6) { width:22px;  height:22px;  left:75%;  animation-duration:9s;  animation-delay:5s; }
        .bubble:nth-child(7) { width:50px;  height:50px;  left:88%;  animation-duration:14s; animation-delay:1.5s; }
        @keyframes rise {
            0%   { transform:translateY(0) rotate(0deg);     opacity:.6; }
            100% { transform:translateY(-110vh) rotate(360deg); opacity:0; }
        }

        /* ── Layout ── */
        .container {
            max-width: 1000px; margin: 2.5rem auto;
            padding: 0 1.2rem; position: relative; z-index: 1;
        }
        .page-title { text-align:center; margin-bottom:2rem; position:relative; }
        .page-title h2 { font-size:2rem; color:var(--dark); display:inline-block; padding-bottom:.5rem; }
        .page-title h2::after {
            content:''; position:absolute; width:80px; height:3px;
            background:var(--grad); bottom:0; left:50%; transform:translateX(-50%); border-radius:2px;
        }

        /* ── Alerts ── */
        .alert {
            padding:1rem 1.5rem; border-radius:10px; margin-bottom:1.5rem;
            display:flex; align-items:center; gap:.8rem;
            animation: slideDown .4s ease;
        }
        @keyframes slideDown {
            from { opacity:0; transform:translateY(-10px); }
            to   { opacity:1; transform:translateY(0); }
        }
        .alert-success { background:rgba(0,184,148,.1); color:var(--success); border-left:4px solid var(--success); }
        .alert-danger  { background:rgba(214,48,49,.1);  color:var(--danger);  border-left:4px solid var(--danger); }

        /* ── Profile grid ── */
        .profile-grid {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 1.8rem;
            align-items: start;
        }

        /* ── Sidebar ── */
        .sidebar-card {
            background: white; border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.09);
            padding: 2rem 1.5rem; text-align: center;
            position: relative; overflow: hidden;
            animation: fadeInLeft .5s ease both;
        }
        @keyframes fadeInLeft {
            from { opacity:0; transform:translateX(-25px); }
            to   { opacity:1; transform:translateX(0); }
        }
        .sidebar-card::before {
            content:''; position:absolute; top:0; left:0; right:0;
            height:5px; background:var(--grad);
        }

        /* ── Avatar upload area ── */
        .avatar-wrap {
            position: relative;
            width: 130px; height: 130px;
            margin: 0 auto 1.2rem;
        }
        .avatar-ring {
            width: 130px; height: 130px;
            border-radius: 50%; padding: 3px;
            background: var(--grad);
            box-shadow: 0 0 0 0 rgba(255,107,107,.4);
            animation: glow 2.5s ease-in-out infinite;
        }
        @keyframes glow {
            0%,100% { box-shadow: 0 0 0 0 rgba(255,107,107,.4); }
            50%      { box-shadow: 0 0 0 10px rgba(255,107,107,.0); }
        }
        .avatar-inner {
            width: 100%; height: 100%; border-radius: 50%;
            background: var(--grad);
            display: flex; align-items: center; justify-content: center;
            font-size: 2.6rem; font-weight: 700; color: white;
            overflow: hidden; cursor: pointer; position: relative;
        }
        .avatar-inner img {
            width: 100%; height: 100%; object-fit: cover; border-radius: 50%;
        }

        /* camera overlay on hover */
        .avatar-overlay {
            position: absolute; inset:0; border-radius:50%;
            background: rgba(0,0,0,0);
            display: flex; align-items: center; justify-content: center;
            flex-direction: column; gap:.2rem;
            color: white; font-size: .7rem; font-weight:600;
            cursor: pointer; transition: background .3s;
            opacity: 0;
        }
        .avatar-overlay i { font-size: 1.5rem; }
        .avatar-wrap:hover .avatar-overlay { background: rgba(0,0,0,.45); opacity:1; }
        .avatar-wrap:hover .avatar-ring    { box-shadow: 0 0 0 4px rgba(255,107,107,.5); }

        /* hidden file input */
        #picInput { display:none; }

        /* upload progress bar */
        .upload-progress {
            display: none; margin: .5rem auto 0;
            width: 130px; height: 4px; background: #eee;
            border-radius: 2px; overflow: hidden;
        }
        .upload-progress-bar {
            height: 100%; width: 0;
            background: var(--grad); border-radius: 2px;
            transition: width .3s ease;
        }

        .sidebar-name  { font-size:1.2rem; font-weight:700; color:var(--dark); margin-bottom:.2rem; }
        .sidebar-email { font-size:.8rem; color:#999; margin-bottom:1.2rem; word-break:break-all; }

        /* ── Main card ── */
        .main-card {
            background: white; border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.09);
            padding: 2.2rem; position: relative; overflow: hidden;
            animation: fadeInRight .5s ease both;
        }
        @keyframes fadeInRight {
            from { opacity:0; transform:translateX(25px); }
            to   { opacity:1; transform:translateX(0); }
        }
        .main-card::before {
            content:''; position:absolute; top:0; left:0; right:0;
            height:5px; background:var(--grad);
        }
        /* decorative blobs */
        .blob { position:absolute; border-radius:50%; filter:blur(55px); pointer-events:none; z-index:0; }
        .blob-1 { width:180px; height:180px; background:rgba(255,107,107,.07); top:-50px; right:-50px; }
        .blob-2 { width:130px; height:130px; background:rgba(255,190,118,.07); bottom:-30px; left:-30px; }

        .card-body { position:relative; z-index:1; }
        .card-title {
            font-size:1.3rem; font-weight:700; color:var(--dark);
            margin-bottom:1.8rem; display:flex; align-items:center; gap:.6rem;
        }
        .card-title i { color:var(--primary); }
        .card-title::after {
            content:''; flex:1; height:2px;
            background:linear-gradient(to right, rgba(255,107,107,.25), transparent);
            margin-left:.4rem;
        }

        /* ── Form ── */
        .form-group { margin-bottom:1.5rem; }
        .form-group label { display:block; margin-bottom:.5rem; font-weight:600; font-size:.88rem; color:var(--dark); }
        .input-wrap { position:relative; }
        .input-icon {
            position:absolute; left:14px; top:50%; transform:translateY(-50%);
            color:#ccc; font-size:.95rem; transition:color .3s;
        }
        .form-control {
            width:100%; padding:.85rem 1rem .85rem 2.7rem;
            border:2px solid #eee; border-radius:12px;
            font-size:.93rem; font-family:'Poppins',sans-serif; color:var(--dark);
            background:var(--light); transition:all .3s;
        }
        .form-control:focus {
            outline:none; border-color:var(--primary);
            box-shadow:0 0 0 4px rgba(255,107,107,.1); background:white;
        }
        .form-control:focus + .input-icon { color:var(--primary); }
        .form-control[disabled] { background:#f0f0f0; cursor:not-allowed; color:#aaa; }

        .save-btn {
            width:100%; padding:.95rem; border:none; border-radius:30px;
            background:var(--grad); color:white;
            font-size:1rem; font-weight:600; cursor:pointer;
            font-family:'Poppins',sans-serif;
            display:flex; align-items:center; justify-content:center; gap:.6rem;
            transition:all .3s; box-shadow:0 6px 20px rgba(255,107,107,.3);
            position:relative; overflow:hidden;
        }
        .save-btn:hover { transform:translateY(-3px); box-shadow:0 10px 25px rgba(255,107,107,.4); }
        .save-btn:active { transform:translateY(0); }

        /* ripple */
        .ripple {
            position:absolute; border-radius:50%;
            background:rgba(255,255,255,.35);
            transform:scale(0); animation:rippleAnim .55s linear;
            pointer-events:none;
        }
        @keyframes rippleAnim { to { transform:scale(5); opacity:0; } }

        @media (max-width:780px) {
            .profile-grid { grid-template-columns:1fr; }
            .header { flex-direction:column; padding:1rem; }
            .logo { margin-bottom:1rem; }
            .user-actions { width:100%; justify-content:space-around; margin-top:1rem; }
            .logout-btn { position:static; margin:1rem auto; display:block; width:fit-content; }
        }
    </style>
</head>
<body>

<div class="bubbles">
    <?php for($i=0;$i<7;$i++) echo '<div class="bubble"></div>'; ?>
</div>

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
    <div class="page-title"><h2>My Profile</h2></div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if (!empty($user_data)): ?>
    <div class="profile-grid">

        <!-- ── SIDEBAR ── -->
        <div class="sidebar-card">

            <!-- Profile picture upload -->
            <form method="POST" enctype="multipart/form-data" id="picForm">
                <div class="avatar-wrap" onclick="document.getElementById('picInput').click()" title="Click to change photo">
                    <div class="avatar-ring">
                        <div class="avatar-inner" id="avatarInner">
                            <?php if ($has_pic): ?>
                                <img src="<?= $pic_src ?>" alt="Profile" id="avatarImg">
                            <?php else: ?>
                                <span id="avatarInitials"><?= htmlspecialchars($initials) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- overlay always on top -->
                    <div class="avatar-overlay">
                        <i class="fas fa-camera"></i>
                        <span>Change</span>
                    </div>
                </div>

                <div class="upload-progress" id="uploadProgress">
                    <div class="upload-progress-bar" id="uploadBar"></div>
                </div>

                <input type="file" id="picInput" name="profile_pic"
                       accept="image/jpeg,image/png,image/gif,image/webp"
                       onchange="handlePicChange(this)">
            </form>

            <div class="sidebar-name" id="sidebarName"><?= htmlspecialchars($user_data['name1'] ?? 'User') ?></div>
            <div class="sidebar-email"><?= htmlspecialchars($user_data['email'] ?? '') ?></div>
        </div>

        <!-- ── MAIN CARD ── -->
        <div class="main-card">
            <div class="blob blob-1"></div>
            <div class="blob blob-2"></div>
            <div class="card-body">
                <div class="card-title"><i class="fas fa-id-card"></i> Profile Information</div>

                <form method="POST">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <div class="input-wrap">
                            <input type="text" id="name" name="name" class="form-control"
                                   value="<?= htmlspecialchars($user_data['name1'] ?? '') ?>"
                                   required placeholder="Enter your full name"
                                   oninput="syncName(this.value)">
                            <i class="fas fa-user input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>
                        <div class="input-wrap">
                            <input type="email" class="form-control"
                                   value="<?= htmlspecialchars($user_data['email'] ?? '') ?>" disabled>
                            <i class="fas fa-envelope input-icon"></i>
                        </div>
                        <small style="color:#bbb;font-size:.76rem;margin-top:.3rem;display:block;">
                            <i class="fas fa-lock" style="font-size:.7rem;"></i> Email cannot be changed
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <div class="input-wrap">
                            <input type="tel" id="phone" name="phone" class="form-control"
                                   value="<?= htmlspecialchars($user_data['cellno'] ?? '') ?>"
                                   placeholder="e.g. 03001234567">
                            <i class="fas fa-phone input-icon"></i>
                        </div>
                    </div>

                    <button type="submit" name="update_profile" class="save-btn" id="saveBtn">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </form>
            </div>
        </div>

    </div>
    <?php endif; ?>
</div>

<script>
    // ── Live name sync to sidebar ──
    function syncName(val) {
        document.getElementById('sidebarName').textContent = val || 'User';
        // update initials if no pic
        if (!document.getElementById('avatarImg')) {
            const parts    = val.trim().split(/\s+/).filter(Boolean);
            let ini        = parts[0] ? parts[0][0].toUpperCase() : '?';
            if (parts.length > 1) ini += parts[parts.length-1][0].toUpperCase();
            const el = document.getElementById('avatarInitials');
            if (el) el.textContent = ini;
        }
    }

    // ── Profile picture preview & auto-submit ──
    function handlePicChange(input) {
        if (!input.files || !input.files[0]) return;
        const file = input.files[0];

        // Client-side size check
        if (file.size > 3 * 1024 * 1024) {
            alert('Image must be under 3MB.');
            input.value = '';
            return;
        }

        // Live preview
        const reader = new FileReader();
        reader.onload = function(e) {
            const inner = document.getElementById('avatarInner');
            inner.innerHTML = `<img src="${e.target.result}" alt="Preview" id="avatarImg" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
        };
        reader.readAsDataURL(file);

        // Show progress animation then submit
        const prog = document.getElementById('uploadProgress');
        const bar  = document.getElementById('uploadBar');
        prog.style.display = 'block';
        let w = 0;
        const iv = setInterval(() => {
            w = Math.min(w + 8, 85);
            bar.style.width = w + '%';
        }, 60);

        setTimeout(() => {
            clearInterval(iv);
            bar.style.width = '100%';
            document.getElementById('picForm').submit();
        }, 700);
    }

    // ── Ripple on save button ──
    document.getElementById('saveBtn')?.addEventListener('click', function(e) {
        const rect   = this.getBoundingClientRect();
        const ripple = document.createElement('span');
        const size   = Math.max(rect.width, rect.height);
        ripple.className = 'ripple';
        ripple.style.cssText = `width:${size}px;height:${size}px;left:${e.clientX-rect.left-size/2}px;top:${e.clientY-rect.top-size/2}px;`;
        this.appendChild(ripple);
        setTimeout(() => ripple.remove(), 600);
    });
</script>
</body>
</html>