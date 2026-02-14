<?php

defined('IN_SCRIPT') || define('IN_SCRIPT', 1);
require_once __DIR__ . '/../bootstrap.php';
include_once 'global.php';
sendNoCacheHeaders('Content-Type: text/html; charset="windows-1252";');

if (empty($GLOBALS['IsPrivate'])) {
    header("HTTP/1.0 403 Forbidden");
    echo "Access denied.";
    exit;
}

function displayAResultSet(&$db, $sql) {
    $result = $db->sql_query($sql);
    $firstrow = true;
    while ($row = $db->sql_fetchrow($result)) {
        if ($firstrow) {
            $firstrow = false;
            echo "<table border=1><tr>\n";
            foreach ($row as $key => $value) {
                echo "<th>" . htmlspecialchars($key) . "</th>";
            }
            echo "</tr>\n";
        }
        echo "<tr>";
        foreach ($row as $key => $value) {
            echo "<td>" . htmlspecialchars($value ?? '') . "</td>\n";
        }
        echo "</tr>\n";
    }
    echo "</table>\n";
    $db->sql_freeresult($result);
    unset($row);
}

    $allowed_table_vars = array(
        'DVD_TABLE', 'DVD_COMMON_ACTOR_TABLE', 'DVD_ACTOR_TABLE', 'DVD_EVENTS_TABLE',
        'DVD_DISCS_TABLE', 'DVD_LOCKS_TABLE', 'DVD_AUDIO_TABLE', 'DVD_COMMON_CREDITS_TABLE',
        'DVD_CREDITS_TABLE', 'DVD_BOXSET_TABLE', 'DVD_GENRES_TABLE', 'DVD_STUDIO_TABLE',
        'DVD_SUBTITLE_TABLE', 'DVD_TAGS_TABLE', 'DVD_STATS_TABLE', 'DVD_SUPPLIER_TABLE',
        'DVD_PROPERTIES_TABLE', 'DVD_EXCLUSIONS_TABLE', 'DVD_LINKS_TABLE', 'DVD_USERS_TABLE',
    );
    $allowed_tables = array();
    foreach ($allowed_table_vars as $var) {
        if (isset($GLOBALS[$var])) {
            $allowed_tables[] = $GLOBALS[$var];
        }
    }

    $table = @$_GET['table'];
    if (is_string($table) && isset($table[0]) && $table[0] == '$') {
        $var_name = substr($table, 1);
        if (in_array($var_name, $allowed_table_vars, true) && isset($GLOBALS[$var_name])) {
            $table = $GLOBALS[$var_name];
        } else {
            $table = '';
        }
    }
    if ($table == '' || !in_array($table, $allowed_tables, true)) {
        $table = $DVD_STATS_TABLE;
    }

    $request = "SELECT * FROM ".$db->sql_escape($table);
    echo "<html><head><title>Dump of table " . htmlspecialchars($table) . "</title></head><body>\n";
    displayAResultSet($db, $request);
    echo "</body></html>\n";
