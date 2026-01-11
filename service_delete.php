<?php
require_once __DIR__ . '/app/layout.php';
$user = require_login();
$pdo  = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);

    if ($id > 0) {
        // 1. Fetch Service to check existence and permission
        $st = $pdo->prepare("SELECT provider_id, category FROM services WHERE id=?");
        $st->execute([$id]);
        $s = $st->fetch();

        if ($s) {
            // Permission Check: Only Admin OR the Creator (Provider) can delete
            if (is_admin() || $s['provider_id'] == $user['id']) {
                
                // 2. Delete Images from Disk
                $imgSt = $pdo->prepare("SELECT file_path FROM service_images WHERE service_id=?");
                $imgSt->execute([$id]);
                $images = $imgSt->fetchAll(PDO::FETCH_COLUMN);

                foreach ($images as $path) {
                    $fullPath = __DIR__ . '/' . $path;
                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                }

                // 3. Delete from DB (Cascade will handle service_images table rows)
                $pdo->prepare("DELETE FROM services WHERE id=?")->execute([$id]);

                // 4. Redirect
                header("Location: /category.php?cat=" . $s['category']);
                exit;
            } else {
                render_header("Error", $user);
                echo "<div class='card'><h1>Access Denied</h1><p>You do not have permission to delete this service.</p></div>";
                render_footer();
                exit;
            }
        }
    }
}

// Fallback if accessed directly or ID invalid
header("Location: /");
