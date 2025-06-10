<?php
/**
 * Quick Password Verification Test
 * This will test if the password in your database is correct
 */

// Database credentials
$host = 'localhost';
$dbname = 'bienetre_pharma';
$username = 'bienetre_user';
$password = 'N3*WsMh),,8&gI=A';

$testPassword = 'AdminPharma123.?';

echo "<h1>Password Verification Test</h1>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✅ Database connected<br>";
    
    // Get the admin user's password hash
    $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = 'admin'");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✅ Admin user found<br>";
        echo "User ID: {$user['id']}<br>";
        echo "Username: {$user['username']}<br>";
        echo "Password hash: " . substr($user['password_hash'], 0, 60) . "...<br><br>";
        
        // Test the password
        $isValid = password_verify($testPassword, $user['password_hash']);
        
        echo "<h2>Password Test Results</h2>";
        echo "Testing password: '<strong>$testPassword</strong>'<br>";
        echo "Result: " . ($isValid ? '<span style="color: green;">✅ PASSWORD MATCHES!</span>' : '<span style="color: red;">❌ PASSWORD DOES NOT MATCH</span>') . "<br>";
        
        if (!$isValid) {
            echo "<br><h3>🔧 Fix Required</h3>";
            echo "The password hash in your database doesn't match. Let's fix it:<br>";
            
            $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
            echo "New correct hash: $newHash<br><br>";
            
            echo "<form method='POST'>";
            echo "<input type='hidden' name='fix_password' value='1'>";
            echo "<input type='hidden' name='new_hash' value='$newHash'>";
            echo "<button type='submit' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Fix Password Now</button>";
            echo "</form>";
            
            if (isset($_POST['fix_password'])) {
                $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = 'admin'");
                $result = $updateStmt->execute([$_POST['new_hash']]);
                
                if ($result) {
                    echo "<br><span style='color: green;'>✅ Password updated! Try logging in again.</span><br>";
                } else {
                    echo "<br><span style='color: red;'>❌ Failed to update password.</span><br>";
                }
            }
        } else {
            echo "<br><h3>🎉 Perfect! Your login should work</h3>";
            echo "<p>The password verification is successful. You should be able to login with:</p>";
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<strong>Username:</strong> admin<br>";
            echo "<strong>Password:</strong> AdminPharma123.?<br>";
            echo "</div>";
            
            echo "<a href='/login' target='_blank' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔗 Test Login Now</a>";
        }
        
    } else {
        echo "❌ Admin user not found in database<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>If the password test shows ✅ PASSWORD MATCHES, go to <a href='/login'>/login</a> and try logging in</li>";
echo "<li>If it shows ❌ PASSWORD DOES NOT MATCH, click the 'Fix Password Now' button above</li>";
echo "<li>Make sure you're typing exactly: <strong>AdminPharma123.?</strong> (with period and question mark)</li>";
echo "</ol>";
?>