<?php
session_start();

$password = "hi";

if (isset($_POST["password"])) {
    if ($_POST["password"] === $password) {
        $_SESSION["authenticated"] = true;
    } else {
        $error = "Invalid password";
    }
}

if (isset($_GET["logout"])) {
    session_destroy();
    header("Location: index.php");
    exit;
}
?>
<!doctype html>
<html>
  <head>
    <title>File Upload</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  </head>
  <body class="bg-dark text-light">
    <div class="container py-5">
      <div class="card">
        <div class="card-body">
          <h2 class="mb-4"> File Upload </h2> <?php if (!isset($_SESSION["authenticated"])): ?><form method="POST"><input class="form-control mb-3" type="password" name="password" placeholder="Password"><button class="btn btn-primary"> Login </button></form> <?php else: ?><div class="d-flex justify-content-between align-items-center mb-3">
            <h4> Upload a file </h4><a href="?logout" class="btn btn-danger btn-sm"> Logout </a>
          </div>
          <div class="alert alert-info"> Maximum file size: 10GB </div><input class="form-control mb-3" type="file" id="file"><button class="btn btn-success" onclick="upload()"> Upload </button>
          <hr>
          <div id="status" class="mt-3"> Waiting for upload... </div>
          <div class="progress mt-3">
            <div id="progress" class="progress-bar" style="width:0%"> 0% </div>
          </div>
          <div id="result" class="mt-4"></div>
          <script>
            const CHUNK_SIZE = 90 * 1024 * 1024;
            const MAX_SIZE = 10 * 1024 * 1024 * 1024;
            async function upload() {
              const file = document.getElementById("file").files[0];
              if (!file) {
                alert("Select a file");
                return;
              }
              if (file.size > MAX_SIZE) {
                alert("File too large");
                return;
              }
              const uploadId = crypto.randomUUID();
              const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
              for (let i = 0; i < totalChunks; i++) {
                document.getElementById("status").innerHTML = `
Uploading chunk ${i+1}/${totalChunks}
<br>
Please do not close this page.
`;
                const chunk = file.slice(i * CHUNK_SIZE, Math.min(file.size,
                  (i + 1) * CHUNK_SIZE));
                const form = new FormData();
                form.append("uploadId", uploadId);
                form.append("filename", file.name);
                form.append("chunkIndex", i);
                form.append("totalChunks", totalChunks);
                form.append("chunk", chunk, file.name);
                let response = await fetch("upload.php", {
                  method: "POST",
                  body: form
                });
                if (!response.ok) {
                  alert(await response.text());
                  return;
                }
                let percent = Math.round(
                  ((i + 1) / totalChunks) * 100);
                let bar = document.getElementById("progress");
                bar.style.width = percent + "%";
                bar.innerText = percent + "%";
              }
              document.getElementById("status").innerHTML = "Combining file...";
              const finish = await fetch("finish.php", {
                method: "POST",
                headers: {
                  "Content-Type": "application/json"
                },
                body: JSON.stringify({
                  uploadId,
                  filename: file.name,
                  totalChunks
                })
              });
              const url = await finish.text();
              document.getElementById("result").innerHTML = `
<div class="alert alert-success">

Upload complete!

<div class="input-group mt-3"><input 
class="form-control"
id="download"
value="${url}"
readonly><button 
class="btn btn-outline-light"
onclick="copyLink()">

Copy

</button></div></div>
`;
            }

            function copyLink() {
              let input = document.getElementById("download");
              input.select();
              navigator.clipboard.writeText(input.value);
            }
          </script> <?php endif; ?>
        </div>
      </div>
    </div>
  </body>
</html>
