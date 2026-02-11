# phpDVDProfiler

The phpDVDProfiler project allows you to display on the web your DVD collection maintained with [Invelos's DVD Profiler](http://www.intl.dvdprofiler.com/) software.

Alas, the project seems to have been dead for some time and has stopped working with newer versions of PHP. So for my own needs, I've decided to make it work again with PHP 7.2, and publish it too so that others can benefit if they need it. I have no intention on making more work than basic maintenance to support newer versions of PHP.

## What's New in This Fork

This fork extends the original project with PHP 8.3+ compatibility and several new features:

- **Modern UI** — Bootstrap 5.3.3 dark theme applied across the entire application
- **Web-based installer** (`install.php`) — 3-step setup wizard that checks requirements, creates configuration, and initializes the database
- **TMDB Cover Fetcher** — search and download movie posters from The Movie Database; includes one-click auto-fetch for all missing covers
- **Web-based XML upload** — upload your `collection.xml` directly from the browser instead of requiring FTP access
- **Full internationalization** — complete translations for 10 languages (English, Czech, German, Danish, Finnish, French, Dutch, Norwegian, Russian, Swedish) with 900+ translated strings
- **Titles Per Page** — configurable pagination for large collections
- **Example configuration** — `config/localsiteconfig.php.example` with all settings documented and ready to customize
- **PHP 8.3+ fixes** — resolved deprecation warnings, SQL compatibility issues, null handling errors, and an XSS vulnerability (CVE-2025-46729)
- **Code reorganization** — PHP files moved into `core/`, `pages/`, `includes/`, `admin/`, `graphs/` subdirectories with backward-compatible URL rewrites

## Getting Started

### Quick Start

1. Extract the files to your web server directory
2. Open `install.php` in your browser and follow the wizard
3. Upload your `collection.xml` via the update page
4. Run the import from `index.php?action=update`

### Manual Installation

See the file `docs/phpdvdprofiler-install.txt` for detailed instructions. Copy `config/localsiteconfig.php.example` to `config/localsiteconfig.php` and edit your settings there.

## Requirements

- PHP 7.2+ (tested with PHP 8.3)
- MySQL 5.7+ or MariaDB 10.x+
- Apache with `mod_rewrite`
- PHP extensions: `mysqli`, `mbstring`, `xml`, `intl`, `gd` (optional, for thumbnails)

## Authors

- **FredLooks** and contributors — Initial work — [FredLooks](https://github.com/FredLooks)
- **Thomas Fintzel** — Gallery code for the covers
- **Julien Mudry** — Compatibility with PHP 7.2
- **Gavin-John Noonan** — Support of 4K UHD media types and numerous fixes — [Gavin-John Noonan](https://github.com/gavinjohn)
- **jakub961241** — PHP 8.3 compatibility, Bootstrap theme, installer, TMDB cover fetcher, Czech translation, full i18n

## License

No idea what the original license was (couldn't find it in the code). At least the gallery code from Thomas Fintzel allows any modification / redistribution / etc. as long as the copyright is there. As for my own modifications and contributions, do whatever you like with them.
