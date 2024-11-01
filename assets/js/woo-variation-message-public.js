jQuery(document).ready(function() {
  
  jQuery( '.variations_form' ).on( 'woocommerce_variation_select_change', function () {

    jQuery('.variations_form').find('select').each(function () {
      if (this.value == "") {
        jQuery('.woocommerce_variation_messages').children().slideUp();
      }
    });
  });
  
  jQuery( '.single_variation_wrap' ).on( 'show_variation', function ( event, variation ) {
  // Fired when the user selects all the required dropdowns / attributes
  // and a final variation is selected / shown
  jQuery.each( woocommerce_variation_message, function(id) {
    if ( JSON.parse(this)[variation.variation_id] ) {
      jQuery('.woocommerce_variation_message_' + id ).slideDown();
    }
    else {
      jQuery('.woocommerce_variation_message_' + id ).slideUp();
    }
    });
  });
  
});