# RSS GENERATOR

RSS Generator is a PHP script able to parse the folder in which it is stored.
It will generate for you, lazies out there,a podcast feed which will somewhat work but iTunes will probably pee on it with disgust

That's about it.
You put the file un a folder with medias, BOOM, you have a podcast feed.

### What it does :
- get all the files in the folder with their lenght and generate XML items with an enclosure for each one

### What it will do (TODO)
- ignore every time of file or folder and concentrate on the handful which are compatible witgh podcasting

- Get from the id3tags :
   - get the title from the title of the media (id3tag)
   - get the author from the artist of the media (id3tag)
   - get description fom the comment section of the id3tags
   - GET ALL THE INFOS FROM THE ID3TAG AND PUT IT IN THE FEED, in a sense.

- get the pubdate from the file date of modification
- Have a GUID liked to the filename so that you can replace a file if you fuck up and not fuck up you feed
- get the feed infos it can't get from elsewhere from the feed.config file you will put next to the php script

### What it would be fun to do
- get the artworks from the id3tag, put it in a subfolder and link to it for every episode
- get some sort of cache so that the id3tags are not read at every fucking refresh
- stylize the fuck out of that xml so that a human can read it without bionic/dev eyes and BOOM, here is your website !

That will be fun, lads.
