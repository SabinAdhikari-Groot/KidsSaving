<?php
// Start the session
session_start();

// Destroy all session data to log the user out
session_unset();
session_destroy();

// Redirect to the index.html page after logging out
header("Location: index.html");
exit;
?>