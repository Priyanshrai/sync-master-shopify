document.addEventListener('DOMContentLoaded', function() {
    // Fetch connection ID from proxy route
    fetch('/apps/proxy')
    .then(response => response.json())
    .then(data => {
      console.log('Connection ID:', data.connectionId);
      document.getElementById('connection-id').textContent = 'Connection ID: ' + data.connectionId;
    })
    .catch(error => console.error('Error fetching connection ID:', error));
  
    // Fetch and display first 5 products
    fetch('/products.json?limit=5')
      .then(response => response.json())
      .then(data => {
        const productList = document.getElementById('product-list');
        data.products.forEach(product => {
          const li = document.createElement('li');
          const encodedTitle = btoa(product.title);
          li.textContent = `${product.title} | ${encodedTitle}`;
          productList.appendChild(li);
          console.log(`${product.title} | ${encodedTitle}`);
        });
      })
      .catch(error => console.error('Error fetching products:', error));
  });