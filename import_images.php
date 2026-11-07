<?php
/**
 * import_images.php
 * -----------------
 * Restores avatar images from database/avatars-backup/
 * back into storage/app/avatars/ on the new PC.
 *
 * Run this AFTER copying the project to a new PC:
 *   php import_images.php
 */

$projectRoot = __DIR__;
$backupDir   = $projectRoot . '/database/avatars-backup';
$targets     = [
    $projectRoot . '/storage/app/avatars',
    $projectRoot . '/storage/app/private/avatars',
];

if (!is_dir($backupDir)) {
    echo "No backup found at database/avatars-backup/\n";
    echo "Nothing to restore.\n";
    exit;
}

$count = 0;

// Copy from backup into BOTH storage locations so all code paths work
foreach ($targets as $target) {

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($backupDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $relative = substr($item->getPathname(), strlen($backupDir) + 1);
        $dest     = $target . DIRECTORY_SEPARATOR . $relative;

        if ($item->isDir()) {
            if (!is_dir($dest)) mkdir($dest, 0755, true);
        } else {
            $ext = strtolower(pathinfo($item->getFilename(), PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp'], true)) {
                if (!is_dir(dirname($dest))) {
                    mkdir(dirname($dest), 0755, true);
                }
                copy($item->getPathname(), $dest);
                $count++;
            }
        }
    }
}

if ($count === 0) {
    echo "No images found in backup folder.\n";
} else {
    echo "✓ Restored " . ($count / count($targets)) . " avatar image(s) to storage.\n";
    echo "All user profile photos are now available.\n";
}

echo "\nDone. You can now start the server:\n";
echo "  php -c php.ini artisan serve --host=127.0.0.1 --port=8000\n";
