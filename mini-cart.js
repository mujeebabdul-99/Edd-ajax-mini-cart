jQuery(document).ready(function ($) {
  $(".edd-add-to-cart, .edd-cart").click(function (e) {
    e.preventDefault();
    // console.log('edd-add-to-cart is click!!');

    $.ajax({
      type: "POST",
      url: wpAjax.ajax_url,
      data: {
        action: "update_mini_cart",
      },
      success: function (response) {
        // console.log(response);
        $(".overlay, .loading-progress").hide();
        if (response.success) {
          $(".shopping-cart-items").html(response.data.mini_cart);
          $(".cart-total-price").text(
            "Subtotal: $" + response.data.total_price
          );
          $(".edd-cart-quantity").text(
            "" + response.data.cart_quantity
          );

          // console.log('Response added');
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        $(".loading-progress").hide();
        // console.log('AJAX error:', textStatus, errorThrown);
      },
    });
  });

  // remove item from mini cart

  $(document).on('click', '#remove-cart-item', function (e) {
    e.preventDefault();
    console.log('edd-remove-to-cart is click!!');
    
    var item_id = $(this).data('item-id');
    $(".overlay, .loading-progress").show();

    $.ajax({
      type: "POST",
      url: wpAjax.ajax_url,
      data: {
        action: "bb_remove_from_cart",
        item_id: item_id,
      },
      success: function (response) {
        // console.log(response);

        $(".overlay, .loading-progress").hide();

        if (response.success) {

          $(".shopping-cart-items").html(response.data.mini_cart);

          $(".cart-total-price").text(
            "Subtotal: $" + response.data.total_price
          );
          $(".edd-cart-quantity").text(
            "" + response.data.cart_quantity
          );

          // console.log('Response added');
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        $(".loading-progress").hide();
        // console.log('AJAX error:', textStatus, errorThrown);
      },
    });
  });
  
});