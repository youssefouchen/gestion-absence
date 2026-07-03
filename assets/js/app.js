/**
 * ENSAH - Gestion des Absences
 * JavaScript Front-end Module
 * Année: 2025/2026
 * 
 * Fonctionnalités:
 * - Recherche AJAX d'étudiants
 * - Gestion des absences (sélection multi)
 * - Validation de formulaires
 * - Notifications Toast
 * - Export/Impression
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ===== CONFIGURATION =====
    const CONFIG = {
        apiBaseUrl: '',
        warningThreshold: 3,  // Seuil d'avertissement absences
        cancelThresholdDays: 3, // Délai annulation absence
        debounceDelay: 300
    };

    // ===== UTILITAIRES =====
    const Utils = {
        // Debounce pour la recherche
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        // Formatage de date
        formatDate: function(dateString) {
            const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
            return new Date(dateString).toLocaleDateString('fr-FR', options);
        },

        // Génération d'ID unique
        uuid: function() {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                const r = Math.random() * 16 | 0;
                return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
            });
        },

        // Échappement HTML (anti-XSS)
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // ===== NOTIFICATIONS TOAST =====
    const Toast = {
        container: null,
        
        init: function() {
            this.container = document.getElementById('toast-container');
            if (!this.container) {
                this.container = document.createElement('div');
                this.container.id = 'toast-container';
                this.container.className = 'toast-container';
                document.body.appendChild(this.container);
            }
        },

        show: function(message, type = 'info', duration = 4000) {
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                warning: 'fa-exclamation-triangle',
                info: 'fa-info-circle'
            };

            const toast = document.createElement('div');
            toast.className = `toast bg-${type} text-white fade-in`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <div class="toast-body d-flex align-items-center gap-2">
                    <i class="fas ${icons[type]}"></i>
                    <span>${Utils.escapeHtml(message)}</span>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="toast"></button>
                </div>
            `;

            this.container.appendChild(toast);

            // Auto-dismiss
            setTimeout(() => {
                toast.classList.remove('fade-in');
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, duration);

            // Click to dismiss
            toast.addEventListener('click', (e) => {
                if (!e.target.closest('button')) {
                    toast.remove();
                }
            });
        }
    };
    Toast.init();

    // ===== RECHERCHE ÉTUDIANTS AJAX =====
    const StudentSearch = {
        init: function() {
            const searchInput = document.getElementById('student-search');
            const resultsContainer = document.getElementById('search-results');
            
            if (!searchInput) return;

            const searchHandler = Utils.debounce(async function(query) {
                if (query.length < 2) {
                    if (resultsContainer) resultsContainer.innerHTML = '';
                    return;
                }

                try {
                    const response = await fetch(
                        `index.php?api=search_students&q=${encodeURIComponent(query)}`
                    );
                    const students = await response.json();
                    StudentSearch.renderResults(students, resultsContainer);
                } catch (error) {
                    console.error('Erreur recherche:', error);
                    Toast.show('Erreur lors de la recherche', 'error');
                }
            }, CONFIG.debounceDelay);

            searchInput.addEventListener('input', (e) => {
                searchHandler(e.target.value.trim());
            });

            // Recherche phonétique (optionnel)
            if (document.getElementById('phonetic-search')) {
                document.getElementById('phonetic-search').addEventListener('click', function() {
                    const query = searchInput.value.trim();
                    if (query) {
                        fetch(`index.php?api=search_phonetic&q=${encodeURIComponent(query)}`)
                            .then(r => r.json())
                            .then(data => StudentSearch.renderResults(data, resultsContainer))
                            .catch(err => Toast.show('Recherche phonétique indisponible', 'warning'));
                    }
                });
            }
        },

        renderResults: function(students, container) {
            if (!container) return;
            
            if (students.length === 0) {
                container.innerHTML = '<div class="text-muted p-3">Aucun résultat</div>';
                return;
            }

            container.innerHTML = students.map(s => `
                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" 
                     data-id="${s.id}" style="cursor:pointer">
                    <div>
                        <strong>${Utils.escapeHtml(s.name_fr)}</strong>
                        <small class="text-muted d-block">${Utils.escapeHtml(s.massar)} • ${Utils.escapeHtml(s.level)}</small>
                    </div>
                    <i class="fas fa-chevron-right text-muted"></i>
                </div>
            `).join('');

            // Click sur un résultat
            container.querySelectorAll('.list-group-item').forEach(item => {
                item.addEventListener('click', function() {
                    const studentId = this.dataset.id;
                    window.location.href = `index.php?page=fiche_etudiant&id=${studentId}`;
                });
            });
        }
    };
    StudentSearch.init();

    // ===== SAISIE ABSENCES (Interface Enseignant) =====
    const AbsenceEntry = {
        selectedStudents: new Set(),
        
        init: function() {
            // Charger les étudiants d'une classe
            const levelSelect = document.getElementById('saisie-level');
            const loadBtn = document.getElementById('load-students-btn');
            
            if (loadBtn && levelSelect) {
                loadBtn.addEventListener('click', () => this.loadStudents(levelSelect.value));
            }

            // Délégation d'événement pour les cartes étudiants
            document.addEventListener('click', (e) => {
                const card = e.target.closest('.student-card');
                if (card && card.dataset.studentId) {
                    this.toggleStudent(card, card.dataset.studentId);
                }
            });

            // Validation et soumission
            const form = document.getElementById('absence-form');
            if (form) {
                form.addEventListener('submit', (e) => this.handleSubmit(e));
            }

            // Mise à jour du compteur
            this.updateCounter();
        },

        async loadStudents(level) {
            const grid = document.getElementById('students-grid');
            if (!grid) return;

            grid.innerHTML = '<div class="col-12 text-center py-4"><div class="spinner-border text-primary"></div></div>';

            try {
                const response = await fetch(`index.php?api=get_students&level=${level}`);
                const students = await response.json();
                
                if (students.length === 0) {
                    grid.innerHTML = '<div class="col-12 text-muted text-center py-4">Aucun étudiant dans cette classe</div>';
                    return;
                }

                grid.innerHTML = students.map(s => `
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="student-card" data-student-id="${s.id}">
                            <div class="student-photo">
                                ${s.photo ? `<img src="${s.photo}" alt="${s.name_fr}">` : s.name_fr.charAt(0).toUpperCase()}
                            </div>
                            <div class="fw-bold text-truncate">${Utils.escapeHtml(s.name_fr)}</div>
                            <small class="text-muted">${Utils.escapeHtml(s.massar)}</small>
                            <span class="status-indicator mt-2"></span>
                        </div>
                    </div>
                `).join('');

            } catch (error) {
                console.error('Erreur chargement étudiants:', error);
                grid.innerHTML = '<div class="col-12 text-danger text-center py-4">Erreur de chargement</div>';
                Toast.show('Impossible de charger la liste des étudiants', 'error');
            }
        },

        toggleStudent: function(card, studentId) {
            card.classList.toggle('selected');
            
            if (this.selectedStudents.has(studentId)) {
                this.selectedStudents.delete(studentId);
            } else {
                this.selectedStudents.add(studentId);
            }
            
            this.updateCounter();
        },

        updateCounter: function() {
            const counter = document.getElementById('selected-count');
            const submitBtn = document.getElementById('submit-absences');
            
            if (counter) {
                counter.textContent = `${this.selectedStudents.size} étudiant(s) sélectionné(s)`;
            }
            
            if (submitBtn) {
                submitBtn.disabled = this.selectedStudents.size === 0;
            }
        },

        async handleSubmit(e) {
            e.preventDefault();
            
            if (this.selectedStudents.size === 0) {
                Toast.show('Veuillez sélectionner au moins un étudiant', 'warning');
                return;
            }

            const form = e.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Désactiver le bouton pendant l'envoi
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-sm"></span> Enregistrement...';

            try {
                const formData = new FormData(form);
                formData.append('student_ids', JSON.stringify(Array.from(this.selectedStudents)));

                const response = await fetch('index.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                
                if (result.success) {
                    Toast.show(`${this.selectedStudents.size} absence(s) enregistrée(s)`, 'success');
                    // Reset
                    this.selectedStudents.clear();
                    document.querySelectorAll('.student-card.selected').forEach(c => c.classList.remove('selected'));
                    this.updateCounter();
                    // Optionnel: recharger la page après délai
                    setTimeout(() => location.reload(), 1500);
                } else {
                    throw new Error(result.message || 'Erreur serveur');
                }
            } catch (error) {
                console.error('Erreur soumission:', error);
                Toast.show('Erreur lors de l\'enregistrement: ' + error.message, 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }
    };
    
    // Initialiser seulement si on est sur la page de saisie
    if (document.getElementById('students-grid')) {
        AbsenceEntry.init();
    }

    // ===== EXPORT CSV & IMPRESSION =====
    const ExportTools = {
        init: function() {
            // Export CSV
            const exportBtn = document.getElementById('export-csv-btn');
            if (exportBtn) {
                exportBtn.addEventListener('click', () => {
                    window.location.href = 'index.php?api=export_csv';
                    Toast.show('Export CSV en cours...', 'info');
                });
            }

            // Impression
            const printBtn = document.getElementById('print-btn');
            if (printBtn) {
                printBtn.addEventListener('click', () => {
                    window.print();
                });
            }
        }
    };
    ExportTools.init();

    // ===== VALIDATION FORMULAIRES =====
    const FormValidator = {
        init: function() {
            // Validation en temps réel des champs requis
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', (e) => {
                    let isValid = true;
                    
                    form.querySelectorAll('[required]').forEach(field => {
                        if (!field.value.trim()) {
                            isValid = false;
                            field.classList.add('is-invalid');
                            
                            // Afficher message d'erreur
                            let errorDiv = field.nextElementSibling;
                            if (!errorDiv || !errorDiv.classList.contains('invalid-feedback')) {
                                errorDiv = document.createElement('div');
                                errorDiv.className = 'invalid-feedback d-block';
                                errorDiv.textContent = 'Ce champ est requis';
                                field.parentNode.insertBefore(errorDiv, field.nextSibling);
                            }
                        } else {
                            field.classList.remove('is-invalid');
                            const errorDiv = field.nextElementSibling;
                            if (errorDiv?.classList.contains('invalid-feedback')) {
                                errorDiv.remove();
                            }
                        }
                    });

                    if (!isValid) {
                        e.preventDefault();
                        Toast.show('Veuillez corriger les erreurs du formulaire', 'error');
                    }
                });

                // Retirer l'erreur quand l'utilisateur commence à saisir
                form.querySelectorAll('[required]').forEach(field => {
                    field.addEventListener('input', function() {
                        this.classList.remove('is-invalid');
                        const errorDiv = this.nextElementSibling;
                        if (errorDiv?.classList.contains('invalid-feedback')) {
                            errorDiv.remove();
                        }
                    });
                });
            });
        }
    };
    FormValidator.init();

    // ===== GESTION DES MODALES DYNAMIQUES =====
    const ModalManager = {
        open: function(content, title = '', onClose = null) {
            const modal = document.createElement('div');
            modal.className = 'modal fade show';
            modal.style.display = 'block';
            modal.setAttribute('tabindex', '-1');
            
            modal.innerHTML = `
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${Utils.escapeHtml(title)}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">${content}</div>
                    </div>
                </div>
                <div class="modal-backdrop fade show"></div>
            `;

            document.body.appendChild(modal);

            // Fermeture
            const closeBtn = modal.querySelector('.btn-close');
            const backdrop = modal.querySelector('.modal-backdrop');
            
            const closeModal = () => {
                modal.classList.remove('show');
                backdrop?.remove();
                setTimeout(() => modal.remove(), 150);
                if (onClose) onClose();
            };

            closeBtn.addEventListener('click', closeModal);
            backdrop?.addEventListener('click', closeModal);

            // Échap pour fermer
            const escHandler = (e) => {
                if (e.key === 'Escape') {
                    closeModal();
                    document.removeEventListener('keydown', escHandler);
                }
            };
            document.addEventListener('keydown', escHandler);

            return { close: closeModal };
        }
    };

    // ===== JUSTIFICATION D'ABSENCE (Étudiant) =====
    const JustificationHandler = {
        init: function() {
            document.addEventListener('click', (e) => {
                if (e.target.closest('.btn-justify')) {
                    const absenceId = e.target.closest('.btn-justify').dataset.absenceId;
                    this.openJustifyModal(absenceId);
                }
            });
        },

        openJustifyModal: function(absenceId) {
            const content = `
                <form id="justify-form" enctype="multipart/form-data">
                    <input type="hidden" name="absence_id" value="${absenceId}">
                    <div class="mb-3">
                        <label class="form-label">Fichier justificatif (PDF, JPG, PNG)</label>
                        <input type="file" name="justification_file" class="form-control" 
                               accept=".pdf,.jpg,.jpeg,.png" required>
                        <small class="text-muted">Taille max: 5MB</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Commentaire (optionnel)</label>
                        <textarea name="comment" class="form-control" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Envoyer la justification</button>
                </form>
            `;

            const modal = ModalManager.open(content, 'Justifier une absence');

            document.getElementById('justify-form')?.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const formData = new FormData(e.target);
                
                try {
                    const response = await fetch('index.php?api=submit_justification', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        Toast.show('Justification envoyée avec succès', 'success');
                        modal.close();
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        throw new Error(result.message);
                    }
                } catch (error) {
                    Toast.show('Erreur: ' + error.message, 'error');
                }
            });
        }
    };
    
    // Initialiser si des boutons de justification existent
    if (document.querySelector('.btn-justify')) {
        JustificationHandler.init();
    }

    // ===== RÉCLAMATIONS =====
    const ComplaintHandler = {
        init: function() {
            document.addEventListener('click', (e) => {
                if (e.target.closest('.btn-complain')) {
                    const absenceId = e.target.closest('.btn-complain').dataset.absenceId;
                    this.openComplaintModal(absenceId);
                }
            });
        },

        openComplaintModal: function(absenceId) {
            const content = `
                <form id="complaint-form">
                    <input type="hidden" name="absence_id" value="${absenceId}">
                    <div class="mb-3">
                        <label class="form-label">Motif de la réclamation</label>
                        <textarea name="message" class="form-control" rows="4" 
                                  placeholder="Décrivez votre réclamation..." required></textarea>
                    </div>
                    <div class="alert alert-info small">
                        <i class="fas fa-info-circle me-1"></i>
                        Votre réclamation sera examinée par l'administration sous 48h.
                    </div>
                    <button type="submit" class="btn btn-warning w-100">Envoyer la réclamation</button>
                </form>
            `;

            const modal = ModalManager.open(content, 'Soumettre une réclamation');

            document.getElementById('complaint-form')?.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                try {
                    const formData = new FormData(e.target);
                    const response = await fetch('index.php?api=submit_complaint', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        Toast.show('Réclamation envoyée. Vous serez notifié de la réponse.', 'success');
                        modal.close();
                    } else {
                        throw new Error(result.message);
                    }
                } catch (error) {
                    Toast.show('Erreur: ' + error.message, 'error');
                }
            });
        }
    };
    
    if (document.querySelector('.btn-complain')) {
        ComplaintHandler.init();
    }

    // ===== MISE À JOUR EN TEMPS RÉEL (WebSocket simulation via polling) =====
    const RealtimeUpdates = {
        interval: null,
        
        start: function(userId) {
            // Polling toutes les 30 secondes pour les notifications
            this.interval = setInterval(async () => {
                try {
                    const response = await fetch(`index.php?api=get_notifications&user_id=${userId}`);
                    const notifications = await response.json();
                    
                    notifications.forEach(notif => {
                        if (!notif.read) {
                            Toast.show(notif.message, notif.type);
                            // Marquer comme lu
                            fetch(`index.php?api=mark_notification_read&id=${notif.id}`);
                        }
                    });
                } catch (error) {
                    console.log('Polling notifications:', error);
                }
            }, 30000);
        },
        
        stop: function() {
            if (this.interval) clearInterval(this.interval);
        }
    };

    // Démarrer le polling si utilisateur connecté
    const currentUser = document.body.dataset.userId;
    if (currentUser) {
        RealtimeUpdates.start(currentUser);
        
        // Arrêter au déchargement de la page
        window.addEventListener('beforeunload', () => RealtimeUpdates.stop());
    }

    // ===== EXPOSER LES FONCTIONS GLOBALES (pour les handlers inline) =====
    window.ENSAH = {
        Toast,
        Utils,
        ModalManager,
        // Exemple: ENSAH.Toast.show('Message', 'success');
    };

    // ===== DEBUG MODE (à désactiver en production) =====
    if (localStorage.getItem('ensah_debug') === 'true') {
        console.log('🔧 ENSAH Debug Mode activé');
        console.log('CONFIG:', CONFIG);
    }
});