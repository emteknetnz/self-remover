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

$replacePatterns = [
    'self::config()' => 'static::config()'
];

$patterns = [
    'self::',
    ': self',
    ': ?self',
    '@return self',
    '@return  self',
    'new self(',
    'new self;',
    'instanceof self)',
    'instanceof self;',
    '@var self',
];

$traitPregReplacements = [
    '#self::([a-zA-Z]+)\(#' => 'self::class::$1(',
];

// only updating traits right now
$patterns = [];
$replacePatterns = [];

function php_files($path, &$files = []) {
    if (!is_dir($path)) {
        return;
    }
    $items = scandir($path);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === '.git') {
            continue;
        }
        $fullPath = "$path/$item";
        if (is_dir($fullPath)) {
            php_files($fullPath, $files);
        } else if (pathinfo($fullPath, PATHINFO_EXTENSION) === 'php') {
            $files[] = $fullPath;
        }
    }
}

$rootDir = __DIR__;
for ($i = 0; $i < 5; $i++) {
    if (!file_exists("$rootDir/.env")) {
        $rootDir = dirname($rootDir);
    } else {
        echo "Root dir is $rootDir\n";
        break;
    }
}

$reposUpdated = [];

foreach ($accounts as $account) {
    $path = "$rootDir/vendor/$account";
    php_files($path, $files);
    foreach ($files as $file) {
        preg_match("#^$path/(.+?)/#", $file, $m);
        if (!$m) {
            // file isn't in the current account
            continue;
        }
        $repo = $m[1];
        $shortName = basename($file, '.php');
        $c = file_get_contents($file);
        $orig = $c;
        $isTrait = strpos($c, "\ntrait $shortName") !== false;
        if ($isTrait) {
            foreach ($traitPregReplacements as $rx => $replacement) {
                $c = preg_replace($rx, $replacement, $c);
                if ($c != $orig) {
                    file_put_contents($file, $c);
                    $reposUpdated[$repo] = true;
                }
            }
            // reverse out code changes previous made
            // foreach ($patterns as $pattern) {
            //     $find = str_replace('self', $shortName, $pattern);
            //     $c = str_replace($find, $pattern, $c);
            //     if ($c != $orig) {
            //         file_put_contents($file, $c);
            //         $reposUpdated[$repo] = true;
            //     }
            // }

        } else {
            foreach ($replacePatterns as $pattern => $replacement) {
                $c = str_replace($pattern, $replacement, $c);
                if ($c != $orig) {
                    file_put_contents($file, $c);
                    $reposUpdated[$repo] = true;
                }
            }
            foreach ($patterns as $pattern) {
                $replacement = str_replace('self', $shortName, $pattern);
                $c = str_replace($pattern, $replacement, $c);
                if ($c != $orig) {
                    file_put_contents($file, $c);
                    $reposUpdated[$repo] = true;
                }
            }
        }
    }
}

echo "Updated the following repos:\n";
foreach ($reposUpdated as $repo => $true) {
    echo "$repo\n";
}
