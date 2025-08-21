<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ربات آپلودر فادلتا - راهنمای استفاده</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0088cc;
            --secondary-color: #24a4e1;
            --accent-color: #ff7b31;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #28a745;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #0088cc 0%, #24a4e1 100%);
            color: #333;
            line-height: 1.6;
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }
        
        header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
            position: relative;
        }
        
        .header-content {
            position: relative;
            z-index: 1;
        }
        
        header h1 {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        header p {
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto;
            opacity: 0.9;
        }
        
        .telegram-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            display: inline-block;
            background: white;
            width: 80px;
            height: 80px;
            line-height: 80px;
            border-radius: 50%;
            color: var(--primary-color);
            animation: pulse 2s infinite;
        }
        
        .badges {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            backdrop-filter: blur(5px);
        }
        
        .content {
            padding: 40px;
        }
        
        section {
            margin-bottom: 40px;
        }
        
        h2 {
            color: var(--primary-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
            position: relative;
        }
        
        h2:after {
            content: '';
            position: absolute;
            bottom: -2px;
            right: 0;
            width: 60px;
            height: 2px;
            background: var(--accent-color);
        }
        
        h3 {
            color: var(--secondary-color);
            margin: 25px 0 15px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        h3 i {
            color: var(--accent-color);
        }
        
        p {
            margin-bottom: 15px;
            text-align: justify;
        }
        
        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            border-radius: 8px;
            padding: 16px;
            margin: 15px 0;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            direction: ltr;
            text-align: left;
            border-left: 4px solid var(--accent-color);
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .feature {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-right: 4px solid var(--primary-color);
            transition: transform 0.3s ease;
        }
        
        .feature:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .steps {
            list-style-type: none;
            counter-reset: step-counter;
        }
        
        .steps li {
            background: #f8f9fa;
            margin-bottom: 15px;
            padding: 20px 20px 20px 60px;
            border-radius: 8px;
            position: relative;
            border: 1px solid #e9ecef;
        }
        
        .steps li:before {
            content: counter(step-counter);
            counter-increment: step-counter;
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            width: 30px;
            height: 30px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .command-list {
            list-style-type: none;
        }
        
        .command-list li {
            background: #f8f9fa;
            margin-bottom: 10px;
            padding: 12px 15px;
            border-radius: 6px;
            border-right: 3px solid var(--secondary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .command-list li i {
            color: var(--accent-color);
        }
        
        .note {
            background: #fff3cd;
            border-right: 4px solid #ffc107;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        
        footer {
            text-align: center;
            padding: 30px;
            background: var(--light-color);
            color: #666;
            border-top: 1px solid #e9ecef;
        }
        
        .btn {
            display: inline-block;
            background: var(--primary-color);
            color: white;
            padding: 12px 25px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: bold;
            margin: 10px 5px;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: var(--secondary-color);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        @media (max-width: 768px) {
            .content {
                padding: 20px;
            }
            
            .features {
                grid-template-columns: 1fr;
            }
            
            header h1 {
                font-size: 2rem;
            }
            
            header p {
                font-size: 1rem;
            }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="header-content">
                <i class="fab fa-telegram telegram-icon"></i>
                <h1>ربات آپلودر فادلتا</h1>
                <p> یک ربات  برای آپلود و مدیریت فایل‌ها در تلگرام با آپدیت های منظم</p>
                <div class="badges">
                    <span class="badge">PHP</span>
                    <span class="badge">Telegram API</span>
                    <span class="badge">MYSQL</span>
                    <span class="badge">RELEASE : 1.0</span>
                </div>
            </div>
        </header>
        
        <div class="content">
            <section id="introduction">
                <h2>معرفی ربات</h2>
                <p>
                    ربات آپلودر تلگرام یک ابزار قدرتمند و کاربردی است که به کاربران امکان آپلود، مدیریت و به اشتراک‌گذاری فایل‌ها را در تلگرام می‌دهد. این ربات با قابلیت‌های پیشرفته و امنیت بالا، تجربه‌ای بی‌نظیر از مدیریت فایل را ارائه می‌دهد.
                </p>
                <p>
                    این سورس به صورت منظم آ|دیت خواهد شد و در هر آپدیت امکانات جدید و قابل توجهی افزوده خواهند شد
                </p>
            </section>
            
            <section id="features">
                <h2>ویژگی‌های اصلی</h2>
                <div class="features">
                    <div class="feature">
                        <h3><i class="fas fa-upload"></i> آپلود فایل</h3>
                        <p>امکان آپلود انواع فایل‌ها با حجم بالا به صورت مستقیم در تلگرام</p>
                    </div>
                    <div class="feature">
                        <h3><i class="fas fa-download"></i> دانلود فایل</h3>
                        <p>دانلود فایل‌های آپلود شده با لینک مستقیم و بدون محدودیت</p>
                    </div>
                    <div class="feature">
                        <h3><i class="fas fa-folder"></i> مدیریت فایل‌ها</h3>
                        <p>دسته‌بندی، جستجو و سازماندهی فایل‌ها در پوشه‌های مختلف</p>
                    </div>
                    <div class="feature">
                        <h3><i class="fas fa-chart-pie"></i>آمار و گزارش</h3>
                        <p>نمایش آمار گزارشات از بخش مربوطه</p>
                    </div>
                    <div class="feature">
                        <h3><i class="fas fa-external-link-alt"></i>آپدیت منظم</h3>
                        <p>انتشار منظم آ|دیت ها و ارتقا نسخه</p>
                    </div>
                    <div class="feature">
                        <h3><i class="fas fa-shield-alt"></i> امنیت بالا</h3>
                        <p>استفاده از رمزنگاری پیشرفته برای حفاظت از فایل‌های کاربران</p>
                    </div>
                </div>
            </section>
            
            <section id="installation">
                <h2>نصب و راه‌اندازی</h2>
                <p>برای نصب و راه‌اندازی ربات، مراحل زیر را دنبال کنید:</p>
                
                <ol class="steps">
                    <li>
                        <strong>سورس ربات را دریافت کنید</strong>
                        <p>ابتدا سورس کد ربات را از کانال تلگرام یا گیتهاب دانلود کنید:</p>
                        <div class="code-block">
Telegram Channel : Fadelta_source
<br>
Project Github : https://github.com/FadeltaTeam/Fadelta_Uploader
                        </div>
                    </li>
                    <li>
                        <strong>پیکربندی ربات</strong>
                        <p>فایل config.php را ویرایش کرده و اطلاعات مورد نیاز را وارد کنید:</p>
                        <div class="code-block">
// تنظیمات ربات
<br>
define('BOT_TOKEN', 'Token'); // توکن ربات
<br>
define('BOT_USERNAME', 'bot_username'); //یوزر نیم ربات بدون @
<br>
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');
<br>

// تنظیمات دیتابیس
<br>
define('DB_HOST', 'localhost');
<br>
define('DB_NAME', 'name'); // اسم دیتابیس
<br>
define('DB_USER', 'username'); // یوزرنیم دیتابیس
<br>
define('DB_PASS', 'password'); // پسورد دیتابیس
<br>

// کانال اجباری
<br>
define('REQUIRED_CHANNEL', '@Channel_username');
                        </div>
                    </li>
                    <li>
                        <strong>نصب دیتابیس</strong>
                        <p>ابتدا یک دیتابیس بسازید و سپس فایل database.sql را ایمپورت کنید</p>
                        <div class="code-block">
                            فایل database.sql در سورس موجود می باشد
                        </div>
                    </li>
                    <li>
                        <strong>اجرای ربات</strong>
                        <p>در نهایت روی فایل index.php وبهوک ست کنید. مانند زیر:</p>
                        <div class="code-block">
https://api.telegram.org/botTOKEN/setwebhook?url=index.php_file_address

                        </div>
                    </li>
                </ol>
                
                <div class="note">
                    <p><strong>توجه:</strong> قبل از اجرای ربات، مطمئن شوید که MYSQL روی سیستم شما  در حال اجرا است.</p>
                </div>
            </section>
            
            <section id="usage">
                <h2>راهنمای استفاده</h2>
                <p>پس از راه‌اندازی ربات، می‌توانید از دستورات زیر استفاده کنید:</p>
                
                <ul class="command-list">
                    <li><i class="fas fa-terminal"></i> <strong>/start</strong> - شروع کار با ربات و نمایش منوی اصلی</li>
                    <li><i class="fas fa-terminal"></i> <strong>/admin</strong> -  ورود به پنل ادمین</li>
                    <li><i class="fas fa-terminal"></i> <strong>/help</strong> - راهنمای استفاده از ربات</li>
                </ul>
                
                <h3>نمونه استفاده</h3>
                <p>برای آپلود فایل ابتدا دستور /admin را ارسال کنید سپس از بخش آپلود فایل اقدام به آ|لود فایل مد نظر خود کنید</p>
            </section>
            
            <section id="support">
                <h2>پشتیبانی</h2>
                <p>در صورت بروز هرگونه مشکل یا داشتن سوال و پیشنهاد، می‌توانید از طریق راه‌های زیر با ما در ارتباط باشید:</p>
                
                <ul class="command-list">
                    <li><i class="fas fa-envelope"></i> <strong>ایمیل:</strong> fadeltasourceteam@gmail.com</li>
                    <li><i class="fab fa-telegram"></i> <strong>کانال تلگرام:</strong> @fadelta_source</li>
                    <li><i class="fab fa-github"></i> <strong>گیت‌هاب:</strong> github.com/FadeltaTeam/Fadelta_Uploader</li>
                </ul>
            </section>
        </div>
        
        <footer>
            <p>ربات آپلودر تلگرام - نسخه 1.0</p>
            <p>© 2025 - تمامی حقوق محفوظ است</p>
            <div>
                <a href="https://github.com/FadeltaTeam/Fadelta_Uploader" class="btn"><i class="fab fa-github"></i> مشاهده در گیت‌هاب</a>
                <a href="https://t.me/fadelta_source" class="btn" style="background: var(--accent-color);"><i class="fab fa-telegram"></i>ورود به کانال</a>
            </div>
        </footer>
    </div>
</body>
</html>
