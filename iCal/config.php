<?php
// Email Setup
define("IMAP_USER", "user@mail.com");
define("IMAP_PASS", 'password');
define("IMAP_HOST", "imap.mail.com");
define("IMAP_POP3", "IMAP"); // Or POP3 (for pop3 protocol replace "IMAP" with "POP3")
define("MESSAGE_HOW_LONG", 1000); // Define here how many symbols you`d like to have stored into your DB
define("MAGIC_SYMBOL", "##"); // The subject`s special trigger that will control whether just to update activity or insert the whole data into DB
// Database Setup
define('DB_HOST', 'localhost');
define('DB_USER', 'dbuser');
define('DB_PASS', 'dbpass');
define('DB_NAME', 'dbname');
define('SAVE_TO_TABLE', 'activity'); // Here we define the table, where emails/activity should be stored
include("imaper.class.php");
?>
