<?php
// index.php - Enhanced main page
session_start();
require_once 'includes/gender-helper.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_name = $_SESSION['user_name'];
$user_gender = $_SESSION['user_gender'];

// Get gender settings from JSON
$config = GenderConfig::getGenderConfig($user_gender);
$colors = GenderConfig::getColors($user_gender);
$greeting = GenderConfig::getGreeting($user_gender, $user_name);
$menu_items = GenderConfig::getMenuItems($user_gender);
$pronouns = GenderConfig::getPronouns($user_gender);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>الرئيسية | ZenStudy</title>
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
          padding: 20px;
      }

      .container {
          max-width: 400px;
          margin: 0 auto;
      }

      .header {
          text-align: center;
          margin-bottom: 40px;
          padding-top: 20px;
      }

      .logo {
          width: 80px;
          height: 80px;
          background: <?= $colors['gradient'] ?>;
          border-radius: 50%;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          font-size: 48px;
          position: relative;
          box-shadow: 0 12px 24px <?= $colors['shadow'] ?>;
          margin-bottom: 16px;
      }

      .logo::after {
          content: "📚";
          position: absolute;
          bottom: -4px;
          right: -4px;
          width: 28px;
          height: 28px;
          background: <?= $colors['gradient'] ?>;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 14px;
      }

      .header h1 {
          font-size: 24px;
          color: #1f2937;
          margin-bottom: 8px;
      }

      .header p {
          color: #6b7280;
          font-size: 14px;
      }

      .menu-items {
          display: flex;
          flex-direction: column;
          gap: 16px;
          margin-bottom: 32px;
      }

      .menu-item {
          display: block;
          background: rgba(255, 255, 255, 0.9);
          border-radius: 16px;
          padding: 20px;
          text-decoration: none;
          color: inherit;
          box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
          transition: all 0.2s ease;
          border: 2px solid transparent;
      }

      .menu-item:hover {
          transform: translateY(-2px);
          border-color: <?= $colors['primary'] ?>;
          box-shadow: 0 12px 24px <?= $colors['shadow'] ?>;
      }

      .menu-title {
          font-size: 18px;
          font-weight: 600;
          color: #1f2937;
          margin-bottom: 4px;
      }

      .menu-desc {
          font-size: 14px;
          color: #6b7280;
      }

      .mood-item {
          background: <?= $colors['gradient'] ?>;
          color: white;
      }

      .mood-item:hover {
          border-color: white;
      }

      .mood-item .menu-title,
      .mood-item .menu-desc {
          color: white;
      }

      .logout-section {
          margin-top: 32px;
      }

      .logout-button {
          width: 100%;
          background: #fee2e2;
          border: 1px solid #fecaca;
          color: #dc2626;
          padding: 12px;
          border-radius: 12px;
          text-decoration: none;
          text-align: center;
          display: block;
          transition: all 0.2s ease;
      }

      .logout-button:hover {
          background: #fecaca;
          transform: translateY(-1px);
      }

      .footer {
          text-align: center;
          margin-top: 32px;
          padding-bottom: 20px;
      }

      .footer p {
          font-size: 12px;
          color: #9ca3af;
          margin-bottom: 4px;
      }

      @media (max-width: 480px) {
          .container {
              margin: 0 10px;
          }
          
          .header {
              padding-top: 10px;
          }
          
          .menu-item {
              padding: 16px;
          }
      }
  </style>
</head>
<body>
  <div class="container">
      <!-- Header -->
      <div class="header">
          <div class="logo">🧘‍<?= $user_gender === 'female' ? '♀️' : '♂️' ?></div>
          <h1><?= $greeting ?></h1>
          <p><?= $config['subtitle'] ?? 'أهلاً وسهلاً بك في ZenStudy' ?></p>
      </div>

      <!-- Menu Items -->
      <div class="menu-items">
          <!-- Mood Assessment - Always First -->
          <a href="mood.php" class="menu-item mood-item">
              <div class="menu-title">💖 تقييم المزاج</div>
              <div class="menu-desc"><?= $pronouns['how_are_you'] ?? 'كيف تشعر اليوم؟' ?></div>
          </a>

          <!-- Dynamic Menu Items -->
          <?php if (!empty($menu_items)): ?>
              <?php foreach ($menu_items as $item): ?>
              <a href="<?= htmlspecialchars($item['href']) ?>" class="menu-item">
                  <div class="menu-title"><?= htmlspecialchars($item['title']) ?></div>
                  <div class="menu-desc"><?= htmlspecialchars($item['description']) ?></div>
              </a>
              <?php endforeach; ?>
          <?php endif; ?>
      </div>

      <!-- Logout Button -->
      <div class="logout-section">
          <a href="logout.php" class="logout-button">تسجيل الخروج</a>
      </div>

      <!-- Footer -->
      <div class="footer">
          <p>ZenStudy © 2024</p>
          <p>دراسة بسكينة وتركيز</p>
      </div>
  </div>
</body>
</html>
