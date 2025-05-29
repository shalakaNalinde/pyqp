<?php
session_start(); // Using sessions for flash messages after upload

$uploadDir = 'uploads/'; // Directory to store uploaded files
$message = ''; // For displaying feedback to the user
$searchResults = []; // To store files found by search
$searchTermDisplay = ''; // To display the current search term in the input field
$filesToList = []; // Array to hold files to be displayed (either all or search results)
<?php

// --- 1. Ensure upload directory exists ---
// This attempts to create the directory if it doesn't exist.
// For production, ensure this directory is writable by the web server user.
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        // Critical error if directory cannot be created.
        // 0777 is for ease of local development; use more restrictive permissions in production (e.g., 0755).
        die("Failed to create upload directory '{$uploadDir}'. Please check permissions or create it manually.");
    }
}

// --- 2. Handle File Upload (HTTP POST request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['paper'])) {
    $file = $_FILES['paper'];

    // Check for upload errors
    if ($file['error'] === UPLOAD_ERR_OK) {
        $fileName = basename($file['name']); // Get the original filename
        // Basic sanitization: replace characters not safe for filenames
        $safeFileName = preg_replace("/[^a-zA-Z0-9.-]/", "", $fileName);
        $targetPath = $uploadDir . $safeFileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Store success message in session to show after redirect
            $_SESSION['message'] = "<p style='color: green; font-weight: bold;'>File '<strong>" . htmlspecialchars($safeFileName) . "</strong>' uploaded successfully!</p>";
        } else {
            // Error moving file
            $_SESSION['message'] = "<p style='color: red; font-weight: bold;'>Error uploading file. Could not move file to the destination. Check server logs and permissions for '{$uploadDir}'.</p>";
        }
    } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) { // If a file was selected but an error occurred
        // Provide more specific error messages
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => "The uploaded file exceeds the server's maximum file size limit (upload_max_filesize).",
            UPLOAD_ERR_FORM_SIZE  => "The uploaded file exceeds the maximum file size specified in the HTML form.",
            UPLOAD_ERR_PARTIAL    => "The uploaded file was only partially uploaded.",
            UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder for uploads on the server.",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk on the server.",
            UPLOAD_ERR_EXTENSION  => "A PHP extension stopped the file upload.",
        ];
        $errorMsg = isset($uploadErrors[$file['error']]) ? $uploadErrors[$file['error']] : "Unknown upload error occurred.";
        $_SESSION['message'] = "<p style='color: red; font-weight: bold;'>Upload Error: " . $errorMsg . "</p>";
    }
    // Redirect after POST to prevent form resubmission on page refresh
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Retrieve and clear any session message (e.g., after upload)
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// --- 3. Handle Search and File Listing (HTTP GET request) ---
// This section runs on every page load, including after a search or by default.
$isSearchPerformed = false;
if (isset($_GET['search'])) { // Check if a search query is present in the URL
    $searchTerm = trim($_GET['search']);
    $searchTermDisplay = htmlspecialchars($searchTerm); // For displaying in the search box
    $isSearchPerformed = true;

    if (!empty($searchTerm)) {
        $allFiles = scandir($uploadDir); // Scan the uploads directory
        if ($allFiles === false) {
            $message .= "<p style='color: red;'>Error: Could not read the upload directory.</p>";
        } else {
            foreach ($allFiles as $file) {
                // Ignore '.' (current dir) and '..' (parent dir)
                if ($file !== '.' && $file !== '..') {
                    // Case-insensitive search: check if search term is part of the filename
                    if (stripos($file, $searchTerm) !== false) {
                        $filesToList[] = $file; // Add matching file to the list
                    }
                }
            }
            if (empty($filesToList)) {
                $message .= "<p>No files found matching '<strong>" . $searchTermDisplay . "</strong>'.</p>";
            }
        }
    } else {
        // If search term is empty, list all files (or you can show a message like "Please enter a search term")
        $message .= "<p>Search term was empty. Showing all files.</p>";
        $allFiles = scandir($uploadDir);
        if ($allFiles === false) {
            $message .= "<p style='color: red;'>Error: Could not read the upload directory.</p>";
        } else {
            foreach ($allFiles as $file) {
                if ($file !== '.' && $file !== '..') {
                    $filesToList[] = $file;
                }
            }
        }
    }
} else {
    // Default behavior: If no search is performed, list all files
    $allFiles = scandir($uploadDir);
    if ($allFiles === false) {
        $message .= "<p style='color: red;'>Error: Could not read the upload directory.</p>";
    } else {
        foreach ($allFiles as $file) {
            if ($file !== '.' && $file !== '..') {
                $filesToList[] = $file;
            }
        }
        if (empty($filesToList) && !$isSearchPerformed) { // Show only if not an empty search result
             $message .= "<p>No files have been uploaded yet. Use the form below to upload your first file.</p>";
        }
    }
}

?>