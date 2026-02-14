<?php
#Version 1.1, 11th July 2006. Added "collectiontype='owned' and " to select.

include_once 'graph_init.php';
include_once $jpgraphlocation.'jpgraph.php';
include_once $jpgraphlocation.'jpgraph_bar.php';

$sql = $db->sql_query("SELECT date_format(from_unixtime(purchasedate), '%Y/%m') AS month, "
            ."COUNT(title) AS count, SUM(paid) AS price "
            ."FROM $DVD_TABLE WHERE collectiontype='owned' AND purchaseplace>0 AND purchasepricecurrencyid='".$db->sql_escape($currency)."' "
            ."$currencyspecialcondition GROUP BY month") or die($db->sql_error());

$dates = array();
$cost = array();
$lowest = '9999/99';
$highest = date("Y/m");

while ($row = $db->sql_fetch_array($sql)) {
    $pdate = $row[0];
    $cnt = $row[1];
    $amt = $row[2];
    if ($pdate < $lowest)
        $lowest = $pdate;

    if (!array_key_exists($pdate, $dates)) {
        $dates[$pdate] = 0;
        $cost[$pdate] = 0;
    }
    $dates[$pdate] = $cnt;
    $cost[$pdate] = $amt;
}

// Pad empty months

$current = $lowest;
while($current < $highest) {
    if (!array_key_exists($current, $dates)) {
        $dates[$current] = 0;
        $cost[$current] = 0;
    }

    list($year, $month) = explode('/', $current);
    $month++;
    if ($month > 12) {
        $month = 1;
        $year++;
    }

    $current = sprintf('%02d/%02d', $year, $month);
}

ksort($dates);

$data = array();
$leg = array();
foreach ($dates as $key => $val) {
    $data[] = $cost[$key];
    $leg[] = $key;
}

$FixedTitle = preg_replace('/\\$currency/', $currency, html_entity_decode($lang['GRAPHS']['COST']));
if (!isset($next))
    $FixedTitle = preg_replace('/\\n.*/', '', $FixedTitle);
else
    $FixedTitle = preg_replace('/\\$next/', $next, $FixedTitle);

$graph = new Graph($graphx, $graphy, 'auto');
$graph->SetScale('textint');
$graph->img->SetMargin(50, 30, 50, 60);
$graph->title->Set($FixedTitle);

$graph->xaxis->SetTickLabels($leg);
$graph->xaxis->SetTextLabelInterval(3);
$graph->xaxis->SetFont(FF_COURIER);
$graph->xaxis->SetLabelAngle(45);
$graph->xaxis->HideTicks();

$graph->yaxis->scale->SetGrace($jpgrace);

$bplot = new BarPlot($data);
$bplot->SetFillColor('lightgreen'); // Fill color
$bplot->value->SetColor('black', 'navy');
if (count($leg) <= 20)
    $bplot->SetShadow();

$graph->Add($bplot);
$graph->Stroke();
