<?php
/*
  Plugin Name: WPDM - Crypto Connect
  Plugin URI: https://www.wpdownloadmanager.com/
  Description: Connect with crypto wallet
  Author: WordPress Download Manager
  Version: 1.2.0
  Author URI: https://www.wpdownloadmanager.com/
 */


namespace WPDMPP\AddOn;

require_once __DIR__ . '/libs/NetCred.php';

use WPDM\__\__;
use WPDM\__\Crypt;
use WPDM\__\HTML\Element;

global $CryptoConnect;


class CryptoConnect {
	function __construct() {

		register_activation_hook(__FILE__, array($this, 'install'));

		add_shortcode( 'wpdm_crypto_connect', [ $this, 'connect' ] );
		add_shortcode( 'wpdm_wallet_info', [ $this, 'walletInfo' ] );
		add_shortcode( 'wpdm_request_payment', [ $this, 'requestPayment' ] );

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueueScripts' ] );
		add_action( 'wp_ajax_wpdmcrypto_validate_payment', [ $this, 'validatePayment' ] );
		add_action( 'wp_ajax_nopriv_wpdmcrypto_validate_payment', [ $this, 'validatePayment' ] );
		add_action( 'wp_ajax_wpdm_crypto_paid', [ $this, 'isPaid' ] );
		add_filter( 'add_wpdm_settings_tab', [ $this, 'settingsTab' ] );
		add_filter( 'wpdm_package_settings_tabs', [ $this, 'packageSettingsTab' ] );
		add_filter( 'wdm_before_fetch_template', [ $this, 'cryptoConnectButtons' ] );
		add_filter( 'wpdm_user_dashboard_menu', [ $this, 'dashboardMenu' ] );
		add_action('init', [$this, 'download']);
		add_action('wpdmpp_order_completed', [$this, 'rewardCustomer']);

		add_filter( 'update_plugins_wpdm-crypto-connect', [ $this, "updatePlugin" ], 10, 4 );

	}

	function install() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		global $wpdb;
		$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}ahm_crypto_payments` (
			  `ID` int NOT NULL AUTO_INCREMENT,		
    		  `crypto` varchar(40) NOT NULL,
    		  `network` varchar(255) NOT NULL,
    		  `transaction_id` varchar(255) NOT NULL,
			  `amount` double NOT NULL,
			  `payment_from` varchar(255) NOT NULL,  
			  `paid_to` varchar(255) NOT NULL,  
			  `product_id` int NOT NULL DEFAULT '0',			  
			  `user_id` int NOT NULL DEFAULT '0',			  
			  `metadata` json NOT NULL,			  
			  `created_at` int NOT NULL DEFAULT '0',			  
			  PRIMARY KEY (`ID`)
			) ENGINE=InnoDB";
		$wpdb->query($sql);

	}


	function updatePlugin( $update, $plugin_data, $plugin_file, $locales ) {
		$id                = basename( __DIR__ );
		$latest_versions   = WPDM()->updater->getLatestVersions();
		$latest_version    = wpdm_valueof( $latest_versions, $id );
		$access_token      = wpdm_access_token();
		$update            = [];
		$update['id']      = $id;
		$update['slug']    = $id;
		$update['url']     = $plugin_data['PluginURI'];
		$update['tested']  = true;
		$update['version'] = $latest_version;
		$update['package'] = $access_token !== '' ? "https://www.wpdownloadmanager.com/?wpdmpp_file={$id}.zip&access_token={$access_token}" : '';

		return $update;
	}


	function enqueueScripts() {
		if((is_singular('wpdmpro') && (double)get_post_meta(get_the_ID(), '__wpdm_crypto_amount', true) > 0) || wpdm_query_var('udb_page', 'txt') === 'cryptoconnect') {
			wp_enqueue_script(
				'vuejs-app',
				plugin_dir_url( __FILE__ ) . 'dist/app.js', // The bundled Vue app file
				array( 'wp-element' ), // Ensure WP's React library doesn't conflict
				filemtime( plugin_dir_path( __FILE__ ) . 'dist/app.js' ),
				true
			);


			wp_enqueue_script(
				'solana-web3',
				'https://unpkg.com/@solana/web3.js@latest/lib/index.iife.min.js', // The bundled Vue app file
				array( 'wp-element' )
			);
		}

		/*wp_enqueue_style(
			'vuejs-app-style',
			plugin_dir_url( __FILE__ ) . 'dist/style.css',
			array(),
			filemtime( plugin_dir_path( __FILE__ ) . 'dist/style.css' )
		);*/
	}

	function settingsTab( $tabs ) {
		$tabs['wpdm-crypto'] = wpdm_create_settings_tab( 'wpdm-crypto', 'Crypto Connect', [$this, 'settings'], 'fas fa-wallet' );
		return $tabs;
	}

	function settings() {
		if(isset($_POST['__wpdm_crypto_network'])) {
			update_option('__wpdm_crypto_network', wpdm_query_var('__wpdm_crypto_network', 'txt'));
			update_option('__wpdm_crypto_solana_wallet', wpdm_query_var('__wpdm_crypto_solana_wallet', 'txt'));
			update_option('__wpdm_crypto_ondashboard', wpdm_query_var('__wpdm_crypto_ondashboard', 'int'));
			die('Settings Saved Successfully.');
		}
		global $wpdb;
		$start = (int) (isset($_GET['st']) ? $_GET['st'] : 0);
		$per_page = 50;
		$payments = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ahm_crypto_payments order by ID desc LIMIT $start, $per_page");
		WPDM()->template->assign('payments', $payments)->assign('network', get_option('__wpdm_crypto_network'))->display('settings.php', __DIR__.'/views/');
	}

	function packageSettingsTab( $tabs ) {
		$tabs['cryptoconnect'] = array(
			'name'     => __( 'Crypto Connect', 'wpdm-crypto-connect' ),
			'callback' => array( $this, 'packageSettings' )
		);

		return $tabs;
	}

	function packageSettings() {
		WPDM()->template->display('crypto-settings.php', __DIR__.'/views/');
	}

	function dashboardMenu( $items ) {
		$items['cryptoconnect'] = ['name' => 'Crypto Connect', 'id' => 'cryptoconnect', 'icon' => 'fa-solid fa-wallet color-info', 'callback' => [$this, 'dashboard']];
		return $items;
	}

	function dashboard() {
		return WPDM()->template->assign('wpdmcryptoc', $this)->fetch('crypto-dashboard.php', __DIR__.'/views/');;
	}

	/**
	 * @return false|string
	 */
	/*function connect() {
		return WPDM()->template->fetch('connect.php', __DIR__.'/views/');
	}*/

	function connect() {
		$id = "cbtn-" . uniqid();

		return (new Element('div'))
			->attr('class', 'vue-app')
			->attr('style', 'display:inline-block;width:auto')
			->attr('id', $id)
			->data('id', $id)
			->data('vue-app', 'Connect')
			->render();
	}

	function walletInfo() {
		$id = "walletinfo";
		$network = get_option('__wpdm_crypto_network', 'devnet');
		return (new Element('div'))
			->attr('class', 'vue-app')
			->attr('id', $id)
			->data('id', $id)
			->data('network', $network)
			->data('vue-app', 'WalletInfo')
			->render();
	}

	static function requestPayment( $params = [] ) {
		$id = "btn-" . uniqid();
		$wallet = get_option('__wpdm_crypto_solana_wallet', '');
		$id      = __::valueof($params, 'id', $id);
		$product = __::valueof($params, 'product', 0);
		$label   = __::valueof($params, 'label', 'Make Payment');
		$amount  = __::valueof($params, 'amount');;
		$style   = __::valueof($params, 'style', 'btn btn-primary');
		$network = get_option('__wpdm_crypto_network', 'devnet');

		return (new Element('div'))
			->attr('class', 'vue-app')
			->attr('style', 'display:inline-block;width:auto')
			->attr('id', $id)
			->data('id', $id)
			->data('product', $product)
			->data('vue-app', 'RequestPayment')
			->data('recipient', $wallet)
			->data('label', $label)
			->data('amount', $amount)
			->data('style', $style)
			->data('network', $network)
			->render();
	}

	function cryptoPaid( $params = [] ) {
		return (new Element('div'))
			->attr('class', 'vue-app')
			->attr('id', 'cp-'.$params['product'])
			->data('id', 'cp-'.$params['product'])
			->data('product', $params['product'])
			->data('vue-app', 'Paid')
			->render();
	}

	function extractSolanaTransferDetails($txData) {
		$accountKeys = $txData['transaction']['message']['accountKeys'];
		$preBalances = $txData['meta']['preBalances'];
		$postBalances = $txData['meta']['postBalances'];

		// Assume the first 2 accounts are sender and receiver
		$sender = $accountKeys[0];
		$receiver = $accountKeys[1];

		// Calculate the difference to get the transferred amount
		$amount = $postBalances[1] - $preBalances[1];

		return [
			'sender' => $sender,
			'receiver' => $receiver,
			'amount_lamports' => $amount,
			'amount_sol' => $amount / 1000000000, // 1 SOL = 10^9 lamports
		];
	}

	function validatePayment() {
		// Check if the request is valid
		if (!isset($_REQUEST['signature'])) {
			wp_send_json(['success' => 0, 'message' => 'Missing signature.'], 400);
		}

		$signature = sanitize_text_field($_REQUEST['signature']);

		$expectedReceiver = get_option('__wpdm_crypto_solana_wallet', '');
		$expectedAmount = (double)get_post_meta(wpdm_query_var('product', 0), '__wpdm_crypto_amount', true);
		//$rpcUrl = get_option('__wpdm_crypto_network') === 'devnet' ? "https://solana-devnet.g.alchemy.com/v2/q06zlFMF0RUfRWw_XYCJBKTgLiKFZFJH" : "https://solana-mainnet.g.alchemy.com/v2/q06zlFMF0RUfRWw_XYCJBKTgLiKFZFJH";
		$rpcUrl = "https://solana-".get_option('__wpdm_crypto_network', 'devnet').".g.alchemy.com/v2/q06zlFMF0RUfRWw_XYCJBKTgLiKFZFJH";
		$payload = [
			"jsonrpc" => "2.0",
			"id" => 1,
			"method" => "getTransaction",
			"params" => [$signature, ["encoding" => "json", "commitment" => 'confirmed']]
		];

		$options = [
			"http" => [
				"method"  => "POST",
				"header"  => "Content-Type: application/json",
				"content" => json_encode($payload)
			]
		];

		$context = stream_context_create($options);
		$response = file_get_contents($rpcUrl, false, $context);
		$result = json_decode($response, true);

		if (!isset($result['result']) || $result['result'] === null) {
			wp_send_json(['success' => 0, 'message' => 'Transaction not found or invalid signature.']);
		}

		$transaction = $result['result'];

		$transactionStatus = $transaction['meta']['status'];

		if (isset($transactionStatus['err']) && $transactionStatus['err'] !== null) {
			wp_send_json(['success' => 0, 'message' => 'Transaction Failed.']);
		}


		$transactionSummery = $this->extractSolanaTransferDetails($transaction);

		$validReceiver = wpdm_valueof($transactionSummery, 'receiver') === $expectedReceiver;
		$validAmount = (double)wpdm_valueof($transactionSummery, 'amount_sol') >= $expectedAmount;

		if ($validReceiver && $validAmount) {
			global $wpdb;
			$expiresAt = strtotime('+1 year');
			//$rpcUrl = get_option('__wpdm_crypto_network') === 'devnet' ? "https://solana-devnet.g.alchemy.com/v2/q06zlFMF0RUfRWw_XYCJBKTgLiKFZFJH" : "https://solana-mainnet.g.alchemy.com/v2/q06zlFMF0RUfRWw_XYCJBKTgLiKFZFJH";
			$rpcUrl = "https://solana-".get_option('__wpdm_crypto_network', 'devnet').".g.alchemy.com/v2/q06zlFMF0RUfRWw_XYCJBKTgLiKFZFJH";
			$wpdb->insert( $wpdb->prefix . "ahm_crypto_payments", [
				'crypto' => 'SOL',
				'network' => $rpcUrl,
				'transaction_id' => $signature,
				'amount' => wpdm_valueof($transactionSummery, 'amount_sol'),
				'product_id' => wpdm_query_var('product', 0),
				'payment_from' => wpdm_valueof($transactionSummery, 'sender'),
				'user_id' => get_current_user_id(),
				'paid_to' => wpdm_valueof($transactionSummery, 'receiver'),
				'metadata' => json_encode(['expiry_date' => $expiresAt, 'IP' => __::get_client_ip()]),
				'created_at' => time(),
			]);
			$download_link = WPDM()->package->expirableDownloadLink(wpdm_query_var('product', 0));
			wp_send_json(['success' => 1, 'message' => 'Thanks for your payment, please click the following button to start download', 'download_link' => $download_link]);
		} elseif ($validReceiver) {
			wp_send_json(['success' => 0, 'message' => 'Transaction is valid, but the paid amount is insufficient.', 'txdata' => $transactionSummery, 'expectedAmount' => $expectedAmount]);
		} else {
			wp_send_json(['success' => -1, 'message' => 'Transaction is invalid.', 'txdata' => $transactionSummery, $expectedReceiver]);
		}
	}

	function isPaid() {
		$product = wpdm_query_var('product', 'int');
		global $wpdb;
		$data = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}ahm_crypto_payments WHERE product_id = '{$product}' AND user_id = '".get_current_user_id()."' order by ID desc");
		$data->metadata = json_decode($data->metadata);
		if($data->metadata->expiry_date > time()) {
			$data->expiresAt   = wp_date( get_option( 'date_format' ) . " " . get_option( 'time_format' ), $data->metadata->expiry_date );
			$data->downloadURL = add_query_arg( [ 'crytodl' => Crypt::encrypt( $data->ID ) ], home_url( '/' ) );
			wp_send_json( [ 'data' => $data ] );
		} else {
			wp_send_json( [ 'data' => false ] );
		}
	}

	function cryptoConnectButtons( $vars ) {
		$vars['wallet_connect'] = $this->connect();
		$label = get_post_meta($vars['ID'], '__wpdm_crypto_btn_label', true);
		$label = $label ? $label : 'Make Payment';
		$price = get_post_meta($vars['ID'], '__wpdm_crypto_amount', true);
		$style = get_post_meta($vars['ID'], '__wpdm_crypto_btn_style', true);
		$style = $style ?: 'btn btn-primary';
		$vars['crypto_payment'] = $this->requestPayment(['product' => $vars['ID'], 'label' => $label, 'amount' => $price, 'style' => $style]);
		$vars['crypto_paid'] = $this->cryptoPaid(['product' => $vars['ID']]);
		return $vars;
	}

	function download() {
		if(isset($_REQUEST['crytodl'])) {
			global $wpdb;
			$id = Crypt::decrypt($_REQUEST['crytodl']);
			$data = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}ahm_crypto_payments WHERE ID = '{$id}'");
			$data->metadata = json_decode($data->metadata);
			if($data->metadata->expiry_date > time()) {
				wp_redirect(WPDM()->package->expirableDownloadLink($data->product_id, 3));
				exit;
			} else {
				wpdmdd('Payment link has expired.');
			}
		}
	}

	function formatID(string $address, int $start = 4, int $end = 4): string {
		if (strlen($address) <= $start + $end) {
			return $address;
		}

		$startStr = substr($address, 0, $start);
		$endStr = substr($address, -$end);

		return $startStr . '...' . $endStr;
	}

	function rewardCustomer($orderId) {
		(new \NetCred())->rewardForOrder($orderId);
	}

}

$CryptoConnect = new CryptoConnect();
