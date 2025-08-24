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
            ntcrnonce: WPDM_PUB_NONCE
        }, function (res) {
            WPDM.unblockUI('#ntcr-rewards');
            if(res.success) {
                $('#codentcrmdl').fadeIn(150, function () {
                    $(this).addClass('show');
                });
            } else
            {
                alert(`Error! ${res.message}`);
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
                admin: 1,
                code: $('#ntcrcnctcode').val(),
                ntcrnonce: WPDM_PUB_NONCE
            }, function (res) {
                console.log(res);
                WPDM.unblockUI('#ntcr-rewards');
                if(res.success) {
                    $('#connectntcr').removeClass('btn-info').addClass('btn-success').html('<i class="fa fa-check-double"></i> Connected');
                    $('#ntcrstatsnc').hide();
                    $('#ntcrstats').show();
                    $('#ntcrstats').load(ajaxurl+"?action=ntcr_stats&admin=1&ntcrnonce="+WPDM_PUB_NONCE);
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
            admin: 1,
            ntcrnonce: WPDM_PUB_NONCE
        }, function (res) {
            $('#ntcrstats').html(res);
            WPDM.unblockUI('#ntcrstats');
        });
    })

});
