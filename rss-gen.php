<?php
Header('Content-type: text/xml');

class BootstrapGenerator
{
    public function __construct()
    {
        if (!file_exists('./.rss-gen-cache')) {
            mkdir('./.rss-gen-cache');
        }

        if (!file_exists('./.rss-gen-cache/id3requirements')) {
            mkdir('./.rss-gen-cache/id3requirements');
            $file = file_get_contents('https://github.com/misnard/getID3/archive/master.zip');
            file_put_contents('./.rss-gen-cache/id3requirements/master.zip', $file);

            $zip = new ZipArchive;
            if ($zip->open('./.rss-gen-cache/id3requirements/master.zip')) {
                $zip->extractTo('./.rss-gen-cache/id3requirements/');
                $zip->close();
                unlink('./.rss-gen-cache/id3requirements/master.zip');
            }
        }

        require_once('./.rss-gen-cache/id3requirements/getID3-master/getid3/getid3.php');
    }
}

class RssGenerator 
{
    protected $rssNode;
    protected $channelNode;
    protected $currentUrl;
    protected $ID3;

    public function __construct()
    {
        $this->ID3 = new getID3;
        $this->rssNode = new SimpleXMLElement('<rss />');
        $this->rssNode->addAttribute('version', '2.0');
        $this->rssNode->addAttribute('itunes:xmlns', null, 'http://www.itunes.com/dtds/podcast-1.0.dtd');

        $this->channelNode = $this->rssNode->addChild('channel');

        //Get current url
        $this->currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") 
        . "://$_SERVER[HTTP_HOST]" 
        . substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/') + 1);
    }

    public function exec($cwd)
    {
        $bulkContent = $this->getDirContent($cwd);
        $this->generateRssContent($bulkContent);
    }

    public function getAuthorizedTypes()
    {
        return ['mp3' => 'audio/mp3', 'mp4' => 'video/mp4', 'm4a' => 'audio/m4a', 'm4v' => 'video/m4v'];
    }

    public function generateRssContent($bulkContent)
    {
        foreach($bulkContent as $item) {
            $this->insertRssItem($item);
        }
    }

    public function getDirContent($cwd)
    {
        $directory = array_reverse(scandir($cwd));
        $bulkContent = [];

        foreach($directory as $filename) {
            $fileExtention = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (array_key_exists($fileExtention, $this->getAuthorizedTypes())) {
                $bulkContent[] = ['item_name' => $filename, 'item_extension' => $this->getAuthorizedTypes()[$fileExtention]];
            }
        }

        return $bulkContent;
    }

    public function getItemUrl($itemPath)
    {
        return $this->currentUrl . $itemPath;
    }

    public function insertRssItem($item)
    {
        $xmlItem = $this->channelNode->addChild('item');
        $id3Infos = $this->getId3($item);

        foreach ($id3Infos as $key => $id3Info) {
            //todo switch if params is here
            if ($key === "itunes:image") {
                $this->insertFileTags($xmlItem, $key, null, ['href' => $id3Info], "itunes");
            } elseif ($key === "itunes:duration") {
                $this->insertFileTags($xmlItem, $key, $id3Info, [], "itunes");
            } elseif ($key === "itunes:author") {
                $this->insertFileTags($xmlItem, $key, $id3Info, [], "itunes");
            } elseif ($key === "dc:creator") {
                $this->insertFileTags($xmlItem, $key, $id3Info, [], "dc");
            } else {
                $this->insertFileTags($xmlItem, $key, $id3Info);
            }
        }

        
        $this->insertEnclosure($item, $xmlItem);

    }

    public function insertFileTags($xmlItem, $tagName, $content, $params = [], $namespace = null)
    {
        $item = $xmlItem->addChild($tagName, $content, $namespace);
        foreach ($params as $key => $param) {
            $item->addAttribute($key, $param);
        }
    }

    public function getId3($item)
    {
        $filteredId3Infos = [];
        $filePath = '.rss-gen-cache/' . $item['item_name'];
        $id3FilePath = $filePath . '.id3';
        $imageName = $filePath . '.png';

        if (file_exists($filePath)) {
            $filteredId3Infos = unserialize(file_get_contents($id3FilePath));
        } else {
            if ($id3FileInfos = $this->ID3->analyze($item['item_name']) ?? false) {
                if ($id3FileInfos['comments']['picture'][0]['data']) {
                    file_put_contents($imageName, 
                    $id3FileInfos['comments']['picture'][0]['data']);
                }
                $filteredId3Infos = [
                    "title" => $id3FileInfos['tags']['id3v2']['title'][0] ?? "Default Title",
                    "pubDate" => date("F d Y H:i:s", filemtime($item['item_name'])),
                    "description" => $id3FileInfos['tags']['id3v2']['comment'][0] ?? "Default description",
                    "link" => $id3FileInfos['tags']['id3v2']['url_user'][0] ?? null,
                    "dc:creator" => $id3FileInfos['tags']['id3v2']['artist'][0] ?? "Default Creator",
                    "itunes:duration" => $id3FileInfos['playtime_string'] ?? "00:00",
                    "itunes:author" =>  $id3FileInfos['tags']['id3v2']['artist'][0] ?? "Default Creator",
                    "itunes:image" => $this->currentUrl . $imageName
                ];

                $fileStream = fopen($id3FilePath, 'wb');

                fwrite($fileStream, serialize($filteredId3Infos));

                fclose($fileStream);
            }
        }

        return $filteredId3Infos;
    }

    public function insertEnclosure($item, $xmlItem)
    {
        $xmlEnclosure = $xmlItem->addChild('enclosure');
        $xmlEnclosure->addAttribute('url', $this->getItemUrl($item['item_name']));
        $xmlEnclosure->addAttribute('length',filesize($item['item_name']));
        $xmlEnclosure->addAttribute('type', $item['item_extension']);
    }

    public function displayRss()
    {
        print($this->rssNode->asXML());
    }
}

new BootstrapGenerator();

$generator = new RssGenerator();
$generator->exec(".");
$generator->displayRss();