<?php
// Shared header include — validates session + provides consistent fast-loading head
session_start();
require_once __DIR__ . '/db.php';

// Validate session: if user_id is set, verify it still exists in DB
if (isset($_SESSION['user_id'])) {
    $check = $pdo->prepare("SELECT id, role FROM users WHERE id = ?");
    $check->execute([$_SESSION['user_id']]);
    $validUser = $check->fetch();
    if (!$validUser) {
        // User was deleted or DB was re-imported — clear stale session
        session_unset();
        session_destroy();
        session_start();
    } else {
        // Keep role in sync
        $_SESSION['user_role'] = $validUser['role'];
    }
}
// Release session file lock so concurrent AJAX requests (e.g. fetchListings)
// are not blocked waiting for this page's session lock to clear.
session_write_close();
?>
