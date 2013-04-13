blindRSS
========

Simple Web-based RSS reader writtin in PHP.

See the demo at http://www.crash-override.net/blindrss_demo/

Installation
============

Put the complete extracted directory where your webserver can find it.
Your webserver must support PHP.

Create a database and run the SQL file doc/DATABASELAYOUT.sql on it.
Copy etc/config.example.php to etc/config.php and edit to match the database.

Create a cronjob that runs the file blindrss.php every once in a while.
To run it every 15 minutes on the command line, it could look like this:

*/15 * * * * php /var/www/blindrss/blindrss.php

You can also call the file blindrss.php from a webbrowser.

Open the index.html in your javascript-capable webbrowser.
Enjoy!

Licenses
========

This work is in the Public Domain

The layout, CSS and Icons are from the Twitter Bootstrap toolkit:
	http://twitter.github.com/bootstrap/
Twitter Bootstrap Code licensed under Apache License v2.0, documentation under CC BY 3.0.
Glyphicons Free licensed under CC BY 3.0.

The RSS logo is from the Mozilla foundation as made available at http://www.feedicons.com/
This means the following file:
	feed-icon-14x14.png

Earlier versions included images from the Tidy Blog design:
	http://www.oswd.org/design/information/id/3505

Earlier versions included images from the Tango Icon theme under the followign license:
	Creative Commons Attribution-ShareAlike 2.5 License Agreement
