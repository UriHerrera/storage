=== BetterDocs Pro ===
Contributors: wpdevteam, re_enter_rupok
Donate link: https://wpdeveloper.com
Tags: docs, documentation, knowledge base, faq, chatgpt ai writer
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 4.3.1
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

A better documentation and knowledgebase plugin for WordPress

== Description ==

Do you want to reduce your support pressure immediately? How about you creating a stunning and resourceful knowledge base for your customers? 🤔

82% of customers prefer to support through an online knowledge base and actually get annoyed to create support tickets as its lengthy process. So creating an informative Documentation page can help to enhance your customer experience.

But how do you create a stunning docs page easily in WordPress site without any coding? Well, we’ve got you covered. 😎

## 📒 Create Stunning Knowledge Base To Scale Customer Support ##

[BetterDocs](https://betterdocs.co/) will help you to create and organize your documentation page in a beautiful way that will make your visitors find any help article easily. It will facilitate your client to take faster decisions and get helped on the spot by self-servicing instead of avoiding the lengthy conversation.

## 🔥 Power Up Knowledge Base To Reduce Support Tickets ##

BetterDocs will help you to give the exact solution and encourage self-servicing to reduce support workload.

### 🌟 Top Features ###

- Choose stunning premade template design to organize your knowledge Base
- Sort posts in Table of Content or Sticky TOC to provide absolute user experience
- Customize documentation page in advance by adding shortcode & page builder widgets
- In-built advanced live search will help visitors to get the exact docs solution
- Integrated with Analytics to track and evaluate the performance


## 📋 Interactive Table of Content (TOC) ##
Sort multiple posts using TOC or Sticky TOC and give exact docs solution to your visitors on spot & give absolute support. This stunning TOC moves with your scroll, so your visitors can always go to other pages easily.

## ⚙️ Advanced Integration With Analytics (PRO) ##

Track and evaluate activities on your documentation page and improve customer experience. Also, analyze the site traffic to get insights about your Knowledge Base.

## 🚀 Backed By A Trusted Team ##

This Documentation plugin is brought to you by the team behind [WPDeveloper](https://wpdeveloper.com/), a dedicated marketplace for WordPress, trusted by 400,000+ happy users.

## 👨‍💻 DOCUMENTATION AND SUPPORT ##

- For documentation and tutorials go to our [Documentation](https://betterdocs.co/docs/)
- If you have any more questions, visit our support on the Plugin’s Forum
- For more information about features, FAQs and documentation, check out our website at [BetterDocs](https://betterdocs.co/)

## 💙 Loved BetterDocs? ##

- Join our [Facebook Group](https://www.facebook.com/groups/wpdeveloper.com/)
- Learn from our tutorials on [Youtube Channel](https://wpdeveloper.com/go/youtube-channel)
- Or rate us on WordPress

## 🔥 WHAT’S NEXT ##

If you like this docs plugin, then consider checking out our other projects:

[Essential Addons For Elementor](https://wordpress.org/plugins/essential-addons-for-elementor-lite/) – Most popular Elementor extensions with 300,000+ active users in the WordPress repository.
[NotificationX](https://wordpress.org/plugins/notificationx/) – Best Social Proof & FOMO Marketing Solution
[WP Scheduled Posts](https://wordpress.org/plugins/wp-scheduled-posts/) – Complete solution for WordPress Post Scheduling to manage schedules through an editorial calendar.

Visit WPDeveloper to learn more about how to do better in WordPress with [Help Tutorial, Tips & Tricks](https://wpdeveloper.com/blog)!


== Installation ==

Follow the following steps to install the plugin.

e.g.

1. Upload `betterdocs.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `<?php do_action('plugin_name_hook'); ?>` in your templates

== Frequently Asked Questions ==

= Does it work with any WordPress theme? =

Yes, it will work with any standard WordPress theme.


== Screenshots ==


== Changelog ==

= 4.3.1 - 27/09/2026 =

- Fixed: GitHub could not be reconnected after disconnecting Git Sync; the authorization popup never returned and the only way out was to uninstall the BetterDocs App from GitHub by hand
- Fixed: Encyclopedia entries are now listed alphabetically within each letter, including entries pulled in by Load More
- Fixed: Instant Answer returned no results when the knowledge base is served from a subdomain or a domain alias
- Few minor bug fixes and improvements

= 4.3.0 - 16/09/2026 =

- New: Content Intelligence — a new Analytics section with five screens: Overview, Content Gaps, Stale Content, Content Health Score and Duplicates & Overlaps
- New: Content Gaps finds what your visitors search for and cannot find, clusters those searches into topics, and can draft the missing doc for you with your own OpenAI key
- New: Stale Content flags docs that have gone out of date, and Content Health Score grades every doc on structure, freshness, length and reader feedback — both run locally, no API key needed
- New: Duplicates & Overlaps finds docs that cover the same ground, so you can merge or retire them
- New: GDPR consent option for the Instant Answer Ask form
- Improvement: Content Intelligence counts can be scoped to a single knowledge base on multi-KB sites
- Improvement: Instant Answer is now keyboard and screen-reader accessible — the launcher has an accessible name and the panel uses proper dialog and focus semantics
- Fixed: Analytics Overview — the "Top Search Queries" card was always empty, even on sites with search data
- Fixed: Analytics — the Apply button in the custom date-range picker rendered white-on-white and was invisible
- Fixed: Opening Multiple KB flashed a bare page — no header, no table, no skeleton — before the screen appeared
- Improvement: Multiple KB row actions now match the rest of the admin
- Fixed: Popular Docs block listed only a subset of docs under the Last Created and Last Updated sorts
- Fixed: Removing every attachment or related article from a doc did not save the empty state
- Fixed: A null post could fatal single_kb_terms() on multi-KB sites
- Fixed: Content restriction settings wiped the nightly Content Intelligence scans
- Few minor bug fixes and improvements

= 4.2.2 - 03/09/2026 =

- New: API Documentation — API Code Samples block and Elementor widget for block and Elementor themes
- New: API Documentation now supports Swagger / OpenAPI 2.0 specs alongside OpenAPI 3.x
- New: More MCP abilities — manage API Documentation, search insights, Git Sync and related docs from MCP-capable editors
- Fixed: API Documentation — stalled materialization runs now recover, operations appear under every tag they declare, new references publish by default, and Try it offers every declared security scheme
- Fixed: The Documentation grid hid knowledge bases containing only private docs from admins and editors
- Improved: Security Enhancement
- Few minor bug fixes and improvements

= 4.2.1 - 27/08/2026 =

- New: Knowledge Base abilities for the MCP server — list, create, update and delete knowledge bases from Claude, Cursor, VS Code and other MCP-capable editors
- New: Analytics ability exposes Pro's doc analytics to MCP-capable editors and any Abilities-aware tool
- Improved: Pro registers its abilities through BetterDocs' abilities registrar, so the MCP tool catalogue reflects Pro features automatically
- Few minor bug fixes and improvements

= 4.2.0 - 20/08/2026 =

- New: API Documentation — turn an OpenAPI/Swagger spec (or an imported Postman collection) into a fully rendered, searchable API reference, with per-endpoint docs, code samples, a "Try it" console, and optional AI-generated descriptions
- Improved: Security Enhancement
- Few minor bug fixes and improvements

= 4.1.0 - 04/08/2026 =

- New: Advanced Analytics — AI Traffic insights: see how AI assistants & crawlers reference your docs, with Share Insight image sharing
- Improved: Security Enhancement
- Few minor bug fixes and improvements

= 4.0.0 - 22/07/2026 =

- New: Advanced Analytics — Article & Author Performance, Link Health scanner, reading-completion, geography, and per-module CSV export, with role-based access and GA4 (Measurement Protocol) support
- New: "Write with AI from Git" — browse a connected repository and draft docs from its content
- Improvement: Redesigned Write / Edit AI Studio modals (Pro layer)
- Fixed: Git Sync now auto-refreshes the GitHub App user token before it expires, preventing sync failures
- Fixed: Advanced content restriction now redirects to your configured off-site Redirect URL instead of falling back to wp-admin
- Few minor bug fixes and improvements

= 3.9.4 - 08/07/2026 =

- Fixed: Doc attachments and related articles were corrupted on save, showing blank attachment rows and could cause a fatal error on the docs page
- Improved: Previously corrupted attachment and related article data is now cleaned up automatically
- Few minor bug fixes and improvements

= 3.9.3 - 08/07/2026 =

- Improvement: Revamped admin with new React-based Knowledge Base and Glossaries screens
- Few minor bug fixes and improvements

= 3.9.2 - 01/07/2026 =

- Few minor bug fixes and improvements

= 3.9.1 - 24/06/2026 =

- Added: reCAPTCHA v3 and spam protection (honeypot, rate limiting) for the Instant Answer Ask form
- Improved: Server-side controls for Instant Answer feedback reactions and ask-form file uploads
- Improved: Security Enhancement
- Fixed: Docs archive (FSE) template could show the wrong block or render blank after switching the active knowledge base
- Fixed: Fatal error on some WPML setups caused by an unguarded class reference
- Fixed: Plugin Check (PCP) compliance across the codebase
- Few minor bug fixes and improvements


= 3.9.0 - 08/06/2026 =

- Added: AI-powered real-time related docs panel
- Few minor bug fixes and improvements


= 3.8.2 - 19/05/2026 =

- Improved: Settings tabs now support direct deep links
- Fixed: Conflict with Filter Everything Pro on doc category pages
- Fixed: Single doc permalink returning 404 for non-Latin doc category slugs under WPML
- Few minor bug fixes and improvements


= 3.8.1 - 13/05/2026 =

- Fixed: WP search not working inside BetterDocs knowledge base (Poseidon Pro theme conflict)
- Fixed: Advanced access rules ignored by Instant Answers and search results
- Fixed: Category Grid Multi-KB query returning no docs on frontend
- Improved: Block API version update for apiVersion 3 iframe compatibility
- Improved: Security Enhancement
- Few minor bug fixes and improvements


= 3.8.0 - 11/05/2026 =

- Added: Git Integration - bidirectional sync between WordPress Docs and a Git repository.
- Few minor bug fixes and improvements

= 3.7.1 - 15/04/2026 =

- Improved: Security Enhancement
- Few minor bug fixes and improvements


= 3.7.0 - 18/03/2026 =

- Fixed: Sidebar not displaying for translated docs in non-Latin languages
- Fixed: Fatal error caused by License Management
- Few minor bug fixes and improvements

= 3.6.15 - 11/03/2026 =

- Few minor bug fixes and improvements

= 3.6.14 - 08/03/2026 =

- Fixed: Severe duplicate URL indexing across multiple Knowledge Bases
- Fixed: Multiple KB Sleek Layout block generating an uncaught error
- Fixed: Knowledge Base queries not working properly for non-Latin languages
- Few minor bug fixes and improvements

= 3.6.13 - 26/02/2026 =

- Few minor bug fixes and improvements

= 3.6.12 - 21/01/2026 =

- Added: Permalink support for Instant Answer single docs titles.
- Added: Knowledge based search shortcode support.
- Few minor bug fixes and improvements

= 3.6.11 - 15/12/2025 =

- Few minor bug fixes and improvements

= 3.6.10 - 04/12/2025 =

- Added: WordPress 6.9 Compatibility
- Few minor bug fixes and improvements

= 3.6.9 - 25/11/2025 =

- Fixed: Customizer controls were not working across multiple layouts.
- Fixed: Glossary descriptions now excluded from Single Doc Printing.
- Few minor bug fixes and improvements.

= 3.6.8 - 17/11/2025 =

- Fixed: Inconsistent docs ordering in the admin dashboard for multilingual setups.
- Few minor bug fixes and improvements

= 3.6.7 - 28/09/2025 =

- Fixed: Critical error when excluding encyclopedia letters.
- Few minor bug fixes and improvements

= 3.6.6 - 09/09/2025 =

- Fixed: Glossary translations are being reset automatically if Glossary items are updated
- Fixed: Encyclopedia Glossary Cyrillic alphabetical bar not working
- Few minor bug fixes and improvements

= 3.6.5 - 17/08/2025 =

- Fixed: Glossary terms showing in multiple languages on the same page when using WPML
- Few minor bug fixes and improvements

= 3.6.4 - 03/08/2025 =

- Added: Heading tag controls for several layouts in the customiser, Elementor widget, and Gutenberg block.
- Few minor bug fixes and improvements

= 3.6.3 - 14/07/2025 =

- Improvement: Optimized Access Control & Restriction feature to prevent memory-related fatal errors
- Fixed: Restricted docs appearing in search results
- Fixed: Instant Answers cross-domain feature not working properly
- Few minor bug fixes and improvements

= 3.6.2 - 24/06/2025 =

- Few minor bug fixes and improvements.

= 3.6.1 - 19/06/2025 =

- Fixed: Instant Answer | Search field is not working.
- Few minor bug fixes and improvements.

= 3.6.0 - 02/06/2025 =

- Added: Advanced Role-based access restriction: apply individual access controls to each Knowledge Base, Category, and individual Doc for enhanced content protection.
- Few minor bug fixes and improvements.

= 3.5.8 - 26/05/2025 =

- Added: Option to enable or disable individual sections and tabs in the Instant Answer settings.
- Improvement: Updated cross-domain support to follow the configuration of the new Instant Answer settings.
- Few minor bug fixes and improvements.

= 3.5.7 - 19/05/2025 =

- Fixed: "Enable Non-Latin Alphabetical Order" setting was not working correctly for the Encyclopedia Elementor widget.
- Few minor bug fixes and improvements.

= 3.5.6 - 08/05/2025 =

- Added: Cross-Domain Support for AI Chatbot in Instant Answer.
- Few minor bug fixes and improvements


[See changelog for all versions](https://betterdocs.co/changelog/).


== Upgrade Notice ==

