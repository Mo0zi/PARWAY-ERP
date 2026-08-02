<?php
require_once __DIR__ . '/config/db.php';
session_unset();
session_destroy();
header("Location: " . base_url('index.php'));
exit();
