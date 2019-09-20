![PODCAST RSS GENERATOR FROM FOLDER](https://i.imgur.com/syxpHj7.jpg)

Podcast RSS Generator From Folder (or PRGFF if you want to splutter) is a PHP script able to parse for medias the folder in which it is stored.

It will generate for you, lazies out there, a podcast feed which will somewhat kind of work but iTunes will probably pee on it with disgust

That's about it.
You put ONLY the rss.php file in a folder with medias, BOOM, you have a podcast feed.

It's rough, it's kind of not for production use, but hey, do you have time to spare doing all the work yourself ?

The only thing is that you'd better have some id3tags ready in your files, AS YOU SHOULD *wink wink*, because it heavily depends on it.

### What it does :
- Autoinstalls on first launch (just ping the rss.php file and it's magical)
- Creates a /.rss-dependencies/ folder to store all the things it will need
- Gets all the files in the folder with their lenght and generate XML items with an enclosure for each one
- Ignores every type of file or folder EXCEPT the handful which are compatible and really used with podcasting (audio/mp3, audio/m4a, video/mp4, video/m4v). No epub or pdf, because WHO USES IT ?
- gets the pubdate from the file date of modification
- Gets info for the items from the id3tags :
   - gets the title from the title of the media (id3tag)
   - gets the author from the artist of the media (id3tag)
   - gets description fom the comment section of the id3tags (no formatting)
   - gets link from the URL field of the media (id3tag)
   - gets artwork of each episode from the file (id3tag)
- Has some sort of cache so that the id3tags and covers are not read at every f....ing refresh
- Have fallbacks when no title or description (uses the filename for the title)
- Have a GUID liked to the filename so that you can replace a file if you didn't check before uploading and have to reupload your episode and not f...k everything up for your subscribers 
- stylize the duck out of that xml with xsl so that a human can read it without bionic/dev eyes and BOOM, here is your effort-free website !

### What it will do (TODO)

- get the feed infos it can't get from elsewhere from a feed.config file you will put next to the php script (if you don't, it should have some sort of fallback, like the name of the folder, at least)


### What it will NEVER DO
- add tags to items, because no-one fills it anyway


That will be fun, lads.

### Troubleshooting 
Just delete the /.rss-dependencies/ folder, it should regenerate everything from scratch

### What is id3tag ?
ID3 is a metadata container most often used in conjunction with the MP3 audio file format. It allows information such as the title, artist, album, track number, and other information about the file, like the cover art, to be stored in the file itself.

![EXAMPLE ID3TAG](https://i.imgur.com/sLRo1WX.png)

To edit yout medias and write id3tags in them, we advice using the following free softwares:
- [EasyTAG](https://wiki.gnome.org/Apps/EasyTAG) on Windows and Linux 
- [MP3TAG](https://www.mp3tag.de/en/) on Windows and MacOS

(Media players such as iTunes are also able to edit those tags, FYI)

### Sources
The XSL template is shamelessly copied from https://github.com/TheCraigHewitt/Seriously-Simple-Podcasting
