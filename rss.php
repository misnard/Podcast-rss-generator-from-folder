<?php
Header('Content-type: text/xml');

/**
 * Class BootstrapGenerator
 */
class BootstrapGenerator
{
    /**
     * BootstrapGenerator constructor.
     */
    public function __construct()
    {
        if (!file_exists('./.rss-dependencies/.cache')) {
            mkdir('./.rss-dependencies/.cache');
        }

        if (!file_exists('./.rss-dependencies/.cache/id3requirements')) {
            mkdir('./.rss-dependencies/.cache/id3requirements');
            $file = file_get_contents('https://github.com/misnard/getID3/archive/master.zip');
            file_put_contents('./.rss-dependencies/.cache/id3requirements/master.zip', $file);

            $zip = new ZipArchive;
            if ($zip->open('./.rss-dependencies/.cache/id3requirements/master.zip')) {
                $zip->extractTo('./.rss-dependencies/.cache/id3requirements/');
                $zip->close();
                unlink('./.rss-dependencies/.cache/id3requirements/master.zip');
            }
        }

        require_once('./.rss-dependencies/.cache/id3requirements/getID3-master/getid3/getid3.php');
    }
}

/**
 * Class CustomXMLElement
 */
class CustomXMLElement extends SimpleXMLElement
{
    /**
     * @param $name
     * @param $value
     */
    public function addProcessingInstruction($name, $value )
    {
        // Create a DomElement from this simpleXML object
        $dom_sxe = dom_import_simplexml($this);

        // Create a handle to the owner doc of this xml
        $dom_parent = $dom_sxe->ownerDocument;

        // Find the topmost element of the domDocument
        $xpath = new DOMXPath($dom_parent);
        $first_element = $xpath->evaluate('/*[1]')->item(0);

        // Add the processing instruction before the topmost element
        $pi = $dom_parent->createProcessingInstruction($name, $value);
        $dom_parent->insertBefore($pi, $first_element);
    }
}

/**
 * Class RssGenerator
 */
class RssGenerator
{
    /**
     * @var CustomXMLElement
     */
    protected $rssNode;
    /**
     * @var SimpleXMLElement
     */
    protected $channelNode;
    /**
     * @var
     */
    protected $currentUrl;
    /**
     * @var getID3
     */
    protected $ID3;
    /**
     * @var
     */
    protected $config;

    /**
     * RssGenerator constructor.
     */
    public function __construct()
    {
        $this->getConfig();
        $this->ID3 = new getID3;
        $this->rssNode = new CustomXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss />');

        if ($this->config['THEME_ENABLE']) {
            $this->rssNode->addProcessingInstruction('xml-stylesheet', 'type="text/xsl" href=".rss-dependencies/template.xsl"');
        }

        $this->rssNode->addAttribute('version', '2.0');
        $this->rssNode->addAttribute('xmlns:xmlns:itunes', 'http://www.itunes.com/dtds/podcast-1.0.dtd');
        $this->rssNode->addAttribute('xmlns:xmlns:dc', 'http://purl.org/dc/elements/1.1/');
        $this->rssNode->addAttribute('xmlns:xmlns:atom', 'http://www.w3.org/2005/Atom');

        $this->channelNode = $this->rssNode->addChild('channel');

        $this->getCurrentUrl();

        $this->insertChannelInfos();
    }

    /**
     * @param $cwd
     */
    public function exec($cwd)
    {
        $bulkContent = $this->getDirContent($cwd);
        $this->generateRssContent($bulkContent);
    }

    /**
     * @return array
     */
    public function getAuthorizedTypes()
    {
        return ['mp3' => 'audio/mp3', 'mp4' => 'video/mp4', 'm4a' => 'audio/m4a', 'm4v' => 'video/m4v'];
    }

    /**
     * @param $bulkContent
     */
    public function generateRssContent($bulkContent)
    {
        foreach($bulkContent as $item) {
            $this->insertRssItem($item);
        }
    }

    /**
     * @param $cwd
     * @return array
     */
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

    /**
     * @param $itemPath
     * @return string
     */
    public function getItemUrl($itemPath)
    {
        return $this->currentUrl . $itemPath;
    }

    /**
     *
     */
    public function getCurrentUrl()
    {
        $this->currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http")
            . "://$_SERVER[HTTP_HOST]"
            . substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/') + 1);
    }

    /**
     * @param $item
     */
    public function insertRssItem($item)
    {
        $xmlItem = $this->channelNode->addChild('item');
        $id3Infos = $this->getId3($item);

        foreach ($id3Infos as $key => $id3Info) {
            //todo switch if params is here
            if ($key === "itunes:itunes:image") {
                $this->insertFileTags($xmlItem, $key, null, ['href' => $id3Info]);
            } elseif ($key === "guid") {
                $this->insertFileTags($xmlItem, $key, $id3Info, ["isPermaLink" => "false"]);
            } else {
                $this->insertFileTags($xmlItem, $key, $id3Info);
            }
        }

        $this->insertEnclosure($item, $xmlItem);
    }

    /**
     * @param $xmlItem
     * @param $tagName
     * @param $content
     * @param array $params
     * @param null $namespace
     */
    public function insertFileTags($xmlItem, $tagName, $content, $params = [], $namespace = null)
    {
        $item = $xmlItem->addChild($tagName, $content, $namespace);
        foreach ($params as $key => $param) {
            $item->addAttribute($key, $param);
        }
    }

    /**
     * @param $item
     * @return array|mixed
     */
    public function getId3($item)
    {
        $filteredId3Infos = [];
        $filePath = './.rss-dependencies/.cache/' . $item['item_name'];
        $id3FilePath = $filePath . '.id3';
        $imageName = $filePath . '.png';

        if (file_exists($filePath)) {
            $filteredId3Infos = unserialize(file_get_contents($id3FilePath));
        } else {
            if ($id3FileInfos = $this->ID3->analyze($item['item_name']) ?? false) {
                if (isset($id3FileInfos['comments']['picture'][0]['data'])) {
                    file_put_contents($imageName,
                        $id3FileInfos['comments']['picture'][0]['data']);
                    $finalImageName = $this->currentUrl . $imageName;
                } else {
                    $finalImageName = $this->config['IMAGE'];
                }
                $fileExtention = pathinfo($item['item_name'], PATHINFO_EXTENSION);
                $filteredId3Infos = [
                    "title" => !empty($id3FileInfos['tags']['id3v2']['title'][0])
                        ? htmlspecialchars($id3FileInfos['tags']['id3v2']['title'][0])
                        : $item['item_name'],
                    "pubDate" => date("D, d M Y H:i:s O", filemtime($item['item_name'])),
                    "description" => !empty($id3FileInfos['tags']['id3v2']['comment'][0]) ?
                        htmlspecialchars($id3FileInfos['tags']['id3v2']['comment'][0]) : " ",
                    "link" => $id3FileInfos['tags']['id3v2']['url_user'][0] ?? null,
                    "dc:dc:creator" => !empty($id3FileInfos['tags']['id3v2']['artist'][0]) ?
                        htmlspecialchars($id3FileInfos['tags']['id3v2']['artist'][0]) : "Default Creator",
                    "itunes:itunes:duration" => $id3FileInfos['playtime_string'] ?? "00:00",
                    "itunes:itunes:author" =>  !empty($id3FileInfos['tags']['id3v2']['artist'][0])
                        ? htmlspecialchars($id3FileInfos['tags']['id3v2']['artist'][0]) : "Default Creator",
                    "itunes:itunes:image" => $finalImageName,
                    "guid" => md5($item['item_name'])
                ];

                $fileStream = fopen($id3FilePath, 'wb');

                fwrite($fileStream, serialize($filteredId3Infos));

                fclose($fileStream);

                /** Update last build date */
                file_put_contents('./.rss-dependencies/.cache/date.txt', date("D, d M Y H:i:s O"));
            }
        }

        return $filteredId3Infos;
    }

    /**
     * @param $item
     * @param $xmlItem
     */
    public function insertEnclosure($item, $xmlItem)
    {
        $xmlEnclosure = $xmlItem->addChild('enclosure');
        $xmlEnclosure->addAttribute('url', $this->getItemUrl($item['item_name']));
        $xmlEnclosure->addAttribute('length',filesize($item['item_name']));
        $xmlEnclosure->addAttribute('type', $item['item_extension']);
    }

    /**
     *
     */
    public function insertChannelInfos()
    {
        $lastBuildDate = file_get_contents('./.rss-dependencies/.cache/date.txt');

        $this->channelNode->addChild('title', htmlspecialchars($this->config['TITLE']));

        $this->channelNode->addChild('description', htmlspecialchars($this->config['DESCRIPTION']));

        $this->channelNode->addChild('link', $this->config['LINK']);

        $channelImageTag = $this->channelNode->addChild('image');
        $channelImageTag->addChild('url', $this->config['IMAGE']);
        $channelImageTag->addChild('title', htmlspecialchars($this->config['TITLE']));
        $channelImageTag->addChild('link', $this->config['LINK']);

        $this->channelNode->addChild('lastBuildDate', $lastBuildDate);

        $channelAtomTag = $this->channelNode->addChild('atom:link');
        $channelAtomTag->addAttribute('href', $this->currentUrl);
        $channelAtomTag->addAttribute('rel', 'self');
        $channelAtomTag->addAttribute('type', 'application/rss+xml');

        $this->channelNode->addChild('language', $this->config['LANGUAGE']);

        $this->channelNode->addChild('webMaster', htmlspecialchars($this->config['MAIL'] . ' (' . $this->config['AUTHOR'] . ')'));

        $this->channelNode->addChild('itunes:itunes:summary', htmlspecialchars($this->config['DESCRIPTION']));

        $this->channelNode->addChild('itunes:itunes:subtitle', htmlspecialchars($this->config['SUBTITLE']));

        $this->channelNode->addChild('itunes:itunes:explicit', $this->config['EXPLICIT']);

        $this->channelNode->addChild('itunes:itunes:author', htmlspecialchars($this->config['AUTHOR']));

        $this->channelNode->addChild('itunes:itunes:image')
            ->addAttribute('href', $this->config['IMAGE']);

        $channelOwnerTag = $this->channelNode->addChild('itunes:owner');
        $channelOwnerTag->addChild('itunes:itunes:name', htmlspecialchars($this->config['AUTHOR']));
        $channelOwnerTag->addChild('itunes:itunes:email', htmlspecialchars($this->config['MAIL']));

        $channelICategoryTag = $this->channelNode->addChild('itunes:itunes:category');
        $channelICategoryTag->addAttribute('text', htmlspecialchars($this->config['CATEGORY']));

        $channelCopyTag = $this->channelNode;
        $channelCopyTag->addChild('copyright', date("Y") . ' ' . htmlspecialchars($this->config['LICENCE']) . ' ' . htmlspecialchars($this->config['AUTHOR']));

        $this->channelNode->addChild('generator', "Podcast RSS Generator From Folder (or PRGFF if you want to splutter)");
    }

    /**
     *
     */
    public function getConfig()
    {
        try {
            $file = new SplFileObject("./.rss-dependencies/channel.config");

            while ($file->valid()) {
                $configBuffer = explode("=", trim($file->fgets()));

                if (count($configBuffer) == 2) {

                    $this->config[$configBuffer[0]] = $configBuffer[1];
                }
            }

            unset($file);
        } catch (Exception $error) {
            echo "Error on config loading " . $error;
        }
    }

    /**
     *
     */
    public function displayRss()
    {
        print($this->rssNode->asXML());
    }
}

new BootstrapGenerator();

$generator = new RssGenerator();
$generator->exec(".");
$generator->displayRss();
