# Tool for crawling (most of) the Euro Area Statistics web site

This script uses [Puppeteer](https://developers.google.com/web/tools/puppeteer)
(an API to conrol a headless Google Chrome browser) to crawl the Euro Area Statistics web site.
It visits all project pages, Insights and interactive publications,
saving screenshots to the `shots` folder and downloads to the `files` folder.
Any JavaScript error messages while loading a page will be displayed on stdout.

## Usage

After installing Puppeteer and [ExifTool](https://exiftool.org/)
(used to add the URL of a screenshot to its description),
create a new directory for the output and run the script via `node`:

```
mkdir output
node spider.js output
```

## Caveats

The script does not visit the blog, Banks' Corner, or languages other than English.

The bank notes visualisation in chapter 1.1 of the interactive publication "Money"
takes several minutes to load and is thus disabled (by blocking the necessary JavaScript library).
An error message `ReferenceError: THREE is not defined` will be displayed.

The flows visualisation in "Financing and investment dynamics" and the pie charts in
chapter 2.1 of the interactive publication "Inflation" are not loaded completely.
