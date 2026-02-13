<?php
require_once(__DIR__ . '/../bootstrap.php');

error_reporting(E_ALL);
defined('IN_SCRIPT') || define('IN_SCRIPT', 1);
include_once('global.php');

if ($inbrowser)
    echo "<pre>";

$coltype = '';
if (isset($collection))
    $coltype = "collectiontype='$collection' AND ";

/**
 * Execute a query and display results with consistent formatting
 *
 * @param string $baseSql The base SQL query (before adult filter and ORDER BY)
 * @param string $message The message to display with the count
 * @param bool $showCooFields Whether to display country of origin fields
 */
function displayQueryResults($baseSql, $message, $showCooFields = false) {
    global $db, $handleadult, $IsPrivate;

    // Add adult filter if needed
    $sql = $baseSql;
    if (($handleadult == 2) || (($handleadult == 1) && !$IsPrivate))
        $sql .= " AND isadulttitle=0";

    // Add ORDER BY
    $sql .= " ORDER BY sorttitle ASC";

    // Execute query
    $result = $db->sql_query($sql);
    $count = $db->sql_numrows($result);

    // Print header message
    echo "You have $count $message\n";

    // Print results if any
    if ($count > 0) {
        // Print column headers
        if ($showCooFields) {
            printf("%20s - %-10s - %s: %s\n", "id", "collection", "title", "| coo1 | coo2 | coo3 |");
        } else {
            printf("%20s - %-10s - %s\n", "id", "collection", "title");
        }

        // Loop through results
        while ($dvd = $db->sql_fetchrow($result)) {
            $id = $dvd['id'];
            $collection = $dvd['collectiontype'];
            $title = $dvd['title'];

            if ($showCooFields) {
                printf("%20s - %-10s - %s: | %s | %s | %s |\n",
                    $id, $collection, $title,
                    $dvd['countryoforigin'], $dvd['countryoforigin2'], $dvd['countryoforigin3']);
            } else {
                printf("%20s - %-10s - %s\n", $id, $collection, $title);
            }
        }
    }

    $db->sql_freeresult($result);
}

// List all of the profiles empty of COO
$sql = "SELECT id,title,collectiontype FROM $DVD_TABLE WHERE $coltype (countryoforigin='' and countryoforigin2='' and countryoforigin3='')";
displayQueryResults($sql, "profiles with no country of origin", false);

// List all of the profiles with an empty slot before a non-empty slot
$sql = "SELECT id,title,collectiontype,countryoforigin,countryoforigin2,countryoforigin3 FROM $DVD_TABLE WHERE $coltype "
    ."((countryoforigin='' AND (countryoforigin2!='' OR countryoforigin3!='')) OR "
    ."(countryoforigin!='' AND countryoforigin2='' AND countryoforigin3!=''))";
displayQueryResults($sql, "profiles with a missing country of origin before a real country of origin", true);

// List all of the profiles with a duplicate slot
$sql = "SELECT id,title,collectiontype,countryoforigin,countryoforigin2,countryoforigin3 FROM $DVD_TABLE WHERE $coltype "
    ."(countryoforigin!='' AND (countryoforigin=countryoforigin2 OR countryoforigin=countryoforigin3)) OR (countryoforigin2!='' AND countryoforigin2=countryoforigin3)";
displayQueryResults($sql, "profiles with a duplicate country of origin", true);

// Find which COOs are not locales
$sql = "SELECT id,title,collectiontype,countryoforigin,countryoforigin2,countryoforigin3 FROM $DVD_TABLE WHERE $coltype "
    ."(countryoforigin!='' AND (countryoforigin=countryoforigin2 OR countryoforigin=countryoforigin3)) OR (countryoforigin2!='' AND countryoforigin2=countryoforigin3)";
displayQueryResults($sql, "profiles with a duplicate country of origin", true);

if ($inbrowser)
    echo "</pre>";
