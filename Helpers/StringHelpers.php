<?php

namespace QueueTask\Helpers;

class StringHelpers
{

    /**
     * 将一个驼峰命名的名字转换成多个单词
     * Converts a CamelCase name into space-separated words.
     * For example, 'PostTag' will be converted to 'Post Tag'.
     * @param string $name the string to be converted
     * @param boolean $ucwords whether to capitalize the first letter in each word              是否单词首字母大写 true大写，false小写
     * @return string the resulting words
     */
    public static function Camel2words($name, $ucwords = true)
    {
        $label = trim(strtolower(str_replace([
            '-',
            '_',
            '.'
        ], ' ', preg_replace('/(?<![A-Z])[A-Z]/', ' \0', $name))));

        return $ucwords ? ucwords($label) : $label;
    }

}
