/**
 * SoVest Search Functionality
 * 
 * This script provides real-time search suggestions, saved search management,
 * and general search page interactivity.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Main search input on search page
    const searchInput = document.getElementById('searchInput');
    const suggestionsContainer = document.getElementById('searchSuggestions');
    
    // Navigation search input
    const navSearchInput = document.getElementById('navSearchInput');
    const navSuggestionsContainer = document.getElementById('navSearchSuggestions');
    
    // Buttons for search history and saved searches management
    const saveSearchBtn = document.getElementById('saveSearch');
    const clearHistoryBtn = document.getElementById('clearHistory');
    const removeSavedButtons = document.querySelectorAll('.remove-saved');
    
    // Initialize search type filter
    const searchTypeFilter = document.querySelector('select[name="type"]');
    
    // Set up real-time suggestions for main search input
    if (searchInput && suggestionsContainer) {
        setupSearchSuggestions(searchInput, suggestionsContainer);
    }
    
    // Set up real-time suggestions for navigation search
    if (navSearchInput && navSuggestionsContainer) {
        setupSearchSuggestions(navSearchInput, navSuggestionsContainer);
    }
    
    // Handle saving searches
    if (saveSearchBtn) {
        saveSearchBtn.addEventListener('click', function() {
            const query = this.getAttribute('data-query');
            const type = this.getAttribute('data-type');
            saveSearch(query, type);
        });
    }
    
    // Handle clearing search history
    if (clearHistoryBtn) {
        clearHistoryBtn.addEventListener('click', function() {
            clearSearchHistory();
        });
    }
    
    // Handle removing saved searches
    removeSavedButtons.forEach(button => {
        button.addEventListener('click', function() {
            const searchId = this.getAttribute('data-id');
            removeSavedSearch(searchId, this.parentElement);
        });
    });
    
    // Update prediction filter visibility based on search type
    if (searchTypeFilter) {
        const predictionFilter = document.querySelector('select[name="prediction"]');
        
        searchTypeFilter.addEventListener('change', function() {
            if (this.value === 'predictions' || this.value === 'all') {
                predictionFilter.parentElement.style.display = 'block';
            } else {
                predictionFilter.parentElement.style.display = 'none';
            }
        });
        
        // Set initial state
        if (searchTypeFilter.value !== 'predictions' && searchTypeFilter.value !== 'all') {
            predictionFilter.parentElement.style.display = 'none';
        }
    }
});

/**
 * Set up real-time search suggestions for an input
 */
function setupSearchSuggestions(inputElement, suggestionsContainer) {
    let debounceTimer;
    
    inputElement.addEventListener('input', function() {
        const query = this.value.trim();
        const type = document.querySelector('select[name="type"]')?.value || 'all';
        
        // Clear previous timer
        clearTimeout(debounceTimer);
        
        // Hide suggestions if query is too short
        if (query.length < 2) {
            suggestionsContainer.innerHTML = '';
            suggestionsContainer.style.display = 'none';
            return;
        }
        
<<<<<<< HEAD:SoVest_code/js/search.js
        // Debounce to avoid excessive API calls
        debounceTimer = setTimeout(() => {
            fetchSuggestions(query, type, suggestionsContainer);
=======
        if (new RegExp(/([A-Za-z]{1,5})(-[A-Za-z]{1,2})?/g).test(query) !== true) return;
        // Debounce to avoid excessive API calls
        debounceTimer = setTimeout(() => {
            fetchSuggestions(query.toUpperCase(), type, suggestionsContainer, inputElement);
>>>>>>> ded11c1df32be455bc722343f9d9556f8d241225:src/public/js/search.js
        }, 300);
    });
    
    // Hide suggestions when clicking outside
    document.addEventListener('click', function(event) {
        if (!inputElement.contains(event.target) && !suggestionsContainer.contains(event.target)) {
            suggestionsContainer.style.display = 'none';
        }
    });
    
    // Show suggestions when input is focused
    inputElement.addEventListener('focus', function() {
        const query = this.value.trim();
        if (query.length >= 2 && suggestionsContainer.innerHTML !== '') {
            suggestionsContainer.style.display = 'block';
        }
    });
}

/**
 * Fetch search suggestions from the API
 */
<<<<<<< HEAD:SoVest_code/js/search.js
function fetchSuggestions(query, type, suggestionsContainer) {
    fetch(`api/search.php?action=suggestions&query=${encodeURIComponent(query)}&type=${type}`)
=======
function fetchSuggestions(query, type, suggestionsContainer, inputElement) {
    fetch(`/api/search?action=suggestions&query=${encodeURIComponent(query)}&type=${type}`)
>>>>>>> ded11c1df32be455bc722343f9d9556f8d241225:src/public/js/search.js
        .then(response => response.json())
        .then(data => {
            suggestionsContainer.innerHTML = '';
            
<<<<<<< HEAD:SoVest_code/js/search.js
=======
            // Check for prediction intent and display if available
            if (data.predictionIntent) {
                displayPredictionIntent(data.predictionIntent, suggestionsContainer);
            }
            
>>>>>>> ded11c1df32be455bc722343f9d9556f8d241225:src/public/js/search.js
            if (data.suggestions && data.suggestions.length > 0) {
                data.suggestions.forEach(suggestion => {
                    const suggestionDiv = document.createElement('div');
                    suggestionDiv.className = 'search-suggestion';
                    
                    // Add different icons based on suggestion type
                    let icon = '';
                    switch (suggestion.type) {
                        case 'stock':
                            icon = '<i class="bi bi-graph-up-arrow"></i>';
                            break;
                        case 'user':
                            icon = '<i class="bi bi-person-circle"></i>';
                            break;
                        case 'prediction':
                            icon = '<i class="bi bi-lightning-charge"></i>';
                            break;
                    }
                    
<<<<<<< HEAD:SoVest_code/js/search.js
                    suggestionDiv.innerHTML = `${icon} ${suggestion.text}`;
                    
                    suggestionDiv.addEventListener('click', function() {
=======
                    // Create suggestion content wrapper
                    const contentDiv = document.createElement('div');
                    contentDiv.className = 'suggestion-content';
                    
                    // Add prediction type indicator for prediction suggestions
                    if (suggestion.type === 'prediction' && suggestion.predictionType) {
                        const badgeClass = suggestion.predictionType === 'Bullish' ? 'bg-success' : 'bg-danger';
                        contentDiv.innerHTML = `${icon} ${suggestion.text} <span class="badge ${badgeClass} ms-2">${suggestion.predictionType}</span>`;
                    } else {
                        contentDiv.innerHTML = `${icon} ${suggestion.text}`;
                    }
                    
                    suggestionDiv.appendChild(contentDiv);
                    
                    // Add create prediction button for stock suggestions
                    if (suggestion.type === 'stock' && suggestion.id) {
                        const actionDiv = document.createElement('div');
                        actionDiv.className = 'suggestion-actions';
                        
                        const createPredBtn = document.createElement('button');
                        createPredBtn.className = 'btn btn-sm btn-primary create-prediction-btn';
                        createPredBtn.innerHTML = '<i class="bi bi-lightning-charge"></i> Predict';
                        
                        createPredBtn.addEventListener('click', function(e) {
                            e.stopPropagation(); // Prevent triggering parent click
                            window.location.href = `/predictions/create?stock_id=${suggestion.id}`;
                        });
                        
                        actionDiv.appendChild(createPredBtn);
                        suggestionDiv.appendChild(actionDiv);
                    }
                    
                    suggestionDiv.addEventListener('click', function(e) {
                        // Don't handle click if the create prediction button was clicked
                        if (e.target.closest('.create-prediction-btn')) {
                            return;
                        }
                        
>>>>>>> ded11c1df32be455bc722343f9d9556f8d241225:src/public/js/search.js
                        // Set input value and submit the containing form
                        const form = inputElement.closest('form');
                        inputElement.value = suggestion.text.split(' - ')[0]; // Use symbol/name only
                        
                        if (suggestion.type && suggestion.type !== 'all') {
                            const typeInput = form.querySelector('select[name="type"]');
                            if (typeInput && suggestion.type === 'stock') {
                                typeInput.value = 'stocks';
                            } else if (typeInput && suggestion.type === 'user') {
                                typeInput.value = 'users';
                            } else if (typeInput && suggestion.type === 'prediction') {
                                typeInput.value = 'predictions';
                            }
                        }
                        
                        form.submit();
                    });
                    
                    suggestionsContainer.appendChild(suggestionDiv);
                });
                
                suggestionsContainer.style.display = 'block';
            } else {
                suggestionsContainer.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error fetching suggestions:', error);
            suggestionsContainer.style.display = 'none';
        });
}

/**
<<<<<<< HEAD:SoVest_code/js/search.js
=======
 * Display prediction intent message and action button
 */
function displayPredictionIntent(intentData, container) {
    const intentDiv = document.createElement('div');
    intentDiv.className = 'prediction-intent';
    
    const stockName = intentData.stockName || 'this stock';
    const predictionType = intentData.predictionType || '';
    
    let message = `Looks like you want to make a prediction about ${stockName}`;
    if (predictionType) {
        const typeText = predictionType === 'Bullish' ? 'will rise' : 'will fall';
        message += ` that it ${typeText}`;
    }
    
    intentDiv.innerHTML = `
        <div class="intent-content">
            <i class="bi bi-lightbulb text-warning"></i>
            <p>${message}</p>
        </div>
        <button class="btn btn-success create-prediction-btn">
            <i class="bi bi-lightning-charge"></i> Create Prediction
        </button>
    `;
    
    // Add click handler to create prediction button
    const createBtn = intentDiv.querySelector('.create-prediction-btn');
    if (createBtn) {
        createBtn.addEventListener('click', function() {
            let url = '/predictions/create';
            
            // Add stock ID if available
            if (intentData.stockId) {
                url += `?stock_id=${intentData.stockId}`;
                
                // Add prediction type if available
                if (predictionType) {
                    url += `&prediction_type=${predictionType}`;
                }
            }
            
            window.location.href = url;
        });
    }
    
    // Add to container as the first element
    container.appendChild(intentDiv);
}

/**
>>>>>>> ded11c1df32be455bc722343f9d9556f8d241225:src/public/js/search.js
 * Save a search to favorites
 */
function saveSearch(query, type) {
    const formData = new FormData();
<<<<<<< HEAD:SoVest_code/js/search.js
    formData.append('action', 'save_search');
    formData.append('query', query);
    formData.append('type', type);
    
    fetch('api/search.php', {
=======
    formData.append('query', query);
    formData.append('type', type);
    formData.append('action', 'save_search');
    
    fetch('/api/search', {
>>>>>>> ded11c1df32be455bc722343f9d9556f8d241225:src/public/js/search.js
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            const saveBtn = document.getElementById('saveSearch');
            saveBtn.innerHTML = '<i class="bi bi-check"></i> Saved';
            saveBtn.classList.remove('btn-outline-success');
            saveBtn.classList.add('btn-success');
            saveBtn.disabled = true;
            
            // Reload page after short delay to show updated saved searches
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            alert('Failed to save search: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error saving search:', error);
        alert('An error occurred while saving the search.');
    });
}

/**
 * Clear search history
 */
function clearSearchHistory() {
    if (!confirm('Are you sure you want to clear your search history?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'clear_history');
    
<<<<<<< HEAD:SoVest_code/js/search.js
    fetch('api/search.php', {
=======
    fetch('/api/search', {
>>>>>>> ded11c1df32be455bc722343f9d9556f8d241225:src/public/js/search.js
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload page to update UI
            window.location.reload();
        } else {
            alert('Failed to clear history: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error clearing history:', error);
        alert('An error occurred while clearing search history.');
    });
}

/**
 * Remove a saved search
 */
function removeSavedSearch(searchId, listItem) {
    const formData = new FormData();
<<<<<<< HEAD:SoVest_code/js/search.js
    formData.append('action', 'remove_saved');
    formData.append('search_id', searchId);
    
    fetch('api/search.php', {
=======
    formData.append('search_id', searchId);
    formData.append('action', 'remove_saved');
    
    fetch('/api/search', {
>>>>>>> ded11c1df32be455bc722343f9d9556f8d241225:src/public/js/search.js
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the item from the UI
            listItem.remove();
            
            // If no more saved searches, hide container
            const savedList = document.querySelector('.card-body .list-group');
            if (savedList && savedList.children.length === 0) {
                savedList.closest('.card').style.display = 'none';
            }
        } else {
            alert('Failed to remove saved search: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error removing saved search:', error);
        alert('An error occurred while removing the saved search.');
    });
}

/**
 * Fetch stock price on demand
 */
document.addEventListener('DOMContentLoaded', function() {
    const fetchPriceBtns = document.querySelectorAll('.fetch-price-btn');

    fetchPriceBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const symbol = this.getAttribute('data-symbol');
            const priceDisplay = document.querySelector(`.price-display[data-symbol="${symbol}"]`);

            // Disable button and show loading
            this.disabled = true;
            this.innerHTML = '<i class="bi bi-hourglass-split"></i> Fetching...';

            // Fetch price from API
            fetch('/api/fetch_stock_price', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify({ symbol: symbol })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data && data.data.price) {
                    // Hide button
                    this.classList.add('d-none');

                    // Show price
                    const priceValue = priceDisplay.querySelector('.price-value');
                    priceValue.textContent = data.data.price.toFixed(2);
                    priceDisplay.classList.remove('d-none');
                } else {
                    this.innerHTML = '<i class="bi bi-x-circle"></i> Failed';
                    this.classList.replace('btn-outline-info', 'btn-outline-danger');
                    setTimeout(() => {
                        this.disabled = false;
                        this.innerHTML = '<i class="bi bi-download"></i> Retry';
                        this.classList.replace('btn-outline-danger', 'btn-outline-info');
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error fetching price:', error);
                this.innerHTML = '<i class="bi bi-x-circle"></i> Error';
                this.classList.replace('btn-outline-info', 'btn-outline-danger');
                setTimeout(() => {
                    this.disabled = false;
                    this.innerHTML = '<i class="bi bi-download"></i> Retry';
                    this.classList.replace('btn-outline-danger', 'btn-outline-info');
                }, 2000);
            });
        });
    });
});