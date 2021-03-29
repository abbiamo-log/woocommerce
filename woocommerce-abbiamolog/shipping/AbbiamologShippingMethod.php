<?php

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	function abbiamolog_shipping_method_init() {
		if ( ! class_exists( 'WC_Abbiamolog_Shipping_Method' ) ) {
      class WC_Abbiamolog_Shipping_Method extends WC_Shipping_Method {

      	/**
      	 * Constructor.
      	 *
      	 * @param int $instance_id Instance ID.
      	 */
      	public function __construct( $instance_id = 0 ) {
      		$this->id                 = 'abbiamolog';
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

      	/**
      	 * Calculate local pickup shipping.
      	 *
      	 * @param array $package Package information.
      	 */
      	public function calculate_shipping( $package = array() ) {
      		$this->add_rate(
      			array(
      				'label'    => 'Abbiamo',
      				'cost'     => '10.99',
      				'calc_tax' => 'per_ordem',
      			)
      		);
      	}
      }
		}
	}

	add_action( 'woocommerce_shipping_init', 'abbiamolog_shipping_method_init' );

	function add_abbiamolog_shipping_method( $methods ) {
		$methods['abbiamolog'] = 'WC_Abbiamolog_Shipping_Method';
		return $methods;
	}

	add_filter( 'woocommerce_shipping_methods', 'add_abbiamolog_shipping_method' );
}
