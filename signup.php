<?php
// signup.php - Enhanced signup page with better gender support
session_start();
require_once "db.php";
require_once "includes/gender-helper.php";

$name = $email = $password = $confirm_password = $gender = "";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $gender = $_POST["gender"] ?? "";

    // Validate input data
    if (empty($name)) $errors[] = "الاسم مطلوب";
    if (strlen($name) < 2) $errors[] = "الاسم يجب أن يكون حرفين على الأقل";
    if (empty($email)) $errors[] = "البريد الإلكتروني مطلوب";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "البريد الإلكتروني غير صحيح";
    if (empty($password)) $errors[] = "كلمة المرور مطلوبة";
    if (empty($gender)) $errors[] = "يجب تحديد الجنس";
    if (strlen($password) < 6) $errors[] = "كلمة المرور يجب أن تكون 6 أحرف على الأقل";
    if ($password !== $confirm_password) $errors[] = "كلمتا المرور غير متطابقتان";

    // Check password strength
    if (!empty($password)) {
        $password_strength = 0;
        if (preg_match('/[a-z]/', $password)) $password_strength++;
        if (preg_match('/[A-Z]/', $password)) $password_strength++;
        if (preg_match('/[0-9]/', $password)) $password_strength++;
        if (preg_match('/[^a-zA-Z0-9]/', $password)) $password_strength++;
        
        if ($password_strength < 2) {
            $errors[] = "كلمة المرور ضعيفة. استخدم مزيج من الأحرف والأرقام";
        }
    }

    // Check if email exists
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors[] = "البريد الإلكتروني مستخدم بالفعل";
            }
            $stmt->close();
        } catch (Exception $e) {
            $errors[] = "حدث خطأ في النظام. حاول مرة أخرى.";
        }
    }

    // Create new user
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, gender, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $gender, $hashed_password);
            
            if ($stmt->execute()) {
                $_SESSION['user_id'] = $conn->insert_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_gender'] = $gender;
                header("Location: index.php");
                exit();
            } else {
                $errors[] = "حدث خطأ أثناء إنشاء الحساب";
            }
            $stmt->close();
        } catch (Exception $e) {
            $errors[] = "حدث خطأ في النظام. حاول مرة أخرى.";
        }
    }
}

// Set colors based on selected gender
$colors = GenderConfig::getColors($gender ?: 'default');
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب | ZenStudy</title>
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
            transition: background 0.3s ease;
        }

        .container {
            width: 100%;
            max-width: 420px;
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
            transition: all 0.3s ease;
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
            transition: all 0.3s ease;
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

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
        }

        .required {
            color: #dc2626;
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

        .form-input.valid {
            border-color: #10b981;
        }

        .form-input.invalid {
            border-color: #ef4444;
        }

        .gender-container {
            display: flex;
            gap: 12px;
            margin-top: 8px;
        }

        .gender-option {
            flex: 1;
            position: relative;
        }

        .gender-input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }

        .gender-label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #fff;
            font-size: 14px;
            font-weight: 500;
        }

        .gender-input:checked + .gender-label {
            border-color: <?= $colors['primary'] ?>;
            background: <?= $colors['primary'] ?>0d;
            color: <?= $colors['primary'] ?>;
            transform: scale(1.02);
        }

        .gender-icon {
            margin-left: 8px;
            font-size: 18px;
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

        .password-strength {
            font-size: 12px;
            margin-top: 4px;
            padding: 4px 8px;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .strength-weak { 
            color: #dc2626; 
            background: #fef2f2;
        }
        .strength-medium { 
            color: #d97706; 
            background: #fef3c7;
        }
        .strength-strong { 
            color: #059669; 
            background: #d1fae5;
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

        .match-indicator {
            position: absolute;
            left: 45px;
            top: 50%;
            transform: translateY(-50%);
            color: #059669;
            font-size: 16px;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .match-indicator.show {
            opacity: 1;
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
            <div class="logo" id="logo">🧘‍♀️</div>
            <div class="logo-text">
                <div class="logo-title" id="logoTitle">ZenStudy</div>
                <div class="logo-subtitle">دراسة بسكينة</div>
            </div>
        </div>

        <!-- Welcome Text -->
        <div class="welcome-text">
            <h2 class="welcome-title">إنشاء حساب جديد</h2>
            <p class="welcome-subtitle">انضم إلى مجتمع ZenStudy</p>
        </div>

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

        <!-- Signup Form -->
        <form method="POST" action="" id="signupForm">
            <div class="form-group">
                <label class="form-label">الاسم الكامل <span class="required">*</span></label>
                <div class="input-container">
                    <span class="input-icon">👤</span>
                    <input 
                        type="text" 
                        name="name" 
                        id="name"
                        class="form-input"
                        placeholder="أدخل اسمك الكامل"
                        value="<?= htmlspecialchars($name) ?>"
                        oninput="validateName()"
                        required
                    >
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">البريد الإلكتروني <span class="required">*</span></label>
                <div class="input-container">
                    <span class="input-icon">📧</span>
                    <input 
                        type="email" 
                        name="email" 
                        id="email"
                        class="form-input"
                        placeholder="أدخل بريدك الإلكتروني"
                        value="<?= htmlspecialchars($email) ?>"
                        oninput="validateEmail()"
                        required
                    >
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">الجنس <span class="required">*</span></label>
                <div class="gender-container">
                    <div class="gender-option">
                        <input 
                            type="radio" 
                            id="male" 
                            name="gender" 
                            value="male" 
                            class="gender-input"
                            <?= $gender === 'male' ? 'checked' : '' ?>
                            onchange="changeTheme('male')"
                            required
                        >
                        <label for="male" class="gender-label">
                            <span class="gender-icon">👨</span>
                            ذكر
                        </label>
                    </div>
                    <div class="gender-option">
                        <input 
                            type="radio" 
                            id="female" 
                            name="gender" 
                            value="female" 
                            class="gender-input"
                            <?= $gender === 'female' ? 'checked' : '' ?>
                            onchange="changeTheme('female')"
                            required
                        >
                        <label for="female" class="gender-label">
                            <span class="gender-icon">👩</span>
                            أنثى
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">كلمة المرور <span class="required">*</span></label>
                <div class="input-container">
                    <span class="input-icon">🔒</span>
                    <input 
                        type="password" 
                        name="password" 
                        id="password"
                        class="form-input"
                        placeholder="أدخل كلمة المرور"
                        oninput="checkPasswordStrength()"
                        required
                    >
                    <button type="button" class="toggle-password" onclick="togglePassword('password')">👁️</button>
                </div>
                <div id="password-strength" class="password-strength"></div>
            </div>

            <div class="form-group">
                <label class="form-label">تأكيد كلمة المرور <span class="required">*</span></label>
                <div class="input-container">
                    <span class="input-icon">🔒</span>
                    <input 
                        type="password" 
                        name="confirm_password" 
                        id="confirm_password"
                        class="form-input"
                        placeholder="أعد كتابة كلمة المرور"
                        oninput="checkPasswordMatch()"
                        required
                    >
                    <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">👁️</button>
                    <span id="match-indicator" class="match-indicator">✅</span>
                </div>
            </div>

            <button type="submit" class="submit-btn" id="submitBtn">
                <span>➡️</span>
                إنشاء الحساب
            </button>
        </form>

        <!-- Footer Links -->
        <div class="footer-links">
            <span>لديك حساب بالفعل؟ </span>
            <a href="login.php" class="footer-link primary">تسجيل الدخول</a>
        </div>
    </div>

    <script>
        const themes = {
            male: {
                primary: '#3b82f6',
                gradient: 'linear-gradient(135deg, #3b82f6, #1d4ed8)',
                bg: 'linear-gradient(135deg, #eff6ff 0%, #dbeafe 50%, #bfdbfe 100%)',
                shadow: 'rgba(59, 130, 246, 0.4)'
            },
            female: {
                primary: '#a855f7',
                gradient: 'linear-gradient(135deg, #a855f7, #ec4899)',
                bg: 'linear-gradient(135deg, #fdf4ff 0%, #fef7ff 50%, #fce7f3 100%)',
                shadow: 'rgba(168, 85, 247, 0.4)'
            },
            default: {
                primary: '#10b981',
                gradient: 'linear-gradient(135deg, #10b981, #0d9488)',
                bg: 'linear-gradient(135deg, #ecfdf5 0%, #f0fdfa 50%, #cffafe 100%)',
                shadow: 'rgba(16, 185, 129, 0.4)'
            }
        };

        function changeTheme(gender) {
            const theme = themes[gender];
            document.body.style.background = theme.bg;
            
            // Update elements with theme colors
            const elementsToUpdate = [
                '.submit-btn',
                '.logo',
                '.logo::after',
                '.logo-title'
            ];
            
            // Update CSS custom properties
            document.documentElement.style.setProperty('--primary-color', theme.primary);
            document.documentElement.style.setProperty('--gradient', theme.gradient);
            document.documentElement.style.setProperty('--shadow', theme.shadow);
            
            // Update logo emoji
            const logo = document.getElementById('logo');
            logo.textContent = gender === 'female' ? '🧘‍♀️' : '🧘‍♂️';
        }

        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const toggleBtn = passwordInput.nextElementSibling;
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }

        function validateName() {
            const nameInput = document.getElementById('name');
            const name = nameInput.value.trim();
            
            if (name.length >= 2) {
                nameInput.classList.add('valid');
                nameInput.classList.remove('invalid');
            } else if (name.length > 0) {
                nameInput.classList.add('invalid');
                nameInput.classList.remove('valid');
            } else {
                nameInput.classList.remove('valid', 'invalid');
            }
        }

        function validateEmail() {
            const emailInput = document.getElementById('email');
            const email = emailInput.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (emailRegex.test(email)) {
                emailInput.classList.add('valid');
                emailInput.classList.remove('invalid');
            } else if (email.length > 0) {
                emailInput.classList.add('invalid');
                emailInput.classList.remove('valid');
            } else {
                emailInput.classList.remove('valid', 'invalid');
            }
        }

        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthDiv = document.getElementById('password-strength');
            const passwordInput = document.getElementById('password');
            
            if (password.length === 0) {
                strengthDiv.textContent = '';
                strengthDiv.className = 'password-strength';
                passwordInput.classList.remove('valid', 'invalid');
                return;
            }
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            if (strength >= 4) {
                strengthDiv.textContent = 'قوة كلمة المرور: قوية جداً';
                strengthDiv.className = 'password-strength strength-strong';
                passwordInput.classList.add('valid');
                passwordInput.classList.remove('invalid');
            } else if (strength >= 3) {
                strengthDiv.textContent = 'قوة كلمة المرور: قوية';
                strengthDiv.className = 'password-strength strength-strong';
                passwordInput.classList.add('valid');
                passwordInput.classList.remove('invalid');
            } else if (strength >= 2) {
                strengthDiv.textContent = 'قوة كلمة المرور: متوسطة';
                strengthDiv.className = 'password-strength strength-medium';
                passwordInput.classList.remove('valid', 'invalid');
            } else {
                strengthDiv.textContent = 'قوة كلمة المرور: ضعيفة';
                strengthDiv.className = 'password-strength strength-weak';
                passwordInput.classList.add('invalid');
                passwordInput.classList.remove('valid');
            }
            
            checkPasswordMatch(); // Re-check match when password changes
        }

        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchIndicator = document.getElementById('match-indicator');
            const confirmInput = document.getElementById('confirm_password');
            
            if (confirmPassword.length > 0) {
                if (password === confirmPassword) {
                    matchIndicator.classList.add('show');
                    confirmInput.classList.add('valid');
                    confirmInput.classList.remove('invalid');
                } else {
                    matchIndicator.classList.remove('show');
                    confirmInput.classList.add('invalid');
                    confirmInput.classList.remove('valid');
                }
            } else {
                matchIndicator.classList.remove('show');
                confirmInput.classList.remove('valid', 'invalid');
            }
        }

        // Auto-focus and form validation
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('name').focus();
            
            // Form submission validation
            document.getElementById('signupForm').addEventListener('submit', function(e) {
                const requiredFields = ['name', 'email', 'password', 'confirm_password'];
                let isValid = true;
                
                requiredFields.forEach(field => {
                    const input = document.getElementById(field);
                    if (!input.value.trim()) {
                        input.classList.add('invalid');
                        isValid = false;
                    }
                });
                
                const genderSelected = document.querySelector('input[name="gender"]:checked');
                if (!genderSelected) {
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                    alert('يرجى ملء جميع الحقول المطلوبة');
                }
            });
        });
    </script>
</body>
</html>
