UBC Augmented Reality
=====================

**Contributors**: Nathan Sidles, Eduardo Jovel

**Tags**: WordPress, Google Maps, wiki, embed, wiki-embed, augmented reality, AR

UBCAR is a community-driven, wiki-inspired, deeply interactive WordPress plugin. It allows WordPress administrators and editors to upload and display geographic locations and associate them with tours, layers, and media files (like images, video links, and embedded webpages).

UBCAR also allows **any** logged-in user to make comments on points, respond to others' comments, and upload their own media files for existing points.

UBCAR displays its points on a map or street view. It allows users to take virtual tours, perusing others' comments and media files, and uploading their own.

UBCAR depends very, very heavily on jQuery and Google Maps. To use most of its map functionality, you'll need to provide a [Google API key](https://developers.google.com/maps/documentation/javascript/tutorial#api_key) to the UBCAR settings page. UBCAR also depends slightly on UBC CTLT's Wiki Embed plugin, but degrades as gracefully as a dried banana if it is not installed.

Installation
------------

1. Download the zipped plugin.
2. Unzip the zipped plugin.
3. Upload the unzipped plugin to your WordPress plugins folder (wp-content/plugins/).
4. Activate the plugin in your WordPress Dashboard.

Use
---
1. Type the shortcode [ubcar-map] on a WordPress Page to have it display the UBCAR frontend there.
2. UBCAR has three special GET request fields that can be called from the UBCAR frontend page URL:
 - **point**: displays a single point (e.g., "?point=1")
 - **layer**: displays a single layer (e.g., "?layer=2")
 - **tour**: displays a single tour (e.g., "?tour=3")
3. UBCAR can also export its points as a KML file with the 'ubcar_download_kml' GET request field. This can be filtered by the 'ubcar_layers[]', 'ubcar_tours[]', and 'ubcar_search' fields.

Future Expansions
-----------------

- [ ] Create JSON exporting tool for UBCAR points
- [ ] Create better backend formatting (no more variable width tables?)
- [ ] Set different colors for KML layers on the map

Change Log
----------

1.0.0 - release

0.9.2 - minor bug fixes

0.9.1 - Revised security procedures, bug fixes

0.9.0 - Brought up to WordPress coding standards

0.8.4 - Improved frontend UI, security fixes, added fullwindow option

0.8.3 - Added Google Maps directions for UBCAR tours, added frontend buttons

0.8.2 - Corrected bug introduced by additional XSS protection.

0.8.1 - Added additional XSS protection.

0.8.0 - Initial release.