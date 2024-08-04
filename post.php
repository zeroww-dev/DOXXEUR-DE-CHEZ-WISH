<?php 
header('Content-Disposition: inline; filename="post.php"');
session_start();

// This is the kill switch. Add a file to your webroot named "DISABLEPOST" to use. Use it wisely.
if (file_exists("DISABLEPOST")) {
    die('Posting is currently disabled due to an attack, AIDS, or some other reason.');
}

// Check and handle missing session variables
if (!isset($_SESSION["name"]) || !isset($_SESSION["dox"]) || !isset($_SESSION["hidden"])) {
    die("Required session variables are missing.");
}

// Handle POST data and initialize variables
$nameField = isset($_POST[$_SESSION["name"]]) ? $_POST[$_SESSION["name"]] : null;
$doxField = isset($_POST[$_SESSION["dox"]]) ? $_POST[$_SESSION["dox"]] : null;
$hidden = isset($_POST[$_SESSION["hidden"]]) ? $_POST[$_SESSION["hidden"]] : null;

// Remove CAPTCHA-related checks
// $captcha = isset($_POST[$_SESSION["captcha"]]) ? $_POST[$_SESSION["captcha"]] : null;

// Check if hidden field is filled
if ($hidden != "") {
    exit;
}

// Perform validation on $nameField and $doxField
if (strlen($nameField) > 30) {
    die("The name of a post cannot exceed 30 characters. Go <a href=\"postdox.php\">back to the index page</a>, repaste the dox, and try again with a shorter filename. Hitting your back button and trying again will just result in another error.");
}

// Validate $doxField contents
$expectedText = 'DOX go here. This is not your personal slam page, nor is it a page on which to brag about having 0wned someone, or to complain that they 0wned you. Post whatever info you have and SHUT UP.';
if (stripos($doxField, $expectedText) !== false) {
    die("Use your back button and remove the filler text from the body, retard. - staff");
}

// Sanitize the name field
$nameField = preg_replace("/[^A-Za-z0-9_]+/", "_", $nameField);
$nameField = trim($nameField, '_');
$nameField = preg_replace('/[_]+/', '_', $nameField);
if (strpos($doxField, '<script') !== false) {
    $nameField = "-mirror-" . $nameField;
}

// Check if file already exists
if (file_exists("dox/" . $nameField . ".txt")) {
    die("An entry with this <a href=dox/" . $nameField . ">already exists.</a>");
}

// Write data to file
$fileName = fopen("dox/" . $nameField . ".txt", "w");
fwrite($fileName, $doxField);
fclose($fileName);
chmod("dox/" . $nameField . ".txt", 0644); // Remove exec bits, just in case

echo 'Dox posted. Click <a href="index.php?dox=' . $nameField . '">here</a> to read it, or go <a href="postdox.php">back to the index</a> to post something else.';

// Clean up session variables
unset($_SESSION["name"]);
unset($_SESSION["dox"]);
unset($_SESSION["hidden"]);
unset($_SESSION["captcha"]);
session_destroy();
?>
