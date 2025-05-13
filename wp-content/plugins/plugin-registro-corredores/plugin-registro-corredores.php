<?php
/**
 * Plugin Name: Registro de Corredores
 * Description: Permite registrar corredores.
 * Version: 1.2
 * Author: Tu Nombre
 */

register_activation_hook(__FILE__, 'crear_tabla_corredores_v1_2');

function crear_tabla_corredores_v1_2() {
    global $wpdb;
    $tabla = $wpdb->prefix . 'corredores';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $tabla (
        id INT(11) NOT NULL AUTO_INCREMENT,
        nombre_equipo VARCHAR(255) NOT NULL,
        lider VARCHAR(255) NOT NULL,
        semestre VARCHAR(50) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Shortcode para formulario de registro
add_shortcode('registro_corredores_ajax', 'mostrar_formulario_registro_ajax_v1_2');

function mostrar_formulario_registro_ajax_v1_2() {
    ob_start(); ?>
    <style>
        #form-registro-corredor input[type="text"] { width: 100%; padding: 10px; margin-bottom: 10px; font-size: 16px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 5px;}
        #form-registro-corredor input[type="submit"] { font-size: 18px; padding: 15px; background-color: #4CAF50; color: white; border: none; cursor: pointer; width: 100%; border-radius: 5px; }
        #form-registro-corredor input[type="submit"]:hover { background-color: #45a049; }
        #mensaje-corredor { margin-top: 15px; font-weight: bold; }
    </style>
    <form id="form-registro-corredor">
        <input type="text" name="nombre_equipo" placeholder="Nombre del equipo" required><br>
        <input type="text" name="lider" placeholder="Líder del equipo" required><br>
        <input type="text" name="semestre" placeholder="Semestre" required><br>
        <input type="submit" value="Registrar Corredor">
    </form>
    <div id="mensaje-corredor"></div>

    <script>
    document.getElementById("form-registro-corredor").addEventListener("submit", function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append("action", "registrar_corredor_ajax_v1_2"); // Acción AJAX específica

        fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById("mensaje-corredor").innerHTML = "<p style='color:green'>" + data.data.message + "</p>";
                this.reset(); // Limpia el formulario
                actualizarTablaCorredoresViaAjax(); // Actualiza la tabla de corredores
                 // Opcional: Actualizar el select de corredores en el formulario de cronómetro si está en la misma página
                if (typeof actualizarSelectCorredores === 'function') {
                    actualizarSelectCorredores();
                }

            } else {
                document.getElementById("mensaje-corredor").innerHTML = "<p style='color:red'>" + (data.data.message || 'Error al registrar el corredor.') + "</p>";
            }
        })
        .catch(error => {
            console.error('Error en fetch registrar_corredor_ajax:', error);
            document.getElementById("mensaje-corredor").innerHTML = "<p style='color: red;'>Error de conexión al registrar.</p>";
        });
    });

    function actualizarTablaCorredoresViaAjax() {
        fetch("<?php echo admin_url('admin-ajax.php'); ?>?action=obtener_lista_corredores_ajax_v1_2")
            .then(res => res.text())
            .then(html => {
                const contenedorTabla = document.getElementById("contenedor-lista-corredores");
                if (contenedorTabla) {
                    contenedorTabla.innerHTML = html;
                } else {
                    // Si el shortcode [lista_corredores_ajax] no está en la página, esto es esperado.
                    // console.warn("Contenedor #contenedor-lista-corredores no encontrado. Asegúrate de usar el shortcode [lista_corredores_ajax].");
                }
            })
            .catch(error => console.error('Error fetching lista corredores:', error));
    }
     // Opcional: Cargar tabla al inicio si el shortcode está en la misma página
    // document.addEventListener('DOMContentLoaded', actualizarTablaCorredoresViaAjax);
    </script>
    <?php return ob_get_clean();
}

// Shortcode para mostrar tabla de corredores (actualizable por AJAX)
add_shortcode('lista_corredores_ajax', 'mostrar_lista_corredores_ajax_v1_2');

function mostrar_lista_corredores_ajax_v1_2_content() { // Renombrada para evitar conflicto
    global $wpdb;
    $corredores = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM {$wpdb->prefix}corredores ORDER BY id ASC")
    );

    $output = ''; // Iniciar output
    if ($corredores) {
        $output .= '<table border="1" cellpadding="5" style="width:100%; margin-top:20px;"><thead><tr>
            <th>ID</th><th>Nombre del Equipo</th><th>Líder</th><th>Semestre</th>
        </tr></thead><tbody>';
        foreach ($corredores as $c) {
            $output .= "<tr>
                <td>" . esc_html($c->id) . "</td>
                <td>" . esc_html($c->nombre_equipo) . "</td>
                <td>" . esc_html($c->lider) . "</td>
                <td>" . esc_html($c->semestre) . "</td>
            </tr>";
        }
        $output .= '</tbody></table>';
    } else {
        $output .= '<p>No hay corredores registrados.</p>';
    }
    return $output;
}

function mostrar_lista_corredores_ajax_v1_2() {
    // El div contenedor ahora tiene un ID para que AJAX pueda reemplazar su contenido
    return '<div id="contenedor-lista-corredores">' . mostrar_lista_corredores_ajax_v1_2_content() . '</div>';
}


// Acción AJAX para obtener la lista de corredores
add_action('wp_ajax_obtener_lista_corredores_ajax_v1_2', 'ajax_handler_obtener_lista_corredores_v1_2');
add_action('wp_ajax_nopriv_obtener_lista_corredores_ajax_v1_2', 'ajax_handler_obtener_lista_corredores_v1_2');

function ajax_handler_obtener_lista_corredores_v1_2() {
    echo mostrar_lista_corredores_ajax_v1_2_content(); // Devuelve solo el contenido interno
    wp_die();
}

// Acción AJAX para registrar corredor
add_action('wp_ajax_registrar_corredor_ajax_v1_2', 'registrar_corredor_ajax_handler_v1_2');
add_action('wp_ajax_nopriv_registrar_corredor_ajax_v1_2', 'registrar_corredor_ajax_handler_v1_2'); // Si usuarios no logueados pueden registrar

function registrar_corredor_ajax_handler_v1_2() {
    global $wpdb;

    if (!isset($_POST['nombre_equipo'], $_POST['lider'], $_POST['semestre'])) {
        wp_send_json_error(['message' => 'Faltan datos del corredor.']);
        return;
    }

    $nombre_equipo = sanitize_text_field($_POST['nombre_equipo']);
    $lider = sanitize_text_field($_POST['lider']);
    $semestre = sanitize_text_field($_POST['semestre']);

    if (empty($nombre_equipo) || empty($lider) || empty($semestre)) {
        wp_send_json_error(['message' => 'Todos los campos son requeridos.']);
        return;
    }

    $result = $wpdb->insert(
        $wpdb->prefix . 'corredores',
        [
            'nombre_equipo' => $nombre_equipo,
            'lider' => $lider,
            'semestre' => $semestre
        ],
        ['%s', '%s', '%s']
    );

    if ($result) {
        wp_send_json_success(['message' => 'Corredor registrado exitosamente.']);
    } else {
        wp_send_json_error(['message' => 'Error al registrar el corredor en la base de datos.']);
    }
}
?>