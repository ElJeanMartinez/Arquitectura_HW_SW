<?php
/**
 * Plugin Name: Cronómetro de Tiempos
 * Description: Cronómetro interactivo para registrar tiempos de corredores (3 intentos por participante).
 * Version: 1.3
 * Author: Tu Nombre
 */

register_activation_hook(__FILE__, 'crear_tablas_cronometro_v1_3');

function crear_tablas_cronometro_v1_3() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Tabla 1: Tiempos agregados (ranking)
    $sql1 = "CREATE TABLE {$wpdb->prefix}tiempos (
        id INT(11) NOT NULL AUTO_INCREMENT,
        corredor_id INT(11) NOT NULL,
        tiempo FLOAT NOT NULL,
        fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // Tabla 2: Detalle de los 3 intentos
    $sql2 = "CREATE TABLE {$wpdb->prefix}tiempos_detalle (
        id INT(11) NOT NULL AUTO_INCREMENT,
        corredor_id INT(11) NOT NULL,
        intento INT(1) NOT NULL,
        tiempo FLOAT NOT NULL,
        fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql1);
    dbDelta($sql2);
}

// Shortcode del cronómetro
add_shortcode('registro_tiempos', 'formulario_cronometro_v1_3');

function formulario_cronometro_v1_3() {
    global $wpdb;
    // Asegúrate de que la tabla 'corredores' exista y tenga datos.
    // Esta tabla es creada por tu segundo plugin "Registro de Corredores".
    $corredores = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}corredores");

    ob_start(); ?>
    <style>
        #form-cronometro { max-width: 500px; margin: 0 auto; padding: 20px; background-color: #f9f9f9; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        #form-cronometro label { font-size: 16px; font-weight: bold; margin-bottom: 5px; display: block; }
        #form-cronometro select, #form-cronometro button { padding: 10px; width: 100%; margin-bottom: 15px; font-size: 14px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        #cronometro { font-size: 2em; color: #333; margin-bottom: 15px; text-align: center; }
        #mensaje-tiempo { margin-top: 20px; text-align: center; font-weight: bold; }
        button[type="submit"] { background-color: #4CAF50; color: white; font-weight: bold; border: none; }
        button[type="submit"]:hover { background-color: #45a049; }
        .button-container { display: flex; justify-content: space-between; margin-bottom:15px;}
        .button-container button { width: 30%; padding: 10px; border-radius: 5px; border: 1px solid #ccc; background-color: #f0f0f0; }
        .button-container button:hover { background-color: #ddd; }
    </style>

    <form id="form-cronometro">
        <label for="select-corredores">Selecciona un corredor:</label>
        <select name="corredor_id" id="select-corredores" required>
            <option value="">-- Selecciona --</option>
            <?php if ($corredores): ?>
                <?php foreach ($corredores as $c): ?>
                    <option value="<?= esc_attr($c->id) ?>"><?= esc_html($c->nombre_equipo) ?> (<?= esc_html($c->lider) ?>)</option>
                <?php endforeach; ?>
            <?php else: ?>
                <option value="" disabled>No hay corredores registrados</option>
            <?php endif; ?>
        </select>

        <label for="intento">Número de intento (1, 2 o 3):</label>
        <select name="intento" id="intento" required>
            <option value="">-- Selecciona intento --</option>
            <option value="1">Intento 1</option>
            <option value="2">Intento 2</option>
            <option value="3">Intento 3</option>
        </select>

        <div>
            <h3 id="cronometro">0.00</h3>
            <input type="hidden" name="tiempo" id="campo-tiempo">
            <div class="button-container">
                <button type="button" onclick="iniciarCrono()">Iniciar</button>
                <button type="button" onclick="detenerCrono()">Detener</button>
                <button type="button" onclick="reiniciarCrono()">Reiniciar</button>
            </div>
        </div>

        <button type="submit">Registrar Tiempo</button>
    </form>

    <div id="mensaje-tiempo"></div>

    <script>
    let tiempoCrono = 0.00; // Renombrado para evitar conflicto con la variable 'tiempo' global si existe
    let intervaloCrono = null; // Renombrado

    function actualizarDisplayCrono() {
        tiempoCrono += 0.01;
        document.getElementById("cronometro").innerText = tiempoCrono.toFixed(2);
    }

    function iniciarCrono() {
        if (!intervaloCrono) {
            intervaloCrono = setInterval(actualizarDisplayCrono, 10);
        }
    }

    function detenerCrono() {
        clearInterval(intervaloCrono);
        intervaloCrono = null;
        document.getElementById("campo-tiempo").value = tiempoCrono.toFixed(2);
    }

    function reiniciarCrono() {
        clearInterval(intervaloCrono);
        intervaloCrono = null;
        tiempoCrono = 0.00;
        document.getElementById("cronometro").innerText = "0.00";
        document.getElementById("campo-tiempo").value = "";
        document.getElementById('form-cronometro').reset(); // Resetea los selects también
    }

    document.getElementById("form-cronometro").addEventListener("submit", function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append("action", "guardar_tiempo_ajax_v1_3"); // Acción AJAX específica

        // Validar que el tiempo no sea cero o vacío
        const tiempoRegistrado = formData.get('tiempo');
        if (!tiempoRegistrado || parseFloat(tiempoRegistrado) <= 0) {
            document.getElementById("mensaje-tiempo").innerHTML = "<p style='color: red;'>El tiempo debe ser mayor que cero. Por favor, inicia y detén el cronómetro.</p>";
            return;
        }


        fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById("mensaje-tiempo").innerHTML = "<p style='color: green;'>" + data.data.message + "</p>";
                reiniciarCrono(); // Reinicia el cronómetro y el formulario
                actualizarTablasViaAjax(); // Actualiza las tablas
            } else {
                document.getElementById("mensaje-tiempo").innerHTML = "<p style='color: red;'>" + (data.data.message || 'Error al guardar el tiempo.') + "</p>";
            }
        })
        .catch(error => {
            console.error('Error en fetch guardar_tiempo_ajax:', error);
            document.getElementById("mensaje-tiempo").innerHTML = "<p style='color: red;'>Error de conexión al guardar el tiempo.</p>";
        });
    });

    function actualizarTablasViaAjax() {
        // Actualizar tabla de intentos
        fetch("<?php echo admin_url('admin-ajax.php'); ?>?action=obtener_tiempos_detalle_ajax_v1_3")
            .then(response => response.text())
            .then(html => {
                const contenedorIntentos = document.getElementById("contenedor-tiempos-detalle");
                if (contenedorIntentos) {
                    contenedorIntentos.innerHTML = html;
                } else {
                    // Si el shortcode [ver_intentos_ajax] no está en la página, esto es esperado.
                    // console.warn("Contenedor #contenedor-tiempos-detalle no encontrado. Asegúrate de usar el shortcode [ver_intentos_ajax].");
                }
            })
            .catch(error => console.error('Error fetching tiempos detalle:', error));

        // Actualizar tabla de ranking (del plugin de cronómetro)
        fetch("<?php echo admin_url('admin-ajax.php'); ?>?action=obtener_ranking_cronometro_ajax_v1_3")
            .then(response => response.text())
            .then(html => {
                const contenedorRanking = document.getElementById("contenedor-ranking-cronometro");
                if (contenedorRanking) {
                    contenedorRanking.innerHTML = html;
                } else {
                     // Si el shortcode [ranking_tiempos_cronometro_ajax] no está en la página, esto es esperado.
                    // console.warn("Contenedor #contenedor-ranking-cronometro no encontrado. Asegúrate de usar el shortcode [ranking_tiempos_cronometro_ajax].");
                }
            })
            .catch(error => console.error('Error fetching ranking tiempos del cronómetro:', error));
    }
    // Opcional: Cargar tablas al inicio si los shortcodes están en la misma página
    // document.addEventListener('DOMContentLoaded', actualizarTablasViaAjax);
    </script>

    <?php
    return ob_get_clean();
}

// Shortcode para mostrar detalles de los tiempos (actualizable por AJAX)
add_shortcode('ver_intentos_ajax', 'mostrar_tiempos_detalle_ajax_v1_3');

function mostrar_tiempos_detalle_ajax_v1_3_content() { // Renombrada para evitar conflicto
    global $wpdb;
    $resultados = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT d.*, c.nombre_equipo, c.lider 
             FROM {$wpdb->prefix}tiempos_detalle d 
             JOIN {$wpdb->prefix}corredores c ON d.corredor_id = c.id 
             ORDER BY d.corredor_id, d.intento ASC"
        )
    );

    $output = ''; // Iniciar output
    if ($resultados) {
        $output .= "<table border='1' cellpadding='5' style='width:100%; margin-top:20px;'>
        <thead><tr><th>Equipo</th><th>Líder</th><th>Intento</th><th>Tiempo (s)</th><th>Fecha</th></tr></thead><tbody>";
        foreach ($resultados as $r) {
            $output .= "<tr>
                <td>" . esc_html($r->nombre_equipo) . "</td>
                <td>" . esc_html($r->lider) . "</td>
                <td>" . esc_html($r->intento) . "</td>
                <td>" . esc_html(number_format((float)$r->tiempo, 2, '.', '')) . "</td>
                <td>" . esc_html($r->fecha) . "</td>
            </tr>";
        }
        $output .= "</tbody></table>";
    } else {
        $output .= "<p>No hay tiempos de intentos registrados aún.</p>";
    }
    return $output;
}

function mostrar_tiempos_detalle_ajax_v1_3() {
    // El div contenedor ahora tiene un ID para que AJAX pueda reemplazar su contenido
    return '<div id="contenedor-tiempos-detalle">' . mostrar_tiempos_detalle_ajax_v1_3_content() . '</div>';
}


// Acción AJAX para obtener los detalles de los tiempos
add_action('wp_ajax_obtener_tiempos_detalle_ajax_v1_3', 'ajax_handler_obtener_tiempos_detalle_v1_3');
add_action('wp_ajax_nopriv_obtener_tiempos_detalle_ajax_v1_3', 'ajax_handler_obtener_tiempos_detalle_v1_3');

function ajax_handler_obtener_tiempos_detalle_v1_3() {
    echo mostrar_tiempos_detalle_ajax_v1_3_content(); // Devuelve solo el contenido interno
    wp_die();
}

// Acción AJAX para guardar tiempo
add_action('wp_ajax_guardar_tiempo_ajax_v1_3', 'guardar_tiempo_ajax_handler_v1_3');
add_action('wp_ajax_nopriv_guardar_tiempo_ajax_v1_3', 'guardar_tiempo_ajax_handler_v1_3'); // Si usuarios no logueados pueden registrar

function guardar_tiempo_ajax_handler_v1_3() {
    global $wpdb;

    // Validar y sanitizar datos
    if (!isset($_POST['corredor_id'], $_POST['tiempo'], $_POST['intento'])) {
        wp_send_json_error(['message' => 'Faltan datos.']);
        return;
    }

    $corredor_id = intval($_POST['corredor_id']);
    $tiempo = floatval($_POST['tiempo']);
    $intento = intval($_POST['intento']);

    if ($corredor_id <= 0 || $tiempo <= 0 || !in_array($intento, [1, 2, 3])) {
        wp_send_json_error(['message' => 'Datos inválidos.']);
        return;
    }

    // Insertar en tabla tiempos (ranking)
    $insert_tiempo = $wpdb->insert($wpdb->prefix . 'tiempos', [
        'corredor_id' => $corredor_id,
        'tiempo' => $tiempo,
        'fecha' => current_time('mysql')
    ]);

    // Insertar en tabla tiempos_detalle (intentos)
    $insert_detalle = $wpdb->insert($wpdb->prefix . 'tiempos_detalle', [
        'corredor_id' => $corredor_id,
        'intento' => $intento,
        'tiempo' => $tiempo,
        'fecha' => current_time('mysql')
    ]);

    if ($insert_tiempo && $insert_detalle) {
        wp_send_json_success(['message' => 'Tiempo registrado exitosamente.']);
    } else {
        wp_send_json_error(['message' => 'Error al guardar en la base de datos.']);
    }
}

// Shortcode para mostrar ranking de tiempos (del plugin de cronómetro, actualizable por AJAX)
add_shortcode('ranking_tiempos_cronometro_ajax', 'mostrar_ranking_tiempos_cronometro_ajax_v1_3');

function mostrar_ranking_tiempos_cronometro_ajax_v1_3_content() { // Renombrada para evitar conflicto
    global $wpdb;
    $ranking = $wpdb->get_results(
        $wpdb->prepare(
           "SELECT c.nombre_equipo, c.lider, MIN(t.tiempo) AS mejor_tiempo 
            FROM {$wpdb->prefix}tiempos t 
            JOIN {$wpdb->prefix}corredores c ON t.corredor_id = c.id 
            GROUP BY t.corredor_id 
            ORDER BY mejor_tiempo ASC"
        )
    );

    $output = ''; // Iniciar output
    if ($ranking) {
        $output .= "<table border='1' cellpadding='5' style='width:100%; margin-top:20px;'>
        <thead><tr><th>Equipo</th><th>Líder</th><th>Mejor Tiempo (s)</th></tr></thead><tbody>";
        foreach ($ranking as $r) {
            $output .= "<tr>
                <td>" . esc_html($r->nombre_equipo) . "</td>
                <td>" . esc_html($r->lider) . "</td>
                <td>" . esc_html(number_format((float)$r->mejor_tiempo, 2, '.', '')) . "</td>
            </tr>";
        }
        $output .= "</tbody></table>";
    } else {
        $output .= "<p>No hay tiempos en el ranking aún.</p>";
    }
    return $output;
}

function mostrar_ranking_tiempos_cronometro_ajax_v1_3() {
    return '<div id="contenedor-ranking-cronometro">' . mostrar_ranking_tiempos_cronometro_ajax_v1_3_content() . '</div>';
}

// Acción AJAX para obtener el ranking (del plugin de cronómetro)
add_action('wp_ajax_obtener_ranking_cronometro_ajax_v1_3', 'ajax_handler_obtener_ranking_cronometro_v1_3');
add_action('wp_ajax_nopriv_obtener_ranking_cronometro_ajax_v1_3', 'ajax_handler_obtener_ranking_cronometro_v1_3');

function ajax_handler_obtener_ranking_cronometro_v1_3() {
    echo mostrar_ranking_tiempos_cronometro_ajax_v1_3_content(); // Devuelve solo el contenido interno
    wp_die();
}
?>