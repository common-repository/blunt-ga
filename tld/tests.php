<?php
/**
 * @package Tld
 * @author Artur Barseghyan (artur.barseghyan@gmail.com)
 * @version 0.1
 * @license MPL 1.1/GPL 2.0/LGPL 2.1
 * @link http://bitbucket.org/barseghyanartur/php-tld
 *
 * Tests for Tld package.
 */

require 'utils.php';

try {
    // Testing the TLD names loaded
    echo '************ Testing the TLD names loaded' . PHP_EOL;
    Tld::init();
    print_r(Tld::getTldNames());
} catch(Exception $e) {
    echo $e . PHP_EOL;
}

// Testing the good patterns
echo '************ Testing the good patterns' . PHP_EOL;
$goodUrls = array('http://www.google.co.uk', 'http://www.v2.google.co.uk', 'http://www.me.congresodelalengua3.ar');
foreach ($goodUrls as $url) {
    try {
        echo 'Testing URL: ' . $url . PHP_EOL;
        echo Tld::getTld($url) . PHP_EOL . PHP_EOL;
    } catch(Exception $e) {
        echo $e . PHP_EOL;
    }
}

// Testing the bad patterns with optionn fail silently set to true (no exceptions raised)
echo '************ Testing the bad patterns' . PHP_EOL;
$badUrls = array('/index.php?a=1&b=2', 'v2.www.google.com', 'http://www.tld.doesnotexist');
foreach ($badUrls as $url) {
    try {
        echo 'Testing URL: ' . $url . PHP_EOL;
        echo Tld::getTld($url, false, true) . PHP_EOL;
    } catch(Exception $e) {
        echo $e . PHP_EOL;
    }
}

// Testing the bad patterns with optionn fail silently set to false (exceptions raised)
echo '************ Testing the bad patterns' . PHP_EOL;
$badUrls = array('/index.php?a=1&b=2', 'v2.www.google.com', 'http://www.tld.doesnotexist');
foreach ($badUrls as $url) {
    try {
        echo 'Testing URL: ' . $url . PHP_EOL;
        echo Tld::getTld($url) . PHP_EOL;
    } catch(Exception $e) {
        echo $e . PHP_EOL;
    }
}
