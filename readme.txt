=== GEO AI Woo ===
Contributors: madeburo
Tags: ai, seo, woocommerce, llms.txt, chatgpt, claude, gemini, perplexity
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generative Engine Optimization for WordPress & WooCommerce. Optimize your site for AI search engines.

== Description ==

GEO AI Woo generates `/llms.txt` and `/llms-full.txt` files that help AI search engines understand your content. It supports ChatGPT, Claude, Gemini, Perplexity, YandexGPT, GigaChat, and more.

**Features:**

* Automatic llms.txt generation
* AI meta box for posts, pages, and products
* Per-bot crawler permissions (allow/disallow)
* WooCommerce integration with product optimization
* Enhanced product schema for AI readability
* Configurable cache and regeneration
* Multilingual support (7 languages)

**Supported AI Crawlers:**

* GPTBot (OpenAI / ChatGPT)
* ClaudeBot (Anthropic / Claude)
* Google-Extended (Google / Gemini)
* PerplexityBot (Perplexity AI)
* YandexBot (Yandex / YandexGPT)
* SputnikBot (Sber / GigaChat)
* Bytespider (ByteDance / Douyin)
* Baiduspider (Baidu / ERNIE)

== Installation ==

1. Upload the `geo-ai-woo` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > GEO AI Woo to configure

The plugin works out of the box with sensible defaults.

== Frequently Asked Questions ==

= What is llms.txt? =

llms.txt is a proposed standard that provides AI systems with a structured overview of your site content, similar to how robots.txt works for search engine crawlers.

= Does this plugin require WooCommerce? =

No. WooCommerce integration is optional. The plugin works with standard WordPress posts and pages. WooCommerce features activate automatically when WooCommerce is installed.

= How often is llms.txt regenerated? =

By default, it regenerates daily. You can change this to immediate (on every post save), hourly, or weekly in Settings > GEO AI Woo > Cache Settings.

= Can I exclude specific content from llms.txt? =

Yes. Each post, page, and product has a "GEO AI Woo" meta box where you can check "Exclude from AI indexing".

== Screenshots ==

1. Settings page with bot rules configuration
2. AI meta box in post editor
3. WooCommerce product data panel

== Changelog ==

= 0.1.0 =
* Initial release
* llms.txt and llms-full.txt generation
* AI meta box for posts, pages, and products
* Bot rules configuration (8 AI crawlers)
* WooCommerce basic integration
* Cache management with configurable frequency
* Multilingual support (7 languages)

== Upgrade Notice ==

= 0.1.0 =
Initial release.
