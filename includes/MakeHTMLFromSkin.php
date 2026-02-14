<?php

// usage: php MakeHTMLFromSkin.php lang skin.html >newskin.html
//
// eg: php MakeHTMLFromSkin.php sv 'phpDVDProfiler Skin.html' >skin.html
// to create the skin with Swedish strings
//
// This code puts the language strings into a skin by replacing the $lang[] elements
// for the purpose of creating an HTML file for import into DVDProfiler

require_once(__DIR__ . '/../bootstrap.php');
include_once('lang_'.$_SERVER['argv'][1].'.php');

    $j = file_get_contents($_SERVER['argv'][2]);

    $j = preg_replace_callback('/\\$lang\\[(.*)\\]\\[(.*)\\]/U', "replace2Lang", $j);
    $j = preg_replace_callback('/\\$lang\\[(.*)\\]/U', "replaceLang", $j);

    echo $j;
