@extends('shopify-app::layouts.default')

@section('content')
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-3">
            <div class="list-group" id="helpTopics">
                <a href="#getting-started" class="list-group-item list-group-item-action active">Getting Started</a>
                <a href="#connecting-stores" class="list-group-item list-group-item-action">Connecting Stores</a>
                <a href="#syncing-data" class="list-group-item list-group-item-action">Syncing Data</a>
                <a href="#managing-connections" class="list-group-item list-group-item-action">Managing Connections</a>
                <a href="#troubleshooting" class="list-group-item list-group-item-action">Troubleshooting</a>
                <a href="#faq" class="list-group-item list-group-item-action">FAQ</a>
            </div>
        </div>
        <div class="col-md-9">
            <h1 class="mb-4">Help Center - Magic Sync Master</h1>
            
            <div id="getting-started" class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="card-title">Getting Started</h2>
                    <p>Welcome to Magic Sync Master! This app allows you to easily sync products, customers, and orders between multiple Shopify stores. Here's how to get started:</p>
                    <ol>
                        <li>Install Magic Sync Master on all the Shopify stores you want to sync.</li>
                        <li>Navigate to the Dashboard to find your unique Connection ID.</li>
                        <li>Share this Connection ID with the owners of the stores you want to connect with.</li>
                        <li>Use the Connection IDs from other stores to establish connections.</li>
                    </ol>
                </div>
            </div>

            <div id="connecting-stores" class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="card-title">Connecting Stores</h2>
                    <p>To connect your store with another:</p>
                    <ol>
                        <li>Go to the Dashboard and find the "Connect to a New Store" section.</li>
                        <li>Enter the Connection ID of the store you want to connect with.</li>
                        <li>Click "Connect to Store".</li>
                        <li>Once connected, you'll see the store listed under "Connected Stores".</li>
                    </ol>
                    <p>Remember, both stores need to have Magic Sync Master installed for the connection to work.</p>
                </div>
            </div>

            <div id="syncing-data" class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="card-title">Syncing Data</h2>
                    <p>Once stores are connected, data syncing happens automatically:</p>
                    <ul>
                        <li><strong>Products:</strong> New products and updates to existing products are synced in real-time.</li>
                        <li><strong>Customers:</strong> Customer information is shared across connected stores.</li>
                        <li><strong>Orders:</strong> New orders are synced to maintain consistent inventory across stores.</li>
                    </ul>
                    <p>All synced items are tagged with the source store's URL for easy identification.</p>
                </div>
            </div>

            <div id="managing-connections" class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="card-title">Managing Connections</h2>
                    <p>You can manage your store connections from the Dashboard:</p>
                    <ul>
                        <li>View all connected stores under the "Connected Stores" section.</li>
                        <li>To disconnect a store, click the "Disconnect" button next to the store name.</li>
                        <li>Disconnecting will stop future syncing, but previously synced data will remain.</li>
                    </ul>
                </div>
            </div>

            <div id="troubleshooting" class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="card-title">Troubleshooting</h2>
                    <p>If you encounter any issues:</p>
                    <ul>
                        <li>Ensure all connected stores have Magic Sync Master installed and up to date.</li>
                        <li>Check that your Shopify plan supports the number of products, customers, and orders you're syncing.</li>
                        <li>Verify that you have the necessary permissions in your Shopify admin settings.</li>
                        <li>If problems persist, please contact our support team.</li>
                    </ul>
                </div>
            </div>

            <div id="faq" class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="card-title">Frequently Asked Questions</h2>
                    <dl>
                        <dt>Is my data secure?</dt>
                        <dd>Yes, all data is transferred securely using Shopify's APIs and our own secure protocols.</dd>

                        <dt>Can I connect to multiple stores?</dt>
                        <dd>Yes, you can connect to as many stores as you need, as long as they all have Magic Sync Master installed.</dd>

                        <dt>What happens if I disconnect a store?</dt>
                        <dd>Disconnecting will stop future syncing, but previously synced data will remain in both stores.</dd>

                        <dt>How often does syncing occur?</dt>
                        <dd>Syncing occurs in real-time. As soon as a change is made in one store, it's synced to connected stores.</dd>

                        <dt>Can I choose what to sync?</dt>
                        <dd>Currently, Magic Sync Master syncs all products, customers, and orders. We're working on selective sync features for future updates.</dd>

                        <dt>What happens if the same product exists in both stores?</dt>
                        <dd>The app uses unique identifiers to match products. If a product exists in both stores, it will be updated rather than duplicated.</dd>

                        <dt>Is there a limit to how much data can be synced?</dt>
                        <dd>The sync capacity depends on your Shopify plan. Make sure your plan can accommodate the volume of data you're syncing.</dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="card-title">Need More Help?</h2>
                    <p>If you need further assistance or have questions not covered here, please don't hesitate to contact me Rai.priyansh007@gmail.com. </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    @parent
    <script type="text/javascript">
        actions.TitleBar.create(app, { title: 'Help Center' });
        var createApp = window['app-bridge'].default;
        var actions = window['app-bridge'].actions;
        var AppLink = actions.AppLink;
        var NavigationMenu = actions.NavigationMenu;

        var app = createApp({
            apiKey: "{{ \Osiset\ShopifyApp\Util::getShopifyConfig('api_key', $shopDomain ?? Auth::user()->name ) }}",
            host: "{{ \Request::get('host') }}",
            forceRedirect: true,
        });

        const dashboardLink = AppLink.create(app, {
            label: 'Dashboard',
            destination: '/',
        });

        const helpLink = AppLink.create(app, {
            label: 'Help',
            destination: '/help',
        });

        const navigationMenu = NavigationMenu.create(app, {
            items: [dashboardLink, helpLink],
            active: helpLink,
        });

        navigationMenu.subscribe(NavigationMenu.Action.LINK_UPDATE, (payload) => {
            const { url } = payload;
            window.location.href = url;
        });

        document.addEventListener('DOMContentLoaded', function() {
            const helpTopics = document.getElementById('helpTopics');
            const topicLinks = helpTopics.getElementsByTagName('a');

            for (let link of topicLinks) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href').substring(1);
                    const targetElement = document.getElementById(targetId);
                    targetElement.scrollIntoView({behavior: 'smooth'});

                    // Update active class
                    for (let l of topicLinks) {
                        l.classList.remove('active');
                    }
                    this.classList.add('active');
                });
            }

            // Update active topic on scroll
            window.addEventListener('scroll', function() {
                let fromTop = window.scrollY + 100; // Offset for better UX

                for (let link of topicLinks) {
                    let section = document.querySelector(link.hash);

                    if (
                        section.offsetTop <= fromTop &&
                        section.offsetTop + section.offsetHeight > fromTop
                    ) {
                        link.classList.add('active');
                    } else {
                        link.classList.remove('active');
                    }
                }
            });
        });
    </script>
@endsection