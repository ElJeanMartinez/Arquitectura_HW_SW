<?php
/**
 * Plugin Name: Registro de Corredores
 * Description: Permite registrar corredores.
 * Version: 1.1
 * Author: Tu Nombre
 */

register_activation_hook(__FILE__, 'crear_tabla_corredores');

function crear_tabla_corredores() {
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
add_shortcode('registro_corredores', 'mostrar_formulario_registro');

function mostrar_formulario_registro() {
    if (isset($_POST['registrar_corredor'])) {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'corredores',
            [
                'nombre_equipo' => sanitize_text_field($_POST['nombre_equipo']),
                'lider' => sanitize_text_field($_POST['lider']),
                'semestre' => sanitize_text_field($_POST['semestre'])
            ]
        );
        wp_redirect($_SERVER['REQUEST_URI']); // Redirige después del envío
        exit;
    }

    ob_start(); ?>
    <form method="post">
        <input type="text" name="nombre_equipo" placeholder="Nombre del equipo" required><br>
        <input type="text" name="lider" placeholder="Líder del equipo" required><br>
        <input type="text" name="semestre" placeholder="Semestre" required><br>
        <input type="submit" name="registrar_corredor" value="Registrar">
    </form>
    <?php return ob_get_clean();
}

// Shortcode para mostrar tabla de corredores
add_shortcode('lista_corredores', 'mostrar_lista_corredores');

function mostrar_lista_corredores() {
    global $wpdb;
    $corredores = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}corredores ORDER BY id ASC");

    if ($corredores) {
        $output = '<table border="1" cellpadding="5"><thead><tr>
            <th>ID</th><th>Nombre del Equipo</th><th>Líder</th><th>Semestre</th>
        </tr></thead><tbody>';
        foreach ($corredores as $c) {
            $output .= "<tr>
                <td>{$c->id}</td>
                <td>{$c->nombre_equipo}</td>
                <td>{$c->lider}</td>
                <td>{$c->semestre}</td>
            </tr>";
        }
        $output .= '</tbody></table>';
    } else {
        $output = '<p>No hay corredores registrados.</p>';
    }

    return $output;
}
