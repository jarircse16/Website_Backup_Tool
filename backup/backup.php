<?php
error_reporting(0);
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: login.php"); // Redirect to the login page if not authenticated
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session
    header("Location: login.php"); // Redirect to the login page after logout
    exit();
}

// Define the root directory as the current directory of the PHP script
$rootDirectory = __DIR__;

// Function to list all subdirectories in a directory
function listSubdirectories($dir) {
    $subdirs = [];
    if ($handle = opendir($dir)) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != ".." && is_dir($dir . '/' . $entry)) {
                $subdirs[] = $entry;
            }
        }
        closedir($handle);
    }
    return $subdirs;
}

// Check if form is submitted
if (isset($_POST['backup'])) {
    // Get selected folders from form
    $selectedFolders = $_POST['folders'];

    if (!empty($selectedFolders)) {
        // Create a temporary directory to store the backup
        $tempDir = sys_get_temp_dir() . '/' . uniqid('backup_') . '/';
        mkdir($tempDir);

        // Create an array to store the folder names
        $folderNames = [];

        // Add selected folders to the zip archive
        $zip = new ZipArchive();
        $zipFileName = 'backup_' . date('YmdHis') . '.zip';
        $zipFilePath = $tempDir . $zipFileName;
        if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE) {
            foreach ($selectedFolders as $folder) {
                $folderPath = $rootDirectory . '/' . $folder;
                if (is_dir($folderPath)) {
                    $files = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($folderPath),
                        RecursiveIteratorIterator::LEAVES_ONLY
                    );

                    foreach ($files as $name => $file) {
                        if (!$file->isDir()) {
                            $filePath = $file->getRealPath();
                            $relativePath = substr($filePath, strlen($rootDirectory) + 1);
                            // Use the original file name in the archive
                            $zip->addFile($filePath, $relativePath);
                        }
                    }

                    // Store the folder name for later use
                    $folderNames[] = $folder;
                }
            }
            $zip->close();

            // Send the zip file to the user for download
            header('Content-Type: application/zip');
            // Use the first folder name as the zip file name
            header('Content-disposition: attachment; filename=' . $folderNames[0] . '.zip');
            header('Content-Length: ' . filesize($zipFilePath));
            readfile($zipFilePath);

            // Clean up temporary files and directories
            unlink($zipFilePath);
            rmdir($tempDir);
            exit;
        } else {
            $error = 'Unable to create the zip file.';
        }
    } else {
        $error = 'Please select one or more folders to back up.';
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Folder Backup</title>
</head>
<body>
    <h1>Select Folders to Back Up</h1>
    <a href="?logout=true">Logout</a> <!-- Add a logout link -->
    <?php if (isset($error)) : ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <form method="post">
        <?php
        $subdirectories = listSubdirectories($rootDirectory);
        foreach ($subdirectories as $folder) :
        ?>
            <label>
                <input type="checkbox" name="folders[]" value="<?php echo $folder; ?>">
                <?php echo $folder; ?>
            </label>
            <br>
        <?php endforeach; ?>

        <button type="submit" name="backup">Backup Selected Folders</button>
    </form>
</body>
</html>
