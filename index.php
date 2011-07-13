<?php

require 'Slim/Slim.php';

Slim::init();

function jsonp($x) {
    Slim::response()->header('Content-Type', 'application/json');
    if (array_key_exists('callback', $_GET)) {
        return preg_replace("/[^a-zA-Z0-9]/", "", $_GET['callback']) . '(' .
            json_encode($x) . ')';
    } else { return json_encode($x); }
}

function tiles() {
    $ts = array();
    $d = opendir('tiles');
    $t = false;
    while ($t = readdir($d)) { if ($t[0] !== '.') array_push($ts, $t); }
    return $ts;
}

// TODO: prettify
Slim::get('/', function () {
    $tiles = tiles();
    echo '<ul>' . implode(array_map(function($t) {
        $k = str_replace('.mbtiles', '', $t);
        return "<li><img src='1.0.0/$k/0/0/0.png'>$t</li>";
    }, $tiles)) . '</ul>';
});

Slim::get('/tiles', function () {
    $tiles = tiles();
    echo jsonp($tiles);
});

Slim::get('/1.0.0/:layername', function ($l) {
    $m = 'tiles/' . $l . '.mbtiles';
    if (!file_exists($m)) { echo 'Tileset not found'; return; }
    $db = new SQLite3($m);
    $q = $db->query('SELECT name, value from metadata;');
    $meta = array();
    while ($r = $q->fetchArray(SQLITE3_ASSOC)) {
        $meta[$r['name']] = $r['value'];
    }
    // TODO: minzoom, maxzoom
    echo jsonp($meta);
});

Slim::get('/1.0.0/:layername/:z/:x/:y\.:format', function ($l, $z, $x, $y) {
    $m = 'tiles/' . $l . '.mbtiles';
    if (!file_exists($m)) { echo 'Tileset not found'; return; }
    Slim::response()->header('Content-Type', 'image/png');
    $db = new SQLite3($m);
    $q = $db->query("SELECT tile_data from tiles where zoom_level = $z
        and tile_column = $x and tile_row = $y;");
    $r = $q->fetchArray();
    echo $r[0];
});

Slim::run();

