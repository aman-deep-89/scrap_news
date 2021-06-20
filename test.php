<?php
require 'phpdiff/vendor/autoload.php';
use Caxy\HtmlDiff\HtmlDiff;
$oldHtml='<ul class="top_bar_contact_list">
<li><div class="question">Have any questions?</div></li>
<li>
    <div>(009) 35475 6688933 32</div>
</li>
<li>
    <div>info@elaerntemplate.com</div>
</li>
</ul>';
$newHtml='<ul class="top_bar_contact_list">
<li><div class="question">queries?</div></li>
<li>
    <div>(009) 797 97 9879</div>
</li>
<li>
    <div>info@elaerntemplate.com</div>
</li>
</ul>';
$htmlDiff = new HtmlDiff($oldHtml, $newHtml);
$htmlDiff->getConfig()
    ->setMatchThreshold(80)
    ->setInsertSpaceInReplace(true)
;
// Calculate the differences using the configuration and get the html diff.
$content = $htmlDiff->build();
echo $content;
?>