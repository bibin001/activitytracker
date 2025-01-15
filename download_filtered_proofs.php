<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $files = $data['files'];

    if (empty($files)) {
        http_response_code(400);
        exit('No files to download.');
    }

    // Initialize the ZIP file
    $zip = new ZipArchive();
    $zipFileName = 'filtered_proofs.zip';
    $zipFilePath = sys_get_temp_dir() . '/' . $zipFileName;

    if ($zip->open($zipFilePath, ZipArchive::CREATE) !== TRUE) {
        exit("Unable to create ZIP file.");
    }

    // Add files to the ZIP
    foreach ($files as $file) {
        $filePath = __DIR__ . '/' . $file['file']; // Ensure correct path to file
        if (file_exists($filePath)) {
            $zip->addFile($filePath, $file['name']); // Add to ZIP with the new name
        }
    }

    $zip->close();

    // Serve the ZIP file for download
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename=' . basename($zipFileName));
    header('Content-Length: ' . filesize($zipFilePath));

    readfile($zipFilePath);

    // Delete the ZIP file after download
    unlink($zipFilePath);
}
?>
