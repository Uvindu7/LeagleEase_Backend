<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lawyer') { header('Location: ../login.html'); exit; }
echo "<h1>Lawyer Dashboard</h1>";
echo "<p>Welcome " . htmlspecialchars($_SESSION['full_name']) . "</p>";
echo "<p>Status: " . ((int)$_SESSION['is_verified'] ? 'Verified' : 'Pending') . "</p>";
echo "<a href='../api/logout.php'>Logout</a>";

