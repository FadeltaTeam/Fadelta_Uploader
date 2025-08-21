<?php
// تنظیمات ربات
define('BOT_TOKEN', 'Token'); // توکن ربات
define('BOT_USERNAME', 'bot_username'); //یوزر نیم ربات بدون @
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');

// تنظیمات دیتابیس
define('DB_HOST', 'localhost');
define('DB_NAME', 'name'); // اسم دیتابیس
define('DB_USER', 'username'); // یوزرنیم دیتابیس
define('DB_PASS', 'password'); // پسورد دیتابیس

// کانال اجباری
define('REQUIRED_CHANNEL', '@Channel_username');

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

// توابع سیستم کامنت و امتیازدهی
function canUserReview($user_id, $file_id) {
    global $pdo;
    
    try {
        // بررسی آیا کاربر قبلاً این فایل را دانلود کرده
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM user_library 
            WHERE user_id = ? AND file_id = ?
        ");
        $stmt->execute([$user_id, $file_id]);
        $has_downloaded = $stmt->fetchColumn() > 0;
        
        // بررسی آیا کاربر قبلاً نظر داده
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM reviews 
            WHERE user_id = ? AND file_id = ?
        ");
        $stmt->execute([$user_id, $file_id]);
        $has_reviewed = $stmt->fetchColumn() > 0;
        
        return $has_downloaded && !$has_reviewed;
    } catch(PDOException $e) {
        logMessage("Error checking review permission: " . $e->getMessage());
        return false;
    }
}

function updateFileRating($file_id) {
    global $pdo;
    
    try {
        // محاسبه میانگین امتیاز و تعداد نظرات
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_reviews,
                AVG(rating) as average_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
            FROM reviews 
            WHERE file_id = ? AND status = 'approved'
        ");
        $stmt->execute([$file_id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // به‌روزرسانی آمار فایل
        $stmt = $pdo->prepare("
            UPDATE files SET 
                average_rating = ?,
                total_reviews = ?,
                rating_breakdown = ?
            WHERE id = ?
        ");
        
        $rating_breakdown = json_encode([
            '5_star' => $stats['five_star'],
            '4_star' => $stats['four_star'],
            '3_star' => $stats['three_star'],
            '2_star' => $stats['two_star'],
            '1_star' => $stats['one_star']
        ]);
        
        $stmt->execute([
            $stats['average_rating'],
            $stats['total_reviews'],
            $rating_breakdown,
            $file_id
        ]);
        
    } catch(PDOException $e) {
        logMessage("Error updating file rating: " . $e->getMessage());
    }
}

function getUserState($user_id) {
    global $pdo;
    
    try {
        // ابتدا بررسی کنید آیا کاربر ادمین است
        $stmt = $pdo->prepare("SELECT upload_state, current_file FROM admins WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $admin_state = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin_state) {
            return $admin_state;
        }
        
        // اگر ادمین نیست، از جدول جداگانه برای وضعیت کاربران معمولی استفاده کنید
        // ایجاد جدول user_states اگر وجود ندارد
        $stmt = $pdo->prepare("
            CREATE TABLE IF NOT EXISTS user_states (
                user_id BIGINT PRIMARY KEY,
                upload_state VARCHAR(50),
                current_file VARCHAR(255),
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        $stmt->execute();
        
        // دریافت وضعیت کاربر
        $stmt = $pdo->prepare("SELECT upload_state, current_file FROM user_states WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        logMessage("Error getting user state: " . $e->getMessage());
        return null;
    }
}

function saveToUserLibrary($user_id, $file_id) {
    global $pdo;
    
    try {
        // بررسی وجود در کتابخانه
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_library WHERE user_id = ? AND file_id = ?");
        $stmt->execute([$user_id, $file_id]);
        $exists = $stmt->fetchColumn();
        
        if ($exists) {
            // افزایش تعداد دانلود
            $stmt = $pdo->prepare("
                UPDATE user_library 
                SET download_count = download_count + 1, last_downloaded = NOW() 
                WHERE user_id = ? AND file_id = ?
            ");
            $stmt->execute([$user_id, $file_id]);
        } else {
            // اضافه کردن به کتابخانه
            $stmt = $pdo->prepare("
                INSERT INTO user_library (user_id, file_id, download_count) 
                VALUES (?, ?, 1)
            ");
            $stmt->execute([$user_id, $file_id]);
        }
    } catch(PDOException $e) {
        logMessage("Error saving to user library: " . $e->getMessage());
    }
}

function sendReviewPrompt($chat_id, $file_unique_id, $file) {
    global $pdo;
    
    // بررسی آیا کاربر قبلاً نظر داده
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM reviews r 
            WHERE r.user_id = ? AND r.file_id = ?
        ");
        $stmt->execute([$chat_id, $file['id']]);
        $has_reviewed = $stmt->fetchColumn() > 0;
        
        if ($has_reviewed) {
            // کاربر قبلاً نظر داده
            $message = "✅ فایل با موفقیت دانلود شد!\n\n";
            $message .= "🌟 شما قبلاً به این فایل امتیاز داده‌اید.\n\n";
            $message .= "برای مشاهده نظرات دیگران از /reviews_{$file_unique_id} استفاده کنید.";
            
            apiRequest('sendMessage', [
                'chat_id' => $chat_id,
                'text' => $message
            ]);
            return;
        }
        
        // ایجاد کیبورد برای نظردهی
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '⭐ امتیازدهی به فایل', 'callback_data' => 'rate_file_' . $file_unique_id]
                ],
                [
                    ['text' => '📝 مشاهده نظرات دیگران', 'callback_data' => 'view_reviews_' . $file_unique_id]
                ],
                [
                    ['text' => '➡️ ادامه بدون امتیاز', 'callback_data' => 'skip_rating']
                ]
            ]
        ];
        
        $message = "✅ فایل با موفقیت دانلود شد!\n\n";
        $message .= "🌟 لطفا با امتیازدهی به این فایل، به بهبود کیفیت محتوا کمک کنید.\n\n";
        $message .= "امتیاز شما تجربه بهتری برای دیگر کاربران ایجاد می‌کند.";
        
        apiRequest('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $message,
            'reply_markup' => json_encode($keyboard)
        ]);
        
    } catch(PDOException $e) {
        logMessage("Error sending review prompt: " . $e->getMessage());
        
        // Fallback به پیام ساده
        apiRequest('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "✅ فایل با موفقیت دانلود شد!\n\nبرای دانلود فایل‌های بیشتر، روی لینک‌های دیگر کلیک کنید."
        ]);
    }
}

function startRatingProcess($chat_id, $user_id, $file_unique_id, $message_id) {
    global $pdo;
    
    try {
        // پیدا کردن فایل
        $stmt = $pdo->prepare("SELECT id FROM files WHERE file_unique_id = ?");
        $stmt->execute([$file_unique_id]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$file) {
            apiRequest('editMessageText', [
                'chat_id' => $chat_id,
                'message_id' => $message_id,
                'text' => "❌ فایل مورد نظر یافت نشد."
            ]);
            return;
        }
        
        // بررسی اجازه نظر دادن
        if (!canUserReview($user_id, $file['id'])) {
            apiRequest('editMessageText', [
                'chat_id' => $chat_id,
                'message_id' => $message_id,
                'text' => "❌ شما نمی‌توانید برای این فایل نظر دهید."
            ]);
            return;
        }
        
        // ارسال کیبورد برای انتخاب امتیاز
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '⭐ 1 ستاره', 'callback_data' => 'rate_1_' . $file['id']],
                    ['text' => '⭐⭐ 2 ستاره', 'callback_data' => 'rate_2_' . $file['id']]
                ],
                [
                    ['text' => '⭐⭐⭐ 3 ستاره', 'callback_data' => 'rate_3_' . $file['id']],
                    ['text' => '⭐⭐⭐⭐ 4 ستاره', 'callback_data' => 'rate_4_' . $file['id']]
                ],
                [
                    ['text' => '⭐⭐⭐⭐⭐ 5 ستاره', 'callback_data' => 'rate_5_' . $file['id']]
                ],
                [
                    ['text' => '↩️ بازگشت', 'callback_data' => 'cancel_rating_' . $file_unique_id]
                ]
            ]
        ];
        
        apiRequest('editMessageText', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => "🌟 لطفا به این فایل امتیاز دهید:\n\nامتیاز خود را از 1 تا 5 ستاره انتخاب کنید.",
            'reply_markup' => json_encode($keyboard)
        ]);
        
    } catch(PDOException $e) {
        logMessage("Error starting rating process: " . $e->getMessage());
        apiRequest('editMessageText', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => "❌ خطایی در شروع فرآیند امتیازدهی رخ داده است."
        ]);
    }
}

function addRatingButtonToMessage($chat_id, $file_unique_id, $message_id, $callback_query_id) {
    global $pdo;
    
    try {
        // دریافت اطلاعات فایل
        $stmt = $pdo->prepare("SELECT file_name FROM files WHERE file_unique_id = ?");
        $stmt->execute([$file_unique_id]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$file) {
            apiRequest('answerCallbackQuery', [
                'callback_query_id' => $callback_query_id,
                'text' => "❌ فایل یافت نشد"
            ]);
            return;
        }
        
        $download_link = "https://t.me/" . BOT_USERNAME . "?start=download_" . $file_unique_id;
        
        // ایجاد کیبورد با دکمه امتیازدهی
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📥 دانلود فایل', 'url' => $download_link]
                ],
                [
                    ['text' => '⭐ امتیازدهی به فایل', 'callback_data' => 'rate_file_' . $file_unique_id]
                ],
                [
                    ['text' => '📝 مشاهده نظرات', 'callback_data' => 'view_reviews_' . $file_unique_id]
                ]
            ]
        ];
        
        // ویرایش پیام
        apiRequest('editMessageReplyMarkup', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'reply_markup' => json_encode($keyboard)
        ]);
        
        apiRequest('answerCallbackQuery', [
            'callback_query_id' => $callback_query_id,
            'text' => "✅ دکمه امتیازدهی اضافه شد"
        ]);
        
    } catch(PDOException $e) {
        logMessage("Error adding rating button: " . $e->getMessage());
        apiRequest('answerCallbackQuery', [
            'callback_query_id' => $callback_query_id,
            'text' => "❌ خطا در اضافه کردن دکمه"
        ]);
    }
}

function setUserState($user_id, $state, $current_file = null) {
    global $pdo;
    
    try {
        // بررسی آیا کاربر ادمین است
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $is_admin = $stmt->fetchColumn() > 0;
        
        if ($is_admin) {
            // برای ادمین‌ها از جدول admins استفاده کنید
            $stmt = $pdo->prepare("UPDATE admins SET upload_state = ?, current_file = ? WHERE user_id = ?");
            $stmt->execute([$state, $current_file, $user_id]);
        } else {
            // برای کاربران معمولی از جدول user_states استفاده کنید
            $stmt = $pdo->prepare("
                INSERT INTO user_states (user_id, upload_state, current_file) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE upload_state = ?, current_file = ?
            ");
            $stmt->execute([$user_id, $state, $current_file, $state, $current_file]);
        }
        
        return true;
    } catch(PDOException $e) {
        logMessage("Error setting user state: " . $e->getMessage());
        return false;
    }
}

function clearUserState($user_id) {
    global $pdo;
    
    try {
        // بررسی آیا کاربر ادمین است
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $is_admin = $stmt->fetchColumn() > 0;
        
        if ($is_admin) {
            $stmt = $pdo->prepare("UPDATE admins SET upload_state = NULL, current_file = NULL WHERE user_id = ?");
            $stmt->execute([$user_id]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM user_states WHERE user_id = ?");
            $stmt->execute([$user_id]);
        }
        
        return true;
    } catch(PDOException $e) {
        logMessage("Error clearing user state: " . $e->getMessage());
        return false;
    }
}

function cancelUserOperation($chat_id, $user_id) {
    $user_state = getUserState($user_id);
    
    if ($user_state) {
        clearUserState($user_id);
        apiRequest('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "❌ عملیات فعلی لغو شد."
        ]);
    } else {
        apiRequest('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "⚠️ هیچ عملیات فعالی برای لغو وجود ندارد."
        ]);
    }
}
?>