<?php
if (!defined('ABSPATH'))
    exit;

class TABAIX_SEO_Admin
{

    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('admin_menu', [$this, 'register_menus']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_menus()
    {
        add_menu_page(
            __('Tabaix SEO Pro', 'tabaix-seo-optimizer-pro'),
            __('Tabaix SEO', 'tabaix-seo-optimizer-pro'),
            'edit_posts',
            'tabaix-seo-dashboard',
            [$this, 'page_dashboard'],
            'dashicons-superhero',
            30
        );
        add_submenu_page('tabaix-seo-dashboard', 'Dashboard', 'Dashboard', 'edit_posts', 'tabaix-seo-dashboard', [$this, 'page_dashboard']);
        add_submenu_page('tabaix-seo-dashboard', 'Content Generator', 'Content Generator', 'edit_posts', 'tabaix-seo-content', [$this, 'page_content']);
        add_submenu_page('tabaix-seo-dashboard', 'SEO & Optimizer', 'SEO & Optimizer', 'edit_posts', 'tabaix-seo-seo', [$this, 'page_seo']);
        add_submenu_page('tabaix-seo-dashboard', 'Image AI', 'Image AI', 'edit_posts', 'tabaix-seo-images', [$this, 'page_images']);
        add_submenu_page('tabaix-seo-dashboard', 'Comment Moderator', 'Comment Moderator', 'moderate_comments', 'tabaix-seo-moderation', [$this, 'page_moderation']);
        add_submenu_page('tabaix-seo-dashboard', 'Analytics & Reports', 'Analytics & Reports', 'edit_posts', 'tabaix-seo-analytics', [$this, 'page_analytics']);

        add_submenu_page('tabaix-seo-dashboard', 'Internal Links', 'Internal Links', 'edit_posts', 'tabaix-seo-internal-links', [$this, 'page_internal_links']);
        add_submenu_page('tabaix-seo-dashboard', 'Settings', 'Settings', 'manage_options', 'tabaix-seo-settings', [$this, 'page_settings']);
    }

    public function enqueue_assets($hook)
    {
        if (strpos($hook, 'tabaix-seo-') === false)
            return;

        wp_enqueue_style('tabaix-seo-admin', TABAIX_SEO_PLUGIN_URL . 'assets/css/admin.css', [], TABAIX_SEO_VERSION);
        wp_enqueue_script('tabaix-seo-admin', TABAIX_SEO_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], TABAIX_SEO_VERSION, true);
        wp_localize_script('tabaix-seo-admin', 'uamAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tabaix_seo_admin_nonce'),
            'provider' => TABAIX_SEO_Settings::get('provider', 'gemini'),
            'pluginUrl' => TABAIX_SEO_PLUGIN_URL,
            'chatbotEnabled' => (int) TABAIX_SEO_Settings::get('chatbot_enabled', 0),
            'currentPage' => sanitize_text_field($_GET['page'] ?? 'tabaix-seo-dashboard'),
        ]);
    }

    // ─── Dashboard ───────────────────────────────────────────────────────────

    public function page_dashboard()
    {
        $provider = TABAIX_SEO_Settings::get('provider', 'gemini');
        $has_gemini = !empty(TABAIX_SEO_Settings::get('gemini_api_key'));
        $has_openai = !empty(TABAIX_SEO_Settings::get('openai_api_key'));
        ?>
        <div class="tabaix-seo-wrap">
            <?php $this->render_header(); ?>
            <div class="tabaix-seo-dashboard-grid">
                <!-- Status Card -->
                <div class="tabaix-seo-card tabaix-seo-status-card">
                    <div class="tabaix-seo-card-header">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <h3>Plugin Status</h3>
                    </div>
                    <div class="tabaix-seo-status-list">
                        <div class="tabaix-seo-status-item <?php echo $has_gemini ? 'ok' : 'warn'; ?>">
                            <span class="tabaix-seo-status-dot"></span>
                            <span>Gemini API
                                <?php echo $has_gemini ? 'Configured' : 'Not Configured'; ?>
                            </span>
                        </div>
                        <div class="tabaix-seo-status-item <?php echo $has_openai ? 'ok' : 'warn'; ?>">
                            <span class="tabaix-seo-status-dot"></span>
                            <span>OpenAI API
                                <?php echo $has_openai ? 'Configured' : 'Not Configured'; ?>
                            </span>
                        </div>

                        <div class="tabaix-seo-status-item <?php echo TABAIX_SEO_Settings::get('moderation_auto') ? 'ok' : 'off'; ?>">
                            <span class="tabaix-seo-status-dot"></span>
                            <span>Auto Moderation
                                <?php echo TABAIX_SEO_Settings::get('moderation_auto') ? 'Active' : 'Disabled'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="tabaix-seo-card">
                    <div class="tabaix-seo-card-header">
                        <span class="dashicons dashicons-bolt"></span>
                        <h3>Quick Actions</h3>
                    </div>
                    <div class="tabaix-seo-quick-actions">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=tabaix-seo-content')); ?>" class="tabaix-seo-action-btn">
                            <span class="dashicons dashicons-edit"></span> Generate Post
                        </a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=tabaix-seo-seo')); ?>" class="tabaix-seo-action-btn">
                            <span class="dashicons dashicons-chart-bar"></span> SEO Analysis
                        </a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=tabaix-seo-images')); ?>" class="tabaix-seo-action-btn">
                            <span class="dashicons dashicons-format-image"></span> Generate Image
                        </a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=tabaix-seo-analytics')); ?>" class="tabaix-seo-action-btn">
                            <span class="dashicons dashicons-analytics"></span> View Report
                        </a>
                    </div>
                </div>

                <!-- Stats -->
                <?php $stats = TABAIX_SEO_Analytics::get_native_analytics(); ?>
                <div class="tabaix-seo-card tabaix-seo-stats-card">
                    <div class="tabaix-seo-card-header">
                        <span class="dashicons dashicons-chart-line"></span>
                        <h3>Site Overview</h3>
                    </div>
                    <div class="tabaix-seo-stats-grid">
                        <div class="tabaix-seo-stat">
                            <span class="tabaix-seo-stat-number">
                                <?php echo esc_html(wp_count_posts()->publish); ?>
                            </span>
                            <span class="tabaix-seo-stat-label">Published Posts</span>
                        </div>
                        <div class="tabaix-seo-stat">
                            <span class="tabaix-seo-stat-number">
                                <?php echo esc_html(wp_count_comments()->approved); ?>
                            </span>
                            <span class="tabaix-seo-stat-label">Comments</span>
                        </div>
                        <div class="tabaix-seo-stat">
                            <span class="tabaix-seo-stat-number">
                                <?php echo esc_html(wp_count_comments()->moderated); ?>
                            </span>
                            <span class="tabaix-seo-stat-label">Pending Review</span>
                        </div>
                        <div class="tabaix-seo-stat">
                            <span class="tabaix-seo-stat-number">
                                <?php echo esc_html(count_users()['total_users']); ?>
                            </span>
                            <span class="tabaix-seo-stat-label">Total Users</span>
                        </div>
                    </div>
                </div>

                <!-- Features Overview -->
                <div class="tabaix-seo-card tabaix-seo-features-card">
                    <div class="tabaix-seo-card-header">
                        <span class="dashicons dashicons-star-filled"></span>
                        <h3>Features</h3>
                    </div>
                    <div class="tabaix-seo-features-list">
                        <?php
                        $features = [
                            ['icon' => 'dashicons-edit', 'label' => 'Blog Post Drafts', 'page' => 'tabaix-seo-content'],
                            ['icon' => 'dashicons-cart', 'label' => 'Product Descriptions', 'page' => 'tabaix-seo-content'],
                            ['icon' => 'dashicons-search', 'label' => 'SEO & Meta Generation', 'page' => 'tabaix-seo-seo'],
                            ['icon' => 'dashicons-share', 'label' => 'Social Media Posts', 'page' => 'tabaix-seo-content'],
                            ['icon' => 'dashicons-email-alt', 'label' => 'Email Marketing Copy', 'page' => 'tabaix-seo-content'],
                            ['icon' => 'dashicons-format-image', 'label' => 'AI Image Generation', 'page' => 'tabaix-seo-images'],
                            ['icon' => 'dashicons-visibility', 'label' => 'Readability Analysis', 'page' => 'tabaix-seo-seo'],
                            ['icon' => 'dashicons-shield', 'label' => 'Comment Moderation', 'page' => 'tabaix-seo-moderation'],

                            ['icon' => 'dashicons-admin-links', 'label' => 'Internal Links', 'page' => 'tabaix-seo-internal-links'],
                            ['icon' => 'dashicons-analytics', 'label' => 'Analytics &amp; Reports', 'page' => 'tabaix-seo-analytics'],
                        ];
                        foreach ($features as $f):
                            ?>
                            <a href="<?php echo esc_url(admin_url("admin.php?page={$f['page']}")); ?>" class="tabaix-seo-feature-item">
                                <span class="dashicons <?php echo esc_attr($f['icon']); ?>"></span>
                                <span>
                                    <?php echo esc_html($f['label']); ?>
                                </span>
                                <span class="dashicons dashicons-arrow-right-alt2 tabaix-seo-ml-auto"></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    // ─── Content Generator Page ───────────────────────────────────────────────

    public function page_content()
    {
        ?>
        <div class="tabaix-seo-wrap">
            <?php $this->render_header(); ?>
            <div class="tabaix-seo-tabs-nav">
                <button class="tabaix-seo-tab active" data-tab="blog-post">📝 Blog Post</button>
                <button class="tabaix-seo-tab" data-tab="product">🛒 Product</button>
                <button class="tabaix-seo-tab" data-tab="social">📱 Social Media</button>
                <button class="tabaix-seo-tab" data-tab="email">✉️ Email</button>
            </div>

            <!-- Blog Post Tab -->
            <div class="tabaix-seo-tab-pane active" id="tab-blog-post">
                <div class="tabaix-seo-two-col">
                    <div class="tabaix-seo-panel">
                        <h3>Blog Post Generator</h3>
                        <div class="tabaix-seo-field-group">
                            <label>Topic / Title *</label>
                            <input type="text" id="bp-topic" placeholder="e.g. 10 Ways to Improve Sleep Quality"
                                class="tabaix-seo-input">
                        </div>
                        <div class="tabaix-seo-field-group">
                            <label>Target Keywords</label>
                            <input type="text" id="bp-keywords" placeholder="sleep tips, better sleep, insomnia"
                                class="tabaix-seo-input">
                        </div>
                        <div class="tabaix-seo-field-group">
                            <label>Word Count</label>
                            <select id="bp-wordcount" class="tabaix-seo-select">
                                <option value="500">Short (~500 words)</option>
                                <option value="800" selected>Medium (~800 words)</option>
                                <option value="1200">Long (~1200 words)</option>
                                <option value="2000">Comprehensive (~2000 words)</option>
                            </select>
                        </div>
                        <div class="tabaix-seo-btn-group">
                            <button class="tabaix-seo-btn tabaix-seo-btn-secondary" id="btn-outline">Generate Outline</button>
                            <button class="tabaix-seo-btn tabaix-seo-btn-secondary" id="btn-intro">Generate Intro</button>
                            <button class="tabaix-seo-btn tabaix-seo-btn-secondary" id="btn-conclusion">Generate Conclusion</button>
                            <button class="tabaix-seo-btn tabaix-seo-btn-primary" id="btn-full-post">✨ Generate Full Post</button>
                        </div>
                    </div>
                    <div class="tabaix-seo-panel">
                        <div class="tabaix-seo-result-header">
                            <h3>Generated Content</h3>
                            <button class="tabaix-seo-btn tabaix-seo-btn-ghost" id="btn-copy-content">📋 Copy</button>
                        </div>
                        <div class="tabaix-seo-result-area" id="content-result">
                            <div class="tabaix-seo-placeholder">Your generated content will appear here...</div>
                        </div>
                    </div>
                </div>

                <!-- Inline Image Generator for Blog Post -->
                <div class="tabaix-seo-panel" style="margin-top:20px;">
                    <h3>🎨 Generate Blog Image</h3>
                    <p class="tabaix-seo-hint">Generate an AI-powered image for your blog post. Use a custom prompt or
                        auto-generate one from the post title above.</p>

                    <div class="tabaix-seo-two-col">
                        <div>
                            <div class="tabaix-seo-field-group">
                                <label>Image Prompt</label>
                                <textarea id="bp-img-prompt" rows="3"
                                    placeholder="Describe the image you want... Leave blank to auto-generate from post title."
                                    class="tabaix-seo-textarea"></textarea>
                            </div>
                            <div class="tabaix-seo-field-group">
                                <label>Style</label>
                                <select id="bp-img-style" class="tabaix-seo-select">
                                    <option value="photorealistic">📷 Photorealistic</option>
                                    <option value="illustration">🎨 Illustration</option>
                                    <option value="digital-art">🖥️ Digital Art</option>
                                    <option value="minimalist">✨ Minimalist</option>
                                    <option value="watercolor">🎭 Watercolor</option>
                                </select>
                            </div>
                            <div class="tabaix-seo-field-group">
                                <label>Aspect Ratio</label>
                                <select id="bp-img-ratio" class="tabaix-seo-select">
                                    <option value="16:9">🖥️ 16:9 (Landscape)</option>
                                    <option value="1:1">◻️ 1:1 (Square)</option>
                                    <option value="4:3">📷 4:3 (Standard)</option>
                                    <option value="3:4">📱 3:4 (Portrait)</option>
                                    <option value="9:16">📱 9:16 (Tall)</option>
                                </select>
                            </div>
                            <div class="tabaix-seo-field-group tabaix-seo-checkbox-group">
                                <label>
                                    <input type="checkbox" id="bp-img-save-library" checked> Save to Media Library
                                </label>
                            </div>
                            <div class="tabaix-seo-btn-group">
                                <button class="tabaix-seo-btn tabaix-seo-btn-primary" id="btn-bp-gen-image">🎨 Generate Image</button>
                                <button class="tabaix-seo-btn tabaix-seo-btn-ghost" id="btn-bp-auto-prompt">✨ Auto-Prompt from Title</button>
                            </div>
                            <span id="bp-img-status" class="tabaix-seo-save-status"></span>
                        </div>

                        <div>
                            <div id="bp-img-preview" class="tabaix-seo-image-preview-box">
                                <div class="tabaix-seo-placeholder tabaix-seo-image-placeholder">
                                    <span class="dashicons dashicons-format-image"
                                        style="font-size:48px;width:48px;height:48px;color:var(--tabaix-seo-text3);"></span>
                                    <p>Your AI-generated image will appear here</p>
                                </div>
                            </div>
                            <div id="bp-img-actions" class="tabaix-seo-image-actions" style="display:none; margin-top:12px;">
                                <a href="#" id="btn-bp-download-image" class="tabaix-seo-btn tabaix-seo-btn-secondary" download>⬇️
                                    Download</a>
                                <button id="btn-bp-set-featured" class="tabaix-seo-btn tabaix-seo-btn-primary">⭐ Set as Featured</button>
                                <button id="btn-bp-insert-image" class="tabaix-seo-btn tabaix-seo-btn-ghost">📌 Copy URL</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Tab -->
            <div class="tabaix-seo-tab-pane" id="tab-product">
                <div class="tabaix-seo-two-col">
                    <div class="tabaix-seo-panel">
                        <h3>Product Description Generator</h3>
                        <div class="tabaix-seo-field-group">
                            <label>Product Name *</label>
                            <input type="text" id="prod-name" placeholder="e.g. Wireless Noise-Cancelling Headphones"
                                class="tabaix-seo-input">
                        </div>
                        <div class="tabaix-seo-field-group">
                            <label>Key Features</label>
                            <textarea id="prod-features" rows="3"
                                placeholder="30hr battery, Active Noise Cancellation, Bluetooth 5.0..."
                                class="tabaix-seo-textarea"></textarea>
                        </div>
                        <div class="tabaix-seo-field-group">
                            <label>Target Audience</label>
                            <input type="text" id="prod-audience" placeholder="e.g. Remote workers, music lovers"
                                class="tabaix-seo-input">
                        </div>
                        <button class="tabaix-seo-btn tabaix-seo-btn-primary" id="btn-product-desc">✨ Generate Description</button>
                    </div>
                    <div class="tabaix-seo-panel">
                        <div class="tabaix-seo-result-header">
                            <h3>Product Description</h3>
                            <button class="tabaix-seo-btn tabaix-seo-btn-ghost" id="btn-copy-product">📋 Copy</button>
                        </div>
                        <div class="tabaix-seo-result-area" id="product-result">
                            <div class="tabaix-seo-placeholder">Your product description will appear here...</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Social Media Tab -->
            <div class="tabaix-seo-tab-pane" id="tab-social">
                <div class="tabaix-seo-two-col">
                    <div class="tabaix-seo-panel">
                        <h3>Social Media Post Generator</h3>
                        <div class="tabaix-seo-field-group">
                            <label>Topic / Article Title *</label>
                            <input type="text" id="social-topic" placeholder="Your blog post title or topic" class="tabaix-seo-input">
                        </div>
                        <div class="tabaix-seo-field-group">
                            <label>Article URL (optional)</label>
                            <input type="url" id="social-url" placeholder="https://yoursite.com/post" class="tabaix-seo-input">
                        </div>
                        <button class="tabaix-seo-btn tabaix-seo-btn-primary" id="btn-social">✨ Generate Posts</button>
                    </div>
                    <div class="tabaix-seo-panel">
                        <h3>Generated Posts</h3>
                        <div id="social-result" class="tabaix-seo-social-results">
                            <div class="tabaix-seo-placeholder">Social media posts will appear here...</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Email Tab -->
            <div class="tabaix-seo-tab-pane" id="tab-email">
                <div class="tabaix-seo-two-col">
                    <div class="tabaix-seo-panel">
                        <h3>Email Marketing Generator</h3>
                        <div class="tabaix-seo-field-group">
                            <label>Email Type</label>
                            <select id="email-type" class="tabaix-seo-select">
                                <option value="newsletter">📰 Newsletter</option>
                                <option value="promotional">🏷️ Promotional</option>
                                <option value="welcome">👋 Welcome Email</option>
                                <option value="abandoned">🛒 Abandoned Cart</option>
                                <option value="announcement">📣 Announcement</option>
                            </select>
                        </div>
                        <div class="tabaix-seo-field-group">
                            <label>Topic / Product *</label>
                            <input type="text" id="email-topic" placeholder="e.g. Summer Sale, New Feature Launch"
                                class="tabaix-seo-input">
                        </div>
                        <div class="tabaix-seo-field-group">
                            <label>Brand Name</label>
                            <input type="text" id="email-brand" placeholder="Your brand name" class="tabaix-seo-input">
                        </div>
                        <button class="tabaix-seo-btn tabaix-seo-btn-primary" id="btn-email">✨ Generate Email</button>
                    </div>
                    <div class="tabaix-seo-panel">
                        <h3>Generated Email</h3>
                        <div id="email-result" class="tabaix-seo-report-area">
                            <div class="tabaix-seo-placeholder">Your email copy will appear here...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    // ─── SEO & Optimizer Page ────────────────────────────────────────────────

    public function page_seo()
    {
        ?>
        <div class="tabaix-seo-wrap">
            <?php $this->render_header(); ?>
            <div class="tabaix-seo-tabs-nav">
                <button class="tabaix-seo-tab active" data-tab="seo-audit">🔍 SEO Audit</button>
                <button class="tabaix-seo-tab" data-tab="readability">👁️ Readability</button>
                <button class="tabaix-seo-tab" data-tab="keywords">🔑 Keywords</button>
                <button class="tabaix-seo-tab" data-tab="originality">🛡️ Originality</button>
                <button class="tabaix-seo-tab" data-tab="grammar">✍️ Grammar</button>
                <button class="tabaix-seo-tab" data-tab="sentiment">💬 Sentiment</button>
                <button class="tabaix-seo-tab" data-tab="performance">📊 Performance</button>
                <button class="tabaix-seo-tab" data-tab="meta">🏷️ Meta & SEO</button>
            </div>

            <!-- SEO Audit -->
            <div class="tabaix-seo-tab-pane active" id="tab-seo-audit">
                <!-- Stats Overview -->
                <div class="tabaix-seo-audit-stats" id="audit-stats">
                    <div class="tabaix-seo-audit-stat-card" data-type="seo_title">
                        <div class="tabaix-seo-audit-stat-icon">🏷️</div>
                        <div class="tabaix-seo-audit-stat-value" id="stat-missing-title">—</div>
                        <div class="tabaix-seo-audit-stat-label">Missing SEO Title</div>
                    </div>
                    <div class="tabaix-seo-audit-stat-card" data-type="meta_desc">
                        <div class="tabaix-seo-audit-stat-icon">📝</div>
                        <div class="tabaix-seo-audit-stat-value" id="stat-missing-desc">—</div>
                        <div class="tabaix-seo-audit-stat-label">Missing Meta Description</div>
                    </div>
                    <div class="tabaix-seo-audit-stat-card" data-type="focus_kw">
                        <div class="tabaix-seo-audit-stat-icon">🔑</div>
                        <div class="tabaix-seo-audit-stat-value" id="stat-missing-kw">—</div>
                        <div class="tabaix-seo-audit-stat-label">Missing Focus Keyword</div>
                    </div>
                    <div class="tabaix-seo-audit-stat-card" data-type="alt_text">
                        <div class="tabaix-seo-audit-stat-icon">🖼️</div>
                        <div class="tabaix-seo-audit-stat-value" id="stat-missing-alt">—</div>
                        <div class="tabaix-seo-audit-stat-label">Images Missing Alt Text</div>
                    </div>
                </div>

                <!-- Controls -->
                <div class="tabaix-seo-audit-controls">
                    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                        <select id="audit-post-type" class="tabaix-seo-select" style="width:auto;min-width:140px">
                            <option value="any">All Types</option>
                            <option value="post">Posts</option>
                            <option value="page">Pages</option>
                        </select>
                        <button class="tabaix-seo-btn tabaix-seo-btn-primary" id="btn-scan-seo">🔍 Scan All Content</button>
                        <span id="audit-scan-status" style="font-size:12px;color:var(--tabaix-seo-text3)"></span>
                    </div>
                    <div style="display:flex;gap:8px;align-items:center">
                        <label style="font-size:12px;color:var(--tabaix-seo-text2);white-space:nowrap">Show:</label>
                        <select id="audit-filter-issues" class="tabaix-seo-select" style="width:auto;min-width:160px">
                            <option value="all">All Posts</option>
                            <option value="issues">With Issues Only</option>
                            <option value="seo_title">Missing SEO Title</option>
                            <option value="meta_description">Missing Meta Desc.</option>
                            <option value="focus_keyword">Missing Keyword</option>
                            <option value="image_alt_text">Missing Alt Text</option>
                        </select>
                    </div>
                </div>

                <!-- Posts Table -->
                <div class="tabaix-seo-panel" style="margin-top:16px;padding:0;overflow:hidden">
                    <table class="tabaix-seo-audit-table" id="audit-table">
                        <thead>
                            <tr>
                                <th style="width:40%">Post / Page</th>
                                <th>SEO Title</th>
                                <th>Meta Desc.</th>
                                <th>Keyword</th>
                                <th>Images</th>
                                <th style="width:80px">Score</th>
                                <th style="width:170px">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="audit-table-body">
                            <tr>
                                <td colspan="7" style="text-align:center;padding:40px;color:var(--tabaix-seo-text3)">
                                    Click <strong>"Scan All Content"</strong> to audit your posts & pages for missing SEO data.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Post Audit Detail Panel (shown on click) -->
                <div class="tabaix-seo-panel tabaix-seo-audit-detail" id="audit-detail" style="display:none;margin-top:16px">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                        <h3 id="audit-detail-title" style="margin:0">Post SEO Audit</h3>
                        <button class="tabaix-seo-btn tabaix-seo-btn-ghost tabaix-seo-btn-sm" id="btn-close-audit-detail">✕ Close</button>
                    </div>
                    <div id="audit-detail-content">
                        <div class="tabaix-seo-placeholder">Loading audit details...</div>
                    </div>
                </div>
            </div>

            <!-- Readability -->
            <div class="tabaix-seo-tab-pane" id="tab-readability">
                <div class="tabaix-seo-two-col">
                    <div class="tabaix-seo-panel">
                        <h3>Readability Analysis</h3>
                        <div class="tabaix-seo-field-group">
                            <label>Content to Analyze</label>
                            <textarea id="read-content" rows="10" placeholder="Paste your content here..."
                                class="tabaix-seo-textarea"></textarea>
                        </div>
                        <button class="tabaix-seo-btn tabaix-seo-btn-primary" id="btn-readability">🔍 Analyze Readability</button>
                    </div>
                    <div class="tabaix-seo-panel">
                        <h3>Readability Report</h3>
                        <div id="readability-result" class="tabaix-seo-report-area">
                            <div class="tabaix-seo-placeholder">Analysis results will appear here...</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Keywords -->
            <div class="tabaix-seo-tab-pane" id="tab-keywords">
                <div class="tabaix-seo-two-col">
                    <div class="tabaix-seo-panel">
                        <h3>Keyword Analysis</h3>
                        <div class="tabaix-seo-field-group">
                            <label>Focus Keyword</label>
                            <input type="text" id="kw-focus" placeholder="Your main target keyword" class="tabaix-seo-input">
                        </div>
                        <div class="tabaix-seo-field-group">
                            <label>Content</label>
                            <textarea id="kw-content" rows="9" placeholder="Paste your content here..."
                                class="tabaix-seo-textarea"></textarea>
                        </div>
                        <button class="tabaix-seo-btn tabaix-seo-btn-primary" id="btn-keywords">🔑 Analyze Keywords</button>
                    </div>
                    <div class="tabaix-seo-panel">
                        <h3>Keyword Report</h3>
                        <div id="keywords-result" class="tabaix-seo-report-area">
                            <div class="tabaix-seo-placeholder">Keyword analysis will appear here...</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Originality -->
            <div class="tabaix-seo-tab-pane" id="tab-originality">
                <div class="tabaix-seo-two-col">
                    <div class="tabaix-seo-panel">
                        <h3>Originality Check</h3>
                        <p class="tabaix-seo-hint">Our AI evaluates if your content sounds unique or generic.</p>
                        <div class="tabaix-seo-field-group">
                            <label>Content to Check</label>
                            <textarea id="orig-content" rows="10" placeholder="Paste your content here..."
                                class="tabaix-seo-textarea"></textarea>
                        </div>
                        <button class="tabaix-seo-btn tabaix-seo-btn-primary" id="btn-originality">🛡️ Check Originality</button>
                    </div>
                    <div class="tabaix-seo-panel">
                        <h3>Originality Report</h3>
                        <div id="originality-result" class="tabaix-seo-report-area">
                            <div class="tabaix-seo-placeholder">Originality report will appear here...</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grammar -->
            <div class="tabaix-seo-tab-pane" id="tab-grammar">
                <div class="tabaix-seo-two-col">
                    <div class="tabaix-seo-panel">
                        <h3>Grammar & Spelling Correction</h3>
                        <div class="tabaix-seo-field-group">
                            <label>Content to Fix</label>
                            <textarea id="gram-content" rows="10" placeholder="Paste your content here..."
                                class="tabaix-seo-textarea"></textarea>
                        </div>
                        <div class="tabaix-seo-btn-group">
                            <button class="tabaix-seo-btn tabaix-seo-btn-secondary" id="btn-grammar-report">📋 Get Report</button>
                            <button class="tabaix-seo-btn tabaix-seo-btn-primary" id="btn-fix-grammar">✅ Fix Grammar</button>
                        </div>
                    </div>
                    <div class="tabaix-seo-panel">
                        <h3>Corrected Content</h3>
                        <div id="grammar-result" class="tabaix-seo-report-area">
                            <div class="tabaix-seo-placeholder">Corrected content will appear here...</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sentiment -->
            <div class="tabaix-seo-tab-pane" id="tab-sentiment">
                <div class="tabaix-seo-two-col">
                    <div class="tabaix-seo-panel">
                        <h3>Sentiment Analysis</h3>
                        <p class="tabaix-seo-hint">Understand the emotional tone and audience reaction to your content.</p>
                        <div class="tabaix-seo-field-group">
                            <label>Content</label>
                            <textarea id="sent-content" rows="10" placeholder="Paste your content here..."
                                class="tabaix-seo-textarea"></textarea>
                        </div>
                        <button class="tabaix-seo-btn tabaix-seo-btn-primary" id="btn-sentiment">💬 Analyze Sentiment</button>
                    </div>
                    <div class="tabaix-seo-panel">
                        <h3>Sentiment Report</h3>
                        <div id="sentiment-result" class="tabaix-seo-report-area">
                            <div class="tabaix-seo-placeholder">Sentiment analysis will appear here...</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance -->
            <div class="tabaix-seo-tab-pane" id="tab-performance">
                <div class="tabaix-seo-two-col">
                    <div class="tabaix-seo-panel">
                        <h3>Content Performance Prediction</h3>
                        <div class="tabaix-seo-field-group">
                            <label>Post Title</label>
                            <input type="text" id="perf-title" placeholder="Your post title" class="tabaix-seo-input">
                        </div>
                        <div class="tabaix-seo-field-group">
                            <label>Niche / Category</label>
                            <input type="text" id="perf-niche" placeholder="e.g. Health & Wellness, Tech" class="tabaix-seo-input">
                        </div>
                        <div class="tabaix-seo-field-group">
                            <label>Content</label>
                            <textarea id="perf-content" rows="7" placeholder="Paste your content here..."
                                class="tabaix-seo-textarea"></textarea>
                        </div>
                        <button class="tabaix-seo-btn tabaix-seo-btn-primary" id="btn-performance">📊 Predict Performance</button>
                    </div>
                    <div class="tabaix-seo-panel">
                        <h3>Performance Prediction</h3>
                        <div id="performance-result" class="tabaix-seo-report-area">
                            <div class="tabaix-seo-placeholder">Performance prediction will appear here...</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Meta & SEO -->
            <div class="tabaix-seo-tab-pane" id="tab-meta">
                <div class="tabaix-seo-two-col">
                    <div class="tabaix-seo-panel">
                        <h3>SEO Title &amp; Meta Description Generator</h3>
                        <div class="tabaix-seo-field-group">
                            <label>Post Title *</label>
                            <input type="text" id="meta-title" placeholder="Your post title" class="tabaix-seo-input">
                        </div>
                        <div class="tabaix-seo-field-group">
                            <label>Focus Keyword</label>
                            <input type="text" id="meta-keyword" placeholder="Primary keyword" class="tabaix-seo-input">
                        </div>
                        <div class="tabaix-seo-field-group">
                            <label>Content Snippet</label>
                            <textarea id="meta-content" rows="4" placeholder="Paste first paragraph of your post..."
                                class="tabaix-seo-textarea"></textarea>
                        </div>
                        <button class="tabaix-seo-btn tabaix-seo-btn-primary" id="btn-meta">🏷️ Generate Meta</button>

                        <hr class="tabaix-seo-separator">
                        <div
                            style="background:rgba(99,102,241,.06);border:1px solid var(--tabaix-seo-border2);border-radius:10px;padding:14px">
                            <strong style="font-size:12px;color:var(--tabaix-seo-accent)">💾 Save to a Post</strong>
                            <p class="tabaix-seo-hint" style="margin:6px 0">Save the generated SEO data directly to a post's meta
                                (injects into &lt;head&gt; automatically).</p>
                            <div class="tabaix-seo-field-group">
                                <label>Select Post</label>
                                <select id="meta-save-post-id" class="tabaix-seo-select">
                                    <option value="">-- Select a post --</option>
                                    <?php
                                    $all_posts = get_posts(['posts_per_page' => 30, 'post_status' => 'publish']);
                                    foreach ($all_posts as $p) {
                                        echo '<option value="' . esc_attr($p->ID) . '">' . esc_html($p->post_title) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <button class="tabaix-seo-btn tabaix-seo-btn-secondary tabaix-seo-btn-block" id="btn-save-meta-to-post">💾 Save Meta to
                                Post</button>
                        </div>
                    </div>
                    <div class="tabaix-seo-panel">
                        <h3>Generated Meta Data</h3>
                        <div id="meta-result" class="tabaix-seo-report-area">
                            <div class="tabaix-seo-placeholder">SEO meta will appear here...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    // ─── Image AI Page ────────────────────────────────────────────────────────

    public function page_images()
    {
        $posts = get_posts(['posts_per_page' => 20, 'post_status' => 'publish']);
        ?>
        <div class="tabaix-seo-wrap">
            <?php $this->render_header(); ?>
            <div class="tabaix-seo-tabs-nav">
                <button class="tabaix-seo-tab active" data-tab="featured-image">🖼️ Featured Image</button>
                <button class="tabaix-seo-tab" data-tab="product-image">🛒 Product Variant</button>
                <button class="tabaix-seo-tab" data-tab="image-optimize">⚡ Optimization Tips</button>
                <button class="tabaix-seo-tab" data-tab="alt-text">🏷️ Alt Text Generator</button>
                <button class="tabaix-seo-tab" data-tab="vision-analyzer">🔍 Vision Analyzer</button>
            </div>

            <!-- Featured Image -->
            <div class="tabaix-seo-tab-pane active" id="tab-featured-image">
                <div class="tabaix-seo-two-col">
                    <div class="tabaix-seo-panel">
                        <h3>Featured Image Generator</h3>
                        <div class="tabaix-seo-field-group">
                            <label>Post (optional)</label>
                            <select id="img-post-id" class="tabaix-seo-select">
                                <option value="0">-- Custom Title --</option>
                                <?php foreach ($posts as $p): ?>
                                    <option value="<?php echo esc_attr($p->ID); ?>">
                                        <?php echo esc_html($p->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="tabaix-seo-field-group">
                            <label>Post Title *</label>
                            <input type="text" id="img-title" placeholder="Enter your blog post title" class="tabaix-seo-input">
                        </div>
                        <div class="tabaix-seo-field-group">
                            <label>Post Excerpt</label>
                            <textarea id="img-excerpt" rows="2" placeholder="Brief description..."
                                class="tabaix-seo-textarea"></textarea>
                        </div>
                        <div class="tabaix-seo-field-group">
                            <label>Image Style</label>
                            <select id="img-style" class="tabaix-seo-select">
                                <option value="photorealistic">📷 Photorealistic</option>
                                <option value="illustration">🎨 Illustration</option>
                                <option value="digital-art">🖥️ Digital Art</option>
                                <option value="minimalist">✨ Minimalist</option>
                                <option value="watercolor">🎭 Watercolor</option>
                            </select>
                        </div>
                        <div class="tabaix-seo-field-group">
                            <label>Aspect Ratio</label>
                            <select id="img-ratio" class="tabaix-seo-select">
                                <option value="16:9">🖥️ 16:9 (Landscape)</option>
                                <option value="1:1" selected>◻️ 1:1 (Square)</option>
                                <option value="4:3">📷 4:3 (Standard)</option>
                                <option value="3:4">📱 3:4 (Portrait)</option>
                                <option value="9:16">📱 9:16 (Tall)</option>
                            </select>
                        </div>
                        <div class="tabaix-seo-field-group tabaix-seo-checkbox-group">
                            <label>
                                <input type="checkbox" id="img-save-library" checked> Save to Media Library
                            </label>
                        </div>
                        <div style="display:flex;gap:10px">
                            <button class="tabaix-seo-btn tabaix-seo-btn-secondary" id="btn-img-auto-prompt" style="flex:1">🤖 AI
                                Auto-Prompt</button>
                            <button class="tabaix-seo-btn tabaix-seo-btn-primary" id="btn-gen-image" style="flex:2">🎨 Generate Image</button>
                        </div>
                    </div>
                    <div class="tabaix-seo-panel tabaix-seo-image-preview-panel">
                        <h3>Generated Image</h3>
                        <div id="image-preview-area" class="tabaix-seo-image-preview">
                            <div class="tabaix-seo-placeholder tabaix-seo-image-placeholder">
                                <span class="dashicons dashicons-format-image"></span>
                                <p>Your AI-generated image will appear here</p>
                            </div>
                        </div>
                        <div id="image-actions" class="tabaix-seo-image-actions" style="display:none; margin-top:15px">
                            <a href="#" id="btn-download-image" class="tabaix-seo-btn tabaix-seo-btn-secondary" download>⬇️ Download</a>
                            <button id="btn-set-featured" class="tabaix-seo-btn tabaix-seo-btn-primary">⭐ Set as Featured</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Image -->
            <div class="tabaix-seo-tab-pane" id="tab-product-image">
                <div class="tabaix-seo-two-col">
                    <div class="tabaix-seo-panel">
                        <h3>Product Image Variant Prompt</h3>
                        <p class="tabaix-seo-hint">Generate professional image prompts for your product variants.</p>
                        <div class="tabaix-seo-field-group">
                            <label>Product Name *</label>
                            <input type="text" id="pi-product" placeholder="e.g. Leather Wallet" class="tabaix-seo-input">
                        </div>
                        <div class="tabaix-seo-field-group">
                            <label>Background Variant</label>
                            <select id="pi-variant" class="tabaix-seo-select">
                                <option value="white background">White Background</option>
                                <option value="lifestyle setting">Lifestyle Setting</option>
                                <option value="gradient background">Gradient Background</option>
                                <option value="dark dramatic background">Dark Dramatic</option>
                                <option value="natural outdoor setting">Natural Outdoor</option>
                            </select>
                        </div>
                        <div class="tabaix-seo-field-group">
                            <label>Photography Style</label>
                            <select id="pi-style" class="tabaix-seo-select">
                                <option value="commercial photography">Commercial Photography</option>
                                <option value="flat lay photography">Flat Lay</option>
                                <option value="macro photography">Macro/Close-up</option>
                                <option value="editorial photography">Editorial</option>
                            </select>
                        </div>
                        <button class="tabaix-seo-btn tabaix-seo-btn-primary" id="btn-product-img">✨ Generate Prompt</button>
                    </div>
                    <div class="tabaix-seo-panel">
                        <h3>Image Prompt</h3>
                        <div id="product-img-result" class="tabaix-seo-report-area"></div>
                    </div>
                </div>
            </div>

            <!-- Image Optimization -->
            <div class="tabaix-seo-tab-pane" id="tab-image-optimize">
                <div class="tabaix-seo-two-col">
                    <div class="tabaix-seo-panel">
                        <h3>Image Optimization Advisor</h3>
                        <div class="tabaix-seo-field-group">
                            <label>File Name</label>
                            <input type="text" id="opt-filename" placeholder="e.g. hero-banner.jpg" class="tabaix-seo-input">
                        </div>
                        <div class="tabaix-seo-field-group">
                            <label>File Size (KB)</label>
                            <input type="number" id="opt-size" placeholder="e.g. 450" class="tabaix-seo-input">
                        </div>
                        <div class="tabaix-seo-field-group">
                            <label>Dimensions</label>
                            <input type="text" id="opt-dimensions" placeholder="e.g. 1920x1080" class="tabaix-seo-input">
                        </div>
                        <button class="tabaix-seo-btn tabaix-seo-btn-primary" id="btn-optimize">⚡ Get Recommendations</button>
                    </div>
                    <div class="tabaix-seo-panel">
                        <h3>Optimization Recommendations</h3>
                        <div id="optimize-result" class="tabaix-seo-report-area"></div>
                    </div>
                </div>
            </div>

            <!-- Alt Text Generator -->
            <div class="tabaix-seo-tab-pane" id="tab-alt-text">
                <div class="tabaix-seo-two-col">
                    <div class="tabaix-seo-panel">
                        <h3>AI Alt Text Generator</h3>
                        <p class="tabaix-seo-hint">Generate descriptive, SEO-optimized alt text for your media library images.</p>
                        <div class="tabaix-seo-field-group">
                            <label>Select Image</label>
                            <select id="alt-attachment-id" class="tabaix-seo-select">
                                <option value="">-- Select an image from media library --</option>
                                <?php
                                $imgs = get_posts(['post_type' => 'attachment', 'post_mime_type' => 'image', 'posts_per_page' => 50, 'post_status' => 'inherit']);
                                foreach ($imgs as $img):
                                    $curr_alt = get_post_meta($img->ID, '_wp_attachment_image_alt', true);
                                    ?>
                                    <option value="<?php echo esc_attr($img->ID); ?>"
                                        data-thumb="<?php echo esc_url(wp_get_attachment_image_url($img->ID, 'thumbnail')); ?>"
                                        data-alt="<?php echo esc_attr($curr_alt); ?>">
                                        <?php echo esc_html($img->post_title ?: basename(get_attached_file($img->ID))); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="tabaix-seo-btn-group">
                            <button class="tabaix-seo-btn tabaix-seo-btn-secondary" id="btn-preview-alt">👁 Preview</button>
                            <button class="tabaix-seo-btn tabaix-seo-btn-primary" id="btn-gen-alt">✨ Generate & Save</button>
                        </div>
                        <hr class="tabaix-seo-separator" style="margin:18px 0">
                        <div
                            style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);border-radius:10px;padding:14px">
                            <strong style="font-size:12px;color:#f59e0b">⚡ Bulk Generation</strong>
                            <div class="tabaix-seo-field-group" style="margin-top:10px">
                                <label>Batch Size</label>
                                <select id="alt-bulk-limit" class="tabaix-seo-select">
                                    <option value="5">5 images</option>
                                    <option value="10" selected>10 images</option>
                                </select>
                            </div>
                            <button class="tabaix-seo-btn tabaix-seo-btn-primary tabaix-seo-btn-block" id="btn-bulk-alt">🚀 Bulk Generate</button>
                        </div>
                    </div>
                    <div class="tabaix-seo-panel">
                        <h3>Results</h3>
                        <div id="alt-result" class="tabaix-seo-report-area"></div>
                        <div id="alt-image-preview" style="margin-top:16px;display:none">
                            <img id="alt-preview-img" src=""
                                style="max-width:100%;border-radius:10px;border:1px solid var(--tabaix-seo-border)">
                            <div style="margin-top:10px">
                                <label style="font-size:11px;font-weight:700;color:var(--tabaix-seo-text2)">ALT TEXT</label>
                                <input type="text" id="alt-edit-field" class="tabaix-seo-input" style="margin-top:5px">
                                <div class="tabaix-seo-btn-group" style="margin-top:10px">
                                    <button class="tabaix-seo-btn tabaix-seo-btn-ghost" id="btn-copy-alt">📋 Copy</button>
                                    <button class="tabaix-seo-btn tabaix-seo-btn-primary" id="btn-save-alt">💾 Save</button>
                                </div>
                            </div>
                        </div>
                        <div id="alt-bulk-results" style="margin-top:16px"></div>
                    </div>
                </div>
            </div>

            <!-- Vision Analyzer -->
            <div class="tabaix-seo-tab-pane" id="tab-vision-analyzer">
                <div class="tabaix-seo-two-col">
                    <div class="tabaix-seo-panel">
                        <h3>Vision AI Analyzer</h3>
                        <p class="tabaix-seo-hint">Analyze images to get AI-powered SEO titles and descriptive alt text.</p>
                        <div class="tabaix-seo-field-group">
                            <label>Select Image</label>
                            <button type="button" class="tabaix-seo-btn tabaix-seo-btn-secondary" id="btn-select-vision-admin">🖼️ Select from
                                Media Library</button>
                            <div id="vision-admin-preview" style="margin-top:15px"></div>
                            <input type="hidden" id="vision-admin-attach-id">
                        </div>
                        <div class="tabaix-seo-field-group">
                            <label>Apply to Post (optional)</label>
                            <select id="vision-admin-post-id" class="tabaix-seo-select">
                                <option value="0">-- Select Post --</option>
                                <?php foreach ($posts as $p): ?>
                                    <option value="<?php echo esc_attr($p->ID); ?>">
                                        <?php echo esc_html($p->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button class="tabaix-seo-btn tabaix-seo-btn-primary tabaix-seo-btn-block" id="btn-analyze-vision-admin">🔍 Analyze
                            Image</button>
                    </div>
                    <div class="tabaix-seo-panel">
                        <h3>Analysis Results</h3>
                        <div id="vision-admin-results" class="tabaix-seo-report-area">
                            <div class="tabaix-seo-placeholder">Results will appear here...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    // ─── Comment Moderation Page ──────────────────────────────────────────────

    public function page_moderation()
    {
        $pending = get_comments(['status' => 'hold', 'number' => 20]);
        ?>
        <div class="tabaix-seo-wrap">
            <?php $this->render_header(); ?>
            <div class="tabaix-seo-two-col">
                <div class="tabaix-seo-panel">
                    <h3>Manual Comment Check</h3>
                    <div class="tabaix-seo-field-group">
                        <label>Comment Text</label>
                        <textarea id="mod-comment" rows="6" placeholder="Paste a comment to analyze..."
                            class="tabaix-seo-textarea"></textarea>
                    </div>
                    <button class="tabaix-seo-btn tabaix-seo-btn-primary" id="btn-mod-comment">🤖 Analyze Comment</button>
                    <div id="mod-result" class="tabaix-seo-report-area tabaix-seo-mt"></div>
                </div>
                <div class="tabaix-seo-panel">
                    <div class="tabaix-seo-panel-header-row">
                        <h3>Pending Comments (
                            <?php echo count($pending); ?>)
                        </h3>
                        <button class="tabaix-seo-btn tabaix-seo-btn-secondary" id="btn-bulk-moderate">🤖 Bulk Analyze</button>
                    </div>
                    <div id="bulk-mod-results">
                        <?php if (empty($pending)): ?>
                            <div class="tabaix-seo-empty">No pending comments.</div>
                        <?php else: ?>
                            <?php foreach ($pending as $c): ?>
                                <div class="tabaix-seo-comment-item" data-comment-id="<?php echo esc_attr($c->comment_ID); ?>">
                                    <div class="tabaix-seo-comment-meta">
                                        <strong>
                                            <?php echo esc_html($c->comment_author); ?>
                                        </strong>
                                        <span>
                                            <?php echo esc_html(gmdate('M j, Y', strtotime($c->comment_date))); ?>
                                        </span>
                                    </div>
                                    <p class="tabaix-seo-comment-text">
                                        <?php echo esc_html(substr($c->comment_content, 0, 150)); ?>...
                                    </p>
                                    <div class="tabaix-seo-comment-actions">
                                        <a href="<?php echo esc_url(get_edit_comment_link($c->comment_ID)); ?>"
                                            class="tabaix-seo-btn tabaix-seo-btn-ghost tabaix-seo-btn-sm">Edit</a>
                                        <div class="tabaix-seo-badge tabaix-seo-badge-pending" id="status-<?php echo esc_attr($c->comment_ID); ?>">Pending</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    // ─── Analytics Page ───────────────────────────────────────────────────────

    public function page_analytics()
    {
        ?>
        <div class="tabaix-seo-wrap">
            <?php $this->render_header(); ?>
            <div class="tabaix-seo-panel tabaix-seo-mb">
                <div class="tabaix-seo-panel-header-row">
                    <h3>AI-Powered Analytics Report</h3>
                    <button class="tabaix-seo-btn tabaix-seo-btn-primary" id="btn-analytics-report">📊 Generate Report</button>
                </div>
                <p class="tabaix-seo-hint">Get an AI-powered summary and recommendations based on your site's current data.</p>
            </div>
            <div id="analytics-report-result"></div>
        </div>
        <?php
    }

    // ─── Internal Links Page ─────────────────────────────────────────────────

    public function page_internal_links()
    {
        $posts = get_posts([
            'post_type' => ['post', 'page'],
            'post_status' => 'publish',
            'posts_per_page' => 100,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
        $manual_links = TABAIX_SEO_Internal_Links::get_manual_links();
        ?>
        <div class="tabaix-seo-wrap">
            <?php $this->render_header(); ?>

            <div class="tabaix-seo-tabs-nav">
                <button class="tabaix-seo-tab active" data-tab="il-overview">🔗 Overview & Scan</button>
                <button class="tabaix-seo-tab" data-tab="il-ai-suggest">🤖 AI Suggestions</button>
                <button class="tabaix-seo-tab" data-tab="il-keywords">🔑 Keyword Extraction</button>
                <button class="tabaix-seo-tab" data-tab="il-broken">🩺 Broken Link Checker</button>
                <button class="tabaix-seo-tab" data-tab="il-manual">✏️ Manual Links</button>
                <button class="tabaix-seo-tab" data-tab="il-autolink">⚡ Auto-Link Rules</button>
            </div>

            <!-- ═══ TAB 1: Overview & Scan ═══ -->
            <div class="tabaix-seo-tab-pane active" id="tab-il-overview">
                <div class="tabaix-seo-panel">
                    <div class="tabaix-seo-panel-header-row">
                        <h3>🔗 Internal Link Overview</h3>
                        <button class="tabaix-seo-btn tabaix-seo-btn-primary" id="btn-scan-links">🔍 Scan All Posts</button>
                    </div>
                    <p class="tabaix-seo-hint">Scan your entire site to analyze internal/external link structure, find orphan content,
                        and discover linking opportunities.</p>
                    <span id="il-scan-status" class="tabaix-seo-save-status"></span>

                    <div id="il-stats-grid" class="tabaix-seo-stats-row" style="margin-top:20px; display:none;">
                        <div class="tabaix-seo-stat-card"><span class="tabaix-seo-stat-number" id="il-stat-posts">0</span><span
                                class="tabaix-seo-stat-label">Total Posts</span></div>
                        <div class="tabaix-seo-stat-card"><span class="tabaix-seo-stat-number" id="il-stat-internal">0</span><span
                                class="tabaix-seo-stat-label">Internal Links</span></div>
                        <div class="tabaix-seo-stat-card"><span class="tabaix-seo-stat-number" id="il-stat-external">0</span><span
                                class="tabaix-seo-stat-label">External Links</span></div>
                        <div class="tabaix-seo-stat-card"><span class="tabaix-seo-stat-number" id="il-stat-nofollow">0</span><span
                                class="tabaix-seo-stat-label">Nofollow Links</span></div>
                        <div class="tabaix-seo-stat-card"><span class="tabaix-seo-stat-number" id="il-stat-avg">0</span><span
                                class="tabaix-seo-stat-label">Avg Links/Post</span></div>
                        <div class="tabaix-seo-stat-card tabaix-seo-stat-warning"><span class="tabaix-seo-stat-number"
                                id="il-stat-orphans">0</span><span class="tabaix-seo-stat-label">Orphan Pages</span></div>
                        <div class="tabaix-seo-stat-card tabaix-seo-stat-warning"><span class="tabaix-seo-stat-number"
                                id="il-stat-nolinks">0</span><span class="tabaix-seo-stat-label">Posts w/o Links</span></div>
                    </div>
                </div>

                <div class="tabaix-seo-two-col">
                    <div class="tabaix-seo-panel" id="il-report-panel" style="display:none;">
                        <h3>📊 Link Report</h3>
                        <div id="il-report-table" class="tabaix-seo-report-area" style="max-height:500px; overflow-y:auto;"></div>
                    </div>
                    <div class="tabaix-seo-panel" id="il-orphans-panel" style="display:none;">
                        <h3>🚫 Orphan Content <small>(no inbound links)</small></h3>
                        <p class="tabaix-seo-hint">These posts have no internal links pointing to them — they are invisible to
                            crawlers.</p>
                        <div id="il-orphans-list"></div>
                    </div>
                </div>
            </div>

            <!-- ═══ TAB 2: AI Suggestions (Internal + External) ═══ -->
            <div class="tabaix-seo-tab-pane" id="tab-il-ai-suggest">
                <div class="tabaix-seo-two-col">
                    <div class="tabaix-seo-panel">
                        <h3>🤖 AI Link Suggestions</h3>
                        <p class="tabaix-seo-hint">Select a post and choose what kind of links you need. AI will analyze the content
                            and suggest both internal links to your other posts and external links to authority resources.</p>

                        <div class="tabaix-seo-field-group">
                            <label>Select Post</label>
                            <select id="il-post-select" class="tabaix-seo-select">
                                <option value="">— Choose a post —</option>
                                <?php foreach ($posts as $p): ?>
                                    <option value="<?php echo esc_attr($p->ID); ?>"><?php echo esc_html($p->post_title); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="tabaix-seo-field-group">
                            <label>Link Type</label>
                            <div class="tabaix-seo-provider-toggle">
                                <label class="tabaix-seo-provider-opt active">
                                    <input type="radio" name="il_link_type" value="all" checked> 🔗 All Links
                                </label>
                                <label class="tabaix-seo-provider-opt">
                                    <input type="radio" name="il_link_type" value="internal"> 🏠 Internal Only
                                </label>
                                <label class="tabaix-seo-provider-opt">
                                    <input type="radio" name="il_link_type" value="external"> 🌍 External Only
                                </label>
                            </div>
                        </div>

                        <button class="tabaix-seo-btn tabaix-seo-btn-primary" id="btn-ai-suggest" disabled>🧠 Get AI Suggestions</button>
                        <span id="il-suggest-status" class="tabaix-seo-save-status"></span>
                    </div>

                    <div class="tabaix-seo-panel">
                        <h3>📋 Suggestions</h3>
                        <div id="il-suggestions" class="tabaix-seo-result-area" style="min-height:200px;">
                            <div class="tabaix-seo-placeholder">Select a post and click "Get AI Suggestions" to see link
                                recommendations.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══ TAB 3: Keyword Extraction ═══ -->
            <div class="tabaix-seo-tab-pane" id="tab-il-keywords">
                <div class="tabaix-seo-two-col">
                    <div class="tabaix-seo-panel">
                        <h3>🔑 AI Keyword Extraction</h3>
                        <p class="tabaix-seo-hint">Analyze a post to extract SEO-relevant keywords. AI will identify which keywords are
                            best for internal linking vs. external linking and rate their search volume potential.</p>

                        <div class="tabaix-seo-field-group">
                            <label>Select Post</label>
                            <select id="kw-post-select" class="tabaix-seo-select">
                                <option value="">— Choose a post —</option>
                                <?php foreach ($posts as $p): ?>
                                    <option value="<?php echo esc_attr($p->ID); ?>"><?php echo esc_html($p->post_title); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button class="tabaix-seo-btn tabaix-seo-btn-primary" id="btn-extract-kw" disabled>🔑 Extract Keywords</button>
                        <span id="kw-status" class="tabaix-seo-save-status"></span>
                    </div>

                    <div class="tabaix-seo-panel">
                        <h3>📊 Extracted Keywords</h3>
                        <div id="kw-results" class="tabaix-seo-result-area" style="min-height:200px;">
                            <div class="tabaix-seo-placeholder">Select a post and click "Extract Keywords" to analyze content.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══ TAB 4: Broken Link Checker ═══ -->
            <div class="tabaix-seo-tab-pane" id="tab-il-broken">
                <div class="tabaix-seo-two-col">
                    <div class="tabaix-seo-panel">
                        <h3>🩺 Broken Link Checker</h3>
                        <p class="tabaix-seo-hint">Check all internal and external links in a post for 404 errors, redirects, and
                            connection issues. Fix broken links directly from here.</p>

                        <div class="tabaix-seo-field-group">
                            <label>Select Post to Check</label>
                            <select id="bl-post-select" class="tabaix-seo-select">
                                <option value="">— Choose a post —</option>
                                <?php foreach ($posts as $p): ?>
                                    <option value="<?php echo esc_attr($p->ID); ?>"><?php echo esc_html($p->post_title); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button class="tabaix-seo-btn tabaix-seo-btn-primary" id="btn-check-broken" disabled>🩺 Check Links</button>
                        <span id="bl-status" class="tabaix-seo-save-status"></span>

                        <div id="bl-summary" style="margin-top:16px; display:none;">
                            <div class="tabaix-seo-stats-row">
                                <div class="tabaix-seo-stat-card"><span class="tabaix-seo-stat-number" id="bl-total">0</span><span
                                        class="tabaix-seo-stat-label">Total Links</span></div>
                                <div class="tabaix-seo-stat-card" style="border-left:3px solid #10b981"><span class="tabaix-seo-stat-number"
                                        id="bl-ok" style="color:#10b981">0</span><span class="tabaix-seo-stat-label">Working</span>
                                </div>
                                <div class="tabaix-seo-stat-card" style="border-left:3px solid #ef4444"><span class="tabaix-seo-stat-number"
                                        id="bl-broken" style="color:#ef4444">0</span><span class="tabaix-seo-stat-label">Broken</span>
                                </div>
                                <div class="tabaix-seo-stat-card" style="border-left:3px solid #f59e0b"><span class="tabaix-seo-stat-number"
                                        id="bl-redirect" style="color:#f59e0b">0</span><span
                                        class="tabaix-seo-stat-label">Redirects</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="tabaix-seo-panel">
                        <h3>📋 Link Health Results</h3>
                        <div id="bl-results" class="tabaix-seo-result-area"
                            style="min-height:200px; max-height:600px; overflow-y:auto;">
                            <div class="tabaix-seo-placeholder">Select a post and click "Check Links" to scan for broken links.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══ TAB 5: Manual Links Manager ═══ -->
            <div class="tabaix-seo-tab-pane" id="tab-il-manual">
                <div class="tabaix-seo-two-col">
                    <div class="tabaix-seo-panel">
                        <h3>✏️ Add Manual Link Rule</h3>
                        <p class="tabaix-seo-hint">Create keyword-to-URL mappings that are automatically applied to your content. Use
                            this for both internal and external links.</p>

                        <div class="tabaix-seo-field-group">
                            <label>Keyword / Anchor Text *</label>
                            <input type="text" id="ml-keyword" class="tabaix-seo-input" placeholder="e.g. SEO best practices">
                        </div>
                        <div class="tabaix-seo-field-group">
                            <label>Target URL *</label>
                            <input type="url" id="ml-url" class="tabaix-seo-input" placeholder="https://example.com/page">
                        </div>
                        <div class="tabaix-seo-field-group">
                            <label>Link Title (tooltip)</label>
                            <input type="text" id="ml-title" class="tabaix-seo-input" placeholder="Descriptive title for SEO">
                        </div>
                        <div class="tabaix-seo-field-group">
                            <label>Link Type</label>
                            <select id="ml-type" class="tabaix-seo-select">
                                <option value="internal">🏠 Internal Link</option>
                                <option value="external">🌍 External Link</option>
                            </select>
                        </div>
                        <div class="tabaix-seo-field-group">
                            <label>Max Links per Post</label>
                            <input type="number" id="ml-max" class="tabaix-seo-input" value="1" min="1" max="10" style="width:80px;">
                        </div>
                        <div class="tabaix-seo-field-group tabaix-seo-toggle-group">
                            <label class="tabaix-seo-toggle-switch">
                                <input type="checkbox" id="ml-nofollow">
                                <span class="tabaix-seo-toggle-slider"></span>
                            </label>
                            <span>Add <code>rel="nofollow"</code></span>
                        </div>
                        <div class="tabaix-seo-field-group tabaix-seo-toggle-group">
                            <label class="tabaix-seo-toggle-switch">
                                <input type="checkbox" id="ml-newtab" checked>
                                <span class="tabaix-seo-toggle-slider"></span>
                            </label>
                            <span>Open in new tab (<code>target="_blank"</code>)</span>
                        </div>

                        <button class="tabaix-seo-btn tabaix-seo-btn-primary tabaix-seo-btn-block" id="btn-save-manual-link">💾 Save Link
                            Rule</button>
                        <span id="ml-status" class="tabaix-seo-save-status"></span>
                    </div>

                    <div class="tabaix-seo-panel">
                        <h3>📋 Saved Link Rules <span id="ml-count" class="tabaix-seo-badge tabaix-seo-badge-pending"
                                style="font-size:11px;"><?php echo count($manual_links); ?></span></h3>
                        <div id="ml-list" class="tabaix-seo-result-area" style="min-height:200px; max-height:500px; overflow-y:auto;">
                            <?php if (empty($manual_links)): ?>
                                <div class="tabaix-seo-placeholder">No manual link rules yet. Add one from the left panel.</div>
                            <?php else: ?>
                                <?php foreach ($manual_links as $ml): ?>
                                    <div class="tabaix-seo-link-rule-item" data-id="<?php echo esc_attr($ml['id']); ?>">
                                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                                            <span
                                                class="tabaix-seo-badge <?php echo $ml['type'] === 'external' ? 'tabaix-seo-badge-spam' : 'tabaix-seo-badge-approve'; ?>"
                                                style="font-size:10px;"><?php echo $ml['type'] === 'external' ? '🌍 External' : '🏠 Internal'; ?></span>
                                            <?php if (!empty($ml['nofollow'])): ?>
                                                <span class="tabaix-seo-badge tabaix-seo-badge-pending" style="font-size:10px;">nofollow</span>
                                            <?php endif; ?>
                                            <?php if ($ml['enabled']): ?>
                                                <span style="color:#10b981;font-size:10px;font-weight:700;">● Active</span>
                                            <?php else: ?>
                                                <span style="color:#94a3b8;font-size:10px;font-weight:700;">○ Inactive</span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="margin-bottom:4px;">
                                            <strong style="color:var(--tabaix-seo-accent);">"<?php echo esc_html($ml['keyword']); ?>"</strong>
                                            <span style="color:var(--tabaix-seo-text2);margin:0 6px;">→</span>
                                            <a href="<?php echo esc_url($ml['url']); ?>" target="_blank"
                                                style="font-size:12px;word-break:break-all;"><?php echo esc_html($ml['url']); ?></a>
                                        </div>
                                        <div style="display:flex;gap:6px;margin-top:6px;">
                                            <button class="tabaix-seo-btn tabaix-seo-btn-sm tabaix-seo-btn-ghost ml-delete-btn"
                                                data-id="<?php echo esc_attr($ml['id']); ?>">🗑️ Delete</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══ TAB 6: Auto-Link Rules ═══ -->
            <div class="tabaix-seo-tab-pane" id="tab-il-autolink">
                <div class="tabaix-seo-two-col">
                    <div class="tabaix-seo-panel">
                        <h3>⚡ Auto-Link Rules</h3>
                        <p class="tabaix-seo-hint">Define keywords that automatically become links when posts are displayed. This is
                            applied dynamically on the frontend. Different from Manual Links — auto-link rules match keywords
                            regardless of where they appear.</p>

                        <div class="tabaix-seo-field-group tabaix-seo-toggle-group">
                            <label class="tabaix-seo-toggle-switch">
                                <input type="checkbox" id="il-autolink-enabled" <?php checked(TABAIX_SEO_Settings::get('autolink_enabled'), 1); ?>>
                                <span class="tabaix-seo-toggle-slider"></span>
                            </label>
                            <span>Enable Auto-Linking on Frontend</span>
                        </div>

                        <div id="il-rules-container">
                            <?php
                            $rules = TABAIX_SEO_Internal_Links::get_autolink_rules();
                            if (empty($rules))
                                $rules = [['keyword' => '', 'url' => '', 'max_links' => 1, 'type' => 'internal', 'nofollow' => false, 'new_tab' => false]];
                            foreach ($rules as $i => $rule):
                                ?>
                                <div class="il-rule-row" data-index="<?php echo esc_attr($i); ?>">
                                    <input type="text" class="il-rule-keyword tabaix-seo-input" placeholder="Keyword"
                                        value="<?php echo esc_attr($rule['keyword'] ?? ''); ?>" style="flex:1;">
                                    <input type="url" class="il-rule-url tabaix-seo-input" placeholder="https://your-site.com/page"
                                        value="<?php echo esc_attr($rule['url'] ?? ''); ?>" style="flex:1.5;">
                                    <select class="il-rule-type tabaix-seo-select" style="width:100px;">
                                        <option value="internal" <?php selected($rule['type'] ?? 'internal', 'internal'); ?>>🏠
                                            Internal</option>
                                        <option value="external" <?php selected($rule['type'] ?? 'internal', 'external'); ?>>🌍
                                            External</option>
                                    </select>
                                    <input type="number" class="il-rule-max" placeholder="Max" min="1" max="10"
                                        value="<?php echo intval($rule['max_links'] ?? 1); ?>" title="Max per post"
                                        style="width:55px;">
                                    <label title="Nofollow"
                                        style="display:flex;align-items:center;gap:3px;font-size:11px;cursor:pointer;">
                                        <input type="checkbox" class="il-rule-nofollow" <?php checked(!empty($rule['nofollow'])); ?>> NF
                                    </label>
                                    <label title="New tab"
                                        style="display:flex;align-items:center;gap:3px;font-size:11px;cursor:pointer;">
                                        <input type="checkbox" class="il-rule-newtab" <?php checked(!empty($rule['new_tab'])); ?>> ↗
                                    </label>
                                    <button type="button" class="tabaix-seo-btn tabaix-seo-btn-sm il-rule-remove" title="Remove">✕</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div style="margin-top:12px; display:flex; gap:10px;">
                            <button type="button" class="tabaix-seo-btn tabaix-seo-btn-secondary" id="btn-add-rule">+ Add Rule</button>
                            <button type="button" class="tabaix-seo-btn tabaix-seo-btn-primary" id="btn-save-rules">💾 Save Rules</button>
                        </div>
                        <span id="il-rules-status" class="tabaix-seo-save-status"></span>
                    </div>

                    <div class="tabaix-seo-panel">
                        <h3>ℹ️ How Auto-Linking Works</h3>
                        <div style="padding:8px 0;line-height:1.8;font-size:13px;color:var(--tabaix-seo-text2);">
                            <p><strong>Internal Links:</strong> Link to other pages/posts on your site. Great for distributing
                                page authority and improving crawlability.</p>
                            <p><strong>External Links:</strong> Link to authority resources. Use <code>nofollow</code> for
                                sponsored or untrusted links.</p>
                            <p><strong>Max per Post:</strong> Limits how many times the keyword is linked in a single post. Set
                                to 1 for most cases.</p>
                            <p><strong>NF (Nofollow):</strong> Adds <code>rel="nofollow"</code> to tell search engines not to
                                follow this link.</p>
                            <p><strong>↗ (New Tab):</strong> Opens the link in a new browser tab with
                                <code>target="_blank"</code>.
                            </p>
                        </div>
                        <div
                            style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);border-radius:10px;padding:14px;margin-top:8px;">
                            <strong style="font-size:12px;color:#f59e0b;">⚠️ SEO Best Practice</strong>
                            <p class="tabaix-seo-hint" style="margin:4px 0;">Don't over-link! 2-5 internal links per 1000 words is
                                ideal. Use nofollow for affiliate or sponsored external links.</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <?php
    }

    // ─── Chatbot Settings Page ────────────────────────────────────────────────



    // ─── Settings Page ────────────────────────────────────────────────────────

    public function page_settings()
    {
        if (isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'tabaix_seo_settings_nonce')) {
            $settings = TABAIX_SEO_Settings::get();
            $settings['provider'] = sanitize_key($_POST['provider'] ?? 'gemini');
            $settings['gemini_api_key'] = sanitize_text_field($_POST['gemini_api_key'] ?? '');
            $settings['openai_api_key'] = sanitize_text_field($_POST['openai_api_key'] ?? '');
            $settings['gemini_model'] = sanitize_text_field($_POST['gemini_model'] ?? TABAIX_SEO_API::DEFAULT_GEMINI_MODEL);
            $settings['openai_model'] = sanitize_text_field($_POST['openai_model'] ?? TABAIX_SEO_API::DEFAULT_OPENAI_MODEL);
            $settings['chatbot_enabled'] = isset($_POST['chatbot_enabled']) ? 1 : 0;
            $settings['chatbot_greeting'] = sanitize_textarea_field($_POST['chatbot_greeting'] ?? '');
            $settings['chatbot_position'] = sanitize_text_field($_POST['chatbot_position'] ?? 'bottom-right');
            $settings['moderation_auto'] = isset($_POST['moderation_auto']) ? 1 : 0;
            $settings['analytics_enabled'] = isset($_POST['analytics_enabled']) ? 1 : 0;
            $settings['recommend_enabled'] = isset($_POST['recommend_enabled']) ? 1 : 0;
            $settings['alt_text_auto'] = isset($_POST['alt_text_auto']) ? 1 : 0;
            update_option(TABAIX_SEO_Settings::OPTION_KEY, $settings);
            echo '<div class="tabaix-seo-notice tabaix-seo-notice-success">✅ Settings saved successfully!</div>';
        }

        $s = TABAIX_SEO_Settings::get();
        $gkey = !empty($s['gemini_api_key']);
        $okey = !empty($s['openai_api_key']);
        $gmodel = $s['gemini_model'] ?? TABAIX_SEO_API::DEFAULT_GEMINI_MODEL;
        $omodel = $s['openai_model'] ?? TABAIX_SEO_API::DEFAULT_OPENAI_MODEL;
        ?>
        <div class="tabaix-seo-wrap">
            <?php $this->render_header(); ?>
            <form method="post" class="tabaix-seo-settings-form">
                <?php wp_nonce_field('tabaix_seo_settings_nonce'); ?>
                <div class="tabaix-seo-settings-grid">

                    <!-- ── Gemini API ── -->
                    <div class="tabaix-seo-card">
                        <div class="tabaix-seo-card-header">
                            <span style="font-size:20px">✦</span>
                            <h3>Google Gemini</h3>
                            <?php if ($gkey): ?>
                                <span class="tabaix-seo-badge tabaix-seo-badge-approve" style="margin-left:auto;font-size:11px">● Connected</span>
                            <?php else: ?>
                                <span class="tabaix-seo-badge tabaix-seo-badge-spam" style="margin-left:auto;font-size:11px">○ Not set</span>
                            <?php endif; ?>
                        </div>

                        <div class="tabaix-seo-field-group">
                            <label>Gemini API Key</label>
                            <div class="tabaix-seo-input-icon-wrap">
                                <input type="password" name="gemini_api_key" id="gemini_api_key"
                                    value="<?php echo esc_attr($s['gemini_api_key'] ?? ''); ?>" class="tabaix-seo-input"
                                    placeholder="AIzaSy…">
                                <button type="button" class="tabaix-seo-toggle-password" data-target="gemini_api_key">👁</button>
                            </div>
                            <div style="display:flex;align-items:center;gap:10px;margin-top:6px">
                                <a href="https://aistudio.google.com/app/apikey" target="_blank" class="tabaix-seo-link">
                                    Get free API key at Google AI Studio →
                                </a>
                            </div>
                        </div>

                        <div class="tabaix-seo-field-group">
                            <label>Gemini Model</label>
                            <select name="gemini_model" id="gemini_model" class="tabaix-seo-select">
                                <?php foreach (TABAIX_SEO_API::gemini_models() as $v => $l): ?>
                                    <option value="<?php echo esc_attr($v); ?>" <?php selected($gmodel, $v); ?>>
                                        <?php echo esc_html($l); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="tabaix-seo-hint" style="margin-top:6px">
                                💡 <strong>Gemini 2.0 Flash</strong> is recommended — fast, free-tier friendly, and supports
                                images.<br>
                                <strong>Gemini 2.5 Pro Exp</strong> gives the best quality but may be slower.
                            </p>
                        </div>

                        <div style="display:flex;gap:10px;align-items:center;margin-top:4px">
                            <button type="button" class="tabaix-seo-btn tabaix-seo-btn-secondary tabaix-seo-btn-sm" id="btn-test-gemini">
                                🔌 Test Connection
                            </button>
                            <span id="gemini-test-result" style="font-size:12px"></span>
                        </div>

                        <div class="tabaix-seo-field-group"
                            style="margin-top:16px;background:rgba(99,102,241,.06);border:1px solid var(--tabaix-seo-border2);border-radius:10px;padding:12px">
                            <strong style="font-size:12px;color:var(--tabaix-seo-accent)">🖼️ Image Generation</strong>
                            <p class="tabaix-seo-hint" style="margin:4px 0">
                                Gemini uses <strong>Gemini 2.5 Flash Image</strong> as the primary image generator
                                with <strong>Imagen 4</strong> as a fallback (requires Gemini API key).
                                The plugin will automatically use these when Gemini is the active provider.
                            </p>
                        </div>
                    </div>

                    <!-- ── OpenAI API ── -->
                    <div class="tabaix-seo-card">
                        <div class="tabaix-seo-card-header">
                            <span style="font-size:20px">⚙</span>
                            <h3>OpenAI</h3>
                            <?php if ($okey): ?>
                                <span class="tabaix-seo-badge tabaix-seo-badge-approve" style="margin-left:auto;font-size:11px">● Connected</span>
                            <?php else: ?>
                                <span class="tabaix-seo-badge tabaix-seo-badge-spam" style="margin-left:auto;font-size:11px">○ Not set</span>
                            <?php endif; ?>
                        </div>

                        <div class="tabaix-seo-field-group">
                            <label>OpenAI API Key</label>
                            <div class="tabaix-seo-input-icon-wrap">
                                <input type="password" name="openai_api_key" id="openai_api_key"
                                    value="<?php echo esc_attr($s['openai_api_key'] ?? ''); ?>" class="tabaix-seo-input"
                                    placeholder="sk-proj-…">
                                <button type="button" class="tabaix-seo-toggle-password" data-target="openai_api_key">👁</button>
                            </div>
                            <a href="https://platform.openai.com/api-keys" target="_blank" class="tabaix-seo-link">
                                Get OpenAI API key →
                            </a>
                        </div>

                        <div class="tabaix-seo-field-group">
                            <label>OpenAI Model</label>
                            <select name="openai_model" id="openai_model" class="tabaix-seo-select">
                                <?php foreach (TABAIX_SEO_API::openai_models() as $v => $l): ?>
                                    <option value="<?php echo esc_attr($v); ?>" <?php selected($omodel, $v); ?>>
                                        <?php echo esc_html($l); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="tabaix-seo-hint" style="margin-top:6px">
                                💡 <strong>GPT-4o</strong> is recommended for most tasks (multimodal, fast, cost-effective).<br>
                                <strong>GPT-4.1</strong> is OpenAI's latest and most capable model.
                            </p>
                        </div>

                        <div style="display:flex;gap:10px;align-items:center;margin-top:4px">
                            <button type="button" class="tabaix-seo-btn tabaix-seo-btn-secondary tabaix-seo-btn-sm" id="btn-test-openai">
                                🔌 Test Connection
                            </button>
                            <span id="openai-test-result" style="font-size:12px"></span>
                        </div>

                        <div class="tabaix-seo-field-group"
                            style="margin-top:16px;background:rgba(99,102,241,.06);border:1px solid var(--tabaix-seo-border2);border-radius:10px;padding:12px">
                            <strong style="font-size:12px;color:var(--tabaix-seo-accent)">🖼️ DALL·E 3 Image Generation</strong>
                            <p class="tabaix-seo-hint" style="margin:4px 0">
                                OpenAI DALL·E 3 is used automatically for image generation when OpenAI is the active provider.
                            </p>
                        </div>
                    </div>

                    <!-- ── Active Provider ── -->
                    <div class="tabaix-seo-card">
                        <div class="tabaix-seo-card-header">
                            <span class="dashicons dashicons-admin-network"></span>
                            <h3>Active Provider</h3>
                        </div>
                        <div class="tabaix-seo-field-group">
                            <p class="tabaix-seo-hint">Choose which provider powers all AI features. The other API can still be used as
                                a fallback.</p>
                            <div class="tabaix-seo-provider-toggle">
                                <label
                                    class="tabaix-seo-provider-opt <?php echo ($s['provider'] ?? 'gemini') === 'gemini' ? 'active' : ''; ?>">
                                    <input type="radio" name="provider" value="gemini" <?php checked($s['provider'] ?? 'gemini', 'gemini'); ?>>
                                    <img src="<?php echo esc_url(TABAIX_SEO_PLUGIN_URL . 'assets/img/gemini-icon.svg'); ?>" alt="" width="20">
                                    Google Gemini
                                </label>
                                <label
                                    class="tabaix-seo-provider-opt <?php echo ($s['provider'] ?? 'gemini') === 'openai' ? 'active' : ''; ?>">
                                    <input type="radio" name="provider" value="openai" <?php checked($s['provider'] ?? 'gemini', 'openai'); ?>>
                                    <img src="<?php echo esc_url(TABAIX_SEO_PLUGIN_URL . 'assets/img/openai-icon.svg'); ?>" alt="" width="20">
                                    OpenAI GPT
                                </label>
                            </div>
                        </div>
                        <div
                            style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);border-radius:10px;padding:12px;margin-top:4px">
                            <strong style="font-size:12px;color:#f59e0b">⚡ Auto-Fallback</strong>
                            <p class="tabaix-seo-hint" style="margin:4px 0">
                                If your active provider's API key is missing, the plugin will automatically try the other
                                provider. Just add both keys and it all works!
                            </p>
                        </div>
                    </div>




                    <!-- ── Chatbot Settings ── -->
                    <div class="tabaix-seo-card">
                        <div class="tabaix-seo-card-header">
                            <span class="dashicons dashicons-format-chat"></span>
                            <h3>Frontend &amp; Admin Chatbot</h3>
                        </div>
                        <div class="tabaix-seo-field-group tabaix-seo-toggle-group">
                            <label class="tabaix-seo-toggle-switch">
                                <input type="checkbox" name="chatbot_enabled" value="1" <?php checked($s['chatbot_enabled'] ?? 0, 1); ?>>
                                <span class="tabaix-seo-toggle-slider"></span>
                            </label>
                            <span>Enable AI Chatbot (Frontend widget &amp; Admin assistant)</span>
                        </div>
                        <div class="tabaix-seo-field-group" style="margin-top:10px">
                            <label>Chatbot Position (Frontend)</label>
                            <select name="chatbot_position" class="tabaix-seo-select">
                                <option value="bottom-right" <?php selected($s['chatbot_position'] ?? 'bottom-right', 'bottom-right'); ?>>Bottom Right</option>
                                <option value="bottom-left" <?php selected($s['chatbot_position'] ?? 'bottom-right', 'bottom-left'); ?>>Bottom Left</option>
                            </select>
                        </div>
                        <div class="tabaix-seo-field-group">
                            <label>Greeting Message</label>
                            <input type="text" name="chatbot_greeting" class="tabaix-seo-input"
                                value="<?php echo esc_attr($s['chatbot_greeting'] ?? 'Hello! I am your AI assistant. How can I help you today?'); ?>"
                                placeholder="Hello! I am your AI assistant…">
                        </div>
                    </div>

                    <!-- ── Features &amp; Automation ── -->
                    <div class="tabaix-seo-card">
                        <div class="tabaix-seo-card-header">
                            <span class="dashicons dashicons-shield"></span>
                            <h3>Features & Automation</h3>
                        </div>
                        <div class="tabaix-seo-field-group tabaix-seo-toggle-group">
                            <label class="tabaix-seo-toggle-switch">
                                <input type="checkbox" name="moderation_auto" value="1" <?php checked($s['moderation_auto'] ?? 0, 1); ?>>
                                <span class="tabaix-seo-toggle-slider"></span>
                            </label>
                            <span>Auto-Moderate Comments on Submission</span>
                        </div>
                        <div class="tabaix-seo-field-group tabaix-seo-toggle-group">
                            <label class="tabaix-seo-toggle-switch">
                                <input type="checkbox" name="alt_text_auto" value="1" <?php checked($s['alt_text_auto'] ?? 0, 1); ?>>
                                <span class="tabaix-seo-toggle-slider"></span>
                            </label>
                            <span>Auto-Generate Image Alt Text on Upload</span>
                        </div>
                        <div class="tabaix-seo-field-group tabaix-seo-toggle-group">
                            <label class="tabaix-seo-toggle-switch">
                                <input type="checkbox" name="analytics_enabled" value="1" <?php checked($s['analytics_enabled'] ?? 1, 1); ?>>
                                <span class="tabaix-seo-toggle-slider"></span>
                            </label>
                            <span>Enable Analytics Insights</span>
                        </div>
                        <div class="tabaix-seo-field-group tabaix-seo-toggle-group">
                            <label class="tabaix-seo-toggle-switch">
                                <input type="checkbox" name="recommend_enabled" value="1" <?php checked($s['recommend_enabled'] ?? 1, 1); ?>>
                                <span class="tabaix-seo-toggle-slider"></span>
                            </label>
                            <span>Enable Content Recommendations</span>
                        </div>
                    </div>

                </div>

                <div class="tabaix-seo-settings-footer">
                    <button type="submit" class="tabaix-seo-btn tabaix-seo-btn-primary tabaix-seo-btn-lg">💾 Save All Settings</button>
                </div>
            </form>
        </div>
        <?php
    }

    // ─── Shared Header ────────────────────────────────────────────────────────

    private function render_header()
    {
        $provider = TABAIX_SEO_Settings::get('provider', 'gemini');
        $chatbot_enabled = (int) TABAIX_SEO_Settings::get('chatbot_enabled', 0);
        ?>
        <div class="tabaix-seo-header">
            <div class="tabaix-seo-header-brand">
                <span class="tabaix-seo-header-icon">✦</span>
                <h1 class="tabaix-seo-header-title">Tabaix SEO Optimizer Pro</h1>
                <span class="tabaix-seo-header-version">v
                    <?php echo esc_html(TABAIX_SEO_VERSION); ?>
                </span>
            </div>
            <div class="tabaix-seo-header-actions">
                <div class="tabaix-seo-provider-switch" title="Switch AI Provider">
                    <button class="tabaix-seo-provider-pill <?php echo $provider === 'gemini' ? 'active' : ''; ?>"
                        data-provider="gemini" id="switch-gemini">
                        <span class="dashicons dashicons-embed-generic"></span> Gemini
                    </button>
                    <button class="tabaix-seo-provider-pill <?php echo $provider === 'openai' ? 'active' : ''; ?>"
                        data-provider="openai" id="switch-openai">
                        <span class="dashicons dashicons-awards"></span> OpenAI
                    </button>
                </div>
                <?php if ($chatbot_enabled): ?>
                    <button id="tabaix-seo-admin-chat-toggle" class="tabaix-seo-btn tabaix-seo-btn-ghost tabaix-seo-btn-sm tabaix-seo-admin-chat-btn"
                        title="AI Assistant" aria-label="AI Assistant">
                        <span class="dashicons dashicons-format-chat"></span> AI Assistant
                    </button>
                <?php endif; ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=tabaix-seo-settings')); ?>" class="tabaix-seo-btn tabaix-seo-btn-ghost tabaix-seo-btn-sm">
                    <span class="dashicons dashicons-admin-settings"></span> Settings
                </a>
            </div>
        </div>

        <?php if ($chatbot_enabled): ?>
            <!-- UAM Admin Chatbot Widget -->
            <div id="tabaix-seo-admin-chatbot" class="tabaix-seo-admin-chatbot" aria-hidden="true" style="display:none">
                <div class="tabaix-seo-achat-header">
                    <div class="tabaix-seo-achat-title">
                        <span class="tabaix-seo-achat-avatar">🤖</span>
                        <div>
                            <strong>AI Admin Assistant</strong>
                            <span class="tabaix-seo-achat-status">● Online</span>
                        </div>
                    </div>
                    <button id="tabaix-seo-admin-chat-close" class="tabaix-seo-achat-close" aria-label="Close">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"
                            stroke-linecap="round">
                            <line x1="18" y1="6" x2="6" y2="18" />
                            <line x1="6" y1="6" x2="18" y2="18" />
                        </svg>
                    </button>
                </div>
                <div id="tabaix-seo-achat-messages" class="tabaix-seo-achat-messages">
                    <div class="tabaix-seo-achat-msg tabaix-seo-achat-bot">
                        <div class="tabaix-seo-achat-bubble">👋 Hi! I&rsquo;m your AI admin assistant. Ask me anything about WordPress,
                            SEO, or your plugin features!</div>
                    </div>
                </div>
                <div class="tabaix-seo-achat-typing tabaix-seo-hidden" id="tabaix-seo-achat-typing">
                    <span></span><span></span><span></span>
                </div>
                <div class="tabaix-seo-achat-input-area">
                    <textarea id="tabaix-seo-achat-input" rows="1" placeholder="Ask me anything…" class="tabaix-seo-achat-input"></textarea>
                    <button id="tabaix-seo-achat-send" class="tabaix-seo-achat-send" aria-label="Send">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5"
                            stroke-linecap="round" stroke-linejoin="round">
                            <line x1="22" y1="2" x2="11" y2="13" />
                            <polygon points="22 2 15 22 11 13 2 9 22 2" />
                        </svg>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    <?php
    }
}
