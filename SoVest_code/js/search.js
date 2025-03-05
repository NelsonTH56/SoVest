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
        
        // Debounce to avoid excessive API calls
        debounceTimer = setTimeout(() => {
            fetchSuggestions(query, type, suggestionsContainer);
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
function fetchSuggestions(query, type, suggestionsContainer) {
    fetch(`api/search.php?action=suggestions&query=${encodeURIComponent(query)}&type=${type}`)
        .then(response => response.json())
        .then(data => {
            suggestionsContainer.innerHTML = '';
            
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
                    
                    suggestionDiv.innerHTML = `${icon} ${suggestion.text}`;
                    
                    suggestionDiv.addEventListener('click', function() {
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
 * Save a search to favorites
 */
function saveSearch(query, type) {
    const formData = new FormData();
    formData.append('action', 'save_search');
    formData.append('query', query);
    formData.append('type', type);
    
    fetch('api/search.php', {
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
    
    fetch('api/search.php', {
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
    formData.append('action', 'remove_saved');
    formData.append('search_id', searchId);
    
    fetch('api/search.php', {
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