<?php
// Spark's testing bootstrap expects this constant, normally defined by PHPUnit.
define('SUPPORTPATH', dirname(__DIR__).'/_support/');
define('ENVIRONMENT','testing');
require dirname(__DIR__,2).'/spark';
