<?php
defined('IN_SCRIPT') || define('IN_SCRIPT', 1);
require_once(__DIR__ . '/../bootstrap.php');
include_once('global.php');
if ($TryToChangeMemoryAndTimeLimits) @ini_set('memory_limit', -1);

if (!isset($graphx) || !$graphx)
    $graphx = 800 - 40;
if (!isset($graphy) || !$graphy)
    $graphy = 'auto';
if ($graphy == 'auto')
    $graphy = ($graphx*3)/4;

function cmp($a, $b) {
    if (strtolower($a) == strtolower($b))
        return(0);
    return((strtolower($a) < strtolower($b))? -1 : 1);
}
