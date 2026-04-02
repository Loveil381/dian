<?php
declare(strict_types=1);

$errorCode = (int) ($errorCode ?? 500);
$errorMessage = (string) ($errorMessage ?? ($errorCode === 404 ? '你访问的页面不存在。' : '页面暂时不可用，请稍后再试。'));
$pageTitle = ($errorCode === 404 ? '页面未找到' : '系统异常') . ' - 魔女小店';
$pageDescription = '魔女小店错误页面';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="assets/css/site.css">
</head>
<body class="auth-page">
<main class="page-shell">
    <section class="auth-card auth-card--wide">
        <h1 class="auth-title"><?php echo $errorCode === 404 ? '页面未找到' : '系统开了个小差'; ?></h1>
        <p class="auth-description"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></p>
        <div class="auth-links">
            <a class="auth-link" href="index.php">返回首页</a>
        </div>
    </section>
</main>
</body>
</html>
