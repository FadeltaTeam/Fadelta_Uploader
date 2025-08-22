<?php
require_once 'config.php';

// اجرای بررسی و حذف مدیاهای منقضی شده
checkAndDeleteExpiredMedia();

// لاگ کردن اجرای کرون
logMessage("Cron job executed successfully at " . date('Y-m-d H:i:s'));
?>