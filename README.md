# Magic Sync Master - Advanced Shopify Synchronization Tool

## Table of Contents

1. [Introduction](#introduction)
2. [Prerequisites](#prerequisites)
3. [Installation](#installation)
4. [Configuration](#configuration)
5. [Running the App](#running-the-app)
6. [Shopify App Setup](#shopify-app-setup)
7. [Extension Setup](#extension-setup)
8. [Usage Guide](#usage-guide)
9. [Features](#features)
10. [Technical Overview](#technical-overview)
11. [Security Considerations](#security-considerations)
12. [Performance Optimization](#performance-optimization)
13. [Troubleshooting](#troubleshooting)
14. [FAQs](#faqs)
15. [Author](#author)

## Introduction

Magic Sync Master is a powerful Shopify app designed to seamlessly synchronize products, customers, and orders across multiple Shopify stores. Developed by Priyansh Rai, this tool is ideal for businesses managing multiple storefronts or dropshipping operations requiring real-time inventory management.

### Key Features
- Real-time synchronization of products, customers, and orders
- User-friendly dashboard for easy management
- Secure data transfer using Shopify's API
- Customizable sync options
- Detailed sync logs and error reporting

## Prerequisites

Ensure you have the following installed:
- PHP 7.4 or higher
- Composer
- Laravel 8.x or higher
- Node.js 14.x or higher and npm
- MySQL 5.7 or higher
- ngrok for local development
- Shopify Partner account

## Installation

1. Clone the repository:
   ```sh
   git clone https://github.com/Priyanshrai/sync-master-shopify
   cd sync-master-shopify
   ```

2. Install PHP dependencies:
   ```sh
   composer install
   ```

3. Install JavaScript dependencies:
   ```sh
   npm install
   ```

4. Set up environment variables:
   ```sh
   cp .env.example .env
   php artisan key:generate
   ```

5. Configure the database in `.env` and run migrations:
   ```sh
   php artisan migrate
   ```

6. Compile assets:
   ```sh
   npm run dev
   ```

## Configuration

1. Update `.env` with your MySQL and Shopify credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=magic_sync_master
   DB_USERNAME=your_username
   DB_PASSWORD=your_password

   SHOPIFY_API_KEY=your_api_key
   SHOPIFY_API_SECRET=your_api_secret
   SHOPIFY_API_SCOPES=read_products,write_products,read_customers,write_customers,read_orders,write_orders
   ```

2. Configure webhook URLs (use your ngrok URL for local development):
   ```env
   SHOPIFY_WEBHOOK_APP_UNINSTALLED_ADDRESS=https://your-url.com/webhook/app-uninstalled
   SHOPIFY_WEBHOOK_PRODUCTS_CREATE_ADDRESS=https://your-url.com/webhook/products-create
   SHOPIFY_WEBHOOK_PRODUCTS_UPDATE_ADDRESS=https://your-url.com/webhook/products-update
   SHOPIFY_WEBHOOK_CUSTOMERS_CREATE_ADDRESS=https://your-url.com/webhook/customers-create
   SHOPIFY_WEBHOOK_CUSTOMERS_UPDATE_ADDRESS=https://your-url.com/webhook/customers-update
   SHOPIFY_WEBHOOK_ORDERS_CREATE_ADDRESS=https://your-url.com/webhook/orders-create
   SHOPIFY_WEBHOOK_ORDERS_UPDATE_ADDRESS=https://your-url.com/webhook/orders-update
   ```

## Running the App

1. For local development, start ngrok:
   ```sh
   ngrok http 8000
   ```

2. Update `APP_URL` in `.env`:
   ```env
   APP_URL=https://your-ngrok-url.ngrok-free.app
   ```

3. Start the Laravel development server:
   ```sh
   php artisan serve
   ```

4. Run the queue worker:
   ```sh
   php artisan queue:work
   ```

5. (Optional) For live reload during development:
   ```sh
   npm run watch
   ```

## Shopify App Setup

1. Create a new app in your [Shopify Partner account](https://partners.shopify.com/).
2. Set the App URL to your application's URL.
3. Configure OAuth:
   - Set Redirect URL to: `https://your-app-url.com/authenticate`
   - Add allowed redirection URL(s) in app settings
4. Update `.env` with your Shopify app's API key and secret.
5. Set up app proxy:
   - Subpath prefix: `apps`
   - Subpath: `proxy`
   - Proxy URL: `https://your-app-url.com/proxy`

## Extension Setup

1. Navigate to the extension directory:
   ```sh
   cd extension
   ```

2. Install dependencies:
   ```sh
   npm install
   ```

3. Deploy the extension:
   ```sh
   npm run shopify app deploy
   ```

## Usage Guide

1. Install the app on your primary Shopify store.
2. Access the dashboard and note your unique Connection ID.
3. Install the app on secondary stores and use the Connection ID to link them.
4. Configure sync settings for products, customers, and orders.
5. Monitor the sync status and logs in the dashboard.

## Features

- Multi-store synchronization
- Real-time and scheduled sync options
- Conflict resolution for simultaneous updates
- Detailed sync logs and error reporting
- Customizable sync rules and filters
- Bulk import/export capabilities

## Technical Overview

- Built with Laravel 8.x and Vue.js
- Uses Laravel Queue for asynchronous processing
- Implements Shopify Webhooks for real-time updates
- Utilizes Shopify App Bridge for seamless store integration

## Security Considerations

- Implements OAuth 2.0 for secure authentication
- Uses HTTPS for all communications
- Implements rate limiting to prevent abuse
- Regularly updates dependencies to patch security vulnerabilities

## Performance Optimization

- Implements caching for frequently accessed data
- Uses database indexing for faster queries
- Optimizes database queries and eager loading relationships
- Implements job batching for large sync operations

## Troubleshooting

- Verify webhook configurations in both `.env` and Shopify app settings.
- Check Laravel logs (`storage/logs/laravel.log`) for detailed error messages.
- Ensure the queue worker is running for background jobs.
- Verify Shopify API scopes are correctly set.

## FAQs

1. **Q: How often does data sync?**
   A: By default, syncing occurs in real-time. You can also configure scheduled syncs.

2. **Q: Is there a limit to how many stores I can connect?**
   A: There's no hard limit, but consider Shopify API rate limits for optimal performance.

3. **Q: What happens to existing data when I connect stores?**
   A: You can choose to perform an initial sync or only sync new data going forward.

4. **Q: Can I customize which data gets synced?**
   A: Yes, the app allows you to set up custom sync rules and filters.

5. **Q: How secure is the data transfer between stores?**
   A: All data is transferred securely using Shopify's API and encrypted connections.


## Support

For support, please email Rai.priyansh007@gmail.com or open an issue on our GitHub repository.

## Author

Magic Sync Master was developed by Priyansh Rai.

For inquiries or support, please contact:
- **Email:** Rai.priyansh007@gmail.com
- **GitHub:** [Priyanshrai](https://github.com/Priyanshrai)

Feel free to reach out with any questions, feature requests, or if you need assistance with the app.
