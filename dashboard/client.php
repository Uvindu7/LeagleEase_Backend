<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') { header('Location: ../login.html'); exit; }
echo "<h1>Client Dashboard</h1>";
echo "<p>Welcome " . htmlspecialchars($_SESSION['full_name']) . "</p>";
echo "<a href='../api/logout.php'>Logout</a>";
echo "<a href='../api/logout.php' onclick=\"event.preventDefault();fetch('../api/logout.php',{method:'POST'}).then(()=>location='../login.html')\">Logout</a>";echo "Welcome, " . htmlspecialchars($_SESSION['full_name']) . "! This is the client dashboard.";