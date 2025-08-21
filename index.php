<?php
require_once 'config.php';

// دریافت داده‌های ورودی
$input = file_get_contents('php://input');
$update = json_decode($input, true);

if (!$update) {
    exit;
}

// پردازش انواع مختلف آپدیت
if (isset($update['message'])) {
    processMessage($update['message']);
} elseif (isset($update['callback_query'])) {
    processCallbackQuery($update['callback_query']);
}

function processMessage($message) {
    $chat_id = $message['chat']['id'];
    $user_id = $message['from']['id'];
    $text = isset($message['text']) ? $message['text'] : '';
    
    // ذخیره اطلاعات کاربر
    saveUser($message['from']);
    
    // بررسی عضویت در کانال
    if (!checkChannelMembership($user_id, $chat_id)) {
        sendChannelJoinMessage($chat_id);
        return;
    }
    
    // بررسی دستورات
    if (strpos($text, '/start') === 0) {
        if (strpos($text, 'download_') !== false) {
            $parts = explode('_', $text);
            $file_unique_id = $parts[1];
            downloadFile($chat_id, $file_unique_id);
        } else {
            sendWelcomeMessage($chat_id, isAdmin($user_id));
        }
    } elseif (isAdmin($user_id)) {
        processAdminMessage($chat_id, $message);
    } else {
        processUserMessage($chat_id, $message);
    }
}

function processCallbackQuery($callback_query) {
    $user_id = $callback_query['from']['id'];
    $chat_id = $callback_query['message']['chat']['id'];
    $data = $callback_query['data'];
    $message_id = $callback_query['message']['message_id'];
    
    // ذخیره اطلاعات کاربر
    saveUser($callback_query['from']);
    
    // بررسی عضویت در کانال
    if (!checkChannelMembership($user_id, $chat_id)) {
        sendChannelJoinMessage($chat_id);
        return;
    }
    
    if (isAdmin($user_id)) {
        processAdminCallback($chat_id, $data, $message_id);
    } else {
        processUserCallback($chat_id, $data);
    }
    
    // پاسخ به کال‌بک کوئری
    apiRequest('answerCallbackQuery', [
        'callback_query_id' => $callback_query['id']
    ]);
}

function processAdminMessage($chat_id, $message) {
    global $pdo;
    $user_id = $message['from']['id'];
    $text = isset($message['text']) ? $message['text'] : '';
    
    // بررسی وضعیت آپلود فایل
    try {
        $stmt = $pdo->prepare("SELECT upload_state, current_file FROM admins WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $admin_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin_data && $admin_data['upload_state']) {
            // ادامه فرآیند آپلود بر اساس وضعیت
            continueUploadProcess($chat_id, $message, $admin_data['upload_state'], $admin_data['current_file']);
            return;
        }
        
        // بررسی وضعیت ارسال همگانی
        if ($admin_data && $admin_data['upload_state'] == 'waiting_broadcast') {
            sendBroadcastMessage($user_id, $text);
            return;
        }
    } catch(PDOException $e) {
        logMessage("Error checking upload state: " . $e->getMessage());
        apiRequest('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "خطایی در سیستم رخ داده است. لطفا دوباره تلاش کنید."
        ]);
        return;
    }
    
    // پردازش دستورات ادمین
    if ($text == '/admin' || $text == 'پنل مدیریت') {
        showAdminPanel($chat_id);
    } elseif ($text == '/upload' || $text == 'آپلود فایل') {
        startUploadProcess($chat_id, $user_id);
    } elseif ($text == '/stats' || $text == 'آمار و گزارشات') {
        showStats($chat_id);
    } elseif ($text == '/broadcast' || $text == 'ارسال همگانی') {
        startBroadcastProcess($chat_id, $user_id);
    } elseif ($text == '/manage' || $text == 'مدیریت فایل‌ها') {
        showFileManagement($chat_id);
    } else {
        // اگر پیام متنی معمولی است و ادمین است، بررسی کن
        apiRequest('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "دستور نامعتبر است. از /admin برای دسترسی به پنل مدیریت استفاده کنید."
        ]);
    }
}

function startUploadProcess($chat_id, $user_id) {
    global $pdo;
    
    try {
        // تنظیم وضعیت آپلود
        $stmt = $pdo->prepare("UPDATE admins SET upload_state = 'waiting_file' WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        apiRequest('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "لطفا فایل خود را ارسال کنید:"
        ]);
    } catch(PDOException $e) {
        logMessage("Error starting upload process: " . $e->getMessage());
        apiRequest('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "خطایی رخ داده است. لطفا دوباره تلاش کنید."
        ]);
    }
}

function continueUploadProcess($chat_id, $message, $state, $current_file) {
    global $pdo;
    $user_id = $message['from']['id'];
    
    if ($state == 'waiting_file') {
        // بررسی وجود فایل
        if (isset($message['document']) || isset($message['photo']) || 
            isset($message['video']) || isset($message['audio']) || 
            isset($message['voice'])) {
            
            // ذخیره اطلاعات فایل
            $file_id = '';
            $file_type = '';
            $file_size = 0;
            $mime_type = '';
            $file_name = '';
            
            if (isset($message['document'])) {
                $file_id = $message['document']['file_id'];
                $file_type = 'document';
                $file_size = $message['document']['file_size'];
                $mime_type = isset($message['document']['mime_type']) ? $message['document']['mime_type'] : '';
                $file_name = isset($message['document']['file_name']) ? $message['document']['file_name'] : '';
            } elseif (isset($message['photo'])) {
                $photos = $message['photo'];
                $largest_photo = end($photos);
                $file_id = $largest_photo['file_id'];
                $file_type = 'photo';
                $file_size = $largest_photo['file_size'];
            } elseif (isset($message['video'])) {
                $file_id = $message['video']['file_id'];
                $file_type = 'video';
                $file_size = $message['video']['file_size'];
                $mime_type = isset($message['video']['mime_type']) ? $message['video']['mime_type'] : '';
                $file_name = isset($message['video']['file_name']) ? $message['video']['file_name'] : '';
            } elseif (isset($message['audio'])) {
                $file_id = $message['audio']['file_id'];
                $file_type = 'audio';
                $file_size = $message['audio']['file_size'];
                $mime_type = isset($message['audio']['mime_type']) ? $message['audio']['mime_type'] : '';
                $file_name = isset($message['audio']['file_name']) ? $message['audio']['file_name'] : '';
            } elseif (isset($message['voice'])) {
                $file_id = $message['voice']['file_id'];
                $file_type = 'voice';
                $file_size = $message['voice']['file_size'];
                $mime_type = 'audio/ogg';
            }
            
            try {
                // ذخیره اطلاعات اولیه فایل
                $file_unique_id = uniqid();
                $stmt = $pdo->prepare("INSERT INTO files (file_id, file_unique_id, type, file_name, file_size, mime_type, uploaded_by) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$file_id, $file_unique_id, $file_type, $file_name, $file_size, $mime_type, $user_id]);
                
                // تغییر وضعیت به انتظار برای کپشن
                $stmt = $pdo->prepare("UPDATE admins SET upload_state = 'waiting_caption', current_file = ? WHERE user_id = ?");
                $stmt->execute([$file_unique_id, $user_id]);
                
                apiRequest('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => "فایل با موفقیت دریافت شد. لطفا کپشن را ارسال کنید (در صورت عدم نیاز به کپشن از /skip استفاده کنید):"
                ]);
            } catch(PDOException $e) {
                logMessage("Error saving file: " . $e->getMessage());
                apiRequest('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => "خطایی در ذخیره فایل رخ داده است. لطفا دوباره تلاش کنید."
                ]);
            }
        } else {
            apiRequest('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "لطفا یک فایل معتبر ارسال کنید."
            ]);
        }
    } elseif ($state == 'waiting_caption') {
        // دریافت کپشن
        $text = isset($message['text']) ? $message['text'] : '';
        
        if ($text == '/skip') {
            $text = '';
        }
        
        if (!empty($text) || $text == '') {
            try {
                // به روز رسانی کپشن فایل
                $stmt = $pdo->prepare("UPDATE files SET caption = ? WHERE file_unique_id = ?");
                $stmt->execute([$text, $current_file]);
                
                // بازنشانی وضعیت آپلود
                $stmt = $pdo->prepare("UPDATE admins SET upload_state = NULL, current_file = NULL WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // ایجاد لینک دانلود
                $download_link = "https://t.me/" . BOT_USERNAME . "?start=download_" . $current_file;
                
                apiRequest('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => "✅ فایل با موفقیت آپلود شد!\n\n📎 لینک دانلود: " . $download_link,
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [
                                ['text' => '📤 اشتراک گذاری لینک', 'url' => 'https://t.me/share/url?url=' . urlencode($download_link)]
                            ],
                            [
                                ['text' => '↩️ بازگشت به پنل', 'callback_data' => 'admin_panel']
                            ]
                        ]
                    ])
                ]);
            } catch(PDOException $e) {
                logMessage("Error updating caption: " . $e->getMessage());
                apiRequest('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => "خطایی در ذخیره کپشن رخ داده است. لطفا دوباره تلاش کنید."
                ]);
            }
        } else {
            apiRequest('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "لطفا یک کپشن معتبر ارسال کنید یا از /skip استفاده کنید."
            ]);
        }
    }
}

function showAdminPanel($chat_id) {
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '📤 آپلود فایل', 'callback_data' => 'upload_file']
            ],
            [
                ['text' => '📊 آمار و گزارشات', 'callback_data' => 'show_stats']
            ],
            [
                ['text' => '📣 ارسال همگانی', 'callback_data' => 'broadcast_message']
            ],
            [
                ['text' => '📁 مدیریت فایل‌ها', 'callback_data' => 'manage_files']
            ]
        ]
    ];
    
    apiRequest('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "👨‍💻 پنل مدیریت\n\nآپلودر فادلتا\n\nنسخه : 1.0\n\nکانال فادلتا سورس : @fadelta_source\n\nلطفا گزینه مورد نظر را انتخاب کنید:",
        'reply_markup' => json_encode($keyboard)
    ]);
}

function processAdminCallback($chat_id, $data, $message_id) {
    switch ($data) {
        case 'upload_file':
            startUploadProcess($chat_id, $chat_id);
            break;
        case 'show_stats':
            showStats($chat_id);
            break;
        case 'broadcast_message':
            startBroadcastProcess($chat_id, $chat_id);
            break;
        case 'manage_files':
            showFileManagement($chat_id);
            break;
        case 'admin_panel':
            showAdminPanel($chat_id);
            break;
        default:
            // پردازش سایر callback_dataها
            if (strpos($data, 'file_') === 0) {
                $file_id = substr($data, 5);
                showFileDetails($chat_id, $file_id, $message_id);
            } elseif (strpos($data, 'delete_') === 0) {
                $file_id = substr($data, 7);
                deleteFile($chat_id, $file_id, $message_id);
            }
            break;
    }
}

function showStats($chat_id) {
    global $pdo;
    
    try {
        // تعداد کل فایل‌ها
        $stmt = $pdo->query("SELECT COUNT(*) FROM files");
        $total_files = $stmt->fetchColumn();
        
        // تعداد کل کاربران
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $total_users = $stmt->fetchColumn();
        
        // تعداد دانلودها
        $stmt = $pdo->query("SELECT SUM(download_count) FROM files");
        $total_downloads = $stmt->fetchColumn();
        
        // فایل‌های پرطرفدار
        $stmt = $pdo->query("SELECT file_name, download_count FROM files ORDER BY download_count DESC LIMIT 5");
        $popular_files = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $message = "📊 آمار ربات:\n\n";
        $message .= "📁 تعداد فایل‌ها: " . number_format($total_files) . "\n";
        $message .= "👥 تعداد کاربران: " . number_format($total_users) . "\n";
        $message .= "📥 تعداد دانلودها: " . number_format($total_downloads) . "\n\n";
        $message .= "🔥 پرطرفدارترین فایل‌ها:\n";
        
        foreach ($popular_files as $index => $file) {
            $message .= ($index + 1) . ". " . ($file['file_name'] ?: 'بدون نام') . " - " . $file['download_count'] . " دانلود\n";
        }
        
        apiRequest('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $message,
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => '↩️ بازگشت به پنل', 'callback_data' => 'admin_panel']
                    ]
                ]
            ])
        ]);
    } catch(PDOException $e) {
        logMessage("Error showing stats: " . $e->getMessage());
        apiRequest('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "خطایی در دریافت آمار رخ داده است."
        ]);
    }
}

function startBroadcastProcess($chat_id, $user_id) {
    global $pdo;
    
    try {
        // تنظیم وضعیت برای دریافت پیام همگانی
        $stmt = $pdo->prepare("UPDATE admins SET upload_state = 'waiting_broadcast' WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        apiRequest('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "لطفا پیام خود برای ارسال همگانی را وارد کنید:"
        ]);
    } catch(PDOException $e) {
        logMessage("Error starting broadcast: " . $e->getMessage());
        apiRequest('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "خطایی در شروع ارسال همگانی رخ داده است."
        ]);
    }
}

function sendBroadcastMessage($user_id, $message_text) {
    global $pdo;
    
    try {
        // دریافت تمام کاربران
        $stmt = $pdo->query("SELECT user_id FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $sent = 0;
        $failed = 0;
        
        foreach ($users as $user) {
            try {
                // ارسال پیام به کاربر
                apiRequest('sendMessage', [
                    'chat_id' => $user,
                    'text' => "📢 پیام همگانی:\n\n" . $message_text
                ]);
                $sent++;
            } catch (Exception $e) {
                $failed++;
                logMessage("Failed to send message to user $user: " . $e->getMessage());
            }
        }
        
        // بازنشانی وضعیت
        $stmt = $pdo->prepare("UPDATE admins SET upload_state = NULL WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        apiRequest('sendMessage', [
            'chat_id' => $user_id,
            'text' => "✅ پیام همگانی ارسال شد!\n\nارسال موفق: $sent\nارسال ناموفق: $failed",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => '↩️ بازگشت به پنل', 'callback_data' => 'admin_panel']
                    ]
                ]
            ])
        ]);
    } catch(PDOException $e) {
        logMessage("Error sending broadcast: " . $e->getMessage());
        apiRequest('sendMessage', [
            'chat_id' => $user_id,
            'text' => "خطایی در ارسال پیام همگانی رخ داده است."
        ]);
    }
}

function showFileManagement($chat_id) {
    global $pdo;
    
    try {
        // دریافت فایل‌ها
        $stmt = $pdo->query("SELECT * FROM files ORDER BY upload_date DESC LIMIT 10");
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($files) == 0) {
            apiRequest('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "هنوز هیچ فایلی آپلود نشده است.",
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [
                            ['text' => '↩️ بازگشت به پنل', 'callback_data' => 'admin_panel']
                        ]
                    ]
                ])
            ]);
            return;
        }
        
        $message = "📁 مدیریت فایل‌ها (آخرین 10 فایل):\n\n";
        
        foreach ($files as $index => $file) {
            $message .= ($index + 1) . ". " . ($file['file_name'] ?: 'بدون نام') . " - " . $file['download_count'] . " دانلود\n";
            $message .= "📅 " . $file['upload_date'] . "\n";
            $message .= "🔗 /download_" . $file['file_unique_id'] . "\n\n";
        }
        
        apiRequest('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $message,
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => '↩️ بازگشت به پنل', 'callback_data' => 'admin_panel']
                    ]
                ]
            ])
        ]);
    } catch(PDOException $e) {
        logMessage("Error showing file management: " . $e->getMessage());
        apiRequest('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "خطایی در نمایش مدیریت فایل‌ها رخ داده است."
        ]);
    }
}

function processUserMessage($chat_id, $message) {
    $text = isset($message['text']) ? $message['text'] : '';
    
    if (strpos($text, '/help') === 0 || $text == 'راهنما') {
        apiRequest('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "🤖 راهنمای ربات:\n\nبرای دانلود فایل، روی لینک دانلود مربوطه کلیک کنید.\n\nدر صورت وجود هرگونه مشکل با پشتیبانی تماس بگیرید."
        ]);
    } else {
        apiRequest('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "دستور نامعتبر است. برای شروع از /start استفاده کنید."
        ]);
    }
}

function processUserCallback($chat_id, $data) {
    if ($data == 'check_membership') {
        if (checkChannelMembership($chat_id, $chat_id)) {
            apiRequest('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "✅ شما در کانال عضو هستید. اکنون می‌توانید از ربات استفاده کنید."
            ]);
        } else {
            sendChannelJoinMessage($chat_id);
        }
    }
}

function downloadFile($chat_id, $file_unique_id) {
    global $pdo;
    
    try {
        // دریافت اطلاعات فایل
        $stmt = $pdo->prepare("SELECT * FROM files WHERE file_unique_id = ?");
        $stmt->execute([$file_unique_id]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$file) {
            apiRequest('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "❌ فایل مورد نظر یافت نشد."
            ]);
            return;
        }
        
        // افزایش تعداد دانلود
        $stmt = $pdo->prepare("UPDATE files SET download_count = download_count + 1 WHERE file_unique_id = ?");
        $stmt->execute([$file_unique_id]);
        
        // ارسال فایل به کاربر بر اساس نوع فایل
        $params = [
            'chat_id' => $chat_id,
            'caption' => $file['caption'] ?: ''
        ];
        
        switch ($file['type']) {
            case 'document':
                $params['document'] = $file['file_id'];
                if ($file['file_name']) {
                    $params['caption'] .= "\n\n📄 نام فایل: " . $file['file_name'];
                }
                $result = apiRequest('sendDocument', $params);
                break;
                
            case 'photo':
                $params['photo'] = $file['file_id'];
                $result = apiRequest('sendPhoto', $params);
                break;
                
            case 'video':
                $params['video'] = $file['file_id'];
                if ($file['file_name']) {
                    $params['caption'] .= "\n\n🎬 نام فایل: " . $file['file_name'];
                }
                $result = apiRequest('sendVideo', $params);
                break;
                
            case 'audio':
                $params['audio'] = $file['file_id'];
                if ($file['file_name']) {
                    $params['caption'] .= "\n\n🎵 نام فایل: " . $file['file_name'];
                }
                $result = apiRequest('sendAudio', $params);
                break;
                
            case 'voice':
                $params['voice'] = $file['file_id'];
                $result = apiRequest('sendVoice', $params);
                break;
                
            default:
                apiRequest('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => "❌ نوع فایل پشتیبانی نمی‌شود."
                ]);
                return;
        }
        
        // بررسی موفقیت آمیز بودن ارسال فایل
        if (isset($result['ok']) && $result['ok']) {
            apiRequest('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "✅ فایل با موفقیت دانلود شد.\n\nبرای دانلود فایل‌های بیشتر، روی لینک‌های دیگر کلیک کنید."
            ]);
        } else {
            // لاگ خطا
            $error = isset($result['description']) ? $result['description'] : 'خطای ناشناخته';
            logMessage("Error sending file $file_unique_id to $chat_id: " . $error);
            
            apiRequest('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "❌ خطایی در ارسال فایل رخ داده است. لطفا بعدا تلاش کنید.\n\nخطا: " . $error
            ]);
        }
        
    } catch(PDOException $e) {
        logMessage("Error downloading file: " . $e->getMessage());
        apiRequest('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "❌ خطایی در دانلود فایل رخ داده است."
        ]);
    }
}

function sendWelcomeMessage($chat_id, $is_admin = false) {
    $message = "👋 به ربات آپلودر خوش آمدید!\n\n";
    $message .= "از این ربات برای دانلود فایل‌های به اشتراک گذاشته شده استفاده کنید.\n";
    $message .= "برای دریافت فایل، روی لینک دانلود مربوطه کلیک کنید.";
    
    if ($is_admin) {
        $message .= "\n\n👨‍💻 شما به عنوان ادمین شناسایی شدید. برای دسترسی به پنل مدیریت از /admin استفاده کنید.";
    }
    
    apiRequest('sendMessage', [
        'chat_id' => $chat_id,
        'text' => $message
    ]);
}

function sendChannelJoinMessage($chat_id) {
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '📢 عضویت در کانال', 'url' => 'https://t.me/' . REQUIRED_CHANNEL]
            ],
            [
                ['text' => '✅ بررسی عضویت', 'callback_data' => 'check_membership']
            ]
        ]
    ];
    
    apiRequest('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "⚠️ برای استفاده از ربات باید در کانال ما عضو شوید.\n\nپس از عضویت، روی دکمه 'بررسی عضویت' کلیک کنید.",
        'reply_markup' => json_encode($keyboard)
    ]);
}

function saveUser($user_data) {
    global $pdo;
    
    $user_id = $user_data['id'];
    $username = isset($user_data['username']) ? $user_data['username'] : '';
    $first_name = isset($user_data['first_name']) ? $user_data['first_name'] : '';
    $last_name = isset($user_data['last_name']) ? $user_data['last_name'] : '';
    
    try {
        // بررسی وجود کاربر
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $exists = $stmt->fetchColumn();
        
        if ($exists) {
            // به روز رسانی آخرین فعالیت
            $stmt = $pdo->prepare("UPDATE users SET last_active = NOW(), username = ?, first_name = ?, last_name = ? WHERE user_id = ?");
            $stmt->execute([$username, $first_name, $last_name, $user_id]);
        } else {
            // ایجاد کاربر جدید
            $stmt = $pdo->prepare("INSERT INTO users (user_id, username, first_name, last_name) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $username, $first_name, $last_name]);
        }
    } catch(PDOException $e) {
        logMessage("Error saving user: " . $e->getMessage());
    }
}

// اجرای وب‌هوک
logMessage("Update received: " . $input);
?>