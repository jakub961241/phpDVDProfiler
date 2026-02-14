<?php

defined('IN_SCRIPT') || define('IN_SCRIPT', 1);
require_once __DIR__ . '/../bootstrap.php';
include_once 'global.php';
sendNoCacheHeaders('Content-Type: text/html; charset="windows-1252";');

/**
 * Execute a credits check query and display results
 *
 * @param object $db Database connection object
 * @param string $check_description Header/description of the check being performed
 * @param string $sql SQL query to execute
 * @param bool $inbrowser Whether output is for browser (HTML) or plain text
 * @param bool $is_first Whether this is the first check (affects header formatting)
 */
function displayCreditsCheck($db, $check_description, $sql, $inbrowser, $is_first = true) {
    // Set format string based on output mode
    if ($inbrowser) {
        $fmt = "<tr><td>%s</td><td>%s</td><td>%s</td></tr>";
        if ($is_first) {
            echo "<center><table border=1><th colspan=3><bold>$check_description</bold></th>\n";
        } else {
            echo "</table></center><br><br><center><table border=1><th colspan=3><bold>$check_description</bold></th>\n";
        }
    } else {
        $fmt = "%s || %s || %s\n";
        if ($is_first) {
            echo "$check_description\n";
        } else {
            echo "\n\n$check_description\n";
        }
    }

    // Execute query
    $res = $db->sql_query($sql) or die($db->sql_error());

    // Loop through results and display
    while ($row = $db->sql_fetchrow($res)) {
        if ($row['description'] != '') {
            $row['title'] = "$row[title] ($row[description])";
        }
        printf($fmt, $row['title'], $row['fullname'], $row['role']);
    }

    // Free result
    $db->sql_freeresult($res);
}

    $check1 = "Checking for truncated 'uncredited' indicators [searching roles for '(u']";
    $check2 = "Checking for truncated 'voice' indicators [searching roles for '(v']";

    $sql1 = "SELECT title,description,fullname,role FROM $DVD_TABLE d, $DVD_COMMON_ACTOR_TABLE ca, $DVD_ACTOR_TABLE a WHERE a.id=d.id AND ca.caid=a.caid AND role LIKE '%(u%' ORDER BY sorttitle";
    displayCreditsCheck($db, $check1, $sql1, $inbrowser, true);

    $sql2 = "SELECT title,description,fullname,role FROM $DVD_TABLE d, $DVD_COMMON_ACTOR_TABLE ca, $DVD_ACTOR_TABLE a WHERE a.id=d.id AND ca.caid=a.caid AND role LIKE '%(v%' ORDER BY sorttitle";
    displayCreditsCheck($db, $check2, $sql2, $inbrowser, false);

    echo "\n";

    if ($inbrowser) {
        echo "</table></center><br>\n";
    }
