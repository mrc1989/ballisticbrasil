<?php
// =====================================================
// Proxy HTTP ? HTTPS (substituto do AllOrigins)
// =====================================================

// ===== CONFIGURAÇÕES =====
$allowed_domains = [
    '136.248.68.132',                 // VPS (ranking TXT)
    'api.steampowered.com',           // Steam API
    'steamcdn-a.akamaihd.net'         // Avatares Steam
];

$timeout = 10;

// ===== CORS =====
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: *");

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ===== VALIDAÇÃO =====
if (!isset($_GET['url'])) {
    http_response_code(400);
    exit('Missing url parameter');
}

$url = urldecode($_GET['url']);
$parsed = parse_url($url);

if (!$parsed || !isset($parsed['host'])) {
    http_response_code(400);
    exit('Invalid URL');
}

// ===== SEGURANÇA (whitelist + subdomínios) =====
$allowed = false;
foreach ($allowed_domains as $domain) {
    if (
        $parsed['host'] === $domain ||
        str_ends_with($parsed['host'], '.' . $domain)
    ) {
        $allowed = true;
        break;
    }
}

if (!$allowed) {
    http_response_code(403);
    exit('Domain not allowed');
}

// ===== REQUEST =====
$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_CONNECTTIMEOUT => $timeout,
    CURLOPT_TIMEOUT => $timeout,
    CURLOPT_USERAGENT => 'RankingProxy/1.0 (+https://ballisticbrasil)',
]);

$response = curl_exec($ch);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($response === false) {
    http_response_code(502);
    exit('Fetch failed: ' . curl_error($ch));
}

curl_close($ch);

// ===== HEADERS DE RESPOSTA =====
header(
    "Content-Type: " .
    ($contentType ?: 'text/plain') .
    "; charset=utf-8"
);

http_response_code($status);

// ===== OUTPUT =====
echo $response;
