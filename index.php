<?php

require_once "./QueueAdapter.php";

$res = QueueAdapter::getQueue();

var_dump($res);