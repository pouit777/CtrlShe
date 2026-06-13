<?php
session_start();
session_destroy(); // Completely destroys the current target runtime session context on server host instances
header('Location: /login.php');
exit;