<?

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

function abbiamolog_shipping_method_init() {
	if ( ! class_exists( 'WC_Abbiamolog_Shipping_Method' ) ) {
		class WC_Abbiamolog_Shipping_Method extends WC_Shipping_Method {

			const METHOD_ID = 'ABBIAMOLOG';

			/**
			* Constructor.
			*
			* @param int $instance_id Instance ID.
			*/
			public function __construct( $instance_id = 0 ) {
				$this->id                 = self::METHOD_ID;
				$this->instance_id        = absint( $instance_id );
				$this->method_title       = 'Abbiamolog';
				$this->method_description = 'Entregando emoções';
				$this->supports           = array(
					'shipping-zones',
					'instance-settings',
					'instance-settings-modal',
				);
				$this->title              = 'Abbiamolog';
				$this->init();
			}

			/**
			* Initialize local pickup.
			*/
			public function init() {

				// Load the settings.
				$this->init_form_fields();
				$this->init_settings();

				// Actions.
				add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
			}

			public function init_settings() {
				$client = new Client(['base_uri' => get_option('wc_settings_tab_abbiamolog_token_url')]);
				$response = $client->request('POST', '/prod/auth/gentoken', [
					'body' => json_encode([
						'username' => get_option('wc_settings_tab_abbiamolog_client_id'),
						'password' => get_option('wc_settings_tab_abbiamolog_secret_key'),
					]),
					'headers' => [
						'Content-Type' => 'application/json',
					],
				]);
				$response_body = json_decode((string) $response->getBody(), true);

				$this->abbiammo_access_token = $response_body['access_token'];
			}

			/**
			* Calculate local pickup shipping.
			*
			* @param array $package Package information.
			*/
			public function calculate_shipping( $package = array() ) {
				global $woocommerce;
				$postcode = $woocommerce->customer->get_shipping_postcode();

				if (empty($postcode)) {
					return;
				}

				$postcode = str_replace('-', '', $postcode);

				$items = $package['contents'];

				$volume = 0;
				$weight = 0;
				$price  = 0;
				foreach ($items as $item) {
					$quantity = $item['quantity'];
					$product  = wc_get_product($item['product_id']);

					$width  = $product->get_width();
					$height = $product->get_height();
					$length = $product->get_length();
					$volume = $volume + ($width * $length * $height) * $quantity;

					$weight = $weight + ($product->get_weight() * 1000) * $quantity;

					$price  = $price + ($product->get_price() * 100) * $quantity;
				}

				$cost = $this->calcule_abbiamo_shipping($postcode, $price, $weight);
				if (is_null($cost)) {
					return;
				}

				$this->add_rate(
					array(
						'id'			 => self::METHOD_ID,
						'label'    => 'Abbiamo',
						'cost'     => floatval( $cost / 100 ),
						'calc_tax' => 'per_ordem',
					)
				);
			}

			/**
			* Calculate Abbiamo shipping.
			*
			* @param string $poscode.
			* @param int $price.
			* @param int $weight
			* @return int|null
			*/
			private function calcule_abbiamo_shipping( $postcode, $price, $weight ) {
				$client = new Client(['base_uri' => get_option('wc_settings_tab_abbiamolog_shipping_url')]);

				try {
					$response = $client->request(
						'GET',
						"/prod/shipping?zip_code={$postcode}&weight={$weight}&price={$price}",
						[
							'headers' => [
								'Authorization' => "Bearer {$this->abbiammo_access_token}",
							],
						],
					);
					$response_body = json_decode((string) $response->getBody(), true);

					return $response_body['amount'];
				} catch (ClientException $e) {
					return null;
				}
			}
		}
	}
}

	add_action( 'woocommerce_shipping_init', 'abbiamolog_shipping_method_init' );

	function add_abbiamolog_shipping_method( $methods ) {
		$methods[WC_Abbiamolog_Shipping_Method::METHOD_ID] = 'WC_Abbiamolog_Shipping_Method';
		return $methods;
	}

	add_filter( 'woocommerce_shipping_methods', 'add_abbiamolog_shipping_method' );
}
