<?
/**
 * Plugin Name: WooCommerce Abbiamolog
 * Plugin URI: https://github.com/abbiamo/woocommerce
 * Description: Abbiamolog Shipping Module for WooCommerce 3 & 4
 * Version: 0.0.1
 * Author: Abbiamo
 * Author URI: https://www.abbiamolog.com
 *
 * WC requires at least: 3.9.3
 * WC tested up to: 4.9.1
 *
 * License: GNU General Public License Version 3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

if(!isset($_SESSION)) {
  session_start();
}

/* Exit if accessed directly */
if (!defined('ABSPATH')) {
  exit;
}

class WooCommerceAbbiamo {
  function init() {
      define('ABBIAMO_FILE_PATH', plugin_dir_path(__FILE__));
      // define('JADLOG_ROOT_URL', plugins_url('', __FILE__));

      // add_action('admin_menu', array($this, 'add_export_tab'));
      add_filter('woocommerce_settings_tabs_array',            array($this, 'add_settings_tab'), 50);
      add_action('woocommerce_settings_tabs_abbiamo_shipping',  array($this, 'settings_tab'));
      add_action('woocommerce_update_options_abbiamo_shipping', array($this, 'update_settings'));

      require_once(ABBIAMO_FILE_PATH . '/vendor/autoload.php');
      require_once(ABBIAMO_FILE_PATH . '/src/shipping/AbbiamologShippingMethod.php');
      require_once(ABBIAMO_FILE_PATH . '/src/order/AbbiamoOrder.php');
  }

  function activate() {
    global $wp_version;

    if (!is_plugin_active('woocommerce/woocommerce.php')) {
        deactivate_plugins(plugin_basename(__FILE__)); /* Deactivate plugin */
        wp_die(__('You must run WooCommerce 3.x to install WooCommerce Abbiamolog plugin', 'abbiamolog'), __('WC not activated', 'abbiamolog'), array('back_link' => true));
        return;
    }

    if (!is_plugin_active('woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php')) {
        deactivate_plugins(plugin_basename(__FILE__)); /* Deactivate plugin */
        wp_die(__('You must run Brazilian Market on WooCommerce 3.7.x to install WooCommerce Abbiamolog plugin', 'abbiamolog'), __('Brazilian Market on WooCommerce not activated', 'abbiamolog'), array('back_link' => true));
        return;
    }

    if ((float)$wp_version < 3.5) {
        deactivate_plugins(plugin_basename(__FILE__)); /* Deactivate plugin */
        wp_die(__('You must run at least WordPress version 3.5 to install WooCommerce Abbiamolog plugin', 'abbiamolog'), __('WP not compatible', 'abbiamolog'), array('back_link' => true));
        return;
    }

    define('ABBIAMOLOG_FILE_PATH', dirname(__FILE__));
  }

  function deactivate() {

  }

  public function add_settings_tab( $settings_tabs ) {
      $settings_tabs['abbiamo_shipping'] = __( 'Abbiamo', 'abbiamo' );
      return $settings_tabs;
  }

  public function settings_tab() {
      echo "<style media=\"screen\" type=\"text/css\">
          #mainform label {
              display: block;
              font-weight: bold;
              padding: 10px 0 0 0;
          }
          </style>
          <div class=\"updated woocommerce-message\">
              <p><strong>".__('Por favor, faça a configuração do plugin Abbiamolog.', 'abbiamolog')."</strong></p>
          </div>";
      echo "<h3>".__('Configurações gerais', 'abbiamolog')."</h3>";
      woocommerce_admin_fields( $this->get_shipments_settings() );
      echo "<h3>".__('Configurações de Loja', 'abbiamolog')."</h3>";
      woocommerce_admin_fields( $this->get_shop_settings() );
      echo "<h3>".__('Endereço de coleta', 'abbiamolog')."</h3>";
      woocommerce_admin_fields( $this->get_pickup_settings() );
  }

  public function update_settings() {
      woocommerce_update_options( $this->get_shipments_settings() );
      woocommerce_update_options( $this->get_shop_settings() );
      woocommerce_update_options( $this->get_pickup_settings() );
  }

  public function get_shipments_settings() {
      return array(
          'ABBIAMOLOG_CLIENT_ID' => array(
              'name'     => __('Client ID da conta', 'abbiamo'),
              'type'     => 'text',
              'css'      => 'width:500px;',
              'desc'     => '',
              'default'  => '',
              'id'       => 'wc_settings_tab_abbiamolog_client_id'
          ),
          'ABBIAMOLOG_SECRET_KEY' => array(
              'name'     => __('Secret Key da conta', 'abbiamo'),
              'type'     => 'text',
              'css'      => 'width:500px;',
              'desc'     => '',
              'default'  => '',
              'id'       => 'wc_settings_tab_abbiamolog_secret_key'
          ),
          'ABBIAMOLOG_TOKEN_URL' => array(
              'name'     => __('Endereço para gerar Token', 'abbiamo'),
              'type'     => 'text',
              'css'      => 'width:500px;',
              'desc'     => '',
              'default'  => '',
              'id'       => 'wc_settings_tab_abbiamolog_token_url'
          ),
          'ABBIAMOLOG_ORDER_URL' => array(
              'name'     => __('Endereço para gerar order', 'abbiamo'),
              'type'     => 'text',
              'css'      => 'width:500px;',
              'desc'     => '',
              'default'  => '',
              'id'       => 'wc_settings_tab_abbiamolog_order_url'
          ),
          'ABBIAMOLOG_SHIPPING_URL' => array(
              'name'     => __('Endereço para calcular o frete', 'abbiamo'),
              'type'     => 'text',
              'css'      => 'width:500px;',
              'desc'     => '',
              'default'  => '',
              'id'       => 'wc_settings_tab_abbiamolog_shipping_url'
          ),
      );
  }

  public function get_pickup_settings() {
    return array(
        'ABBIAMOLOG_PICKUP_ZIP_CODE' => array(
            'name'     => __('CEP', 'abbiamo'),
            'type'     => 'text',
            'css'      => 'width:500px;',
            'desc'     => 'Sem pontuação. Ex: 01000000',
            'default'  => '',
            'id'       => 'wc_settings_tab_abbiamolog_pickup_zip_code',
        ),
        'ABBIAMOLOG_PICKUP_STATE' => array(
            'name'     => __('Estado', 'abbiamo'),
            'type'     => 'select',
            'options'  => [
              'AC' => 'Acre',
              'AL' => 'Alagoas',
              'AP' => 'Amapá',
              'AM' => 'Amazonas',
              'BA' => 'Bahia',
              'CE' => 'Ceará',
              'ES' => 'Espírito Santos',
              'GO' => 'Goiás',
              'MO' => 'Maranhão',
              'MA' => 'Mato Grosso',
              'MS' => 'Mato Grosso do Sul',
              'MG' => 'Minas Gerais',
              'PA' => 'Pará',
              'PB' => 'Paraíba',
              'PR' => 'Paraná',
              'PE' => 'Pernambuco',
              'PI' => 'Piauí',
              'RJ' => 'Rio de Janeiro',
              'RN' => 'Rio Grande do Norte',
              'RS' => 'Rio Grande do Sul',
              'RO' => 'Rondônia',
              'RR' => 'Roraima',
              'SC' => 'Santa Catarina',
              'SP' => 'São Paulo',
              'SE' => 'Sergipe',
              'TO' => 'Tocantins',
              'DF' => 'Distrito Federal',
             ],
            'default'  => 'SP',
            'id'       => 'wc_settings_tab_abbiamolog_pickup_state',
        ),
        'ABBIAMOLOG_PICKUP_CITY' => array(
            'name'     => __('Cidade', 'abbiamo'),
            'type'     => 'text',
            'css'      => 'width:500px;',
            'desc'     => '',
            'default'  => '',
            'id'       => 'wc_settings_tab_abbiamolog_pickup_city',
        ),
        'ABBIAMOLOG_PICKUP_NEIGHBORHOOD' => array(
            'name'     => __('Bairro', 'abbiamo'),
            'type'     => 'text',
            'css'      => 'width:500px;',
            'desc'     => '',
            'default'  => '',
            'id'       => 'wc_settings_tab_abbiamolog_pickup_neighborhood',
        ),
        'ABBIAMOLOG_PICKUP_STREET' => array(
            'name'     => __('Endereço', 'abbiamo'),
            'type'     => 'text',
            'css'      => 'width:500px;',
            'desc'     => '',
            'default'  => '',
            'id'       => 'wc_settings_tab_abbiamolog_pickup_street',
        ),
        'ABBIAMOLOG_PICKUP_STREET' => array(
            'name'     => __('Endereço', 'abbiamo'),
            'type'     => 'text',
            'css'      => 'width:500px;',
            'desc'     => '',
            'default'  => '',
            'id'       => 'wc_settings_tab_abbiamolog_pickup_street',
        ),
        'ABBIAMOLOG_PICKUP_STREET_NUMBER' => array(
            'name'     => __('Número', 'abbiamo'),
            'type'     => 'text',
            'css'      => 'width:500px;',
            'desc'     => '',
            'default'  => '',
            'id'       => 'wc_settings_tab_abbiamolog_pickup_street_number',
        ),
        'ABBIAMOLOG_PICKUP_STARTING_TIME' => array(
            'name'     => __('Início do horário de coleta', 'abbiamo'),
            'type'     => 'text',
            'css'      => 'width:500px;',
            'desc'     => 'Ex: 12:00',
            'default'  => '',
            'id'       => 'wc_settings_tab_abbiamolog_pickup_starting_time',
        ),
        'ABBIAMOLOG_PICKUP_ENDING_TIME' => array(
            'name'     => __('Limite do horário de coleta', 'abbiamo'),
            'type'     => 'text',
            'css'      => 'width:500px;',
            'desc'     => 'Ex: 18:00',
            'default'  => '',
            'id'       => 'wc_settings_tab_abbiamolog_pickup_ending_time',
        ),
    );
  }

  public function get_shop_settings() {
    return array(
      'ABBIAMOLOG_SHOP_EMAIL' => array(
          'name'     => __('Email', 'abbiamo'),
          'type'     => 'text',
          'css'      => 'width:500px;',
          'desc'     => '',
          'default'  => '',
          'id'       => 'wc_settings_tab_abbiamolog_shop_email'
      ),
      'ABBIAMOLOG_SHOP_PHONE' => array(
          'name'     => __('Telefone', 'abbiamo'),
          'type'     => 'text',
          'css'      => 'width:500px;',
          'desc'     => '',
          'default'  => '',
          'id'       => 'wc_settings_tab_abbiamolog_shop_phone'
      ),
      'ABBIAMOLOG_SHOP_DOCUMENT' => array(
          'name'     => __('CNPJ', 'abbiamo'),
          'type'     => 'text',
          'css'      => 'width:500px;',
          'desc'     => 'Sem pontuação. Ex: 00000000000000',
          'default'  => '',
          'id'       => 'wc_settings_tab_abbiamolog_shop_document'
      ),
      'ABBIAMOLOG_SHOP_COMPANY_NAME' => array(
          'name'     => __('Razão Social', 'abbiamo'),
          'type'     => 'text',
          'css'      => 'width:500px;',
          'desc'     => 'Nome de registro da sua empresa que será usado em documentos oficiais como contrato social e notas fiscais. Ele precisa seguir as Leis de Registro de Empresas.',
          'default'  => '',
          'id'       => 'wc_settings_tab_abbiamolog_shop_company_name'
      ),
      'ABBIAMOLOG_SHOP_TRADING_NAME' => array(
          'name'     => __('Nome da Empresa', 'abbiamo'),
          'type'     => 'text',
          'css'      => 'width:500px;',
          'desc'     => 'Também chamado de nome fantasia, esse é o nome que será conhecido pelos seus clientes e será usado no dia a dia.',
          'default'  => '',
          'id'       => 'wc_settings_tab_abbiamolog_shop_trading_name'
      ),
    );
  }
}

$abbiamo_woocommerce = new WooCommerceAbbiamo();

register_activation_hook(__FILE__, array($abbiamo_woocommerce, 'activate'));
register_deactivation_hook(__FILE__, array($abbiamo_woocommerce, 'deactivate'));

$abbiamo_woocommerce->init();
