AUTHOR
    Artur Barseghyan (artur.barseghyan@gmail.com)

LICENSE
    MPL 1.1/GPL 2.0/LGPL 2.1

DESCRIPTION
    Extracts the top level domain (TLD) from a URL given. List of TLD names is taken from
    http://mxr.mozilla.org/mozilla/source/netwerk/dns/src/effective_tld_names.dat?raw=1

USAGE EXAMPLE
    To get the top level domain name from the URL given:
        require 'tld/utils.php';
        echo Tld::getTld("http://www.google.co.uk")

    To update/sync the tld names with the most recent version run the following from your terminal:
        php -f tld/update.php

    To run tests:
        php -f tld/tests.php