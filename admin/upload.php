<?php declare(strict_types=1);

session_start();

if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    echo json_encode(['error' => '未登录']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => '无效请求']);
    exit;
}

$file = $_FILES['file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => '上传失败，错误码：' . $file['error']]);
    exit;
}

$allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowed_mimes, true)) {
    http_response_code(400);
    echo json_encode(['error' => '仅支持 JPG、PNG、GIF、WEBP 图片']);
    exit;
}

if ($file['size'] > 5 * 1024 * 1024) {
    http_response_code(413);
    echo json_encode(['error' => '文件大小超过 5MB 限制']);
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$real_mime = $finfo->file($file['tmp_name']);
if (!in_array($real_mime, $allowed_mimes, true)) {
    http_response_code(415);
    echo json_encode(['error' => '不支持的文件类型']);
    exit;
}

// 确保上传目录存在。
$upload_dir = __DIR__ . '/../assets/uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// 根据真实 MIME 生成后缀，并使用随机文件名避免碰撞。
$ext = match ($real_mime) {
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
    default => 'jpg',
};
$filename = 'img_' . bin2hex(random_bytes(16)) . '.' . $ext;
$target_path = $upload_dir . $filename;

if (move_uploaded_file($file['tmp_name'], $target_path)) {
    echo json_encode(['url' => 'assets/uploads/' . $filename]);
    exit;
}

http_response_code(500);
echo json_encode(['error' => '保存文件失败']);
