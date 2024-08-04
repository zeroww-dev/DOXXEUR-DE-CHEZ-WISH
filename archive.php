<?php
ob_start(); // Start output buffering
header('Content-Disposition: inline; filename="archive.php"');

$dirFiles = array();

if (!isset($_GET['sort'])) {
    $_GET['sort'] = "undefine";
} 

// Load all txt files into an array
if ($handle = opendir('dox')) {
    while (false !== ($file = readdir($handle))) {
        if ($file != "." && $file != ".." && $file != ".htaccess" && pathinfo($file, PATHINFO_EXTENSION) == "txt") {
            $dirFiles[] = $file;
        }
    }
    closedir($handle);
}

// Navigation
echo 'Last <a href="?sort=1" alt="24 hours">24 hours</a> <a href="?sort=2" alt="3 Days">3 Days</a> <a href="?sort=3" alt="week">week</a> <a href="?sort=4" alt="month">month</a><br />';

// Search Form
echo '<table border="0" cellspacing="0" cellpadding="3">';
echo '<tr align="left" valign="top">';
echo '  <td>Search:';
echo '    <form action="./search.php" method="GET" target="_self">';
echo '    <input type="hidden" value="SEARCH" name="action">';
echo '    <input type="text" name="keyword" class="text" size="12" maxlength="30" value="Search query" onFocus="if (value == \'Search query\') {value=\'\'}" onBlur="if (value == \'\') {value=\'Search query\'}" > ';
echo '    <input type="submit" value="Search" class="button"><br />';
echo '    </form>';
echo ' </td>';
echo '</tr>';
echo '</table>';
echo '<br /><br />';

// Get the sort by GET_
if (empty($_GET)) {
    $showdays = 9001;
} else {
    $get_sort = preg_quote($_GET['sort'], '/');
    $whitelist_sort = preg_replace('/[^0-9]/', '', $get_sort);
    $cutinput = substr($whitelist_sort, 0, 1);

    switch ((int)$cutinput) {
        case 0:
            $showdays = 9001;
            break;
        case 1:
            $showdays = 1;
            break;
        case 2:
            $showdays = 3;
            break;
        case 3:
            $showdays = 7;
            break;
        case 4:
            $showdays = 30;
            break;
        default:
            $showdays = 9001;
    }
}

// Sort the array
natcasesort($dirFiles);

// Table header
echo '<table><thead><tr>';
echo '<th class="doxcols">Name</th> <th class="doxcols">Mirror</th> <th class="doxcols">Status</th> <th class="doxcols">Date</th> <th class="doxcols">Time</th> <th class="doxcols">Filesize</th>';
echo '</tr></thead><tbody>';

// Index of the files
$key = 0;

foreach ($dirFiles as $file) {
    $key++;
    if (stristr($file, '.txt')) {
        $xfile = str_replace(".txt", "", $file);
        $xfileName = str_replace("-mirror-", "", $xfile);

        // Check for mirror
        $mirror = false;
        $online = false;

        if (strpos($file, '-mirror-') !== False) {
            $mirror = true;
            $fd = fopen("dox/" . $file, "r");
            $text = fread($fd, 51200);
            fclose($fd);

            include('scrape.php');

            if ($text == '') {
                $online = false;
            } else {
                if (strpos($text, 'Error, this is a private paste. If this is your private paste, please login to Pastebin first.') !== False) {
                    $online = false;
                } else {
                    $online = true;
                }
            }
        } else {
            $mirror = false;
        }

        if ((time() - filemtime("dox/$file")) <= ($showdays * 86400)) {
            echo '<tr>';
            echo '<td>';
            echo '<a href="index.php?dox=' . $xfile . '" alt="' . $xfile . '">' . htmlspecialchars($xfileName) . "</a>";

            // Icon Output
            if (file_exists('img/verification/' . $file)) {
                $datestamp = file_get_contents('img/verification/' . $file);
                echo ' <img src="img/green-checkbox.png" alt="' . htmlspecialchars($datestamp) . '" title="' . htmlspecialchars($datestamp) . '" />';
            }
            if (file_exists('img/ssn/' . $file)) {
                $datestamp = file_get_contents('img/ssn/' . $file);
                echo ' <img src="img/ssn.png" alt="' . htmlspecialchars($datestamp) . '" title="' . htmlspecialchars($datestamp) . '" />';
            }
            if (file_exists('img/rip/' . $file)) {
                $datestamp = file_get_contents('img/rip/' . $file);
                echo ' <img src="img/rip.png" alt="' . htmlspecialchars($datestamp) . '" title="' . htmlspecialchars($datestamp) . '" />';
            }
            if (file_exists('img/mail/' . $file)) {
                $datestamp = file_get_contents('img/mail/' . $file);
                echo ' <img src="img/mail.png" alt="' . htmlspecialchars($datestamp) . '" title="' . htmlspecialchars($datestamp) . '" />';
            }
            echo '</td>';

            echo '<td>';
            if ($mirror) {
                echo 'Mirror';
            } else {
                echo 'Original';
            }
            echo '</td>';

            echo '<td>';
            if ($online) {
                echo 'Online';
            } else {
                echo 'Offline';
            }
            echo '</td>';

            echo '<td>' . date("F j, Y", filemtime("dox/$file")) . '</td>';
            echo '<td>' . date("g:i a", filemtime("dox/$file")) . '</td>';
            echo '<td>' . filesize("dox/$file") . ' bytes</td>';
            echo '</tr>';
        }
    }
}

// Close table
echo '</tbody></table>';
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
    <title>Archive</title>
    <link href="style/blue.css" rel="stylesheet" type="text/css" />
</head>
<body>
    <p class="contact">
        Complaints: (901) 747-4300<br>
        <a href="privacy.php">Privacy Policy</a> <a href="faq.php">FAQ</a>
    </p>
</body>
</html>
<?php
ob_end_flush(); // Flush the output buffer and turn off output buffering
?>
