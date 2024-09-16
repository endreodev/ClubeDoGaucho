<?php
function cupons_plugin_create_tables() {
    global $wpdb;

    $table_cupons = $wpdb->prefix . 'cupons';
    $table_itens = $wpdb->prefix . 'cupons_itens';

    $charset_collate = $wpdb->get_charset_collate();

    $sql_cupons = "CREATE TABLE $table_cupons (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        cpf varchar(11) NOT NULL,
        doc varchar(20) NOT NULL,
        data date NOT NULL,
        hora time NOT NULL,
        unidade varchar(100) NOT NULL,
        status char(1) NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY doc (doc)
    ) $charset_collate;";

    $sql_itens = "CREATE TABLE $table_itens (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        cupom_id mediumint(9) NOT NULL,
        ean varchar(13) NOT NULL,
        descricao varchar(255) NOT NULL,
        qtde float NOT NULL,
        valor_unit float NOT NULL,
        valor_total float NOT NULL,
        valor_desconto float NOT NULL,
        valor_pago float NOT NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (cupom_id) REFERENCES $table_cupons(id) ON DELETE CASCADE,
        UNIQUE KEY (cupom_id, ean)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_cupons);
    dbDelta($sql_itens);
}
?>
