<?php
if(!defined('ABSPATH')) die('Dream more!');
$stats = (new NetCred())->getStats();

?>
<div class="col-md-12">
	<div class="panel panel-default text-center">
		<div class="panel-heading">
			<?php _e('Your Account Balance', 'wpdm-crypto-connect'); ?>
		</div>
		<div class="panel-body">
			<h2 class="text-success"><span id="ntcrbalance"><?php echo number_format(wpdm_valueof($stats, 'balance', 0, 'int'),0) ?></span> NTCR  <a href="#" id="recalntcr" class="float-right ttip text-muted" data-tooltip="ReCalculate">⟳</a></h2>
		</div>
	</div>
</div>
<div class="col-md-4">
	<div class="panel panel-default">
		<div class="panel-body">
			<h3>10,000,000,000 NTCR</h3>
		</div>
		<div class="panel-footer">
			<?php _e('Max Supply', 'wpdm-crypto-connect'); ?>
		</div>
	</div>
</div>
<div class="col-md-4">
	<div class="panel panel-default">
		<div class="panel-body">
			<h3 class=" text-info">2,000,000,000 NTCR</h3>
		</div>
		<div class="panel-footer">
			<?php _e('Reward Allocation', 'wpdm-crypto-connect'); ?>
		</div>
	</div>
</div>
<div class="col-md-4">
	<div class="panel panel-default">
		<div class="panel-body">
			<h3 class=" text-primary"><?php echo number_format(wpdm_valueof($stats, 'rewardDistributed', 0, 'int'), 0) ?> NTCR</h3>
		</div>
		<div class="panel-footer">
			<?php _e('Reward Distributed', 'wpdm-crypto-connect'); ?>
		</div>
	</div>
</div>
<div class="col-md-4">
	<div class="panel panel-default">
		<div class="panel-body">
			<h3 class=" text-primary"><?php echo number_format(wpdm_valueof($stats, 'tokenHolders'), 0); ?></h3>
		</div>
		<div class="panel-footer">
			<?php _e('Holders', 'wpdm-crypto-connect'); ?>
		</div>
	</div>
</div>
<div class="col-md-4">
	<div class="panel panel-default">
		<div class="panel-body">
			<h3 class=" text-success"><?php echo wpdmpp_price_format(wpdm_valueof($stats, 'liquidity', 0, 'int'), false, true) ?> USD</h3>
		</div>
		<div class="panel-footer">
			Liquidity
		</div>
	</div>
</div>
<div class="col-md-4">
	<div class="panel panel-default">
		<div class="panel-body">
			<h3 class=" text-success"><?php echo printf("%.9f",wpdm_valueof($stats, 'currentPrice')) ?> USD</h3>
		</div>
		<div class="panel-footer">
			<?php _e('Current Rate', 'wpdm-crypto-connect'); ?>
		</div>
	</div>
</div>


