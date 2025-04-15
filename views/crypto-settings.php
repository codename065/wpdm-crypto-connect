<?php
if(!defined('ABSPATH')) die('Dream more!');
$amount = get_post_meta(get_the_ID(), '__wpdm_crypto_amount', true);
$crypto_btn_label = get_post_meta(get_the_ID(), '__wpdm_crypto_btn_label', true);
$crypto_btn_style = get_post_meta(get_the_ID(), '__wpdm_crypto_btn_style', true);
?>
<div class="panel panel-default">
	<div class="panel-heading">
		<?php _e('Crypto Connect', 'wpdm-crypto-connect'); ?>
	</div>
	<div class="panel-body">

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label><?php _e('Pay with Solana For Download', 'wpdm-crypto-connect'); ?></label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="<?php echo (double)$amount; ?>" name="file[crypto_amount]" placeholder="0.1" />
                        <span class="input-group-addon">SOL</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label><?php _e('Payment Button Label', 'wpdm-crypto-connect'); ?></label>
                    <input type="text" class="form-control" value="<?php echo esc_attr($crypto_btn_label); ?>" name="file[crypto_btn_label]" placeholder="<?php _e('Complete Payment', 'wpdm-crypto-connect'); ?>" />
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label><?php _e('Payment Button Style', 'wpdm-crypto-connect'); ?></label>
                    <input type="text" class="form-control" value="<?php echo esc_attr($crypto_btn_style); ?>" name="file[crypto_btn_style]" placeholder="btn btn-primary" />
                </div>
            </div>
        </div>

    </div>
</div>
