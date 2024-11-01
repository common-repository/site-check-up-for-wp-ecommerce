String.prototype.nl2br = function () {
    return this.replace(/\n/g, "<br />");
};

var ajaxurl = jQuery("#ajaxurl").attr('value');

var timing_offset = 0;
var debug_offset = 0;
var query_offset = 0;
var other_offset = 0;

var updating_content = 0;

function get_info() {
    jQuery.ajax(
        {
            url: snappy.ajaxurl,
            data: {
                action: 'info',
                security: snappy.security,
                dir: snappy.dir,
                timing_offset: timing_offset,
                debug_offset: debug_offset,
                query_offset: query_offset,
                other_offset: other_offset
            },
            dataType: 'json',
            type: 'POST',
            cache: false,
            success: get_info_result,
            error: get_info_error
        });
}

function get_info_error(jqXHR, textStatus, errorThrown) {
}

var updating_content = 0;

function get_info_result(files) {
    updating_content++;
    var ctrl;
    var elem;
    var response;

    if (files.hasOwnProperty('timing')) {
        response = files.timing;
        if (response.count > 0) {
            ctrl = jQuery("#timing");
            ctrl.append(response.buffer.nl2br());
            timing_offset = response.current;
            elem = document.getElementById("timing");
            ctrl.animate({scrollTop: elem.scrollHeight}, "slow");
        }
    }

    if (files.hasOwnProperty('debug')) {
        response = files.debug;
        if (response.count > 0) {
            ctrl = jQuery("#debug");
            ctrl.append(response.buffer.nl2br());
            debug_offset = response.current;
            elem = document.getElementById('debug');
            ctrl.animate({scrollTop: elem.scrollHeight}, "slow");
        }
    }

    if (files.hasOwnProperty('query')) {
        response = files.debug;
        if (response.count > 0) {
            ctrl = jQuery("#query");
            ctrl.append(response.buffer.nl2br());
            query_offset = response.current;
            elem = document.getElementById('query');
            ctrl.animate({scrollTop: elem.scrollHeight}, "slow");
        }
    }

    if (files.hasOwnProperty('other')) {
        response = files.other;
        if (response.count > 0) {
            ctrl = jQuery("#other");
            ctrl.append(response.buffer.nl2br());
            other_offset = response.current;
            elem = document.getElementById('other');
            ctrl.animate({scrollTop: elem.scrollHeight}, "slow");
        }
    }
    updating_content--;
}

function infoRefreshTimer() {
    if (jQuery('#pause').prop('checked')) {
        return;
    }

    get_info();
}


var test_index = 0;
var test_index_max = 100;
var random_string = '';

var test_result = true;

jQuery(document).ready(function ($) {

    $action_name = 'wp_ajax_snappy_cache_test_'.$i;

    function test_ajax_complete(result) {
        var returned_string = '';

        if ( result.hasOwnProperty( 'data' ) ) {
            if (result.data.hasOwnProperty('value_from_cache')) {
                returned_string = result.data.value_from_cache;
            }
        } else {
            console.log( 'odd return value?' );
        }

        if ( returned_string != random_string ) {
            $("#object_cache_test_result").html('OBJECT CACHE TEST FAILED! Returned random value.');
            $("#object_cache_test_result").css( "color", "red" );
            test_result = false;
        } else if (returned_string == "FALSE" ) {
            $("#object_cache_test_result").html('OBJECT CACHE TEST FAILED! Not enabled.');
            $("#object_cache_test_result").css( "color", "red" );
            test_result = false;
        }

        if ( test_index >= test_index_max || ! test_result ) {

            if ( test_result ) {
                $("#object_cache_test_result").html('OBJECT CACHE TEST PASSED!');
                $("#object_cache_test_result").css( "color", "green" );
            } else {
                $("#object_cache_test_result").html('OBJECT CACHE TEST FAILED! Returned random value.');
                $("#object_cache_test_result").css("color", "red");
            }

        } else {
            var action_name = 'snappy_cache_test_' + test_index;
            var ajax_data = {action: action_name, test_index: test_index};
            $("#object_cache_test_result").html('Test ' + test_index + ' of ' + test_index_max );
            if ( isOdd( test_index ) ) {
                $("#object_cache_test_result").css( "color", "darkgray" );
            } else {
                if ( test_result ) {
                    $("#object_cache_test_result").css("color", "darkgreen");
                } else {
                    $("#object_cache_test_result").css("color", "darkred");
                }
            }
            snappy_do_ajax(ajax_data, test_ajax_complete, test_ajax_fail);
            test_index++;
        }
    }

    function isOdd(n) {
        return (Math.abs(n) % 2 == 1);
    }
    function test_ajax_fail(result) {
        var action_name = 'wp_ajax_snappy_cache_test_'.test_index;
        var ajax_data = {action: action_name, test_index: test_index};
        snappy_do_ajax(ajax_data, test_ajax_complete, test_ajax_fail);
    }

    jQuery("#object_cache_test").click(function () {
        random_string = randomString(20);
        test_result = true;
        var ajax_data = {action: 'snappy_cache_test', set_this_value: random_string};
        snappy_do_ajax(ajax_data, test_ajax_complete, test_ajax_fail);
        return false;
    });

});


function snappy_do_ajax(data, success_callback, error_callback) {
    jQuery.ajax({
        type: "post",
        dataType: "json",
        url: snappy.adminajax,
        data: data,
        success: success_callback,
        error: error_callback,
        timeout: 30000
    });
}

function randomString(length) {
    var result = '';
    var chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    for (var i = length; i > 0; --i) result += chars[Math.round(Math.random() * (chars.length - 1))];
    return result;
}


jQuery(document).ready( function ($) {

    jQuery( ".url-check-result.url-ready-to-check" ).each( function() {
        var url = jQuery( this ).data( "url" );

        jQuery( this).removeClass( "url-ready-to-check" );
        jQuery( this ).addClass( "url-check-in-progress" );
        jQuery( this).text( "URL check in progress" );

        jQuery.ajax({
            type: "post",
            dataType: "json",
            url: snappy.adminajax,
            data: { action: "snappy_get_url", url: url, security: snappy.security },
            start: jQuery.now(),

            success: function ( result ) {
                var data = result.data;
                var code = data.code;
                var message = data.message;
                var url = data.url;
                var elapsed =  data.elapsed;

                var status = jQuery("[data-url='" + url + "']");
                elapsed = elapsed.toFixed(1);

                if ( code == 200 ) {
                    status.removeClass("url-check-in-progress");
                    status.addClass("url-check-ok");
                    status.text("URL is OK " + elapsed + ' seconds');
                } else {
                    status.removeClass("url-check-in-progress");
                    status.addClass("url-check-failed");
                    status.text("URL check failed " + message);
                }
            },

            error:  function ( data ) {
                var url = this.data.url;
                var status = jQuery("[data-url='" + url + "']");
                status.removeClass("url-check-in-progress");
                status.addClass("url-check-failed");
                status.text("URL check failed (ajax timeout)" );
            },
            timeout: 20000
        });
    });
});

