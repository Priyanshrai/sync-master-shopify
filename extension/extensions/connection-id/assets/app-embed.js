// Fetch the connection ID from the proxy route
fetch('/apps/proxy')
  .then(response => {
    if (!response.ok) {
      throw new Error('Network response was not ok: ' + response.statusText);
    }
    return response.text();
  })
  .then(connectionId => {
    console.log('Connection ID:', connectionId);
    document.getElementById('connection-status').textContent = 'Connection ID: ' + connectionId;
  })
  .catch(error => {
    console.error('Error fetching connection ID:', error);
    document.getElementById('connection-status').textContent = 'Failed to fetch Connection ID: ' + error.message;
  });

// Fetch and display product titles
fetch('/products.json?limit=5')
  .then(response => response.json())
  .then(data => {
    const productList = document.getElementById('product-list');
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
    document.getElementById('product-list').textContent = 'Failed to fetch products';
  });