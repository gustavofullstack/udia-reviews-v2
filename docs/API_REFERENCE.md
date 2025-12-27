# TriqHub Reviews Plugin - API Reference

## Overview

The TriqHub Reviews plugin provides a comprehensive review system for WooCommerce stores with form submission, display components, and integration capabilities. This document details all public APIs, hooks, filters, and endpoints available for developers.

## Table of Contents

1. [Constants & Configuration](#constants--configuration)
2. [Shortcodes](#shortcodes)
3. [Helper Functions](#helper-functions)
4. [Security Functions](#security-functions)
5. [Custom Post Type](#custom-post-type)
6. [WordPress Hooks & Filters](#wordpress-hooks--filters)
7. [AJAX Endpoints](#ajax-endpoints)
8. [Cache Management](#cache-management)
9. [Shop Display Integration](#shop-display-integration)

## Constants & Configuration

### Plugin Constants

| Constant | Value | Description | Scope |
|----------|-------|-------------|-------|
| `UDIA_REVIEW_V2_VERSION` | `'1.0.0'` | Plugin version | Global |
| `UDIA_REVIEW_V2_PATH` | Plugin directory path | Absolute path to plugin directory | Global |
| `UDIA_REVIEW_V2_URL` | Plugin URL | Full URL to plugin directory | Global |
| `UDIA_REVIEW_V2_LOADED` | `true` | Prevents duplicate loading | Global |

### Class Constants (UDIA_Constants)

| Constant | Type | Default Value | Description |
|----------|------|---------------|-------------|
| `TEXT_DOMAIN` | string | `'udia-reviews-v2'` | Text domain for translations |
| `CPT_SLUG` | string | `'reviews'` | Custom Post Type slug |
| `CACHE_GROUP_REVIEWS` | string | `'udia_reviews'` | Cache group for review data |
| `CACHE_GROUP_FRAGMENTS` | string | `'udia_fragments'` | Cache group for HTML fragments |
| `CACHE_TTL_FRAGMENT` | int | `600` | Fragment cache TTL (10 minutes) |
| `RATE_LIMIT_REVIEWS_MAX` | int | `5` | Maximum review submissions per period |
| `RATE_LIMIT_PERIOD` | int | `3600` | Rate limit period in seconds (1 hour) |
| `SECURITY_LOG_MAX` | int | `100` | Maximum security log entries |
| `TEXT_LENGTH_MIN` | int | `10` | Minimum review text length |
| `TEXT_LENGTH_MAX` | int | `2000` | Maximum review text length |

### Initialization Methods

```php
// Initialize constants
UDIA_Constants::init( string $plugin_file ): void

// Get plugin path
UDIA_Constants::get_plugin_path(): string

// Get plugin URL
UDIA_Constants::get_plugin_url(): string
```

## Shortcodes

### 1. Review Form Shortcode

**Shortcode:** `[udia_review_v2_form]`

**Description:** Displays a review submission form for logged-in users.

**Output:**
- For logged-out users: Login prompt with link
- For logged-in users: Review form with:
  - Pre-filled name field (from user profile)
  - Product selector (from last order)
  - Manual product input field
  - 5-star rating system
  - Review content textarea
  - Honeypot anti-spam field

**Example:**
```php
echo do_shortcode('[udia_review_v2_form]');
```

### 2. Review List Shortcode

**Shortcode:** `[udia_review_v2_list posts_per_page="20"]`

**Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `posts_per_page` | int | `20` | Number of reviews to display |

**Features:**
- Cached output (10-minute TTL)
- Responsive design
- Avatar generation with initials
- Product association display

**Example:**
```php
echo do_shortcode('[udia_review_v2_list posts_per_page="10"]');
```

### 3. Review Carousel Shortcode

**Shortcode:** `[udia_review_v2_carousel posts_per_page="10"]`

**Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `posts_per_page` | int | `10` | Number of reviews in carousel |

**Features:**
- Responsive carousel with navigation
- Trimmed content preview
- Cached output (10-minute TTL)
- Touch/swipe support

**Example:**
```php
echo do_shortcode('[udia_review_v2_carousel posts_per_page="5"]');
```

### 4. Product Review Summary Shortcode

**Shortcode:** `[udia_product_review_summary product_id="0" variation_id="0"]`

**Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `product_id` | int | `0` | WooCommerce product ID (auto-detected on product pages) |
| `variation_id` | int | `0` | Product variation ID |

**Output:**
- Star rating display (1-5 stars)
- Review count
- Auto-detects current product on single product pages

**Example:**
```php
// Manual product ID
echo do_shortcode('[udia_product_review_summary product_id="123"]');

// Auto-detect on product page
echo do_shortcode('[udia_product_review_summary]');
```

### 5. Product Full Reviews Shortcode

**Shortcode:** `[udia_product_full_reviews product_id="0" variation_id="0" posts_per_page="10"]`

**Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `product_id` | int | `0` | Product ID (auto-detected) |
| `variation_id` | int | `0` | Variation ID |
| `posts_per_page` | int | `10` | Number of reviews to display |

**Features:**
- Complete review list for specific product
- Cached output
- Fallback message for no reviews

**Example:**
```php
echo do_shortcode('[udia_product_full_reviews posts_per_page="15"]');
```

### 6. Global Review Summary Shortcode

**Shortcode:** `[udia_global_review_summary]`

**Description:** Displays global review statistics across all products.

**Features:**
- Links to review page (configurable)
- Global average rating
- Total review count
- Defaults to 5.0 rating if no reviews exist

**Example:**
```php
echo do_shortcode('[udia_global_review_summary]');
```

## Helper Functions

### UDIA_Helpers Class

#### 1. Fragment Caching

```php
UDIA_Helpers::get_cached_fragment(
    string $key,
    callable $callback,
    int $ttl = 600
): string
```

**Parameters:**
- `$key`: Unique cache identifier
- `$callback`: Function that generates content
- `$ttl`: Time to live in seconds (default: 600)

**Example:**
```php
$html = UDIA_Helpers::get_cached_fragment(
    'my_reviews_list',
    function() {
        return do_shortcode('[udia_review_v2_list]');
    },
    300 // 5 minutes
);
```

#### 2. Database Index Creation

```php
UDIA_Helpers::create_meta_indexes(): void
```

**Description:** Creates database indexes for optimal query performance. Called on plugin activation.

**Creates indexes:**
- `idx_udia_prod_id` on `postmeta` table for `prod_id` queries
- `idx_udia_rating` on `postmeta` table for rating aggregations

#### 3. Avatar Generation

```php
UDIA_Helpers::generate_initials_avatar(
    string $name,
    int|null $user_id = null
): array
```

**Returns:**
```php
array(
    'initials'   => string,  // 2-character initials
    'bg_color'   => string,  // HSL background color
    'text_color' => string   // Always '#ffffff'
)
```

**Example:**
```php
$avatar = UDIA_Helpers::generate_initials_avatar('John Doe', 123);
// Returns: ['initials' => 'JD', 'bg_color' => 'hsl(120, 70%, 50%)', 'text_color' => '#ffffff']
```

#### 4. Product Review Queries

```php
UDIA_Helpers::get_product_reviews(
    int $product_id,
    int $variation_id = 0,
    int $posts_per_page = -1
): WP_Query
```

**Parameters:**
- `$product_id`: Main product ID
- `$variation_id`: Variation ID (optional)
- `$posts_per_page`: Number of reviews (-1 for all)

**Returns:** `WP_Query` object with review posts

#### 5. Product Review Statistics

```php
UDIA_Helpers::get_product_review_stats(
    int $product_id,
    int $variation_id = 0
): array
```

**Returns:**
```php
array(
    'average_rating' => float,  // 0-5, rounded to 1 decimal
    'total_reviews'  => int     // Total review count
)
```

**Cache:** 10 minutes

#### 6. Global Review Statistics

```php
UDIA_Helpers::get_global_review_stats(): array
```

**Returns:**
```php
array(
    'average_rating' => float,  // 1-5, defaults to 5.0 if no reviews
    'total_reviews'  => int     // Total reviews across all products
)
```

**Cache:** 10 minutes

#### 7. Cache Management

```php
// Clear caches when new review is published
UDIA_Helpers::clear_caches_on_new_review(
    int $post_id,
    WP_Post $post,
    bool $update
): void

// Clear global cache on review status change
UDIA_Helpers::clear_global_cache_on_review_change(
    int $post_id,
    WP_Post $post_after,
    WP_Post|null $post_before = null
): void
```

## Security Functions

### UDIA_Security Class

#### 1. Rate Limiting

```php
UDIA_Security::check_ip_rate_limit(
    string $ip,
    string $action = 'review_submit',
    int|null $max = null,
    int|null $period = null
): bool
```

**Parameters:**
- `$ip`: Client IP address
- `$action`: Action identifier
- `$max`: Maximum attempts (default: 5)
- `$period`: Time period in seconds (default: 3600)

**Returns:** `true` if within limit, `false` if exceeded

**Example:**
```php
$allowed = UDIA_Security::check_ip_rate_limit(
    '192.168.1.1',
    'review_submit',
    3,    // 3 attempts max
    1800  // 30 minutes
);
```

#### 2. Activity Logging

```php
UDIA_Security::log_suspicious_activity(
    string $type,
    array $data = []
): void
```

**Logs to:**
- WordPress option `udia_security_log` (last 100 entries)
- PHP error log (when WP_DEBUG is enabled)

**Filter:** `udia_log_suspicious_activity` - Control logging behavior

#### 3. Client IP Detection

```php
UDIA_Security::get_client_ip(): string
```

**Detection order:**
1. Cloudflare (`HTTP_CF_CONNECTING_IP`)
2. Proxy (`HTTP_X_FORWARDED_FOR`)
3. Real IP (`HTTP_X_REAL_IP`)
4. Remote address (`REMOTE_ADDR`)

#### 4. Text Validation

```php
UDIA_Security::validate_text_length(
    string $text,
    int|null $min = null,
    int|null $max = null
): bool
```

**Default limits:**
- Minimum: 10 characters
- Maximum: 2000 characters

#### 5. Spam Detection

```php
UDIA_Security::is_likely_spam(string $text): bool
```

**Detection criteria:**
- Spam keywords (casino, viagra, etc.)
- Excessive links (>2)
- Repeated characters (10+ repetitions)

#### 6. Security Headers

```php
UDIA_Security::add_csp_headers(): void
```

**Sets headers:**
- Content-Security-Policy
- X-Content-Type-Options: nosniff
- X-Frame-Options: SAMEORIGIN
- X-XSS-Protection: 1; mode=block

## Custom Post Type

### Registration

**Post Type:** `reviews`

**Labels:** Fully translated with Portuguese (Brazil) defaults

**Supports:**
- Title
- Editor (content)
- Thumbnail
- Custom fields

**Configuration:**
- Public: Yes
- Menu icon: `dashicons-star-filled`
- Has archive: Yes
- Rewrite slug: `reviews`
- Show in REST API: Yes

### Meta Fields

| Meta Key | Type | Description |
|----------|------|-------------|
| `rating` | int | Star rating (1-5) |
| `prod_id` | int | Associated product ID |
| `variation_id` | int | Product variation ID |
| `product_label` | string | Display name for product |
| `order_item_id` | int | WooCommerce order item ID |

## WordPress Hooks & Filters

### Actions

#### 1. Plugin Initialization
```php
add_action('plugins_loaded', 'udia_v2_init');
```

**Function:** `udia_v2_init()`
- Loads text domain
- Initializes core functionality if WooCommerce is active

#### 2. Plugin Activation
```php
register_activation_hook(__FILE__, 'udia_v2_activate');
```

**Function:** `udia_v2_activate()`
- Creates database indexes
- Flushes rewrite rules

#### 3. Review Display in Shop Loop
```php
add_action('woocommerce_shop_loop_item_title', 'display_review_summary', 5);
```

**Class:** `UDIA_Shop_Display::display_review_summary()`
- Displays star ratings in product listings
- Position: After image, before title

#### 4. Cache Management Hooks
```php
// Clear caches on new review
add_action('wp_insert_post', ['UDIA_Helpers', 'clear_caches_on_new_review'], 10, 3);

// Clear global cache on review change
add_action('wp_insert_post', ['UDIA_Helpers', 'clear_global_cache_on_review_change'], 11, 3);

// Clear cache on review deletion
add_action('delete_post', $callback, 11);
```

#### 5. Admin Styling
```php
add_action('admin_enqueue_scripts', 'triqhub_enqueue_admin_udia_reviews_v2');
```

**Function:** `triqhub_enqueue_admin_udia_reviews_v2()`
- Enqueues TriqHub admin CSS

#### 6. Shop Display Styles
```php
add_action('wp_head', ['UDIA_Shop_Display', 'add_shop_styles']);
```

**Conditional:** Only on shop pages (shop, category, tag, taxonomy)

### Filters

#### 1. Suspicious Activity Logging
```php
apply_filters('udia_log_suspicious_activity', bool $should_log, string $type)
```

**Parameters:**
- `$should_log`: Current logging decision
- `$type`: Activity type identifier

**Example:**
```php
add_filter('udia_log_suspicious_activity', function($should_log, $type) {
    if ($type === 'review_spam') {
        return true; // Always log spam attempts
    }
    return $should_log;
}, 10, 2);
```

## AJAX Endpoints

### Endpoint Structure

**Base URL:** `admin-ajax.php`

**Action Parameter:** `udia_v2_action`

### Available Actions

#### 1. Submit Review
**Action:** `submit_review`

**Required POST data:**
```php
array(
    'name'           => string,  // Reviewer name
    'order_item_id'  => int,     // Order item ID (or 0)
    'manual_product' => string,  // Manual product name
    'rating'         => int,     // 1-5
    'content'        => string,  // Review text
    'nonce'          => string   // WordPress nonce
)
```

**Security checks:**
- Nonce verification
- Rate limiting
- Honeypot field validation
- Text length validation
- Spam detection

**Response:**
```json
{
    "success": true,
    "message": "Review submitted successfully",
    "review_id": 123
}
```

#### 2. Get User's Last Order Items
**Action:** `get_last_order_items`

**Returns:** JSON array of order items for current user

**Response:**
```json
[
    {
        "id": 456,
        "name": "Product Name - Variation",
        "product_id": 123,
        "variation_id": 0
    }
]
```

### AJAX Handler Class: UDIA_AJAX

**Methods:**
- `handle_ajax()`: Main AJAX router
- `submit_review()`: Review submission handler
- `get_last_order_items()`: Order items fetcher
- `verify_nonce()`: Security verification
- `sanitize_review_data()`: Data sanitization

## Cache Management

### Cache Groups

| Group | Purpose | TTL |
|-------|---------|-----|
| `udia_reviews` | Review data and statistics | 10 minutes |
| `udia_fragments` | HTML fragment caching | Configurable |

### Cache Keys