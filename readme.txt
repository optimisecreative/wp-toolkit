=== Optimise Creative Toolkit ===
Contributors: optimisecreative
Tags: performance, optimisation, alt text, client, login
Requires at least: 6.3
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Switch on only the performance and login tweaks your site needs. Every module is off by default.

== Description ==

Optimise Creative Toolkit is a modular set of performance and login tweaks. Each module is off by default and does nothing until you switch it on, so a site only carries the changes it actually uses.

* Each module is off by default.
* A module that is off registers no hooks, so it does nothing on your site.
* The plugin makes no external requests.
* All settings live in a single option row.
* Everything is removed when the plugin is uninstalled.

= Modules =

* **Optimise Clean** removes emojis, oEmbed embeds, XML-RPC, unneeded head links, jQuery Migrate, dashicons for visitors, and slows the Heartbeat API.
* **Optimise Security Headers** adds standard browser security headers and stops visitors finding usernames through author scans or the REST API.
* **Optimise Environment** colours the admin bar on local and staging sites, labels admin tabs, and hides non-production sites from search engines.
* **Optimise Duplicate** adds a Duplicate link to posts and pages that copies content, categories, tags and custom fields into a new draft.
* **Optimise Lazy Hero** keeps the first images on a page from being lazy-loaded and preloads the featured image, for a faster Largest Contentful Paint.
* **Optimise Upload Guard** shrinks oversized photos on upload and can block very large image files.
* **Optimise Alt** lists every image without alt text so you can fix them all on one screen.
* **Optimise Noindex** keeps tag, author, date, attachment and other thin archive pages out of search results and the built-in sitemap.
* **Optimise Revisions** caps how many revisions each post keeps and slows autosave, so the database stays lean.
* **Optimise Comments Off** switches comments off everywhere: front end, admin menus, admin bar, feeds, REST API and XML-RPC.
* **Optimise Scripts** adds head, body and footer code (GA4, Tag Manager, pixels, cookie banners) without editing the theme. Only users allowed raw HTML can change it.
* **Optimise Mail From** sends site emails from your business name and address instead of "WordPress".
* **Optimise Client Mode** hides plugin, theme, tool and settings menus from clients, blocks plugin, theme and core changes, and adds a support widget. Named developer accounts are never restricted.
* **Optimise Login** lets you change the login screen logo and colours, and optionally add a credit link (off by default).

= Export and import =

Download a site's toolkit settings as a JSON file from the settings page and import it on your next site to reuse the same setup.

= Privacy =

Optimise Creative Toolkit does not collect, store or send any data about you, your site or your visitors. It makes no external requests. Links to optimisecreative.com in the admin are ordinary links and only open if you click them.

= About Optimise Creative =

Optimise Creative Toolkit is built and maintained by [Optimise Creative](https://optimisecreative.com/?utm_source=wordpress.org&utm_medium=readme&utm_campaign=optimise-creative-toolkit), a UK web studio. It is the toolkit we install on every WordPress site we build, released free for everyone.

Need more than a toolkit? We help with [WordPress speed optimisation](https://optimisecreative.com/wordpress-speed-optimisation/?utm_source=wordpress.org&utm_medium=readme&utm_campaign=optimise-creative-toolkit), [security reviews](https://optimisecreative.com/wordpress-security-review/?utm_source=wordpress.org&utm_medium=readme&utm_campaign=optimise-creative-toolkit), [accessibility fixes](https://optimisecreative.com/website-accessibility-fixes/?utm_source=wordpress.org&utm_medium=readme&utm_campaign=optimise-creative-toolkit) and [custom plugin development](https://optimisecreative.com/custom-wordpress-plugin-development/?utm_source=wordpress.org&utm_medium=readme&utm_campaign=optimise-creative-toolkit).

== Installation ==

1. Upload the `optimise-creative-toolkit` folder to the `/wp-content/plugins/` directory, or install the plugin through the WordPress Plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to Settings > Optimise Creative Toolkit.
4. Switch on the modules you need, adjust their options, and save.

== Frequently Asked Questions ==

= Does it slow my site down? =

No. Modules that are switched off load no code, and the plugin makes no external requests. Active modules only make the changes you have chosen.

= What happens on uninstall? =

The plugin's single settings option is deleted, so no settings are left behind in your database.

= Is there a pro version? =

No. Every module is free, with no upsells, licence keys or locked features.

= Can you help with my site? =

Yes. [Get in touch with Optimise Creative](https://optimisecreative.com/contact/?utm_source=wordpress.org&utm_medium=readme&utm_campaign=optimise-creative-toolkit) for speed, SEO, security or custom development work.

== Changelog ==

= 0.1.0 =
* Initial release.
