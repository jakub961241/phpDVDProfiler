<?php
define('BASE_PATH', __DIR__ . '/');
set_include_path(
    BASE_PATH . 'core' . PATH_SEPARATOR .
    BASE_PATH . 'config' . PATH_SEPARATOR .
    BASE_PATH . 'db' . PATH_SEPARATOR .
    BASE_PATH . 'lang' . PATH_SEPARATOR .
    BASE_PATH . 'includes' . PATH_SEPARATOR .
    BASE_PATH . 'pages' . PATH_SEPARATOR .
    BASE_PATH . 'graphs' . PATH_SEPARATOR .
    BASE_PATH . 'admin' . PATH_SEPARATOR .
    BASE_PATH . 'js' . PATH_SEPARATOR .
    get_include_path()
);
