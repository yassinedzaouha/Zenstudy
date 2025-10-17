<?php
// login.php - Enhanced login page with better gender support
session_start();
require_once "db.php";
require_once "includes/gender-helper.php";

$email = $password = "";
$errors = [];
$login_attempts = $_SESSION['login_attempts'] ?? 0;

// Protection against repeated login attempts
if ($login_attempts >= 5) {
    $last_attempt = $_SESSION['last_attempt'] ?? 0;
    if (time() - $last_attempt < 300) { // 5 minutes
        $errors[] = "تم تجاوز عدد المحاولات المسموح. حاول مرة أخرى بعد 5 دقائق.";
    } else {
        $_SESSION['login_attempts'] = 0;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && empty($errors)) {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($email) || empty($password)) {
        $errors[] = "المرجو ملء جميع الخانات.";
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, name, gender, password FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_gender'] = $user['gender'];
                    $_SESSION['login_attempts'] = 0;
                    
                    // Redirect based on gender
                    header("Location: index.php");
                    exit();
                } else {
                    $errors[] = "كلمة المرور غير صحيحة.";
                    $_SESSION['login_attempts'] = $login_attempts + 1;
                    $_SESSION['last_attempt'] = time();
                }
            } else {
                $errors[] = "الحساب غير موجود.";
                $_SESSION['login_attempts'] = $login_attempts + 1;
                $_SESSION['last_attempt'] = time();
            }
            $stmt->close();
        } catch (Exception $e) {
            $errors[] = "حدث خطأ في النظام. حاول مرة أخرى.";
        }
    }
}

// Get default colors
$colors = GenderConfig::getColors('default');
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول | ZenStudy</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: <?= $colors['bg'] ?>;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 400px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
        }

        .logo {
            width: 60px;
            height: 60px;
            background: <?= $colors['gradient'] ?>;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            position: relative;
            box-shadow: 0 8px 20px <?= $colors['shadow'] ?>;
        }

        .logo::after {
            content: "📚";
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 20px;
            height: 20px;
            background: <?= $colors['gradient'] ?>;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
        }

        .logo-text {
            margin-right: 15px;
        }

        .logo-title {
            font-size: 28px;
            font-weight: bold;
            background: <?= $colors['gradient'] ?>;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo-subtitle {
            font-size: 12px;
            color: #6b7280;
            margin-top: -5px;
        }

        .welcome-text {
            text-align: center;
            margin-bottom: 30px;
        }

        .welcome-title {
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .welcome-subtitle {
            font-size: 14px;
            color: #6b7280;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .input-container {
            position: relative;
        }

        .input-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 16px;
        }

        .form-input {
            width: 100%;
            height: 48px;
            padding: 0 45px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.2s ease;
            background: #fff;
        }

        .form-input:focus {
            outline: none;
            border-color: <?= $colors['primary'] ?>;
            box-shadow: 0 0 0 3px <?= $colors['primary'] ?>1a;
        }

        .toggle-password {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            font-size: 16px;
            padding: 4px;
            transition: color 0.2s ease;
        }

        .toggle-password:hover {
            color: <?= $colors['primary'] ?>;
        }

        .submit-btn {
            width: 100%;
            height: 48px;
            background: <?= $colors['gradient'] ?>;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px <?= $colors['shadow'] ?>;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .submit-btn:hover {
            filter: brightness(1.1);
            box-shadow: 0 6px 16px <?= $colors['shadow'] ?>;
            transform: translateY(-1px);
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .errors {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
        }

        .error-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .error-item {
            color: #dc2626;
            font-size: 14px;
            display: flex;
            align-items: center;
            margin-bottom: 4px;
        }

        .error-item:before {
            content: "⚠️";
            margin-left: 8px;
        }

        .footer-links {
            text-align: center;
            margin-top: 24px;
        }

        .footer-link {
            color: #6b7280;
            font-size: 14px;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .footer-link.primary {
            color: <?= $colors['primary'] ?>;
            font-weight: 500;
        }

        .footer-link:hover {
            text-decoration: underline;
        }

        .attempts-warning {
            background: #fef3cd;
            border: 1px solid #fde68a;
            color: #92400e;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            text-align: center;
            font-size: 14px;
        }

        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
                margin: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Logo -->
        <div class="logo-container">
            <div class="logo">🧘‍♀️</div>
            <div class="logo-text">
                <div class="logo-title">ZenStudy</div>
                <div class="logo-subtitle">دراسة بسكينة</div>
            </div>
        </div>

        <!-- Welcome Text -->
        <div class="welcome-text">
            <h2 class="welcome-title">مرحباً بعودتك</h2>
            <p class="welcome-subtitle">سجل دخولك للمتابعة</p>
        </div>

        <!-- Attempts Warning -->
        <?php if ($login_attempts >= 3 && $login_attempts < 5): ?>
        <div class="attempts-warning">
            تحذير: تبقى لك <?= 5 - $login_attempts ?> محاولات قبل قفل الحساب مؤقتاً
        </div>
        <?php endif; ?>

        <!-- Errors -->
        <?php if (!empty($errors)): ?>
        <div class="errors">
            <ul class="error-list">
                <?php foreach ($errors as $error): ?>
                <li class="error-item"><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="">
            <div class="form-group">
                <div class="input-container">
                    <span class="input-icon">📧</span>
                    <input 
                        type="email" 
                        name="email" 
                        class="form-input"
                        placeholder="أدخل بريدك الإلكتروني"
                        value="<?= htmlspecialchars($email) ?>"
                        required
                        <?= $login_attempts >= 5 ? 'disabled' : '' ?>
                    >
                </div>
            </div>

            <div class="form-group">
                <div class="input-container">
                    <span class="input-icon">🔒</span>
                    <input 
                        type="password" 
                        name="password" 
                        id="password"
                        class="form-input"
                        placeholder="أدخل كلمة المرور"
                        required
                        <?= $login_attempts >= 5 ? 'disabled' : '' ?>
                    >
                    <button type="button" class="toggle-password" onclick="togglePassword()">👁️</button>
                </div>
            </div>

            <button type="submit" class="submit-btn" <?= $login_attempts >= 5 ? 'disabled' : '' ?>>
                <span>➡️</span>
                تسجيل الدخول
            </button>
        </form>

        <!-- Footer Links -->
        <div class="footer-links">
            <span>ليس لديك حساب؟ </span>
            <a href="signup.php" class="footer-link primary">إنشاء حساب جديد</a>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.toggle-password');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }

        // Auto-focus on first empty field
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.querySelector('input[name="email"]');
            const passwordInput = document.querySelector('input[name="password"]');
            
            if (!emailInput.value) {
                emailInput.focus();
            } else {
                passwordInput.focus();
            }
        });
    </script>
</body>
</html>
