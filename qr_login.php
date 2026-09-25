<?php
require_once 'conn.php';

if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit();
}

$error_msg = "";

if (isset($_GET['token'])) {
    $raw_input = trim($_GET['token']);
    
    // Parse match fallback: If a full URL was somehow passed, extract only the query parameter value
    if (filter_var($raw_input, FILTER_VALIDATE_URL)) {
        $url_components = parse_url($raw_input);
        if (isset($url_components['query'])) {
            parse_str($url_components['query'], $query_params);
            if (isset($query_params['token'])) {
                $raw_input = $query_params['token'];
            }
        }
    }

    $qr_token = mysqli_real_escape_string($conn, $raw_input);

    // Query validation gate matches unique user assigned token
    $query = "SELECT * FROM users WHERE qr_token = '$qr_token' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // Inject operational administrative parameters into local session array
        $_SESSION['user'] = $user;
        
        header("Location: dashboard.php");
        exit();
    } else {
        $error_msg = "Access Denied: Token mismatch. Expected token: " . htmlspecialchars($qr_token);
    }
} else {
    $error_msg = "Bad Request: Missing cryptographic parameters.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CrimeMapping - QR Authentication Gate</title>
    <link rel="stylesheet" href="login.css">
</head>
<body style="background: #0f172a; display: flex; align-items: center; justify-content: center; height: 100vh; font-family: 'Inter', sans-serif;">
    <div style="background: #1e293b; padding: 32px; border-radius: 8px; border: 1px solid #334155; text-align: center; max-width: 450px; width:100%;">
        <h2 style="color: #fff; margin-bottom: 12px; font-size: 20px;">QR Authentication Gateway</h2>
        
        <div style="color: #ef4444; background: rgba(239, 68, 68, 0.1); padding: 12px; border-radius: 6px; font-size: 13px; border: 1px solid rgba(239, 68, 68, 0.2); word-wrap: break-word; font-family: monospace;">
            <?php echo $error_msg; ?>
        </div>
        
        <a href="index.php" style="color: #1982fc; text-decoration: none; font-size: 14px; display: inline-block; margin-top: 20px; font-weight: 500;">Return to standard terminal login</a>
    </div>
</body>
</html>