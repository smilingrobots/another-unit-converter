<?php

namespace AUCP\Tests;

use PHPUnit;

class TestCase extends PHPUnit\Framework\TestCase {

    protected $backupGlobalsBlacklist = array( 'wpdb' );

}
