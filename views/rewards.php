<?php
if(!defined('ABSPATH')) die('Dream more!');

$liquidity = (new NetCred())->getLiquidity();
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&display=swap" rel="stylesheet">
<style>
    #ntcr-rewards {
        background: #f9fbfd !important;
    }
    #ntcr-rewards h2{ font-family: "JetBrains Mono", monospace; font-weight: 700; font-size: 48px; }
    #ntcr-rewards h3{ font-size: 20px !important; font-family: "JetBrains Mono", monospace; font-weight: 700; }
    #ntcr-rewards .card-header { font-weight: 800 !important; font-size: 16px !important; }
    #ntcr-rewards .card-header,
    #ntcr-rewards .card-footer {
        padding: 8px 20px !important;
        font-size: 14px;
        background: #fcfcfc !important;
        font-family: "JetBrains Mono", monospace;
        font-weight: 600;
    }
    .custom-tooltip {
        position: absolute;
        background-color: rgba(38, 62, 103, 0.92);
        color: #fff;
        padding: 10px 18px;
        border-radius: 6px;
        font-size: 12px;
        pointer-events: none;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        z-index: 1000;
        transition: opacity 0.2s ease;
        max-width: 250px;
    }
</style>
<div class="card" id="ntcr-rewards">
	<div class="card-header">
        <a href="" class="float-right text-primary"><i class="fa-regular fa-file-lines"></i> NTCR Whitepaper</a>
        <a href="" class="float-right text-success" style="margin-right: 15px"><i class="fa fa-road"></i> Roadmap</a>
        <a href="" class="float-right text-info" style="margin-right: 15px"><i class="fa fa-home"></i> NTRC Home</a>
		<?php _e('NetCred (NTCR) Status', 'wpdm-crypto-connect'); ?>
	</div>
	<div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <div class="card text-center">
                        <div class="card-header">
                            Your Reward Balance
                        </div>
                        <div class="card-body lead">
                            <h2 class=" text-success"><?php echo number_format((new NetCred())->getBalance(get_current_user_id()),0) ?> NTCR</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body lead">
                            <h3 class="">10,000,000,000 NTCR</h3>
                        </div>
                        <div class="card-footer">
                            Max Supply
                        </div>
                    </div>
                </div>
                 <div class="col-md-4">
                    <div class="card">
                        <div class="card-body lead">
                            <h3 class=" text-info">2,000,000,000 NTCR</h3>
                        </div>
                        <div class="card-footer">
                            Reward Allocation
                        </div>
                    </div>
                </div>
                 <div class="col-md-4">
                    <div class="card">
                        <div class="card-body lead">
                            <h3 class=" text-primary">6,508,000 NTCR</h3>
                        </div>
                        <div class="card-footer">
                            Reward Distributed
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body lead">
                            <h3 class=" text-primary">19,458</h3>
                        </div>
                        <div class="card-footer">
                            Holders
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body lead">
                            <h3 class=" text-success"><?php echo wpdmpp_price_format($liquidity, false, true) ?> USD</h3>
                        </div>
                        <div class="card-footer">
                            Liquidity  <span class="ttip" style="cursor: pointer" data-tooltip="Until the launch date, 1.5% of each sale is allocated to liquidity and updated daily!">🌱</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body lead">
                            <h3 class=" text-success"><?php echo (new NetCred())->getRate() ?> USD</h3>
                        </div>
                        <div class="card-footer">
                            Current Rate
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="card text-center m-0">
                        <div class="card-header">
                            Launch Date
                        </div>
                        <div class="card-body lead">
                            <h3 class="text-success">July 01, 2025</h3>
                        </div>
                    </div>
                </div>
                <!-- div class="col-md-4">
                    <div class="card">
                        <div class="d-flex justify-content-between align-items-start p-3">
                            <div>Reward Distributed</div>
                            <span class="badge bg-success text-white rounded-pill">10,000,000</span>
                        </div>
                    </div>
                </div -->
                <div class="col-md-6">

                </div>
            </div>
	</div>
</div>
<script>
    jQuery(function ($) {
        const $tooltip = $('<div class="custom-tooltip"></div>').appendTo('body').hide();

        $('.ttip').on('mouseenter', function () {
            $tooltip.text($(this).data('tooltip')).fadeIn(150);
        }).on('mousemove', function (e) {
            $tooltip.css({
                top: e.pageY + 30,
                left: e.pageX - 120
            });
        }).on('mouseleave', function () {
            $tooltip.fadeOut(150);
        });
    });
</script>
