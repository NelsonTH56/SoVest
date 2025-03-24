/**
 * SoVest Prediction Management
 * 
 * Handles client-side functionality for prediction creation and management
 */

document.addEventListener('DOMContentLoaded', function() {
    // Stock search autocomplete
    const stockSearchInput = document.getElementById('stock-search');
    const stockSuggestions = document.getElementById('stock-suggestions');
    const stockIdInput = document.getElementById('stock_id');
    
    if (stockSearchInput) {
        // Handle stock search
        let searchTimeout;
        stockSearchInput.addEventListener('input', function() {
            const searchTerm = this.value.trim();
            
            // Clear previous timeout
            clearTimeout(searchTimeout);
            
            // Clear suggestions if search term is empty
            if (searchTerm.length === 0) {
                stockSuggestions.innerHTML = '';
                stockIdInput.value = '';
                return;
            }
            
            // Set timeout to prevent excessive API calls
            searchTimeout = setTimeout(function() {
                // Call stock search API using Laravel endpoint
                fetch(`${apiEndpoints.searchStocks}?term=${encodeURIComponent(searchTerm)}`)
                    .then(response => response.json())
                    .then(data => {
                        stockSuggestions.innerHTML = '';
                        
                        if (data.success && data.data.length > 0) {
                            // Create suggestion elements
                            const suggestionsList = document.createElement('div');
                            suggestionsList.className = 'list-group';
                            
                            data.data.forEach(stock => {
                                const suggestion = document.createElement('button');
                                suggestion.className = 'list-group-item list-group-item-action bg-dark text-light';
                                suggestion.innerHTML = `<strong>${stock.symbol}</strong> - ${stock.name}`;
                                suggestion.addEventListener('click', function() {
                                    stockSearchInput.value = `${stock.symbol} - ${stock.name}`;
                                    stockIdInput.value = stock.id;
                                    stockSuggestions.innerHTML = '';
                                });
                                suggestionsList.appendChild(suggestion);
                            });
                            
                            stockSuggestions.appendChild(suggestionsList);
                        } else if (data.data.length === 0) {
                            stockSuggestions.innerHTML = '<div class="alert alert-info">No stocks found</div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error searching for stocks:', error);
                        stockSuggestions.innerHTML = '<div class="alert alert-danger">Error searching for stocks</div>';
                    });
            }, 300);
        });
    }
    
    // Form validation
    const predictionForm = document.getElementById('prediction-form');
    if (predictionForm) {
        predictionForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            let isValid = true;
            let errorMessage = '';
            
            // Check stock selection
            if (!stockIdInput.value) {
                isValid = false;
                errorMessage = 'Please select a stock from the suggestions';
            }
            
            // Check prediction type
            const predictionType = document.getElementById('prediction_type');
            if (predictionType.value === '') {
                isValid = false;
                errorMessage = 'Please select a prediction type';
            }
            
            // Check end date
            const endDate = document.getElementById('end_date');
            if (!endDate.value) {
                isValid = false;
                errorMessage = 'Please select an end date for your prediction';
            } else {
                const selectedDate = new Date(endDate.value);
                const currentDate = new Date();
                
                if (selectedDate <= currentDate) {
                    isValid = false;
                    errorMessage = 'End date must be in the future';
                }
            }
            
            // Check reasoning
            const reasoning = document.getElementById('reasoning');
            if (!reasoning.value.trim()) {
                isValid = false;
                errorMessage = 'Please provide reasoning for your prediction';
            }
            
            if (!isValid) {
                // Display error message
                if (!document.querySelector('.alert-danger')) {
                    const errorAlert = document.createElement('div');
                    errorAlert.className = 'alert alert-danger mt-3';
                    errorAlert.textContent = errorMessage;
                    predictionForm.insertAdjacentElement('afterbegin', errorAlert);
                    
                    // Auto-remove after 5 seconds
                    setTimeout(function() {
                        errorAlert.remove();
                    }, 5000);
                }
                return;
            }
            
            // Submit form if valid
            this.submit();
        });
    }
    
    // Handle prediction deletion
    const deleteButtons = document.querySelectorAll('.delete-prediction');
    const deleteModal = document.getElementById('deleteModal');
    
    if (deleteButtons.length > 0 && deleteModal) {
        let predictionToDelete = null;
        
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                predictionToDelete = this.getAttribute('data-id');
                const modal = new bootstrap.Modal(deleteModal);
                modal.show();
            });
        });
        
        // Handle delete confirmation
        const confirmDeleteBtn = document.getElementById('confirmDelete');
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', function() {
                if (predictionToDelete) {
                    // Create form data
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('prediction_id', predictionToDelete);
                    
                    // Send delete request
                    fetch(apiEndpoints.deletePrediction, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Reload page to show updated predictions
                            window.location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting prediction:', error);
                        alert('An error occurred while deleting the prediction');
                    });
                }
            });
        }
    }
    
    // Handle prediction editing
    const editButtons = document.querySelectorAll('.edit-prediction');
    
    if (editButtons.length > 0) {
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const predictionId = this.getAttribute('data-id');
                // Redirect to edit page using Laravel route
                window.location.href = `/predictions/edit/${predictionId}`;
            });
        });
    }
});