document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const searchSuggestions = document.getElementById('searchSuggestions');
    let debounceTimer;

    // Function to show suggestions
    function showSuggestions(suggestions) {
        searchSuggestions.innerHTML = '';
        searchSuggestions.classList.add('active');

        if (suggestions.length === 0) {
            const noResults = document.createElement('div');
            noResults.className = 'suggestion-item';
            noResults.textContent = 'No products found';
            searchSuggestions.appendChild(noResults);
            return;
        }

        suggestions.forEach(product => {
            const item = document.createElement('div');
            item.className = 'suggestion-item';
            
            // Create HTML for suggestion item
            item.innerHTML = `
                <img src="${product.image_path}" alt="${product.name}" onerror="this.src='../assets/images/default-product.jpg'">
                <div class="item-details">
                    <div class="item-name">${product.name}</div>
                    <div class="item-category">${product.category}</div>
                    <div class="item-price">₱${product.price}</div>
                </div>
            `;

            // Add click event to navigate to product
            item.addEventListener('click', () => {
                window.location.href = `product.php?id=${product.id}`;
            });

            searchSuggestions.appendChild(item);
        });
    }

    // Function to fetch suggestions
    function fetchSuggestions(query) {
        // Show loading state
        searchSuggestions.innerHTML = `
            <div class="suggestion-item loading">
                <i class="fas fa-spinner fa-spin"></i>
                Searching...
            </div>
        `;
        searchSuggestions.classList.add('active');

        fetch(`get_suggestions.php?query=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    searchSuggestions.innerHTML = `
                        <div class="suggestion-item error">
                            <i class="fas fa-exclamation-circle"></i>
                            ${data.message}
                        </div>
                    `;
                } else {
                    showSuggestions(data);
                }
            })
            .catch(error => {
                searchSuggestions.innerHTML = `
                    <div class="suggestion-item error">
                        <i class="fas fa-exclamation-circle"></i>
                        Failed to fetch suggestions
                    </div>
                `;
                console.error('Error:', error);
            });
    }

    // Add event listeners
    searchInput.addEventListener('input', function(e) {
        clearTimeout(debounceTimer);
        const query = e.target.value.trim();

        if (query.length >= 2) {
            debounceTimer = setTimeout(() => fetchSuggestions(query), 300);
        } else {
            searchSuggestions.innerHTML = '';
            searchSuggestions.classList.remove('active');
        }
    });

    // Close suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchSuggestions.contains(e.target)) {
            searchSuggestions.classList.remove('active');
        }
    });

    // Prevent suggestions from closing when clicking inside
    searchSuggestions.addEventListener('click', function(e) {
        e.stopPropagation();
    });
}); 