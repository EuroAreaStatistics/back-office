# Back office

Configuration
-------------

For development, start a local web server:
```
env REMOTE_USER=admin php -S localhost:8000 devServer.php
```
and visit http://localhost:8000/ .

For production:
- Set up an Apache HTTP Server with mod\_rewrite and access control.
- Adjust the authenticated user name in `02projects/default/urlMapperConfig.php` (default: "admin").
- Copy all files to the web server's document root.
- Enable write access for PHP scripts to the directory `02projects/default/wizard-edit-repo`.
- Update the URL to the preview server in `mainConfig.php` and set the environment variable `CODE_PREVIEW` on the web server to match the preview server's `API_PW`.
