<?php
// pages/php/login.php

// 檢查是否已登入
if (isset($_SESSION['user_id'])) {
    header('Location: /tracking');
    exit;
}

// 顯示錯誤訊息（如果有）
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

return [
    'error' => $error
];
?>