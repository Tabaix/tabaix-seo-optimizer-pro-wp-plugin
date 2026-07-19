<?php
if (!defined('ABSPATH')) exit;

/**
 * TABAIX_SEO_SEO_Translator — Enterprise Auto-Translator powered by ImageTight API
 * 
 * - Generates AI translations via Vercel Cloud when saving a post.
 * - Saves translations cleanly in post_meta (no bloated database tables).
 * - Creates virtual subdirectories (e.g., /ar/post-name) for perfect SEO.
 * - Injects hreflang tags for Google Indexing.
 * - Adds a frontend language switcher.
 */
class TABAIX_SEO_SEO_Translator
{
    private static $instance = null;

    // Supported Languages (Expanded Master List of 60 Major Languages)
    private $all_languages = [
        'af' => 'Afrikaans',
        'sq' => 'Albanian',
        'am' => 'Amharic',
        'ar' => 'Arabic',
        'hy' => 'Armenian',
        'bn' => 'Bengali',
        'bs' => 'Bosnian',
        'bg' => 'Bulgarian',
        'ca' => 'Catalan',
        'zh-CN' => 'Chinese (Simplified)',
        'zh-TW' => 'Chinese (Traditional)',
        'hr' => 'Croatian',
        'cs' => 'Czech',
        'da' => 'Danish',
        'nl' => 'Dutch',
        'en' => 'English',
        'et' => 'Estonian',
        'fi' => 'Finnish',
        'fr' => 'French',
        'ka' => 'Georgian',
        'de' => 'German',
        'el' => 'Greek',
        'gu' => 'Gujarati',
        'he' => 'Hebrew',
        'hi' => 'Hindi',
        'hu' => 'Hungarian',
        'is' => 'Icelandic',
        'id' => 'Indonesian',
        'it' => 'Italian',
        'ja' => 'Japanese',
        'kn' => 'Kannada',
        'kk' => 'Kazakh',
        'km' => 'Khmer',
        'ko' => 'Korean',
        'lv' => 'Latvian',
        'lt' => 'Lithuanian',
        'mk' => 'Macedonian',
        'ms' => 'Malay',
        'ml' => 'Malayalam',
        'mr' => 'Marathi',
        'mn' => 'Mongolian',
        'my' => 'Myanmar (Burmese)',
        'ne' => 'Nepali',
        'no' => 'Norwegian',
        'fa' => 'Persian',
        'pl' => 'Polish',
        'pt' => 'Portuguese',
        'pa' => 'Punjabi',
        'ro' => 'Romanian',
        'ru' => 'Russian',
        'sr' => 'Serbian',
        'si' => 'Sinhala',
        'sk' => 'Slovak',
        'sl' => 'Slovenian',
        'es' => 'Spanish',
        'sw' => 'Swahili',
        'sv' => 'Swedish',
        'ta' => 'Tamil',
        'te' => 'Telugu',
        'th' => 'Thai',
        'tr' => 'Turkish',
        'uk' => 'Ukrainian',
        'ur' => 'Urdu',
        'uz' => 'Uzbek',
        'vi' => 'Vietnamese',
        'cy' => 'Welsh'
    ];

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // Settings & Admin
        add_action('admin_menu', [$this, 'register_admin_page'], 99);
        add_action('save_post', [$this, 'handle_auto_translation'], 10, 3);

        // Manual translate meta box
        add_action('add_meta_boxes', [$this, 'add_translation_meta_box']);
        add_action('wp_ajax_tabaix_seo_translate_post', [$this, 'ajax_translate_post']);

        // Settings save handler
        add_action('admin_post_tabaix_seo_save_translations', [$this, 'handle_save_translations']);

        // Frontend Virtual Pages (Rewrite Rules)
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // Frontend Display
        add_filter('the_title', [$this, 'filter_title'], 10, 2);
        add_filter('the_content', [$this, 'filter_content']);
        add_action('wp_head', [$this, 'inject_hreflang_tags']);
        add_action('template_redirect', [$this, 'auto_redirect_by_browser_language']);
    }

    public function register_admin_page()
    {
        add_submenu_page(
            'tabaix-seo-dashboard',
            'Global Translations',
            '🌍 Translations',
            'manage_options',
            'tabaix-seo-translations',
            [$this, 'render_admin_page']
        );
    }

    public function handle_save_translations()
    {
        if (isset($_POST['tabaix_seo_translator_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tabaix_seo_translator_nonce'])), 'tabaix_seo_translator_save')) {
            if (!current_user_can('manage_options')) wp_die('Unauthorized');

            $settings = TABAIX_SEO_Settings::get();
            if (!is_array($settings)) $settings = [];
            
            $settings['translator_enabled']     = !empty($_POST['tabaix_seo_settings']['translator_enabled']) ? 1 : 0;
            $settings['show_language_switcher'] = !empty($_POST['tabaix_seo_settings']['show_language_switcher']) ? 1 : 0;
            $settings['auto_redirect_enabled']  = !empty($_POST['tabaix_seo_settings']['auto_redirect_enabled']) ? 1 : 0;
            update_option(TABAIX_SEO_Settings::OPTION_KEY, $settings);

            $translator_langs = isset($_POST['tabaix_seo_translator_langs']) ? array_map('sanitize_key', (array)$_POST['tabaix_seo_translator_langs']) : [];
            $free_langs = isset($_POST['tabaix_seo_free_langs']) ? array_map('sanitize_key', (array)$_POST['tabaix_seo_free_langs']) : [];
            
            update_option('tabaix_seo_translator_langs', $translator_langs);
            update_option('tabaix_seo_free_langs', $free_langs);

            wp_safe_redirect(admin_url('admin.php?page=tabaix-seo-translations&saved=true'));
            exit;
        }
    }

    public function render_admin_page()
    {
        if (isset($_GET['saved']) && $_GET['saved'] === 'true') {
            echo '<div class="notice notice-success is-dismissible"><p><strong>💾 Translation settings saved successfully!</strong></p></div>';
        }

        $seo_langs  = get_option('tabaix_seo_translator_langs', []);
        $free_langs = get_option('tabaix_seo_free_langs', []);
        if (!is_array($seo_langs))  $seo_langs  = [];
        if (!is_array($free_langs)) $free_langs = [];

        $translator_on = (int) TABAIX_SEO_Settings::get('translator_enabled',     1);
        $switcher_on   = (int) TABAIX_SEO_Settings::get('show_language_switcher', 1);
        $redirect_on   = (int) TABAIX_SEO_Settings::get('auto_redirect_enabled',  0);
        ?>
        <style>
        .tabaix-tg-grid{display:flex;flex-wrap:wrap;gap:16px;margin:14px 0 28px;}
        .tabaix-tg-card{background:#fff;border:1px solid #ddd;border-radius:10px;padding:18px 22px;min-width:240px;flex:1;box-shadow:0 1px 4px rgba(0,0,0,.06);}
        .tabaix-tg-card h4{margin:0 0 5px;font-size:14px;}
        .tabaix-tg-card p{margin:0 0 14px;color:#666;font-size:12px;line-height:1.5;}
        .tabaix-sw{position:relative;display:inline-block;width:46px;height:24px;vertical-align:middle;}
        .tabaix-sw input{opacity:0;width:0;height:0;}
        .tabaix-sl{position:absolute;cursor:pointer;inset:0;background:#ccc;border-radius:24px;transition:.3s;}
        .tabaix-sl:before{position:absolute;content:"";height:18px;width:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;}
        input:checked+.tabaix-sl{background:#2271b1;}
        input:checked+.tabaix-sl:before{transform:translateX(22px);}
        .tabaix-tg-row{display:flex;align-items:center;gap:10px;}
        .tabaix-badge{font-weight:700;font-size:12px;padding:2px 9px;border-radius:20px;background:#e0e0e0;color:#555;}
        .tabaix-badge.on{background:#d4edda;color:#155724;}
        </style>
        <div class="wrap">
        <h1>🌍 SEO Translations</h1>
        <p>Control how posts are translated and what visitors see. Toggle each feature on or off independently.</p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="tabaix_seo_save_translations">
        <?php wp_nonce_field('tabaix_seo_translator_save', 'tabaix_seo_translator_nonce'); ?>

        <h2 style="margin-bottom:6px;">⚙️ Feature Toggles</h2>
        <div class="tabaix-tg-grid">

          <div class="tabaix-tg-card">
            <h4>🔄 Auto-Translate Posts</h4>
            <p>Translate posts automatically on publish using your Gemini or OpenAI key. No external service required.</p>
            <div class="tabaix-tg-row">
              <label class="tabaix-sw">
                <input type="hidden"   name="tabaix_seo_settings[translator_enabled]" value="0">
                <input type="checkbox" name="tabaix_seo_settings[translator_enabled]" value="1" <?php checked($translator_on,1); ?>>
                <span class="tabaix-sl"></span>
              </label>
              <span class="tabaix-badge <?php echo $translator_on?'on':''; ?>"><?php echo $translator_on?'ON':'OFF'; ?></span>
            </div>
          </div>

          <div class="tabaix-tg-card">
            <h4>🌐 Show Language Switcher Button</h4>
            <p>Display a language selector dropdown on every post so readers can switch between available translations.</p>
            <div class="tabaix-tg-row">
              <label class="tabaix-sw">
                <input type="hidden"   name="tabaix_seo_settings[show_language_switcher]" value="0">
                <input type="checkbox" name="tabaix_seo_settings[show_language_switcher]" value="1" <?php checked($switcher_on,1); ?>>
                <span class="tabaix-sl"></span>
              </label>
              <span class="tabaix-badge <?php echo $switcher_on?'on':''; ?>"><?php echo $switcher_on?'ON':'OFF'; ?></span>
            </div>
          </div>

          <div class="tabaix-tg-card">
            <h4>↪️ Auto-Redirect by Browser Language</h4>
            <p>Automatically redirect visitors to the translated version based on their browser language (like Amazon). Off by default.</p>
            <div class="tabaix-tg-row">
              <label class="tabaix-sw">
                <input type="hidden"   name="tabaix_seo_settings[auto_redirect_enabled]" value="0">
                <input type="checkbox" name="tabaix_seo_settings[auto_redirect_enabled]" value="1" <?php checked($redirect_on,1); ?>>
                <span class="tabaix-sl"></span>
              </label>
              <span class="tabaix-badge <?php echo $redirect_on?'on':''; ?>"><?php echo $redirect_on?'ON':'OFF'; ?></span>
            </div>
          </div>

        </div>

        <hr style="margin:8px 0 24px;">
        <h2>✨ Premium SEO Languages</h2>
        <p>Translated by your AI key and <strong>saved permanently</strong> — creates real URLs like <code>/ar/your-post/</code> that Google can index and rank.</p>
        <div style="display:flex;flex-wrap:wrap;gap:4px 0;">
        <?php foreach ($this->all_languages as $code => $name) :
            $ch = in_array($code, $seo_langs) ? 'checked' : ''; ?>
          <label style="display:inline-block;width:210px;margin-bottom:8px;">
            <input type="checkbox" name="tabaix_seo_translator_langs[]" value="<?php echo esc_attr($code); ?>" <?php echo esc_attr($ch); ?>>
            <?php echo esc_html($name); ?>
          </label>
        <?php endforeach; ?>
        </div>

        <hr style="margin:24px 0;">
        <h2>🆓 Free Live Translations</h2>
        <p>Shown in the dropdown but translated <strong>live in the visitor's browser</strong> via Google Translate. Not indexed by Google.</p>
        <div style="display:flex;flex-wrap:wrap;gap:4px 0;">
        <?php foreach ($this->all_languages as $code => $name) :
            $ch = in_array($code, $free_langs) ? 'checked' : ''; ?>
          <label style="display:inline-block;width:210px;margin-bottom:8px;">
            <input type="checkbox" name="tabaix_seo_free_langs[]" value="<?php echo esc_attr($code); ?>" <?php echo esc_attr($ch); ?>>
            <?php echo esc_html($name); ?>
          </label>
        <?php endforeach; ?>
        </div>

        <br><br>
        <?php submit_button('💾 Save Translation Settings'); ?>
        </form>
        </div>
        <?php
    }

    /* ───────────────────────────────────────────────
       Translation Trigger (Save Post - Global)
    ─────────────────────────────────────────────── */
    /**
     * Translate a single post into all configured premium languages using Gemini/OpenAI.
     * Called automatically on save_post, or manually via AJAX.
     *
     * @param int     $post_id
     * @param WP_Post $post
     * @param bool    $update
     * @param bool    $force   Set true to re-translate even if a translation already exists.
     */
    public function handle_auto_translation($post_id, $post, $update, $force = false)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        if ($post->post_status !== 'publish') return;

        // Respect the "Auto-Translate Posts" toggle (manual force always bypasses)
        if (!$force && !TABAIX_SEO_Settings::get('translator_enabled', 1)) return;

        $langs_to_translate = get_option('tabaix_seo_translator_langs', []);
        if (!is_array($langs_to_translate)) {
            $langs_to_translate = maybe_unserialize($langs_to_translate);
        }
        if (empty($langs_to_translate) || !is_array($langs_to_translate)) return;


        foreach ($langs_to_translate as $lang_code) {
            $lang_name = $this->all_languages[$lang_code] ?? $lang_code;

            // Skip if already translated (unless force-retranslate)
            if (!$force && get_post_meta($post_id, '_tabaix_seo_translation_' . $lang_code . '_title', true)) {
                continue;
            }

            // ── Translate Title ──────────────────────────────────────────────
            $title_prompt = "Translate the following text into {$lang_name}. "
                . "Return ONLY the translated text, nothing else. No explanations, no quotes.\n\n"
                . $post->post_title;

            $translated_title = TABAIX_SEO_API::generate($title_prompt, '', [
                'max_tokens' => 200,
                'temperature' => 0.3,
            ]);

            if (!is_wp_error($translated_title) && !empty(trim($translated_title))) {
                update_post_meta(
                    $post_id,
                    '_tabaix_seo_translation_' . $lang_code . '_title',
                    sanitize_text_field(trim($translated_title))
                );
            }

            // ── Translate Content ────────────────────────────────────────────
            // Strip shortcodes and blocks from the raw content before sending;
            // keep HTML so the translated content renders correctly.
            $raw_content = $post->post_content;

            $content_prompt = "Translate the following WordPress post content into {$lang_name}. "
                . "Preserve all HTML tags exactly as they are. "
                . "Return ONLY the translated HTML, nothing else.\n\n"
                . $raw_content;

            $translated_content = TABAIX_SEO_API::generate($content_prompt, '', [
                'max_tokens' => 8192,
                'temperature' => 0.3,
            ]);

            if (!is_wp_error($translated_content) && !empty(trim($translated_content))) {
                update_post_meta(
                    $post_id,
                    '_tabaix_seo_translation_' . $lang_code . '_content',
                    wp_kses_post(trim($translated_content))
                );
            }
        }
    }

    /* ───────────────────────────────────────────────
       Manual Translate Meta Box (Post Editor)
    ─────────────────────────────────────────────── */

    public function add_translation_meta_box()
    {
        add_meta_box(
            'tabaix_seo_translate_box',
            '🌍 SEO Translations',
            [$this, 'render_translation_meta_box'],
            ['post', 'page'],
            'side',
            'default'
        );
    }

    public function render_translation_meta_box($post)
    {
        $langs = get_option('tabaix_seo_translator_langs', []);
        if (!is_array($langs)) $langs = [];

        if (empty($langs)) {
            echo '<p style="color:#666;">No languages selected. Go to <a href="' . esc_url( admin_url('admin.php?page=tabaix-seo-translations') ) . '">🌍 Translations</a> to configure.</p>';
            return;
        }

        echo '<p style="margin:0 0 8px;"><strong>Configured languages:</strong></p>';
        echo '<ul style="margin:0 0 10px; padding-left:16px;">';
        foreach ($langs as $code) {
            $has = (bool) get_post_meta($post->ID, '_tabaix_seo_translation_' . $code . '_title', true);
            $label = esc_html($this->all_languages[$code] ?? $code);
            $icon  = $has ? '✅' : '⏳';
            echo '<li>' . esc_html( $icon ) . ' ' . esc_html( $label ) . '</li>';
        }
        echo '</ul>';

        wp_nonce_field('tabaix_seo_translate_nonce', 'tabaix_seo_translate_nonce');
        echo '<button type="button" id="tabaix-seo-translate-btn" class="button button-primary" style="width:100%;">';
        echo '🔄 Translate / Re-translate Now</button>';
        echo '<p id="tabaix-seo-translate-msg" style="margin-top:8px; color:#2271b1;"></p>';
        ?>
        <script>
        document.getElementById('tabaix-seo-translate-btn').addEventListener('click', function() {
            var btn = this;
            var msg = document.getElementById('tabaix-seo-translate-msg');
            btn.disabled = true;
            btn.textContent = '⏳ Translating…';
            msg.textContent = '';
            fetch(ajaxurl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'tabaix_seo_translate_post',
                    post_id: <?php echo (int) $post->ID; ?>,
                    nonce: document.getElementById('tabaix_seo_translate_nonce').value
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    msg.style.color = '#00a32a';
                    msg.textContent = '✅ ' + data.data.message;
                } else {
                    msg.style.color = '#d63638';
                    msg.textContent = '❌ ' + (data.data || 'Translation failed.');
                }
                btn.disabled = false;
                btn.textContent = '🔄 Translate / Re-translate Now';
            })
            .catch(function() {
                msg.style.color = '#d63638';
                msg.textContent = '❌ Network error. Please try again.';
                btn.disabled = false;
                btn.textContent = '🔄 Translate / Re-translate Now';
            });
        });
        </script>
        <?php
    }

    public function ajax_translate_post()
    {
        check_ajax_referer('tabaix_seo_translate_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized.');
        }

        $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        if (!$post_id) {
            wp_send_json_error('Invalid post ID.');
        }

        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error('Post not found.');
        }

        // Force re-translate all languages
        $this->handle_auto_translation($post_id, $post, true, true);

        // Count how many translations now exist
        $langs = get_option('tabaix_seo_translator_langs', []);
        if (!is_array($langs)) $langs = [];
        $done = 0;
        foreach ($langs as $code) {
            if (get_post_meta($post_id, '_tabaix_seo_translation_' . $code . '_title', true)) {
                $done++;
            }
        }

        wp_send_json_success([
            'message' => "Translated into {$done} of " . count($langs) . " language(s) successfully.",
        ]);
    }

    /* ───────────────────────────────────────────────
       SEO URL Routing (e.g., site.com/ar/post-name)
    ─────────────────────────────────────────────── */
    public function add_rewrite_rules()
    {
        $lang_codes = implode('|', array_keys($this->all_languages));
        // Add rule for /lang/post-name
        add_rewrite_rule(
            '^(' . $lang_codes . ')/([^/]+)/?$',
            'index.php?name=$matches[2]&tabaix_seo_lang=$matches[1]',
            'top'
        );
    }

    public function add_query_vars($vars)
    {
        $vars[] = 'tabaix_seo_lang';
        return $vars;
    }

    /* ───────────────────────────────────────────────
       Frontend Filters (Replacing English with Translation)
    ─────────────────────────────────────────────── */
    public function filter_title($title, $post_id = null)
    {
        if (!is_singular() || !$post_id) return $title;
        $lang = get_query_var('tabaix_seo_lang');
        if (!$lang) return $title;

        $translated_title = get_post_meta($post_id, '_tabaix_seo_translation_' . $lang . '_title', true);
        return $translated_title ? $translated_title : $title;
    }

    public function filter_content($content)
    {
        if (!is_singular() || !in_the_loop() || !is_main_query()) return $content;

        // 1. Check if we are viewing a translated SEO URL
        $lang = get_query_var('tabaix_seo_lang');
        if ($lang) {
            $translated_content = get_post_meta(get_the_ID(), '_tabaix_seo_translation_' . $lang . '_content', true);
            if ($translated_content) {
                $content = $translated_content;
            } else {
                $content = "<p><em>This translation is pending. Showing original content.</em></p>" . $content;
            }
        }

        // 2. Show Language Switcher only when toggle is ON
        if (!TABAIX_SEO_Settings::get('show_language_switcher', 1)) {
            return $content;
        }

        // Wrapped in try/catch to prevent fatal errors if wp_options returns
        // a non-array value for the language settings (e.g. corrupted serialisation).
        try {
            $switcher = $this->get_language_switcher();
        } catch ( \Throwable $e ) {
            if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
                error_log( 'TABAIX SEO Translator - get_language_switcher() error: ' . $e->getMessage() );
            }
            $switcher = '';
        }

        return $switcher . $content . $this->get_google_translate_script();
    }

    public function enqueue_assets()
    {
        if (!is_singular()) {
            return;
        }

        wp_enqueue_style(
            'tabaix-seo-seo-translator-style',
            plugins_url('../assets/css/tabaix-seo-seo-translator.css', __FILE__),
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'tabaix-seo-seo-translator',
            plugins_url('../assets/js/tabaix-seo-seo-translator.js', __FILE__),
            [],
            '1.0.0',
            true
        );
    }

    private function get_google_translate_script()
    {
        return '<div id="google_translate_element" class="tabaix-seo-google-translate-element"></div>';
    }

    public function inject_hreflang_tags()
    {
        if (!is_singular()) return;
        $post_id = get_the_ID();
        $original_url = get_permalink($post_id);
        
        // Original tag
        echo '<link rel="alternate" hreflang="x-default" href="' . esc_url($original_url) . '" />' . "\n";
        echo '<link rel="alternate" hreflang="en" href="' . esc_url($original_url) . '" />' . "\n";

        // Tags for each available translation
        foreach ($this->all_languages as $code => $name) {
            if (get_post_meta($post_id, '_tabaix_seo_translation_' . $code . '_title', true)) {
                $lang_url = home_url('/' . $code . '/' . basename($original_url) . '/');
                echo '<link rel="alternate" hreflang="' . esc_attr($code) . '" href="' . esc_url($lang_url) . '" />' . "\n";
            }
        }
    }

    /* ───────────────────────────────────────────────
       Auto-Detect Browser Language & Redirect (Amazon Style)
    ─────────────────────────────────────────────── */
    public function auto_redirect_by_browser_language()
    {
        // Only run if the "Auto-Redirect by Browser Language" toggle is ON
        if (!TABAIX_SEO_Settings::get('auto_redirect_enabled', 0)) {
            return;
        }

        if (!is_singular()) return;

        // If we are already on a translated page, do not redirect!
        if (get_query_var('tabaix_seo_lang')) {
            return;
        }

        // Only redirect once per browser session so users can switch back to English
        if (!empty($_COOKIE['tabaix_seo_lang_redirected'])) return;

        $post_id = get_the_ID();
        if (!$post_id) return;

            $browser_langs = explode(',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '' ) ) );

        foreach ($browser_langs as $lang_string) {
            $lang_code = substr(trim($lang_string), 0, 2); // "en-US" -> "en", "ar-SA" -> "ar"

            // If the browser language is English, stay on the default page
            if ($lang_code === 'en') {
                setcookie('tabaix_seo_lang_redirected', '1', time() + 86400, '/');
                return;
            }

            // Check if we support this language and if this post has been translated
            if (array_key_exists($lang_code, $this->all_languages)) {
                if (get_post_meta($post_id, '_tabaix_seo_translation_' . $lang_code . '_title', true)) {
                    setcookie('tabaix_seo_lang_redirected', '1', time() + 86400, '/');
                    $original_url = get_permalink($post_id);
                    $lang_url = home_url('/' . $lang_code . '/' . basename(untrailingslashit($original_url)) . '/');
                    wp_safe_redirect($lang_url, 302);
                    exit;
                }
            }
        }
    }

    private function get_language_switcher()
    {
        $post_id = get_the_ID();
        $original_url = get_permalink($post_id);
        $current_lang = get_query_var('tabaix_seo_lang') ?: 'en';

        $html = '<div class="tabaix-seo-language-switcher">';
        $html .= '<strong style="margin-right: 10px;">🌍 Read in:</strong>';
        
        // Custom dropdown logic
        $html .= '<select class="tabaix-seo-language-switcher-select">';
        
        $en_selected = ($current_lang === 'en') ? 'selected' : '';
        $html .= '<option value="' . esc_url($original_url) . '" ' . $en_selected . ' data-lang-code="en">🇬🇧 English (Original)</option>';

        $seo_langs  = get_option('tabaix_seo_translator_langs', []);
        if (!is_array($seo_langs)) {
            $seo_langs = maybe_unserialize($seo_langs);
        }
        if (!is_array($seo_langs)) {
            $seo_langs = !empty($seo_langs) ? array_map('trim', explode(',', (string) $seo_langs)) : [];
        }
        $seo_langs = array_values(array_filter((array) $seo_langs, 'is_string'));

        if (!empty($seo_langs)) {
            $html .= '<optgroup label="Premium SEO Translations">';
            foreach ($this->all_languages as $code => $name) {
                if (in_array($code, $seo_langs, true) && get_post_meta($post_id, '_tabaix_seo_translation_' . $code . '_title', true)) {
                    $lang_url = home_url('/' . $code . '/' . basename(untrailingslashit($original_url)) . '/');
                    $selected = ($current_lang === $code) ? 'selected' : '';
                    $html .= '<option value="' . esc_url($lang_url) . '" ' . $selected . ' data-lang-code="' . esc_attr($code) . '">✨ ' . esc_html($name) . '</option>';
                }
            }
            $html .= '</optgroup>';
        }

        // Free Fallback Languages (Google Translate)
        $free_langs = get_option('tabaix_seo_free_langs', []);
        if (!is_array($free_langs)) {
            $free_langs = maybe_unserialize($free_langs);
        }
        if (!is_array($free_langs)) {
            $free_langs = !empty($free_langs) ? array_map('trim', explode(',', (string) $free_langs)) : array();
        }

        if (!empty($free_langs)) {
            $html .= '<optgroup label="Live Translations (Free)">';
            foreach ($free_langs as $code) {
                // Do not display in free list if it was already rendered in the Premium list
                if (in_array($code, $seo_langs, true) && get_post_meta($post_id, '_tabaix_seo_translation_' . $code . '_title', true)) {
                    continue; 
                }
                
                if (isset($this->all_languages[$code])) {
                    $html .= '<option value="' . esc_attr($code) . '" data-lang-code="' . esc_attr($code) . '">' . esc_html($this->all_languages[$code]) . '</option>';
                }
            }
            $html .= '</optgroup>';
        }

        $html .= '</select></div>';
        return $html;
    }
}

// Initialize
TABAIX_SEO_SEO_Translator::get_instance();
