<?php
// تنظیمات ربات
define('BOT_TOKEN', '6689441624:AAFsVldHWqVPEggdxF9NJzOuuqnBJFGhYxM');
define('BOT_USERNAME', 'fadelta_uploaderbot');
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');

// تنظیمات دیتابیس
define('DB_HOST', 'localhost');
define('DB_NAME', 'fadeir_uploader');
define('DB_USER', 'fadeir_uploader');
define('DB_PASS', 'faraz1010Fa+++');

// کانال اجباری
define('REQUIRED_CHANNEL', '@fadelta_source');

// اتصال به دیتابیس
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("set names utf8mb4");
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// توابع کمکی
function apiRequest($method, $params = []) {
    $url = API_URL . $method;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_error($ch)) {
        logMessage("CURL Error: " . curl_error($ch));
    }
    
    curl_close($ch);
    
    return json_decode($response, true);
}

function isAdmin($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn() > 0;
    } catch(PDOException $e) {
        logMessage("Error checking admin: " . $e->getMessage());
        return false;
    }
}

function logMessage($message) {
    $log_file = 'log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $caller = isset($backtrace[1]['function']) ? $backtrace[1]['function'] : 'unknown';
    
    $log_entry = "$timestamp - [$caller] - $message" . PHP_EOL;
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// تابع برای بررسی عضویت در کانال - فقط تعریف در اینجا
function checkChannelMembership($user_id, $chat_id = null) {
    // در این نسخه ساده، همیشه true برمی‌گردانیم
    // در نسخه کامل باید از Telegram API برای بررسی عضویت استفاده کنید
    return true;
}
?>