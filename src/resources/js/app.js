/**
 * SoVest Application JavaScript
 *
 * This file is the main entry point for all application JavaScript.
 * It initializes core functionality and imports necessary dependencies.
 */

import axios from 'axios';
window.axios = axios;

// Configure axios defaults
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Get CSRF token from meta tag
const token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

/**
 * Flash Message Handler
 * Automatically fade out flash messages after 5 seconds
 */
document.addEventListener('DOMContentLoaded', () => {
    const flashMessages = document.querySelectorAll('.alert');

    flashMessages.forEach((message) => {
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            message.style.transition = 'opacity 0.5s ease-out';
            message.style.opacity = '0';
            setTimeout(() => {
                message.remove();
            }, 500);
        }, 5000);

        // Add close button functionality if exists
        const closeButton = message.querySelector('[data-dismiss="alert"]');
        if (closeButton) {
            closeButton.addEventListener('click', () => {
                message.style.transition = 'opacity 0.5s ease-out';
                message.style.opacity = '0';
                setTimeout(() => {
                    message.remove();
                }, 500);
            });
        }
    });
});

/**
 * Form Validation Helper
 */
window.validateForm = (formId) => {
    const form = document.getElementById(formId);
    if (!form) return false;

    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');

    requiredFields.forEach((field) => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('border-red-500');

            // Show error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'form-error';
            errorDiv.textContent = 'This field is required';

            if (!field.nextElementSibling || !field.nextElementSibling.classList.contains('form-error')) {
                field.parentNode.insertBefore(errorDiv, field.nextSibling);
            }
        } else {
            field.classList.remove('border-red-500');

            // Remove error message
            if (field.nextElementSibling && field.nextElementSibling.classList.contains('form-error')) {
                field.nextElementSibling.remove();
            }
        }
    });

    return isValid;
};

/**
 * Confirmation Dialog Helper
 */
window.confirmAction = (message = 'Are you sure?') => {
    return confirm(message);
};

/**
 * API Helper Functions
 */
window.api = {
    /**
     * Fetch prediction data
     */
    async getPrediction(id) {
        try {
            const response = await axios.get(`/api/predictions/${id}`);
            return response.data;
        } catch (error) {
            console.error('Error fetching prediction:', error);
            throw error;
        }
    },

    /**
     * Create a new prediction
     */
    async createPrediction(data) {
        try {
            const response = await axios.post('/api/predictions/create', data);
            return response.data;
        } catch (error) {
            console.error('Error creating prediction:', error);
            throw error;
        }
    },

    /**
     * Update a prediction
     */
    async updatePrediction(id, data) {
        try {
            const response = await axios.post('/api/predictions/update', { ...data, id });
            return response.data;
        } catch (error) {
            console.error('Error updating prediction:', error);
            throw error;
        }
    },

    /**
     * Delete a prediction
     */
    async deletePrediction(id) {
        try {
            const response = await axios.delete(`/api/predictions/delete/${id}`);
            return response.data;
        } catch (error) {
            console.error('Error deleting prediction:', error);
            throw error;
        }
    },

    /**
     * Search stocks
     */
    async searchStocks(query) {
        try {
            const response = await axios.get('/api/search_stocks', {
                params: { query }
            });
            return response.data;
        } catch (error) {
            console.error('Error searching stocks:', error);
            throw error;
        }
    },

    /**
     * Get stock data
     */
    async getStock(symbol) {
        try {
            const response = await axios.get(`/api/stocks/${symbol}`);
            return response.data;
        } catch (error) {
            console.error('Error fetching stock:', error);
            throw error;
        }
    },

    /**
     * Get stock price
     */
    async getStockPrice(symbol) {
        try {
            const response = await axios.get(`/api/stocks/${symbol}/price`);
            return response.data;
        } catch (error) {
            console.error('Error fetching stock price:', error);
            throw error;
        }
    }
};

/**
 * Debounce Helper
 * Useful for search input handlers
 */
window.debounce = (func, wait = 300) => {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};

/**
 * Format Currency Helper
 */
window.formatCurrency = (value, currency = 'USD') => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency
    }).format(value);
};

/**
 * Format Date Helper
 */
window.formatDate = (date, options = {}) => {
    const defaultOptions = {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    };
    return new Date(date).toLocaleDateString('en-US', { ...defaultOptions, ...options });
};

/**
 * Toast Notification Helper
 */
window.showToast = (message, type = 'info') => {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} fixed top-4 right-4 z-50 animate-fade-in`;
    toast.textContent = message;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.transition = 'opacity 0.5s ease-out';
        toast.style.opacity = '0';
        setTimeout(() => {
            toast.remove();
        }, 500);
    }, 3000);
};

console.log('SoVest application initialized');
