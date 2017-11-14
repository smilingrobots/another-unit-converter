<?php

require dirname(__DIR__) . '/another-unit-converter/vendor/antecedent/patchwork/Patchwork.php';
require dirname(__DIR__) . '/another-unit-converter/vendor/autoload.php';

Phake::setClient( Phake::CLIENT_PHPUNIT );

/* Empty definitions for WordPress functions and classes we use */
require dirname(__DIR__) . '/tests/wordpress/functions.php';

