# All URL Export

**Contributors:** Yash Ojha  
**Collaborated with:** Ifox Solutions  
**Tags:** export, csv, urls, posts, pages, wpml, translations  
**Requires at least:** WordPress 5.0  
**Tested up to:** WordPress 6.4  
**Requires PHP:** 7.4  
**Stable tag:** 1.0.0  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

Export all post types with customizable fields including title, H1, content, dates, author, URLs, editor used, and WPML translations.

## Description

All URL Export is a powerful WordPress plugin that allows you to export all your posts, pages, and custom post types with highly customizable options.

### Features

* **Multiple Post Types** - Export posts, pages, and any custom post type
* **Flexible Field Selection** - Choose exactly which fields to export:
  * Post ID
  * Title (post title or H1 from content)
  * URL/Permalink
  * Slug
  * Content
  * Excerpt
  * Author
  * Published Date
  * Last Modified Date
  * Post Type
  * Status
  * **Editor Used** (Block Editor, Classic, Elementor, Divi, WPBakery, Beaver Builder, Oxygen, Brizy, Thrive Architect)
  * Featured Image URL
  * Categories
  * Tags
  * SEO Meta Title
  * SEO Meta Description

* **Title/H1 Options** - Fetch the title from:
  * WordPress post title
  * First H1 tag in content (useful for pages where H1 differs from post title)
  * Both (exported in separate columns)

* **Editor Detection** - Automatically detects which editor was used:
  * Block Editor (Gutenberg)
  * Classic Editor
  * Elementor
  * Divi Builder
  * WPBakery Page Builder
  * Beaver Builder
  * Oxygen Builder
  * Brizy
  * Thrive Architect

* **WPML Integration** - Full support for multilingual sites:
  * Automatically detects WPML installation
  * Exports translations alongside original posts
  * Each language gets its own columns in the CSV

* **Preview & Download** - Preview your export before downloading:
  * Interactive preview table
  * Download as CSV with UTF-8 support (Excel compatible)

## Installation

1. Upload the `all-url-export` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Tools > All URL Export to access the export interface

## Frequently Asked Questions

### Does this plugin support custom post types?

Yes! The plugin automatically detects all public post types registered on your site.

### Can I see which editor was used to create each post?

Yes! Select the "Editor Used" field and the export will show whether each post was created with Gutenberg, Classic Editor, Elementor, Divi, WPBakery, or other page builders.

### Can I export content from page builders like Elementor or Divi?

Yes, the plugin has special parsing for Elementor, Divi, and WPBakery to extract clean content and H1 tags.

### How does the WPML integration work?

When WPML is active, you can enable translation export. The plugin will fetch posts in the default language and add columns for each translation language.

### What SEO plugins are supported for meta titles/descriptions?

The plugin supports Yoast SEO, Rank Math, and All in One SEO.

### Is the CSV file compatible with Excel?

Yes, the CSV includes a UTF-8 BOM for proper encoding in Excel and other spreadsheet applications.

## Screenshots

1. Main export settings interface
2. Post types and field selection
3. Title source and editor options
4. WPML translation options
5. Preview panel with export data

## Changelog

### 1.0.0

* Initial release
* Multiple post type support
* Customizable field selection
* Title/H1 extraction options
* Editor detection (Gutenberg, Classic, Elementor, Divi, WPBakery, Beaver Builder, Oxygen, Brizy, Thrive Architect)
* Multiple editor support for content parsing
* WPML translation integration
* Preview and CSV download functionality

## Upgrade Notice

### 1.0.0

Initial release of All URL Export plugin.
