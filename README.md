![PODCAST RSS GENERATOR FROM FOLDER](https://i.imgur.com/syxpHj7.jpg)

Podcast RSS Generator From Folder (or PRGFF if you want to splutter) is a PHP script able to parse for medias the folder in which it is stored.

It will generate for you, lazies out there, a podcast feed which will somewhat kind of work but iTunes will probably pee on it with disgust

That's about it.
You put the file in a folder with medias, BOOM, you have a podcast feed.

It's rough, it's kind of not for production use, but hey, do you have time to spare doing all the work yourself ?

The only thing is that you'd better have some id3tags ready in your files, AS YOU SHOULD *wink wink*, because it heavily depends on it.

### What it does :
- get all the files in the folder with their lenght and generate XML items with an enclosure for each one

### What it will do (TODO)
- ignore every type of file or folder EXCEPT the handful which are compatible and really used with podcasting (audio/mp3, audio/m4a, video/mp4, video/m4v). No epub or pdf, because WHO USES IT ?

- Get info for the items from the id3tags :
   - get the title from the title of the media (id3tag)
   - get the author from the artist of the media (id3tag)
   - get description fom the comment section of the id3tags (why not accept markdown formatting)
   - GET ALL THE INFOS FROM THE ID3TAG AND PUT IT IN THE FEED, in a sense.

- get the pubdate from the file date of modification
- Have a GUID liked to the filename so that you can replace a file if you didn't check before uploading and have to reupload your episode and not f...k everything up for your subscribers 
- get the feed infos it can't get from elsewhere from a feed.config file you will put next to the php script (if you want)

### What it would be fun to do
- get the artworks from the id3tag, put it in a subfolder and link to it for every episode
- get some sort of cache so that the id3tags are not read at every f....ing refresh
- stylize the duck out of that xml so that a human can read it without bionic/dev eyes and BOOM, here is your website !

### What it will NEVER DO
- link to an article for every episode, because if you have a blog to link to, it will probably be able to do podcasting, or else, get a good one moron.
- add tags to items, because no-one fills it anyway


That will be fun, lads.
