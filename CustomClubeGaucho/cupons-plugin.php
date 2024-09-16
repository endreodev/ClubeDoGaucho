<?php
/**
 * Plugin Name: API Assinaturas Modificada
 * Plugin URI: http://clubedogaucho.com/api-assinaturas
 * Description: Fornece API de assinaturas de clientes com filtro de alteração nos últimos 3 dias.
 * Version: 2.1
 * Author: Endreo Figueiredo
 * Author URI: http://clubedogaucho.com
 * License: GPLv2 or later
 * Text Domain: api-assinaturas-modificada
 */

    if (!defined('ABSPATH')) {
        exit;
    }

    // Incluir arquivos necessários
    include_once plugin_dir_path(__FILE__) . 'includes/create-tables.php';
    include_once plugin_dir_path(__FILE__) . 'includes/api.php';
    include_once plugin_dir_path(__FILE__) . 'includes/api-assinaturas-woocommerce.php';
    include_once plugin_dir_path(__FILE__) . 'includes/api-assinaturas-woocommerce_recent.php';
    include_once plugin_dir_path(__FILE__) . 'includes/history-page.php';
    include_once plugin_dir_path(__FILE__) . 'includes/payment-filter.php';

    // Hook para criar tabelas no momento da ativação do plugin
    register_activation_hook(__FILE__, 'cupons_plugin_create_tables');

    // Registrar o shortcode para exibir a tabela de histórico
    add_action('init', 'cupons_plugin_register_shortcodes');

    // Adicionar estilos personalizados para a tabela de histórico
    add_action('wp_enqueue_scripts', 'cupons_plugin_enqueue_styles');


	// Função para remover o botão de cancelamento na tela de visualização de assinatura
	function remover_botao_cancelamento_view_subscription() {
		if ( is_account_page() && isset( $_GET['view-subscription'] ) ) {
			remove_action( 'woocommerce_subscription_details_after_subscription_table', 'wcs_view_subscription_actions', 10 );
		}
	}
    
	add_action( 'template_redirect', 'remover_botao_cancelamento_view_subscription' );


?>
