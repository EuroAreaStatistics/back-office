# Back office

Configuration
-------------

Set up an Apache HTTP Server with mod\_rewrite and access control.

Adjust the authenticated user name in `02projects/default/urlMapperConfig.php` (default: "admin").

Copy all files to the web server's document root.

Update the URL to the preview server in `mainConfig.php` and set the environment variable `CODE_PREVIEW` on the web server to match the preview server's `API_PW`.
