<?php

session_start();

if (!isset($_SESSION["authenticated"])) {
    http_response_code(403);
    die("Unauthorized");
}
header("Content-Type: text/plain");

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    die("Invalid request");
}

$uploadId = preg_replace('/[^A-Za-z0-9_-]/', '', $data["uploadId"]);
$filename = basename($data["filename"]);
$totalChunks = intval($data["totalChunks"]);

if (!$uploadId || !$filename || !$totalChunks) {
    die("Missing parameters");
}
$seed = random_int(100000, 999999);
$tempDir = __DIR__ . "/$uploadId";
$outputDir = __DIR__ . "/" . $seed;

if (!is_dir($tempDir)) {
    die("Upload not found");
}

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

$safeName = $filename;

$outputFile = "$outputDir/$safeName";

$out = fopen($outputFile, "wb");

if (!$out) {
    die("Could not create output file");
}

for ($i = 0; $i < $totalChunks; $i++) {

    $chunkFile = "$tempDir/$i.part";

    if (!file_exists($chunkFile)) {
        fclose($out);
        die("Missing chunk $i");
    }

    $chunk = fopen($chunkFile, "rb");

    while (!feof($chunk)) {
        fwrite($out, fread($chunk, 1024 * 1024));
    }

    fclose($chunk);

    unlink($chunkFile);
}

fclose($out);

rmdir($tempDir);

$url = "https://yourdomain.com/files/" . $seed . "/" . 
       rawurlencode($safeName);

echo $url;
