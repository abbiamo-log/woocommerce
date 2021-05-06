<?

/* Exit if accessed directly */
if (!defined('ABSPATH')) {
  exit;
}

namespace WooCommerce\Abbiamo\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class AbbiamoHttpHandler {
  private const OAUTH_URL = 'https://9ke2hmv7vg.execute-api.us-east-1.amazonaws.com/prod/auth/gentoken';
  private const SHIPPING_URL = 'https://dlah3ejgyf.execute-api.us-east-1.amazonaws.com/prod/shipping';
  private const ORDER_URL = 'https://lqwrlnop9b.execute-api.us-east-1.amazonaws.com/prod/v1/order';

  public function __construct() {
    $this->client = new Client();
  }

  public function create_abbiamo_order( $order_request ) {
    try {
      $access_token = $this->get_access_token();

      $response = $this->client->post(self::ORDER_URL, [
        'body' => json_encode($order_request),
        'headers' => [
          'Content-Type' => 'application/json',
          'Authorization' => "Bearer {$access_token}",
        ],
      ]);

      $response_body = json_decode((string) $response->getBody(), true);

      return $response_body['invoice_created'];
    } catch (ClientException $e) {
      error_log($e->getMessage());
      return null;
    }
  }

  public function get_shipping_rate( $postcode, $price, $weight ) {
    try {
      $access_token = $this->get_access_token();
      $response = $this->client->get(
        self::SHIPPING_URL . "?zip_code={$postcode}&weight={$weight}&amount={$price}",
        [
          'headers' => [
            'Authorization' => "Bearer {$access_token}",
          ],
        ],
      );
      $response_body = json_decode((string) $response->getBody(), true);

      return $response_body['amount'];
    } catch (ClientException $e) {
      return null;
    }
  }

  private function get_access_token() {
    $response = $this->client->post(self::OAUTH_URL, [
      'body' => json_encode([
        'username' => get_option('wc_settings_tab_abbiamolog_client_id'),
        'password' => get_option('wc_settings_tab_abbiamolog_secret_key'),
      ]),
      'headers' => [
        'Content-Type' => 'application/json',
      ],
    ]);
    $response_body = json_decode((string) $response->getBody(), true);

    return $response_body['access_token'];
  }
}
