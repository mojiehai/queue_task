<?php

define('QUEUETASKROOT', dirname(__DIR__));

function __autoload($class)
{
    $classArr = explode('\\', $class);
    $file = QUEUETASKROOT;
    foreach ($classArr as $v) {
        if ($v == 'QueueTask') {
            $v = 'src';
        } else if ($v == 'Tests') {
            $v = 'tests';
        }
        $file .= DIRECTORY_SEPARATOR . $v;
    }
    $file .= '.php';

    if (file_exists($file)) {
        require $file;
    }
}
