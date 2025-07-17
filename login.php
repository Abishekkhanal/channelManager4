<?php
require_once 'config/database.php';

// Redirect if already logged in
if (isAdminLoggedIn()) {
    header('Location: admin/dashboard.php');
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error_message = "Please fill in all fields.";
    } else {
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("SELECT id, name, email, password FROM admins WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() === 1) {
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($password, $admin['password'])) {
                    // Login successful
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_name'] = $admin['name'];
                    $_SESSION['admin_email'] = $admin['email'];
                    
                    header('Location: admin/dashboard.php');
                    exit();
                } else {
                    $error_message = "Invalid email or password.";
                }
            } else {
                $error_message = "Invalid email or password.";
            }
        } catch(PDOException $e) {
            $error_message = "Database error. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Grand Hotel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            color: #2c3e50;
            font-size: 2rem;
            margin-bottom: 5px;
        }

        .logo p {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #2c3e50;
            font-weight: bold;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #2980b9;
        }

        .alert {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .links {
            text-align: center;
            margin-top: 20px;
        }

        .links a {
            color: #3498db;
            text-decoration: none;
            margin: 0 10px;
        }

        .links a:hover {
            text-decoration: underline;
        }

        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .remember-me input {
            margin-right: 8px;
        }

        .remember-me label {
            color: #7f8c8d;
            font-weight: normal;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>Grand Hotel</h1>
            <p>Admin Login</p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Remember me</label>
            </div>

            <button type="submit" class="btn">Login</button>
        </form>

        <div class="links">
            <a href="signup.php">Create Admin Account</a>
            <a href="index.php">Back to Hotel</a>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
                return false;
            }
        });
    </script>
</body>
</html>