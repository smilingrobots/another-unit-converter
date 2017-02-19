<?php

$loader = require dirname(__DIR__) . '/another-unit-converter/vendor/autoload.php';

Phake::setClient(Phake::CLIENT_PHPUNIT);

require getenv( 'WP_TESTS_DIR' ) . '/includes/functions.php';
require getenv( 'WP_TESTS_DIR' ) . '/includes/bootstrap.php';

require __DIR__ . '/includes/class-aucp-test-case.php';
