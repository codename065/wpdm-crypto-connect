<?php

use WPDM\__\__;
use WPDM\__\Session;
use WPDM\__\TempStorage;

class NetCred {

	const MINT_ADDRESS = '5UUH9RTDiSpq6HKS6bp4NdU9PNJpXRXuiw6ShBTBhgH2';
	const MORALIS_API_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJub25jZSI6IjdlZTZkMzJmLTVmNTctNDk5OS05ZDlkLTVjYmU3MTQzMzM2YyIsIm9yZ0lkIjoiNDU4NDIzIiwidXNlcklkIjoiNDcxNjM4IiwidHlwZUlkIjoiZmZiMmQzODgtMWVlOC00MGUxLWEwMjMtNTQ1NGMwZDdjODgyIiwidHlwZSI6IlBST0pFQ1QiLCJpYXQiOjE3NTIwODE4MDAsImV4cCI6NDkwNzg0MTgwMH0.jEGXme7-Dy70yI3fWUao30AeZx4byWN8Q5hkeqRPZOg';
	const MAX_SUPPLY = 10e9;
	const REWARD_ALLOCATION = 2e9;
	const CIRCULATION = 5e9;

	const LIQUIDITY_X = 10;

	function __construct() {

	}

	function moralisKey(  ) {
		return get_option('__wpdm_moralis_key', '');
	}

	function getLiquidity() {
		global $wpdb;
		$liquidity = $wpdb->get_var("SELECT SUM(liquidity_amount) FROM " . $wpdb->prefix . "ntcr_liquidity_collection");
		return $liquidity;
	}

	function getRate() {
		$stats = $this->getStats();
		return  number_format($stats->currentPrice, 9);
	}

	function getRewardAmount($action = 'signup', $amount = 0) {
		$rewards = get_option('__wpdm_ntcr_rewards');
		$rewards_type = get_option('__wpdm_ntcr_rewards_type');
		$reward = wpdm_valueof($rewards, $action);
		$reward_type = wpdm_valueof($rewards_type, $action);
		$rewardAmount = 0;
		if($reward_type === 'percent' && $amount > 0) {
			$rewardAmount = $amount / $reward_type * 100;
		} else
			$rewardAmount = $reward;
		return $rewardAmount;
	}

	function calculateReward($orderAmount, $type = 'purchase') {
		$rewardAmount = $this->getRewardAmount($type, $orderAmount);
		$stats = $this->getStats();
		$tokenPrice = $stats->currentPrice;
		$tokenPrice = $tokenPrice > 0 ? $tokenPrice : 0.000003;
		$tokenAmount = (int)($rewardAmount / $tokenPrice);
		return $tokenAmount;
	}

	function rewardForOrder($orderId, $type = 'purchase') {
		$order = new \WPDMPP\Libs\Order($orderId);
		if(!$order || !$order->uid) return;
		global $wpdb;
		$rewarded =	$wpdb->get_var("SELECT id FROM " . $wpdb->prefix . "ntcr_liquidity_collection WHERE order_id = '$orderId'");
		if($rewarded) return;
		$tokenAmount = $this->calculateReward($order->total, $type);
		if($tokenAmount <=0) return false;
		$this->award($order->uid, $tokenAmount, false, $order->order_id, $order->total);

		$emailTemplate = file_get_contents(dirname(__DIR__) . '/views/ntcr-order-reward-email.html');
		$user = get_user($order->uid);
		$tokenAmountFormatted = number_format($tokenAmount, 0);
		$emailTemplate = str_replace(["{{NTCR}}", "{{date}}"], [$tokenAmountFormatted, wp_date(get_option('date_format'))], $emailTemplate);
		$headers[]     = "From: WordPress Download Manager <support@wpdownloadmanager.com>";
		$headers[]     = "Content-type: text/html";
		wp_mail( $user->user_email, "🎉 You’ve Just Earned {$tokenAmountFormatted} NTCR!", $emailTemplate, $headers );
	}

	function rewardForSignup($userID) {
		if(!$userID) return false;
		global $wpdb;
		$tokenAmount = $this->getRewardAmount('signup');
		if($tokenAmount <= 0) return false;
		$this->award($userID, $tokenAmount, 'Reward for signup');
	}

	function award($user, $amount, $description = false, $orderId = '', $orderAmount = 0) {
		$admin_auth_token = get_option('__ntcr_admin_auth_token');
		if(!$admin_auth_token) return;
		global $wpdb;
		$tokenPrice = $this->getRate();
		$liquidityAmount = number_format($amount * $tokenPrice * self::LIQUIDITY_X, 9);
		$wpdb->insert(
			$wpdb->prefix . 'ntcr_liquidity_collection',
			array(
				'order_id' => $orderId,
				'order_amount' => $orderAmount,
				'token_amount' => $amount,
				'liquidity_amount' => $liquidityAmount,
				'token_price' => $tokenPrice,
				'user_id' => $user,
				'date' => time()
			)
		);
		$description = $description ?: 'Reward for order #' . $orderId;
		$wpuser = get_user($user);
		$this->request("token/transaction", ['amount' => $amount, 'receiver' => $wpuser->user_email, 'receiver_name' => $wpuser->display_name, 'type' => 'reward', 'action' => 'partner.customer.reward', 'partner_url' => home_url(), 'partner_name' => get_bloginfo('name'), 'description' => $description, 'order_id' => $orderId], $admin_auth_token);
		$ntcrAwarded = (double)get_user_meta($user, '__ntcr_awarded', true);
		$ntcrAwarded += $amount;
		update_user_meta($user, '__ntcr_awarded', $ntcrAwarded);
	}

	function getBalance($user) {
		//$ntcrAwarded = (double)get_user_meta($user, '__ntcr_awarded', true);
		//$ntcrClaimed = (double)get_user_meta($user, '__ntcr_claimed', true);
		//return $ntcrAwarded - $ntcrClaimed;
		$stats = $this->getStats();
		return (int)__::valueof($stats, 'balance');
	}

	function distributed() {
		global $wpdb;
		return $wpdb->get_var("SELECT SUM(token_amount) FROM " . $wpdb->prefix . "ntcr_liquidity_collection");
	}

	function recalculate() {
		global $wpdb;
		$uid = get_current_user_id();
		$orders = WPDMPP()->order->getOrders($uid, true);
		$startTime = 1747267200; //May 15, 2025
		foreach($orders as $order) {
			if($order->order_status === 'Completed' && (int)$order->date > $startTime) {
				$reward = $wpdb->get_row( "SELECT id FROM " . $wpdb->prefix . "ntcr_liquidity_collection WHERE order_id = '{$order->ID}'" );
				if(!$reward)
					$this->rewardForOrder($order->order_id);
			}
		}
		$stats = $this->getStats(true);
		return $stats;
	}

	function tokenHolders() {
		$holders = (int) TempStorage::get('ntcr_token_holders');
		if(is_array($holders) && isset($holders['totalHolders'])) return $holders['totalHolders'];
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => "https://solana-gateway.moralis.io/token/mainnet/holders/".self::MINT_ADDRESS,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'GET',
			CURLOPT_HTTPHEADER => array(
				"X-API-Key: ".$this->moralisKey()
			),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		$data = json_decode($response, true);
		if (isset($data['totalHolders'])) {
			TempStorage::set('ntcr_token_holders', $data, 3600);
			return $data['totalHolders'];
		} else {
			return 0; // or handle error
		}
	}

	function request($endpoint, $params = [], $auth_token = false, $method = 'POST') {
		$curl = curl_init();
		$headers[] = 'Content-Type: application/json';
		if($auth_token) $headers[] = 'Authorization: Bearer ' . $auth_token;
		curl_setopt_array($curl, array(
			CURLOPT_URL => 'https://api.netcred.io/'.$endpoint,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_POSTFIELDS => json_encode($params),
			CURLOPT_HTTPHEADER => $headers,
		));

		$response = curl_exec($curl);
		$response = json_decode($response);
		curl_close($curl);

		return $response;
	}

	function connectPlatform() {
		$time = Session::get('ntcr_conn_requested');

		$user = wp_get_current_user();
		$code = wpdm_query_var('code');

		if(time() - $time < 60 && !$code) {
			wp_send_json( ['sccess'=> false, 'message' => 'Too fast!'] );
			return;
		}

		$params = ['email' => $user->user_email];
		if($code) $params['code'] = (int)$code;

		$response = $this->request('auth/connect', $params);

		if($response->user) {
			update_user_meta( $user->ID, '__ntcr_connected', json_encode( $response ) );
			if(is_admin() && current_user_can('manage_options'))
				update_option('__ntcr_admin_auth_token', $response->user->app_token);
			//$stats = $this->getStats();
			//wp_send_json( ['connection' => $response, 'stats' => $stats] );
		}
		Session::set('ntcr_conn_requested', time());
		wp_send_json( $response );
	}

	public static function isConnected() {
		$user = wp_get_current_user();
		$connected = get_user_meta($user->ID, '__ntcr_connected', true);
		return $connected ? json_decode($connected) : false;
	}

	function getStats($fetch = false) {
		$user_id = get_current_user_id();
		$stats = TempStorage::get("__ntcr_stats_".$user_id);
		if(!$fetch && $stats) return $stats;
		$connection = self::isConnected();
		if(!$connection || !@$connection->user->app_token) return false;
		$stats = $this->request('token/stats', [], $connection->user->app_token, 'GET');
		if($stats && $stats->success === true)
			TempStorage::set("__ntcr_stats_".$user_id, $stats, 3600);
		return $stats;
	}

}
