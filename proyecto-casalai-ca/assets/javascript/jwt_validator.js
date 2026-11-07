/**
 * Validador de JWT en tiempo real con sistema de extensión de sesión
 * 
 * Este script verifica periódicamente si el token JWT sigue siendo válido.
 * Si el token expira o es inválido, destruye la sesión y redirige al login.
 * Incluye sistema de extensión de sesión con modal y animación.
 */

(function() {
    'use strict';

    if (typeof window !== 'undefined') {
        window.__jwt_validator_queue = window.__jwt_validator_queue || [];
        window.openJwtExtensionModal = window.openJwtExtensionModal || function(secondsLeft) {
            console.warn('[JWT] openJwtExtensionModal placeholder called before validator init:', secondsLeft);
            window.__jwt_validator_queue.push(secondsLeft);
        };
    }

    if (typeof window !== 'undefined') {
        window.JWTValidator = window.JWTValidator || {};
        window.JWTValidator.openModal = window.JWTValidator.openModal || function(secondsLeft) {
            console.warn('[JWT] placeholder openModal called before validator initialization:', secondsLeft);
        };
        window.openJwtExtensionModal = window.openJwtExtensionModal || function(secondsLeft) {
            console.warn('[JWT] placeholder openJwtExtensionModal called before validator initialization:', secondsLeft);
            if (window.JWTValidator && typeof window.JWTValidator.openModal === 'function') {
                window.JWTValidator.openModal(secondsLeft);
            }
        };
    }
    
    // Configuración
    const CONFIG = {
        verifyInterval: 5000, // Verificar cada 5 segundos
        verifyEndpoint: 'api/verify_token.php',
        extendEndpoint: 'api/extend_token.php',
        invalidateEndpoint: 'api/invalidate_token.php',
        extensionThreshold: 30, // Mostrar modal 30 segundos antes de expirar
        extensionTime: 1830, // Extender sesión por 30 minutos (1830 segundos)
        maxExtensions: 3, // Máximo de extensiones permitidas
        extensionCookieName: 'session_extensions' // Nombre de la cookie para rastrear extensiones
    };
    
    let verifyTimer = null;
    let countdownTimer = null;
    let modalShowTimer = null;
    let isRedirecting = false;
    let isModalShown = false;
    let isVerificationInProgress = false;
    let lastVerifyData = null;
    
    console.log('[JWT] Script jwt_validator.js cargado');
    console.log('[JWT] Configuración:', CONFIG);
    
    /**
     * Obtiene el número de extensiones de sesión usadas
     */
    function getExtensionCount() {
        const cookieValue = document.cookie
            .split('; ')
            .find(row => row.startsWith(CONFIG.extensionCookieName + '='));
        return cookieValue ? parseInt(cookieValue.split('=')[1]) : 0;
    }
    
    /**
     * Incrementa el contador de extensiones de sesión
     */
    function incrementExtensionCount() {
        const currentCount = getExtensionCount();
        const newCount = currentCount + 1;
        const expires = new Date();
        expires.setDate(expires.getDate() + 1); // Expira en 24 horas
        document.cookie = `${CONFIG.extensionCookieName}=${newCount}; expires=${expires.toUTCString()}; path=/`;
        return newCount;
    }
    
    /**
     * Reinicia el contador de extensiones de sesión
     */
    function resetExtensionCount() {
        document.cookie = `${CONFIG.extensionCookieName}=0; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/`;
    }
    
    /**
     * Verifica el token JWT
     */
    function scheduleExtensionModal(secondsLeft, remainingExtensions) {
        if (modalShowTimer) {
            clearTimeout(modalShowTimer);
            modalShowTimer = null;
        }

        const seconds = Number(secondsLeft);
        console.log('[JWT] scheduleExtensionModal:', { secondsLeft: secondsLeft, parsed: seconds, remainingExtensions });

        if (!Number.isFinite(seconds) || seconds <= 0) {
            console.log('[JWT] El tiempo restante no es válido para agendar modal:', secondsLeft);
            return;
        }

        if (seconds <= CONFIG.extensionThreshold) {
            if (!isModalShown) {
                showExtensionModal(seconds, remainingExtensions);
            }
            return;
        }

        const delayMs = (seconds - CONFIG.extensionThreshold) * 1000;
        console.log('[JWT] Agendando apertura de modal en:', delayMs, 'ms');
        modalShowTimer = setTimeout(() => {
            if (!isModalShown) {
                showExtensionModal(CONFIG.extensionThreshold, remainingExtensions);
            }
        }, delayMs);
    }

    function verifyToken() {
        if (isRedirecting || isModalShown || isVerificationInProgress) {
            console.log('[JWT] Verificación omitida:', {
                isRedirecting,
                isModalShown,
                isVerificationInProgress
            });
            return;
        }
        
        isVerificationInProgress = true;
        console.log('[JWT] Verificando token...');
        fetch(CONFIG.verifyEndpoint, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (response.status === 401) {
                invalidateSession();
                return null;
            }
            if (!response.ok) {
                throw new Error(`Error HTTP ${response.status} al verificar el token`);
            }
            return response.json();
        })
        .then(data => {
            if (!data) {
                return;
            }

            console.log('[JWT] Respuesta de verificación:', data);
            lastVerifyData = data;

            const state = data.state;
            const remainingExtensions = data.extensions_remaining ?? CONFIG.maxExtensions;
            const secondsLeft = Number(data.expires_in);
            console.log('[JWT] token state:', state, 'expires_in parsed:', secondsLeft, 'type:', typeof secondsLeft);

            if (!Number.isFinite(secondsLeft)) {
                console.log('[JWT] expires_in no es numérico:', data.expires_in);
                return;
            }

            if (state === 'warning') {
                if (remainingExtensions <= 0) {
                    console.log('[JWT] No hay extensiones disponibles, invalidando sesión');
                    invalidateSession();
                    return;
                }
                scheduleExtensionModal(secondsLeft, remainingExtensions);
                return;
            }

            if (state === 'valid') {
                scheduleExtensionModal(secondsLeft, remainingExtensions);
                return;
            }

            if (['expired', 'invalid', 'missing'].includes(state)) {
                console.log('[JWT] Token expirado o inválido, invalidando sesión');
                invalidateSession();
                return;
            }

            console.error('[JWT] Estado inesperado al verificar el token:', state);
        })
        .catch(error => {
            console.error('[JWT] No se pudo verificar el token; se reintentará:', error);
        })
        .finally(() => {
            isVerificationInProgress = false;
        });
    }
    
    /**
     * Muestra el modal de extensión de sesión
     */
    function showExtensionModal(secondsLeft, remainingExtensions) {
        console.log('[JWT] showExtensionModal llamado con secondsLeft:', secondsLeft, 'remainingExtensions:', remainingExtensions);
        isModalShown = true;
        
        if (remainingExtensions <= 0) {
            console.log('[JWT] No hay extensiones disponibles, invalidando sesión');
            invalidateSession();
            return;
        }
        
        console.log('[JWT] Creando modal HTML...');
        
        try {
            const existingModal = document.getElementById('session-extension-modal');
            if (existingModal) {
                existingModal.remove();
            }

            const modal = document.createElement('div');
            modal.id = 'session-extension-modal';
            modal.style.cssText = `
                position: fixed;
                inset: 0;
                z-index: 2147483647;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                display: flex;
                align-items: center;
                justify-content: center;
                background: rgba(0, 0, 0, 0.62);
                padding: 20px;
                box-sizing: border-box;
            `;

            modal.innerHTML = `
                <div style="background: #ffffff; border-radius: 16px; padding: 32px 28px; max-width: 450px; width: 100%; text-align: center; box-shadow: 0 16px 50px rgba(0,0,0,0.28); animation: jwtModalSlideIn 0.25s ease-out; box-sizing: border-box;">
                    <div style="margin-bottom: 20px;">
                        <svg width="120" height="120" viewBox="0 0 100 100" style="display:block; margin:0 auto;">
                            <circle cx="50" cy="50" r="45" fill="none" stroke="#e0e0e0" stroke-width="8" />
                            <circle class="countdown-progress" cx="50" cy="50" r="45" fill="none" stroke="#ff6b6b" stroke-width="8" stroke-dasharray="283" stroke-dashoffset="0" stroke-linecap="round" transform="rotate(-90 50 50)" />
                            <text class="countdown-text" x="50" y="55" text-anchor="middle" font-size="24" font-weight="bold" fill="#333">${secondsLeft}</text>
                        </svg>
                    </div>
                    <h2 style="margin: 0 0 12px; color: #333; font-size: 24px; font-weight: 600;">Tu sesión está por expirar</h2>
                    <p style="margin: 0 0 12px; color: #666; font-size: 16px; line-height: 1.5;">¿Deseas extender tu sesión por 30 minutos adicionales?</p>
                    <p style="margin: 0 0 24px; color: #888; font-size: 14px;">Extensiones disponibles: <strong>${remainingExtensions}</strong> de ${CONFIG.maxExtensions}</p>
                    <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                        <button id="btn-extend-session" style="padding: 12px 24px; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; background: #4CAF50; color: white;">Extender Sesión</button>
                        <button id="btn-logout-session" style="padding: 12px 24px; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; background: #f44336; color: white;">Cerrar Sesión</button>
                    </div>
                </div>
            `;

            const style = document.createElement('style');
            style.setAttribute('data-modal-extension', 'true');
            style.textContent = `
                @keyframes jwtModalSlideIn {
                    from { transform: translateY(-20px); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
            `;

            document.head.appendChild(style);
            document.body.appendChild(modal);
            
            console.log('[JWT] Modal agregado al DOM');
            
            startCountdownAnimation(secondsLeft, modal);
            
            const btnExtend = modal.querySelector('#btn-extend-session');
            const btnLogout = modal.querySelector('#btn-logout-session');
            console.log('[JWT] btnExtend encontrado:', !!btnExtend, 'btnLogout encontrado:', !!btnLogout);
            if (btnExtend) {
                btnExtend.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    extendSession();
                });
            }
            if (btnLogout) {
                btnLogout.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    invalidateSession();
                });
            }
        } catch (error) {
            console.error('[JWT] Error creando modal de extensión:', error);
            isModalShown = false;
            invalidateSession();
        }
    }
    
    /**
     * Inicia la animación de cuenta regresiva
     */
    function startCountdownAnimation(secondsLeft, modalElement) {
        const progressCircle = modalElement ? modalElement.querySelector('.countdown-progress') : null;
        const countdownText = modalElement ? modalElement.querySelector('.countdown-text') : null;
        console.log('[JWT] Iniciando animación de cuenta regresiva:', { progressCircle, countdownText, modalElement: !!modalElement });
        if (!progressCircle || !countdownText) {
            console.warn('[JWT] No se encontró el círculo o el texto del contador dentro del modal.');
            return;
        }

        const circumference = 2 * Math.PI * 45; // 283
        
        let remaining = secondsLeft;
        
        countdownTimer = setInterval(() => {
            remaining--;
            
            if (remaining <= 0) {
                clearInterval(countdownTimer);
                hideExtensionModal();
                invalidateSession();
                return;
            }
            
            // Actualizar texto
            countdownText.textContent = remaining;
            
            // Actualizar círculo de progreso
            const offset = circumference * (1 - remaining / secondsLeft);
            progressCircle.style.strokeDashoffset = offset;
            
            // Cambiar color cuando queda poco tiempo
            if (remaining <= 5) {
                progressCircle.style.stroke = '#f44336';
            } else if (remaining <= 10) {
                progressCircle.style.stroke = '#ff9800';
            }
        }, 1000);
    }
    
    /**
     * Oculta el modal de extensión de sesión
     */
    function hideExtensionModal() {
        const modal = document.getElementById('session-extension-modal');
        if (modal) {
            modal.remove();
        }
        
        const styles = document.querySelector('style[data-modal-extension]');
        if (styles) {
            styles.remove();
        }
        
        if (countdownTimer) {
            clearInterval(countdownTimer);
            countdownTimer = null;
        }
        
        if (typeof modalShowTimer !== 'undefined' && modalShowTimer) {
            clearTimeout(modalShowTimer);
            modalShowTimer = null;
        }
        
        isModalShown = false;
    }
    
    /**
     * Extiende la sesión del usuario
     */
    function extendSession() {
        const extensionCount = getExtensionCount();
        
        // Deshabilitar botón para evitar múltiples clics
        const btnExtend = document.getElementById('btn-extend-session');
        if (btnExtend) {
            btnExtend.disabled = true;
            btnExtend.textContent = 'Extendiendo...';
        }
        
        fetch(CONFIG.extendEndpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error al extender sesión');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Incrementar contador de extensiones
                incrementExtensionCount();
                
                // Ocultar modal
                hideExtensionModal();
                
                // Mostrar mensaje de éxito
                showSuccessMessage('Sesión extendida exitosamente por 30 minutos');
                
                // Reiniciar verificación
                console.log('Sesión extendida, continuando verificación');
            } else {
                throw new Error(data.message || 'Error al extender sesión');
            }
        })
        .catch(error => {
            console.error('Error al extender sesión:', error);
            hideExtensionModal();
            showSessionModal('No se pudo extender la sesión', 'La sesión no pudo extenderse. Serás redirigido al login.');
            invalidateSession();
        });
    }
    
    /**
     * Invalida la sesión y cierra el token en el servidor
     */
    function invalidateSession() {
        if (isRedirecting) {
            return;
        }
        isRedirecting = true;
        
        if (verifyTimer) {
            clearInterval(verifyTimer);
            verifyTimer = null;
        }
        hideExtensionModal();
        resetExtensionCount();
        
        fetch(CONFIG.invalidateEndpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            console.log('[JWT] Sesión invalidada:', data);
            showSessionModal('Sesión expirada', 'Tu sesión ha expirado o fue cerrada. Serás redirigido al login.');
        })
        .catch(error => {
            console.error('[JWT] Error al invalidar sesión:', error);
            showSessionModal('Sesión cerrada', 'No se pudo completar la validación de sesión. Serás redirigido al login.');
        });
    }
    
    /**
     * Muestra un modal de estado para expiración o error de sesión
     */
    function showSessionModal(title, message, redirectAfterMs = 1200) {
        const modal = document.createElement('div');
        modal.id = 'session-status-modal';
        modal.style.cssText = `
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.55);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10002;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        `;
        modal.innerHTML = `
            <div style="background:white; border-radius:14px; padding:28px 24px; width:min(90%, 420px); box-shadow:0 12px 40px rgba(0,0,0,0.22); text-align:center;">
                <h3 style="margin:0 0 12px; color:#333;">${title}</h3>
                <p style="margin:0 0 20px; color:#666; line-height:1.5;">${message}</p>
                <button id="session-status-confirm" style="border:none; background:#007bff; color:white; padding:10px 18px; border-radius:8px; cursor:pointer; font-weight:600;">Aceptar</button>
            </div>
        `;

        document.body.appendChild(modal);

        const confirmBtn = document.getElementById('session-status-confirm');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', () => {
                modal.remove();
                window.location.href = '?pagina=login';
            });
        }

        if (redirectAfterMs > 0) {
            setTimeout(() => {
                modal.remove();
                window.location.href = '?pagina=login';
            }, redirectAfterMs);
        }
    }

    /**
     * Muestra un mensaje de éxito temporal
     */
    function showSuccessMessage(message) {
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #4CAF50;
            color: white;
            padding: 16px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            z-index: 10001;
            animation: slideInRight 0.3s ease-out;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
        `;
        toast.textContent = message;
        
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from { 
                    transform: translateX(100%);
                    opacity: 0;
                }
                to { 
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;
        
        document.head.appendChild(style);
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideInRight 0.3s ease-out reverse';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    /**
     * Inicia la verificación periódica
     */
    function startVerification() {
        // Verificar inmediatamente al cargar
        verifyToken();
        
        // Configurar verificación periódica
        verifyTimer = setInterval(verifyToken, CONFIG.verifyInterval);
        
        console.log('Validación JWT en tiempo real iniciada (intervalo:', CONFIG.verifyInterval / 1000, 'segundos)');
    }
    
    /**
     * Detiene la verificación periódica
     */
    function stopVerification() {
        if (verifyTimer) {
            clearInterval(verifyTimer);
            verifyTimer = null;
            console.log('Validación JWT en tiempo real detenida');
        }
    }
    
    // Iniciar verificación cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startVerification);
    } else {
        startVerification();
    }
    
    // Exponer funciones globalmente si es necesario
    window.JWTValidator = window.JWTValidator || {};
    window.JWTValidator.start = window.JWTValidator.start || startVerification;
    window.JWTValidator.stop = window.JWTValidator.stop || stopVerification;
    window.JWTValidator.verify = window.JWTValidator.verify || verifyToken;
    window.JWTValidator.openModal = function(secondsLeft) {
        console.log('[JWT] openModal invoked manually with secondsLeft:', secondsLeft);
        if (!isModalShown) {
            showExtensionModal(secondsLeft, CONFIG.maxExtensions);
        } else {
            console.log('[JWT] Modal ya está visible, no se abrirá de nuevo.');
        }
    };
    console.log('[JWT] JWTValidator global expuesto:', window.JWTValidator);

    window.openJwtExtensionModal = function(secondsLeft) {
        console.log('[JWT] openJwtExtensionModal invoked manually with secondsLeft:', secondsLeft);
        if (window.JWTValidator && typeof window.JWTValidator.openModal === 'function') {
            window.JWTValidator.openModal(secondsLeft);
        } else {
            console.warn('[JWT] JWTValidator.openModal no está disponible todavía, encolando llamada:', secondsLeft);
            window.__jwt_validator_queue = window.__jwt_validator_queue || [];
            window.__jwt_validator_queue.push(secondsLeft);
        }
    };

    if (window.__jwt_validator_queue && window.__jwt_validator_queue.length > 0) {
        console.log('[JWT] Procesando cola de llamadas pendientes a openJwtExtensionModal:', window.__jwt_validator_queue);
        window.__jwt_validator_queue.forEach(function(secondsLeft) {
            if (window.JWTValidator && typeof window.JWTValidator.openModal === 'function') {
                window.JWTValidator.openModal(secondsLeft);
            }
        });
        window.__jwt_validator_queue = [];
    }
})();
