# phpDVDProfiler

The phpDVDProfiler project allows you to display on the web your DVD collection maintained with Invelos's DVDProfiler software.

Alas, the project seems to have been dead for some time and has stopped working with newer versions of PHP. So for my own needs, I've decided to make it work again with PHP 7.2, and publish it too so that others can benefit if they need it. I have no intention on making more work than basic maintenance to support newer versions of PHP.

## Getting Started

See the file phpdvdprofiler-install.txt for instructions on installing the application.

## Authors

* **FredLooks and contributors** - *Initial work* - [FredLooks](http://www.invelos.com/UserProfile.aspx?Alias=FredLooks)
* **Thomas Fintzel** - *Gallery code for the covers*
* **Julien Mudry** - *Compatibility with PHP 7.2*
* **Gavin-John Noonan** - *Suport of 4K UHD media types and numerous fixes* - [Gavin-John Noonan](https://github.com/gjnoonan)
* **jakub961241** - *Czech translation and PHP 8.3+ compatibility upgrade*

## Changelog

### Version 20260208 (fork by jakub961241)
Changes since version 20250511:
- feature: added complete Czech translation (`lang_cs.php`) with 900+ translated strings including UI labels, genre names, country names, audio languages, crew roles and statistics
- feature: registered Czech locale in `locale.php` and added language selector in `index.php`
- fix: full PHP 8.3+ compatibility — changed default `$dbtype` from `mysql` to `mysqli` in `globalinits.php`
- fix: replaced direct `mysql_fetch_array()` in `getimages.php` and `mysql_error()` in `imagedata.php` with database abstraction layer methods
- fix: replaced deprecated `var` property declarations with `public` in `mysqli.php`, `mysql.php` and `incupdate.php`
- fix: replaced deprecated `@list()` with explicit array access using null coalescing (`??`) in `functions.php`
- fix: added deprecation notice to `mysql.php` recommending `mysqli`

### Version 20250511
Changes since version 20230807:
- security: removed XSS using the search function (CVE-2025-46729)
- fix: some minimal formatting
- fix: better compatibility with PHP 8.0 (in TestFonts) and 8.2
- fix: display of watched statistics when there's no entry for the current month (#49)

## License

No idea what the original license was (couldn't find it in the code). At least the gallery code from Thomas Fintzel allows any modification / redistribution / etc. as long as the copyright is there. As for my own modifications and contributions, do whatever you like with them.
