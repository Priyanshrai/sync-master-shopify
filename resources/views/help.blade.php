@extends('shopify-app::layouts.default')

@section('content')
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h1 class="mb-4">Help Center - Magic Sync Master</h1>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="card-title">How to Use Magic Sync Master</h2>
                        <ol>
                            <li>
                                <strong>Connecting Stores:</strong>
                                <ul>
                                    <li>On the dashboard, find your unique Connection ID.</li>
                                    <li>Share this ID with another store owner who wants to connect.</li>
                                    <li>To connect to another store, enter their Connection ID in the "Connect to Store" form and click "Connect".</li>
                                </ul>
                            </li>
                            <li>
                                <strong>Syncing Data:</strong>
                                <ul>
                                    <li>Once connected, products, customers, and orders will automatically sync between stores.</li>
                                    <li>New data will be synced in real-time.</li>
                                </ul>
                            </li>
                            <li>
                                <strong>Disconnecting Stores:</strong>
                                <ul>
                                    <li>To disconnect from a store, click the "Disconnect" button next to the store's name in the Connected Stores list.</li>
                                </ul>
                            </li>
                        </ol>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="card-title">Frequently Asked Questions</h2>
                        <dl>
                            <dt>Is my data secure?</dt>
                            <dd>Yes, all data is transferred securely between stores using Shopify's APIs.</dd>

                            <dt>Can I connect to multiple stores?</dt>
                            <dd>Yes, you can connect to as many stores as you need.</dd>

                            <dt>What happens if I disconnect a store?</dt>
                            <dd>Disconnecting will stop any future syncing, but previously synced data will remain.</dd>
                        </dl>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="card-title">Need More Help?</h2>
                        <p>If you need further assistance, please contact our support team at support@magicsyncmaster.com</p>
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
    </script>
@endsection