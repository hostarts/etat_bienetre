<?php
/**
 * Comprehensive Login Debug
 * This will trace every step of the login process to find the exact issue
 */

$rootPath = dirname(__DIR__);

// Load environment
$envFile = $rootPath . '/.env';
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    $lines = explode("\n", $envContent);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, '"\'');
        }
    }
}

// Load classes
require_once $rootPath . '/app/Helpers/Database.php';
require_once $rootPath . '/app/Helpers/Session.php';
require_once $rootPath . '/app/Helpers/Security.php';
require_once $rootPath . '/app/Middleware/CSRFMiddleware.php';
require_once $rootPath . '/app/Middleware/AuthMiddleware.php';

// Start session
Session::start();

echo "<h1>Comprehensive Login Debug</h1>";

// If this is a login attempt, process it step by step
if (isset($_POST['debug_login'])) {
    echo "<h2>🔍 DEBUGGING LOGIN PROCESS STEP BY STEP</h2>";
    
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>Login Attempt Details:</h3>";
    echo "Username: '$username'<br>";
    echo "Password: '$password'<br>";
    echo "Session ID: " . session_id() . "<br>";
    echo "Request Method: " . $_SERVER['REQUEST_METHOD'] . "<br>";
    echo "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'None') . "<br>";
    echo "IP Address: " . ($_SERVER['REMOTE_ADDR'] ?? 'None') . "<br>";
    echo "</div>";
    
    try {
        echo "<h3>Step 1: Validate Input</h3>";
        if (empty($username) || empty($password)) {
            throw new Exception("Username or password is empty");
        }
        echo "✅ Input validation passed<br>";
        
        echo "<h3>Step 2: Database Connection</h3>";
        $db = Database::getInstance();
        echo "✅ Database connected<br>";
        
        echo "<h3>Step 3: User Lookup</h3>";
        $stmt = $db->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            throw new Exception("User not found or not active");
        }
        echo "✅ User found: ID={$user['id']}, Username={$user['username']}, Role={$user['role']}<br>";
        
        echo "<h3>Step 4: Account Lock Check</h3>";
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            throw new Exception("Account is locked until " . $user['locked_until']);
        }
        echo "✅ Account not locked<br>";
        
        echo "<h3>Step 5: Password Verification</h3>";
        $passwordValid = password_verify($password, $user['password_hash']);
        if (!$passwordValid) {
            throw new Exception("Password verification failed");
        }
        echo "✅ Password verification successful<br>";
        
        echo "<h3>Step 6: Reset Login Attempts</h3>";
        $stmt = $db->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        echo "✅ Login attempts reset and last_login updated<br>";
        
        echo "<h3>Step 7: Create Database Session</h3>";
        try {
            $sessionId = session_id();
            $expiresAt = date('Y-m-d H:i:s', time() + (int)($_ENV['SESSION_LIFETIME'] ?? 7200));
            
            $stmt = $db->prepare("
                INSERT INTO user_sessions (id, user_id, ip_address, user_agent, expires_at) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                expires_at = VALUES(expires_at), 
                user_id = VALUES(user_id)
            ");
            $stmt->execute([
                $sessionId,
                $user['id'],
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $expiresAt
            ]);
            echo "✅ Database session created/updated<br>";
        } catch (Exception $e) {
            echo "⚠️ Database session creation failed: " . $e->getMessage() . " (continuing anyway)<br>";
        }
        
        echo "<h3>Step 8: Set User in PHP Session</h3>";
        Session::setUser([
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'role' => $user['role']
        ]);
        echo "✅ User set in PHP session<br>";
        
        echo "<h3>Step 9: Verify Session Data</h3>";
        $sessionUser = Session::getUser();
        if ($sessionUser) {
            echo "✅ Session user retrieved: {$sessionUser['username']}<br>";
        } else {
            throw new Exception("Failed to retrieve user from session after setting");
        }
        
        echo "<h3>Step 10: Test AuthMiddleware</h3>";
        $authResult = AuthMiddleware::authenticate();
        echo "AuthMiddleware::authenticate(): " . ($authResult ? '✅ Success' : '❌ Failed') . "<br>";
        
        if (!$authResult) {
            echo "🔍 <strong>ISSUE: AuthMiddleware still failing after successful login setup</strong><br>";
            
            // Debug AuthMiddleware step by step
            echo "<h4>AuthMiddleware Debug:</h4>";
            
            // Test each validation step
            $sessionCheck = Session::getUser();
            echo "- Session::getUser(): " . ($sessionCheck ? '✅ Found' : '❌ Not found') . "<br>";
            
            if (method_exists('Session', 'validateFingerprint')) {
                $fingerprintCheck = Session::validateFingerprint();
                echo "- Session::validateFingerprint(): " . ($fingerprintCheck ? '✅ Valid' : '❌ Invalid') . "<br>";
            }
            
            if (method_exists('Session', 'checkTimeout')) {
                $timeoutCheck = Session::checkTimeout();
                echo "- Session::checkTimeout(): " . ($timeoutCheck ? '✅ Valid' : '❌ Expired') . "<br>";
            }
            
            // Test database session validation
            try {
                $stmt = $db->prepare("
                    SELECT us.*, u.is_active 
                    FROM user_sessions us
                    JOIN users u ON us.user_id = u.id
                    WHERE us.id = ? AND us.expires_at > NOW() AND u.is_active = 1
                ");
                $stmt->execute([session_id()]);
                $dbSession = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "- Database session validation: " . ($dbSession ? '✅ Valid' : '❌ Invalid') . "<br>";
            } catch (Exception $e) {
                echo "- Database session validation: ❌ Error: " . $e->getMessage() . "<br>";
            }
        }
        
        echo "<h3>Step 11: Set Success Flash Message</h3>";
        Session::setFlash('success', 'Login successful! Welcome ' . $user['full_name']);
        echo "✅ Success flash message set<br>";
        
        echo "<div style='background: #d4edda; padding: 20px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px 0;'>";
        if ($authResult) {
            echo "<h3>🎉 LOGIN PROCESS COMPLETED SUCCESSFULLY!</h3>";
            echo "<p>All steps passed. You should now be logged in.</p>";
            echo "<p><a href='/dashboard' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Test Dashboard Access</a></p>";
        } else {
            echo "<h3>⚠️ LOGIN PROCESS COMPLETED BUT AUTHENTICATION STILL FAILS</h3>";
            echo "<p>The login steps all succeeded, but AuthMiddleware::authenticate() still returns false.</p>";
            echo "<p>This indicates an issue in the AuthMiddleware logic itself.</p>";
        }
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 20px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>❌ LOGIN FAILED</h3>";
        echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>At step:</strong> " . (isset($step) ? $step : 'Unknown') . "</p>";
        echo "</div>";
        
        // Log the failed attempt
        if (isset($user)) {
            $stmt = $db->prepare("UPDATE users SET login_attempts = login_attempts + 1 WHERE id = ?");
            $stmt->execute([$user['id']]);
        }
    }
}

echo "<h2>Current Authentication Status</h2>";
$currentUser = Session::getUser();
if ($currentUser) {
    echo "✅ <strong>You are currently logged in as:</strong> {$currentUser['full_name']} ({$currentUser['username']})<br>";
    echo "User ID: {$currentUser['id']}<br>";
    echo "Role: {$currentUser['role']}<br>";
    
    $authStatus = AuthMiddleware::authenticate();
    echo "AuthMiddleware status: " . ($authStatus ? '✅ Authenticated' : '❌ Not authenticated') . "<br>";
    
    if ($authStatus) {
        echo "<p><a href='/dashboard' target='_blank' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Go to Dashboard</a></p>";
    } else {
        echo "<p style='color: red;'>⚠️ Session exists but AuthMiddleware fails - this is the core issue</p>";
    }
} else {
    echo "❌ <strong>You are not currently logged in</strong><br>";
}

echo "<h2>Test Login Form</h2>";
echo "<p>This form will debug every step of the login process:</p>";

echo "<form method='POST' action='' style='background: #f8f9fa; padding: 20px; border-radius: 5px; border: 1px solid #dee2e6;'>";
echo "<input type='hidden' name='debug_login' value='1'>";
$csrfToken = CSRFMiddleware::generateToken();
echo "<input type='hidden' name='csrf_token' value='$csrfToken'>";

echo "<div style='margin-bottom: 15px;'>";
echo "<label for='username' style='display: block; margin-bottom: 5px; font-weight: bold;'>Username:</label>";
echo "<input type='text' id='username' name='username' value='admin' style='width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;'>";
echo "</div>";

echo "<div style='margin-bottom: 15px;'>";
echo "<label for='password' style='display: block; margin-bottom: 5px; font-weight: bold;'>Password:</label>";
echo "<input type='password' id='password' name='password' value='AdminPharma123.?' style='width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;'>";
echo "</div>";

echo "<button type='submit' style='background: #007bff; color: white; padding: 12px 24px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; width: 100%;'>🔍 Debug Login Process</button>";
echo "</form>";

echo "<h2>Check Current Session Data</h2>";
echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px; overflow: auto;'>";
echo "Session ID: " . session_id() . "\n";
echo "Session Data:\n";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Check Database Sessions</h2>";
try {
    $db = Database::getInstance();
    $stmt = $db->query("SELECT id, user_id, ip_address, created_at, expires_at FROM user_sessions ORDER BY created_at DESC LIMIT 5");
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($sessions)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Session ID</th><th>User ID</th><th>IP</th><th>Created</th><th>Expires</th><th>Status</th></tr>";
        foreach ($sessions as $sess) {
            $isExpired = strtotime($sess['expires_at']) < time();
            $isCurrent = $sess['id'] === session_id();
            $status = $isCurrent ? 'CURRENT' : ($isExpired ? 'EXPIRED' : 'ACTIVE');
            $style = $isCurrent ? 'background: #d4edda;' : ($isExpired ? 'background: #f8d7da;' : '');
            
            echo "<tr style='$style'>";
            echo "<td>" . substr($sess['id'], 0, 8) . "...</td>";
            echo "<td>{$sess['user_id']}</td>";
            echo "<td>{$sess['ip_address']}</td>";
            echo "<td>{$sess['created_at']}</td>";
            echo "<td>{$sess['expires_at']}</td>";
            echo "<td>$status</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No database sessions found.";
    }
} catch (Exception $e) {
    echo "Error checking database sessions: " . $e->getMessage();
}

echo "<hr>";
echo "<h2>Quick Actions</h2>";
echo "<ul>";
echo "<li><a href='/login' target='_blank'>Test Normal Login Page</a></li>";
echo "<li><a href='/' target='_blank'>Test Dashboard (should redirect if not logged in)</a></li>";
echo "<li><a href='/clients' target='_blank'>Test Clients Page</a></li>";
echo "</ul>";

echo "<h2>What to Look For</h2>";
echo "<ul>";
echo "<li><strong>If all steps pass but AuthMiddleware fails:</strong> Issue is in AuthMiddleware logic</li>";
echo "<li><strong>If login steps fail:</strong> Issue is in login process (password, database, etc.)</li>";
echo "<li><strong>If session data doesn't persist:</strong> Session configuration issue</li>";
echo "<li><strong>If redirect doesn't work:</strong> AuthController redirect issue</li>";
echo "</ul>";
?>