# TriqHub: Reviews Plugin Documentation

## Introduction

TriqHub: Reviews is a comprehensive WordPress plugin designed to enhance WooCommerce stores with a sophisticated customer review system. The plugin provides a unified, centralized, and fully responsive interface for collecting, managing, and displaying customer testimonials directly on product pages and throughout your e-commerce site.

Built with performance and security as core principles, the plugin implements advanced caching mechanisms, spam protection, and seamless WooCommerce integration. It offers multiple display formats including review forms, lists, carousels, and automated shop displays, all with a consistent design language that can be customized to match your store's branding.

## Features

### Core Functionality
- **Custom Post Type Management**: Dedicated "reviews" post type with custom meta fields for structured data storage
- **WooCommerce Integration**: Automatic association of reviews with products and variations
- **User Authentication**: Review submission restricted to logged-in users with purchase verification
- **Multi-format Display**: Shortcodes for forms, lists, carousels, and summary widgets
- **Automated Shop Display**: Automatic injection of review summaries in product loops and category pages
- **Performance Optimization**: Built-in fragment caching and database indexing

### Display Components
- **Review Form**: User-friendly submission form with star ratings and product selection
- **Review List**: Paginated display of all reviews with avatar generation
- **Review Carousel**: Interactive carousel with navigation controls for featured testimonials
- **Product Review Summary**: Compact star rating display for individual products
- **Global Review Summary**: Aggregate rating display across all products
- **Full Reviews Display**: Complete review listing for specific products

### Security & Anti-Spam
- **Rate Limiting**: IP-based submission throttling to prevent abuse
- **Honeypot Fields**: Hidden form fields to trap automated spam bots
- **Content Validation**: Length and content filtering for review text
- **Suspicious Activity Logging**: Comprehensive security event tracking
- **Spam Detection**: Keyword-based and pattern-based spam identification

### Performance Features
- **Fragment Caching**: Shortcode output caching with configurable TTL
- **Database Optimization**: Custom meta indexes for improved query performance
- **Lazy Loading**: Progressive enhancement for carousel and list components
- **CSS/JS Optimization**: Minified assets and conditional loading

### Administrative Features
- **TriqHub Connector**: Centralized license and update management
- **Update System**: GitHub integration for automatic updates
- **Translation Ready**: Full localization support with Portuguese (Brazil) as primary language
- **Custom Styling**: Admin interface styling for consistent TriqHub branding

## Installation & Usage

### Requirements
- WordPress 6.0 or higher
- PHP 7.4 or higher
- WooCommerce 5.0 or higher (active and installed)

### Installation Methods

#### Method 1: WordPress Admin (Recommended)
1. Navigate to **Plugins → Add New** in your WordPress admin
2. Click **Upload Plugin**
3. Select the `triqhub-reviews.zip` file
4. Click **Install Now**
5. Activate the plugin

#### Method 2: Manual Installation
1. Download the plugin ZIP file from GitHub
2. Extract the contents to `/wp-content/plugins/`
3. Rename the folder to `triqhub-reviews`
4. Navigate to **Plugins** in WordPress admin
5. Locate "TriqHub: Reviews" and click **Activate**

#### Method 3: Git Installation (Developers)
```bash
cd /wp-content/plugins/
git clone https://github.com/gustavofullstack/triqhub-reviews.git
cd triqhub-reviews
composer install
```

### Post-Installation Setup

1. **Verify WooCommerce Activation**: Ensure WooCommerce is installed and active
2. **Check System Requirements**: Confirm PHP 7.4+ and WordPress 6.0+
3. **Review Permalinks**: Navigate to **Settings → Permalinks** and click **Save Changes** to flush rewrite rules
4. **Configure Display Options**: Use shortcodes or enable automatic shop display

### Basic Usage

#### Shortcode Implementation
Add any of the following shortcodes to pages, posts, or widgets:

```php
// Review submission form (requires user login)
[udia_review_v2_form]

// Display all reviews (paginated)
[udia_review_v2_list posts_per_page="20"]

// Interactive review carousel
[udia_review_v2_carousel posts_per_page="10"]

// Product-specific review summary
[udia_product_review_summary product_id="123"]

// Complete reviews for a specific product
[udia_product_full_reviews product_id="123" posts_per_page="10"]

// Global store rating summary
[udia_global_review_summary]
```

#### Automatic Shop Integration
The plugin automatically adds review summaries to WooCommerce shop pages. This occurs:
- Below product images and above titles in product loops
- On individual product pages (when using appropriate shortcodes)
- On category, tag, and taxonomy archive pages

#### Template Overrides
To customize display templates, copy files from `/wp-content/plugins/triqhub-reviews/templates/` to your theme's `triqhub-reviews/` directory.

## Configuration & Architecture

### Plugin Structure
```
triqhub-reviews/
├── assets/                 # CSS, JS, and image files
│   ├── css/
│   │   ├── triqhub-admin.css
│   │   └── udia-reviews.css
│   └── js/
│       └── udia-reviews.js
├── includes/               # Core PHP classes
│   ├── core/
│   │   └── class-triqhub-connector.php
│   ├── class-udia-ajax.php
│   ├── class-udia-assets.php
│   ├── class-udia-constants.php
│   ├── class-udia-core.php
│   ├── class-udia-cpt.php
│   ├── class-udia-helpers.php
│   ├── class-udia-security.php
│   ├── class-udia-shop-display.php
│   └── class-udia-shortcodes.php
├── languages/              # Translation files
├── templates/              # Display templates
├── vendor/                 # Composer dependencies
├── triqhub-reviews.php     # Main plugin file
└── README.md
```

### Database Schema
The plugin extends WordPress with the following data structure:

#### Custom Post Type: `reviews`
- **Post Content**: Review text (2000 character max)
- **Post Title**: Author name with product reference
- **Post Status**: `publish`, `pending`, `draft`

#### Post Meta Fields
- `rating` (int): Star rating from 1-5
- `product_id` (int): Associated WooCommerce product ID
- `variation_id` (int): Associated product variation ID (if applicable)
- `order_item_id` (int): Original order item ID
- `product_label` (string): Human-readable product name
- `verified_purchase` (bool): Whether review is from a verified purchase

#### Custom Database Indexes
- `idx_reviews_rating`: Index on rating meta field
- `idx_reviews_product_id`: Index on product_id meta field
- `idx_reviews_variation_id`: Index on variation_id meta field

### Configuration Constants
The plugin uses the following constants defined in `class-udia-constants.php`:

```php
// Core paths and URLs
define( 'UDIA_REVIEW_V2_PATH', /* Plugin directory path */ );
define( 'UDIA_REVIEW_V2_URL', /* Plugin directory URL */ );

// Text domain for translations
define( 'UDIA_Constants::TEXT_DOMAIN', 'udia-reviews-v2' );

// Security settings
define( 'UDIA_Constants::RATE_LIMIT_REVIEWS_MAX', 3 ); // Max submissions per period
define( 'UDIA_Constants::RATE_LIMIT_PERIOD', 3600 );   // Rate limit period in seconds
define( 'UDIA_Constants::SECURITY_LOG_MAX', 100 );     // Max security log entries

// Content validation
define( 'UDIA_Constants::TEXT_LENGTH_MIN', 10 );       // Minimum review length
define( 'UDIA_Constants::TEXT_LENGTH_MAX', 2000 );     // Maximum review length

// Caching
define( 'UDIA_Constants::CACHE_TTL', 600 );           // Cache TTL in seconds (10 minutes)
```

### Hooks & Filters

#### Actions
```php
// Plugin initialization
add_action( 'plugins_loaded', 'udia_v2_init' );

// Activation/deactivation
register_activation_hook( __FILE__, 'udia_v2_activate' );

// Admin assets
add_action( 'admin_enqueue_scripts', 'triqhub_enqueue_admin_udia_reviews_v2' );

// Shop display
add_action( 'woocommerce_shop_loop_item_title', array( $this, 'display_review_summary' ), 5 );

// Security headers
add_action( 'init', array( 'UDIA_Security', 'add_csp_headers' ) );
```

#### Filters
```php
// Customize cache TTL
add_filter( 'udia_reviews_cache_ttl', function( $ttl ) {
    return 300; // 5 minutes
} );

// Modify spam detection sensitivity
add_filter( 'udia_is_likely_spam', function( $is_spam, $text ) {
    // Add custom spam detection logic
    return $is_spam;
}, 10, 2 );

// Customize avatar generation
add_filter( 'udia_avatar_colors', function( $colors ) {
    $colors[] = '#FF5733'; // Add custom color
    return $colors;
} );
```

#### Shortcodes
```php
// Review form
add_shortcode( 'udia_review_v2_form', array( $this, 'form_shortcode' ) );

// Review list
add_shortcode( 'udia_review_v2_list', array( $this, 'list_shortcode' ) );

// Review carousel
add_shortcode( 'udia_review_v2_carousel', array( $this, 'carousel_shortcode' ) );

// Product review summary
add_shortcode( 'udia_product_review_summary', array( $this, 'product_review_summary_shortcode' ) );

// Full product reviews
add_shortcode( 'udia_product_full_reviews', array( $this, 'product_full_reviews_shortcode' ) );

// Global review summary
add_shortcode( 'udia_global_review_summary', array( $this, 'global_review_summary_shortcode' ) );
```

### CSS Customization
The plugin uses CSS custom properties for easy theming:

```css
:root {
    --udia-accent: #39ff14;        /* Primary accent color (neon green) */
    --udia-bg: #0a0a0a;           /* Background color */
    --udia-card-bg: #1a1a1a;      /* Card background */
    --udia-text: #ffffff;         /* Primary text color */
    --udia-text-muted: #aaaaaa;   /* Muted text color */
    --udia-border: #333333;       /* Border color */
    --udia-radius: 8px;           /* Border radius */
    --udia-shadow: 0 4px 20px rgba(0, 0, 0, 0.3); /* Box shadow */
}
```

Override these variables in your theme's CSS:

```css
.udia-review-wrap {
    --udia-accent: #ff3366; /* Custom accent color */
    --udia-bg: #ffffff;     /* Light mode background */
}
```

## API Reference

### Helper Methods

#### UDIA_Helpers Class
```php
/**
 * Generate initials-based avatar with consistent colors
 *
 * @param string $author_name Author display name
 * @param int    $author_id   WordPress user ID
 * @return array Avatar data with initials, bg_color, and text_color
 */
UDIA_Helpers::generate_initials_avatar( $author_name, $author_id );

/**
 * Get review statistics for a specific product
 *
 * @param int $product_id   WooCommerce product ID
 * @param int $variation_id Product variation ID (optional)
 * @return array Statistics including average_rating and total_reviews
 */
UDIA_Helpers::get_product_review_stats( $product_id, $variation_id = 0 );

/**
 * Get reviews for a specific product with pagination
 *
 * @param int $product_id     WooCommerce product ID
 * @param int $variation_id   Product variation ID (optional)
 * @param int $posts_per_page Number of reviews per page
 * @return WP_Query Query object containing matching reviews
 */
UDIA_Helpers::get_product_reviews( $product_id, $variation_id = 0, $posts_per_page = 10 );

/**
 * Get global review statistics across all products
 *
 * @return array Global statistics including average_rating and total_reviews
 */
UDIA_Helpers::get_global_review_stats();

/**
 * Create database indexes for performance optimization
 *
 * @return void
 */
UDIA_Helpers::create_meta_indexes();
```

#### UDIA_Security Class
```php
/**
 * Check IP-based rate limiting for an action
 *
 * @param string $ip     Client IP address
 * @param string $action Action identifier
 * @param int    $max    Maximum attempts (optional)
 * @param int    $period Time period in seconds (optional)
 * @return bool True if within rate limit
 */
UDIA_Security::check_ip_rate_limit( $ip, $action = 'review_submit', $max = null, $period = null );

/**
 * Validate text length against configured limits
 *
 * @param string $text Text to validate
 * @param int    $min  Minimum length (optional)
 * @param int    $max  Maximum length (optional)
 * @return bool True if valid length
 */
UDIA_Security::validate_text_length( $text, $min = null, $max = null );

/**
 * Detect likely spam content
 *
 * @param string $text Content to analyze
 * @return bool True if content appears to be spam
 */
UDIA_Security::is_likely_spam( $text );

/**
 * Get client IP address with proxy support
 *
 * @return string Client IP address
 */
UDIA_Security::get_client_ip();

/**
 * Log suspicious activity for security monitoring
 *
 * @param string $type Type of suspicious activity
 * @param array  $data Additional context data
 * @return void
 */
UDIA_Security::log_suspicious_activity( $type, $data = array() );
```

### AJAX Endpoints
The plugin provides the following AJAX endpoints (all require WordPress nonce verification):

#### Submit Review
```javascript
// Endpoint: admin-ajax.php?action=udia_submit_review
// Method: POST
// Parameters:
// - nonce: WordPress security nonce
// - name: Reviewer name
// - rating: Star rating (1-5)
// - content: Review text
// - order_item_id: Associated order item ID
// - manual_product: Manual product name (if no order)
// - udia_honeypot: Honeypot field (should be empty)
```

#### Load Order Products
```javascript
// Endpoint: admin-ajax.php?action=udia_get_order_products
// Method: GET
// Parameters:
// - nonce: WordPress security nonce
// Returns: JSON array of recent purchase products for current user
```

### JavaScript API
The plugin includes a JavaScript module for enhanced functionality:

```javascript
// Initialize review carousel
if (typeof udiaReviews !== 'undefined') {
    udiaReviews.initCarousel('.udia-review-carousel-wrap');
}

// Form submission handling
document.querySelector('.udia-review-form').addEventListener('submit', function(e) {
    e.preventDefault();
    // Custom submission logic
});

// Event hooks
document.addEventListener('udia:reviewSubmitted', function(e) {
    console.log('Review submitted:', e.detail);
});

document.addEventListener('udia:carouselInitialized', function(e) {
    console.log('Carousel initialized:', e.detail);
});
```

## Troubleshooting

### Common Issues

#### Issue: Reviews not displaying on shop pages
**Symptoms**: Star ratings missing from product loops
**Solutions**:
1. Verify WooCommerce is active and properly configured
2. Check if `woocommerce_shop_loop_item_title` hook is available in your theme
3. Ensure theme isn't removing default WooCommerce hooks
4. Check for CSS conflicts with `udia-shop-review-summary` class

#### Issue: Review form shows "Carregando itens..." indefinitely
**Symptoms**: Product dropdown never populates
**Solutions**:
1. Verify user is logged in and has completed purchases
2. Check browser console for JavaScript errors
3. Verify AJAX nonce is properly generated
4. Check WooCommerce order status (completed orders only)

#### Issue: "You need to log in" message for logged-in users
**Symptoms**: Login prompt appears despite active session
**Solutions**:
1. Clear WordPress and browser cookies
2. Verify `is_user_logged_in()` function works correctly
3. Check for plugin conflicts affecting authentication
4. Verify WordPress login cookies are set correctly

#### Issue: Poor performance with many reviews
**Symptoms**: Slow page load times on review-heavy pages
**Solutions**:
1. Enable fragment caching (enabled by default)
2. Implement object caching (Redis/Memcached)
3. Reduce `posts_per_page` parameter in shortcodes
4. Ensure database indexes are created (run `UDIA_Helpers::create_meta_indexes()`)

#### Issue: Security false positives
**Symptoms**: Legitimate reviews blocked as spam
**Solutions**:
1. Adjust rate limit settings via filters
2. Modify spam detection sensitivity
3. Whitist specific IP addresses
4. Check security logs for pattern identification

### Debug Mode
Enable detailed logging for troubleshooting:

```php
// Add to wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
