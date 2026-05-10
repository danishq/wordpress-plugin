# AdFlipr Integration Plugin

A WordPress plugin to integrate WooCommerce with AdFlipr for sending products, orders, subscriptions, and coupon data for email and digital marketing.

## Quick Start

To create a deployable WordPress plugin zip file:

```bash
# First build the React application
npm install
npm run build

# Then run the packaging script
./create-plugin-zip.sh
```

This script will automatically package all necessary files into `adflipr.zip` ready for WordPress installation.

## Documentation

- **Runtime behavior (hooks, REST, webhooks, security notes):** [`docs/PLUGIN_BEHAVIOR.md`](docs/PLUGIN_BEHAVIOR.md)

## Features

- **WooCommerce Integration**: Automatically sends product, order, subscription, and coupon data to AdFlipr
- **Cart Tracking**: Monitors shopping cart activities for better marketing insights
- **Transactional Emails**: Enhanced email marketing capabilities
- **Authentication System**: Secure connection with AdFlipr services
- **React Dashboard**: Modern UI built with React for managing AdFlipr integration

## Installation

1. Upload the `adflipr` plugin directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'AdFlipr' in the WordPress admin menu
4. Enter your AdFlipr credentials to authenticate

## Configuration

After installation, you need to authenticate with your AdFlipr account:

1. Go to "AdFlipr" in the WordPress admin sidebar
2. Enter your AdFlipr email and password
3. Click "Authenticate"
4. Upon successful authentication, your WooCommerce data will be automatically synchronized

## React App Integration

This plugin includes a React application built with WordPress Scripts.

### Building the React App

To build the React application:

1. Navigate to the plugin's root directory:

   ```
   cd /path/to/plugins/adflipr
   ```

2. Install the Node.js dependencies:

   ```
   npm install
   ```

3. Build the React application:
   ```
   npm run build
   ```

### Development

To start development mode with hot reloading:

```
npm run start
```

This will watch your source files and rebuild automatically when changes are detected.

### Usage

#### Shortcode

Use the shortcode `[adflipr_react_app]` to display the React app on any post or page.

Example:

```
[adflipr_react_app]
```

#### Admin Page

The plugin also adds a React App submenu to the AdFlipr admin section. Navigate to "AdFlipr > React App" in the WordPress admin sidebar.

### Structure

- `src/` - Contains the React application source code
- `build/` - Contains the compiled JavaScript and CSS (generated after running `npm run build`)
- `includes/` - Contains the PHP classes that power the plugin
  - `class-adflipr-admin-page.php` - Handles admin UI and React integration
  - `class-adflipr-auth.php` - Authentication system
  - `class-adflipr-data.php` - Data synchronization with AdFlipr
  - `class-adflipr-cart-tracker.php` - Tracks shopping cart activities
  - `class-adflipr-transactional-email.php` - Email functionality
- `assets/js/` - Contains additional JavaScript files for the plugin
  - `adflipr-cart-tracker.js` - Frontend JavaScript for cart tracking

### Customization

To modify the React application, edit the files in the `src/` directory and rebuild the application using `npm run build` or start the development server with `npm run start`.

## Dependencies

The React application uses:

- WordPress Scripts (@wordpress/scripts)
- WordPress Element (@wordpress/element)
- Material UI (@mui/material)
- Emotion for styling (@emotion/react, @emotion/styled)

## Troubleshooting

If you encounter authentication issues:

1. Open **WordPress Admin → AdFlipr**
2. Use **Disconnect AdFlipr on this site** (nonce-protected). Alternatively call `wp_nonce_url( admin_url( 'admin.php?page=adflipr-settings&reset_token=1' ), 'adflipr_reset_token' )` from code.
3. Re-authenticate with your credentials from the AdFlipr UI.

## Support

For support, please contact the AdFlipr support team or open an issue on the plugin repository.

## License

This plugin is licensed under the GPL v3 or later.
