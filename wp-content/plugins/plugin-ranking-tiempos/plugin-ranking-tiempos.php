<?php
/**
 * Plugin Name: Ranking de Tiempos Dedicado
 * Description: Muestra los mejores tiempos por corredor (dedicado).
 * Version: 1.2
 * Author: Tu Nombre
 */

// Shortcode para mostrar el ranking (actualizable por AJAX)
add_shortcode('ranking_tiempos_dedicado_ajax', 'mostrar_ranking_tiempos_dedicado_ajax_v1_2');

function mostrar_ranking_tiempos_dedicado_ajax_v1_2_content() { // Renombrada para evitar conflicto
    global $wpdb;
    $tabla_tiempos = $wpdb->prefix . 'tiempos';
    $tabla_corredores = $wpdb->prefix . 'corredores';

    // Asegúrate de que la tabla 'corredores' exista
    $resultados = $wpdb->get_results( $wpdb->prepare(
        "SELECT c.nombre_equipo, c.lider, MIN(t.tiempo) AS mejor_tiempo
         FROM $tabla_tiempos t
         JOIN $tabla_corredores c ON t.corredor_id = c.id
         GROUP BY t.corredor_id
         ORDER BY mejor_tiempo ASC
         LIMIT 10" // Límite específico de este ranking
    ));

    $output = ''; // Iniciar output
    if (!empty($resultados)) {
        $output .= "<table border='1' cellpadding='5' style='width:100%; margin-top:20px;'>
            <thead><tr><th>Equipo</th><th>Líder</th><th>Mejor Tiempo (s)</th></tr></thead><tbody>";
        foreach ($resultados as $r) {
            $output .= "<tr>
                <td>" . esc_html($r->nombre_equipo) . "</td>
                <td>" . esc_html($r->lider) . "</td>
                <td>" . esc_html(number_format((float)$r->mejor_tiempo, 2, '.', '')) . "</td>
            </tr>";
        }
        $output .= "</tbody></table>";
    } else {
        $output .= "<p>No hay tiempos registrados en el ranking dedicado aún.</p>";
    }
    return $output;
}

function mostrar_ranking_tiempos_dedicado_ajax_v1_2() {
    // El div contenedor ahora tiene un ID para que AJAX pueda reemplazar su contenido
    // También se añade un botón para actualizar manualmente este ranking si se desea.
    $content = '<div id="contenedor-ranking-dedicado">' . mostrar_ranking_tiempos_dedicado_ajax_v1_2_content() . '</div>';
    $content .= '<button type="button" onclick="actualizarRankingDedicadoViaAjax()" style="margin-top:10px;">Actualizar Ranking Dedicado</button>';
    $content .= '<script>
    function actualizarRankingDedicadoViaAjax() {
        fetch("' . admin_url('admin-ajax.php') . '?action=obtener_ranking_dedicado_ajax_v1_2")
            .then(response => response.text())
            .then(html => {
                const contenedorRanking = document.getElementById("contenedor-ranking-dedicado");
                if (contenedorRanking) {
                    contenedorRanking.innerHTML = html;
                } else {
                    // console.warn("Contenedor #contenedor-ranking-dedicado no encontrado.");
                }
            })
            .catch(error => console.error("Error fetching ranking dedicado:", error));
    }
    // Opcional: Cargar tabla al inicio
    // document.addEventListener("DOMContentLoaded", actualizarRankingDedicadoViaAjax);
    </script>';
    return $content;
}

// Acción AJAX para obtener el ranking dedicado
add_action('wp_ajax_obtener_ranking_dedicado_ajax_v1_2', 'ajax_handler_obtener_ranking_dedicado_v1_2');
add_action('wp_ajax_nopriv_obtener_ranking_dedicado_ajax_v1_2', 'ajax_handler_obtener_ranking_dedicado_v1_2');

function ajax_handler_obtener_ranking_dedicado_v1_2() {
    echo mostrar_ranking_tiempos_dedicado_ajax_v1_2_content(); // Devuelve solo el contenido interno
    wp_die();
}
?>