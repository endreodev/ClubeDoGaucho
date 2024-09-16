<?php

// Função para registrar a API REST
function cupons_plugin_register_api() {
    register_rest_route('wc/v1', '/create-cupons', array(
        'methods' => 'POST',
        'callback' => 'cupons_plugin_handle_request',
    ));
}

add_action('rest_api_init', 'cupons_plugin_register_api');

// Função para manipular a solicitação da API
function cupons_plugin_handle_request(WP_REST_Request $request) {
    global $wpdb;

    $params = $request->get_json_params();

    $cpf = sanitize_text_field($params['cpf']);
    $cpf_limpo = str_replace(array('.', '-'), '', $cpf);
    $cupom = $params['cupom'];
    $itens = $params['itens'];

    $table_cupons = $wpdb->prefix . 'cupons';
    $table_itens = $wpdb->prefix . 'cupons_itens';

    // Verificar se o CPF existe no meta dos usuários do WooCommerce
    $user_id = $wpdb->get_var(
        $wpdb->prepare("SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'billing_cpf' AND REPLACE(REPLACE(meta_value, '.', ''), '-', '') = %s", $cpf_limpo)
    );

    if (!$user_id) {
        return new WP_REST_Response('CPF não encontrado no banco de dados', 301);
    }

    // Verificar se o cupom já existe
    $existing_cupom = $wpdb->get_row(
        $wpdb->prepare("SELECT id FROM $table_cupons WHERE doc = %s", $cupom['doc'])
    );

    if ($existing_cupom) {
        // Excluir itens existentes do cupom
        $wpdb->delete($table_itens, array('cupom_id' => $existing_cupom->id));

        // Atualizar dados na tabela cupons
        $wpdb->update($table_cupons, array(
            'cpf' => $cpf,
            'data' => sanitize_text_field($cupom['data']),
            'hora' => sanitize_text_field($cupom['hora']),
            'unidade' => sanitize_text_field($cupom['unidade']),
            'status' => sanitize_text_field($cupom['status']),
        ), array('id' => $existing_cupom->id));

        $cupom_id = $existing_cupom->id;
    } else {
        // Inserir dados na tabela cupons
        $wpdb->insert($table_cupons, array(
            'cpf' => $cpf,
            'doc' => sanitize_text_field($cupom['doc']),
            'data' => sanitize_text_field($cupom['data']),
            'hora' => sanitize_text_field($cupom['hora']),
            'unidade' => sanitize_text_field($cupom['unidade']),
            'status' => sanitize_text_field($cupom['status']),
        ));

        $cupom_id = $wpdb->insert_id;
    }

    // Inserir novamente todos os itens na tabela cupons_itens
    foreach ($itens as $item) {
        $wpdb->insert($table_itens, array(
            'cupom_id' => $cupom_id,
            'ean' => sanitize_text_field($item['EAN']),
            'descricao' => sanitize_text_field($item['descricao']),
            'qtde' => floatval($item['qtde']),
            'valor_unit' => floatval($item['valor_unit']),
            'valor_total' => floatval($item['valor_total']),
            'valor_desconto' => floatval($item['valor_desconto']),
            'valor_pago' => floatval($item['valor_pago']),
        ));
    }

    return new WP_REST_Response('Cupom e itens inseridos ou atualizados com sucesso', 200);
}
