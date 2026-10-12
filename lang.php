<?php


$GLOBALS['__lang_strings'] = require __DIR__ . '/lang/en.php';

function t(string $key, array $vars = []): string {
    $str = $GLOBALS['__lang_strings'][$key] ?? $key; 

    foreach ($vars as $k => $v) {
        $str = str_replace(':' . $k, $v, $str);
    }
    return $str;
}

function currentLang(): string {
    return 'en';
}
