document.addEventListener('DOMContentLoaded', function() {
  const connectionStatus = document.getElementById('connection-status');
  const productList = document.getElementById('product-list');
  const syncButton = document.getElementById('sync-button');
  const refreshButton = document.getElementById('refresh-button');
  const syncStatus = document.getElementById('sync-status');

  let currentConnectionId = '';

  // Fetch the connection ID from the proxy route
  function fetchConnectionId() {
      connectionStatus.textContent = 'Checking connection...';
      fetch('/apps/proxy', {
          headers: {
              'ngrok-skip-browser-warning': '69420'
          }
      })
      .then(response => {
          if (!response.ok) {
              throw new Error('Network response was not ok: ' + response.statusText);
          }
          return response.text();
      })
      .then(connectionId => {
          console.log('Connection ID:', connectionId);
          currentConnectionId = connectionId;
          updateConnectionStatus();
      })
      .catch(error => {
          console.error('Error fetching connection ID:', error);
          connectionStatus.textContent = 'Failed to fetch Connection ID: ' + error.message;
      });
  }

  function updateConnectionStatus() {
      connectionStatus.textContent = currentConnectionId ? `Connection ID: ${currentConnectionId}` : 'No connection';
  }

  // Fetch and display product titles
  function fetchProducts() {
      productList.innerHTML = '<li>Loading products...</li>';
      fetch('/products.json?limit=5')
      .then(response => response.json())
      .then(data => {
          productList.innerHTML = '';
          data.products.forEach(product => {
              const title = product.title;
              const encodedTitle = btoa(title);
              console.log(`${title} | ${encodedTitle}`);
              
              const li = document.createElement('li');
              li.textContent = `${title} | ${encodedTitle}`;
              productList.appendChild(li);
          });
      })
      .catch(error => {
          console.error('Error fetching products:', error);
          productList.innerHTML = '<li>Failed to fetch products: ' + error.message + '</li>';
      });
  }

  // Simulated sync function
  function syncData() {
      const originalText = syncStatus.textContent;
      syncStatus.textContent = 'Syncing...';
      syncButton.disabled = true;
      refreshButton.disabled = true;

      // Simulate a sync operation with a 2-second delay
      setTimeout(() => {
          fetchConnectionId(); // Refresh the connection ID
          fetchProducts(); // Refresh the product list
          syncStatus.textContent = 'Last synced: ' + new Date().toLocaleString();
          syncButton.disabled = false;
          refreshButton.disabled = false;
      }, 2000);
  }

  // Event listeners for buttons
  syncButton.addEventListener('click', syncData);
  refreshButton.addEventListener('click', () => {
      fetchProducts();
      syncStatus.textContent = 'Products refreshed: ' + new Date().toLocaleString();
  });

  // Initial fetch of connection ID and products
  fetchConnectionId();
  fetchProducts();
});