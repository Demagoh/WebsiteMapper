# WebsiteMapper
**WebsiteMapper** is a Linux CLI script written in PHP, which maps out your website from the server's filesystem.

## Functionality
For the actual mapping of your website you can specify:
- which files/directories to ignore
- which directories to generalize (only name, not explore)
- which files are index files and should be culled down to just a "\[path]/"

You can also specify how you want the map of the website to be formatted:
- as plain text
- as a Cloudflare rule expression.

At some point I'll also put in a feature to let you know which paths weren't found. I might also add more ways to format the website map.

## License
This script is licensed under the <a href="LICENSE" target="_blank">MIT license</a>.

## Contributing
Just fork it, I'm not going to deal with pull requests.

## How to use it
To run this script you'll need:
- a PHP CLI interpreter (should come with your standard PHP packages on Linux)
- a JSON config file as shown below, with the following attributes:
    - **websiteRootSystemPath** (required)
        - specifies the system path (or filesystem path, if you so wish), to the root of the public part of your website
        - type: *string*
        - example: ``"/www/public"``
    - **websitePathsToIgnore** (optional)
        - specifies the website paths to the files and/or directories which you want to be ignored (left out of the website map)
        - type: *array of strings*
        - example: ``["/totallyRealPage.php","/hello"]``
    - **websiteDirectoriesToGeneralize** (optional)
        - specifies the website paths to the directories which you want to be "generalized" (see above)
        - type: *array of strings*
        - example: ``["/files","/CSS"]``
    - **indexFilesToCull** (optional)
        - specifies how the index files are named
        - type: *array of strings*
        - example: ``["/index.html","/index.php"]``

An example of this JSON config file:
```
{
  "websiteRootSystemPath": "/www/public",
  "websitePathsToIgnore": [
    "/totallyRealPage.php",
    "/hello"
  ],
  "websiteDirectoriesToGeneralize": [
    "/files",
    "/CSS"
  ],
  "indexFilesToCull": [
    "/index.html",
    "/index.php"
  ]
}
```
<br />

You'll need to specify this config file for the script to work:
```
$ php websiteMapper.php myconfig.json
```

If you have any issues in your configuration, the script will throw an "error" and terminate its execution. Otherwise you'll be able to continue on to the mapping of the website.

Once you've mapped the website you'll only have to choose how you want the website map to be saved. At this time the script always saves your website map to a file called ``websitemap.txt`` (it will create it if it doesn't already exist).

That's it. Nothing complicated.