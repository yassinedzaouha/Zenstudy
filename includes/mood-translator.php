<?php
// includes/mood-translator.php - Mood translation from English to Arabic

class MoodTranslator {
    
    private static $translations = [
        'male' => [
            'excellent' => 'ممتاز',
            'happy' => 'سعيد', 
            'normal' => 'عادي',
            'tired' => 'متعب',
            'sad' => 'حزين'
        ],
        'female' => [
            'excellent' => 'ممتازة',
            'happy' => 'سعيدة',
            'normal' => 'عادية', 
            'tired' => 'متعبة',
            'sad' => 'حزينة'
        ]
    ];
    
    private static $mood_icons = [
        'excellent' => '🌟',
        'happy' => '😊',
        'normal' => '😐',
        'tired' => '😔', 
        'sad' => '😢'
    ];
    
    private static $mood_values = [
        'excellent' => 5,
        'happy' => 4,
        'normal' => 3,
        'tired' => 2,
        'sad' => 1
    ];
    
    public static function translateMood($english_mood, $gender) {
        $translations = self::$translations[$gender] ?? self::$translations['male'];
        return $translations[$english_mood] ?? $english_mood;
    }
    
    public static function getMoodIcon($english_mood) {
        return self::$mood_icons[$english_mood] ?? '😐';
    }
    
    public static function getMoodValue($english_mood) {
        return self::$mood_values[$english_mood] ?? 3;
    }
    
    public static function getAllMoodOptions($gender) {
        $moods = [];
        foreach (self::$translations['male'] as $english => $arabic) {
            $moods[] = [
                'english' => $english,
                'arabic' => self::translateMood($english, $gender),
                'icon' => self::getMoodIcon($english),
                'value' => self::getMoodValue($english)
            ];
        }
        return $moods;
    }
}
?>
