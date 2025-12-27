# TriqHub: Reviews - User Guide

## Table of Contents
1. [Overview](#overview)
2. [System Requirements](#system-requirements)
3. [Installation](#installation)
4. [Configuration](#configuration)
5. [Shortcodes Reference](#shortcodes-reference)
6. [Shop Display Integration](#shop-display-integration)
7. [Security Features](#security-features)
8. [Performance Optimization](#performance-optimization)
9. [Common Use Cases](#common-use-cases)
10. [Troubleshooting & FAQ](#troubleshooting--faq)
11. [Changelog](#changelog)

## Overview

TriqHub: Reviews is a comprehensive WooCommerce review management system designed for high-performance e-commerce websites. The plugin provides a unified, centralized, and responsive review system with form submission, list display, carousel presentation, and direct product page integration.

### Key Features
- **Custom Post Type Management**: Dedicated 'reviews' CPT with full WordPress integration
- **Multiple Display Formats**: Form, list, carousel, and summary shortcodes
- **Shop Loop Integration**: Automatic star ratings display in product listings
- **Advanced Security**: Rate limiting, spam detection, and honeypot protection
- **Performance Optimized**: Fragment caching, database indexing, and optimized queries
- **Responsive Design**: Mobile-first CSS with dark/light theme compatibility
- **WooCommerce Integration**: Direct product association and order-based validation

## System Requirements

### Minimum Requirements
- **WordPress**: 6.0 or higher
- **PHP**: 7.4 or higher
- **WooCommerce**: Latest version (required)
- **MySQL**: 5.6 or higher
- **Memory Limit**: 128MB minimum, 256MB recommended

### Recommended Environment
- **PHP**: 8.0+ for optimal performance
- **OPCache**: Enabled
- **Object Cache**: Redis or Memcached (for high-traffic sites)
- **CDN**: For static asset delivery
- **SSL/TLS**: Required for secure form submissions

## Installation

### Method 1: WordPress Admin (Recommended)
1. Navigate to **Plugins → Add New** in WordPress admin
2. Click **Upload Plugin**
3. Select the `triqhub-reviews.zip` file
4. Click **Install Now**
5. Activate the plugin
6. Ensure WooCommerce is installed and active

### Method 2: Manual Installation
1. Download the plugin ZIP file
2. Extract to `/wp-content/plugins/` directory
3. Rename folder to `triqhub-reviews`
4. Navigate to **Plugins** in WordPress admin
5. Locate "TriqHub: Reviews" and click **Activate**

### Method 3: Git Installation (Developers)
```bash
cd /wp-content/plugins/
git clone https://github.com/gustavofullstack/triqhub-reviews.git
cd triqhub-reviews
composer install
```

### Post-Installation Checklist
1. Verify WooCommerce is active
2. Check for admin notices regarding requirements
3. Flush rewrite rules: **Settings → Permalinks → Save Changes**
4. Verify CPT registration at **Reviews → All Reviews**
5. Test shortcode functionality on a test page

## Configuration

### Plugin Constants & Settings

#### Core Constants (Defined in `class-udia-constants.php`)
```php
// Text Domain
define('UDIA_Constants::TEXT_DOMAIN', 'udia-reviews-v2');

// Custom Post Type
define('UDIA_Constants::CPT_SLUG', 'reviews');

// Cache Configuration
define('UDIA_Constants::CACHE_TTL_FRAGMENT', 600); // 10 minutes
define('UDIA_Constants::CACHE_GROUP_FRAGMENTS', 'udia_fragments');
define('UDIA_Constants::CACHE_GROUP_REVIEWS', 'udia_reviews');

// Security Settings
define('UDIA_Constants::RATE_LIMIT_REVIEWS_MAX', 3); // Max submissions per period
define('UDIA_Constants::RATE_LIMIT_PERIOD', 3600);   // 1 hour in seconds
define('UDIA_Constants::SECURITY_LOG_MAX', 100);     // Max log entries

// Content Validation
define('UDIA_Constants::TEXT_LENGTH_MIN', 10);       // Minimum characters
define('UDIA_Constants::TEXT_LENGTH_MAX', 2000);     // Maximum characters
```

### Database Optimization

The plugin automatically creates database indexes on activation:

1. **`idx_udia_prod_id`**: Index on `meta_key` and `meta_value` for product-based queries
2. **`idx_udia_rating`**: Index on `meta_key` and `meta_value` for rating aggregations

To manually recreate indexes:
```php
// Run in WordPress context
UDIA_Helpers::create_meta_indexes();
```

### Cache Configuration

#### Fragment Caching
- **Duration**: 10 minutes (600 seconds)
- **Groups**: `udia_fragments`, `udia_reviews`
- **Automatic Clearing**: On review submission/update/deletion
- **Manual Clearing**: Use `wp_cache_flush()` or object cache flush

#### Cache Keys Structure
```
udia_reviews_list_[hash]          // List shortcode output
udia_reviews_carousel_[hash]      // Carousel shortcode output
udia_product_stats_[product_id]_[variation_id]  // Product statistics
udia_global_review_stats          // Global statistics
```

### Security Configuration

#### Rate Limiting
- **Default**: 3 submissions per hour per IP
- **Action-specific**: Different limits per action type
- **Transient-based**: Uses WordPress transients with configurable periods

#### Spam Protection
1. **Honeypot Field**: Hidden form field to catch bots
2. **Keyword Filtering**: Common spam keyword detection
3. **Link Limiting**: Maximum 2 URLs per review
4. **Character Repetition**: Blocks excessive repeated characters

#### Content Security Policy
The plugin adds the following security headers:
```
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:;
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
X-XSS-Protection: 1; mode=block
```

### Styling Configuration

#### CSS Custom Properties (Variables)
```css
:root {
    --udia-accent: #39ff14;        /* Primary accent color (neon green) */
    --udia-text-muted: rgba(255, 255, 255, 0.7); /* Muted text color */
    /* Additional variables defined in assets/css/triqhub-admin.css */
}
```

#### Custom CSS Overrides
Add to theme's `style.css` or custom CSS plugin:
```css
/* Override accent color */
.udia-review-wrap {
    --udia-accent: #ff3366;
}

/* Custom star colors */
.udia-shop-star.filled {
    color: #ff9900;
}
```

## Shortcodes Reference

### 1. Review Form Shortcode
**Shortcode**: `[udia_review_v2_form]`

**Description**: Displays a review submission form for logged-in users.

**Features**:
- Auto-populates user name from WordPress profile
- Fetches last order items for product selection
- Manual product entry fallback
- 5-star rating system
- Honeypot spam protection
- Required content validation (10-2000 characters)

**Output Example**:
```html
<div class="udia-review-wrap">
    <form class="udia-review-form" method="post" aria-label="Formulário de depoimento">
        <!-- Form fields -->
    </form>
</div>
```

**Usage**:
```php
// In page/post editor
[udia_review_v2_form]

// In theme template
echo do_shortcode('[udia_review_v2_form]');
```

### 2. Reviews List Shortcode
**Shortcode**: `[udia_review_v2_list posts_per_page="20"]`

**Parameters**:
- `posts_per_page` (int): Number of reviews to display (default: 20)

**Features**:
- Cached output (10-minute TTL)
- Avatar generation with initials
- Rating display (★ symbols)
- Product association display
- Responsive grid layout

**Output Structure**:
```html
<div id="udia-reviews-list" class="udia-reviews-list">
    <div class="udia-single-review" id="udia-review-{ID}">
        <div class="udia-review-avatar" style="background: hsl(...);">JS</div>
        <div class="udia-review-author-name">John Smith</div>
        <div class="udia-review-rating">★★★★★</div>
        <div class="udia-review-product">Produto: Product Name</div>
        <div class="udia-review-content">Review content...</div>
    </div>
</div>
```

### 3. Reviews Carousel Shortcode
**Shortcode**: `[udia_review_v2_carousel posts_per_page="10"]`

**Parameters**:
- `posts_per_page` (int): Number of slides to display (default: 10)

**Features**:
- Horizontal sliding carousel
- Navigation buttons (prev/next)
- Dot indicators
- Content trimming (20 words)
- Touch/swipe support
- Auto-height adjustment

**JavaScript Requirements**:
```javascript
// Carousel functionality requires:
// - CSS Grid or Flexbox support
// - Touch events for mobile
// - Resize observer for responsiveness
```

### 4. Product Review Summary Shortcode
**Shortcode**: `[udia_product_review_summary product_id="123" variation_id="456"]`

**Parameters**:
- `product_id` (int): WooCommerce product ID (auto-detected on product pages)
- `variation_id` (int): Product variation ID (optional)

**Auto-detection**: On WooCommerce product pages, parameters are automatically populated.

**Output**:
- Star rating display (filled/empty stars)
- Review count in parentheses
- Link to full reviews (when clicked)

**Example Output**:
```html
<div class="udia-product-review-summary">
    <div class="udia-product-stars">
        <span class="udia-product-star filled">★</span>
        <!-- More stars -->
    </div>
    <div class="udia-product-review-count">42</div>
</div>
```

### 5. Product Full Reviews Shortcode
**Shortcode**: `[udia_product_full_reviews product_id="123" posts_per_page="10"]`

**Parameters**:
- `product_id` (int): Product ID (auto-detected)
- `variation_id` (int): Variation ID (optional)
- `posts_per_page` (int): Reviews per page (default: 10)

**Features**:
- Complete review list for specific product
- "No reviews" message when empty
- Cached queries for performance
- Responsive avatar system

### 6. Global Review Summary Shortcode
**Shortcode**: `[udia_global_review_summary]`

**Description**: Displays overall site review statistics with link to reviews page.

**Features**:
- Average rating across all products
- Total review count
- Link to `/feedback/` page (configurable)
- Default 5-star display when no reviews exist

## Shop Display Integration

### Automatic Placement
The plugin automatically adds review summaries to shop pages using the `woocommerce_shop_loop_item_title` hook with priority 5.

**Hook Position**: Between product image and title in WooCommerce loop.

### Customization Options

#### 1. Disable Auto-placement
```php
// In theme's functions.php
remove_action('woocommerce_shop_loop_item_title', array(UDIA_Shop_Display::class, 'display_review_summary'), 5);
```

#### 2. Custom Placement
```php
// Manual placement in theme template
$product = wc_get_product(get_the_ID());
$stats = UDIA_Helpers::get_product_review_stats($product->get_id());
echo UDIA_Shortcodes::product_review_summary_shortcode(array(
    'product_id' => $product->get_id()
));
```

#### 3. Style Customization
The plugin injects inline CSS on shop pages. Override in theme CSS:
```css
/* Custom star size */
.udia-shop-star {
    font-size: 1.2rem !important;
}

/* Different color scheme */
.udia-shop-star.filled {
    color: #ff9900;
    text-shadow: 0 0 8px rgba(255, 153, 0, 0.6);
}

/* Hide review count */
.udia-shop-count {
    display: none;
}
```

### Shop Display CSS Properties
```css
.udia-shop-review-summary {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    margin: 0.5rem 0;
    padding: 0.4rem 0;
    font-size: 0.9rem;
    line-height: 1.2;
}

.udia-shop-star {
    color: #ddd;
    font-size: 1rem;
    line-height: 1;
    transition: color 0.2s ease;
}

.udia-shop-star.filled {
    color: var(--udia-accent, #39ff14);
    text-shadow: 0 0 10px rgba(57, 255, 20, 0.6);
    filter: drop-shadow(0 0 4px rgba(57, 255, 20, 0.4));
}

.udia-shop-count {
    color: var(--udia-accent, #39ff14);
    font-size: 0.9rem;
    font-weight: 700;
    text-shadow: 0 0 10px rgba(57, 255, 20, 0.6);
}
```

## Security Features

### 1. Rate Limiting System
**Configuration**:
```php
// Default: 3 submissions per hour per IP
UDIA_Constants::RATE_LIMIT_REVIEWS_MAX = 3;
UDIA_Constants::RATE_LIMIT_PERIOD = 3600;
```

**Customization**:
```php
// Filter to modify rate limits
add_filter('udia_rate_limit_max', function($max, $action) {
    if ($action === 'review_submit') {
        return 5; // Increase to 5 per hour
    }
    return $max;
}, 10, 2);
```

### 2. Spam Detection
**Methods Implemented**:
1. **Honeypot Field**: Hidden form field ignored by humans
2. **Keyword Filtering**: Blocks common spam terms
3. **URL Limiting**: Maximum 2 URLs per review
4. **Character Pattern Detection**: Blocks excessive repetition

**Spam Keywords List**:
```php
$spam_keywords = array(
    'http://', 'https://', 'www.',
    'casino', 'viagra', 'cialis',
    'buy now', 'click here', 'limited time',
    'make money', 'work from home',
);
```

### 3. Input Validation
**Content Requirements**:
- Minimum length: 10 characters
- Maximum length: 2000 characters
- UTF-8 encoding required
- HTML stripped (plain text only)

**Rating Validation**:
- Integer between 1-5
- Defaults to 5 if invalid
- Sanitized before database storage

### 4. Activity Logging
**Log Structure**:
```php
$log_entry = array(
    'timestamp' => current_time('mysql'),
    'type'      => $type, // 'spam_detected', 'rate_limit_exceeded', etc.
    'ip'        => self::get_client_ip(),
    'user_id'   => get_current_user_id(),
    'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT']),
    'data'      => $data, // Additional context
);
```

**Viewing Logs**:
```php
$logs = get_option('udia_security_log', array());
print_r($logs);
```

### 5. IP Address Detection
**Priority Chain**:
1. Cloudflare (`HTTP_CF_CONNECTING_IP`)
2. Proxy (`HTTP_X_FORWARDED_FOR`)
3. Real IP (`HTTP_X_REAL_IP`)
4. Remote address (`REMOTE_ADDR`)

## Performance Optimization

### Caching Strategy

#### 1. Fragment Caching
```php
// Cache key generation
$cache_key = 'udia_reviews_list_' . md5(serialize($atts));

// Cache retrieval
$cached_output = wp_cache_get($cache_key, 'udia_reviews');

// Cache setting (10 minutes)
wp_cache_set($cache_key, $out, 'udia_reviews', 10 * MINUTE_IN_SECONDS);
```

#### 2. Database Optimization
**Indexes Created**:
```sql
-- For product-based queries
ADD INDEX idx_udia_prod_id (meta_key(20), meta_value(20))

-- For rating aggregations
ADD INDEX idx_udia_rating (meta_key(20), meta_value(5))
```

**Optimized Queries**:
- Uses `fields => 'ids'` to reduce memory usage
- Implements `no_found_rows => true` for count queries
- Disables term and meta caching where unnecessary

#### 3. Asset Optimization
**CSS Features**:
- Minimal inline styles
- CSS custom properties for theming
- Mobile-first responsive design
- Reduced repaint/reflow operations

**JavaScript**:
- Vanilla JS where possible
- Debounced resize handlers
- Efficient DOM querying

### Memory Management

#### Query Optimization
```php
$args = array(
    'post_type'      => 'reviews',
    'post_status'    => 'publish',
    'posts_per_page' => -1