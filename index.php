<?php
ob_start(); // Start output buffering
header('Content-Disposition: inline; filename="index.php"');

if (!isset($_GET['dox'])) {
    $_GET['dox'] = "undefine";
}

$filename = $_GET['dox'];
if ($filename == "") { 
    include("archive.php"); 
    die(); 
}

// Start of HTML output
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
    <title>DOXBIN</title>
    <link href="style/blue.css" rel="stylesheet" type="text/css" />
</head>
<body>
    <a href="./index.php" style="text-decoration:none;"></a>
    <a href="postdox.php">Post Dox</a> 
    <a href="index.php">Back to the archive</a> 
    <a href="proscription.php">Proscription List</a><br />
    <?php
        if (file_exists('dox/' . $filename . '.txt')) {
            echo '<div class="doxheader">';

            if (file_exists('img/verification/' . $filename . '.txt')) {
                $ver = file_get_contents('img/verification/' . $filename . '.txt');
                echo '<div class="verified">' . $ver . '</div>';
            }
            if (file_exists('img/ssn/' . $filename . '.txt')) {
                $ver = file_get_contents('img/ssn/' . $filename . '.txt');
                echo '<div class="ssn">' . $ver . '</div>';
            }
            if (file_exists('img/rip/' . $filename . '.txt')) {
                $ver = file_get_contents('img/rip/' . $filename . '.txt');
                echo '<div class="rip">' . $ver . '</div>';
            }
            if (file_exists('img/mail/' . $filename . '.txt')) {
                $ver = file_get_contents('img/mail/' . $filename . '.txt');
                echo '<div class="mail">' . $ver . '</div>';
            }

            $dox = file_get_contents('dox/' . $filename . '.txt');

            if (strpos($dox, "<script") !== False) {
                $text = $dox;
                include('scrape.php');

                if ($text == '') {
                    $dox = "Unfortunately Pastebin has removed this dox so now this mirror doesn't work. The staff will probably get to work on fixing this soon. Thanks for your patience.";
                } else {
                    if (strpos($text, "Error, this is a private paste. If this is your private paste, please login to Pastebin first.") !== False) {
                        $dox = "Unfortunately this paste has been set to private. This may be because the staff don't want you to see it right now, or because somebody fucked up. Please wait until it is fixed. Thanks for your patience.";
                    } else {
                        $dox = $text;
                    }
                }
            }

            echo '</div><p><textarea name="doxviewer" readonly="readonly" rows="25" cols="80">';
            echo htmlspecialchars($dox);
            echo '</textarea></p></body></html>';
        } else {
            include('archive.php');
        }
    ?>
    <p class="contact">
        Complaints: (901) 747-4300<br>
        <a href="privacy.php">Privacy Policy</a> <a href="faq.php">FAQ</a>
    </p>
</body>
</html>
<?php
ob_end_flush(); // Flush the output buffer and turn off output buffering
?>
