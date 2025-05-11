<?php
if(!defined('ABSPATH')) die('Dream more!');

$liquidity = (new NetCred())->getLiquidity();

$connected = NetCred::isConnected();
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&display=swap" rel="stylesheet">
<style>
    #ntcr-rewards {
        background: #f9fbfd !important;
    }
    #ntcr-rewards h2{ font-family: "JetBrains Mono", monospace; font-weight: 700; font-size: 48px; }
    #ntcr-rewards h3{ font-size: 15px !important; font-family: "JetBrains Mono", monospace; font-weight: 700; }
    #ntcr-rewards .panel-header { font-weight: 800 !important; font-size: 16px !important; }
    #ntcr-rewards .panel-header,
    #ntcr-rewards .panel-footer {
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
    .float-right{
        float: right !important;
    }
</style>
<div class="panel panel-default" id="ntcr-rewards">
	<div class="panel-heading">
        <?php if($connected) { ?>
            <button type="button" id="connectntcr" class="float-right btn btn-xs btn-success" style="margin-left: 15px;border-radius: 4px;padding: 4px 12px;"><i class="fa fa-check-double"></i> Connected</button>
        <?php } else { ?>
            <button type="button" id="connectntcr" class="float-right btn btn-xs btn-info" style="margin-left: 15px;border-radius: 4px;padding: 4px 12px;"><i class="fa fa-link"></i> Connect</button>
        <?php } ?>
        <a href="https://netcred.io/#docs" target="_blank" class="float-right text-primary"><i class="fa-regular fa-file-lines"></i> NTCR Whitepaper</a>
        <a href="https://netcred.io/#roadmap" target="_blank" class="float-right text-success" style="margin-right: 15px"><i class="fa fa-road"></i> Roadmap</a>
        <a href="https://netcred.io" target="_blank" class="float-right text-info" style="margin-right: 15px"><i class="fa fa-home"></i> NTCR Home</a>
		<?php _e('NetCred (NTCR) Status', 'wpdm-crypto-connect'); ?>
	</div>
	<div class="panel-body">
            <div class="row" id="ntcrstats" <?php if(!$connected) { ?>style="display:none"<?php } ?>>
                <?php include wpdm_tpl_path("admin-ntcr-stats.php", __DIR__); ?>
            </div>

            <div class="panel panel-default m-0" id="ntcrstatsnc" <?php if($connected) { ?>style="display:none"<?php } ?>><div class="panel-body text-secondary text-center">Your account is not connected with NetCred yet!</div></div>

	</div>
</div>
<style>
    /* Modal overlay */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
    }
    /* Modal box */
    .modal-box {
        width: 90%;
        max-width: 300px;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0.8);
        transition: transform 0.3s ease, opacity 0.3s ease;
    }

    .modal-overlay.show .modal-box {
        transform: translate(-50%, -50%) scale(1);
        opacity: 1;
    }

    .modal-close {
        position: absolute;
        top: 10px; right: 10px;
        background: none;
        border: none;
        font-size: 18px;
        cursor: pointer;
    }
</style>
<div class="modal-overlay" id="codentcrmdl">
    <div class="modal-box">
        <div class="panel" style=" border-radius: 8px !important;border:0;box-shadow: 0 0 16px rgba(0, 0, 0, 0.4)">
            <div class="panel-body">
                <h3 class="mb-3">Verification Code</h3>
                <div>
                    <input type="text" id="ntcrcnctcode" value="" class="form-control form-control-lg input-lg" style="text-align: center;font-family: monospace;font-weight: bold" placeholder="......" />
                    <small class="text-sm">Verification code sent to you email address</small>
                </div>
            </div>
            <div class="panel-footer text-center"><button type="button" class="btn btn-secondary cldmdlntcr">Close</button> <button type="button" id="vrfycnctntcr" class="btn btn-primary">Connect Now</button></div>
        </div>
    </div>
</div>


<script>
    jQuery(function ($) {
        const $tooltip = $('<div class="custom-tooltip"></div>').appendTo('body').hide();
        const $body = $('body');

        const clsntcrcnctmdl = () => {
            $('#codentcrmdl').fadeOut(150, function () {
                $(this).removeClass('show');
            });
        }

        $('.ttip').on('mouseenter', function () {
            $tooltip.text($(this).data('tooltip')).fadeIn(150);
        }).on('mousemove', function (e) {
            $tooltip.css({
                top: e.pageY + 30,
                left: e.pageX - 20
            });
        }).on('mouseleave', function () {
            $tooltip.fadeOut(150);
        });

        $body.on('click', '#connectntcr', function (e) {
            e.preventDefault();
            WPDM.blockUI('#ntcr-rewards');
            $.get(ajaxurl, {
                action: 'ntcr_connect',
                ntcrnonce: '<?php echo wp_create_nonce(WPDM_PUB_NONCE) ?>'
            }, function (res) {
                WPDM.unblockUI('#ntcr-rewards');
                if(res.success) {
                    $('#codentcrmdl').fadeIn(150, function () {
                        $(this).addClass('show');
                    });
                }
            });
        });

        $body.on('click', '.cldmdlntcr', function () {
            clsntcrcnctmdl();
        });

        $body.on('click', '#vrfycnctntcr', function () {
            clsntcrcnctmdl();
            WPDM.blockUI('#ntcr-rewards');
            $.get(ajaxurl, {
                action: 'ntcr_connect',
                code: $('#ntcrcnctcode').val(),
                ntcrnonce: '<?php echo wp_create_nonce(WPDM_PUB_NONCE) ?>'
            }, function (res) {
                console.log(res);
                WPDM.unblockUI('#ntcr-rewards');
                if(res.success) {
                    $('#connectntcr').removeClass('btn-info').addClass('btn-success').html('<i class="fa fa-check-double"></i> Connected');
                    $('#ntcrstatsnc').hide();
                    $('#ntcrstats').show();
                    $('#ntcrstats').load(ajaxurl+"?action=ntcr_stats&ntcrnonce=<?php echo wp_create_nonce(WPDM_PUB_NONCE) ?>");
                    WPDM.notify('You are connected with NTCR Platform', 'success', 'top-center', 5000);
                }
            }
            )
        });

        $body.on('click', '#recalntcr', function (e) {
            e.preventDefault();
            WPDM.blockUI('#ntcrstats');
            $.get(ajaxurl, {
                action: 'ntcr_recalculate',
                ntcrnonce: '<?php echo wp_create_nonce(WPDM_PUB_NONCE) ?>'
            }, function (res) {
                $('#ntcrstats').html(res);
                WPDM.unblockUI('#ntcrstats');
            });
        })

    });
</script>
