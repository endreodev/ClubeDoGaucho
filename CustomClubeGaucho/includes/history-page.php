<?php

function enqueue_bootstrap_and_mask() {
    // Enfileirar o CSS do Bootstrap
    wp_enqueue_style('bootstrap-css', 'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css');
    
    // Enfileirar o JS do Bootstrap
    wp_enqueue_script('bootstrap-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.bundle.min.js', array('jquery'), null, true);
    
    // Enfileirar o JS para mascarar os valores
    wp_enqueue_script('jquery-mask', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js', array('jquery'), null, true);
    
}

add_action('wp_enqueue_scripts', 'enqueue_bootstrap_and_mask');


function format_currency($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

function format_date_brazil($date) {
    // Criar um objeto DateTime a partir da data fornecida
    $dateTime = new DateTime($date);

    // Formatar a data no padrão brasileiro
    return $dateTime->format('d/m/Y');
}

// Função para registrar o shortcode e exibir a tabela de histórico de cupons e itens do cliente logado
function cupons_plugin_generate_history_table() {
    if (!is_user_logged_in()) {
        return '<p>Você precisa estar logado para ver seu histórico de cupons.</p>';
    }

    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    $cpf = get_user_meta($user_id, 'billing_cpf', true);

    if (empty($cpf)) {
        return '<p>Não há histórico de compra para usuário.</p>';
    }

    global $wpdb;
    $table_cupons = $wpdb->prefix . 'cupons';
	

    $cpf_limpo = str_replace(array('.', '-'), '', $cpf);
    $cupons = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_cupons WHERE  REPLACE(REPLACE(cpf, '.', ''), '-', '') = %s ORDER BY doc", $cpf_limpo));

    if (empty($cupons)) {
        return '<p>Nenhum cupom encontrado.</p>';
    }

    $output = '
	<div class="container d-flex justify-content-center mt-3">
        <div class="row">
            <div class="col-sm-12 mt-5 mb-5">
                <div class="card">
                    <div class="card-body table-responsive  table-sm">
                        <h5 class="card-title">
                            Historico de Compras de Produtos
                        </h5>
                        <p>
                            Todas as notas por data
                        </p>	
	<table class="table table-hover table-sm"><thead><tr><th>Documento</th><th>Data</th><th>Hora</th><th>Unidade</th><th>Status</th><th>Detalhes</th></tr></thead><tbody>';

    foreach ($cupons as $index => $cupom) {
        $output .= '<tr>';
        $output .= '<td>' . $cupom->doc . '</td>';
        $output .= '<td>' . format_date_brazil($cupom->data) . '</td>';
        $output .= '<td>' . $cupom->hora . '</td>';
        $output .= '<td>' . $cupom->unidade . '</td>';
        $output .= '<td>' . ( $cupom->status == "C" ? "CANCELADO" : "NORMAL" ) . '</td>';
        $output .= '<td><button class="btn btn-sm" type="button" data-toggle="collapse" data-target="#details' . $index . '" aria-expanded="false" aria-controls="details' . $index . '">Ver Itens</button></td>';
        $output .= '</tr>';

        $output .= '<tr id="details' . $index . '" class="collapse">';
        $output .= '<td colspan="6"><table class="table table-hover table-sm"><thead><tr><th>Descrição</th><th>EAN</th><th>Quantidade</th><th>Valor Unitário</th><th>Valor Total</th><th>Desconto</th><th>Valor Pago</th></tr></thead><tbody>';

        $itens = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}cupons_itens WHERE cupom_id = %d", $cupom->id));
        foreach ($itens as $item) {
            $output .= '<tr>';
            $output .= '<td>' . $item->descricao . '</td>';
            $output .= '<td>' . $item->ean . '</td>';
            $output .= '<td>' . $item->qtde . '</td>';
            $output .= '<td>' . format_currency($item->valor_unit) . '</td>';
            $output .= '<td>' . format_currency($item->valor_total) . '</td>';
            $output .= '<td>' . format_currency($item->valor_desconto) . '</td>';
            $output .= '<td>' . format_currency($item->valor_pago) . '</td>';
            $output .= '</tr>';
        }

        $output .= '</tbody></table></td></tr>';
    }

    $output .= '</tbody></table></div></div></div></div></div>';

    return $output;
}


// Registrar o shortcode para exibir a tabela de histórico
function cupons_plugin_register_shortcodes() {
    add_shortcode('cupons_historico', 'cupons_plugin_generate_history_table');
}

add_action('init', 'cupons_plugin_register_shortcodes');


// Função para registrar o shortcode e exibir a tabela de histórico de cupons e itens do cliente logado
function cupons_plugin_generate_history_table_all() {
    if (!is_user_logged_in()) {
        return '<p>Você precisa estar logado para ver seu histórico de cupons.</p>';
    }

//     $current_user = wp_get_current_user();
//     $user_id = $current_user->ID;
//     $cpf = get_user_meta($user_id, 'billing_cpf', true);

//     if (empty($cpf)) {
//         return '<p>Não há histórico de compra para usuário.</p>';
//     }

    global $wpdb;
    $table_cupons = $wpdb->prefix . 'cupons';
	

    $cpf_limpo = str_replace(array('.', '-'), '', $cpf);
    $cupons = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_cupons ORDER BY id desc "));

    if (empty($cupons)) {
        return '<p>Nenhum cupom encontrado.</p>';
    }

    $output = '
	<div class="container d-flex justify-content-center mt-3">
        <div class="row">
            <div class="col-sm-12 mt-5 mb-5">
                <div class="card">
                    <div class="card-body table-responsive  table-sm">
                        <h5 class="card-title">
                            Historico de Compras de Produtos
                        </h5>
                        <p>
                            Todas as notas por data
                        </p>	
	<table class="table table-hover table-sm"><thead><tr><th>Documento</th><th>Data</th><th>Hora</th><th>cpf</th><th>Unidade</th><th>Status</th><th>Detalhes</th></tr></thead><tbody>';

    foreach ($cupons as $index => $cupom) {
        $output .= '<tr>';
        $output .= '<td>' . $cupom->doc . '</td>';
        $output .= '<td>' . format_date_brazil($cupom->data) . '</td>';
        $output .= '<td>' . $cupom->hora . '</td>';
		$output .= '<td>' . $cupom->cpf . '</td>';
        $output .= '<td>' . $cupom->unidade . '</td>';
        $output .= '<td>' . $cupom->status . '</td>';
        $output .= '<td><button class="btn btn-sm" type="button" data-toggle="collapse" data-target="#details' . $index . '" aria-expanded="false" aria-controls="details' . $index . '">Ver Itens</button></td>';
        $output .= '</tr>';

        $output .= '<tr id="details' . $index . '" class="collapse">';
        $output .= '<td colspan="6"><table class="table table-hover table-sm"><thead><tr><th>Descrição</th><th>EAN</th><th>Quantidade</th><th>Valor Unitário</th><th>Valor Total</th><th>Desconto</th><th>Valor Pago</th></tr></thead><tbody>';

        $itens = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}cupons_itens WHERE cupom_id = %d", $cupom->id));
        foreach ($itens as $item) {
            $output .= '<tr>';
            $output .= '<td>' . $item->descricao . '</td>';
            $output .= '<td>' . $item->ean . '</td>';
            $output .= '<td>' . $item->qtde . '</td>';
            $output .= '<td>' . format_currency($item->valor_unit) . '</td>';
            $output .= '<td>' . format_currency($item->valor_total) . '</td>';
            $output .= '<td>' . format_currency($item->valor_desconto) . '</td>';
            $output .= '<td>' . format_currency($item->valor_pago) . '</td>';
            $output .= '</tr>';
        }

        $output .= '</tbody></table></td></tr>';
    }

    $output .= '</tbody></table></div></div></div></div></div>';

    return $output;
}



// Registrar o shortcode para exibir a tabela de histórico
function cupons_plugin_register_shortcodes_all() {
    add_shortcode('cupons_historico_all', 'cupons_plugin_generate_history_table_all');
}

add_action('init', 'cupons_plugin_register_shortcodes_all');

// Adicionar estilos personalizados para a tabela de histórico
function cupons_plugin_enqueue_styles() {
    wp_enqueue_style('cupons-plugin-styles', plugins_url('assets/styles.css', __FILE__));
}

add_action('wp_enqueue_scripts', 'cupons_plugin_enqueue_styles');



