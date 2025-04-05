<?php
if(!defined('ABSPATH')) die('Dream more!');
?>
<div class="panel panel-default">
	<div class="panel-heading">
        Crypto Connect
	</div>
	<div class="panel-body">

        <div class="form-group">
            <label>Network</label>
            <select name="__wpdm_crypto_network" class="form-control wpdm-custom-select">
                <option value="devnet">Devnet</option>
                <option value="mainnet-beta" <?php selected('mainnet', get_option('__wpdm_crypto_network')) ?> >Mainnet</option>
            </select>
        </div>

        <div class="form-group">
            <label>Solana Wallet Address</label>
            <input type="text" name="__wpdm_crypto_solana_walltet" class="form-control" value="<?php echo get_option('__wpdm_crypto_solana_walltet', ''); ?>" />
        </div>

    </div>
</div>
