=== Tabaix SEO Optimizer Pro ===
Contributors: tabaix
Tags: seo, image compression, internal linking, ai, content generation
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A comprehensive optimization plugin featuring local in-browser image compression (WebP/AVIF), automated AI internal linking, and content generation.

== Description ==

**Tabaix SEO Suite & ImageTight** is an all-in-one performance and SEO toolkit designed to fix slow load times and optimize your site architecture.

= Core Features =

**Image Optimizer (ImageTight)**
* Auto-Compress on Upload — Automatically convert large images securely.
* Next-Gen Formats — Deliver WebP or AVIF to fix Google PageSpeed warnings.
* HEIC Support — Upload iPhone photos (HEIC) without errors.
* Media Library Scanner — Find and replace heavy images with a single click.
* Edge-Powered — Offload heavy compression to external cloud processing to save your server CPU.

**AI Content & SEO Suite**
* Internal Link Scanner — Build intelligent site silos and find semantic link opportunities instantly.
* Content Generation — Draft structured blog outlines and meta descriptions.
* Dual AI Provider Support — Bring your own key for Google AI Studio (Gemini) or OpenAI.

= Usage =

1. Upload the plugin and activate it.
2. Navigate to **Tabaix Options** in your sidebar.
3. Under the **Image Optimizer** tab, configure your quality settings and format (WebP/AVIF).
4. Run the Media Library Scanner to find heavy legacy images.
5. Provide your optional Google Gemini API key to unlock the Internal Link Scanner AI.

== Installation ==

1. Upload the `tabaix-seo-optimizer` folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress **Plugins** menu.
3. Configure your API options under the settings menu.

== Frequently Asked Questions ==

= Do I need a paid subscription to use this? =
No. The core plugin allows you to use your own free Google AI Studio key for text generation and internal linking analysis. 

= Does the image compression slow down my server? =
No. Image compression is offloaded to the Vercel Edge Network, meaning your WordPress hosting CPU is completely protected from heavy image processing tasks.

= What image formats are supported? =
WebP, AVIF, JPEG, and PNG. We also support native decoding of Apple HEIC files.

= How does the internal link scanner work? =
It analyzes your existing posts and uses advanced AI to build semantic relationships, suggesting exact anchor text and URLs to naturally interlink your content for better SEO structure.

== Screenshots ==

1. Dashboard with quick actions and site overview
2. Image Optimizer — Media Library scanner finding heavy images
3. Internal Linking — Semantic post suggestions
4. Content Generator — SEO meta drafting
5. Settings — API configuration

== External Services ==

This plugin connects to the following external services:

= ImageTight Image Compression API =
* **What it does:** Compresses and converts images (WebP/AVIF) from your WordPress Media Library.
* **What data is sent:** Image files from your media library, sent only when you manually trigger compression or enable auto-compress on upload.
* **API endpoint:** https://imagetight-api.vercel.app
* **Terms of Service:** https://imagetight.com/terms
* **Privacy Policy:** https://imagetight.com/privacy

= Google Gemini API =
* **What it does:** Powers AI-assisted text generation, content suggestions, SEO meta drafting, alt text generation, internal link suggestions, auto-translation, and chatbot responses.
* **What data is sent:** Post content, titles, and image data (for vision features) — sent only when you explicitly trigger an AI feature. Requires your own Google AI Studio API key.
* **API endpoint:** https://generativelanguage.googleapis.com
* **Terms of Service:** https://ai.google.dev/terms
* **Privacy Policy:** https://policies.google.com/privacy

= OpenAI API =
* **What it does:** Alternative AI provider for the same text generation, SEO, and image generation features above.
* **What data is sent:** Post content, titles, and prompts — sent only when you explicitly trigger an AI feature using your OpenAI key. Requires your own OpenAI API key.
* **API endpoint:** https://api.openai.com
* **Terms of Service:** https://openai.com/terms
* **Privacy Policy:** https://openai.com/privacy

NOTE: No data is sent to any external service automatically without user action, except for image compression if "Auto-Compress on Upload" is enabled in your settings.

== Source Code ==

The full source code for this plugin, including the uncompiled source for all build files, is available at:
https://github.com/Tabaix/tabaix-seo-optimizer-pro

The compiled files in `includes/toc-build/` and `includes/pros-cons-build/` are generated from the source in the `/src/` directory of the repository using standard WordPress block build tools (`@wordpress/scripts`).

To rebuild the assets:
1. Clone the repository
2. Run `npm install`
3. Run `npm run build`

== Changelog ==

= 2.0.0 =
* Initial public release as Tabaix All-in-One SEO & Optimizer.
* Integrated ImageTight web API for edge compression.
* Added Internal Link AI Scanner.
* Added support for Google Gemini via BYOK (Bring Your Own Key).

== Upgrade Notice ==

= 2.0.0 =
Initial release. No upgrade needed.
