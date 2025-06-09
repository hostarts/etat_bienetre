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
        // Mobile menu