<?php

// I had already manually processed a number of these, so this list
// is only a subset
$composerRepos = [
    'silverstripe/environmentcheck',
    'silverstripe/errorpage',
    'silverstripe/event-dispatcher',
    'silverstripe/externallinks',
    'silverstripe/framework',
    'silverstripe/frameworktest',
    'silverstripe/graphql',
    'silverstripe/hybridsessions',
    'silverstripe/ldap',
    'silverstripe/linkfield',
    'silverstripe/login-forms',
    'silverstripe/lumberjack',
    'silverstripe/markdown-php-codesniffer',
    'silverstripe/mfa',
    'silverstripe/mink-facebook-web-driver',
    'silverstripe/realme',
    'silverstripe/recipe-plugin',
    'silverstripe/reports',
    'silverstripe/restfulserver',
    'silverstripe/securityreport',
    'silverstripe/session-manager',
    'silverstripe/sharedraftcontent',
    'silverstripe/siteconfig',
    'silverstripe/sitewidecontent-report',
    'silverstripe/spamprotection',
    'silverstripe/staticpublishqueue',
    'silverstripe/subsites',
    'silverstripe/tagfield',
    'silverstripe/testsession',
    'silverstripe/textextraction',
    'silverstripe/userforms',
    'silverstripe/vendor-plugin',
    'silverstripe/versioned',
    'silverstripe/versioned-admin',
    'silverstripe/versionfeed',
    'silverstripe/webauthn-authenticator',
    'symbiote/silverstripe-advancedworkflow',
    'symbiote/silverstripe-gridfieldextensions',
    'symbiote/silverstripe-queuedjobs',
    'tractorcow/silverstripe-fluent',
];

$token = getenv('TOKEN');
if (!$token) {
    echo "Please set TOKEN\n";
    exit(1);
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

function cmd($cmd, $dir)
{
    return shell_exec("cd $dir && $cmd && cd - >/dev/null");
}

$nonSemverRepos = [
    'silverstripe/frameworktest',
    'silverstripe/mink-facebook-web-driver',
    'silverstripe/testsession',
];

foreach ($composerRepos as $composerRepo) {
    [$account, $repo] = explode('/', $composerRepo);
    $dir = "$rootDir/vendor/$composerRepo";
    // current git branch
    $branch = cmd('git rev-parse --abbrev-ref HEAD', $dir);
    // if branch is not semver tag, exit
    if (!preg_match('/^\d+\.\d+$/', $branch) && !in_array($composerRepo, $nonSemverRepos)) {
        echo "Branch is wrong, $composerRepo, $branch\n";
        exit;
    }
}
