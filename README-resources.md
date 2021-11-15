# Overview of external resources

All files in resources/ are managed by Bower (http://bower.io/).
The file .bowerrc defines global settings for Bower, the file bower.json lists our dependencies.
Each dependency is installed in a separate directory in resources/ and
includes meta information in the file .bower.json located in a dependency's directory.
License information missing from the .bower.json files are documented below.

## Useful commands:

* Install a new dependecy:
        bower install 'package#version' --save --save-exact
* Search for a package ([online](http://bower.io/search))
        bower search package
* Remove unused files from resources/ (requires extra configuration in bower.json):
        preen

## Additional licenses

* tinymce-dist: LGPL-2.1
* gosquared-flags: MIT
* jquery-ui: MIT
* modernizr: MIT
* tablesorter: MIT
