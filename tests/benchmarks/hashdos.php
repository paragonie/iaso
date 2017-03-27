<?php

use ParagonIE\Iaso\JSON;
require '../../vendor/autoload.php';

/*
 * 26 MB PoC file
if (!file_exists('1048576.json')) {
    \shell_exec("wget https://raw.githubusercontent.com/bk2204/php-hash-dos/master/example/1048576.json");
}
$string = \file_get_contents('poc.json');
*/
$string = \file_get_contents('poc.json');

// Init variables.
$start = $end = 0.00;

$start = \microtime(true);
$native = \json_decode($string);
$end = \microtime(true);

$diff = $end - $start;
echo number_format($diff, 3) . ' seconds (native)', PHP_EOL;

$start = \microtime(true);
$native = JSON::parse($string);
$end = \microtime(true);

$diff = $end - $start;
echo number_format($diff, 3) . ' seconds (Iaso)', PHP_EOL;
