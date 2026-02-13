<?php
include_once('graph_init.php');
include_once($jpgraphlocation.'jpgraph.php');
include_once($jpgraphlocation.'jpgraph_pie.php');
include_once($jpgraphlocation.'jpgraph_pie3d.php');

$sql = "SELECT IF (LOCATE('.',id) = '0',0,SUBSTRING(id,locate('.',id)+1,LENGTH(id)-LOCATE('.',id)))+0 as locality, "
    ."SUM(1) AS count FROM $DVD_TABLE WHERE collectiontype='owned' $localespecialcondition "
    ."GROUP BY locality ORDER BY count DESC";
$result = $db->sql_query($sql) or die($db->sql_error());

$i = 1;
$data = array();
$name = array();
$data[0] = 0;
$name[0] = $lang['OTHER'].' (%d)';
while ($row = $db->sql_fetchrow($result)) {
    $cnt = $row['count'];
    if ($i > 1 && (($data[1] * $localemin) > $cnt)) {
        #echo "$i, $cnt, $data[1]\n";
        $data[0] += $row['count'];
    }
    else {
        $loc = $row['locality'];
        $data[$i] = $row['count'];
        $name[$i] = html_entity_decode($lang["LOCALE$loc"].' (%d)');
        $i++;
    }
}
if ($data[0] == 0) {
    unset($data[0]);
    unset($name[0]);
}

$cnt = array_shift($data);
$loc = array_shift($name);
array_push($data, $cnt);
array_push($name, $loc);

$graph = new PieGraph($graphx, $graphy, 'auto');
$graph->img->SetMargin(50, 30, 50, 60);
$graph->title->Set(html_entity_decode($lang['GRAPHS']['LOCALITY']));

$bplot = new PiePlot3D($data);
$bplot->SetLabels($name, 1);
$bplot->SetLabelType(PIE_VALUE_ABS);
$bplot->SetLabelPos(0.5);
$bplot->value->SetFormat('%d');
$bplot->SetLegends($name);
$bplot->SetEdge('black', 1);
$bplot->value->SetColor('black', 'navy');

$graph->Add($bplot);
$graph->legend->SetPos(0.01,0.05,'left','top');
$graph->Stroke();
