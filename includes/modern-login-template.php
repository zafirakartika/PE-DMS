<?php
/**
 * MODERN LOGIN PAGE TEMPLATE
 * Replace the existing login.php with this modern design
 */
require_once '../config/config.php';

if (isLoggedIn()) {
    redirect('../admin/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT id, full_name, password, role, last_login FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        
        // Update last login
        $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $updateStmt->execute([$user['id']]);

        redirect('../admin/dashboard.php');
    } else {
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="icon" type="image/x-icon" href="../assets/images/daihatsu-logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/modern-style.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
        }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 1rem;
        }

        .login-box {
            background: white;
            border-radius: 12px;
            padding: 3rem 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .login-logo {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.75rem;
            margin: 0 auto 1rem;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .login-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
        }

        .login-subtitle {
            font-size: 0.95rem;
            color: #475569;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 0.95rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.5rem;
            letter-spacing: 0.2px;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            color: #0f172a;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: inherit;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3b82f6;
            background: #eff6ff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-group input::placeholder {
            color: #94a3b8;
        }

        .remember-forgot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            font-size: 0.875rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #3b82f6;
        }

        .forgot-password {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .forgot-password:hover {
            color: #1e40af;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            background: #fef2f2;
            color: #7f1d1d;
            border-left: 4px solid #ef4444;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-btn {
            width: 100%;
            padding: 0.75rem 1rem;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            letter-spacing: 0.3px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
        }

        .login-btn:active {
            transform: scale(0.98);
        }

        .login-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
            color: #475569;
            font-size: 0.875rem;
        }

        .login-footer a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .login-footer a:hover {
            color: #1e40af;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-box">
            <div class="login-header">
                <div class="login-logo">
                    <i class="fas fa-file-archive"></i>
                </div>
                <h1 class="login-title">Welcome Back</h1>
                <p class="login-subtitle">Sign in to your account</p>
            </div>

            <?php if ($error): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="login-form">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="you@example.com" 
                        required 
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Enter your password" 
                        required
                    >
                </div>

                <div class="remember-forgot">
                    <label class="remember-me">
                        <input type="checkbox" name="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="#" class="forgot-password">Forgot password?</a>
                </div>

                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>

            <div class="login-footer">
                Don't have an account? <a href="register.php">Create one</a>
            </div>
        </div>
    </div>
</body>
</html>
