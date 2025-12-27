# TriqHub: Reviews

![License](https://img.shields.io/badge/License-GPL%20v3-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue)
![WooCommerce](https://img.shields.io/badge/WooCommerce-Required-orange)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)

## Introduction

**TriqHub: Reviews** is a professional, high-performance review system for WooCommerce stores. Built with modern WordPress development practices, this plugin provides a comprehensive solution for collecting, managing, and displaying customer reviews with enterprise-grade security, caching, and performance optimizations.

Designed for e-commerce stores that demand reliability and scalability, TriqHub: Reviews integrates seamlessly with WooCommerce while offering advanced features like automated shop display, carousel reviews, and real-time statistics.

## Features List

### Core Functionality
- **Custom Post Type Integration**: Dedicated 'reviews' CPT with proper WordPress integration
- **WooCommerce Integration**: Automatic product association and order-based review validation
- **Multiple Display Formats**: Form, list, carousel, and summary shortcodes
- **Shop Loop Integration**: Automatic review display in product listings

### Security & Performance
- **Rate Limiting**: IP-based submission limits to prevent abuse
- **Spam Detection**: Advanced honeypot and keyword-based spam filtering
- **Caching System**: Multi-layer fragment and object caching for optimal performance
- **Database Optimization**: Custom indexes for fast query execution
- **Content Security Policy**: Built-in CSP headers for XSS protection

### User Experience
- **Responsive Design**: Mobile-first CSS with dark/light theme compatibility
- **Avatar Generation**: Dynamic colored avatars with user initials
- **Star Rating System**: 1-5 star ratings with visual feedback
- **Product Association**: Automatic product detection from user orders
- **Accessibility**: ARIA labels and keyboard navigation support

### Developer Features
- **Shortcode System**: Six configurable shortcodes for flexible display
- **REST API Ready**: CPT registered with WordPress REST API
- **Translation Ready**: Full text domain support for localization
- **Hook System**: WordPress action/filter integration points
- **Update System**: GitHub-based automatic updates

### Display Components
- **Review Form**: Secure submission form with product selection
- **Review List**: Paginated list of all reviews
- **Review Carousel**: Responsive, navigable carousel display
- **Product Summary**: Compact star rating and count display
- **Full Reviews**: Complete review listing for specific products
- **Global Summary**: Site-wide review statistics

## Quick Start

For detailed installation, configuration, and usage instructions, please refer to the complete [User Guide](docs/USER_GUIDE.md).

### Basic Installation
1. Ensure WooCommerce is installed and active
2. Upload the plugin via WordPress admin or install from GitHub
3. Activate the plugin
4. Use shortcodes to display reviews anywhere on your site

### Example Shortcodes
```php
[udia_review_v2_form]          // Display review submission form
[udia_review_v2_list]          // Show list of all reviews
[udia_review_v2_carousel]      // Display reviews in a carousel
[udia_product_review_summary]  // Show product-specific rating summary
```

## License

This project is licensed under the GNU General Public License v3.0. See the [LICENSE](LICENSE) file for complete details.

- **Open Source**: Free to use, modify, and distribute
- **Commercial Use**: Permitted with proper attribution
- **Warranty**: Provided "as is" without warranty of any kind
- **Contributions**: Welcome via GitHub pull requests

---

*TriqHub: Reviews is maintained by Triq Hub and the open-source community. For support, feature requests, or contributions, please visit our [GitHub repository](https://github.com/gustavofullstack/triqhub-reviews).*