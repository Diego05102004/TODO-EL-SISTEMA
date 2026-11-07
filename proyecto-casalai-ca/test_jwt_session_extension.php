<?php
/**
 * Prueba de sesión JWT con extensión y modal de expiración.
 *
 * Este script genera un token de prueba corto, carga un cliente web que
 * verifica periódicamente el token y permite extender la sesión hasta 3 veces.
 */

require_once 'vendor/autoload.php';

use Usuario\ProyectoCasalaiCa\Config\Auth;

// Establecer el tiempo de expiración corto para la prueba
$token = Auth::generateToken(1, 'Administrador', 60); // Token válido por 60 segundos
Auth::setTokenCookie($token, 60); // 1 minuto de duración para prueba rápida

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba JWT - Extensión de Sesión</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            width: 100%;
            max-width: 720px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.12);
            padding: 32px;
            margin: 20px;
        }
        h1 {
            margin-top: 0;
            color: #333;
        }
        .note {
            background: #e7f3ff;
            border-left: 6px solid #007bff;
            color: #034f84;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }
        .token-box {
            background: #f7f7f7;
            border: 1px solid #ddd;
            padding: 16px;
            border-radius: 8px;
            overflow-wrap: anywhere;
            margin-bottom: 20px;
        }
        .status {
            margin-top: 20px;
            padding: 16px;
            border-radius: 8px;
            background: #fff8e1;
            border: 1px solid #ffe08a;
            color: #805700;
        }
        .timer-box {
            margin-top: 16px;
            padding: 16px;
            border-radius: 10px;
            background: #eef6ff;
            border: 1px solid #c8ddff;
        }
        .timer-label {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            margin-bottom: 12px;
            color: #0b3c70;
        }
        .timer-progress {
            width: 100%;
            height: 18px;
            border-radius: 10px;
            background: #d6e9ff;
            overflow: hidden;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.08);
        }
        .timer-progress-bar {
            width: 100%;
            height: 100%;
            border-radius: 10px;
            background: linear-gradient(90deg, #28a745, #ffc107);
            transition: width 0.8s ease, background 0.4s ease;
        }
        .btn {
            display: inline-block;
            margin-right: 10px;
            padding: 12px 22px;
            border-radius: 8px;
            text-decoration: none;
            color: white;
            background: #007bff;
            transition: background 0.2s ease;
        }
        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Prueba de Extensión de Sesión JWT</h1>
        <div class="note">
            Este test utiliza un token JWT de prueba con expiración rápida (60 segundos).
            Cuando falten 30 segundos mostrará un modal para extender la sesión 5 minutos más.
            Sólo se permiten 3 extensiones.
            También puedes abrir el modal manualmente con el botón de prueba.
        </div>
        <div class="token-box">
            <strong>Token JWT de prueba:</strong>
            <pre><?php echo htmlspecialchars($token); ?></pre>
        </div>
        <div class="status" id="statusBox">
            Estado: cargando verificación...
        </div>
        <div class="timer-box">
            <div class="timer-label">
                <span>Tiempo restante de sesión</span>
                <span id="timeRemaining">00:00</span>
            </div>
            <div class="timer-progress">
                <div class="timer-progress-bar" id="timerProgressBar"></div>
            </div>
        </div>
        <div style="margin-top: 24px;">
            <a href="?pagina=login" class="btn">Ir al Login</a>
            <a href="test_jwt_session_extension.php" class="btn">Regenerar prueba</a>
            <button id="btnOpenModal" class="btn" style="background:#28a745;">Abrir modal manual</button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/javascript/jwt_validator.js"></script>
    <script>
        const statusBox = document.getElementById('statusBox');
        const timerLabel = document.getElementById('timeRemaining');
        const timerProgressBar = document.getElementById('timerProgressBar');
        const verifyInterval = 1000;
        const verifyEndpoint = 'api/verify_token.php';
        let pollTimer = null;
        let currentMaxDuration = 60;
        let lastRemainingSeconds = 0;

        function updateStatus(text, color = '#805700') {
            statusBox.textContent = text;
            statusBox.style.color = color;
        }

        function formatTime(totalSeconds) {
            const seconds = Math.max(0, Math.floor(totalSeconds));
            const minutes = Math.floor(seconds / 60);
            const remainder = seconds % 60;
            return `${String(minutes).padStart(2, '0')}:${String(remainder).padStart(2, '0')}`;
        }

        function updateTimer(secondsRemaining) {
            const normalized = Math.max(0, secondsRemaining);
            if (normalized > currentMaxDuration) {
                currentMaxDuration = normalized;
            }

            timerLabel.textContent = formatTime(normalized);
            const percentage = currentMaxDuration > 0 ? (normalized / currentMaxDuration) * 100 : 0;
            timerProgressBar.style.width = `${Math.min(100, Math.max(0, percentage))}%`;

            if (normalized <= 10) {
                timerProgressBar.style.background = 'linear-gradient(90deg, #dc3545, #ffc107)';
            } else if (normalized <= 30) {
                timerProgressBar.style.background = 'linear-gradient(90deg, #ffc107, #fd7e14)';
            } else {
                timerProgressBar.style.background = 'linear-gradient(90deg, #28a745, #ffc107)';
            }
        }

        function verify() {
            fetch(verifyEndpoint, {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.valid) {
                    updateStatus(`Token válido. expira en ${data.expires_in} segundos.`, '#155724');
                    updateTimer(data.expires_in);
                } else {
                    updateStatus('Token inválido o expirado. Serás redirigido.', '#721c24');
                    updateTimer(0);
                    clearInterval(pollTimer);
                    setTimeout(() => window.location.href = '?pagina=login', 3000);
                }
            })
            .catch(() => {
                updateStatus('Error al verificar token. Redirigiendo...', '#721c24');
                updateTimer(0);
                clearInterval(pollTimer);
                setTimeout(() => window.location.href = '?pagina=login', 3000);
            });
        }

        pollTimer = setInterval(verify, verifyInterval);
        verify();

        console.log('[JWT TEST] JWTValidator global:', window.JWTValidator);
        console.log('[JWT TEST] openJwtExtensionModal:', typeof window.openJwtExtensionModal);

        function openJwtExtensionTestModal() {
            let modalTimerSeconds = lastRemainingSeconds || currentMaxDuration;
            let modalTimerInterval = null;

            function updateModalTimer(seconds) {
                const label = document.getElementById('swalTimerLabel');
                const progressBar = document.getElementById('swalTimerProgressBar');
                if (!label || !progressBar) {
                    return;
                }
                label.textContent = formatTime(seconds);
                const percentage = currentMaxDuration > 0 ? (seconds / currentMaxDuration) * 100 : 0;
                progressBar.style.width = `${Math.max(0, Math.min(100, percentage))}%`;

                if (seconds <= 10) {
                    progressBar.style.background = 'linear-gradient(90deg, #dc3545, #ffc107)';
                } else if (seconds <= 30) {
                    progressBar.style.background = 'linear-gradient(90deg, #ffc107, #fd7e14)';
                } else {
                    progressBar.style.background = 'linear-gradient(90deg, #28a745, #ffc107)';
                }
            }

            Swal.fire({
                title: '¿Extender la sesión JWT?',
                html:
                    '<div style="text-align:left; margin:0 auto; max-width:330px;">' +
                    '<p>El token actual expirará pronto. Puedes extender la sesión antes de que termine.</p>' +
                    '<div class="timer-box" style="margin-top:12px; padding:12px; box-sizing:border-box;">' +
                    '<div class="timer-label" style="margin-bottom:10px; color:#0b3c70;">' +
                    '<span>Tiempo restante</span><span id="swalTimerLabel">' + formatTime(modalTimerSeconds) + '</span>' +
                    '</div>' +
                    '<div class="timer-progress" style="height:14px;">' +
                    '<div class="timer-progress-bar" id="swalTimerProgressBar" style="width:100%;"></div>' +
                    '</div>' +
                    '</div>' +
                    '</div>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Extender sesión',
                cancelButtonText: 'Cancelar',
                showLoaderOnConfirm: true,
                didOpen: () => {
                    updateModalTimer(modalTimerSeconds);
                    modalTimerInterval = setInterval(() => {
                        modalTimerSeconds = Math.max(0, modalTimerSeconds - 1);
                        updateModalTimer(modalTimerSeconds);
                        if (modalTimerSeconds <= 0 && modalTimerInterval) {
                            clearInterval(modalTimerInterval);
                        }
                    }, 1000);
                },
                willClose: () => {
                    if (modalTimerInterval) {
                        clearInterval(modalTimerInterval);
                    }
                },
                preConfirm: () => {
                    return fetch('api/extend_token.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .catch(error => ({ success: false, message: error.message }));
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    const data = result.value;
                    if (data.success) {
                        console.log('Sesión extendida:', data);
                        updateStatus(`Sesión extendida. Nuevo tiempo restante: ${data.expires_in} segundos.`, '#155724');
                        updateTimer(data.expires_in);
                        Swal.fire('¡Extendido!', 'La sesión JWT se ha extendido correctamente.', 'success');
                    } else {
                        console.error('Error extendiendo sesión:', data);
                        updateStatus(`No se pudo extender la sesión: ${data.message}`, '#721c24');
                        updateTimer(0);
                        Swal.fire('Error', data.message || 'No se pudo extender la sesión.', 'error');
                    }
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    console.log('Extensión cancelada por el usuario');
                    updateStatus('Cancelaste la extensión de sesión.', '#856404');
                }
            });
        }

        const openModalBtn = document.getElementById('btnOpenModal');
        if (openModalBtn) {
            openModalBtn.addEventListener('click', function() {
                openJwtExtensionTestModal();
            });
        }
    </script>
</body>
</html>
