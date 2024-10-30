<?php
/**
 * @package Tld
 * @author Artur Barseghyan (artur.barseghyan@gmail.com)
 * @version 0.1
 * @license MPL 1.1/GPL 2.0/LGPL 2.1
 * @link http://bitbucket.org/barseghyanartur/php-tld
 *
 * Gets top level domains from a URL given. List of TLD names is taken from
 * http://mxr.mozilla.org/mozilla/source/netwerk/dns/src/effective_tld_names.dat?raw=1
 */
require 'exceptions.php';

/**
 * Main Tld class. Does all the job.
 *
 * @example
 * require 'utils.php';
 * echo Tld::getTld('http://www.google.co.uk'); // good pattern, echoes 'google.co.uk'
 * echo Tld::getTld('http://www.me.congresodelalengua3.ar'); // good pattern, echoes 'me.congresodelalengua3.ar'
 * echo Tld::getTld('http://www.v2.google.co.uk'); // good pattern, echoes 'google.co.uk'
 * echo Tld::getTld('/index.php?a=1&b=2'); // bad pattern, raises <TldBadUrl> exception
 * echo Tld::getTld('v2.www.google.com'); // bad pattern, raises <TldBadUrl> exception
 * echo Tld::getTld('http://www.tld.doesnotexist'); // bad pattern, raises <TldDomainNotFound> exception
 */
class Tld {
    /**
     * Container for TLD names.
     * @var <array>
     */
    private static $tldNames = array();

    /**
     * URL to read the original source of TLD names from.
     */
    const NAMES_SOURCE_URL = 'http://mxr.mozilla.org/mozilla/source/netwerk/dns/src/effective_tld_names.dat?raw=1';

    /**
     * Local path to the TLD names file.
     */
    const NAMES_LOCAL_PATH = 'res/effective_tld_names.dat.txt';

    /**
     * Initializes $tldNames array if empty. Throws a <TldIOError> exception in case of read/write errors or returns
     * boolean false if <$failSilently> has been set to true.
     *
     * @static
     * @param <int> $retryCount
     * @param <bool> $failSilently
     * @return <array>
     * @throws <TldIOError>
     */
    public static function init($retryCount = 0, $failSilently = false) {
        // If number of retries exceeds 1, we throw an exception to avoid infinite loops
        if ($retryCount > 1) {
            if ($failSilently)
                return false;
             else
                throw new TldIOError();
        }

        // If $tldNames is not empty, we return its' value
        if (count(self::$tldNames) > 0)
            return self::$tldNames;

        // Try to read the file. If something fails, we try to grab the file and recursively run the "init" again.
        $localFile = @fopen(dirname(__FILE__) . DIRECTORY_SEPARATOR . self::NAMES_LOCAL_PATH, 'r');

        // If file not opened, obtain the TLD names file from Mozilla's website and try again (recursion)
        if (false === $localFile) {
            self::updateTldNames();
            return self::init(++$retryCount);
        }

        // Read file line by line.
        while(false !== ($line = fgets($localFile))) {
            $line = trim($line);
            if (false == preg_match('/^[\/\n]/', $line) && strlen($line) > 0) {
                self::$tldNames[] = $line;
            }
        }
        fclose($localFile);

        return self::$tldNames;
    }

    /**
     * Extracts the TLD from the URL given. Returns a string. May throw <TldBadUrl> or <TldDomainNotFound>
     * exceptions if there's bad URL provided or no TLD match found respectively. In case if <$failSilently> has been
     * set to true, returns boolean false on failure, instead of raising an exception.
     * 
     * @static
     * @param <str> $url
     * @param <bool> $activeOny: If set to true, only active TLDs are matched against.
     * @param <bool> $failSilently
     * @return <str>
     * @throws <TldBadUrl>  or <TldDomainNotFound>
     */
    public static function getTld($url, $activeOny = false, $failSilently = false) {
        self::init();

        // Parsing URL
        $parsedUrl = parse_url($url);
        // Checking if we have the host key and it's not empty
        if (array_key_exists('host', $parsedUrl) && $parsedUrl['host'])
            $domainName = $parsedUrl['host'];
        else {
            if ($failSilently)
                return false;
            else
                throw new TldBadUrl($url); // Bad URL
        }

        // Splitting the parts by '.'
        $domainParts = explode('.', $domainName);

        // Looping from much to less (for example if we have a domain named "v3.api.google.co.uk" we'll try
        // "v3.api.google.co.uk", then "api.google.co.uk", then "api.google.co.uk", then "google.co.uk", then
        // "co.uk" and finally "uk". If the last one does not match any TLDs, we throw a <TldDomainNotFound>
        // exception.
        for ($i = 0; $i < count($domainParts); $i++) {
            $slicedDomainParts = array_slice($domainParts, $i); // Sliced URL

            $match = implode('.', $slicedDomainParts); // Exact match
            $wildcardMatch = '*.' . implode('.', array_slice($slicedDomainParts, 1)); // Wildcard match

            if (in_array($match, self::$tldNames) || in_array($wildcardMatch, self::$tldNames)) {
                return implode('.', array_slice($domainParts, $i - 1));
            }

            if (!$activeOny) {
                $inactiveMatch = '!' . $match; // No longer active domains, still may occur.
                if (in_array($inactiveMatch, self::$tldNames))
                    return implode('.', array_slice($domainParts, $i - 1));
            }
        }

        if ($failSilently)
            return false;
        else
            throw new TldDomainNotFound($domainName);
    }

    /**
     * Updates the local TLD names file. Throws a <TldIOError> exception in case of read/write errors or returns
     * boolean false if <$failSilently> has been set to true.
     * 
     * @static
     * @param <bool> $failSilently
     * @return <bool>
     * @throws <TldIOError>
     */
    public static function updateTldNames($failSilently = false) {
        $res = false;
        try {
            $res = file_put_contents(
                dirname(__FILE__) . DIRECTORY_SEPARATOR . self::NAMES_LOCAL_PATH,
                file_get_contents(self::NAMES_SOURCE_URL)
                );
        } catch(Exception $e) {
            if ($failSilently)
                return false;
            else
                throw new TldIOError();
        }

        if (false == $res) {
            if ($failSilently)
                return false;
            else
                throw new TldIOError();
        }
        return true;
    }

    /**
     * Returns the array of TLD names.
     * 
     * @static
     * @return <array>
     */
    public static function getTldNames() {
        return self::$tldNames;
    }
}