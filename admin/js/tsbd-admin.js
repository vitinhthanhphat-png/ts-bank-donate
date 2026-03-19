/* jshint esversion: 6 */
(function ($) {
    'use strict';

    const nonce  = TSBD.nonce;
    const ajax   = TSBD.ajax_url;
    const str    = TSBD.strings;

    // ─── Show/Hide type-specific fields ──────────────────────────────────────
    function toggleTypeFields() {
        const type = $('input[name="type"]:checked').val();
        $('#tsbd-bank-fields').toggle(type === 'bank');
        $('#tsbd-momo-fields').toggle(type === 'momo');
        // Update pill selector visual state
        $('.tsbd-type-pill').removeClass('is-selected');
        $('.tsbd-type-pill input[value="' + type + '"]').closest('.tsbd-type-pill').addClass('is-selected');
    }
    // Pill click — trigger radio change
    $(document).on('click', '.tsbd-type-pill', function () {
        $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
    });
    $(document).on('change', 'input[name="type"]', toggleTypeFields);
    toggleTypeFields(); // init

    // ─── Live QR Preview (display-only, VietQR CDN URL) ──────────────────────
    let _qrDebounce = null;
    function updateLiveQRPreview() {
        const type = $('input[name="type"]:checked').val();
        if ( type !== 'bank' ) return;

        const bin     = $('#tsbd_bank_bin').val();
        const acctNo  = $('#tsbd_account_no').val().trim();
        const tpl     = $('input[name="vietqr_template"]:checked').val() || 'compact2';
        const name    = $('#tsbd_account_name').val().trim();

        const $img    = $('#tsbd-qr-preview');
        const $ph     = $('#tsbd-qr-preview-placeholder');
        const $badge  = $('#tsbd-qr-bank-badge');
        const $bname  = $('#tsbd-qr-badge-name');
        const $bacct  = $('#tsbd-qr-badge-acct');

        if ( !bin || !acctNo ) {
            $img.hide(); $ph.show(); $badge.hide();
            return;
        }

        // Build VietQR URL (direct CDN, no backend call, not saved to media)
        const nameEnc  = encodeURIComponent( name.toUpperCase() );
        const infoEnc  = encodeURIComponent( $('input[name="default_note"]').val() );
        const amount   = $('input[name="default_amount"]').val() || 0;
        const qrUrl    = `https://img.vietqr.io/image/${encodeURIComponent(bin)}-${encodeURIComponent(acctNo)}-${encodeURIComponent(tpl)}.png?amount=${amount}&addInfo=${infoEnc}&accountName=${nameEnc}`;

        $img.addClass('is-loading');
        const imgObj = new Image();
        imgObj.onload = function () {
            $img.attr('src', qrUrl).removeClass('is-loading').show();
            $ph.hide();
            // Sync same live QR into template preview box
            $('#tsbd-tpl-live-box .tsbd-qr-img').attr('src', qrUrl).show();
            $('#tsbd-tpl-live-box .tsbd-qr-card').show();
            // Update bank badge
            const opt   = $('#tsbd_bank_bin option:selected');
            const short = opt.data('short') || bin;
            const init  = ( short.substr(0,2) ).toUpperCase();
            $('#tsbd-qr-badge-ico').text( init );
            $bname.text( short );
            $bacct.text( acctNo );
            $badge.show();
        };
        imgObj.onerror = function () { $img.removeClass('is-loading'); };
        imgObj.src = qrUrl;
    }

    // Trigger on relevant field changes
    $(document).on('input change', '#tsbd_account_no, #tsbd_account_name, input[name="default_note"], input[name="default_amount"]', function () {
        clearTimeout( _qrDebounce );
        _qrDebounce = setTimeout( updateLiveQRPreview, 600 );
    });
    $(document).on('change', '#tsbd_bank_bin', function () {
        clearTimeout( _qrDebounce );
        _qrDebounce = setTimeout( updateLiveQRPreview, 300 );
    });
    updateLiveQRPreview(); // init on page load

    // ─── Box Template Strip — switch live preview class ───────────────────────
    function switchTplLivePreview( tpl ) {
        // Template class lives on .tsbd-panel (#tsbd-tpl-live-panel), matching frontend structure
        const $panel = $('#tsbd-tpl-live-panel');
        if ( ! $panel.length ) return;
        $panel.removeClass( function( _i, cls ) {
            return ( cls.match(/\btsbd-template-\S+/g) || [] ).join(' ');
        });
        $panel.addClass( 'tsbd-template-' + ( tpl || 'modern' ) );
        // Auto-switch to template tab so user sees the change
        switchPreviewTab('template');
    }

    // ─── Preview tab switcher (QR Code | Template) ────────────────────────────
    function switchPreviewTab( tab ) {
        $('.tsbd-preview-tab').removeClass('is-active');
        $(`.tsbd-preview-tab[data-tab="${tab}"]`).addClass('is-active');
        if ( tab === 'qr' ) {
            $('.tsbd-qr-preview-body').show();
            $('#tsbd-tpl-live-pane').hide();
        } else {
            $('.tsbd-qr-preview-body').hide();
            $('#tsbd-tpl-live-pane').show();
        }
    }
    $(document).on('click', '.tsbd-preview-tab', function () {
        switchPreviewTab( $(this).data('tab') );
    });

    $(document).on('click', '.tsbd-tpl-card', function () {
        const tpl = $(this).data('tpl');
        $('#tsbd_box_template').val( tpl );
        $('.tsbd-tpl-card').removeClass('is-active');
        $(this).addClass('is-active');
        switchTplLivePreview( tpl );
    });
    // Sync cards when select changes
    $(document).on('change', '#tsbd_box_template', function () {
        const val = $(this).val();
        $('.tsbd-tpl-card').removeClass('is-active');
        $(`.tsbd-tpl-card[data-tpl="${val}"]`).addClass('is-active');
        switchTplLivePreview( val );
    });

    // ─── Load Bank List via AJAX ──────────────────────────────────────────────
    function loadBankList() {
        const $select = $('#tsbd_bank_bin');
        if (!$select.length) return;

        $.post(ajax, { action: 'tsbd_get_banks', nonce }, function (res) {
            if (!res.success) return;
            const savedBin = $('#tsbd_bank_saved_bin').val();
            $select.empty().append('<option value="">— Chọn ngân hàng —</option>');
            res.data.forEach(function (bank) {
                const opt = $('<option>')
                    .val(bank.bin)
                    .text(bank.short + ' — ' + bank.name)
                    .attr('data-short', bank.short)
                    .attr('data-logo', bank.logo);
                if (bank.bin === savedBin) opt.prop('selected', true);
                $select.append(opt);
            });
        });
    }
    loadBankList();

    // Update bank_short hidden field on bank select change
    $(document).on('change', '#tsbd_bank_bin', function () {
        const opt = $(this).find(':selected');
        $('#tsbd_bank_short').val(opt.data('short') || '');
    });

    // ─── VietQR template picker ───────────────────────────────────────────────
    // Listen to radio change (clicking a label fires radio change natively — no need to .trigger('change'))
    $(document).on('change', 'input[name="vietqr_template"]', function () {
        $('.tsbd-vqr-opt').removeClass('is-selected');
        $(this).closest('.tsbd-vqr-opt').addClass('is-selected');
        // Trigger live QR preview update
        clearTimeout( _qrDebounce );
        _qrDebounce = setTimeout( updateLiveQRPreview, 200 );
    });

    // ─── Save Account form ────────────────────────────────────────────────────
    $('#tsbd-account-form').on('submit', function (e) {
        e.preventDefault();

        const $btn     = $('#tsbd-save-btn');
        const origText = $btn.text();
        $btn.prop('disabled', true).text(str.generating);
        const $spinner = $('.tsbd-spinner').addClass('is-active');
        const type     = $('input[name="type"]:checked').val() || 'bank';

        // Build formData manually to avoid capturing hidden type-section fields
        const formData = {
            id:              $('input[name="id"]').val().trim(),
            type:            type,
            label:           $('input[name="label"]').val().trim(),
            active:          $('input[name="active"]').is(':checked') ? 1 : 0,
            default_note:    $('input[name="default_note"]').val().trim(),
            default_amount:  $('input[name="default_amount"]').val().trim() || '0',
            box_template:    $('select[name="box_template"]').val(),
        };

        if (type === 'bank') {
            const $bankOpt = $('#tsbd_bank_bin').find(':selected');
            formData.bank_bin      = $('#tsbd_bank_bin').val();
            formData.bank_short    = $('#tsbd_bank_short').val();
            formData.bank_logo     = $bankOpt.data('logo') || '';
            formData.account_no    = $('#tsbd_account_no').val().trim();
            formData.account_name  = $('#tsbd_account_name').val().trim();
            // VietQR template — radio inputs hidden via CSS but checked state valid
            formData.vietqr_template = $('#tsbd-bank-fields input[name="vietqr_template"]:checked').val() || 'compact2';
        } else {
            formData.phone        = $('#tsbd_phone').val().trim();
            formData.account_name = $('#tsbd_momo_name').val().trim();
        }

        $.post(ajax, {
            action:  'tsbd_save_account',
            nonce,
            account: formData,
        }, function (res) {
            if (res.success) {
                showNotice(str.success, 'success');
                if (res.data.qr_url) {
                    $('#tsbd-qr-preview').attr('src', res.data.qr_url + '?v=' + Date.now()).show();
                    $('#tsbd-qr-preview-placeholder').hide();
                }
                // Update URL with new account id without page reload
                if (!formData.id && res.data.id) {
                    $('input[name="id"]').val(res.data.id);
                    history.replaceState(null, '', '?page=tsbd-add&id=' + res.data.id);
                    // Update button text
                    $('#tsbd-save-btn').text('Cập nhật & Tạo QR');
                }
            } else {
                showNotice(res.data || str.error, 'error');
            }
        }).fail(function () {
            showNotice(str.error, 'error');
        }).always(function () {
            $btn.prop('disabled', false).text(origText);
            $spinner.removeClass('is-active');
        });
    });

    // ─── Regenerate QR (list page) ────────────────────────────────────────────
    $(document).on('click', '.tsbd-btn-regen', function () {
        const $btn     = $(this);
        const origHtml = $btn.html(); // Save SVG icon HTML
        $btn.prop('disabled', true).html('<span style="font-size:11px;line-height:1">...</span>');
        const id = $btn.data('id');

        $.post(ajax, { action: 'tsbd_regenerate_qr', nonce, id }, function (res) {
            if (res.success) {
                const $row = $btn.closest('tr');
                // Update QR thumb in table row
                if ( $row.find('img.tsbd-qr-thumb').length ) {
                    $row.find('img.tsbd-qr-thumb').attr('src', res.data.qr_url + '?v=' + Date.now());
                } else {
                    $row.find('td:first').html('<img src="' + res.data.qr_url + '?v=' + Date.now() + '" class="tsbd-qr-thumb" alt="QR">');
                }
                showNotice('QR đã tạo lại thành công!', 'success');
            } else {
                showNotice(str.error, 'error');
            }
        }).always(() => $btn.prop('disabled', false).html(origHtml));
    });

    // ─── Delete Account ───────────────────────────────────────────────────────
    $(document).on('click', '.tsbd-btn-delete', function () {
        if (!confirm(str.confirm_delete)) return;
        const $btn = $(this).prop('disabled', true);
        const id   = $(this).data('id');

        $.post(ajax, { action: 'tsbd_delete_account', nonce, id }, function (res) {
            if (res.success) {
                $btn.closest('tr').fadeOut(300, function () { $(this).remove(); });
            } else {
                showNotice(str.error, 'error');
                $btn.prop('disabled', false);
            }
        });
    });

    // ─── Copy Shortcode (list page) ───────────────────────────────────────────
    $(document).on('click', '.tsbd-sc-copy', function () {
        const sc  = $(this).data('sc');
        const $btn = $(this);
        navigator.clipboard.writeText(sc).then(function () {
            $btn.css('color', '#7C9070');
            setTimeout(() => $btn.css('color', ''), 1500);
            showNotice('Đã copy: ' + sc, 'success');
        }).catch(function () {
            // Fallback for older browsers
            const $tmp = $('<textarea>').val(sc).appendTo('body').select();
            document.execCommand('copy');
            $tmp.remove();
            showNotice('Đã copy shortcode!', 'success');
        });
    });

    $('#tsbd-settings-form').on('submit', function (e) {
        e.preventDefault();
        const $btn     = $(this).find('[type=submit]').prop('disabled', true);
        const $spinner = $('.tsbd-spinner').addClass('is-active');
        const settings = {};

        $(this).serializeArray().forEach(function (f) {
            settings[f.name] = f.value;
        });
        // Checkboxes
        ['show_amount_suggestions', 'allow_custom_amount', 'allow_note_change',
         'load_google_fonts', 'show_footer_credit'].forEach(function (key) {
            settings[key] = $('input[name="' + key + '"]').is(':checked') ? 1 : 0;
        });

        $.post(ajax, {
            action: 'tsbd_save_settings',
            nonce,
            settings,
        }, function (res) {
            showNotice(res.success ? str.success : str.error, res.success ? 'success' : 'error');
        }).always(() => {
            $btn.prop('disabled', false);
            $spinner.removeClass('is-active');
        });
    });

    // ─── Clear cache ──────────────────────────────────────────────────────────
    $('#tsbd-clear-cache').on('click', function () {
        $(this).prop('disabled', true);
        // Simply expire the transient by requesting fresh banks
        $.post(ajax, { action: 'tsbd_get_banks', nonce, force: 1 }, function () {
            showNotice('Cache đã được xoá!', 'success');
        }).always(() => $('#tsbd-clear-cache').prop('disabled', false));
    });

    // ─── Helper ───────────────────────────────────────────────────────────────
    function showNotice(msg, type) {
        const $n = $('#tsbd-notice').removeClass('is-success is-error').addClass('is-' + type).text(msg).show();
        setTimeout(() => $n.fadeOut(), 4000);
    }

}(jQuery));
