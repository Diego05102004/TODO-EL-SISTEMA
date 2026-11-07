// Efecto de scroll para la barra
window.addEventListener('scroll', function () {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 10) {
        navbar.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
    } else {
        navbar.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
    }
});

// Mostrar/ocultar notificaciones (debe haber un contenedor con ID contenedor-notificacion en tu HTML)
function toggleNotification() {
    const notificacion = document.getElementById('contenedor-notificacion');
    if (!notificacion) return;

    if (notificacion.style.height === '0px' || notificacion.style.height === '') {
        notificacion.style.height = 'auto';
        notificacion.style.opacity = '1';
        notificacion.style.padding = '15px';
    } else {
        notificacion.style.height = '0px';
        notificacion.style.opacity = '0';
        notificacion.style.padding = '0';
    }
}