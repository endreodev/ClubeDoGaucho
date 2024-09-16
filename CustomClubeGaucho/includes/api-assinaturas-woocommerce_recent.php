<?php

// Função para registrar a API REST
add_action('rest_api_init', function () {
    register_rest_route('wc/v1', '/custom-subscriptions-recent', array(
        'methods'  => WP_REST_Server::READABLE,
        'callback' => 'get_recent_custom_subscriptions',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ));
});

function get_recent_custom_subscriptions($request)
{
    // Obtenha todos os status disponíveis para assinaturas
    $all_statuses = array(
        'wc-active', 
        'wc-cancelled', 
        'wc-on-hold', 
        'wc-expired', 
        'wc-pending-cancel', 
        'wc-trash', 
        'wc-switched', 
        'wc-pending', 
        'wc-completed'
    );

    $results = wc_get_orders(
        array(
            'type'   => 'shop_subscription',
            'status' => $all_statuses,
            'limit'  => 5000,
            'return' => 'ids',
        )
    );

    $subscriptions = array();
    $latest_subscriptions = array();
    $date_limit = new DateTime('-3 days'); // Defina o limite de 3 dias atrás

    foreach ($results as $item) {
        $subscription = wcs_get_subscription($item);
        $cpf = limparCPF_recent(get_post_meta($subscription->get_id(), '_billing_cpf', true));

        // Verifique se a assinatura foi alterada nos últimos 3 dias
        $modified_date = $subscription->get_date_modified();
        if ($modified_date && new DateTime($modified_date) >= $date_limit) {
            // Verifique se já temos uma assinatura para este CPF
            if (!isset($latest_subscriptions[$cpf])) {
                $latest_subscriptions[$cpf] = $subscription;
            } else {
                // Compare as datas para manter a última assinatura
                $existing_date = $latest_subscriptions[$cpf]->get_date_created();
                $current_date = $subscription->get_date_created();
                if ($current_date > $existing_date) {
                    $latest_subscriptions[$cpf] = $subscription;
                }
            }
        }
    }

    foreach ($latest_subscriptions as $subscription) {
        $next_payment_date = $subscription->get_date('next_payment');
        $formatted_date = !empty($next_payment_date) ? (new DateTime($next_payment_date))->format('d/m/Y') : 'N/A';
        $status_traduzido = traduzir_status_assinatura_recent($subscription->get_status());
        $user_info = get_userdata($subscription->get_user_id());

        // Corrige a data de nascimento, se necessário
        $data_nascimento = $user_info->billing_birthdate;
        $destination_array = explode('/', $data_nascimento);
        if (count($destination_array) >= 3) {
            if (strlen($destination_array[2]) <= 3) {
                $data_nascimento = $destination_array[0] . "/" . $destination_array[1] . "/19" . substr($destination_array[2], -2);
            }
        }

        // Recupera o IP do cliente do meta dado do pedido
        $customer_ip = get_post_meta($subscription->get_id(), '_customer_ip_address', true);
                
        $subscriptions[] = array(
            'status' => $status_traduzido,
            'nome' => esc_html(ucfirst($user_info->first_name) . ' ' . ucfirst($user_info->last_name)),
            'cpf' => limparCPF(get_post_meta($subscription->get_id(), '_billing_cpf', true)),
            'fone' => $user_info->billing_phone,
            'data_nasc' => $data_nascimento,
            'email' => esc_html($subscription->get_billing_email()),
            'cidade' => $user_info->billing_city,
            'estado' => $user_info->billing_state,
            'data_assinatura' => $subscription->get_date_created()->date('d/m/Y'),
            'ultimo_pagamento' => $subscription->get_date_paid() ? $subscription->get_date_paid()->date('d/m/Y') : 'N/A',
            'proximo_vencimento' => $formatted_date,
            'valor_mensalidade' => $subscription->get_total(),
            'ip_cliente' => $customer_ip,
            'id' => $subscription->get_id()
        );
    }

    return new WP_REST_Response($subscriptions, 200);
}

function limparCPF_recent($cpf) {
    return str_replace(array('.', '-'), '', $cpf);
}

// Função auxiliar para traduzir o status da assinatura
function traduzir_status_assinatura_recent($status)
{
    $status_traduzidos = array(
        'pending'           => 'PENDENTE',
        'processing'        => 'PROCESSANDO',
        'on-hold'           => 'EM ESPERA',
        'completed'         => 'COMPLETO',
        'cancelled'         => 'CANCELADO',
        'refunded'          => 'REEMBOLSADO',
        'failed'            => 'FALHOU',
        'trash'             => 'LIXEIRA',
        'wc-active'         => 'ATIVO',                // Usado em assinaturas
        'wc-expired'        => 'EXPIRADO',             // Usado em assinaturas
        'wc-pending-cancel' => 'CANCELAMENTO PENDENTE',// Usado em assinaturas
        'wc-on-hold'        => 'EM ESPERA',            // Usado em assinaturas
        'wc-cancelled'      => 'CANCELADO',            // Usado em assinaturas
        'wc-switched'       => 'TROCADO',              // Caso especial de assinatura trocada
        'wc-pending'        => 'PENDENTE',             // Status de assinatura pendente
        'wc-completed'      => 'COMPLETO',             // Status de assinatura completa
		'active'         	=> 'ATIVO',                // Usado em assinaturas
        'expired'        	=> 'EXPIRADO',             // Usado em assinaturas
        'pending-cancel' 	=> 'CANCELAMENTO PENDENTE',// Usado em assinaturas
        'on-hold'        	=> 'EM ESPERA',            // Usado em assinaturas
        'cancelled'      	=> 'CANCELADO',            // Usado em assinaturas
        'switched'       	=> 'TROCADO',              // Caso especial de assinatura trocada
        'pending'        	=> 'PENDENTE',             // Status de assinatura pendente
        'completed'      	=> 'COMPLETO',             // Status de assinatura completa
    );

    return isset($status_traduzidos[$status]) ? $status_traduzidos[$status] : strtoupper($status);
}
