// PWA Installation Handler
class PWAInstaller {
    constructor() {
        this.deferredPrompt = null;
        this.installButton = document.getElementById('pwa-install-btn');
        this.isInstalled = false;
        this.init();
    }

    init() {
        console.log('PWA: Initializing PWA installer');
        
        // Check if PWA is already installed
        this.checkIfInstalled();
        
        // Always show button if not installed (for better UX)
        setTimeout(() => {
            if (!this.isInstalled) {
                console.log('PWA: Showing install button (not installed)');
                this.showInstallButton();
            }
        }, 1000);

        // Listen for the beforeinstallprompt event
        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('PWA: beforeinstallprompt event fired');
            
            // Prevent the mini-infobar from appearing on mobile
            e.preventDefault();
            
            // Save the event so it can be triggered later
            this.deferredPrompt = e;
            
            // Show the install button
            this.showInstallButton();
        });

        // Listen for the appinstalled event
        window.addEventListener('appinstalled', (e) => {
            console.log('PWA: App was installed');
            this.isInstalled = true;
            this.hideInstallButton();
            this.showInstallSuccessMessage();
            
            // Store installation status
            localStorage.setItem('pwa-installed', 'true');
        });

        // Handle install button click
        if (this.installButton) {
            this.installButton.addEventListener('click', () => {
                this.handleInstallClick();
            });
        }

        // Registrar Service Worker desde el método de clase (con guardado)
        if (typeof this.registerServiceWorker === 'function') {
            this.registerServiceWorker();
        } else {
            console.warn('PWA: registerServiceWorker no disponible');
        }
        
        // Listen for page visibility changes to maintain button state
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                console.log('PWA: Page became visible, checking button state');
                setTimeout(() => {
                    this.maintainButtonVisibility();
                }, 500);
            }
        });
        
        // Listen for focus events to maintain button state
        window.addEventListener('focus', () => {
            console.log('PWA: Window focused, checking button state');
            setTimeout(() => {
                this.maintainButtonVisibility();
            }, 500);
        });
    }

    showInstallButton() {
        if (this.installButton && !this.isInstalled) {
            console.log('PWA: Showing install button');
            this.installButton.classList.remove('hidden');
            this.installButton.title = 'Instalar aplicación';
            
            // Store button state
            sessionStorage.setItem('pwa-button-shown', 'true');
        }
    }

    hideInstallButton() {
        if (this.installButton) {
            console.log('PWA: Hiding install button');
            this.installButton.classList.add('hidden');
            
            // Clear button state
            sessionStorage.removeItem('pwa-button-shown');
        }
    }

    // New method to ensure button visibility is maintained
    maintainButtonVisibility() {
        const shouldShow = !this.isInstalled && 
                          (this.deferredPrompt || sessionStorage.getItem('pwa-button-shown') === 'true');
        
        if (shouldShow) {
            this.showInstallButton();
        } else if (this.isInstalled) {
            this.hideInstallButton();
        }
    }

    async handleInstallClick() {
        console.log('PWA: Install button clicked');
        
        if (!this.deferredPrompt) {
            console.log('PWA: No deferred prompt available');
            // Show manual installation instructions
            this.showManualInstallInstructions();
            return;
        }

        try {
            // Show the install prompt
            this.deferredPrompt.prompt();
            
            // Wait for the user to respond to the prompt
            const { outcome } = await this.deferredPrompt.userChoice;
            
            console.log(`PWA: User response to the install prompt: ${outcome}`);
            
            if (outcome === 'accepted') {
                console.log('PWA: User accepted the install prompt');
            } else {
                console.log('PWA: User dismissed the install prompt');
            }
            
            // Clear the deferredPrompt
            this.deferredPrompt = null;
            this.hideInstallButton();
            
        } catch (error) {
            console.error('PWA: Error during installation:', error);
            this.showManualInstallInstructions();
        }
    }

    showManualInstallInstructions() {
        const userAgent = navigator.userAgent.toLowerCase();
        let instructions = '';
        
        if (userAgent.includes('chrome')) {
            instructions = 'Para instalar:\n1. Haz clic en el menú de Chrome (⋮)\n2. Selecciona "Instalar aplicación"\n3. Confirma la instalación';
        } else if (userAgent.includes('firefox')) {
            instructions = 'Para instalar:\n1. Haz clic en el menú de Firefox (☰)\n2. Busca la opción "Instalar aplicación"\n3. Confirma la instalación';
        } else if (userAgent.includes('safari')) {
            instructions = 'Para instalar en Safari:\n1. Haz clic en el botón de compartir\n2. Selecciona "Agregar a pantalla de inicio"\n3. Confirma la instalación';
        } else {
            instructions = 'Para instalar esta aplicación, busca la opción "Instalar aplicación" o "Agregar a pantalla de inicio" en el menú de tu navegador.';
        }
        
        alert(instructions);
    }

    checkIfInstalled() {
        // Check multiple indicators of PWA installation
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches;
        const isInWebAppiOS = (window.navigator.standalone === true);
        const isInWebAppChrome = (window.matchMedia('(display-mode: standalone)').matches);
        const wasInstalled = localStorage.getItem('pwa-installed') === 'true';
        
        this.isInstalled = isStandalone || isInWebAppiOS || isInWebAppChrome || wasInstalled;
        
        console.log('PWA: Installation check:', {
            isStandalone,
            isInWebAppiOS,
            isInWebAppChrome,
            wasInstalled,
            finalResult: this.isInstalled
        });
        
        if (this.isInstalled) {
            console.log('PWA: App is already installed, hiding button');
            this.hideInstallButton();
        } else {
            console.log('PWA: App is not installed, will show button');
        }
        
        return this.isInstalled;
    }

    showInstallSuccessMessage() {
        // Show success message using SweetAlert2 if available
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '¡Aplicación Instalada!',
                text: 'El Sistema de Botica se ha instalado correctamente en tu dispositivo.',
                icon: 'success',
                confirmButtonText: 'Perfecto',
                confirmButtonColor: '#fb7185'
            });
        } else {
            // Fallback to alert
            alert('¡Aplicación instalada correctamente!');
        }
    }

    // Method to manually trigger install (for testing)
    triggerInstall() {
        if (this.deferredPrompt) {
            this.handleInstallClick();
        } else {
            console.log('PWA: No install prompt available');
        }
    }

    // Registrar Service Worker (método de clase)
    registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', async () => {
                try {
                    const registration = await navigator.serviceWorker.register('/sw.js');
                    console.log('PWA: Service Worker registered successfully:', registration);

                    // Escuchar nuevas versiones
                    registration.addEventListener('updatefound', () => {
                        console.log('PWA: New service worker version found');
                        const newWorker = registration.installing;

                        if (newWorker) {
                            newWorker.addEventListener('statechange', () => {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    console.log('PWA: New version available');
                                    if (typeof Swal !== 'undefined') {
                                        Swal.fire({
                                            title: 'Actualización Disponible',
                                            text: 'Hay una nueva versión de la aplicación disponible.',
                                            icon: 'info',
                                            showCancelButton: true,
                                            confirmButtonText: 'Actualizar',
                                            cancelButtonText: 'Más tarde',
                                            confirmButtonColor: '#fb7185'
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                window.location.reload();
                                            }
                                        });
                                    }
                                }
                            });
                        }
                    });
                } catch (error) {
                    console.log('PWA: Service Worker registration failed:', error);
                }
            });
        }
    }
}

// Initialize PWA installer when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    console.log('PWA: Initializing PWA installer');
    window.pwaInstaller = new PWAInstaller();
});

// Nota: registro del Service Worker se realiza dentro de la clase (registerServiceWorker)