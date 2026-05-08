// validBoletaForm: validación mínima antes de enviar el formulario de boleta
function validBoletaForm() {
    try {
        var form = document.querySelector('.needs-validation');
        if (!form) return true; // no hay formulario, permitir enviar

        // obtener campos por name (Symfony suele usar names basados en form name)
        var numero = form.querySelector('[name$="[numeroBoleta]"]');
        var profesional = form.querySelector('[name$="[profesional]"]');
        var email = form.querySelector('[name$="[emailProfesional]"]');

        // limpieza de mensajes previos
        var prev = form.querySelectorAll('.error-bubble');
        prev.forEach(function(el){ el.remove(); });

        var ok = true;
        var ok = true;
        function showError(el, msg) {
            if (!el) return;
            var bubble = document.createElement('div');
            bubble.className = 'error-bubble';
            bubble.innerHTML = '<div class="error-bubble-item">'+msg+'</div>';
            el.parentNode.style.position = 'relative';
            el.parentNode.appendChild(bubble);
            ok = false;
        }

        if (numero && numero.value.trim() === '') {
            showError(numero, 'El número de boleta es obligatorio');
        }

        if (profesional && profesional.value.trim() === '') {
            showError(profesional, 'El profesional es obligatorio');
        }

        if (email && email.value.trim() !== '') {
            var re = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
            if (!re.test(email.value.trim())) {
                showError(email, 'Ingrese un email válido');
            }
        }

        // si falló validación, desplazar un poco la vista
        if (!ok) {
            var first = form.querySelector('.error-bubble');
            if (first) first.scrollIntoView({behavior:'smooth', block: 'center'});
        }

        return ok;
    } catch (e) {
        console.error('validBoletaForm error', e);
        return true;
    }
}
