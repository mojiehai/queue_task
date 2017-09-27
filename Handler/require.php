<?php

/**
 * 自动加载的当前文件夹下的类
 *
 * spl_autoload_register可能会影响其他框架的自动导入逻辑，用原始导入方式解耦
 */

$path = dirname(__FILE__);

$res = opendir($path);

while($file = readdir($res)){
    if($file == '.' || $file == '..' || $file == 'require.php'){
        continue;
    }
    $newpath = $path.DIRECTORY_SEPARATOR.$file;
    if(file_exists($newpath)){
        include_once $newpath;
    }
}
closedir($res);