<?php

defined('ABSPATH') or die('Hey, what are you doing here? You silly human!');

/**
 * Gig Logistics Delivery Orders Class
 *
 * Adds order admin page customizations
 *
 * @since 1.0
 */
class GIGL_Delivery_Orders
{

	/** @var \GIGL_Delivery_Orders single instance of this class */
	private static $instance;

	/**
	 * Add various admin hooks/filters
	 */
	public function __construct()
	{

		// update order status
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
		// add_action('admin_footer-edit.php', array($this, 'add_order_bulk_actions'));

		add_action('load-edit.php', array($this, 'process_order_bulk_actions'));

		// add 'Gig Logistics Delivery Information' order meta box
		add_action('add_meta_boxes', array($this, 'add_order_meta_box'));

		// process order meta box order actions
		add_action(
			'woocommerce_order_action_GIGL_Delivery_Main_update_status',
			array($this, 'process_order_meta_box_actions')
		);

		// add 'Update Gig Logistics Delivery Status' order meta box order actions
		add_filter('woocommerce_order_actions', array($this, 'add_order_meta_box_actions'));
	}

	/**
	 * Add "Update Gigl Order Status"
	 *
	 * @since 1.0
	 */
	public function enqueue_admin_scripts($hook)
	{

		// Only load on WooCommerce Orders list page
		if ('edit.php' !== $hook) {
			return;
		}


		wp_enqueue_script(
			'gigl-admin-script',
			GIGL_PLUGIN_URL . 'assets/js/gigl-admin.js',
			array('jquery'),
			'1.0.0',
			true
		);

		wp_localize_script(
			'gigl-admin-script',
			'gigl_admin',
			array(
				'bulk_action_label' => __('Update Order Status (via gigl delivery)', 'gig-logistics-delivery'),
			)
		);
	}

	/**
	 * Processes the bulk action
	 *
	 * @since 1.0
	 */
	public function process_order_bulk_actions()
	{

		global $typenow;

		if ('shop_order' === $typenow) {

			$wp_list_table = _get_list_table('WP_Posts_List_Table');
			$action = $wp_list_table->current_action();

			if (!in_array($action, array('update_order_status'), true)) {
				return;
			}

			check_admin_referer('bulk-posts');
			if (
				!isset($_REQUEST['_wpnonce']) ||
				!wp_verify_nonce(
					sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])),
					'bulk-posts'
				)
			) {
				return;
			}
			if (isset($_REQUEST['post']) && is_array($_REQUEST['post'])) {

				$order_ids = array_map(
					'absint',
					wp_unslash($_REQUEST['post'])
				);
			}

			if (empty($order_ids)) {
				return;
			}

			if (function_exists('wc_set_time_limit')) {
				wc_set_time_limit(0);
			}

			foreach ($order_ids as $order_id) {
				try {
					GIGL_Delivery_Main()->update_order_shipping_status($order_id);
				} catch (\Exception $e) {
					// Optional: log error if needed
				}
			}
		}
	}

	/**
	 * Add 'Update Shipping Status' order actions to the 'Edit Order' page
	 *
	 * @since 1.0
	 * @param array $actions
	 * @return array
	 */
	public function add_order_meta_box_actions($actions)
	{

		$actions['GIGL_Delivery_Main_update_status'] =
			__('Update Order Status (via gigl delivery)', 'gig-logistics-delivery');

		return $actions;
	}

	/**
	 * Handle actions from the 'Edit Order' order action select box
	 *
	 * @since 1.0
	 * @param \WC_Order $order
	 */
	public function process_order_meta_box_actions($order)
	{

		GIGL_Delivery_Main()->update_order_shipping_status($order);
	}

	/**
	 * Add 'Gig Logistics Delivery Information' meta-box
	 *
	 * @since 1.0
	 */
	public function add_order_meta_box()
	{

		add_meta_box(
			'GIGL_Delivery_Main_order_meta_box',
			__('Gig Logistics Delivery', 'gig-logistics-delivery'),
			array($this, 'render_order_meta_box'),
			'shop_order',
			'side'
		);
	}

	/**
	 * Display the meta-box
	 *
	 * @since 1.0
	 */
	public function render_order_meta_box()
	{

		global $post;

		$order = wc_get_order($post);
		$gigl_order_id = $order->get_meta('gig_logistics_delivery_waybill');

		if ($gigl_order_id && $gigl_order_id > 0) {
			$this->show_gig_logistics_delivery_shipment_status($order);
		} else {
			$this->shipment_order_send_form($order);
		}
	}

	public function show_gig_logistics_delivery_shipment_status($order)
	{

		$gigl_order_id = $order->get_meta('gig_logistics_delivery_waybill');
		?>

		<table id="GIGL_Delivery_Main_order_meta_box">
			<tr>
				<th><strong><?php esc_html_e('Way-bill', 'gig-logistics-delivery'); ?> :</strong></th>
				<td><?php echo esc_html(empty($gigl_order_id) ? __('N/A', 'gig-logistics-delivery') : $gigl_order_id); ?>
				</td>
			</tr>

			<tr>
				<th><strong><?php esc_html_e('Shipping Status', 'gig-logistics-delivery'); ?> :</strong></th>
				<td>
					<?php echo wp_kses_post($order->get_meta('gig_logistics_delivery_status_res')); ?>
				</td>
			</tr>

			<tr>
				<th><strong><?php esc_html_e('Tracking ID', 'gig-logistics-delivery'); ?> :</strong></th>
				<td>
					<?php echo wp_kses_post($order->get_meta('gig_logistics_delivery_tracking_id')); ?>
				</td>
			</tr>
		</table>
		<?php
	}

	public function shipment_order_send_form($order)
	{
		?>
		<p><?php esc_html_e('No scheduled task for this order', 'gig-logistics-delivery'); ?></p>
		<?php
	}

	/**
	 * Gets the main loader instance.
	 *
	 * @return \GIGL_Delivery_Orders
	 */
	public static function instance()
	{

		if (null === self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

// fire it up!
return GIGL_Delivery_Orders::instance();
