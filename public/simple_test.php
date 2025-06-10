<?php
echo "<h1>Simple PHP Test</h1>";
echo "<p>If you see this, PHP is working and .htaccess is not interfering!</p>";
echo "<p>REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p>SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "</p>";
echo "<p><a href='debug_files.php'>Try Debug Files</a></p>";
echo "<p><a href='index.php'>Try Main App</a></p>";
?>