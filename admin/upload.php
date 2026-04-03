<?php declare(strict_types=1);

require_once __DIR__ . '/../includes/csrf.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    echo json_encode(['error' => '未登录。'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => '请求方法不正确。'], JSON_UNESCAPED_UNICODE);
    exit;
}

csrf_verify();

if (empty($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => '未检测到上传文件。'], JSON_UNESCAPED_UNICODE);
    exit;
}

$file = $_FILES['file'];
if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => '上传失败，错误码：' . (string) ($file['error'] ?? UPLOAD_ERR_NO_FILE)], JSON_UNESCAPED_UNICODE);
    exit;
}

$allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$realMime = $finfo->file((string) $file['tmp_name']);

if (!in_array($realMime, $allowedMimes, true)) {
    http_response_code(415);
    echo json_encode(['error' => '仅支持 JPG、PNG、GIF、WEBP 图片。'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ((int) ($file['size'] ?? 0) > 5 * 1024 * 1024) {
    http_response_code(413);
    echo json_encode(['error' => '文件大小不能超过 5MB。'], JSON_UNESCAPED_UNICODE);
    exit;
}

$uploadDir = __DIR__ . '/../assets/uploads/';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
    http_response_code(500);
    echo json_encode(['error' => '创建上传目录失败。'], JSON_UNESCAPED_UNICODE);
    exit;
}

$extension = match ($realMime) {
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
    default => 'jpg',
};

$filename = 'img_' . bin2hex(random_bytes(16)) . '.' . $extension;
$targetPath = $uploadDir . $filename;

if (!move_uploaded_file((string) $file['tmp_name'], $targetPath)) {
    http_response_code(500);
    echo json_encode(['error' => '保存上传文件失败。'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['url' => 'assets/uploads/' . $filename], JSON_UNESCAPED_UNICODE);
