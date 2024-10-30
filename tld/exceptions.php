<?php
/**
 * @package Tld
 * @author Artur Barseghyan (artur.barseghyan@gmail.com)
 * @version 0.1
 * @license MPL 1.1/GPL 2.0/LGPL 2.1
 * @link http://bitbucket.org/barseghyanartur/php-tld
 * 
 * Tld package exceptions.
 */

/**
 * Supposed to be thrown when problems with reading/writing occur.
 */
class TldIOError extends Exception {
    public function __construct($message = null) {
        if (null == $message) {
            $message = sprintf("Can't read from or write to the %s file!", Tld::NAMES_LOCAL_PATH);
        }
        parent::__construct($message);
    }
}

/**
 * Supposed to be thrown when domain name is not found (didn't match) the local TLD policy.
 */
class TldDomainNotFound extends Exception {
    public function __construct($domainName) {
        $message = sprintf("Domain %s didn't match any existing TLD name!", $domainName);
        parent::__construct($message);
    }
}

/**
 * Supposed to be thrown when bad URL is given.
 */
class TldBadUrl extends Exception {
    public function __construct($url) {
        $message = sprintf("Is not a valid URL %s!", $url);
        parent::__construct($message);
    }
}