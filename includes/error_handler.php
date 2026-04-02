<?php declare(strict_types=1);

function shop_error_page(int $code, string $message): void
{
    http_response_code($code);
    $safe_message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $code; ?> - 魔女小店</title>
    <style>
        body {
            margin: 0;
            font-family: "Microsoft YaHei", sans-serif;
            background: linear-gradient(180deg, #fffaf0 0%, #f8fafc 100%);
            color: #1f2937;
        }

        .error-shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .error-card {
            width: 100%;
            max-width: 560px;
            background: #ffffff;
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
            text-align: center;
        }

        .error-code {
            font-size: 48px;
            font-weight: 700;
            color: #dc2626;
            margin-bottom: 12px;
        }

        .error-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .error-message {
            font-size: 15px;
            line-height: 1.8;
            color: #4b5563;
            margin-bottom: 24px;
        }

        .error-link {
            display: inline-block;
            padding: 12px 22px;
            border-radius: 999px;
            background: #2563eb;
            color: #ffffff;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="error-shell">
        <div class="error-card">
            <div class="error-code"><?php echo $code; ?></div>
            <div class="error-title">页面暂时无法访问</div>
            <div class="error-message"><?php echo $safe_message; ?></div>
            <a class="error-link" href="index.php">返回首页</a>
        </div>
    </div>
</body>
</html>
<?php
    exit;
}
