<?php
/**
 * phpDVDProfiler Installer
 * Creates database, tables, directories, and generates local configuration.
 */
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

$pageTitle = 'phpDVDProfiler Installer';
$schemaFile = __DIR__ . '/db/schema.sql';
$configTarget = __DIR__ . '/config/localsiteconfig.php';
$step = isset($_GET['step']) ? (int)$_GET['step'] : 0;
if (isset($_POST['step'])) $step = (int)$_POST['step'];

// Directories that need to exist (relative to project root)
$requiredDirs = [
    'images'             => 'DVD cover images',
    'images/thumbnails'  => 'Cover thumbnails (writable)',
    'imagecache'         => 'Resized image cache (writable)',
    'headshots/cast'     => 'Actor headshot images',
    'headshots/crew'     => 'Crew headshot images',
    'skins'              => 'Custom skin templates',
    'import'             => 'XML collection upload (writable)',
];

$writableDirs = ['images', 'images/thumbnails', 'imagecache', 'import'];

// PHP extensions
$requiredExt = [
    'mysqli'   => 'MySQL database connection',
    'xml'      => 'XML parsing for collection import',
    'mbstring' => 'Multi-byte string handling',
];
$optionalExt = [
    'gd'   => 'Image resizing and thumbnails',
    'intl'  => 'Internationalization support',
    'curl'  => 'Remote image fetching',
];

// ---- Helper functions ----

function renderHeader($pageTitle, $step) {
    echo '<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>' . htmlspecialchars($pageTitle) . '</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #1a1d21; }
.installer-card { max-width: 700px; margin: 40px auto; }
.step-indicator { font-size: 0.85rem; color: #6c757d; }
.check-ok { color: #198754; }
.check-warn { color: #ffc107; }
.check-fail { color: #dc3545; }
</style>
</head>
<body>
<div class="container">
<div class="installer-card">
<h2 class="text-center mb-1 mt-4">phpDVDProfiler Installer</h2>
<p class="text-center step-indicator mb-4">Step ' . ($step + 1) . ' of 4</p>';
}

function renderFooter() {
    echo '</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>';
}

function checkIcon($ok, $warn = false) {
    if ($ok) return '<span class="check-ok">&#10004;</span>';
    if ($warn) return '<span class="check-warn">&#9888;</span>';
    return '<span class="check-fail">&#10008;</span>';
}

// ---- STEP 0: System Check ----
if ($step === 0) {
    renderHeader($pageTitle, $step);

    $allOk = true;
    echo '<div class="card bg-dark border-secondary mb-3"><div class="card-body">';
    echo '<h5 class="card-title">System Requirements</h5>';
    echo '<table class="table table-dark table-sm mb-0">';

    // PHP version
    $phpOk = version_compare(PHP_VERSION, '8.0', '>=');
    if (!$phpOk) $allOk = false;
    echo '<tr><td>' . checkIcon($phpOk) . ' PHP Version</td><td>' . PHP_VERSION . ($phpOk ? '' : ' <small class="text-danger">(8.0+ required)</small>') . '</td></tr>';

    // Required extensions
    foreach ($requiredExt as $ext => $desc) {
        $loaded = extension_loaded($ext);
        if (!$loaded) $allOk = false;
        echo '<tr><td>' . checkIcon($loaded) . " $ext</td><td>$desc</td></tr>";
    }

    // Optional extensions
    foreach ($optionalExt as $ext => $desc) {
        $loaded = extension_loaded($ext);
        echo '<tr><td>' . checkIcon($loaded, true) . " $ext <small>(optional)</small></td><td>$desc</td></tr>";
    }

    // Schema file
    $schemaOk = is_readable($schemaFile);
    if (!$schemaOk) $allOk = false;
    echo '<tr><td>' . checkIcon($schemaOk) . ' db/schema.sql</td><td>Database schema file</td></tr>';

    // Config writable
    $configDir = dirname($configTarget);
    $configWritable = is_writable($configDir);
    if (!$configWritable) $allOk = false;
    echo '<tr><td>' . checkIcon($configWritable) . ' config/ writable</td><td>For generating localsiteconfig.php</td></tr>';

    echo '</table></div></div>';

    // Directories
    echo '<div class="card bg-dark border-secondary mb-3"><div class="card-body">';
    echo '<h5 class="card-title">Directories</h5>';
    echo '<table class="table table-dark table-sm mb-0">';
    foreach ($requiredDirs as $dir => $desc) {
        $path = __DIR__ . '/' . $dir;
        $exists = is_dir($path);
        $writable = $exists && is_writable($path);
        $needsWrite = in_array($dir, $writableDirs);
        $ok = $exists && (!$needsWrite || $writable);
        echo '<tr><td>' . checkIcon($ok, !$exists) . " $dir</td><td>$desc" .
             ($exists && $needsWrite && !$writable ? ' <small class="text-danger">(not writable)</small>' : '') .
             (!$exists ? ' <small class="text-warning">(will be created)</small>' : '') .
             '</td></tr>';
    }
    echo '</table></div></div>';

    // Existing config check
    if (file_exists($configTarget)) {
        echo '<div class="alert alert-warning"><strong>Note:</strong> <code>config/localsiteconfig.php</code> already exists. It will be overwritten if you continue.</div>';
    }

    if ($allOk) {
        echo '<form method="post" action="install.php">
        <input type="hidden" name="step" value="1">
        <div class="d-grid"><button type="submit" class="btn btn-primary btn-lg">Continue &rarr;</button></div>
        </form>';
    } else {
        echo '<div class="alert alert-danger">Please fix the issues above before continuing.</div>';
        echo '<form method="post" action="install.php">
        <input type="hidden" name="step" value="0">
        <div class="d-grid"><button type="submit" class="btn btn-secondary">Re-check</button></div>
        </form>';
    }
    renderFooter();
    exit;
}

// ---- STEP 1: Database Configuration Form ----
if ($step === 1) {
    // Restore previous values from session
    $defaults = [
        'dbhost' => $_SESSION['inst_dbhost'] ?? 'localhost',
        'dbport' => $_SESSION['inst_dbport'] ?? '',
        'dbname' => $_SESSION['inst_dbname'] ?? 'phpdvdprofiler',
        'dbuser' => $_SESSION['inst_dbuser'] ?? 'root',
        'dbpasswd' => $_SESSION['inst_dbpasswd'] ?? '',
        'table_prefix' => $_SESSION['inst_table_prefix'] ?? 'DVDPROFILER_',
        'update_login' => $_SESSION['inst_update_login'] ?? 'admin',
        'update_pass' => $_SESSION['inst_update_pass'] ?? '',
        'sitetitle' => $_SESSION['inst_sitetitle'] ?? 'My DVD Collection',
        'tmdb_api_key' => $_SESSION['inst_tmdb_api_key'] ?? '',
    ];

    renderHeader($pageTitle, $step);
    $err = $_SESSION['inst_error'] ?? '';
    unset($_SESSION['inst_error']);
    if ($err) echo '<div class="alert alert-danger">' . htmlspecialchars($err) . '</div>';

    echo '<form method="post" action="install.php">
    <input type="hidden" name="step" value="2">
    <div class="card bg-dark border-secondary mb-3"><div class="card-body">
    <h5 class="card-title">Database Connection</h5>
    <div class="row g-2 mb-2">
      <div class="col-8"><label class="form-label">Host</label><input type="text" name="dbhost" class="form-control form-control-sm" value="' . htmlspecialchars($defaults['dbhost']) . '"></div>
      <div class="col-4"><label class="form-label">Port <small class="text-muted">(empty=default)</small></label><input type="text" name="dbport" class="form-control form-control-sm" value="' . htmlspecialchars($defaults['dbport']) . '"></div>
    </div>
    <div class="mb-2"><label class="form-label">Database Name</label><input type="text" name="dbname" class="form-control form-control-sm" value="' . htmlspecialchars($defaults['dbname']) . '">
    <small class="form-text text-muted">Will be created if it doesn\'t exist.</small></div>
    <div class="row g-2 mb-2">
      <div class="col-6"><label class="form-label">DB Username</label><input type="text" name="dbuser" class="form-control form-control-sm" value="' . htmlspecialchars($defaults['dbuser']) . '"></div>
      <div class="col-6"><label class="form-label">DB Password</label><input type="password" name="dbpasswd" class="form-control form-control-sm" value="' . htmlspecialchars($defaults['dbpasswd']) . '"></div>
    </div>
    <div class="mb-0"><label class="form-label">Table Prefix</label><input type="text" name="table_prefix" class="form-control form-control-sm" value="' . htmlspecialchars($defaults['table_prefix']) . '"></div>
    </div></div>

    <div class="card bg-dark border-secondary mb-3"><div class="card-body">
    <h5 class="card-title">Site Settings</h5>
    <div class="mb-2"><label class="form-label">Site Title</label><input type="text" name="sitetitle" class="form-control form-control-sm" value="' . htmlspecialchars($defaults['sitetitle']) . '"></div>
    <div class="row g-2 mb-2">
      <div class="col-6"><label class="form-label">Admin Username</label><input type="text" name="update_login" class="form-control form-control-sm" value="' . htmlspecialchars($defaults['update_login']) . '"></div>
      <div class="col-6"><label class="form-label">Admin Password</label><input type="password" name="update_pass" class="form-control form-control-sm" value="' . htmlspecialchars($defaults['update_pass']) . '"></div>
    </div>
    <div class="mb-0"><label class="form-label">TMDB API Key <small class="text-muted">(optional, for cover fetching)</small></label><input type="text" name="tmdb_api_key" class="form-control form-control-sm" value="' . htmlspecialchars($defaults['tmdb_api_key']) . '" placeholder="Get one at themoviedb.org/settings/api">
    </div>
    </div></div>

    <div class="d-flex gap-2">
      <a href="install.php?step=0" class="btn btn-outline-secondary">&larr; Back</a>
      <button type="submit" class="btn btn-primary flex-grow-1">Install &rarr;</button>
    </div>
    </form>';
    renderFooter();
    exit;
}

// ---- STEP 2: Execute Installation ----
if ($step === 2) {
    $dbhost = trim($_POST['dbhost'] ?? 'localhost');
    $dbport = trim($_POST['dbport'] ?? '');
    $dbname = trim($_POST['dbname'] ?? 'phpdvdprofiler');
    $dbuser = trim($_POST['dbuser'] ?? 'root');
    $dbpasswd = $_POST['dbpasswd'] ?? '';
    $table_prefix = trim($_POST['table_prefix'] ?? 'DVDPROFILER_');
    $update_login = trim($_POST['update_login'] ?? 'admin');
    $update_pass = $_POST['update_pass'] ?? '';
    $sitetitle = trim($_POST['sitetitle'] ?? 'My DVD Collection');
    $tmdb_api_key = trim($_POST['tmdb_api_key'] ?? '');

    // Save to session for back navigation
    foreach (['dbhost','dbport','dbname','dbuser','dbpasswd','table_prefix','update_login','update_pass','sitetitle','tmdb_api_key'] as $k) {
        $_SESSION['inst_' . $k] = $$k;
    }

    // Validate
    if ($dbname === '' || $dbuser === '' || $update_login === '' || $update_pass === '') {
        $_SESSION['inst_error'] = 'All fields are required (database name, user, admin login & password).';
        header('Location: install.php?step=1');
        exit;
    }

    // Validate table_prefix: only alphanumeric and underscore allowed (prevents SQL injection via schema replacement)
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table_prefix)) {
        $_SESSION['inst_error'] = 'Table prefix may only contain letters, numbers, and underscores.';
        header('Location: install.php?step=1');
        exit;
    }

    // Connect to MySQL server (without database)
    $port = $dbport !== '' ? (int)$dbport : 3306;
    $conn = @new mysqli($dbhost, $dbuser, $dbpasswd, '', $port);
    if ($conn->connect_error) {
        $_SESSION['inst_error'] = 'Database connection failed: ' . $conn->connect_error;
        header('Location: install.php?step=1');
        exit;
    }

    $log = [];

    // Create database if not exists
    $dbnameSafe = $conn->real_escape_string($dbname);
    if ($conn->query("CREATE DATABASE IF NOT EXISTS `$dbnameSafe` CHARACTER SET latin1")) {
        $log[] = ['ok', "Database <code>$dbname</code> ready."];
    } else {
        $log[] = ['fail', "Could not create database: " . $conn->error];
    }

    // Select database
    if (!$conn->select_db($dbname)) {
        $_SESSION['inst_error'] = "Cannot select database '$dbname': " . $conn->error;
        $conn->close();
        header('Location: install.php?step=1');
        exit;
    }
    $conn->set_charset('utf8');

    // Load and execute schema
    $schema = file_get_contents($schemaFile);
    if ($schema === false) {
        $_SESSION['inst_error'] = 'Cannot read db/schema.sql';
        $conn->close();
        header('Location: install.php?step=1');
        exit;
    }

    // Replace table prefix if different
    if ($table_prefix !== 'DVDPROFILER_') {
        $schema = str_replace('DVDPROFILER_', $table_prefix, $schema);
    }

    // Parse and execute SQL statements (# is comment)
    $statements = [];
    $current = '';
    foreach (explode("\n", $schema) as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || $trimmed[0] === '#') continue;
        $current .= $line . "\n";
        if (substr(rtrim($line), -1) === ';') {
            $statements[] = trim($current);
            $current = '';
        }
    }

    $tablesCreated = 0;
    $errors = 0;
    foreach ($statements as $sql) {
        if ($conn->query($sql)) {
            if (stripos($sql, 'CREATE TABLE') !== false) $tablesCreated++;
        } else {
            $errors++;
            $short = substr($sql, 0, 80);
            $log[] = ['fail', "SQL Error: " . $conn->error . " <small>($short...)</small>"];
        }
    }
    $log[] = ['ok', "$tablesCreated tables created, $errors errors."];

    $conn->close();

    // Create directories
    foreach ($requiredDirs as $dir => $desc) {
        $path = __DIR__ . '/' . $dir;
        if (!is_dir($path)) {
            if (@mkdir($path, 0775, true)) {
                $log[] = ['ok', "Created directory <code>$dir/</code>"];
            } else {
                $log[] = ['warn', "Could not create <code>$dir/</code> - create it manually."];
            }
        }
    }

    // Generate localsiteconfig.php (use var_export for safe PHP string literals)
    $configContent = "<?php\n// Generated by phpDVDProfiler installer on " . date('Y-m-d H:i:s') . "\n"
        . "\$dbhost = " . var_export($dbhost, true) . ";\n"
        . "\$dbport = " . var_export($dbport, true) . ";\n"
        . "\$dbname = " . var_export($dbname, true) . ";\n"
        . "\$dbuser = " . var_export($dbuser, true) . ";\n"
        . "\$dbpasswd = " . var_export($dbpasswd, true) . ";\n"
        . "\$dbtype = 'mysqli';\n"
        . "\$table_prefix = " . var_export($table_prefix, true) . ";\n"
        . "\n"
        . "\$update_login = " . var_export($update_login, true) . ";\n"
        . "\$update_pass = " . var_export($update_pass, true) . ";\n"
        . "\$force_formlogin = 1;\n"
        . "\n"
        . "\$sitetitle = " . var_export($sitetitle, true) . ";\n"
        . "\$skinfile = 'internal';\n"
        . "\$allowskins = true;\n"
        . "\$allowtitlesperpage = true;\n"
        . "\$allowsecondcol = true;\n"
        . "\$allowthirdcol = true;\n"
        . "\$allowdefaultsorttype = true;\n"
        . "\$allowstickyboxsets = true;\n"
        . "\$allowlocale = true;\n"
        . "\$allowpopupimages = true;\n"
        . "\$allowactorsort = true;\n"
        . "\$allowtitledesc = true;\n"
        . "\n"
        . "\$tmdb_api_key = " . var_export($tmdb_api_key, true) . ";\n";

    if (@file_put_contents($configTarget, $configContent)) {
        $log[] = ['ok', "Configuration saved to <code>config/localsiteconfig.php</code>"];
    } else {
        $log[] = ['fail', "Could not write <code>config/localsiteconfig.php</code> - create it manually."];
    }

    // Save log to session for display
    $_SESSION['inst_log'] = $log;
    $_SESSION['inst_done'] = true;

    header('Location: install.php?step=3');
    exit;
}

// ---- STEP 3: Results ----
if ($step === 3) {
    $log = $_SESSION['inst_log'] ?? [];
    renderHeader($pageTitle, $step);

    echo '<div class="card bg-dark border-secondary mb-3"><div class="card-body">';
    echo '<h5 class="card-title">Installation Results</h5>';

    $hasErrors = false;
    foreach ($log as $entry) {
        [$type, $msg] = $entry;
        if ($type === 'ok') {
            echo '<div class="mb-1"><span class="check-ok">&#10004;</span> ' . $msg . '</div>';
        } elseif ($type === 'warn') {
            echo '<div class="mb-1"><span class="check-warn">&#9888;</span> ' . $msg . '</div>';
        } else {
            echo '<div class="mb-1"><span class="check-fail">&#10008;</span> ' . $msg . '</div>';
            $hasErrors = true;
        }
    }
    echo '</div></div>';

    if (!$hasErrors) {
        echo '<div class="card bg-dark border-secondary mb-3"><div class="card-body">';
        echo '<h5 class="card-title check-ok">Installation Complete!</h5>';
        echo '<p>Next steps:</p>';
        echo '<ol>';
        echo '<li>Export your DVD collection as XML from DVD Profiler</li>';
        echo '<li>Upload the XML file and cover images to the server</li>';
        echo '<li>Run the <a href="index.php?action=update">collection import</a></li>';
        echo '<li><strong class="text-warning">Delete this file (install.php) for security!</strong></li>';
        echo '</ol>';
        echo '</div></div>';

        echo '<div class="d-flex gap-2">';
        echo '<a href="index.php" class="btn btn-primary flex-grow-1">Go to phpDVDProfiler &rarr;</a>';
        echo '<a href="index.php?action=update" class="btn btn-outline-success">Import Collection</a>';
        echo '</div>';
    } else {
        echo '<div class="d-flex gap-2">';
        echo '<a href="install.php?step=1" class="btn btn-outline-secondary">&larr; Back to Settings</a>';
        echo '<a href="install.php?step=0" class="btn btn-outline-secondary">Start Over</a>';
        echo '</div>';
    }

    // Clean up session
    unset($_SESSION['inst_log']);

    renderFooter();
    exit;
}
