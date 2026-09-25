<?php
require_once 'conn.php';

// 1. Force drop the old table layout if it exists to clean out old column names
mysqli_query($conn, "DROP TABLE IF EXISTS users");

// 2. Build the precise column matrix expected by your login/signup forms
$table_query = "CREATE TABLE users (
    uid INT AUTO_INCREMENT PRIMARY KEY,
    fname VARCHAR(50) NOT NULL,
    lname VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    type VARCHAR(20) DEFAULT 'admin'
)";

if (mysqli_query($conn, $table_query)) {
    // 3. Generate a fresh, securely hashed default password
    $default_email = "admin@crimemapping.gov";
    $default_password = password_hash("admin123", PASSWORD_BCRYPT);

    $insert_query = "INSERT INTO users (fname, lname, email, password, type) 
                     VALUES ('System', 'Admin', '$default_email', '$default_password', 'admin')";

    if (mysqli_query($conn, $insert_query)) {
        echo "<h2 style='color: green; font-family: sans-serif;'>✅ Admin Table Rebuilt & Repaired successfully!</h2>";
        echo "<p>The old conflicting table structure inside database <strong>'schema'</strong> was removed.</p>";
        echo "<p>You can now go to <a href='login.php'>login.php</a> and sign in with:</p>";
        echo "<ul><li><strong>Email:</strong> admin@crimemapping.gov</li><li><strong>Password:</strong> admin123</li></ul>";
    } else {
        echo "<h2 style='color: red;'>❌ Error inserting admin record: " . mysqli_error($conn) . "</h2>";
    }
} else {
    echo "<h2 style='color: red;'>❌ Error constructing new users table matrix: " . mysqli_error($conn) . "</h2>";
}
?>