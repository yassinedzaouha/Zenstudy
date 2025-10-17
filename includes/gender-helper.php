<?php
// includes/gender-helper.php - Helper functions for gender settings management

class GenderConfig {
    private static $config = null;
    
    public static function loadConfig() {
        if (self::$config === null) {
            $configPath = __DIR__ . '/../config/gender-config.json';
            if (file_exists($configPath)) {
                $jsonContent = file_get_contents($configPath);
                self::$config = json_decode($jsonContent, true);
            } else {
                // Default settings if file doesn't exist
                self::$config = [
                    'default' => [
                        'greeting_prefix' => 'مرحباً',
                        'greeting_suffix' => '🌿',
                        'subtitle' => 'أهلاً وسهلاً بك في ZenStudy',
                        'pronouns' => [
                            'how_are_you' => 'كيف تشعر اليوم؟'
                        ],
                        'colors' => [
                            'primary' => '#10b981',
                            'gradient' => 'linear-gradient(135deg, #10b981, #0d9488)',
                            'bg' => 'linear-gradient(135deg, #ecfdf5 0%, #f0fdfa 50%, #cffafe 100%)',
                            'shadow' => 'rgba(16, 185, 129, 0.3)'
                        ],
                        'menu_items' => []
                    ]
                ];
            }
        }
        return self::$config;
    }
    
    public static function getGenderConfig($gender) {
        $config = self::loadConfig();
        return $config[$gender] ?? $config['default'] ?? [];
    }
    
    public static function getGreeting($gender, $name) {
        $config = self::getGenderConfig($gender);
        $prefix = $config['greeting_prefix'] ?? 'مرحباً';
        $suffix = $config['greeting_suffix'] ?? '🌿';
        return $prefix . ' ' . $name . ' ' . $suffix;
    }
    
    public static function getColors($gender) {
        $config = self::getGenderConfig($gender);
        return $config['colors'] ?? [
            'primary' => '#10b981',
            'gradient' => 'linear-gradient(135deg, #10b981, #0d9488)',
            'bg' => 'linear-gradient(135deg, #ecfdf5 0%, #f0fdfa 50%, #cffafe 100%)',
            'shadow' => 'rgba(16, 185, 129, 0.3)'
        ];
    }
    
    public static function getMenuItems($gender) {
        $config = self::getGenderConfig($gender);
        return $config['menu_items'] ?? [];
    }
    
    public static function getPronouns($gender) {
        $config = self::getGenderConfig($gender);
        return $config['pronouns'] ?? [];
    }
}
?>
