<?php
if(!defined('ABSPATH')) die('Dream more!');
$stats = (new NetCred())->getStats();
?>
<div class="col-md-12">
	<div class="panel panel-default text-center">
		<div class="panel-heading">
			Your Reward Balance
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
			Max Supply
		</div>
	</div>
</div>
<div class="col-md-4">
	<div class="panel panel-default">
		<div class="panel-body">
			<h3 class=" text-info">2,000,000,000 NTCR</h3>
		</div>
		<div class="panel-footer">
			Reward Allocation
		</div>
	</div>
</div>
<div class="col-md-4">
	<div class="panel panel-default">
		<div class="panel-body">
			<h3 class=" text-primary"><?php echo number_format(wpdm_valueof($stats, 'rewardDistributed', 0, 'int'), 0) ?> NTCR</h3>
		</div>
		<div class="panel-footer">
			Reward Distributed
		</div>
	</div>
</div>
<div class="col-md-4">
	<div class="panel panel-default">
		<div class="panel-body">
			<h3 class=" text-primary"><?php echo number_format((new NetCred())->tokenHolders(), 0); ?></h3>
		</div>
		<div class="panel-footer">
			Holders
		</div>
	</div>
</div>
<div class="col-md-4">
	<div class="panel panel-default">
		<div class="panel-body">
			<h3 class=" text-success"><?php echo wpdmpp_price_format(wpdm_valueof($stats, 'liquidity', 0, 'int'), false, true) ?> USD</h3>
		</div>
		<div class="panel-footer">
			Liquidity  <span class="ttip" style="cursor: pointer" data-tooltip="Until the launch date, 1.5% of each sale is allocated to liquidity and updated daily!">🌱</span>
		</div>
	</div>
</div>
<div class="col-md-4">
	<div class="panel panel-default">
		<div class="panel-body">
			<h3 class=" text-success"><?php echo printf("%.9f",wpdm_valueof($stats, 'currentPrice')) ?> USD</h3>
		</div>
		<div class="panel-footer">
			Current Rate
		</div>
	</div>
</div>


