<?php
require_once 'conn.php';

if (!isset($_SESSION['registration_success_token'])) {
    header("Location: index.php");
    exit();
}

$token = $_SESSION['registration_success_token'];
// Clear the temporary token so it can't be refreshed maliciously
unset($_SESSION['registration_success_token']);

// 🌟 FIX: Swapped out the deprecated Google API for the highly reliable, ultra-fast GoQR API 🌟
$stable_qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($token) . "&ecc=M";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Registration Successful - Get Token</title>
    <link rel="stylesheet" href="login.css">
</head>
<body style="background: #0f172a; display: flex; align-items: center; justify-content: center; min-height: 100vh; font-family: 'Inter', sans-serif;">
    <div style="background: #1e293b; padding: 40px; border-radius: 8px; border: 1px solid #334155; text-align: center; max-width: 450px; width:100%;">
        <h2 style="color: #22c55e; margin-bottom: 8px; font-size: 24px;">Account Created Successfully!</h2>
        <p style="color: #94a3b8; font-size: 14px; margin-bottom: 24px;">Your cryptographic access key has been initialized. Save or screenshot the QR matrix below for instant authentication.</p>

        <div style="background: #ffffff; padding: 15px; display: inline-block; border-radius: 8px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
            <img src="<?php echo $stable_qr_api_url; ?>" alt="Your Authentication QR Token" style="display: block; width: 250px; height: 250px;">
        </div>

        <div>
            <a href="index.php" style="display: block; width: 100%; padding: 12px; background: #1982fc; border: none; color: white; font-weight: bold; border-radius: 6px; text-decoration: none; font-size:14px; transition: background 0.2s;">Proceed to Login Station</a>
        </div>
    </div>
</body>
</html>