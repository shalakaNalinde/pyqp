<?php
$uploadDir = 'uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['paper'])) {
    $file = $_FILES['paper'];
    $fileName = basename($file['name']);
    $targetPath = $uploadDir . $fileName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        echo "<h2 style='text-align:center;'>File uploaded successfully!</h2>";
        echo "<p style='text-align:center;'><a href='index.html'>Go Back</a></p>";
    } else {
        echo "Error uploading file.";
    }
}
?>