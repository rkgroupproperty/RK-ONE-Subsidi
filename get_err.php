<?php
$html = file_get_contents('https://sistem.rkgroupproperti.com/system/git-pull/rk_git_pull_2026');
preg_match_all('/<h[1-4][^>]*>(.*?)<\/h[1-4]>/is', $html, $headings);
print_r($headings[1]);
preg_match('/<div class="header-message[^>]*>(.*?)<\/div>/is', $html, $m);
if ($m) echo "Header message: " . strip_tags($m[1]) . "\n";
