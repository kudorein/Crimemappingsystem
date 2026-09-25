<?php
require_once 'conn.php';

if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit();
}

$error_msg = "";

if (isset($_POST["submit"])) {
    $email = mysqli_real_escape_string($conn, trim($_POST["email"]));
    $password = $_POST["password"];

    $query = "SELECT * FROM users WHERE email = '$email' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user;
            header("Location: dashboard.php");
            exit();
        } else {
            $error_msg = "Access Denied: Invalid passphrase validation match.";
        }
    } else {
        $error_msg = "No administrator account matching that identity found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CrimeMapping - Login Gate</title>
    <link rel="stylesheet" href="login.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-brand-side">
            <div class="brand-overlay"></div>
            <div class="brand-content">
                <div class="brand-badge">CRIME MAPPING SYSTEM</div>
                <h1>Operational Mapping Command Node</h1>
                <p>Access tactical spatial intelligence matrices, incident trend visualizations, and localized threat profile registries.</p>
            </div>
        </div>

        <div class="auth-form-side">
            <div class="auth-box">
                <div class="auth-header">
                    <h2>Operational Entry</h2>
                    <p>Enter administrative verification keys or upload QR credentials.</p>
                </div>

                <?php if (!empty($error_msg)): ?>
                    <div style="color: #ef4444; background: rgba(239, 68, 68, 0.1); padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; border: 1px solid rgba(239, 68, 68, 0.2);">
                        <?php echo $error_msg; ?>
                    </div>
                <?php endif; ?>

                <div id="qr-error-box" style="display: none; color: #ef4444; background: rgba(239, 68, 68, 0.1); padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; border: 1px solid rgba(239, 68, 68, 0.2);">
                </div>

                <form action="login.php" method="POST" autocomplete="off">
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 6px; font-size: 11px; font-weight:600; text-transform:uppercase; color: #94a3b8;">Email Address</label>
                        <input type="email" name="email" placeholder="admin@crimemapping.gov" style="width: 100%; padding: 12px; border-radius: 6px; border: 1px solid #334155; background: #1e293b; color: #fff; font-size:14px;" required>
                    </div>

                    <div style="margin-bottom: 24px;">
                        <label style="display: block; margin-bottom: 6px; font-size: 11px; font-weight:600; text-transform:uppercase; color: #94a3b8;">Password</label>
                        <input type="password" name="password" placeholder="••••••••••••" style="width: 100%; padding: 12px; border-radius: 6px; border: 1px solid #334155; background: #1e293b; color: #fff; font-size:14px;" required>
                    </div>

                    <button type="submit" name="submit" style="width: 100%; padding: 12px; background: #1982fc; border: none; color: white; font-weight: bold; border-radius: 6px; cursor: pointer; font-size:14px; transition: background 0.2s;">LOG IN</button>
                </form>

                <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #334155; text-align: center;">
                    <label style="display: block; margin-bottom: 12px; font-size: 11px; font-weight:600; text-transform:uppercase; color: #94a3b8; letter-spacing: 0.05em;">
                        Tactical QR Token Authentication
                    </label>
                    <div style="position: relative; display: inline-block; width: 100%;">
                        <input type="file" id="qr-input-file" accept="image/*" style="position: absolute; left: 0; top: 0; opacity: 0; width: 100%; height: 100%; cursor: pointer;">
                        <div style="padding: 12px; background: #0f172a; border: 1px dashed #334155; color: #38bdf8; font-weight: 500; font-size: 13px; border-radius: 6px; transition: border-color 0.2s;">
                            📁 Upload QR Image Matrix
                        </div>
                    </div>
                </div>

                <div style="margin-top: 24px; text-align: center; font-size: 14px; color: #94a3b8;">
                    Account non-existent? <a href="signup.php" style="color: #1982fc; text-decoration: none; font-weight: 500;">Register here</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('qr-input-file').addEventListener('change', function(e) {
            const errorBox = document.getElementById('qr-error-box');
            errorBox.style.display = 'none'; // Clear previous errors

            if (e.target.files.length === 0) {
                return;
            }

            const imageFile = e.target.files[0];
            const html5QrCode = new Html5Qrcode("qr-input-file"); // Dummy ID node context

            // Process image upload evaluation
            html5QrCode.scanFile(imageFile, true)
                .then(decodedText => {
                    // Check if the decrypted URL matches our expected entry script format
                    if (decodedText.includes("qr_login.php?token=")) {
                        window.location.href = decodedText;
                    } else {
                        errorBox.textContent = "Security Notice: Matrix payload structure unrecognized.";
                        errorBox.style.display = 'block';
                    }
                })
                .catch(err => {
                    errorBox.textContent = "Error: Failed to clear or parse valid QR graphic layout.";
                    errorBox.style.display = 'block';
                    console.error(err);
                });
        });
    </script>
</body>
</html>