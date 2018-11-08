<?php

require "./bootstrap.php";

use QueueTask\Process\Manage;

(new Manage())->run();
