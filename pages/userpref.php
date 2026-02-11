<?php
require_once(__DIR__ . '/../bootstrap.php');
/*  $Id$ */

defined('IN_SCRIPT') || define('IN_SCRIPT', 1);
include_once('version.php');
include_once('global.php');

$ajax = isset($_GET['ajax']) || isset($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

function GetSkins() {
global $debugskin;

    function cmpcase($a, $b) {
        return(strcasecmp($a['displayname'], $b['displayname']));
    }

    $TheSkins = array();
    if ($handle=@opendir('skins')) {
        while (($dn=readdir($handle)) !== false) {
            if ($dn == '.' || $dn == '..')
                continue;
            if (!is_dir("skins/$dn"))
                continue;
            if ($h2=opendir('skins/'.$dn)) {
                while (($fn=readdir($h2)) !== false) {
                    if (preg_match('/.*\.htm[l]$/i', $fn)) {
                        if (is_readable("skins/$dn/$fn"))
                            $TheSkins[] = array('dirname' => $dn, 'filename' => $fn, 'displayname' => preg_replace('/\.htm[l]$/i', '', $fn));
                    }
                    if ($debugskin && preg_match('/.*\.htm[l].hidden$/i', $fn)) {
                        if (is_readable("skins/$dn/$fn"))
                            $TheSkins[] = array('dirname' => $dn, 'filename' => $fn, 'displayname' => preg_replace('/\.htm[l].hidden$/i', ' *** HIDDEN ***', $fn));
                    }
                }
                closedir($h2);
            }
        }
        closedir($handle);
    }
    usort($TheSkins, "cmpcase");
    return($TheSkins);
}

    if ($allowskins) {
        $TheSkins = GetSkins();
        if (count($TheSkins) == 0)
            $allowskins = false;
    }

    if (!$allowactorsort &&
        !$allowsecondcol &&
        !$allowthirdcol &&
        !$allowdefaultsorttype &&
        !$allowtitledesc &&
        !$allowlocale &&
        !$allowstickyboxsets &&
        !$allowskins &&
        !$allowpopupimages &&
        !$allowtitlesperpage &&
        !$allowwidths) {
        if ($ajax) {
            echo "<div class=\"text-center py-4\"><h5>{$lang['PREFS']['USERPREFS']}</h5><p>{$lang['PREFS']['NOPREFSETTABLE']}</p></div>";
        } else {
            header('Content-Type: text/html; charset="windows-1252";');
            echo<<<EOT
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
<meta charset="windows-1252">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$lang['PREFS']['USERPREFS']}</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="format.css.php">
<link rel="stylesheet" type="text/css" href="custom.css">
</head>
<body>
<div class="container py-4"><div class="text-center"><h5>{$lang['PREFS']['USERPREFS']}</h5><p>{$lang['PREFS']['NOPREFSETTABLE']}</p>
<a class="btn btn-primary" href="index.php">$lang[IMPORTCLICK]</a></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
$endbody</html>
EOT;
        }
        exit;
    }

    if (!$ajax) SendNoCacheHeaders('Content-Type: text/html; charset="windows-1252";');

    // Authentication - same credentials as update page
    $pref_authorized = false;
    if (isset($_POST['pref_login']) && isset($_POST['pref_pass'])) {
        if ($_POST['pref_login'] === $update_login && $_POST['pref_pass'] === $update_pass) {
            $pref_authorized = true;
        }
    }

    if (!$pref_authorized) {
        // Show login form
        if ($ajax) {
            echo <<<EOT
<div class="py-3" style="max-width:400px; margin:0 auto;">
<h4 class="text-center mb-4">{$lang['PREFS']['USERPREFS']}</h4>
<form method="POST" action="userpref.php?ajax=1" class="card card-body bg-dark border-secondary" id="prefLoginForm">
<div class="mb-3">
  <label for="pref_login" class="form-label">$lang[LOGIN]</label>
  <input type="text" id="pref_login" name="pref_login" class="form-control form-control-sm" autocomplete="username">
</div>
<div class="mb-3">
  <label for="pref_pass" class="form-label">$lang[PASSWORD]</label>
  <input type="password" id="pref_pass" name="pref_pass" class="form-control form-control-sm" autocomplete="current-password">
</div>
<div class="d-grid"><button type="submit" class="btn btn-primary">$lang[LOGIN]</button></div>
</form>
</div>
<script>
document.getElementById('prefLoginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var fd = new FormData(this);
    fetch('userpref.php', {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        body: fd
    }).then(function(r){return r.text();}).then(function(html) {
        var mc = document.getElementById('main-content');
        if (mc) { mc.innerHTML = html; var scripts = mc.querySelectorAll('script'); scripts.forEach(function(s){ var n=document.createElement('script'); n.textContent=s.textContent; s.parentNode.replaceChild(n,s); }); }
    });
});
document.getElementById('pref_login').focus();
</script>
EOT;
        } else {
            echo <<<EOT
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
<meta charset="windows-1252">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$lang['PREFS']['USERPREFS']}</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="format.css.php">
<link rel="stylesheet" type="text/css" href="custom.css">
</head>
<body>
<div class="container py-4" style="max-width:400px">
<h4 class="text-center mb-4">{$lang['PREFS']['USERPREFS']}</h4>
<form method="POST" class="card card-body bg-dark border-secondary">
<div class="mb-3">
  <label for="pref_login" class="form-label">$lang[LOGIN]</label>
  <input type="text" id="pref_login" name="pref_login" class="form-control form-control-sm" autocomplete="username" autofocus>
</div>
<div class="mb-3">
  <label for="pref_pass" class="form-label">$lang[PASSWORD]</label>
  <input type="password" id="pref_pass" name="pref_pass" class="form-control form-control-sm" autocomplete="current-password">
</div>
<div class="d-grid"><button type="submit" class="btn btn-primary">$lang[LOGIN]</button></div>
</form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
$endbody</html>
EOT;
        }
        exit;
    }

    $ddstyle = 'style="margin:3px 0 0 5px"';
    if (!$ajax) {
    echo<<<EOT
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
<meta charset="windows-1252">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$lang['PREFS']['USERPREFS']}</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="format.css.php">
<link rel="stylesheet" type="text/css" href="custom.css">
EOT;
    }
    echo<<<EOT
<script type="text/javascript">
<!--

function getexpirydate(numdays) {
    Today = new Date();
    Today.setTime(Date.parse(Today) + numdays*24*60*60*1000);
    return(Today.toUTCString());
}

function getcookie(cookiename) {
var cookiestring = "" + document.cookie;
var index1 = cookiestring.indexOf(cookiename);
var index2;

    if (index1 == -1 || cookiename == "")
        return("");
    index2 = cookiestring.indexOf(';', index1);
    if (index2 == -1)
        index2 = cookiestring.length;
    return(unescape(cookiestring.substring(index1+cookiename.length+1, index2)));
}

function setcookie(name, value, durationindays) {
    cookiestring = name + "=" + escape(value) + ";EXPIRES=" + getexpirydate(durationindays);
    document.cookie = cookiestring;
    return(true);
}

function ProcessADropDown(groupobj, groupname, expiresindays)
{
    if (groupobj[groupobj.selectedIndex].value == 'sitedefault')
        setcookie(groupname, getcookie(groupname), -1);
    else
        setcookie(groupname, groupobj[groupobj.selectedIndex].value, expiresindays);
}

function ResetWidths(obj)
{
    if (obj.checked == true) {
        setcookie('widthgt800', getcookie('widthgt800'), -1);
    }
    return true;
}

function HandleCookies()
{


EOT;
    if ($allowactorsort) echo "\tProcessADropDown(document.config.actorsort, 'actorsort', 10*365);\n";
    if ($allowsecondcol) echo "\tProcessADropDown(document.config.secondcol, 'secondcol', 10*365);\n";
    if ($allowthirdcol) echo "\tProcessADropDown(document.config.thirdcol, 'thirdcol', 10*365);\n";
    if ($allowdefaultsorttype) echo "\tProcessADropDown(document.config.defaultsorttype, 'defaultsorttype', 10*365);\n";
    if ($allowtitledesc) echo "\tProcessADropDown(document.config.titledesc, 'titledesc', 10*365);\n";
    if ($allowlocale) echo "\tProcessADropDown(document.config.locale, 'locale', 10*365);\n";
    if ($allowstickyboxsets) echo "\tProcessADropDown(document.config.stickyboxsets, 'stickyboxsets', 10*365);\n";
    if ($allowpopupimages) echo "\tProcessADropDown(document.config.popupimages, 'popupimages', 10*365);\n";
    if ($allowskins) echo "\tProcessADropDown(document.config.skins, 'skinfile', 10*365);\n";
    if ($allowtitlesperpage) echo "\tProcessADropDown(document.config.titlesperpage, 'titlesperpage', 10*365);\n";
    if ($allowwidths) echo "\tResetWidths(document.config.widths);\n";
// remove the temporary cookies to reduce user confusion when changing defaultsorttype
    echo<<<EOT
    setcookie('cookiesort', getcookie('cookiesort'), -1);
    setcookie('cookieorder', getcookie('cookieorder'), -1);
    document.location.reload();
}
// -->
</script>
EOT;
    if (!$ajax) {
    echo<<<EOT
</head>
<body>
<div class="container py-4">
EOT;
    }
    echo<<<EOT
<h4 class="text-center mb-3">{$lang['PREFS']['USERPREFS']}</h4>
<form name=config action="javascript:;" onSubmit="HandleCookies();return true">
<div class="row justify-content-center"><div class="col-lg-8">
<table class=bgl align=center width="100%" border=1>
<tr class=t><th>{$lang['PREFS']['PREFERENCE']}</th><th>{$lang['PREFS']['CURRENTVAL']}</th><th>{$lang['PREFS']['SELECTIONS']}</th></tr>
EOT;

    if ($allowactorsort) {
        $n1 = $n2 = $n3 = $n4 = $n5 = '';
        if (isset($_COOKIE['actorsort'])) {
            $a = $lang['PREFS']['ACTORSORT'][$actorsort];
            if ($_COOKIE['actorsort'] == '0')
                $n1 = 'selected';
            else if ($_COOKIE['actorsort'] == '1')
                $n2 = 'selected';
            else if ($_COOKIE['actorsort'] == '2')
                $n3 = 'selected';
        }
        else {
            $a = $lang['PREFS']['SITEDEFAULT']."<br>[".$lang['PREFS']['ACTORSORT'][$siteactorsort]."]";
            $n4 = 'selected';
        }
        echo<<<EOT
<tr class=a><td valign=middle align=center>{$lang['PREFS']['ACTORSORT']['NAME']}</td><td valign=middle align=center>$a</td><td>
<select $ddstyle name="actorsort">
<option value="0" $n1>{$lang['PREFS']['ACTORSORT'][0]}</option>
<option value="1" $n2>{$lang['PREFS']['ACTORSORT'][1]}</option>
<option value="2" $n3>{$lang['PREFS']['ACTORSORT'][2]}</option>
<option value="sitedefault" $n4>{$lang['PREFS']['SITEDEFAULT']} [{$lang['PREFS']['ACTORSORT'][$siteactorsort]}]</option>
</select></td></tr>
EOT;
    }

    if ($allowsecondcol) {
        $n1 = $n2 = $n3 = $n4 = $n5 = $n6 = $n7 = $n8 = $n9 = $n10 = $n11 = '';
        if (isset($_COOKIE['secondcol'])) {
            $a = $lang['PREFS']['COLUMNS'][$secondcol];
            if ($_COOKIE['secondcol'] == 'released')
                $n1 = 'selected';
            else if ($_COOKIE['secondcol'] == 'productionyear')
                $n2 = 'selected';
            else if ($_COOKIE['secondcol'] == 'purchasedate')
                $n3 = 'selected';
            else if ($_COOKIE['secondcol'] == 'collectionnumber')
                $n4 = 'selected';
            else if ($_COOKIE['secondcol'] == 'runningtime')
                $n5 = 'selected';
            else if ($_COOKIE['secondcol'] == 'rating')
                $n6 = 'selected';
            else if ($_COOKIE['secondcol'] == 'genres')
                $n7 = 'selected';
            else if ($_COOKIE['secondcol'] == 'reviews')
                $n10 = 'selected';
            else if ($_COOKIE['secondcol'] == 'director')
                $n11 = 'selected';
            else if ($_COOKIE['secondcol'] == 'none')
                $n9 = 'selected';
        }
        else {
            $a = $lang['PREFS']['SITEDEFAULT']."<br>[".$lang['PREFS']['COLUMNS'][$sitesecondcol]."]";
            $n8 = 'selected';
        }
        echo<<<EOT
<tr class=a><td valign=middle align=center>{$lang['PREFS']['COLUMNS']['SECONDNAME']}</td><td valign=middle align=center>$a</td><td>
<select $ddstyle name="secondcol">
<option value="released" $n1>{$lang['PREFS']['COLUMNS']['released']}</option>
<option value="productionyear" $n2>{$lang['PREFS']['COLUMNS']['productionyear']}</option>
<option value="purchasedate" $n3>{$lang['PREFS']['COLUMNS']['purchasedate']}</option>
<option value="collectionnumber" $n4>{$lang['PREFS']['COLUMNS']['collectionnumber']}</option>
<option value="runningtime" $n5>{$lang['PREFS']['COLUMNS']['runningtime']}</option>
<option value="rating" $n6>{$lang['PREFS']['COLUMNS']['rating']}</option>
<option value="genres" $n7>{$lang['PREFS']['COLUMNS']['genres']}</option>
<option value="reviews" $n10>{$lang['PREFS']['COLUMNS']['reviews']}</option>
<option value="director" $n11>{$lang['PREFS']['COLUMNS']['director']}</option>
<option value="sitedefault" $n8>{$lang['PREFS']['SITEDEFAULT']} [{$lang['PREFS']['COLUMNS'][$sitesecondcol]}]</option>
<option value="none" $n9>{$lang['PREFS']['COLUMNS']['none']}</option>
</select></td></tr>
EOT;
    }

    if ($allowthirdcol) {
        $n1 = $n2 = $n3 = $n4 = $n5 = $n6 = $n7 = $n8 = $n9 = $n10 = $n11 = '';
        if (isset($_COOKIE['thirdcol'])) {
            $a = $lang['PREFS']['COLUMNS'][$thirdcol];
            if ($_COOKIE['thirdcol'] == 'released')
                $n1 = 'selected';
            else if ($_COOKIE['thirdcol'] == 'productionyear')
                $n2 = 'selected';
            else if ($_COOKIE['thirdcol'] == 'purchasedate')
                $n3 = 'selected';
            else if ($_COOKIE['thirdcol'] == 'collectionnumber')
                $n4 = 'selected';
            else if ($_COOKIE['thirdcol'] == 'runningtime')
                $n5 = 'selected';
            else if ($_COOKIE['thirdcol'] == 'rating')
                $n6 = 'selected';
            else if ($_COOKIE['thirdcol'] == 'genres')
                $n7 = 'selected';
            else if ($_COOKIE['thirdcol'] == 'reviews')
                $n10 = 'selected';
            else if ($_COOKIE['thirdcol'] == 'director')
                $n11 = 'selected';
            else if ($_COOKIE['thirdcol'] == 'none')
                $n9 = 'selected';
        }
        else {
            $a = $lang['PREFS']['SITEDEFAULT']."<br>[".$lang['PREFS']['COLUMNS'][$sitethirdcol]."]";
            $n8 = 'selected';
        }
        echo<<<EOT
<tr class=a><td valign=middle align=center>{$lang['PREFS']['COLUMNS']['THIRDNAME']}</td><td valign=middle align=center>$a</td><td>
<select $ddstyle name="thirdcol">
<option value="released" $n1>{$lang['PREFS']['COLUMNS']['released']}</option>
<option value="productionyear" $n2>{$lang['PREFS']['COLUMNS']['productionyear']}</option>
<option value="purchasedate" $n3>{$lang['PREFS']['COLUMNS']['purchasedate']}</option>
<option value="collectionnumber" $n4>{$lang['PREFS']['COLUMNS']['collectionnumber']}</option>
<option value="runningtime" $n5>{$lang['PREFS']['COLUMNS']['runningtime']}</option>
<option value="rating" $n6>{$lang['PREFS']['COLUMNS']['rating']}</option>
<option value="genres" $n7>{$lang['PREFS']['COLUMNS']['genres']}</option>
<option value="reviews" $n10>{$lang['PREFS']['COLUMNS']['reviews']}</option>
<option value="director" $n11>{$lang['PREFS']['COLUMNS']['director']}</option>
<option value="sitedefault" $n8>{$lang['PREFS']['SITEDEFAULT']} [{$lang['PREFS']['COLUMNS'][$sitethirdcol]}]</option>
<option value="none" $n9>{$lang['PREFS']['COLUMNS']['none']}</option>
</select></td></tr>
EOT;
    }

    if ($allowdefaultsorttype) {
        $n1 = $n2 = $n3 = $n4 = $n5 = '';
        $z = $$sitedefaultsorttype;
        $displaysitedefaultsorttype = $lang['PREFS']['DEFAULTSORTTYPE'][$sitedefaultsorttype].$lang['PREFS']['COLUMNS'][$z];
        if (isset($_COOKIE['defaultsorttype'])) {
            $z = $$defaultsorttype;
            $a = $lang['PREFS']['DEFAULTSORTTYPE'][$defaultsorttype].$lang['PREFS']['COLUMNS'][$z];
            if ($_COOKIE['defaultsorttype'] == 'firstcol')
                $n1 = 'selected';
            else if ($_COOKIE['defaultsorttype'] == 'secondcol')
                $n2 = 'selected';
            else if ($_COOKIE['defaultsorttype'] == 'thirdcol')
                $n3 = 'selected';
        }
        else {
            $a = $lang['PREFS']['SITEDEFAULT']."<br>[$displaysitedefaultsorttype]";
            $n4 = 'selected';
        }
        echo<<<EOT
<tr class=a><td valign=middle align=center>{$lang['PREFS']['DEFAULTSORTTYPE']['NAME']}</td><td valign=middle align=center>$a</td><td>
<select $ddstyle name="defaultsorttype">
<option value="firstcol" $n1>{$lang['PREFS']['DEFAULTSORTTYPE']['firstcol']}{$lang['PREFS']['COLUMNS'][$firstcol]}</option>
<option value="secondcol" $n2>{$lang['PREFS']['DEFAULTSORTTYPE']['secondcol']}{$lang['PREFS']['COLUMNS'][$secondcol]}</option>
<option value="thirdcol" $n3>{$lang['PREFS']['DEFAULTSORTTYPE']['thirdcol']}{$lang['PREFS']['COLUMNS'][$thirdcol]}</option>
<option value="sitedefault" $n4>{$lang['PREFS']['SITEDEFAULT']} [$displaysitedefaultsorttype]</option>
</select></td></tr>
EOT;
    }

    if ($allowtitledesc) {
        $n1 = $n2 = $n3 = $n4 = $n5 = '';
        if (isset($_COOKIE['titledesc'])) {
            $a = $lang['PREFS']['TITLEDESC'][$titledesc];
            if ($_COOKIE['titledesc'] == '0')
                $n1 = 'selected';
            else if ($_COOKIE['titledesc'] == '1')
                $n2 = 'selected';
            else if ($_COOKIE['titledesc'] == '2')
                $n3 = 'selected';
            else if ($_COOKIE['titledesc'] == '3')
                $n4 = 'selected';
        }
        else {
            $a = $lang['PREFS']['SITEDEFAULT']."<br>[".$lang['PREFS']['TITLEDESC'][$sitetitledesc]."]";
            $n5 = 'selected';
        }
        echo<<<EOT
<tr class=a><td valign=middle align=center>{$lang['PREFS']['TITLEDESC']['NAME']}</td><td valign=middle align=center>$a</td><td>
<select $ddstyle name="titledesc">
<option value="0" $n1>{$lang['PREFS']['TITLEDESC'][0]}</option>
<option value="1" $n2>{$lang['PREFS']['TITLEDESC'][1]}</option>
<option value="2" $n3>{$lang['PREFS']['TITLEDESC'][2]}</option>
<option value="3" $n4>{$lang['PREFS']['TITLEDESC'][3]}</option>
<option value="sitedefault" $n5>{$lang['PREFS']['SITEDEFAULT']} [{$lang['PREFS']['TITLEDESC'][$sitetitledesc]}]</option>
</select></td></tr>
EOT;
    }

    if ($allowlocale) {
        $n1 = $n2 = $n3 = $n4 = $n5 = $n6 = $n7 = $n8 = $n9 = $n10 = '';
        if (isset($_COOKIE['locale'])) {
            $a = $lang['PREFS']['LOCALE'][$locale];
            if ($_COOKIE['locale'] == 'en')
                $n1 = 'selected';
            else if ($_COOKIE['locale'] == 'de')
                $n2 = 'selected';
            else if ($_COOKIE['locale'] == 'no')
                $n3 = 'selected';
            else if ($_COOKIE['locale'] == 'fr')
                $n4 = 'selected';
            else if ($_COOKIE['locale'] == 'nl')
                $n5 = 'selected';
            else if ($_COOKIE['locale'] == 'sv')
                $n6 = 'selected';
            else if ($_COOKIE['locale'] == 'dk')
                $n7 = 'selected';
            else if ($_COOKIE['locale'] == 'fi')
                $n8 = 'selected';
            else if ($_COOKIE['locale'] == 'ru')
                $n9 = 'selected';
        }
        else {
            $a = $lang['PREFS']['SITEDEFAULT']."<br>[".$lang['PREFS']['LOCALE'][$sitelocale]."]";
            $n10 = 'selected';
        }
        echo<<<EOT
<tr class=a><td valign=middle align=center>{$lang['PREFS']['LOCALE']['NAME']}</td><td valign=middle align=center>$a</td><td>
<select $ddstyle name="locale">
<option value="en" $n1>{$lang['PREFS']['LOCALE']['en']}</option>
<option value="de" $n2>{$lang['PREFS']['LOCALE']['de']}</option>
<option value="no" $n3>{$lang['PREFS']['LOCALE']['no']}</option>
<option value="fr" $n4>{$lang['PREFS']['LOCALE']['fr']}</option>
<option value="nl" $n5>{$lang['PREFS']['LOCALE']['nl']}</option>
<option value="sv" $n6>{$lang['PREFS']['LOCALE']['sv']}</option>
<option value="dk" $n7>{$lang['PREFS']['LOCALE']['dk']}</option>
<option value="fi" $n8>{$lang['PREFS']['LOCALE']['fi']}</option>
<option value="ru" $n9>{$lang['PREFS']['LOCALE']['ru']}</option>
<option value="sitedefault" $n10>{$lang['PREFS']['SITEDEFAULT']} [{$lang['PREFS']['LOCALE'][$sitelocale]}]</option>
</select></td></tr>
EOT;
    }

    if ($allowstickyboxsets) {
        $n1 = $n2 = $n3 = $n4 = $n5 = '';
        if (isset($_COOKIE['stickyboxsets'])) {
            $a = $lang['PREFS']['STICKYBOXSETS'][$stickyboxsets];
            if ($_COOKIE['stickyboxsets'] == '1')
                $n1 = 'selected';
            else if ($_COOKIE['stickyboxsets'] == '0')
                $n2 = 'selected';
        }
        else {
            $a = $lang['PREFS']['SITEDEFAULT']."<br>[".$lang['PREFS']['STICKYBOXSETS'][$sitestickyboxsets]."]";
            $n3 = 'selected';
        }
        echo<<<EOT
<tr class=a><td valign=middle align=center>{$lang['PREFS']['STICKYBOXSETS']['NAME']}</td><td valign=middle align=center>$a</td><td>
<select $ddstyle name="stickyboxsets">
<option value="1" $n1>{$lang['PREFS']['STICKYBOXSETS'][1]}</option>
<option value="0" $n2>{$lang['PREFS']['STICKYBOXSETS'][0]}</option>
<option value="sitedefault" $n3>{$lang['PREFS']['SITEDEFAULT']} [{$lang['PREFS']['STICKYBOXSETS'][$sitestickyboxsets]}]</option>
</select></td></tr>
EOT;
    }

    if ($allowpopupimages) {
        $n1 = $n2 = $n3 = $n4 = $n5 = '';
        if (isset($_COOKIE['popupimages'])) {
            $a = $lang['PREFS']['POPUPIMAGES'][$popupimages];
            if ($_COOKIE['popupimages'] == '1')
                $n1 = 'selected';
            else if ($_COOKIE['popupimages'] == '0')
                $n2 = 'selected';
        }
        else {
            $a = $lang['PREFS']['SITEDEFAULT']."<br>[".$lang['PREFS']['POPUPIMAGES'][$sitepopupimages]."]";
            $n3 = 'selected';
        }
        echo<<<EOT
<tr class=a><td valign=middle align=center>{$lang['PREFS']['POPUPIMAGES']['NAME']}</td><td valign=middle align=center>$a</td><td>
<select $ddstyle name="popupimages">
<option value="1" $n1>{$lang['PREFS']['POPUPIMAGES'][1]}</option>
<option value="0" $n2>{$lang['PREFS']['POPUPIMAGES'][0]}</option>
<option value="sitedefault" $n3>{$lang['PREFS']['SITEDEFAULT']} [{$lang['PREFS']['POPUPIMAGES'][$sitepopupimages]}]</option>
</select></td></tr>
EOT;
    }

    if ($allowtitlesperpage) {
        $n1 = $n2 = $n3 = $n4 = $n5 = $n6 = '';
        $siteTitlesPerPage = isset($siteTitlesPerPage) ? (int)$siteTitlesPerPage : 0;
        if (isset($_COOKIE['titlesperpage'])) {
            $a = isset($lang['PREFS']['TITLESPERPAGE'][(int)$TitlesPerPage]) ? $lang['PREFS']['TITLESPERPAGE'][(int)$TitlesPerPage] : "$TitlesPerPage per page";
            if ($_COOKIE['titlesperpage'] == '0')
                $n1 = 'selected';
            else if ($_COOKIE['titlesperpage'] == '25')
                $n2 = 'selected';
            else if ($_COOKIE['titlesperpage'] == '50')
                $n3 = 'selected';
            else if ($_COOKIE['titlesperpage'] == '100')
                $n4 = 'selected';
            else if ($_COOKIE['titlesperpage'] == '200')
                $n5 = 'selected';
        }
        else {
            $sitedefval = isset($lang['PREFS']['TITLESPERPAGE'][$siteTitlesPerPage]) ? $lang['PREFS']['TITLESPERPAGE'][$siteTitlesPerPage] : "$siteTitlesPerPage";
            $a = $lang['PREFS']['SITEDEFAULT']."<br>[$sitedefval]";
            $n6 = 'selected';
        }
        echo<<<EOT
<tr class=a><td valign=middle align=center>{$lang['PREFS']['TITLESPERPAGE']['NAME']}</td><td valign=middle align=center>$a</td><td>
<select $ddstyle name="titlesperpage">
<option value="0" $n1>{$lang['PREFS']['TITLESPERPAGE'][0]}</option>
<option value="25" $n2>{$lang['PREFS']['TITLESPERPAGE'][25]}</option>
<option value="50" $n3>{$lang['PREFS']['TITLESPERPAGE'][50]}</option>
<option value="100" $n4>{$lang['PREFS']['TITLESPERPAGE'][100]}</option>
<option value="200" $n5>{$lang['PREFS']['TITLESPERPAGE'][200]}</option>
<option value="sitedefault" $n6>{$lang['PREFS']['SITEDEFAULT']} [{$lang['PREFS']['TITLESPERPAGE'][$siteTitlesPerPage]}]</option>
</select></td></tr>
EOT;
    }

    if ($allowskins) {
        $n1 = $n2 = '';
        if ($skinfile == 'internal')
            $n1 = 'selected';
        $ss = $siteskinfile;
        if ($siteskinfile == 'internal')
            $ss = $lang['PREFS']['SKINS']['INTERNAL'];
        $ss = preg_replace('/\.htm[l]$/i', '', $ss);
        if (isset($_COOKIE['skinfile'])) {
            if ($skinfile == 'internal')
                $a = $lang['PREFS']['SKINS']['INTERNAL'];
            else
                $a = preg_replace('/\.htm[l]$/i', '', $skinfile);
        }
        else {
            $a = $lang['PREFS']['SITEDEFAULT']."<br>[$ss]";
            $n2 = 'selected';
        }
        echo <<<EOT
<tr class=a><td valign=middle align=center>{$lang['PREFS']['SKINS']['NAME']}</td><td valign=middle align=center>$a</td><td>
<select $ddstyle name="skins">
<option value="internal" $n1>{$lang['PREFS']['SKINS']['INTERNAL']}</option>

EOT;
        foreach ($TheSkins as $k => $SkinValue) {
            $t = '';
            if ($SkinValue['filename'] == $skinfile)
                $t = 'selected';
            echo '<option value="'.rawurlencode("$SkinValue[dirname]/$SkinValue[filename]")."\" $t>$SkinValue[displayname]</option>\n";
        }
        echo <<<EOT
<option value="sitedefault" $n2>{$lang['PREFS']['SITEDEFAULT']} [$ss]</option>
</select></td></tr>
EOT;
        unset($TheSkins);
    }

    if ($allowwidths) {
        echo<<<EOT
<tr class=a><td colspan=3 align=center>
<br><input type="checkbox" name="widths">{$lang['PREFS']['WIDTHS']['NAME']}<br></td></tr>
EOT;
    }

    unset($mapping);
    echo '</table></div></div><div class="text-center my-3"><input type="submit" class="btn btn-primary" value="',$lang['PREFS']['UPDATEPREFS'],'"></div></form>';
    if (!$ajax) {
        echo '<div class="text-center"><a class="btn btn-outline-secondary btn-sm" href="index.php">', $lang['IMPORTCLICK'], "</a></div>\n";
        echo "</div>\n"; // close container
        echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>';
        echo "\n$endbody</html>\n";
    }
