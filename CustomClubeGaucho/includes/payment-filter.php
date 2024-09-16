<?php

    add_filter('woocommerce_available_payment_gateways', 'remove_credit_card_payment_gateways');

    function remove_credit_card_payment_gateways($available_gateways) {
		
		// Obter o valor total do carrinho
		$cart_total = WC()->cart->total;

		// Definir o valor limite acima do qual as opções de cartão de crédito serão removidas
		$valor_limite = 59;

		// Verificar se o valor total do carrinho é maior que o valor limite
		if ($cart_total > $valor_limite) {
		
			// IDs dos gateways de pagamento com cartão de crédito que você deseja remover
			$credit_card_gateways = array(
				'woo-pagarme-payments-credit_card', // Pagar.me
				'stripe', // Stripe
				'paypal', // PayPal
			);

			// Iterar sobre os gateways de pagamento e removê-los se forem de cartão de crédito
			foreach ($credit_card_gateways as $gateway_id) {
				if (isset($available_gateways[$gateway_id])) {
					unset($available_gateways[$gateway_id]);
				}
			}                                        

			// Selecionar automaticamente outro método de pagamento se disponível
			if (isset($available_gateways['woo-pagarme-payments-pix'])) {
				$available_gateways['woo-pagarme-payments-pix']->set_current();
			}
		
		}elseif($cart_total < $valor_limite){
						
			
			if (isset($available_gateways['woo-pagarme-payments-pix'])) {
				unset($available_gateways['woo-pagarme-payments-pix']);
// 				unset($available_gateways['woo-pagarme-payments-billet']);
			}
		}
		
        return $available_gateways;
    }