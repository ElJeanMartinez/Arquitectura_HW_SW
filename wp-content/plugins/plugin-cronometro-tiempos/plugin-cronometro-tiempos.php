<?php
/**
 * Plugin Name: Cronómetro de Tiempos
 * Description: Cronómetro interactivo para registrar tiempos de corredores (3 intentos por participante).
 * Version: 1.2
 * Author: Tu Nombre
 */

register_activation_hook(__FILE__, 'crear_tablas_cronometro');

function crear_tablas_cronometro() {
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
add_shortcode('registro_tiempos', 'formulario_cronometro');

function formulario_cronometro() {
    global $wpdb;
    $corredores = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}corredores");

    if (isset($_POST['registrar_tiempo'])) {
        $corredor_id = intval($_POST['corredor_id']);
        $tiempo = floatval($_POST['tiempo']);
        $intento = intval($_POST['intento']);

        // Insertar en tabla tiempos (ranking)
        $wpdb->insert(
            $wpdb->prefix . 'tiempos',
            [
                'corredor_id' => $corredor_id,
                'tiempo' => $tiempo,
            ]
        );

        // Insertar en tabla tiempos_detalle (intentos)
        $wpdb->insert(
            $wpdb->prefix . 'tiempos_detalle',
            [
                'corredor_id' => $corredor_id,
                'intento' => $intento,
                'tiempo' => $tiempo,
            ]
        );

        wp_redirect($_SERVER['REQUEST_URI']);
        exit;
    }

    ob_start(); ?>
    <form method="post">
        <label>Selecciona un corredor:</label><br>
        <select name="corredor_id" required>
            <option value="">-- Selecciona --</option>
            <?php foreach ($corredores as $c): ?>
                <option value="<?= esc_attr($c->id) ?>"><?= esc_html($c->nombre_equipo) ?> (<?= esc_html($c->lider) ?>)</option>
            <?php endforeach; ?>
        </select><br><br>

        <label>Número de intento (1, 2 o 3):</label><br>
        <select name="intento" required>
            <option value="">-- Selecciona intento --</option>
            <option value="1">Intento 1</option>
            <option value="2">Intento 2</option>
            <option value="3">Intento 3</option>
        </select><br><br>

        <div>
            <h3 id="cronometro">0.00</h3>
            <input type="hidden" name="tiempo" id="campo-tiempo">
            <button type="button" onclick="iniciarCrono()">Iniciar</button>
            <button type="button" onclick="detenerCrono()">Detener</button>
            <button type="button" onclick="reiniciarCrono()">Reiniciar</button>
        </div><br>

        <input type="submit" name="registrar_tiempo" value="Registrar Tiempo">
    </form>

    <script>
        let tiempo = 0.00;
        let intervalo = null;

        function actualizarCrono() {
            tiempo += 0.01;
            document.getElementById("cronometro").innerText = tiempo.toFixed(2);
        }

        function iniciarCrono() {
            if (!intervalo) {
                intervalo = setInterval(actualizarCrono, 10);
            }
        }

        function detenerCrono() {
            clearInterval(intervalo);
            intervalo = null;
            document.getElementById("campo-tiempo").value = tiempo.toFixed(2);
        }

        function reiniciarCrono() {
            clearInterval(intervalo);
            intervalo = null;
            tiempo = 0.00;
            document.getElementById("cronometro").innerText = "0.00";
            document.getElementById("campo-tiempo").value = "";
        }
    </script>
    <?php return ob_get_clean();
}
add_shortcode('ver_intentos', 'mostrar_tiempos_detalle');

function mostrar_tiempos_detalle() {
    global $wpdb;

    $resultados = $wpdb->get_results("
        SELECT d.*, c.nombre_equipo, c.lider
        FROM {$wpdb->prefix}tiempos_detalle d
        JOIN {$wpdb->prefix}corredores c ON d.corredor_id = c.id
        ORDER BY d.corredor_id, d.intento ASC
    ");

    if ($resultados) {
        $output = "<table border='1' cellpadding='5'>
        <thead><tr><th>Equipo</th><th>Líder</th><th>Intento</th><th>Tiempo</th><th>Fecha</th></tr></thead><tbody>";
        foreach ($resultados as $r) {
            $output .= "<tr>
                <td>" . esc_html($r->nombre_equipo) . "</td>
                <td>" . esc_html($r->lider) . "</td>
                <td>" . esc_html($r->intento) . "</td>
                <td>" . esc_html($r->tiempo) . "</td>
                <td>" . esc_html($r->fecha) . "</td>
            </tr>";
        }
        $output .= "</tbody></table>";
    } else {
        $output = "<p>No hay tiempos registrados aún.</p>";
    }

    return $output;
}
