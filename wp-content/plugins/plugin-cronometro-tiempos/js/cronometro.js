document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('form-cronometro');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const corredorId = document.getElementById('corredor_id').value;
        const tiempo = document.getElementById('tiempo').value;

        fetch(plugin_crono.ajax_url, {
            method: 'POST',
            body: new URLSearchParams({
                action: 'guardar_tiempo',
                corredor_id: corredorId,
                tiempo: tiempo
            })
        })
        .then(res => res.json())
        .then(data => {
            document.getElementById("mensaje-tiempo").innerHTML = "<p style='color: green;'>" + data.data.message + "</p>";
            if (typeof reiniciarCrono === 'function') reiniciarCrono();
            if (typeof actualizarTablas === 'function') actualizarTablas();
            form.reset();
        });
    });
});
