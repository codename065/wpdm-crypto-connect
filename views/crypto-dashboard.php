<?php

use WPDM\__\Crypt;

if(!defined('ABSPATH')) die('Dream more!');
$amount = get_post_meta(get_the_ID(), '__wpdm_crypto_amount', true);
$crypto_btn_label = get_post_meta(get_the_ID(), '__wpdm_crypto_btn_label', true);
$crypto_btn_style = get_post_meta(get_the_ID(), '__wpdm_crypto_btn_style', true);
$network = get_option('__wpdm_crypto_network', 'devnet');
?>
<div class="card">
	<div class="card-header">
        <div class="float-right">
	        <?php
	        global $CryptoConnect;
	        echo $CryptoConnect->connect()
	        ?>
        </div>
		<div style="line-height: 34px"><?php _e('Connect with Wallet', 'wpdm-crypto-connect'); ?></div>
	</div>
	<div class="card-body">
		<?php
		echo $CryptoConnect->walletInfo();
		?>
    </div>
</div>

<?php require(__DIR__."/rewards.php") ?>

<div class="card">
	<div class="card-header">
		<?php _e('Purchases', 'wpdm-crypto-connect'); ?>
	</div>
	<div class="card-body-np">
        <table class="table table-striped m-0">
            <thead>
            <tr>
                <th><?php _e('Product', 'wpdm-crypto-connect'); ?></th>
                <th><?php _e('Amount', 'wpdm-crypto-connect'); ?></th>
                <th><?php _e('Sender Wallet', 'wpdm-crypto-connect'); ?></th>
                <th><?php _e('Transaction ID', 'wpdm-crypto-connect'); ?></th>
                <th><?php _e('Date', 'wpdm-crypto-connect'); ?></th>
                <th><?php _e('Expiry Date', 'wpdm-crypto-connect'); ?></th>
                <th><?php _e('Action', 'wpdm-crypto-connect'); ?></th>
            </tr>
            </thead>
            <tbody>
	        <?php
	        global $wpdb;
	        $payments = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ahm_crypto_payments WHERE user_id = '".get_current_user_id()."' order by ID desc");
            if($payments && count($payments) > 0) {
            foreach($payments as $payment){ ?>
                <tr>
                    <td><?php echo get_the_title($payment->product_id); ?></td>
                    <td><?php echo $payment->amount; ?> <?php echo $payment->crypto; ?></td>
                    <td><a target="_blank" href="https://solscan.io/account/<?php echo $payment->payment_from; ?>?cluster=<?php echo $network ?>"><?php echo $wpdmcryptoc->formatID($payment->payment_from); ?></a></td>
                    <td><a target="_blank" href="https://solscan.io/tx/<?php echo $payment->transaction_id; ?>?cluster=<?php echo $network ?>"><?php echo $wpdmcryptoc->formatID($payment->transaction_id); ?></a></td>
                    <td><?php echo $payment->created_at ? wp_date(get_option('date_format'), $payment->created_at) : '-'; ?></td>
                    <td><?php echo $payment->metadata ? wp_date(get_option('date_format'), json_decode($payment->metadata)->expiry_date) : '-'; ?></td>
                    <td><a href="<?php echo add_query_arg( [ 'crytodl' => Crypt::encrypt( $payment->ID ) ], home_url( '/' ) ); ?>" class="btn btn-primary btn-sm"><?php _e('Download', 'download-manager'); ?></a></td>
                </tr>
	        <?php } } else {
                ?>
                <tr>
                    <td colspan="7" class="text-center text-info"><?php _e('You did not purchase any with crypto yet.', 'wpdm-crypto-connect'); ?></td>
                </tr>
                <?php
            } ?>
        </table>
    </div>
</div>
