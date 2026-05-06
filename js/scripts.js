// js/scripts.js - Fonctions JavaScript globales

// ============================================
// FONCTIONS GÉNÉRALES
// ============================================

// Attendre que le DOM soit chargé
document.addEventListener('DOMContentLoaded', function() {
    console.log('Plateforme Éducative - Chargée avec succès');
    
    // Initialiser les tooltips (si présents)
    initTooltips();
    
    // Initialiser la validation des formulaires
    initFormValidation();
    
    // Masquer les messages d'alerte après 5 secondes
    autoHideAlerts();
});

// ============================================
// GESTION DES ALERTES
// ============================================

/**
 * Masque automatiquement les messages d'alerte après X secondes
 */
function autoHideAlerts() {
    const alerts = document.querySelectorAll('.alert-success, .alert-error, .alert-info');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alert.parentNode) alert.style.display = 'none';
            }, 500);
        }, 4000);
    });
}

/**
 * Affiche un message d'alerte
 * @param {string} message - Le message à afficher
 * @param {string} type - Le type (success, error, info)
 * @param {string} targetId - L'ID de l'élément cible
 */
function showAlert(message, type = 'success', targetId = 'alerts-container') {
    let container = document.getElementById(targetId);
    if (!container) {
        container = document.createElement('div');
        container.id = targetId;
        const mainContent = document.querySelector('.main-content, .container');
        if (mainContent) mainContent.insertBefore(container, mainContent.firstChild);
    }
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
        <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
    `;
    container.appendChild(alert);
    setTimeout(() => alert.remove(), 5000);
}

// ============================================
// VALIDATION DES FORMULAIRES
// ============================================

/**
 * Initialise la validation des formulaires
 */
function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate="true"]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
}

/**
 * Valide un formulaire
 * @param {HTMLFormElement} form - Le formulaire à valider
 * @returns {boolean}
 */
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('error');
            isValid = false;
            showError(input, 'Ce champ est requis');
        } else {
            input.classList.remove('error');
            removeError(input);
        }
        
        // Validation spécifique email
        if (input.type === 'email' && input.value.trim()) {
            const emailRegex = /^[^\s@]+@([^\s@.,]+\.)+[^\s@.,]{2,}$/;
            if (!emailRegex.test(input.value)) {
                input.classList.add('error');
                isValid = false;
                showError(input, 'Email invalide');
            }
        }
        
        // Validation mot de passe
        if (input.type === 'password' && input.value.trim()) {
            if (input.value.length < 6) {
                input.classList.add('error');
                isValid = false;
                showError(input, 'Le mot de passe doit contenir au moins 6 caractères');
            }
        }
    });
    
    return isValid;
}

/**
 * Affiche un message d'erreur sous un champ
 * @param {HTMLElement} input - Le champ en erreur
 * @param {string} message - Le message d'erreur
 */
function showError(input, message) {
    let error = input.parentElement.querySelector('.error-message');
    if (!error) {
        error = document.createElement('small');
        error.className = 'error-message';
        input.parentElement.appendChild(error);
    }
    error.style.color = '#ef5350';
    error.style.fontSize = '12px';
    error.style.marginTop = '5px';
    error.style.display = 'block';
    error.textContent = message;
}

/**
 * Supprime le message d'erreur d'un champ
 * @param {HTMLElement} input - Le champ corrigé
 */
function removeError(input) {
    const error = input.parentElement.querySelector('.error-message');
    if (error) error.remove();
}

// ============================================
// GESTION DES MODALES
// ============================================

/**
 * Ouvre une modale
 * @param {string} modalId - L'ID de la modale
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Focus sur le premier champ
        const firstInput = modal.querySelector('input:not([type="hidden"]), textarea, select');
        if (firstInput) firstInput.focus();
    }
}

/**
 * Ferme une modale
 * @param {string} modalId - L'ID de la modale
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

/**
 * Initialise les modales (fermeture au clic externe)
 */
function initModals() {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });
}

// ============================================
// GESTION DES CONFIRMATIONS
// ============================================

/**
 * Affiche une boîte de confirmation personnalisée
 * @param {string} message - Le message de confirmation
 * @param {function} onConfirm - Callback si confirmé
 * @param {function} onCancel - Callback si annulé
 */
function confirmAction(message, onConfirm, onCancel) {
    if (confirm(message)) {
        if (onConfirm) onConfirm();
    } else {
        if (onCancel) onCancel();
    }
}

/**
 * Bouton de suppression avec confirmation
 */
function initDeleteButtons() {
    const deleteBtns = document.querySelectorAll('.btn-delete, .delete-btn, [data-confirm]');
    deleteBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm') || 'Êtes-vous sûr de vouloir supprimer cet élément ?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
}

// ============================================
// GESTION DU CLAVIER (Touche ESCAPE)
// ============================================

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const activeModals = document.querySelectorAll('.modal.active');
        activeModals.forEach(modal => {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        });
    }
});

// ============================================
// FONCTIONS D'INITIALISATION
// ============================================

/**
 * Initialise les tooltips
 */
function initTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', function(e) {
            const tooltip = document.createElement('div');
            tooltip.className = 'custom-tooltip';
            tooltip.textContent = this.getAttribute('data-tooltip');
            tooltip.style.position = 'absolute';
            tooltip.style.background = '#333';
            tooltip.style.color = '#fff';
            tooltip.style.padding = '5px 10px';
            tooltip.style.borderRadius = '5px';
            tooltip.style.fontSize = '12px';
            tooltip.style.zIndex = '1000';
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
            tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
            
            this.addEventListener('mouseleave', function() {
                tooltip.remove();
            });
        });
    });
}

// ============================================
// FONCTIONS POUR LES GRAPHIQUES (Chart.js)
// ============================================

/**
 * Crée un graphique en barres
 * @param {string} canvasId - L'ID du canvas
 * @param {object} data - Les données du graphique
 */
function createBarChart(canvasId, data) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' }
            }
        }
    });
}

/**
 * Crée un graphique en ligne
 * @param {string} canvasId - L'ID du canvas
 * @param {object} data - Les données du graphique
 */
function createLineChart(canvasId, data) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'line',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

// ============================================
// FONCTIONS POUR LE CALENDRIER
// ============================================

/**
 * Calendrier dynamique (utilisé dans dashboard_etudiant.php)
 * L'implémentation complète est déjà dans dashboard_etudiant.php
 * Cette fonction est un complément
 */
function initCalendar() {
    const calendarContainer = document.getElementById('calendar');
    if (!calendarContainer) return;
    
    // La logique du calendrier est déjà implémentée
    // dans dashboard_etudiant.php
}

// ============================================
// FONCTIONS POUR LE DARK MODE (optionnel)
// ============================================

/**
 * Bascule entre le mode clair et sombre
 */
function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');
    localStorage.setItem('darkMode', isDark);
}

/**
 * Initialise le thème selon les préférences
 */
function initTheme() {
    const savedTheme = localStorage.getItem('darkMode');
    if (savedTheme === 'true') {
        document.body.classList.add('dark-mode');
    }
}

// ============================================
// EXPORT (si module)
// ============================================

// Initialiser les fonctions au chargement
initModals();
initDeleteButtons();
initTheme();