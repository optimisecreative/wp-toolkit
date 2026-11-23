# Optimise Creative Toolkit

Free, modular WordPress tools from [Optimise Creative](https://optimisecreative.com/wp-toolkit/). Switch on only the modules a site needs. Every module is off by default and registers no hooks until you enable it. There are no upsells, no tracking and no external requests.

**Requires:** WordPress 6.3+ · PHP 8.0+ · **Licence:** GPL-2.0-or-later

## Modules

| Module | What it does |
|---|---|
| Clean | Removes emojis, embeds, XML-RPC, unused head links, jQuery Migrate and Dashicons for visitors, and can slow the Heartbeat API |
| Security Headers | Adds nosniff, frame, referrer and permissions headers, optional HSTS, and hides usernames from author scans and the REST API |
| Environment | Colours the admin bar on local and staging sites, labels admin tabs, and noindexes non-production sites |
| Duplicate | Copies posts and pages into new drafts with one click |
| Lazy Hero | Loads the first images eagerly and preloads the featured image, for a faster Largest Contentful Paint |
| Upload Guard | Scales huge photos down on upload and can block oversized files |
| Alt | An alt text audit page with a score and inline saving |
| Noindex | Keeps thin archive pages out of search results and the core sitemap |
| Revisions | Limits stored revisions and slows autosave |
| Comments Off | Switches comments off across the whole site |
| Scripts | Head, body and footer code boxes for GA4, Tag Manager and pixels |
| Mail From | Sends site emails from your business name instead of "WordPress" |
| Client Mode | Simplifies the admin for clients and blocks plugin, theme and core changes; named developer accounts are never restricted |
| Login | Brands the login screen with your logo and colours |

Settings live in a single option row (`optimise_toolkit`), which is deleted on uninstall. **Settings → Optimise Creative Toolkit** can export and import that configuration as JSON.

## Adding a module

1. Create `modules/<id>/class-<id>.php` with a class extending `OPTK_Module` that implements `id()`, `label()`, `description()` and `boot()`, plus an optional `fields()`.
2. Register it in `OPTK_Plugin::MODULES` in `includes/class-plugin.php`.

The settings card, sanitising and storage are handled for you. Use only core hooks, and make no external requests.

## Release

The WordPress.org readme is `readme.txt`. Files listed in `.distignore` are left out of the release zip.
