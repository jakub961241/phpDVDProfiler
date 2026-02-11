<?php
if (!defined('IN_SCRIPT')) {
    define('IN_SCRIPT', 1);
    require_once(__DIR__ . '/../bootstrap.php');
    include_once('global.php');
}

$cf_standalone = empty($ajax);
$TMDB_API_KEY = (isset($tmdb_api_key) && $tmdb_api_key !== '') ? $tmdb_api_key : '134b15beef358903890a761f41dd98d8';
$TMDB_IMG_BASE = 'https://image.tmdb.org/t/p/';

// Handle AJAX API actions (search / download)
if (isset($_GET['tmdb_action'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $db->convertToUtf8 = true;

    if ($_GET['tmdb_action'] === 'search') {
        $query = $_GET['query'] ?? '';
        $year = $_GET['year'] ?? '';
        $url = "https://api.themoviedb.org/3/search/movie?api_key=$TMDB_API_KEY"
             . "&query=" . urlencode($query)
             . "&language=cs-CZ&include_adult=false";
        if ($year) $url .= "&year=$year";
        $ctx = stream_context_create(['http' => ['timeout' => 10]]);
        $response = @file_get_contents($url, false, $ctx);
        echo ($response !== false) ? $response : json_encode(['error' => 'TMDB API request failed']);
        exit;
    }

    if ($_GET['tmdb_action'] === 'download') {
        $mediaid = $_GET['mediaid'] ?? '';
        $poster_path = $_GET['poster_path'] ?? '';
        $size = $_GET['size'] ?? 'original';
        if (!$mediaid || !$poster_path) { echo json_encode(['error' => 'Missing parameters']); exit; }

        $cleanId = rtrim($mediaid, "\x00");
        $filename = $img_physpath . $cleanId . 'f.jpg';
        $thumbname = $img_physpath . $thumbnails . '/' . $cleanId . 'f.jpg';

        $ctx = stream_context_create(['http' => ['timeout' => 30]]);
        $imgData = @file_get_contents($TMDB_IMG_BASE . $size . $poster_path, false, $ctx);
        if ($imgData === false) { echo json_encode(['error' => 'Failed to download image']); exit; }
        if (file_put_contents($filename, $imgData) === false) { echo json_encode(['error' => "Failed to save"]); exit; }

        if (extension_loaded('gd')) {
            $src = @imagecreatefromstring($imgData);
            if ($src) {
                $w = imagesx($src); $h = imagesy($src);
                $nw = 150; $nh = (int)($h * $nw / $w);
                $thumb = imagecreatetruecolor($nw, $nh);
                imagecopyresampled($thumb, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
                imagejpeg($thumb, $thumbname, 80);
                imagedestroy($thumb); imagedestroy($src);
            }
        }
        echo json_encode(['success' => true, 'filename' => $filename, 'size' => strlen($imgData)]);
        exit;
    }
}

// Page content
$db->convertToUtf8 = true;
$result = $db->sql_query("SELECT id, title, originaltitle, productionyear FROM {$table_prefix}dvd ORDER BY title ASC");
$dvds = [];
while ($row = $db->sql_fetchrow($result)) {
    $cleanId = rtrim($row['id'], "\x00");
    $dvds[] = [
        'cleanId' => $cleanId,
        'title' => $row['title'],
        'originaltitle' => $row['originaltitle'],
        'year' => $row['productionyear'],
        'hasCover' => file_exists($img_physpath . $cleanId . 'f.jpg'),
    ];
}
$db->sql_freeresult($result);
$total = count($dvds);
$withCover = count(array_filter($dvds, fn($d) => $d['hasCover']));
$missing = $total - $withCover;

// --- Output ---
if ($cf_standalone) {
    header('Content-Type: text/html; charset=UTF-8');
}
?>
<?php if ($cf_standalone): ?>
<!DOCTYPE html>
<html lang="cs" data-bs-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $lang['CF']['TITLE'] ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
<?php endif; ?>
<style>
.cf-table td, .cf-table th { vertical-align: middle; font-size: 0.9rem; }
.cf-poster-grid { display: flex; flex-wrap: wrap; gap: 10px; padding: 8px 0; }
.cf-poster-item { text-align: center; width: 120px; }
.cf-poster-item img { max-width: 110px; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.3); cursor: pointer; border: 2px solid transparent; transition: border-color 0.2s; }
.cf-poster-item img:hover { border-color: #0d6efd; }
.cf-poster-item img.selected { border-color: #198754; }
.cf-poster-item .cf-ptitle { font-size: 0.75em; max-height: 2.4em; overflow: hidden; }
.cf-poster-item .cf-pyear { font-size: 0.8em; opacity: 0.7; }
#cf-progress-bar { transition: width 0.3s; }
</style>

<div class="p-2">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><?= $lang['CF']['TITLE'] ?></h5>
    <div>
      <span class="badge bg-success"><?= $withCover ?> <?= $lang['CF']['WITHCOVER'] ?></span>
      <span class="badge bg-danger"><?= $missing ?> <?= $lang['CF']['NOCOVER'] ?></span>
      <span class="badge bg-secondary"><?= $total ?> <?= $lang['CF']['TOTAL'] ?></span>
    </div>
  </div>

<?php if ($missing > 0): ?>
  <div class="d-flex align-items-center gap-3 mb-3">
    <button class="btn btn-sm btn-primary" onclick="cfAutoFetchAll()"><?= $lang['CF']['AUTOFETCH'] ?></button>
    <div class="flex-grow-1">
      <div class="progress" style="height: 18px; display:none" id="cf-progress-container">
        <div class="progress-bar" id="cf-progress-bar" style="width: 0%">0 / <?= $missing ?></div>
      </div>
    </div>
    <span id="cf-auto-status" style="font-size:0.85rem"></span>
  </div>
<?php endif; ?>

  <table class="table table-sm table-hover cf-table">
    <thead>
      <tr>
        <th style="width:40px"></th>
        <th><?= $lang['CF']['COLNAME'] ?></th>
        <th><?= $lang['CF']['COLORIGINAL'] ?></th>
        <th style="width:50px"><?= $lang['CF']['COLYEAR'] ?></th>
        <th style="width:100px"></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($dvds as $dvd): ?>
      <tr id="cf-row-<?= htmlspecialchars($dvd['cleanId']) ?>">
        <td class="text-center" id="cf-st-<?= htmlspecialchars($dvd['cleanId']) ?>">
          <?= $dvd['hasCover'] ? '<span class="text-success">OK</span>' : '<span class="text-danger">X</span>' ?>
        </td>
        <td><?= htmlspecialchars($dvd['title']) ?></td>
        <td class="text-muted"><?= htmlspecialchars($dvd['originaltitle']) ?></td>
        <td><?= htmlspecialchars($dvd['year']) ?></td>
        <td>
          <button class="btn btn-sm btn-outline-info"
            onclick="cfSearch('<?= htmlspecialchars(addslashes($dvd['cleanId']), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($dvd['title']), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($dvd['year']), ENT_QUOTES, 'UTF-8') ?>')">
            <?= $lang['CF']['SEARCH'] ?>
          </button>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Modal -->
<div class="modal fade" id="cfModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title" id="cfModalTitle"><?= $lang['CF']['SELECTCOVER'] ?></h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="input-group input-group-sm mb-2">
          <input type="text" class="form-control" id="cfModalQ" placeholder="<?= $lang['CF']['NAMEPH'] ?>">
          <input type="text" class="form-control" id="cfModalY" placeholder="<?= $lang['CF']['YEARPH'] ?>" style="max-width:80px">
          <button class="btn btn-outline-info" onclick="cfModalSearch()"><?= $lang['CF']['SEARCH'] ?></button>
        </div>
        <div id="cfModalRes" class="cf-poster-grid"></div>
        <div id="cfModalLoad" style="display:none" class="text-center py-3">
          <div class="spinner-border spinner-border-sm text-info"></div> <?= $lang['CF']['SEARCHING'] ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
    var cfLang = {
        noresults: <?= json_encode(html_entity_decode($lang['CF']['NORESULTS'], ENT_QUOTES, 'UTF-8')) ?>,
        alldone: <?= json_encode(html_entity_decode($lang['CF']['ALLDONE'], ENT_QUOTES, 'UTF-8')) ?>,
        confirmfetch: <?= json_encode(html_entity_decode($lang['CF']['CONFIRMFETCH'], ENT_QUOTES, 'UTF-8')) ?>,
        done: <?= json_encode(html_entity_decode($lang['CF']['DONE'], ENT_QUOTES, 'UTF-8')) ?>
    };
    var cfCurId = '';
    var cfModalInstance = new bootstrap.Modal(document.getElementById('cfModal'));

    window.cfSearch = function(id, title, year) {
        cfCurId = id;
        document.getElementById('cfModalTitle').textContent = title;
        document.getElementById('cfModalQ').value = title;
        document.getElementById('cfModalY').value = (year && year !== '0') ? year : '';
        document.getElementById('cfModalRes').innerHTML = '';
        cfModalInstance.show();
        cfModalSearch();
    };

    window.cfModalSearch = function() {
        var q = document.getElementById('cfModalQ').value.trim();
        var y = document.getElementById('cfModalY').value.trim();
        if (!q) return;
        document.getElementById('cfModalLoad').style.display = '';
        document.getElementById('cfModalRes').innerHTML = '';

        var url = 'fetch_covers.php?tmdb_action=search&query=' + encodeURIComponent(q);
        if (y) url += '&year=' + encodeURIComponent(y);

        fetch(url).then(function(r){return r.json();}).then(function(data) {
            document.getElementById('cfModalLoad').style.display = 'none';
            if (data.error) { document.getElementById('cfModalRes').innerHTML = '<span class="text-danger">'+data.error+'</span>'; return; }
            var res = (data.results||[]);
            if (!res.length) { document.getElementById('cfModalRes').innerHTML = '<span class="text-warning">'+cfLang.noresults+'</span>'; return; }
            var h = '';
            res.forEach(function(m) {
                if (!m.poster_path) return;
                var yr = m.release_date ? m.release_date.substring(0,4) : '?';
                h += '<div class="cf-poster-item">'
                   + '<img src="https://image.tmdb.org/t/p/w185'+m.poster_path+'" loading="lazy" onclick="cfDownload(\''+cfCurId+'\',\''+m.poster_path+'\',this)">'
                   + '<div class="cf-ptitle">'+cfEsc(m.title)+'</div>'
                   + '<div class="cf-pyear">'+yr+' | '+(m.vote_average?m.vote_average.toFixed(1):'?')+'/10</div></div>';
            });
            document.getElementById('cfModalRes').innerHTML = h;
        }).catch(function(e) {
            document.getElementById('cfModalLoad').style.display = 'none';
            document.getElementById('cfModalRes').innerHTML = '<span class="text-danger">'+e.message+'</span>';
        });
    };

    window.cfDownload = function(id, path, el) {
        if (el) { document.querySelectorAll('.cf-poster-item img').forEach(function(i){i.classList.remove('selected');}); el.classList.add('selected'); }
        var url = 'fetch_covers.php?tmdb_action=download&mediaid='+encodeURIComponent(id)+'&poster_path='+encodeURIComponent(path)+'&size=original';
        fetch(url).then(function(r){return r.json();}).then(function(d) {
            if (d.error) { alert(d.error); return; }
            var s = document.getElementById('cf-st-'+id);
            if (s) s.innerHTML = '<span class="text-success">OK</span>';
            cfModalInstance.hide();
        }).catch(function(e){ alert(e.message); });
    };

    window.cfAutoFetchAll = function() {
        var missing = [];
        document.querySelectorAll('[id^="cf-st-"]').forEach(function(el) {
            if (el.innerHTML.indexOf('text-danger') !== -1) {
                var id = el.id.replace('cf-st-', '');
                var row = document.getElementById('cf-row-' + id);
                if (row) missing.push({ id: id, title: row.children[1].textContent, year: row.children[3].textContent });
            }
        });
        if (!missing.length) { alert(cfLang.alldone); return; }
        if (!confirm(cfLang.confirmfetch.replace('%d', missing.length))) return;

        var bar = document.getElementById('cf-progress-bar');
        var stat = document.getElementById('cf-auto-status');
        document.getElementById('cf-progress-container').style.display = '';
        var ok = 0, fail = 0;

        function next(i) {
            if (i >= missing.length) { stat.textContent = cfLang.done.replace('%ok',ok).replace('%fail',fail); return; }
            var d = missing[i];
            bar.style.width = ((i+1)/missing.length*100)+'%';
            bar.textContent = (i+1)+' / '+missing.length;
            stat.textContent = d.title + '...';

            var sUrl = 'fetch_covers.php?tmdb_action=search&query='+encodeURIComponent(d.title);
            if (d.year && d.year !== '0') sUrl += '&year='+encodeURIComponent(d.year);

            fetch(sUrl).then(function(r){return r.json();}).then(function(data) {
                var r = (data.results||[]).filter(function(x){return x.poster_path;});
                if (!r.length) { fail++; var s=document.getElementById('cf-st-'+d.id); if(s) s.innerHTML='<span class="text-warning">?</span>'; setTimeout(function(){next(i+1);},300); return; }
                var dlUrl = 'fetch_covers.php?tmdb_action=download&mediaid='+encodeURIComponent(d.id)+'&poster_path='+encodeURIComponent(r[0].poster_path)+'&size=original';
                return fetch(dlUrl).then(function(r){return r.json();}).then(function(dd) {
                    if (dd.success) { ok++; var s=document.getElementById('cf-st-'+d.id); if(s) s.innerHTML='<span class="text-success">OK</span>'; } else { fail++; }
                    setTimeout(function(){next(i+1);},500);
                });
            }).catch(function(){ fail++; setTimeout(function(){next(i+1);},500); });
        }
        next(0);
    };

    function cfEsc(t) { var d=document.createElement('div'); d.appendChild(document.createTextNode(t)); return d.innerHTML; }
})();
</script>
<?php if ($cf_standalone): ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php endif; ?>
