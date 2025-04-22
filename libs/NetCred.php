<?php

class NetCred {

	const MINT_ADDRESS = '5UUH9RTDiSpq6HKS6bp4NdU9PNJpXRXuiw6ShBTBhgH2';
	const MAX_SUPPLY = 10e9;
	const REWARD_ALLOCATION = 2e9;
	const CIRCULATION = 5e9;

	const LIQUIDITY_X = 10;

	function __construct() {

	}

	function getLiquidity() {
		global $wpdb;
		$liquidity = $wpdb->get_var("SELECT SUM(liquidity_amount) FROM " . $wpdb->prefix . "ntcr_liquidity_collection");
		return $liquidity;
	}

	function getRate() {
		return  number_format($this->getLiquidity() / self::CIRCULATION, 9);
	}

	function calculateReward($orderAmount, $percentage = 0.5) {
		$rewardAmount = (double)$orderAmount * (double)$percentage / 100;
		$tokenPrice = $this->getRate();
		$tokenAmount = (int)($rewardAmount / $tokenPrice);
		return $tokenAmount;
	}

	function rewardForOrder($orderId) {
		$order = new \WPDMPP\Libs\Order($orderId);
		if(!$order || !$order->uid) return;
		global $wpdb;
		$rewarded =	$wpdb->get_var("SELECT id FROM " . $wpdb->prefix . "ntcr_liquidity_collection WHERE order_id = '$orderId'");
		if($rewarded) return;
		$tokenAmount = $this->calculateReward($order->total);
		$this->award($order->uid, $tokenAmount, $order->order_id, $order->total);
	}

	function award($user, $amount, $orderId = '', $orderAmount = 0) {
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
		if(!$wpdb->insert_id) {
			wpdmdd($wpdb->last_error, $wpdb->last_query);
		}
		$ntcrAwarded = (double)get_user_meta($user, '__ntcr_awarded', true);
		$ntcrAwarded += $amount;
		update_user_meta($user, '__ntcr_awarded', $ntcrAwarded);
	}

	function getBalance($user) {
		$ntcrAwarded = (double)get_user_meta($user, '__ntcr_awarded', true);
		$ntcrClaimed = (double)get_user_meta($user, '__ntcr_claimed', true);
		return $ntcrAwarded - $ntcrClaimed;
	}

	function distributed() {
		global $wpdb;
		return $wpdb->get_var("SELECT SUM(token_amount) FROM " . $wpdb->prefix . "ntcr_liquidity_collection");
	}

	function recalculate($uid) {
		global $wpdb;
		$orders = WPDMPP()->order->getOrders($uid, true);
		foreach($orders as $order) {
			if($order->order_status === 'Completed') {
				$reward = $wpdb->get_row( "SELECT id FROM " . $wpdb->prefix . "ntcr_liquidity_collection WHERE order_id = '$order->ID'" );
				if(!$reward)
					$this->rewardForOrder($order->order_id);
			}
		}
	}

	function tokenHolders() {
		$holders = (int)\WPDM\__\TempStorage::get('ntcr_token_holders');
		if(is_array($holders) && isset($holders['totalHolders'])) return $holders['totalHolders'];
		$curl = curl_init();
		$key = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJub25jZSI6IjliZDFmOGU1LWQzMTYtNGE4Ni1iOGRkLTJkZmY0MDhkM2MyOCIsIm9yZ0lkIjoiNDQzMjY1IiwidXNlcklkIjoiNDU2MDYzIiwidHlwZUlkIjoiMTFhNmRmNTktMDIyMi00ZTc3LWFlM2QtMGRiM2ZjNDkwNzBhIiwidHlwZSI6IlBST0pFQ1QiLCJpYXQiOjE3NDUzMzU0MTMsImV4cCI6NDkwMTA5NTQxM30.YAmp6fIWiY9OO-Pcs7i4Nk3GQp_5UdrnS2b6GoIB3dY";
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
				"X-API-Key: {$key}"
			),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		$data = json_decode($response, true);
		if (isset($data['totalHolders'])) {
			\WPDM\__\TempStorage::set('ntcr_token_holders', $data, 3600);
			return $data['totalHolders'];
		} else {
			return 0; // or handle error
		}
	}

}
