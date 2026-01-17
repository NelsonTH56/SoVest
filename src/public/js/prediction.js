/**
 * SoVest Prediction Management
 *
 * Enhanced client-side validation for prediction forms with real-time feedback
 * Multi-step form with stock price fetching
 */

document.addEventListener('DOMContentLoaded', function () {
    // Multi-step form variables
    let currentStep = 1;
    const totalSteps = 5; // Now 5 steps including confirmation
    let selectedStockSymbol = null;
    let currentStockPrice = null;

    // Form elements
    const predictionForm = document.getElementById('prediction-form');
    const stockSearchInput = document.getElementById('stock-search');
    const stockIdInput = document.getElementById('stock_id');
    const stockSuggestions = document.getElementById('stock-suggestions');
    const predictionTypeSelect = document.getElementById('prediction_type');
    const targetPriceInput = document.getElementById('target_price');
    const percentChangeInput = document.getElementById('percent_change');
    const endDateInput = document.getElementById('end_date');
    const reasoningTextarea = document.getElementById('reasoning');
    const submitButton = document.querySelector('button[type="submit"]');
    const today = new Date();
    today.setHours(0, 0, 0, 0); // Reset time part for date comparison

    // Calculate min date - must be at least 1 week (7 days) in the future
    const minDate = new Date(today);
    minDate.setDate(minDate.getDate() + 7);

    // No maximum date restriction - can be any date after the minimum
    if (endDateInput != null) {
        // Set min attribute on the date input (no max restriction)
        endDateInput.min = formatDateForInput(minDate);
    }

    // Flag to prevent circular updates between target price and percent change
    let isUpdatingPrice = false;

    

    // Enable tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Update the form text to inform users of date constraints
    if (endDateInput != null) {
        const endDateContainer = document.querySelector('#end_date')?.closest('.mb-4');
        const dateHelpText = endDateContainer?.querySelector('.form-text');
        if (dateHelpText) {
            dateHelpText.innerHTML = `Select a date at least one week from today (on or after <strong>${formatDateForDisplay(minDate)}</strong>). Predictions must be for future dates beyond the current week.`;
        }
    }

    // Validation state object to track form validity
    const validationState = {
        stock: false,
        predictionType: false,
        endDate: false,
        reasoning: false
    };

    // Initialize tooltips for enhanced user guidance
    function initTooltips() {
        // Add dynamic tooltips that appear on focus
        if (stockSearchInput) {
            addTooltip(stockSearchInput, 'Search for a stock by symbol or name. Select from the dropdown.');
        }

        if (predictionTypeSelect) {
            addTooltip(predictionTypeSelect, 'Choose "Bullish" if you believe the stock will rise, or "Bearish" if you think it will fall.');
        }

        if (targetPriceInput) {
            addTooltip(targetPriceInput, 'Set a specific price target to make your prediction more measurable. Optional but recommended.');
        }

        if (endDateInput) {
            addTooltip(endDateInput, 'Select when you expect your prediction to be fulfilled. Must be a future date.');
        }

        if (reasoningTextarea) {
            addTooltip(reasoningTextarea, 'Explain why you believe this prediction will come true. Include specific factors like earnings, news, or market trends.');
        }
    }

    /**
     * Creates and attaches a dynamic tooltip to an element
     */
    function addTooltip(element, text) {
        if (!element) return;

        // Create tooltip container
        const tooltipId = `tooltip-${element.id}`;
        let tooltip = document.getElementById(tooltipId);

        // Only create if it doesn't exist
        if (!tooltip) {
            tooltip = document.createElement('div');
            tooltip.id = tooltipId;
            tooltip.className = 'validation-tooltip';
            tooltip.innerHTML = `<i class="bi bi-info-circle"></i> ${text}`;
            tooltip.style.display = 'none';

            // Insert tooltip after the field's parent container (usually an input-group or form-group)
            const container = element.closest('.input-group') || element.closest('.mb-4');
            if (container) {
                container.appendChild(tooltip);
            } else {
                // Fallback to inserting after the element itself
                element.parentNode.insertBefore(tooltip, element.nextSibling);
            }

            // Show tooltip on focus
            element.addEventListener('focus', function () {
                tooltip.style.display = 'block';
            });

            // Hide tooltip on blur
            element.addEventListener('blur', function () {
                tooltip.style.display = 'none';
            });
        }
    }

    /**
     * Stock search functionality with enhanced validation
     */
    if (stockSearchInput) {
        // Handle stock search
        let searchTimeout;
        stockSearchInput.addEventListener('input', function () {
            const searchTerm = this.value.trim();

            // Clear previous timeout
            clearTimeout(searchTimeout);

            // Clear suggestions if search term is empty
            if (searchTerm.length === 0) {
                stockSuggestions.innerHTML = '';
                stockIdInput.value = '';
                selectedStockSymbol = null;
                hideStockInfo();
                validateStock(false);
                return;
            }

            // Allow searches for stock symbols (2-5 letters) or company names (2+ characters)
            if (searchTerm.length < 2) return;

            // Set timeout to prevent excessive API calls
            searchTimeout = setTimeout(function () {
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
                                suggestion.addEventListener('click', function () {
                                    stockSearchInput.value = `${stock.symbol} - ${stock.name}`;
                                    stockIdInput.value = stock.id;
                                    selectedStockSymbol = stock.symbol;
                                    stockSuggestions.innerHTML = '';
                                    validateStock(true);
                                    showStockInfo(stock.symbol, stock.name);
                                    fetchStockPrice(stock.symbol);
                                });
                                suggestionsList.appendChild(suggestion);
                            });

                            stockSuggestions.appendChild(suggestionsList);
                        } else if (data.data.length === 0) {
                            stockSuggestions.innerHTML = '<div class="alert alert-info">No stocks found</div>';
                            validateStock(false);
                        }
                    })
                    .catch(error => {
                        console.error('Error searching for stocks:', error);
                        stockSuggestions.innerHTML = '<div class="alert alert-danger">Error searching for stocks</div>';
                        validateStock(false);
                    });
            }, 300);
        });

        // Close suggestions when clicking outside
        document.addEventListener('click', function (event) {
            if (!stockSearchInput.contains(event.target) && stockSuggestions != null && !stockSuggestions.contains(event.target)) {
                stockSuggestions.innerHTML = '';
            }
        });

        // Show suggestions when focusing on the search input if there's content
        stockSearchInput.addEventListener('focus', function () {
            if (this.value.trim().length > 0 && stockSuggestions != null && stockSuggestions.innerHTML === '') {
                // Trigger the input event to show suggestions
                this.dispatchEvent(new Event('input'));
            }
        });

        // Handle keyboard navigation in suggestions
        stockSearchInput.addEventListener('keydown', function (event) {
            const suggestions = stockSuggestions.querySelectorAll('.list-group-item');
            if (suggestions.length === 0) return;

            // Find currently highlighted item
            const current = stockSuggestions.querySelector('.list-group-item.active');
            let index = -1;

            if (current) {
                for (let i = 0; i < suggestions.length; i++) {
                    if (suggestions[i] === current) {
                        index = i;
                        break;
                    }
                }
            }

            // Handle arrow keys
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                if (current) current.classList.remove('active');
                index = (index + 1) % suggestions.length;
                suggestions[index].classList.add('active');
                suggestions[index].scrollIntoView({ block: 'nearest' });
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                if (current) current.classList.remove('active');
                index = (index - 1 + suggestions.length) % suggestions.length;
                suggestions[index].classList.add('active');
                suggestions[index].scrollIntoView({ block: 'nearest' });
            } else if (event.key === 'Enter' && current) {
                event.preventDefault();
                current.click();
            } else if (event.key === 'Escape') {
                stockSuggestions.innerHTML = '';
            }
        });
    }

    /**
     * Validate stock selection
     */
    function validateStock(isValid = null) {
        if (!stockIdInput || !stockSearchInput) return;

        // If isValid is not provided, determine based on input values
        if (isValid === null) {
            isValid = stockIdInput.value.trim() !== '';
        }

        if (isValid) {
            setFieldValid(stockSearchInput, 'Stock selected successfully');
            validationState.stock = true;
        } else {
            setFieldInvalid(stockSearchInput, 'Please select a stock from the suggestions');
            validationState.stock = false;
        }

        updateSubmitButton();
    }

    /**
     * Validate prediction type
     */
    function validatePredictionType() {
        // Check for radio buttons instead of select
        const selectedType = document.querySelector('input[name="prediction_type"]:checked');

        if (selectedType && (selectedType.value === 'Bullish' || selectedType.value === 'Bearish')) {
            validationState.predictionType = true;
        } else {
            validationState.predictionType = false;
        }

        updateSubmitButton();
    }

    /**
     * Validate target price (optional field) with direction enforcement
     */
    function validateTargetPrice() {
        if (!targetPriceInput) return true;

        const targetPrice = targetPriceInput.value.trim();

        // Target price is optional but must be a valid number if provided
        if (targetPrice === '') {
            removeValidationStatus(targetPriceInput);
            updatePercentChangeFromTargetPrice();
            return true;
        }

        const priceValue = parseFloat(targetPrice);

        if (isNaN(priceValue) || priceValue <= 0) {
            setFieldInvalid(targetPriceInput, 'Price must be a positive number');
            return false;
        }

        // Enforce direction constraints if we have current price
        const predictionType = document.querySelector('input[name="prediction_type"]:checked');

        if (predictionType && currentStockPrice) {
            const type = predictionType.value;

            if (type === 'Bullish' && priceValue <= currentStockPrice) {
                setFieldInvalid(targetPriceInput, `Bullish predictions require a target above $${currentStockPrice.toFixed(2)}`);
                return false;
            } else if (type === 'Bearish' && priceValue >= currentStockPrice) {
                setFieldInvalid(targetPriceInput, `Bearish predictions require a target below $${currentStockPrice.toFixed(2)}`);
                return false;
            }
        }

        setFieldValid(targetPriceInput, `Target price set to $${priceValue.toFixed(2)}`);
        updatePercentChangeFromTargetPrice();
        return true;
    }

    /**
     * Update percent change input based on target price
     */
    function updatePercentChangeFromTargetPrice() {
        if (isUpdatingPrice || !percentChangeInput || !currentStockPrice) return;

        isUpdatingPrice = true;

        const targetPrice = parseFloat(targetPriceInput?.value);

        if (!isNaN(targetPrice) && targetPrice > 0 && currentStockPrice > 0) {
            const percentChange = ((targetPrice - currentStockPrice) / currentStockPrice) * 100;
            percentChangeInput.value = Math.round(percentChange);
        } else {
            percentChangeInput.value = '';
        }

        isUpdatingPrice = false;
    }

    /**
     * Update target price input based on percent change
     */
    function updateTargetPriceFromPercentChange() {
        if (isUpdatingPrice || !targetPriceInput || !currentStockPrice) return;

        isUpdatingPrice = true;

        let percentChange = parseFloat(percentChangeInput?.value);

        if (!isNaN(percentChange) && currentStockPrice > 0) {
            // Get the selected prediction type
            const selectedType = document.querySelector('input[name="prediction_type"]:checked');
            const predictionType = selectedType ? selectedType.value : null;

            // Automatically apply correct sign based on prediction type
            if (predictionType === 'Bearish' && percentChange > 0) {
                // For bearish, make it negative
                percentChange = -Math.abs(percentChange);
                percentChangeInput.value = Math.round(percentChange);
            } else if (predictionType === 'Bullish' && percentChange < 0) {
                // For bullish, make it positive
                percentChange = Math.abs(percentChange);
                percentChangeInput.value = Math.round(percentChange);
            }

            const targetPrice = currentStockPrice * (1 + percentChange / 100);
            targetPriceInput.value = targetPrice.toFixed(2);
        } else {
            targetPriceInput.value = '';
        }

        isUpdatingPrice = false;

        // Validate the computed target price
        validateTargetPrice();
    }

    /**
     * Automatically adjust percent change sign based on prediction type
     */
    function adjustPercentChangeSign() {
        if (!percentChangeInput || !percentChangeInput.value) return;

        let percentChange = parseFloat(percentChangeInput.value);
        if (isNaN(percentChange)) return;

        const selectedType = document.querySelector('input[name="prediction_type"]:checked');
        if (!selectedType) return;

        const predictionType = selectedType.value;

        // Automatically apply correct sign based on prediction type
        if (predictionType === 'Bearish' && percentChange > 0) {
            // For bearish, make it negative
            percentChangeInput.value = Math.round(-Math.abs(percentChange));
            // Update target price with the corrected percent change
            updateTargetPriceFromPercentChange();
        } else if (predictionType === 'Bullish' && percentChange < 0) {
            // For bullish, make it positive
            percentChangeInput.value = Math.round(Math.abs(percentChange));
            // Update target price with the corrected percent change
            updateTargetPriceFromPercentChange();
        }
    }

    /**
     * Validate percent change with direction enforcement
     */
    function validatePercentChange() {
        if (!percentChangeInput) return true;

        const percentValue = parseFloat(percentChangeInput.value);
        const predictionType = document.querySelector('input[name="prediction_type"]:checked');

        if (percentChangeInput.value.trim() === '') {
            removeValidationStatus(percentChangeInput);
            return true;
        }

        if (isNaN(percentValue)) {
            setFieldInvalid(percentChangeInput, 'Please enter a valid number');
            return false;
        }

        if (predictionType) {
            const type = predictionType.value;

            if (type === 'Bullish' && percentValue <= 0) {
                setFieldInvalid(percentChangeInput, 'Bullish predictions require a positive percent change');
                return false;
            } else if (type === 'Bearish' && percentValue >= 0) {
                setFieldInvalid(percentChangeInput, 'Bearish predictions require a negative percent change');
                return false;
            }
        }

        setFieldValid(percentChangeInput, `${percentValue > 0 ? '+' : ''}${Math.round(percentValue)}% change`);
        return true;
    }

    /**
     * Enforce direction constraints by clamping values when direction changes
     */
    function enforceDirectionConstraints() {
        const predictionType = document.querySelector('input[name="prediction_type"]:checked');
        if (!predictionType || !currentStockPrice) return;

        const type = predictionType.value;
        const targetPrice = parseFloat(targetPriceInput?.value);

        if (!isNaN(targetPrice) && targetPrice > 0) {
            // Clamp or adjust invalid values based on direction
            if (type === 'Bullish' && targetPrice <= currentStockPrice) {
                // Set to 5% above current price for bullish
                const newTarget = currentStockPrice * 1.05;
                targetPriceInput.value = newTarget.toFixed(2);
                updatePercentChangeFromTargetPrice();
            } else if (type === 'Bearish' && targetPrice >= currentStockPrice) {
                // Set to 5% below current price for bearish
                const newTarget = currentStockPrice * 0.95;
                targetPriceInput.value = newTarget.toFixed(2);
                updatePercentChangeFromTargetPrice();
            }
        }

        // Revalidate
        validateTargetPrice();
        validatePercentChange();
    }

    // Business day validation functions removed - no longer needed
    // as we only require dates to be at least 7 days in the future

    /**
     * Format date as YYYY-MM-DD for input fields
     */
    function formatDateForInput(date) {
        return date.toISOString().split('T')[0];
    }

    /**
     * Format date in a user-friendly format (e.g., "Monday, January 1, 2025")
     */
    function formatDateForDisplay(date) {
        return date.toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    /**
     * Validate end date
     */
    function validateEndDate() {
        if (!endDateInput) return;

        const endDate = new Date(endDateInput.value);

        if (isNaN(endDate.getTime())) {
            setFieldInvalid(endDateInput, 'Please select a valid date');
            validationState.endDate = false;
        } else if (endDate < minDate) {
            const minDateStr = formatDateForDisplay(minDate);
            setFieldInvalid(endDateInput, `End date must be at least one week from today (${minDateStr} or later)`);
            validationState.endDate = false;
        } else {
            // Calculate days from today for feedback
            const daysDiff = Math.ceil((endDate - today) / (1000 * 60 * 60 * 24));

            setFieldValid(endDateInput, `Prediction timeframe: ${daysDiff} day${daysDiff !== 1 ? 's' : ''} from now`);
            validationState.endDate = true;
        }

        updateSubmitButton();
    }

    /**
     * Validate reasoning
     */
    function validateReasoning() {
        if (!reasoningTextarea) return;

        const reasoning = reasoningTextarea.value.trim();
        const minLength = 30; // Minimum recommended characters

        if (reasoning.length < minLength) {
            setFieldInvalid(reasoningTextarea, `Please provide more detail (${reasoning.length}/${minLength} characters)`);
            validationState.reasoning = false;
        } else {
            setFieldValid(reasoningTextarea, 'Reasoning looks good');
            validationState.reasoning = true;
        }

        // Update character counter
        const reasoningCounter = document.getElementById('reasoning-counter');
        if (reasoningCounter) {
            reasoningCounter.textContent = `${reasoning.length} characters (minimum ${minLength} recommended)`;

            if (reasoning.length < minLength) {
                reasoningCounter.classList.remove('text-success');
                reasoningCounter.classList.add('text-danger');
            } else {
                reasoningCounter.classList.remove('text-danger');
                reasoningCounter.classList.add('text-success');
            }
        }

        updateSubmitButton();
    }

    /**
     * Set field as valid with feedback
     */
    function setFieldValid(field, message = '') {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');

        // Find or create feedback element
        updateFeedbackElement(field, message, true);
    }

    /**
     * Set field as invalid with error message
     */
    function setFieldInvalid(field, message) {
        field.classList.remove('is-valid');
        field.classList.add('is-invalid');

        // Find or create feedback element
        updateFeedbackElement(field, message, false);
    }

    /**
     * Remove validation status
     */
    function removeValidationStatus(field) {
        field.classList.remove('is-valid');
        field.classList.remove('is-invalid');

        // Remove feedback elements
        const container = field.closest('.input-group') || field.closest('.mb-4') || field.parentNode;
        const validFeedback = container.querySelector('.valid-feedback');
        const invalidFeedback = container.querySelector('.invalid-feedback');

        if (validFeedback) validFeedback.remove();
        if (invalidFeedback) invalidFeedback.remove();
    }

    /**
     * Update feedback element with message
     */
    function updateFeedbackElement(field, message, isValid) {
        if (!message) return;

        const container = field.closest('.input-group') || field.closest('.mb-4') || field.parentNode;
        const feedbackClass = isValid ? 'valid-feedback' : 'invalid-feedback';
        const oppositeClass = isValid ? 'invalid-feedback' : 'valid-feedback';

        // Remove opposite feedback if it exists
        const oppositeFeedback = container.querySelector(`.${oppositeClass}`);
        if (oppositeFeedback) oppositeFeedback.remove();

        // Find existing feedback or create new one
        let feedback = container.querySelector(`.${feedbackClass}`);

        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = feedbackClass;
            container.appendChild(feedback);
        }

        feedback.textContent = message;
        feedback.style.display = 'block'; // Ensure visibility
    }

    /**
     * Update submit button state based on validation
     */
    function updateSubmitButton() {
        if (!submitButton) return;

        const isFormValid = validationState.stock &&
            validationState.predictionType &&
            validationState.endDate &&
            validationState.reasoning;

        submitButton.disabled = !isFormValid;

        // Visual feedback on button
        if (isFormValid) {
            submitButton.classList.remove('btn-secondary');
            submitButton.classList.add('btn-primary');
        } else {
            submitButton.classList.remove('btn-primary');
            submitButton.classList.add('btn-secondary');
        }
    }

    /**
     * Handle pre-populated fields from search results or edit form
     */
    function checkPrePopulatedFields() {
        // Check if stock is pre-populated (in edit mode or from "Make Prediction" entry point)
        if (stockIdInput && stockIdInput.value) {
            validateStock(true);

            // Extract symbol from the search input if available
            if (stockSearchInput && stockSearchInput.value) {
                const inputValue = stockSearchInput.value;
                // Format is typically "SYMBOL - Company Name"
                const symbolMatch = inputValue.match(/^([A-Z]+)/);
                if (symbolMatch) {
                    selectedStockSymbol = symbolMatch[1];
                    // Show stock info card
                    const parts = inputValue.split(' - ');
                    if (parts.length >= 2) {
                        showStockInfo(parts[0].trim(), parts.slice(1).join(' - ').trim());
                    }
                    // Fetch the current price for preselected stocks
                    fetchStockPrice(selectedStockSymbol);
                }
            }
        }

        // Check if prediction type is pre-populated
        const selectedPredictionType = document.querySelector('input[name="prediction_type"]:checked');
        if (selectedPredictionType) {
            validatePredictionType();
        }

        // Check if end date is pre-populated
        if (endDateInput && endDateInput.value) {
            validateEndDate();
        }

        // Check if reasoning is pre-populated
        if (reasoningTextarea && reasoningTextarea.value) {
            validateReasoning();
        }

        // Check target price (optional)
        if (targetPriceInput && targetPriceInput.value) {
            validateTargetPrice();
        }
    }

    // Add event listeners for real-time validation
    const predictionTypeRadios = document.querySelectorAll('input[name="prediction_type"]');
    predictionTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            validatePredictionType();
            // When direction changes, enforce constraints and update suggestions
            enforceDirectionConstraints();
            if (currentStockPrice) {
                updatePriceSuggestion(currentStockPrice);
            }
            // Automatically adjust percent change sign based on prediction type
            adjustPercentChangeSign();
        });
    });

    if (targetPriceInput) {
        targetPriceInput.addEventListener('input', validateTargetPrice);
        targetPriceInput.addEventListener('blur', validateTargetPrice);
    }

    // Add event listener for percent change input
    if (percentChangeInput) {
        percentChangeInput.addEventListener('input', function() {
            validatePercentChange();
            updateTargetPriceFromPercentChange();
        });
        percentChangeInput.addEventListener('blur', validatePercentChange);
    }

    if (endDateInput) {
        endDateInput.addEventListener('change', validateEndDate);
        endDateInput.addEventListener('blur', validateEndDate);
    }

    if (reasoningTextarea) {
        reasoningTextarea.addEventListener('input', validateReasoning);
        reasoningTextarea.addEventListener('blur', validateReasoning);
    }

    // Enhanced form submission validation
    if (predictionForm) {
        predictionForm.addEventListener('submit', function (event) {
            // Validate all fields first
            validateStock();
            validatePredictionType();
            validateEndDate();
            validateReasoning();
            validateTargetPrice();

            // Check confirmation checkbox
            const confirmCheckbox = document.getElementById('confirm-checkbox');
            const isConfirmed = confirmCheckbox && confirmCheckbox.checked;

            // Check if form is valid
            if (!validationState.stock ||
                !validationState.predictionType ||
                !validationState.endDate ||
                !validationState.reasoning ||
                !isConfirmed) {

                event.preventDefault();

                // Show error summary at the top of the form
                let errorSummary = document.getElementById('error-summary');
                if (!errorSummary) {
                    errorSummary = document.createElement('div');
                    errorSummary.id = 'error-summary';
                    errorSummary.className = 'alert alert-danger mb-4';
                    predictionForm.prepend(errorSummary);
                }

                // Collect all error messages
                let errorMessages = [];

                if (!validationState.stock) {
                    errorMessages.push('Please select a valid stock from the suggestions');
                }

                if (!validationState.predictionType) {
                    errorMessages.push('Please select a prediction type (Bullish or Bearish)');
                }

                if (!validationState.endDate) {
                    errorMessages.push('Please select a future date for your prediction');
                }

                if (!validationState.reasoning) {
                    errorMessages.push('Please provide detailed reasoning for your prediction (minimum 30 characters)');
                }

                if (!isConfirmed) {
                    errorMessages.push('Please confirm that you understand predictions cannot be edited after submission');
                }

                // Add error messages to summary
                errorSummary.innerHTML = '<h5><i class="bi bi-exclamation-triangle-fill me-2"></i>Please correct the following errors:</h5><ul>' +
                    errorMessages.map(msg => `<li>${msg}</li>`).join('') +
                    '</ul>';

                // Scroll to top of form to show error summary
                errorSummary.scrollIntoView({ behavior: 'smooth' });
            }
        });
    }

    // Handle prediction deletion
    const deleteButtons = document.querySelectorAll('.delete-prediction');
    const deleteModal = document.getElementById('deleteModal');

    if (deleteButtons.length > 0 && deleteModal) {
        let predictionToDelete = null;

        deleteButtons.forEach(button => {
            button.addEventListener('click', function () {
                predictionToDelete = this.getAttribute('data-id');
                const modal = new bootstrap.Modal(deleteModal);
                modal.show();
            });
        });

        // Handle delete confirmation
        const confirmDeleteBtn = document.getElementById('confirmDelete');
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', function () {
                if (predictionToDelete) {
                    const url = `${apiEndpoints.deletePrediction}`.replace("/0", `/${predictionToDelete}`);
                    // Send delete request
                    fetch(url, {
                        method: 'DELETE',
                        headers: {
                            //'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
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
    /**
     * Multi-step form navigation
     */
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');
    const submitBtn = document.getElementById('submit-btn');
    const formSteps = document.querySelectorAll('.form-step');
    const stepIndicators = document.querySelectorAll('.step');

    function showStep(step) {
        // Hide all steps
        formSteps.forEach(s => s.classList.remove('active'));

        // Show current step
        const currentStepEl = document.querySelector(`.form-step[data-step="${step}"]`);
        if (currentStepEl) {
            currentStepEl.classList.add('active');
        }

        // Update step indicators (all 5 steps including confirmation)
        stepIndicators.forEach((indicator, index) => {
            const stepNum = index + 1;
            if (stepNum < step) {
                indicator.classList.add('completed');
                indicator.classList.remove('active');
            } else if (stepNum === step) {
                indicator.classList.add('active');
                indicator.classList.remove('completed');
            } else {
                indicator.classList.remove('active', 'completed');
            }
        });

        // Update buttons
        if (step === 1) {
            prevBtn.style.display = 'none';
        } else {
            prevBtn.style.display = 'inline-flex';
        }

        if (step === totalSteps) {
            // On confirmation step
            nextBtn.style.display = 'none';
            submitBtn.style.display = 'inline-flex';
            // Populate confirmation details
            populateConfirmationScreen();
        } else {
            nextBtn.style.display = 'inline-flex';
            submitBtn.style.display = 'none';
        }

        currentStep = step;
    }

    /**
     * Populate the confirmation screen with prediction details
     */
    function populateConfirmationScreen() {
        // Stock info
        const confirmStock = document.getElementById('confirm-stock');
        if (confirmStock && stockSearchInput) {
            confirmStock.textContent = stockSearchInput.value || 'Not selected';
        }

        // Current price
        const confirmCurrentPrice = document.getElementById('confirm-current-price');
        if (confirmCurrentPrice) {
            confirmCurrentPrice.textContent = currentStockPrice ? `$${currentStockPrice.toFixed(2)}` : 'N/A';
        }

        // Direction
        const confirmDirection = document.getElementById('confirm-direction');
        const predictionType = document.querySelector('input[name="prediction_type"]:checked');
        if (confirmDirection && predictionType) {
            confirmDirection.textContent = predictionType.value;
            confirmDirection.className = 'confirm-value direction-' + predictionType.value.toLowerCase();
        }

        // Target price
        const confirmTargetPrice = document.getElementById('confirm-target-price');
        if (confirmTargetPrice) {
            const targetPrice = parseFloat(targetPriceInput?.value);
            confirmTargetPrice.textContent = !isNaN(targetPrice) ? `$${targetPrice.toFixed(2)}` : 'Not set';
        }

        // Percent change
        const confirmPercentChange = document.getElementById('confirm-percent-change');
        if (confirmPercentChange) {
            const percentChange = parseFloat(percentChangeInput?.value);
            if (!isNaN(percentChange)) {
                const sign = percentChange > 0 ? '+' : '';
                confirmPercentChange.textContent = `${sign}${Math.round(percentChange)}%`;
                confirmPercentChange.className = 'confirm-value ' + (percentChange > 0 ? 'positive' : 'negative');
            } else {
                confirmPercentChange.textContent = 'N/A';
            }
        }

        // Timeframe
        const confirmTimeframe = document.getElementById('confirm-timeframe');
        if (confirmTimeframe && endDateInput?.value) {
            const endDate = new Date(endDateInput.value);
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            confirmTimeframe.textContent = endDate.toLocaleDateString('en-US', options);
        }

        // Reasoning preview
        const confirmReasoning = document.getElementById('confirm-reasoning');
        if (confirmReasoning && reasoningTextarea) {
            const reasoning = reasoningTextarea.value.trim();
            // Show first 200 characters with ellipsis if longer
            confirmReasoning.textContent = reasoning.length > 200
                ? reasoning.substring(0, 200) + '...'
                : reasoning;
        }
    }

    function validateCurrentStep() {
        switch(currentStep) {
            case 1:
                return validationState.stock;
            case 2:
                return validationState.predictionType;
            case 3:
                return validationState.endDate;
            case 4:
                return validationState.reasoning;
            case 5:
                // Confirmation step - already validated
                return true;
            default:
                return false;
        }
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', function() {
            console.log('Next button clicked, currentStep:', currentStep);
            console.log('validationState:', JSON.stringify(validationState));
            console.log('validateCurrentStep() returns:', validateCurrentStep());
            if (validateCurrentStep()) {
                if (currentStep < totalSteps) {
                    showStep(currentStep + 1);
                }
            } else {
                // Show validation errors for current step
                switch(currentStep) {
                    case 1:
                        validateStock();
                        break;
                    case 2:
                        validatePredictionType();
                        break;
                    case 3:
                        validateEndDate();
                        break;
                    case 4:
                        validateReasoning();
                        break;
                }
            }
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', function() {
            if (currentStep > 1) {
                showStep(currentStep - 1);
            }
        });
    }

    /**
     * Stock price fetching functionality
     */
    function showStockInfo(symbol, name) {
        const stockInfoCard = document.getElementById('stock-info-card');
        const stockNameEl = document.getElementById('stock-name');
        const stockSymbolEl = document.getElementById('stock-symbol');

        if (stockInfoCard && stockNameEl && stockSymbolEl) {
            stockNameEl.textContent = name;
            stockSymbolEl.textContent = symbol;
            stockInfoCard.style.display = 'block';

            // Show loader, hide price
            document.getElementById('current-price-loader').style.display = 'block';
            document.getElementById('current-price-display').style.display = 'none';
        }
    }

    function hideStockInfo() {
        const stockInfoCard = document.getElementById('stock-info-card');
        if (stockInfoCard) {
            stockInfoCard.style.display = 'none';
        }
        currentStockPrice = null;
    }

    function fetchStockPrice(symbol) {
        if (!symbol || !apiEndpoints.getStockPrice) return;

        const priceUrl = `${apiEndpoints.getStockPrice}/${symbol}/price`;

        fetch(priceUrl)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data && data.data.price) {
                    currentStockPrice = parseFloat(data.data.price);
                    displayStockPrice(currentStockPrice);
                    updatePriceSuggestion(currentStockPrice);
                } else {
                    displayStockPriceError();
                }
            })
            .catch(error => {
                console.error('Error fetching stock price:', error);
                displayStockPriceError();
            });
    }

    function displayStockPrice(price) {
        const priceDisplay = document.getElementById('current-price');
        const priceLoader = document.getElementById('current-price-loader');
        const priceSection = document.getElementById('current-price-display');
        const targetPriceInput = document.getElementById('target_price');

        if (priceDisplay && priceLoader && priceSection) {
            priceDisplay.textContent = `$${price.toFixed(2)}`;
            priceLoader.style.display = 'none';
            priceSection.style.display = 'block';

            // Update the target price input placeholder with current price
            if (targetPriceInput) {
                targetPriceInput.placeholder = price.toFixed(2);
            }
        }
    }

    function displayStockPriceError() {
        const priceDisplay = document.getElementById('current-price');
        const priceLoader = document.getElementById('current-price-loader');
        const priceSection = document.getElementById('current-price-display');

        if (priceDisplay && priceLoader && priceSection) {
            priceDisplay.textContent = 'N/A';
            priceLoader.style.display = 'none';
            priceSection.style.display = 'block';
        }
    }

    function updatePriceSuggestion(currentPrice) {
        const priceSuggestion = document.getElementById('price-suggestion');
        const predictionType = document.querySelector('input[name="prediction_type"]:checked');

        if (priceSuggestion && currentPrice && predictionType) {
            const type = predictionType.value;
            const suggestedIncrease = currentPrice * 0.10; // 10% increase
            const suggestedDecrease = currentPrice * 0.10; // 10% decrease

            if (type === 'Bullish') {
                const targetPrice = (currentPrice + suggestedIncrease).toFixed(2);
                priceSuggestion.innerHTML = `Current: $${currentPrice.toFixed(2)}. Try $${targetPrice} (+10%) as a target`;
            } else if (type === 'Bearish') {
                const targetPrice = (currentPrice - suggestedDecrease).toFixed(2);
                priceSuggestion.innerHTML = `Current: $${currentPrice.toFixed(2)}. Try $${targetPrice} (-10%) as a target`;
            }
        }
    }

    // Note: prediction type change listeners are already added above with direction enforcement

    // Initialize tooltips
    initTooltips();

    // Check pre-populated fields
    checkPrePopulatedFields();

    // Initialize first step
    showStep(1);
});