@extends('shopify-app::layouts.default')

@section('content')
    <div class="container mt-5">
        <h1>Magic Sync Master Dashboard</h1>
        <p>You are: {{ $shopDomain }}</p>
        <div class="card mt-4">
            <div class="card-body">
                <h5 class="card-title">Your Connection ID: <span id="connectionId">{{ $connectionId }}</span></h5>
                <p>Share this ID with another store to connect.</p>
            </div>
        </div>
        <div class="card mt-4">
            <div class="card-body">
                <h5 class="card-title">Store Connection</h5>
                @if($connectedShop)
                    <p>Connected to: <span id="connectedShop">{{ $connectedShop }}</span></p>
                    <button id="disconnectBtn" class="btn btn-danger">Disconnect</button>
                @else
                    <p>Not connected to any store.</p>
                    <div id="connectForm">
                        <input type="text" id="connectionIdInput" class="form-control" placeholder="Enter Connection ID">
                        <button id="connectBtn" class="btn btn-primary mt-2">Connect to Store</button>
                    </div>
                @endif
            </div>
        </div>
        <div id="message" class="mt-3 alert" style="display: none;"></div>
    </div>
@endsection
@section('scripts')
    @parent
    <script type="text/javascript">
        actions.TitleBar.create(app, { title: 'Magic Sync Master' });

        // Helper function to show messages
        function showMessage(message, isError = false) {
            const messageElement = document.getElementById('message');
            messageElement.textContent = message;
            messageElement.classList.remove('alert-success', 'alert-danger');
            messageElement.classList.add(isError ? 'alert-danger' : 'alert-success');
            messageElement.style.display = 'block';
        }

        // Helper function to make authenticated requests
        function authenticatedFetch(url, method, data = {}) {
            return new Promise((resolve, reject) => {
                const session = utils.getSessionToken(app);
                session.then(token => {
                    fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'Authorization': `Bearer ${token}`,
                        },
                        body: method !== 'GET' ? JSON.stringify(data) : undefined,
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(resolve)
                    .catch(reject);
                }).catch(reject);
            });
        }

        // Connect function
        function connect() {
            const connectionId = document.getElementById('connectionIdInput').value;
            authenticatedFetch('{{ route('connect') }}', 'POST', { connection_id: connectionId })
                .then(response => {
                    if (response.success) {
                        showMessage('Successfully connected to ' + response.connected_shop);
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showMessage('Connection failed: ' + response.error, true);
                    }
                })
                .catch(error => {
                    showMessage('An error occurred while connecting to the store.', true);
                    console.error('Error:', error);
                });
        }

        // Disconnect function
        function disconnect() {
            authenticatedFetch('{{ route('disconnect') }}', 'POST')
                .then(response => {
                    if (response.success) {
                        showMessage('Successfully disconnected');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showMessage('Disconnection failed: ' + response.error, true);
                    }
                })
                .catch(error => {
                    showMessage('An error occurred while disconnecting from the store.', true);
                    console.error('Error:', error);
                });
        }

        // Add event listeners
        document.addEventListener('DOMContentLoaded', function() {
            const connectBtn = document.getElementById('connectBtn');
            if (connectBtn) {
                connectBtn.addEventListener('click', connect);
            }

            const disconnectBtn = document.getElementById('disconnectBtn');
            if (disconnectBtn) {
                disconnectBtn.addEventListener('click', disconnect);
            }
        });
    </script>
@endsection