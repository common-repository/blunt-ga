<?php
/**
 * @package Tld
 * @author Artur Barseghyan (artur.barseghyan@gmail.com)
 * @version 0.1
 * @license MPL 1.1/GPL 2.0/LGPL 2.1
 * @link http://bitbucket.org/barseghyanartur/php-tld
 *
 * Commands for updating the TLD names file from command line.
 */

require 'utils.php';

Tld::updateTldNames();
echo sprintf("File %s has been successfully updated!" . PHP_EOL, Tld::NAMES_LOCAL_PATH);