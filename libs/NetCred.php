<?php

class NetCred {
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
		if(!$order) return;
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

}
