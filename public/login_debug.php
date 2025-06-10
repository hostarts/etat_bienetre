<?php
/**
 * Fixed Login Debug Script
 * This will help us identify what's causing the login error
 */

// Fix the path issue - go up one directory from public/
$rootPath = dirname(__DIR__);

echo "<h1>Login Debug Script</h1>";
echo "<p><strong>Root Path:</strong> $rootPath</p>";
echo "<p><strong>Current Directory:</strong> " . getcwd() . "</p>";

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
    echo "✅ Environment loaded<br>";
} else {
    echo "❌ Environment file not found at: $envFile<br>";
}

// Load required classes with correct paths
echo "<h2>Loading Helper Classes</h2>";
$helpers = ['Security', 'Database', 'Session'];
foreach ($helpers as $helper) {
    $file = $rootPath . '/app/Helpers/' . $helper . '.php';
    echo "Looking for $helper at: $file<br>";
    
    if (file_exists($file)) {
        require_once $file;
        echo "✅ Loaded: $helper<br>";
    } else {
        echo "❌ Missing: $helper<br>";
    }
}

echo "<h2>1. Database Connection Test</h2>";
if (class_exists('Database')) {
    try {
        $db = Database::getInstance();
        echo "✅ Database connection successful<br>";
        
        // Test if admin user exists
        $stmt = $db->prepare("SELECT id, username, email, password_hash, full_name, role, is_active FROM users WHERE username = ?");
        $stmt->execute(['admin']);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "✅ Admin user found in database<br>";
            echo "<strong>User Details:</strong><br>";
            echo "ID: {$user['id']}<br>";
            echo "Username: {$user['username']}<br>";
            echo "Email: {$user['email']}<br>";
            echo "Full Name: {$user['full_name']}<br>";
            echo "Role: {$user['role']}<br>";
            echo "Active: " . ($user['is_active'] ? 'Yes' : 'No') . "<br>";
            echo "Password Hash: " . substr($user['password_hash'], 0, 30) . "...<br>";
        } else {
            echo "❌ Admin user NOT found in database<br>";
            
            // Let's check what users do exist
            $stmt = $db->query("SELECT username, email, role FROM users LIMIT 5");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($users)) {
                echo "<strong>Existing users:</strong><br>";
                foreach ($users as $u) {
                    echo "- {$u['username']} ({$u['email']}) - {$u['role']}<br>";
                }
            } else {
                echo "No users found in database<br>";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Database error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Database class not loaded<br>";
}

echo "<h2>2. Password Verification Test</h2>";
$testPassword = 'AdminPharma123.?';

if (isset($user) && $user) {
    // Test password verification using built-in PHP function
    $isValid = password_verify($testPassword, $user['password_hash']);
    echo "Testing password: '$testPassword'<br>";
    echo "Against hash: " . substr($user['password_hash'], 0, 50) . "...<br>";
    echo "Result: " . ($isValid ? '✅ Password matches' : '❌ Password does NOT match') . "<br>";
    
    if (!$isValid) {
        // Generate a new hash and show what it should be
        $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
        echo "<br><strong>Suggested fix:</strong><br>";
        echo "Current hash: {$user['password_hash']}<br>";
        echo "Correct hash should be: $newHash<br>";
        echo "Run this SQL to fix:<br>";
        echo "<pre>UPDATE users SET password_hash = '$newHash' WHERE username = 'admin';</pre>";
    }
} else {
    echo "Cannot test password - user not found<br>";
    
    if (class_exists('Database')) {
        echo "<br><strong>Let's create the admin user:</strong><br>";
        $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
        echo "Run this SQL to create admin user:<br>";
        echo "<pre>";
        echo "INSERT INTO users (username, email, password_hash, full_name, role, is_active) VALUES ";
        echo "('admin', 'admin@bienetre-pharma.dz', '$newHash', 'Administrateur', 'admin', 1);";
        echo "</pre>";
    }
}

echo "<h2>3. File Structure Check</h2>";
$requiredFiles = [
    'AuthController' => $rootPath . '/app/Controllers/AuthController.php',
    'Login View' => $rootPath . '/app/Views/auth/login.php',
    'AuthMiddleware' => $rootPath . '/app/Middleware/AuthMiddleware.php'
];

foreach ($requiredFiles as $name => $path) {
    if (file_exists($path)) {
        echo "✅ $name exists<br>";
    } else {
        echo "❌ $name missing at: $path<br>";
    }
}

echo "<h2>4. Quick Password Fix</h2>";
if (class_exists('Database') && isset($user)) {
    echo "<form method='POST' action=''>
        <input type='hidden' name='fix_password' value='1'>
        <p><button type='submit'>Fix Admin Password Now</button></p>
    </form>";
    
    if (isset($_POST['fix_password'])) {
        try {
            $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE username = 'admin'");
            $result = $stmt->execute([$newHash]);
            
            if ($result) {
                echo "✅ Password updated successfully!<br>";
                echo "You can now login with: admin / AdminPharma123.?<br>";
            } else {
                echo "❌ Failed to update password<br>";
            }
        } catch (Exception $e) {
            echo "❌ Error updating password: " . $e->getMessage() . "<br>";
        }
    }
}

echo "<h2>5. Create Admin User (if missing)</h2>";
if (class_exists('Database') && (!isset($user) || !$user)) {
    echo "<form method='POST' action=''>
        <input type='hidden' name='create_admin' value='1'>
        <p><button type='submit'>Create Admin User</button></p>
    </form>";
    
    if (isset($_POST['create_admin'])) {
        try {
            $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
            $stmt = $db->prepare("
                INSERT INTO users (username, email, password_hash, full_name, role, is_active) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $result = $stmt->execute([
                'admin',
                'admin@bienetre-pharma.dz',
                $newHash,
                'Administrateur',
                'admin',
                1
            ]);
            
            if ($result) {
                echo "✅ Admin user created successfully!<br>";
                echo "Username: admin<br>";
                echo "Password: AdminPharma123.?<br>";
            } else {
                echo "❌ Failed to create admin user<br>";
            }
        } catch (Exception $e) {
            echo "❌ Error creating admin user: " . $e->getMessage() . "<br>";
        }
    }
}

echo "<h2>6. Test Login Process</h2>";
echo "<p>After fixing the password, test login here:</p>";
echo "<p><a href='/login' target='_blank'>🔗 Open Login Page</a></p>";

echo "<h2>7. Manual SQL Commands</h2>";
echo "<p>If the automatic fixes don't work, run these SQL commands manually:</p>";
echo "<pre>";
echo "-- Check if admin user exists:\n";
echo "SELECT * FROM users WHERE username = 'admin';\n\n";

echo "-- Update admin password:\n";
$manualHash = password_hash($testPassword, PASSWORD_DEFAULT);
echo "UPDATE users SET password_hash = '$manualHash' WHERE username = 'admin';\n\n";

echo "-- Or create new admin user:\n";
echo "INSERT INTO users (username, email, password_hash, full_name, role, is_active) \n";
echo "VALUES ('admin', 'admin@bienetre-pharma.dz', '$manualHash', 'Administrateur', 'admin', 1);";
echo "</pre>";

echo "<hr>";
echo "<p><strong>Summary:</strong></p>";
echo "<ul>";
echo "<li>Username: <strong>admin</strong></li>";
echo "<li>Password: <strong>AdminPharma123.?</strong></li>";
echo "<li>Make sure to use the exact password with period and question mark</li>";
echo "</ul>";
?>