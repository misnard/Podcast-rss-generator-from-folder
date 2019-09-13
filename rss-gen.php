<?php

$dirContent = array_reverse(scandir('.'));

$rssContents = [];

$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . 
substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/') + 1);

$typeMaping = ['mp3' => 'audio/mp3', 'mp4' => 'video/mp4'];
 
foreach($dirContent as $content) {
    if ($content !== ".." && $content !== "." && !strpos($content, '.php')) {
        $rssContents[] = [$content, pathinfo($content)['extension']];
    } elseif (strpos($content, '.php')) {
        $scriptName = $content;
    }
}

$rss = new SimpleXMLElement('<rss />');
$rss->addAttribute('version', '2.0');
$channel = $rss->addChild('channel');

foreach($rssContents as $rssContent) {

    $item = $channel->addChild('item');
    $item->addChild('title', 'Test');
    $item->addChild('link', 'Test liens');
    $item->addChild('description', 'test desc');
    $enclosure = $item->addChild('enclosure');
    $enclosure->addAttribute('url', $actual_link . $rssContent[0]);
    $enclosure->addAttribute('length',filesize($rssContent[0]));
    $enclosure->addAttribute('type', $typeMaping[$rssContent[1]]);
}

Header('Content-type: text/xml');
print($rss->asXML());