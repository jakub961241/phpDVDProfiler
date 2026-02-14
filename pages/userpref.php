<?php
require_once(__DIR__ . '/../bootstrap.php');
/*  $Id$ */

defined('IN_SCRIPT') || define('IN_SCRIPT', 1);
include_once('version.php');
include_once('global.php');

$ajax = isset($_GET['ajax']) || isset($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

/* ------------------------------------------------------------------ */
/*  Helper: Bootstrap dark-theme HTML page header                     */
/* ------------------------------------------------------------------ */
function renderPrefPageHeader($title) {
    global $lang;
    return <<<EOT
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
<meta charset="windows-1252">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>$title</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="format.css.php">
<link rel="stylesheet" type="text/css" href="custom.css">
</head>
<body>
EOT;
}

/* ------------------------------------------------------------------ */
/*  Helper: Bootstrap dark-theme HTML page footer                     */
/* ------------------------------------------------------------------ */
function renderPrefPageFooter() {
    global $endbody;
    return '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>'
         . "\n$endbody</html>";
}

/* ------------------------------------------------------------------ */
/*  Helper: Render one preference dropdown table row                  */
/*                                                                    */
/*  $cookieName   - cookie key, e.g. 'actorsort'                     */
/*  $currentValue - resolved runtime value of the preference          */
/*  $siteDefault  - site-level default value                          */
/*  $options      - ordered array of [value => displayLabel]          */
/*  $label        - row label shown in the first column               */
/*  $langSection  - $lang['PREFS'][...] sub-array for this pref      */
/*  $ddstyle      - inline style string for the <select>             */
/* ------------------------------------------------------------------ */
function renderPreferenceDropdown($cookieName, $currentValue, $siteDefault, $options, $label, $langSection, $ddstyle) {
    global $lang;

    // Determine "current value" display and which option is selected
    $selectedValue = null;
    if (isset($_COOKIE[$cookieName])) {
        $a = isset($langSection[$currentValue]) ? $langSection[$currentValue] : $currentValue;
        $selectedValue = $_COOKIE[$cookieName];
    } else {
        $siteDisplayLabel = isset($langSection[$siteDefault]) ? $langSection[$siteDefault] : $siteDefault;
        $a = $lang['PREFS']['SITEDEFAULT'] . "<br>[" . $siteDisplayLabel . "]";
        $selectedValue = 'sitedefault';
    }

    // Build <option> tags
    $optionsHtml = '';
    foreach ($options as $val => $displayLabel) {
        // Cast both to string for comparison (cookie values are strings)
        $sel = ((string)$val === (string)$selectedValue) ? ' selected' : '';
        $optionsHtml .= "<option value=\"$val\"$sel>$displayLabel</option>\n";
    }

    echo <<<EOT
<tr class=a><td valign=middle align=center>$label</td><td valign=middle align=center>$a</td><td>
<select $ddstyle name="$cookieName">
$optionsHtml</select></td></tr>
EOT;
}

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
            echo renderPrefPageHeader($lang['PREFS']['USERPREFS']);
            echo<<<EOT
<div class="container py-4"><div class="text-center"><h5>{$lang['PREFS']['USERPREFS']}</h5><p>{$lang['PREFS']['NOPREFSETTABLE']}</p>
<a class="btn btn-primary" href="index.php">$lang[IMPORTCLICK]</a></div></div>
EOT;
            echo renderPrefPageFooter();
        }
        exit;
    }

    if (!$ajax) sendNoCacheHeaders('Content-Type: text/html; charset="windows-1252";');

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
            echo renderPrefPageHeader($lang['PREFS']['USERPREFS']);
            echo <<<EOT
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
EOT;
            echo renderPrefPageFooter();
        }
        exit;
    }

    $ddstyle = 'style="margin:3px 0 0 5px"';
    if (!$ajax) {
        // Output head without closing </head> or <body> yet -- the JS block comes next
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
        renderPreferenceDropdown(
            'actorsort',
            $actorsort,
            $siteactorsort,
            array(
                '0' => $lang['PREFS']['ACTORSORT'][0],
                '1' => $lang['PREFS']['ACTORSORT'][1],
                '2' => $lang['PREFS']['ACTORSORT'][2],
                'sitedefault' => $lang['PREFS']['SITEDEFAULT'] . ' [' . $lang['PREFS']['ACTORSORT'][$siteactorsort] . ']',
            ),
            $lang['PREFS']['ACTORSORT']['NAME'],
            $lang['PREFS']['ACTORSORT'],
            $ddstyle
        );
    }

    /* Column options shared by secondcol and thirdcol */
    $columnOptionValues = array('released','productionyear','purchasedate','collectionnumber','runningtime','rating','genres','reviews','director');

    if ($allowsecondcol) {
        $opts = array();
        foreach ($columnOptionValues as $v) {
            $opts[$v] = $lang['PREFS']['COLUMNS'][$v];
        }
        $opts['sitedefault'] = $lang['PREFS']['SITEDEFAULT'] . ' [' . $lang['PREFS']['COLUMNS'][$sitesecondcol] . ']';
        $opts['none'] = $lang['PREFS']['COLUMNS']['none'];

        renderPreferenceDropdown(
            'secondcol',
            $secondcol,
            $sitesecondcol,
            $opts,
            $lang['PREFS']['COLUMNS']['SECONDNAME'],
            $lang['PREFS']['COLUMNS'],
            $ddstyle
        );
    }

    if ($allowthirdcol) {
        $opts = array();
        foreach ($columnOptionValues as $v) {
            $opts[$v] = $lang['PREFS']['COLUMNS'][$v];
        }
        $opts['sitedefault'] = $lang['PREFS']['SITEDEFAULT'] . ' [' . $lang['PREFS']['COLUMNS'][$sitethirdcol] . ']';
        $opts['none'] = $lang['PREFS']['COLUMNS']['none'];

        renderPreferenceDropdown(
            'thirdcol',
            $thirdcol,
            $sitethirdcol,
            $opts,
            $lang['PREFS']['COLUMNS']['THIRDNAME'],
            $lang['PREFS']['COLUMNS'],
            $ddstyle
        );
    }

    if ($allowdefaultsorttype) {
        $z = $$sitedefaultsorttype;
        $displaysitedefaultsorttype = $lang['PREFS']['DEFAULTSORTTYPE'][$sitedefaultsorttype].$lang['PREFS']['COLUMNS'][$z];

        /* For the "current value" display when cookie is set, we need
           custom logic because the display combines two lang lookups.
           We handle this by overriding $a after renderPreferenceDropdown
           would compute it -- but actually the simplest approach that
           keeps identical output is to handle the current-value text
           inline, since renderPreferenceDropdown uses $langSection[$currentValue]. */

        // Build a synthetic langSection that maps the value to its composite display string
        $dstLangSection = array(
            'firstcol'  => $lang['PREFS']['DEFAULTSORTTYPE']['firstcol'].$lang['PREFS']['COLUMNS'][$firstcol],
            'secondcol' => $lang['PREFS']['DEFAULTSORTTYPE']['secondcol'].$lang['PREFS']['COLUMNS'][$secondcol],
            'thirdcol'  => $lang['PREFS']['DEFAULTSORTTYPE']['thirdcol'].$lang['PREFS']['COLUMNS'][$thirdcol],
        );

        // Current value display: when cookie is set, original code did:
        //   $z = $$defaultsorttype; $a = DEFAULTSORTTYPE[$defaultsorttype].COLUMNS[$z]
        // That equals dstLangSection[$defaultsorttype].
        // For sitedefault display, original used $displaysitedefaultsorttype.
        // We need a custom siteDefault display. In our helper, siteDefault display
        // looks up $langSection[$siteDefault], so we put the right value into a
        // synthetic section.
        $dstLangSection[$sitedefaultsorttype] = $displaysitedefaultsorttype;

        renderPreferenceDropdown(
            'defaultsorttype',
            $defaultsorttype,
            $sitedefaultsorttype,
            array(
                'firstcol'    => $lang['PREFS']['DEFAULTSORTTYPE']['firstcol'].$lang['PREFS']['COLUMNS'][$firstcol],
                'secondcol'   => $lang['PREFS']['DEFAULTSORTTYPE']['secondcol'].$lang['PREFS']['COLUMNS'][$secondcol],
                'thirdcol'    => $lang['PREFS']['DEFAULTSORTTYPE']['thirdcol'].$lang['PREFS']['COLUMNS'][$thirdcol],
                'sitedefault' => $lang['PREFS']['SITEDEFAULT'] . ' [' . $displaysitedefaultsorttype . ']',
            ),
            $lang['PREFS']['DEFAULTSORTTYPE']['NAME'],
            $dstLangSection,
            $ddstyle
        );
    }

    if ($allowtitledesc) {
        renderPreferenceDropdown(
            'titledesc',
            $titledesc,
            $sitetitledesc,
            array(
                '0' => $lang['PREFS']['TITLEDESC'][0],
                '1' => $lang['PREFS']['TITLEDESC'][1],
                '2' => $lang['PREFS']['TITLEDESC'][2],
                '3' => $lang['PREFS']['TITLEDESC'][3],
                'sitedefault' => $lang['PREFS']['SITEDEFAULT'] . ' [' . $lang['PREFS']['TITLEDESC'][$sitetitledesc] . ']',
            ),
            $lang['PREFS']['TITLEDESC']['NAME'],
            $lang['PREFS']['TITLEDESC'],
            $ddstyle
        );
    }

    if ($allowlocale) {
        renderPreferenceDropdown(
            'locale',
            $locale,
            $sitelocale,
            array(
                'en' => $lang['PREFS']['LOCALE']['en'],
                'de' => $lang['PREFS']['LOCALE']['de'],
                'no' => $lang['PREFS']['LOCALE']['no'],
                'fr' => $lang['PREFS']['LOCALE']['fr'],
                'nl' => $lang['PREFS']['LOCALE']['nl'],
                'sv' => $lang['PREFS']['LOCALE']['sv'],
                'dk' => $lang['PREFS']['LOCALE']['dk'],
                'fi' => $lang['PREFS']['LOCALE']['fi'],
                'ru' => $lang['PREFS']['LOCALE']['ru'],
                'sitedefault' => $lang['PREFS']['SITEDEFAULT'] . ' [' . $lang['PREFS']['LOCALE'][$sitelocale] . ']',
            ),
            $lang['PREFS']['LOCALE']['NAME'],
            $lang['PREFS']['LOCALE'],
            $ddstyle
        );
    }

    if ($allowstickyboxsets) {
        renderPreferenceDropdown(
            'stickyboxsets',
            $stickyboxsets,
            $sitestickyboxsets,
            array(
                '1' => $lang['PREFS']['STICKYBOXSETS'][1],
                '0' => $lang['PREFS']['STICKYBOXSETS'][0],
                'sitedefault' => $lang['PREFS']['SITEDEFAULT'] . ' [' . $lang['PREFS']['STICKYBOXSETS'][$sitestickyboxsets] . ']',
            ),
            $lang['PREFS']['STICKYBOXSETS']['NAME'],
            $lang['PREFS']['STICKYBOXSETS'],
            $ddstyle
        );
    }

    if ($allowpopupimages) {
        renderPreferenceDropdown(
            'popupimages',
            $popupimages,
            $sitepopupimages,
            array(
                '1' => $lang['PREFS']['POPUPIMAGES'][1],
                '0' => $lang['PREFS']['POPUPIMAGES'][0],
                'sitedefault' => $lang['PREFS']['SITEDEFAULT'] . ' [' . $lang['PREFS']['POPUPIMAGES'][$sitepopupimages] . ']',
            ),
            $lang['PREFS']['POPUPIMAGES']['NAME'],
            $lang['PREFS']['POPUPIMAGES'],
            $ddstyle
        );
    }

    if ($allowtitlesperpage) {
        $siteTitlesPerPage = isset($siteTitlesPerPage) ? (int)$siteTitlesPerPage : 0;

        // Build a langSection that maps integer keys to their display labels,
        // matching the original fallback logic for current-value display.
        $tppLangSection = array();
        foreach (array(0, 25, 50, 100, 200) as $v) {
            $tppLangSection[$v] = isset($lang['PREFS']['TITLESPERPAGE'][$v]) ? $lang['PREFS']['TITLESPERPAGE'][$v] : "$v per page";
        }
        // For current value display when cookie is set, original used $TitlesPerPage
        $tppLangSection[(int)$TitlesPerPage] = isset($lang['PREFS']['TITLESPERPAGE'][(int)$TitlesPerPage])
            ? $lang['PREFS']['TITLESPERPAGE'][(int)$TitlesPerPage]
            : "$TitlesPerPage per page";
        // For site default display
        $sitedefval = isset($lang['PREFS']['TITLESPERPAGE'][$siteTitlesPerPage]) ? $lang['PREFS']['TITLESPERPAGE'][$siteTitlesPerPage] : "$siteTitlesPerPage";
        $tppLangSection[$siteTitlesPerPage] = $sitedefval;

        renderPreferenceDropdown(
            'titlesperpage',
            (int)$TitlesPerPage,
            $siteTitlesPerPage,
            array(
                '0'   => $lang['PREFS']['TITLESPERPAGE'][0],
                '25'  => $lang['PREFS']['TITLESPERPAGE'][25],
                '50'  => $lang['PREFS']['TITLESPERPAGE'][50],
                '100' => $lang['PREFS']['TITLESPERPAGE'][100],
                '200' => $lang['PREFS']['TITLESPERPAGE'][200],
                'sitedefault' => $lang['PREFS']['SITEDEFAULT'] . ' [' . $lang['PREFS']['TITLESPERPAGE'][$siteTitlesPerPage] . ']',
            ),
            $lang['PREFS']['TITLESPERPAGE']['NAME'],
            $tppLangSection,
            $ddstyle
        );
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
        echo renderPrefPageFooter();
    }
