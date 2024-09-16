<?php
/*
Plugin Name: Plugin Downloader
Description: Permite o download dos plugins instalados no WordPress.
Version: 1.0
Author: Seu Nome
*/

// Adiciona a página ao menu de administração
add_action('admin_menu', 'plugin_downloader_menu');
function plugin_downloader_menu() {
    add_menu_page(
        'Plugin Downloader',
        'Plugin Downloader',
        'manage_options',
        'plugin-downloader',
        'plugin_downloader_page',
        'dashicons-download',
        80
    );
}

// Exibe a página de plugins
function plugin_downloader_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Obtém a lista de plugins instalados
    $all_plugins = get_plugins();
    ?>
    <div class="wrap">
        <h1>Download de Plugins Instalados</h1>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th>Plugin</th>
                    <th>Versão</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($all_plugins as $plugin_file => $plugin_data) : ?>
                <tr>
                    <td><?php echo esc_html($plugin_data['Name']); ?></td>
                    <td><?php echo esc_html($plugin_data['Version']); ?></td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=plugin-downloader&download=' . urlencode($plugin_file)); ?>" class="button">Baixar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Lógica para baixar o plugin selecionado
add_action('admin_init', 'plugin_downloader_handle_download');
function plugin_downloader_handle_download() {
    if (isset($_GET['download'])) {
        $plugin_slug = sanitize_text_field(wp_unslash($_GET['download']));
        $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_slug;

        if (file_exists($plugin_path)) {
            // Cria um arquivo zip do plugin
            $zip = new ZipArchive();
            $zip_file = sys_get_temp_dir() . '/' . basename($plugin_slug) . '.zip';

            if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($plugin_path), RecursiveIteratorIterator::LEAVES_ONLY);
                foreach ($files as $file) {
                    if (!$file->isDir()) {
                        $file_path = $file->getRealPath();
                        $relative_path = substr($file_path, strlen($plugin_path) + 1);
                        $zip->addFile($file_path, $relative_path);
                    }
                }
                $zip->close();

                // Força o download do arquivo zip
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename=' . basename($zip_file));
                header('Content-Length: ' . filesize($zip_file));

                readfile($zip_file);
                unlink($zip_file);
                exit;
            }
        }
    }
}
