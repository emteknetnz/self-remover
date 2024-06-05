<?php

$accounts = [
    'bringyourownideas',
    'colymba',
    'cwp',
    'dnadesign',
    'silverstripe',
    'symbiote',
    'tractorcow',
];

// function to recursively scan php files in vendor/<account>
function getPhpFiles($path, &$files) {
    if (!is_dir($path)) {
        return;
    }
    $items = scandir($path);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $fullPath = "$path/$item";
        if (is_dir($fullPath)) {
            getPhpFiles($fullPath, $files);
        } else if (pathinfo($fullPath, PATHINFO_EXTENSION) === 'php') {
            $files[] = $fullPath;
        }
    }
}

// loop directories in vendor/<account>
foreach ($accounts as $account) {
    $path = "vendor/$account";
    $files = [];
    $files = getPhpFiles($path, $files);
    print_r($files);
    echo "Scanned $path\n";
}
