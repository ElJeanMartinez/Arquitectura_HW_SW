<?php
/**
 * Plugin Name: Ranking de Tiempos
 * Description: Muestra los mejores tiempos por corredor.
 * Version: 1.1
 * Author: Tu Nombre
 */

add_shortcode('ranking_tiempos', 'mostrar_ranking_tiempos');

function mostrar_ranking_tiempos() {
    global $wpdb;
    $tabla_tiempos = $wpdb->prefix . 'tiempos';
    $tabla_corredores = $wpdb->prefix . 'corredores';

    $resultados = $wpdb->get_results("
        SELECT c.nombre_equipo, c.lider, MIN(t.tiempo) AS mejor_tiempo
        FROM $tabla_tiempos t
        JOIN $tabla_corredores c ON t.corredor_id = c.id
        GROUP BY t.corredor_id
        ORDER BY mejor_tiempo ASC
        LIMIT 10
    ");

    ob_start();
    if (!empty($resultados)) {
        echo "<table border='1' cellpadding='5'>
                <thead><tr><th>Equipo</th><th>Líder</th><th>Mejor Tiempo (s)</th></tr></thead><tbody>";
        foreach ($resultados as $r) {
            echo "<tr>
                    <td>" . esc_html($r->nombre_equipo) . "</td>
                    <td>" . esc_html($r->lider) . "</td>
                    <td>" . esc_html($r->mejor_tiempo) . "</td>
                  </tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<p>No hay tiempos registrados aún.</p>";
    }

    return ob_get_clean();
}

