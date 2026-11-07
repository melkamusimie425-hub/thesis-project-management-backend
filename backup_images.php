<?php
/**
 * backup_images.php
 * -----------------
 * Copies every uploaded user avatar from storage into
 *   database/avatars-backup/{user_id}/photo.{ext}
 *
 * This folder travels with the project, so when you copy
 * to another PC or push to GitHub the images are included.
 *
 * Run:  php backup_images.php
 */

$projectRoot  = __DIR__;
$backupDir    = $projectRoot . '/database/avatars-backup';
$storageRoots = [
    $projectRoot . '/storage/app/avatars',          // new location
    $projectRoot . '/storage/app/private/avatars',  // old location
];

// ── Helper ────────────────────────────────────────────────────────────
function copyDir(string $src, string $dst): array {
    $copied = [];
    if (!is_dir($src)) return $copied;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $relative = substr($item->getPathname(), strlen($src) + 1);
        $target   = $dst . DIRECTORY_SEPARATOR . $relative;

        if ($item->isDir()) {
            if (!is_dir($target)) mkdir($target, 0755, true);
        } else {
            // Only copy image files
            $ext = strtolower(pathinfo($item->getFilename(), PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp'], true)) {
                if (!is_dir(dirname($target))) {
                    mkdir(dirname($target), 0755, true);
                }
                copy($item->getPathname(), $target);
                $copied[] = $relative;
            }
        }
    }
    return $copied;
}

// ── Create backup root ────────────────────────────────────────────────
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
    echo "Created: database/avatars-backup/\n";
}

// ── Copy from each storage location ──────────────────────────────────
$totalCopied = [];
foreach ($storageRoots as $src) {
    if (is_dir($src)) {
        $copied = copyDir($src, $backupDir);
        $totalCopied = array_merge($totalCopied, $copied);
        echo "Scanned: " . str_replace($projectRoot . '/', '', $src) . "\n";
    }
}

// ── Report ────────────────────────────────────────────────────────────
if (empty($totalCopied)) {
    echo "\nNo avatar images found yet.\n";
    echo "Upload some profile photos first, then run this script again.\n";
} else {
    echo "\n✓ Copied " . count($totalCopied) . " image(s):\n";
    foreach ($totalCopied as $f) {
        echo "   database/avatars-backup/{$f}\n";
    }
    echo "\nAll images are now inside your project folder.\n";
}

// ── Add a .gitignore so images ARE tracked ─────────────────────────── 
$gitignore = $backupDir . '/.gitignore';
file_put_contents($gitignore,
"# Track all avatar backup images\n" .
"!.gitignore\n" .
"!*/\n" .
"!*/*.jpg\n" .
"!*/*.jpeg\n" .
"!*/*.png\n" .
"!*/*.gif\n" .
"!*/*.webp\n"
);

echo "\n.gitignore written — images will be tracked by Git.\n";

// ── Also export DB again to keep it fresh ────────────────────────────
echo "\nRe-exporting database...\n";

$mysqldumpPaths = [
    'mysqldump',
    'C:\\xampp\\mysql\\bin\\mysqldump.exe',
    'C:\\xampp86\\mysql\\bin\\mysqldump.exe',
];
$mysqldump = null;
foreach ($mysqldumpPaths as $path) {
    if ($path === 'mysqldump') {
        exec('mysqldump --version 2>&1', $o, $c);
        if ($c === 0) { $mysqldump = 'mysqldump'; break; }
    } elseif (file_exists($path)) {
        $mysqldump = '"' . $path . '"';
        break;
    }
}

if ($mysqldump) {
    $sqlFile = $projectRoot . '/database/otpms_wsu.sql';
    $cmd = sprintf(
        '%s --host=127.0.0.1 --user=root --single-transaction --add-drop-table otpms_wsu > "%s" 2>&1',
        $mysqldump, $sqlFile
    );
    exec($cmd, $out, $code);
    if ($code === 0 && file_exists($sqlFile) && filesize($sqlFile) > 100) {
        $kb = number_format(filesize($sqlFile)/1024, 1);
        echo "✓ Database exported: database/otpms_wsu.sql ({$kb} KB)\n";
    } else {
        echo "⚠ DB export failed — run php export_db.php separately.\n";
    }
} else {
    echo "⚠ mysqldump not found — run php export_db.php separately.\n";
}

echo "\n════════════════════════════════════════\n";
echo "BACKUP COMPLETE — project is fully portable.\n";
echo "════════════════════════════════════════\n";
echo "Copy the entire project folder to any PC and run:\n";
echo "  php artisan migrate:fresh --seed\n";
echo "  (or import database/otpms_wsu.sql via phpMyAdmin)\n";
echo "  php import_images.php   ← restores avatars on the new PC\n";
