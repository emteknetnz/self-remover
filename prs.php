<?php

// I had already manually processed a number of these, so this list
// is only a subset
$composerRepos = [
    // 'silverstripe/environmentcheck',
    // 'silverstripe/errorpage',
    // 'silverstripe/event-dispatcher',
    // 'silverstripe/externallinks',
    // 'silverstripe/framework',
    // 'silverstripe/frameworktest',
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

function cmd($cmd, $dir) {
    return trim(shell_exec("cd $dir && $cmd && cd - >/dev/null"));
}

function github_api($url, $data = [], $httpMethod = '')
{
    global $token;
    // silverstripe-themes has a kind of weird redirect only for api requests
    $url = str_replace('/silverstripe-themes/silverstripe-simple', '/silverstripe/silverstripe-simple', $url);
    $jsonStr = empty($data) ? '' : json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, !empty($data));
    if ($httpMethod) {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $httpMethod);
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: silverstripe-module-standardiser',
        'Accept: application/vnd.github+json',
        "Authorization: Bearer $token",
        'X-GitHub-Api-Version: 2022-11-28'
    ]);
    if ($jsonStr) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
    }
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpcode >= 300) {
        print("HTTP code $httpcode returned from GitHub API\n");
        print("$response\n");
        print("Failure calling github api: $url\n");
    }
    return json_decode($response, true);
}
$nonSemverRepos = [
    'silverstripe/frameworktest',
    'silverstripe/mink-facebook-web-driver',
    'silverstripe/testsession',
];

$title = "ENH Use class name instead of self";
$prUrls = [];

foreach ($composerRepos as $composerRepo) {
    [$account, $___] = explode('/', $composerRepo);
    $dir = "$rootDir/vendor/$composerRepo";
    // current git branch
    $branch = cmd('git rev-parse --abbrev-ref HEAD', $dir);
    // if branch is not semver tag, exit
    if (!preg_match('/^\d+\.\d+$/', $branch) && !in_array($composerRepo, $nonSemverRepos)) {
        echo "Branch is wrong, $composerRepo, $branch\n";
        exit;
    }
    $prBranch = "pulls/$branch/remove-self";
    // checkout new branch
    cmd("git checkout -b $prBranch", $dir);
    // set ccs origin
    $origin = cmd('git remote get-url origin', $dir);
    $ccs = preg_replace('#^https://github.com/.+?/(.+)$#', 'git@github.com:creative-commoners/$1', $origin);
    $ccs = preg_replace('#^git@github.com:.+?/(.+)$#', 'git@github.com:creative-commoners/$1', $ccs);
    preg_match('#^git@github.com:(.+?)/(.+)$#', $ccs, $m);
    // ghrepo is different from composer repo
    [$_, $__, $repo] = $m;
    $repo = preg_replace('#\.git$#', '', $repo);
    cmd("git remote add ccs $ccs", $dir);
    cmd("git add . && git commit -m '$title'", $dir);
    cmd("git push --set-upstream ccs $(git rev-parse --abbrev-ref HEAD)", $dir);
    // create PR via github API
    $url = "https://api.github.com/repos/$account/$repo/pulls";
    $data = [
        'title' => $title,
        'head' => "creative-commoners:$prBranch",
        'base' => $branch,
        'body' => 'Issue https://github.com/silverstripe/developer-docs/issues/486',
    ];
    $json = github_api($url, $data, 'POST');
    $prUrls[] = $json['url'];
}

// print urls
foreach ($prUrls as $prUrl) {
    echo "- $prUrl\n";
}
