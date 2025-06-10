/**
 * Bienetre Pharma - Main JavaScript File
 */

// Global app object
window.BienetrePharma = {
    // Configuration
    config: {
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        baseUrl: window.location.origin
    },
    
    // Initialize the application
    init: function() {
        this.initEventListeners();
        this.initAlerts();
        this.initSidebar();
        this.initForms();
        console.log('Bienetre Pharma App Initialized');
    },
    
    // Initialize event listeners
    initEventListeners: function() {
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', this.toggleSidebar);
        }
        
        // ESC key to close modals
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                BienetrePharma.closeAllModals();
            }
        });
        
        // Close alerts automatically
        this.autoCloseAlerts();
    },
    
    // Initialize alert system
    initAlerts: function() {
        const alerts = document.querySelectorAll('.alert[data-auto-dismiss]');
        alerts.forEach(alert => {
            const timeout = parseInt(alert.getAttribute('data-auto-dismiss'));
            if (timeout > 0) {
                setTimeout(() => {
                    alert.remove();
                }, timeout);
            }
        });
    },
    
    // Auto-close alerts after timeout
    autoCloseAlerts: function() {
        const flashMessage = document.getElementById('flashMessage');
        if (flashMessage) {
            setTimeout(() => {
                flashMessage.style.opacity = '0';
                setTimeout(() => {
                    flashMessage.remove();
                }, 300);
            }, 5000);
        }
    },
    
    // Initialize sidebar
    initSidebar: function() {
        // Set active menu items based on current route
        this.setActiveMenuItem();
    },
    
    // Set active menu item
    setActiveMenuItem: function() {
        const currentPath = window.location.pathname;
        const menuItems = document.querySelectorAll('.menu-item');
        
        menuItems.forEach(item => {
            const href = item.getAttribute('href');
            if (href && currentPath.startsWith(href)) {
                item.classList.add('active');
            }
        });
    },
    
    // Toggle sidebar for mobile
    toggleSidebar: function() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        
        if (sidebar) {
            sidebar.classList.toggle('show');
        }
    },
    
    // Initialize forms
    initForms: function() {
        // Add CSRF token to all forms
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            if (!form.querySelector('input[name="csrf_token"]') && this.config.csrfToken) {
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = this.config.csrfToken;
                form.appendChild(csrfInput);
            }
        });
        
        // Form validation
        this.initFormValidation();
    },
    
    // Initialize form validation
    initFormValidation: function() {
        const forms = document.querySelectorAll('form[data-validate]');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!BienetrePharma.validateForm(this)) {
                    e.preventDefault();
                }
            });
        });
    },
    
    // Validate form
    validateForm: function(form) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                this.showFieldError(field, 'Ce champ est requis');
                isValid = false;
            } else {
                this.clearFieldError(field);
            }
        });
        
        // Email validation
        const emailFields = form.querySelectorAll('input[type="email"]');
        emailFields.forEach(field => {
            if (field.value && !this.isValidEmail(field.value)) {
                this.showFieldError(field, 'Adresse email invalide');
                isValid = false;
            }
        });
        
        // Phone validation
        const phoneFields = form.querySelectorAll('input[type="tel"]');
        phoneFields.forEach(field => {
            if (field.value && !this.isValidPhone(field.value)) {
                this.showFieldError(field, 'Numéro de téléphone invalide');
                isValid = false;
            }
        });
        
        return isValid;
    },
    
    // Show field error
    showFieldError: function(field, message) {
        this.clearFieldError(field);
        
        field.classList.add('error');
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
    },
    
    // Clear field error
    clearFieldError: function(field) {
        field.classList.remove('error');
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
    },
    
    // Email validation
    isValidEmail: function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },
    
    // Phone validation (Algerian format)
    isValidPhone: function(phone) {
        const phoneRegex = /^(\+213|0)[567]\d{8}$/;
        return phoneRegex.test(phone);
    },
    
    // Show loading overlay
    showLoading: function(message = 'Chargement...') {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            const loadingText = overlay.querySelector('.loading-text');
            if (loadingText) {
                loadingText.textContent = message;
            }
            overlay.style.display = 'flex';
        }
    },
    
    // Hide loading overlay
    hideLoading: function() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    },
    
    // Show alert
    showAlert: function(type, message) {
        const alertHTML = `
            <div class="alert alert-${type}" id="dynamicAlert">
                <div class="alert-content">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
                    <span>${message}</span>
                </div>
                <button class="alert-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.insertAdjacentHTML('afterbegin', alertHTML);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                const alert = document.getElementById('dynamicAlert');
                if (alert) {
                    alert.remove();
                }
            }, 5000);
        }
    },
    
    // Close all modals
    closeAllModals: function() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.style.display = 'none';
        });
    },
    
    // AJAX helper
    ajax: function(options) {
        const defaults = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        const config = Object.assign(defaults, options);
        
        // Add CSRF token for POST requests
        if (config.method === 'POST' && this.config.csrfToken) {
            if (config.data) {
                config.data += '&csrf_token=' + encodeURIComponent(this.config.csrfToken);
            } else {
                config.data = 'csrf_token=' + encodeURIComponent(this.config.csrfToken);
            }
        }
        
        const xhr = new XMLHttpRequest();
        xhr.open(config.method, config.url);
        
        // Set headers
        Object.keys(config.headers).forEach(key => {
            xhr.setRequestHeader(key, config.headers[key]);
        });
        
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                if (config.success) {
                    let response = xhr.responseText;
                    try {
                        response = JSON.parse(response);
                    } catch (e) {
                        // Not JSON, use as-is
                    }
                    config.success(response);
                }
            } else {
                if (config.error) {
                    config.error(xhr.statusText);
                }
            }
        };
        
        xhr.onerror = function() {
            if (config.error) {
                config.error('Network Error');
            }
        };
        
        xhr.send(config.data);
    },
    
    // Format currency
    formatCurrency: function(amount) {
        return new Intl.NumberFormat('fr-DZ', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount) + ' DA';
    },
    
    // Format date
    formatDate: function(date) {
        return new Intl.DateTimeFormat('fr-DZ').format(new Date(date));
    }
};

// Initialize app when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    BienetrePharma.init();
});

// Additional utility functions

// Toggle sidebar function (called from HTML)
function toggleSidebar() {
    BienetrePharma.toggleSidebar();
}

// Print function for invoices
function printInvoice() {
    window.print();
}

// Confirm delete action
function confirmDelete(message = 'Êtes-vous sûr de vouloir supprimer cet élément ?') {
    return confirm(message);
}

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        BienetrePharma.showAlert('success', 'Copié dans le presse-papiers');
    }).catch(function() {
        BienetrePharma.showAlert('error', 'Erreur lors de la copie');
    });
}

// Add some CSS for form validation
const validationCSS = `
<style>
.field-error {
    color: #dc3545;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.form-group input.error,
.form-group select.error,
.form-group textarea.error {
    border-color: #dc3545;
    box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.25);
}

.alert {
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}
</style>
`;

document.head.insertAdjacentHTML('beforeend', validationCSS);