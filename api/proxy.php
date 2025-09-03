<?php
// /api/proxy.php (Fixed Version)
// 版权所有：小奏 (https://blog.mofuc.cn/)
// 本软件是小奏独立开发的开源项目，二次开发请务必保留原作者的版权信息。
// 博客: https://blog.mofuc.cn/
// B站: https://space.bilibili.com/63216596
// GitHub: https://github.com/Meguminlove/qingjiu-auth-frontend
// --- 动态配置加载 ---
// [FIXED] Corrected path to config.php, which is in the parent directory.
if (!file_exists(__DIR__ . '/../config.php')) {
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['code' => 503, 'message' => '系统尚未安装，请先访问 install.php 进行安装。']);
    exit;
}
require_once __DIR__ . '/../config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['code' => 500, 'message' => '数据库连接失败。']);
    exit;
}
$conn->set_charset('utf8mb4');

$settings = [];
$result = $conn->query("SELECT setting_key, setting_value FROM settings");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}
$conn->close();

$API_BASE_URL = $settings['api_url'] ?? 'https://www.79tian.com/api/v1';
$API_KEY = $settings['api_key'] ?? '';
// --- 动态配置加载结束 ---

header('Content-Type: application/json; charset=utf-8');

// --- [IMPROVED] Internal endpoint to provide local settings to the frontend ---
if (isset($_GET['endpoint']) && $_GET['endpoint'] === '/local/site-info') {
    $response_data = [
        'code' => 200,
        'message' => '获取本地配置成功',
        'data' => [
            // Return all necessary settings from the local database
            'system_announcement' => $settings['system_announcement'] ?? '欢迎使用！',
            'download_url' => $settings['download_url'] ?? '',
            'update_version' => $settings['update_version'] ?? 'v1.0.0',
            'update_log' => $settings['update_log'] ?? '暂无更新日志。',
        ],
    ];
    echo json_encode($response_data);
    exit;
}


// Check if the target API endpoint is provided
if (!isset($_GET['endpoint'])) {
    http_response_code(400);
    echo json_encode(['code' => 400, 'message' => 'Target endpoint not specified.']);
    exit;
}

// Get the target endpoint and other query parameters
$endpoint = $_GET['endpoint'];
$queryParams = $_GET;
unset($queryParams['endpoint']);

// Rebuild the query string
$queryString = http_build_query($queryParams);
$targetUrl = rtrim($API_BASE_URL, '/') . $endpoint . ($queryString ? '?' . $queryString : '');

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $targetUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, 'QingJiu-PHP-Proxy/1.3-Fixed');

// Prepare request headers
$headers = [
    'X-API-Key: ' . $API_KEY,
    'Content-Type: application/json',
    'Accept: application/json',
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Handle POST or PUT requests
$method = $_SERVER['REQUEST_METHOD'];
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

if ($method === 'POST' || $method === 'PUT') {
    $requestBody = file_get_contents('php://input');
    if ($requestBody) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
    }
}

// Execute cURL request
$response_body = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// Handle the request result
if ($response_body === false) {
    http_response_code(500);
    echo json_encode(['code' => 500, 'message' => 'Proxy request failed.', 'error' => $curl_error]);
} else {
    http_response_code($http_code);
    echo $response_body;
}
