# Magic Sync Master

Magic Sync Master is a Shopify app that allows seamless synchronization of products, customers, and orders between multiple Shopify stores.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Running the App](#running-the-app)
5. [Shopify App Setup](#shopify-app-setup)
6. [Extension Setup](#extension-setup)
7. [Usage](#usage)
8. [Troubleshooting](#troubleshooting)
9. [Contributing](#contributing)
10. [License](#license)

## Prerequisites

Before you begin, ensure you have the following installed:
- PHP (7.4 or higher)
- Composer
- Laravel
- Node.js and npm
- MySQL
- ngrok

## Installation

1. Clone the repository:
    ```sh
    git clone https://github.com/Priyanshrai/sync-master-shopify
    ```

2. Install PHP dependencies:
    ```sh
    composer install
    ```

3. Copy the `.env.example` file to `.env` and update the necessary environment variables:
    ```sh
    cp .env.example .env
    ```

4. Generate an application key:
    ```sh
    php artisan key:generate
    ```

5. Run database migrations:
    ```sh
    php artisan migrate
    ```

## Configuration

1. Update the `.env` file with your MySQL credentials:
    ```env
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=magic_sync_master
    DB_USERNAME=root
    DB_PASSWORD=
    ```

2. Set up Shopify API credentials in the `.env` file:
    ```env
    SHOPIFY_APP_NAME="Magic Sync Master"
    SHOPIFY_API_KEY="your_api_key"
    SHOPIFY_API_SECRET="your_api_secret"
    SHOPIFY_API_SCOPES=read_products,write_products,read_customers,write_customers,read_orders,write_orders
    ```

3. Configure webhook URLs (replace with your ngrok URL):
    ```env
    SHOPIFY_WEBHOOK_APP_UNINSTALLED_ADDRESS=https://your-ngrok-url.ngrok-free.app/webhook/app-uninstalled
    SHOPIFY_WEBHOOK_PRODUCTS_CREATE_ADDRESS=https://your-ngrok-url.ngrok-free.app/webhook/products-create
    SHOPIFY_WEBHOOK_PRODUCTS_UPDATE_ADDRESS=https://your-ngrok-url.ngrok-free.app/webhook/products-update
    SHOPIFY_WEBHOOK_CUSTOMERS_CREATE_ADDRESS=https://your-ngrok-url.ngrok-free.app/webhook/customers-create
    SHOPIFY_WEBHOOK_CUSTOMERS_UPDATE_ADDRESS=https://your-ngrok-url.ngrok-free.app/webhook/customers-update
    SHOPIFY_WEBHOOK_ORDERS_CREATE_ADDRESS=https://your-ngrok-url.ngrok-free.app/webhook/orders-create
    SHOPIFY_WEBHOOK_ORDERS_UPDATE_ADDRESS=https://your-ngrok-url.ngrok-free.app/webhook/orders-update
    ```

## Running the App

1. Start ngrok:
    ```sh
    ngrok http 8000
    ```

2. Update the `APP_URL` in `.env` with your ngrok URL:
    ```env
    APP_URL=https://your-ngrok-url.ngrok-free.app
    ```

3. Start the MySQL server (using XAMPP or your preferred method).

4. Run the Laravel development server:
    ```sh
    php artisan serve
    ```

5. In a separate terminal, start the queue worker:
    ```sh
    php artisan queue:work
    ```

## Shopify App Setup

1. Create a new app in your [Shopify Partner account](https://partners.shopify.com/).

2. Set the App URL to your ngrok URL.

3. In the App Configuration settings, set the Redirect URL to:
    ```sh
    https://your-ngrok-url.ngrok-free.app/authenticate
    ```

4. Configure the Allowed redirection URL(s) in your Shopify app settings.

5. Update the `.env` file with your Shopify app's API key and secret.

6. Set up the proxy in the partner settings to:
    ```sh
    https://your-ngrok-url.ngrok-free.app
    ```

7. For the app proxy, configure the following in your Shopify Partner account under "App proxy":

    - Subpath prefix: `apps`
    - Subpath: `proxy`
    - Proxy URL: `https://your-ngrok-url.ngrok-free.app/proxy`

    This configuration fetches and displays data on a store from your app. Learn more about app proxy in the [Shopify documentation](https://shopify.dev/docs/admin-api/rest/reference/online-store/app-proxy).

## Extension Setup

1. Navigate to the extension directory:
    ```sh
    cd extension
    ```

2. Install npm dependencies:
    ```sh
    npm install
    ```

3. Deploy the extension:
    ```sh
    npm run shopify app deploy
    ```

## Usage

1. Install the app on a Shopify store.
2. Use the dashboard to connect multiple stores.
3. Products, customers, and orders will automatically sync between connected stores.

## Troubleshooting

- If you encounter any issues with webhooks, ensure your ngrok URL is correctly set in both the `.env` file and Shopify app settings.
- Check the Laravel logs (`storage/logs/laravel.log`) for any error messages.



