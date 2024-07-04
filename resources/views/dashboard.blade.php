@extends('shopify-app::layouts.default')

@section('content')
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="alert alert-info">
                    <strong>Your Store:</strong> {{ $shopDomain }}
                </div>
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Your Connection ID</h5>
                        <div class="input-group">
                            <input type="text" class="form-control" id="connectionId" value="{{ $connectionId }}" readonly>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" id="copyBtn">Copy</button>
                            </div>
                        </div>
                        <small class="text-muted">Share this ID with another store to connect.</small>
                    </div>
                </div>
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Connected Stores</h5>
                        @if(count($connectedShops) > 0)
                            <ul class="list-group">
                                @foreach($connectedShops as $connectedShop)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        {{ $connectedShop }}
                                        <button class="btn btn-danger btn-sm disconnectBtn" data-shop="{{ $connectedShop }}">Disconnect</button>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p>Not connected to any stores.</p>
                        @endif
                        <div id="connectForm" class="mt-3">
                            <div class="form-group">
                                <input type="text" id="connectionIdInput" class="form-control" placeholder="Enter Connection ID" required maxlength="10">
                                <div class="invalid-feedback">Please enter a valid Connection ID.</div>
                            </div>
                            <button id="connectBtn" class="btn btn-primary btn-block">Connect to Store</button>
                        </div>
                    </div>
                </div>
                <div id="message" class="alert" style="display: none;"></div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @parent
    <script type="text/javascript">
        actions.TitleBar.create(app, { title: 'Magic Sync Master' });
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
                    active: dashboardLink,
                });

                navigationMenu.subscribe(NavigationMenu.Action.LINK_UPDATE, (payload) => {
                    const { url } = payload;
                    window.location.href = url;
                });

        function showMessage(message, isError = false) {
            const messageElement = document.getElementById('message');
            messageElement.textContent = message;
            messageElement.classList.remove('alert-success', 'alert-danger');
            messageElement.classList.add(isError ? 'alert-danger' : 'alert-success');
            messageElement.style.display = 'block';

            // Auto-hide message after 5 seconds
            setTimeout(() => {
                messageElement.style.display = 'none';
            }, 5000);
        }

        function authenticatedFetch(url, method, data = {}) {
            return new Promise((resolve, reject) => {
                utils.getSessionToken(app)
                    .then(token => {
                        fetch(url, {
                            method: method,
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'Authorization': `Bearer ${token}`,
                            },
                            body: method !== 'GET' ? JSON.stringify(data) : undefined,
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                resolve(data);
                            } else {
                                reject(data);
                            }
                        })
                        .catch(error => reject(error));
                    })
                    .catch(reject);
            });
        }

        function connect() {
            const connectionIdInput = document.getElementById('connectionIdInput');
            const connectionId = connectionIdInput.value.trim();
            
            if (!connectionId || connectionId.length !== 10) {
                connectionIdInput.classList.add('is-invalid');
                showMessage('Connection ID must be exactly 10 characters long.', true);
                return;
            }
            
            connectionIdInput.classList.remove('is-invalid');
            
            authenticatedFetch('{{ route('connect') }}', 'POST', { connection_id: connectionId })
                .then(response => {
                    if (response.success) {
                        showMessage('Successfully connected to ' + response.connected_shop);
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        throw new Error(response.error);
                    }
                })
                .catch(error => {
                    let errorMessage = 'Connection failed: ';
                    if (error.message) {
                        errorMessage += error.message;
                    } else if (error.error) {
                        errorMessage += error.error;
                    } else {
                        errorMessage += 'Unknown error occurred';
                    }
                    showMessage(errorMessage, true);
                    console.error('Error:', error);
                });
        }

        function disconnect(shopDomain) {
            authenticatedFetch('{{ route('disconnect') }}', 'POST', { shop_domain: shopDomain })
                .then(response => {
                    if (response.success) {
                        showMessage('Successfully disconnected from ' + shopDomain);
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        throw new Error(response.error || 'Unknown error occurred');
                    }
                })
                .catch(error => {
                    showMessage('Disconnection failed: ' + error.message, true);
                    console.error('Error:', error);
                });
        }

        function copyConnectionId() {
            const connectionId = document.getElementById('connectionId');
            connectionId.select();
            document.execCommand('copy');
            showMessage('Connection ID copied to clipboard');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const connectBtn = document.getElementById('connectBtn');
            if (connectBtn) {
                connectBtn.addEventListener('click', connect);
            }

            const copyBtn = document.getElementById('copyBtn');
            if (copyBtn) {
                copyBtn.addEventListener('click', copyConnectionId);
            }

            const connectionIdInput = document.getElementById('connectionIdInput');
            if (connectionIdInput) {
                connectionIdInput.addEventListener('input', function() {
                    this.classList.remove('is-invalid');
                });
            }

            const disconnectBtns = document.querySelectorAll('.disconnectBtn');
            disconnectBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const shopDomain = this.getAttribute('data-shop');
                    disconnect(shopDomain);
                });
            });
        });
    </script>
@endsection