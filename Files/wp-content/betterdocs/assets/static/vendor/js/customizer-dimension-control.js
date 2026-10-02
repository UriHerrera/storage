( function( $ ) {
    'use strict';
    $(document).ready(function () {
        $(".customize-control-betterdocs-multi-dimension").each(function() {
            let dimension_control = $(this).find('.betterdocs-dimension-control');
            let dinput1 = $(this).find('.betterdocs-dimension-input-1');
            let dinput2 = $(this).find('.betterdocs-dimension-input-2');
            let dinput3 = $(this).find('.betterdocs-dimension-input-3');
            let dinput4 = $(this).find('.betterdocs-dimension-input-4');
            let dimension_result = {
                input1: dinput1.val(),
                input2: dinput2.val(),
                input3: dinput3.val(),
                input4: dinput4.val()
            };

            dimension_control.val(JSON.stringify(dimension_result)).change();
            
            // Disconnected button
            $( '.betterdocs-dimension-disconnected' ).on( 'click', function() {

                // Set up variables
                var elements 	= $(this).data( 'element-connect' );
                
                // Add connected class
                $(this).parent().parent( '.betterdocs-dimension-fields' ).find( 'input' ).addClass( 'connected' ).removeClass( 'disconnected' ).attr( 'data-element-connect', elements );

                // Add class
                $(this).parent( '.betterdocs-dimension-link' ).addClass( 'connected' );

            });

            // Connected button
            $( '.betterdocs-dimension-connected' ).on( 'click', function() {
                // Remove connected class
                $(this).parent().parent( '.betterdocs-dimension-fields' ).find( 'input' ).removeClass( 'connected' ).addClass( 'disconnected' ).attr( 'data-element-connect', '' );
                
                // Remove class
                $(this).parent( '.betterdocs-dimension-link' ).removeClass( 'connected' );

            } );

            // Values connected inputs
            $(document).on( 'input', ".betterdocs-dimension-input.connected", function() {
                
                var dataElement 	  = $(this).attr( 'data-element-connect' );
                var currentFieldValue = $(this).val();
                let dimension_control = $(this).parents('.customize-control-betterdocs-multi-dimension').find('.betterdocs-dimension-control');
                let dimension_result  = JSON.parse(dimension_control.val());

                $(this).parent().parent('.betterdocs-dimension-fields').find('.connected[ data-element-connect="' + dataElement + '" ]').each(function(key, value) {
                    $(this).val(currentFieldValue).change();
                    let eachDataInput = $(this).attr('data-input');
                    dimension_result[eachDataInput] = ( $(this).val() === undefined ||  $(this).val() === '' )  ? '0' : $(this).val();
                    dimension_control.val(JSON.stringify(dimension_result)).change();
                });
            });

            $(document).on( 'input', ".betterdocs-dimension-input.disconnected", function() {
                let dimension_control = $(this).parents('.customize-control-betterdocs-multi-dimension').find('.betterdocs-dimension-control');
                
                let dimension_result = JSON.parse(dimension_control.val());
                let dataInput = $(this).attr('data-input');
                dimension_result[dataInput] = ( $(this).val() === undefined ||  $(this).val() === '' )  ? '0' : $(this).val();
                dimension_control.val(JSON.stringify(dimension_result)).change();
            });

        });

        // Get the default values for Multidimensional Controllers
        var multiDimensional_controller_list = $(".customize-control-betterdocs-multi-dimension");

        var default_values = {};

        for( var controller of multiDimensional_controller_list ) {
            default_values[$(controller).attr('id')] = $(controller).children('input').val();
        }

        // MultiDimension Reset Controller
        $('.betterdocs-customizer-reset.betterdocs-multi-dimension').on('click', function(e){
            var current_default_values = default_values[$(this).parent().parent().attr('id')];
            var input1 = JSON.parse( default_values[$(this).parent().parent().attr('id')] )['input1'];
            var input2 = JSON.parse( default_values[$(this).parent().parent().attr('id')] )['input2'];
            var input3 = JSON.parse( default_values[$(this).parent().parent().attr('id')] )['input3'];
            var input4 = JSON.parse( default_values[$(this).parent().parent().attr('id')] )['input4'];
            $(this).parent().next().next().val(current_default_values).change();
            $(this).parent().next().next().next().find('.betterdocs-dimension-input-1').val(input1);
            $(this).parent().next().next().next().find('.betterdocs-dimension-input-2').val(input2);
            $(this).parent().next().next().next().find('.betterdocs-dimension-input-3').val(input3);
            $(this).parent().next().next().next().find('.betterdocs-dimension-input-4').val(input4);
        });
    });
})(jQuery);