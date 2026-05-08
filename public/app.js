// Auto-submit búsqueda con debounce
const searchInput = document.querySelector('.search');
if (searchInput) {
    let timer;
    searchInput.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => searchInput.closest('form').submit(), 400);
    });
}

// Auto-ocultar flash messages después de 4 segundos
document.querySelectorAll('.flash').forEach(el => {
    setTimeout(() => {
        el.style.transition = 'opacity 0.5s';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 500);
    }, 4000);
});