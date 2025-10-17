<?php
session_start();
require_once 'db.php';
require_once 'includes/gender-helper.php';
require_once 'includes/mood-translator.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_gender = $_SESSION['user_gender'];
$config = GenderConfig::getGenderConfig($user_gender);
$colors = GenderConfig::getColors($user_gender);
$pronouns = GenderConfig::getPronouns($user_gender);
$mood_options = MoodTranslator::getAllMoodOptions($user_gender);

$message = '';

// Handle mood submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mood_english'])) {
    $mood_english = $_POST['mood_english'];
    $mood_value = MoodTranslator::getMoodValue($mood_english);
    $mood_icon = MoodTranslator::getMoodIcon($mood_english);
    $notes = trim($_POST['notes'] ?? '');
    $today = date('Y-m-d');
    
    try {
        // Delete today's entry and add new one
        $delete_stmt = $conn->prepare("DELETE FROM user_moods WHERE user_id = ? AND mood_date = ?");
        $delete_stmt->bind_param("is", $user_id, $today);
        $delete_stmt->execute();
        $delete_stmt->close();
        
        // Add new mood entry
        $insert_stmt = $conn->prepare("INSERT INTO user_moods (user_id, mood_value, mood_name, mood_icon, notes, mood_date) VALUES (?, ?, ?, ?, ?, ?)");
        $insert_stmt->bind_param("iissss", $user_id, $mood_value, $mood_english, $mood_icon, $notes, $today);
        
        if ($insert_stmt->execute()) {
            $message = "تم حفظ مزاجك! ✅";
        } else {
            $message = "حدث خطأ أثناء الحفظ ❌";
        }
        $insert_stmt->close();
    } catch (Exception $e) {
        $message = "حدث خطأ: " . $e->getMessage();
    }
}

// Get today's mood
$today_mood = null;
$today = date('Y-m-d');
try {
    $today_stmt = $conn->prepare("SELECT * FROM user_moods WHERE user_id = ? AND mood_date = ?");
    $today_stmt->bind_param("is", $user_id, $today);
    $today_stmt->execute();
    $today_result = $today_stmt->get_result();
    if ($today_result->num_rows > 0) {
        $today_mood = $today_result->fetch_assoc();
        $today_mood['mood_name_arabic'] = MoodTranslator::translateMood($today_mood['mood_name'], $user_gender);
    }
    $today_stmt->close();
} catch (Exception $e) {
    // Handle error silently
}

// Get mood history
$mood_history = [];
try {
    $history_stmt = $conn->prepare("SELECT * FROM user_moods WHERE user_id = ? ORDER BY mood_date DESC LIMIT 7");
    $history_stmt->bind_param("i", $user_id);
    $history_stmt->execute();
    $result = $history_stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $row['mood_name_arabic'] = MoodTranslator::translateMood($row['mood_name'], $user_gender);
        $mood_history[] = $row;
    }
    $history_stmt->close();
} catch (Exception $e) {
    // Handle error silently
}

$actual_count = count($mood_history);
$avg_mood = 0;
if ($actual_count > 0) {
    $total = array_sum(array_column($mood_history, 'mood_value'));
    $avg_mood = round($total / $actual_count, 1);
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقييم المزاج | ZenStudy</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: <?= $colors['bg'] ?>;
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 400px; margin: 0 auto; }
        .header { 
            display: flex; 
            align-items: center; 
            margin-bottom: 30px; 
            background: rgba(255, 255, 255, 0.9);
            padding: 16px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .back-btn {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 8px 12px;
            color: #6b7280;
            text-decoration: none;
            margin-left: 12px;
            transition: all 0.2s ease;
        }
        .back-btn:hover {
            background: <?= $colors['primary'] ?>;
            color: white;
            border-color: <?= $colors['primary'] ?>;
        }
        .page-title { flex: 1; text-align: center; }
        .page-title h1 { font-size: 24px; color: #1f2937; margin-bottom: 4px; }
        .page-title p { font-size: 14px; color: #6b7280; }
        .card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            border: 2px solid transparent;
            transition: all 0.2s ease;
        }
        .card:hover {
            border-color: <?= $colors['primary'] ?>33;
        }
        .mood-options { 
            display: grid; 
            grid-template-columns: repeat(5, 1fr);
            gap: 8px; 
            margin: 20px 0; 
        }
        .mood-option { position: relative; }
        .mood-input { position: absolute; opacity: 0; cursor: pointer; }
        .mood-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 12px 4px;
            border: 2px solid #e5e7eb;
            border-radius: 16px;
            cursor: pointer;
            background: #fff;
            min-height: 80px;
            justify-content: center;
            transition: all 0.2s;
        }
        .mood-input:checked + .mood-label {
            border-color: <?= $colors['primary'] ?>;
            background: <?= $colors['primary'] ?>0d;
            transform: scale(1.05);
            box-shadow: 0 4px 12px <?= $colors['shadow'] ?>;
        }
        .mood-emoji { font-size: 24px; margin-bottom: 4px; }
        .mood-name { font-size: 11px; font-weight: 500; color: #374151; text-align: center; }
        .notes-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            min-height: 80px;
            resize: vertical;
            font-family: inherit;
            transition: all 0.2s ease;
        }
        .notes-input:focus {
            outline: none;
            border-color: <?= $colors['primary'] ?>;
            box-shadow: 0 0 0 3px <?= $colors['primary'] ?>1a;
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
        }
        .submit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px <?= $colors['shadow'] ?>;
        }
        .current-mood {
            text-align: center;
            padding: 20px;
            background: <?= $colors['gradient'] ?>;
            color: white;
            border-radius: 16px;
            margin-bottom: 20px;
        }
        .current-mood-emoji { font-size: 48px; margin-bottom: 8px; }
        .message {
            background: #d1fae5;
            border: 1px solid #10b981;
            color: #065f46;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            text-align: center;
        }
        .mood-stats {
            text-align: center;
            padding: 16px;
            background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .stats-title {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 4px;
        }
        .stats-value {
            font-size: 24px;
            font-weight: bold;
            color: <?= $colors['primary'] ?>;
        }
        .history-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 16px;
            text-align: center;
        }
        .history-item {
            display: flex;
            align-items: center;
            padding: 12px;
            background: #f9fafb;
            border-radius: 12px;
            margin-bottom: 8px;
            transition: all 0.2s ease;
        }
        .history-item:hover {
            background: #f3f4f6;
            transform: translateX(-2px);
        }
        .history-emoji { font-size: 24px; margin-left: 12px; }
        .history-content { flex: 1; }
        .history-name { font-weight: 500; color: #1f2937; }
        .history-date { font-size: 12px; color: #6b7280; }
        .count-badge {
            background: <?= $colors['primary'] ?>;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 8px;
        }
        .empty-state {
            text-align: center;
            color: #6b7280;
            padding: 40px 20px;
        }
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="index.php" class="back-btn">← رجوع</a>
            <div class="page-title">
                <h1>تقييم المزاج</h1>
                <p><?= $pronouns['how_are_you'] ?? 'كيف تشعر اليوم؟' ?></p>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($today_mood): ?>
        <div class="current-mood">
            <div class="current-mood-emoji"><?= htmlspecialchars($today_mood['mood_icon']) ?></div>
            <div>مزاجك اليوم: <?= htmlspecialchars($today_mood['mood_name_arabic']) ?></div>
        </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST">
                <h3 style="margin-bottom: 16px; text-align: center;">اختر مزاجك اليوم</h3>
                
                <div class="mood-options">
                    <?php foreach ($mood_options as $mood): ?>
                    <div class="mood-option">
                        <input 
                            type="radio" 
                            id="mood_<?= $mood['value'] ?>" 
                            name="mood_english" 
                            value="<?= htmlspecialchars($mood['english']) ?>"
                            class="mood-input"
                            <?= ($today_mood && $today_mood['mood_name'] == $mood['english']) ? 'checked' : '' ?>
                            required
                        >
                        <label for="mood_<?= $mood['value'] ?>" class="mood-label">
                            <div class="mood-emoji"><?= htmlspecialchars($mood['icon']) ?></div>
                            <div class="mood-name"><?= htmlspecialchars($mood['arabic']) ?></div>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div style="margin: 20px 0;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500;">ملاحظات (اختياري)</label>
                    <textarea name="notes" class="notes-input" placeholder="كيف كان يومك؟"><?= htmlspecialchars($today_mood['notes'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="submit-btn">
                    حفظ المزاج ✅
                </button>
            </form>
        </div>

        <?php if ($actual_count > 1 && $avg_mood > 0): ?>
        <div class="mood-stats">
            <div class="stats-title">متوسط مزاجك (آخر <?= $actual_count ?> أيام)</div>
            <div class="stats-value"><?= $avg_mood ?>/5</div>
        </div>
        <?php endif; ?>

        <?php if ($actual_count > 0): ?>
        <div class="card">
            <h3 class="history-title">
                <span class="count-badge"><?= $actual_count ?></span>
                سجل المزاج
            </h3>
            
            <?php foreach ($mood_history as $mood): ?>
            <div class="history-item">
                <div class="history-emoji"><?= htmlspecialchars($mood['mood_icon']) ?></div>
                <div class="history-content">
                    <div class="history-name"><?= htmlspecialchars($mood['mood_name_arabic']) ?></div>
                    <div class="history-date"><?= date('Y/m/d', strtotime($mood['mood_date'])) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="empty-state">
                <div class="empty-state-icon">📊</div>
                <div>لا يوجد سجل مزاج</div>
                <div style="font-size: 14px; margin-top: 8px;">ابدأ بتسجيل مزاجك يومياً</div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
</merged_code>
