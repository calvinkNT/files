<?php

session_start();

if (!isset($_SESSION["authenticated"])) {
    http_response_code(403);
    die("Unauthorized");
}

header("Content-Type: text/plain");

if (!isset($_FILES['chunk'])) {
    die("No chunk received\n" . print_r($_FILES, true));
}

$uploadId = preg_replace('/[^A-Za-z0-9_-]/', '', $_POST['uploadId'] ?? '');
$chunkIndex = intval($_POST['chunkIndex'] ?? -1);

if (!$uploadId || $chunkIndex < 0) {
    die("Invalid parameters\n");
}

$dir = __DIR__ . "/$uploadId";

if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

$destination = "$dir/$chunkIndex.part";

if (!move_uploaded_file($_FILES['chunk']['tmp_name'], $destination)) {
    die("Failed to move chunk\n");
}

echo "Saved chunk $chunkIndex\n";
