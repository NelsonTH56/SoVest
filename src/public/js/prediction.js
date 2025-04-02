/**
 * SoVest Prediction Management
 * 
 * Enhanced client-side validation for prediction forms with real-time feedback
 */

document.addEventListener('DOMContentLoaded', function () {
    // Form elements
    const predictionForm = document.getElementById('prediction-form');
    const stockSearchInput = document.getElementById('stock-search');
    const stockIdInput = document.getElementById('stock_id');
    const stockSuggestions = document.getElementById('stock-suggestions');
    const predictionTypeSelect = document.getElementById('prediction_type');
    const targetPriceInput = document.getElementById('target_price');
    const endDateInput = document.getElementById('end_date');
    //const dateHelpText = document.querySelector('#end_date').closest('.mb-4').querySelector('.form-text');
    const endDateFeedback = document.getElementById('end-date-feedback');
    const reasoningTextarea = document.getElementById('reasoning');
    const submitButton = document.querySelector('button[type="submit"]');
    const today = new Date();
    today.setHours(0, 0, 0, 0); // Reset time part for date comparison
    // Calculate min/max dates for validation
    const minDate = isBusinessDay(today) ? today : getNextBusinessDay(today);
    const maxDate = addBusinessDays(minDate, 5);

    if (endDateInput != null) {
        // Set min/max attributes on the date input
        endDateInput.min = formatDateForInput(minDate);
        endDateInput.max = formatDateForInput(maxDate);
    }

    

    // Enable tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Update the form text to inform users of business day constraints
    if (
        endDateInput != null &&
        document.querySelector('#end_date').closest('.mb-4').querySelector('.form-text') != null) {
        const dateHelpText = document.querySelector('#end_date').closest('.mb-4').querySelector('.form-text');
        dateHelpText.innerHTML = `Select a business day (Monday-Friday) between <strong>${formatDateForDisplay(minDate)}</strong> and <strong>${formatDateForDisplay(maxDate)}</strong>. Predictions must be within 5 business days.`;
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
                validateStock(false);
                return;
            }

            if (/([A-Za-z]{2,5})(-[A-Za-z]{1,2})?/g.test(searchTerm) !== true) return;

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
                                    stockSuggestions.innerHTML = '';
                                    validateStock(true);
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
        if (!predictionTypeSelect) return;

        const predictionType = predictionTypeSelect.value;

        if (predictionType === 'Bullish' || predictionType === 'Bearish') {
            setFieldValid(predictionTypeSelect, `You selected a ${predictionType.toLowerCase()} prediction`);
            validationState.predictionType = true;
        } else {
            setFieldInvalid(predictionTypeSelect, 'Please select a prediction type');
            validationState.predictionType = false;
        }

        updateSubmitButton();
    }

    /**
     * Validate target price (optional field)
     */
    function validateTargetPrice() {
        if (!targetPriceInput) return;

        const targetPrice = targetPriceInput.value.trim();

        // Target price is optional but must be a valid number if provided
        if (targetPrice === '') {
            removeValidationStatus(targetPriceInput);
            return true;
        }

        const priceValue = parseFloat(targetPrice);

        if (!isNaN(priceValue) && priceValue > 0) {
            setFieldValid(targetPriceInput, `Target price set to $${priceValue.toFixed(2)}`);
            return true;
        } else {
            setFieldInvalid(targetPriceInput, 'Price must be a positive number');
            return false;
        }
    }

    /**
     * Check if a date is a business day (Monday to Friday)
     * @param {Date} date - The date to check
     * @return {boolean} - True if it's a business day, false otherwise
     */
    function isBusinessDay(date) {
        const dayOfWeek = date.getUTCDay(); // 0 (Sunday) to 6 (Saturday), dates from the date control are in UTC so we must use getUTCDay() here.
        return dayOfWeek >= 1 && dayOfWeek <= 5; // Monday to Friday
    }

    /**
     * Get the next business day from a given date
     * @param {Date} date - The starting date
     * @return {Date} - The next business day
     */
    function getNextBusinessDay(date) {
        const nextDay = new Date(date);
        nextDay.setDate(nextDay.getDate() + 1);

        // Keep adding days until we find a business day
        while (!isBusinessDay(nextDay)) {
            nextDay.setDate(nextDay.getDate() + 1);
        }

        return nextDay;
    }

    /**
     * Calculate the date that is exactly 5 business days from today
     * @return {Date} - Date 5 business days from today/next business day
     */
    function getMaxBusinessDay() {
        //const today = new Date();
        //today.setHours(0, 0, 0, 0);

        // Start from today if it's a business day, otherwise next business day
        const startDate = isBusinessDay(today) ? new Date(today) : getNextBusinessDay(today);
        return addBusinessDays(startDate, 5);
    }

    /**
     * Calculate a date that is N business days from a given date
     * @param {Date} startDate - The starting date
     * @param {number} businessDays - Number of business days to add
     * @return {Date} - Resulting date after adding business days
     */
    function addBusinessDays(startDate, businessDays) {
        const result = new Date(startDate);

        // If the start date is not a business day, start from the next business day
        if (!isBusinessDay(result)) {
            const nextBusiness = getNextBusinessDay(result);
            result.setTime(nextBusiness.getTime());
            // We already moved to the first business day, so subtract 1
            businessDays--;
        }

        // Add the remaining business days
        while (businessDays > 0) {
            result.setDate(result.getDate() + 1);
            if (isBusinessDay(result)) {
                businessDays--;
            }
        }

        return result;
    }

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
        } else if (!isBusinessDay(endDate)) {
            setFieldInvalid(endDateInput, 'End date must be a business day (Monday-Friday)');
            validationState.endDate = false;
        } else if (endDate < minDate) {
            //TODO: there is a bug here because endDate, the data that is coming from the html controler, is int UTC and the minDate and maxDate aare local time
            const minDateStr = formatDateForDisplay(minDate);
            setFieldInvalid(endDateInput, `End date must be today or a future business day (${minDateStr} or later)`);
            validationState.endDate = false;
        } else if (endDate > maxDate) {
            const maxDateStr = formatDateForDisplay(maxDate);
            setFieldInvalid(endDateInput, `End date must be within 5 business days (no later than ${maxDateStr})`);
            validationState.endDate = false;
        } else {
            // Calculate business days from today for feedback
            let businessDays = 0;
            let currentDate = new Date(today);

            while (currentDate < endDate) {
                currentDate.setDate(currentDate.getDate() + 1);
                if (isBusinessDay(currentDate)) {
                    businessDays++;
                }
            }

            setFieldValid(endDateInput, `Prediction timeframe: ${businessDays} business day${businessDays !== 1 ? 's' : ''} from now`);
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
        // Check if stock is pre-populated (in edit mode, the hidden input will have a value)
        if (stockIdInput && stockIdInput.value) {
            validateStock(true);
        }

        // Check if prediction type is pre-populated
        if (predictionTypeSelect && predictionTypeSelect.value) {
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
    if (predictionTypeSelect) {
        predictionTypeSelect.addEventListener('change', validatePredictionType);
    }

    if (targetPriceInput) {
        targetPriceInput.addEventListener('input', validateTargetPrice);
        targetPriceInput.addEventListener('blur', validateTargetPrice);
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

            // Check if form is valid
            if (!validationState.stock ||
                !validationState.predictionType ||
                !validationState.endDate ||
                !validationState.reasoning) {

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

    // Handle prediction editing
    const editButtons = document.querySelectorAll('.edit-prediction');

    if (editButtons.length > 0) {
        editButtons.forEach(button => {
            button.addEventListener('click', function () {
                const predictionId = this.getAttribute('data-id');
                // Redirect to edit page using Laravel route
                window.location.href = `/predictions/edit/${predictionId}`;
            });
        });
    }

    // Initialize tooltips
    initTooltips();

    // Check pre-populated fields
    checkPrePopulatedFields();
});