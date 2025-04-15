<?php
if(!defined('ABSPATH')) die('Dream more!');
function format_id(string $address, int $start = 4, int $end = 4): string {
	if (strlen($address) <= $start + $end) {
		return $address;
	}

	$startStr = substr($address, 0, $start);
	$endStr = substr($address, -$end);

	return $startStr . '...' . $endStr;
}

?>
<div class="panel panel-default">
	<div class="panel-heading">
        <?php _e('Crypto Connect', 'wpdm-crypto-connect'); ?>
	</div>
	<div class="panel-body">

        <div class="form-group">
            <label><?php _e('Network', 'wpdm-crypto-connect'); ?></label>
            <select name="__wpdm_crypto_network" class="form-control wpdm-custom-select">
                <option value="devnet">Devnet</option>
                <option value="mainnet" <?php selected('mainnet', get_option('__wpdm_crypto_network')) ?> >Mainnet</option>
            </select>
        </div>

        <div class="form-group">
            <label><?php _e('Solana Wallet Address', 'wpdm-crypto-connect'); ?></label>
            <input type="text" name="__wpdm_crypto_solana_wallet" class="form-control" value="<?php echo get_option('__wpdm_crypto_solana_wallet', ''); ?>" />
        </div>

        <div class="form-group">
            <input type="hidden" name="__wpdm_crypto_ondashboard" value="0">
            <label><input type="checkbox" name="__wpdm_crypto_ondashboard" value="1" <?php checked(1, (int)get_option('__wpdm_crypto_ondashboard', 0)); ?> /> <?php _e('Show wallet connect on user dashboard', 'wpdm-crypto-connect'); ?></label>
        </div>

    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <?php _e('Received Payments', 'wpdm-crypto-connect'); ?>
    </div>
    <div class="panel-body-np">
        <table class="table table-striped">
            <thead>
            <tr>
                <th><?php _e('Product', 'wpdm-crypto-connect'); ?></th>
                <th><?php _e('Amount', 'wpdm-crypto-connect'); ?></th>
                <th><?php _e('Sender', 'wpdm-crypto-connect'); ?></th>
                <th><?php _e('Sender Wallet', 'wpdm-crypto-connect'); ?></th>
                <th><?php _e('Transaction ID', 'wpdm-crypto-connect'); ?></th>
                <th><?php _e('Date', 'wpdm-crypto-connect'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($payments as $payment){ ?>
            <tr>
                <td><?php echo $payment->product_id; ?></td>
                <td><?php echo $payment->amount; ?> <?php echo $payment->crypto; ?></td>
                <td><?php echo get_user($payment->user_id)->display_name; ?></td>
                <td><a target="_blank" href="https://solscan.io/account/<?php echo $payment->payment_from; ?>?cluster=<?php echo $network ?>"><?php echo format_id($payment->payment_from); ?></a></td>
                <td><a target="_blank" href="https://solscan.io/tx/<?php echo $payment->transaction_id; ?>?cluster=<?php echo $network ?>"><?php echo format_id($payment->transaction_id); ?></a></td>
                <td><?php echo $payment->created_at ? wp_date(get_option('date_format')." ".get_option('time_format'), $payment->created_at) : '-'; ?></td>
            </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
