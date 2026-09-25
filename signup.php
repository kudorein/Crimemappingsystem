<?php
require_once 'conn.php';

if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit();
}

$error_msg = "";
$success_msg = "";

if (isset($_POST["register"])) {
    $fname = mysqli_real_escape_string($conn, trim($_POST["fname"]));
    $lname = mysqli_real_escape_string($conn, trim($_POST["lname"]));
    $email = mysqli_real_escape_string($conn, trim($_POST["email"]));
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    if ($password !== $confirm_password) {
        $error_msg = "Token conflict: Passphrases do not match.";
    } elseif (strlen($password) < 6) {
        $error_msg = "Security vulnerability: Password must be at least 6 characters long.";
    } else {
        $check_query = "SELECT uid FROM users WHERE email = '$email' LIMIT 1";
        $check_result = mysqli_query($conn, $check_query);

        if (mysqli_num_rows($check_result) > 0) {
            $error_msg = "Administrative email identity already active.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            
            // 1. Generate a clean random cryptographic hex token string 
            $generated_qr_token = bin2hex(random_bytes(16));
            
            // 2. Insert user payload parameters alongside your unique token string value context
            $insert_query = "INSERT INTO users (fname, lname, email, password, type, qr_token) 
                             VALUES ('$fname', '$lname', '$email', '$hashed_password', 'admin', '$generated_qr_token')";
            
            if (mysqli_query($conn, $insert_query)) {
                // 3. Keep token alive across redirect buffer inside server memory
                $_SESSION['registration_success_token'] = $generated_qr_token;
                
                header("Location: registration_success.php");
                exit();
            } else {
                $error_msg = "Database Exception: Account configuration failed: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CrimeMapping - Register Admin</title>
    <link rel="stylesheet" href="login.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-brand-side">
            <div class="brand-overlay"></div>
            <div class="brand-content">
                <div class="brand-badge">CRIME MAPPING GIS</div>
                <h1>Administrative Profile Enrollment</h1>
                <p>Register identity parameters to access analytical mapping dashboards and live incident response tracking fields.</p>
            </div>
        </div>

        <div class="auth-form-side">
            <div class="auth-box">
                <div class="auth-header">
                    <h2>Register Administrative Node</h2>
                    <p>Establish credentials below.</p>
                </div>

                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger"><?php echo $error_msg; ?></div>
                <?php endif; ?>

                <?php if (!empty($success_msg)): ?>
                    <div class="alert alert-success"><?php echo $success_msg; ?></div>
                <?php endif; ?>

                <form action="signup.php" method="POST" autocomplete="off">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                        <div>
                            <label style="display:block; font-size:11px; font-weight:600; text-transform:uppercase; color:#94a3b8; margin-bottom:6px;">First Name</label>
                            <input type="text" name="fname" class="form-control" placeholder="John" required>
                        </div>
                        <div>
                            <label style="display:block; font-size:11px; font-weight:600; text-transform:uppercase; color:#94a3b8; margin-bottom:6px;">Last Name</label>
                            <input type="text" name="lname" class="form-control" placeholder="Doe" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Official Email Access Channel</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="admin@crimemapping.gov" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="••••••••••••" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password Entry Matrix</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="••••••••••••" required>
                    </div>

                    <button type="submit" name="register" class="btn btn-success">Create Account</button>
                </form>

                <div class="auth-footer">
                    Identity active? <a href="index.php">LOGIN</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>