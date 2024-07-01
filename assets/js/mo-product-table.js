jQuery(document).ready(function ($) {
    // Grouped products and default products arrays
    var groupedProducts = [
        [28, 27], // Group 1
        [17, 18, 19],  // Group 2
    ];

    var groupedDefaultProducts = [28, 17];

    // Create a map for quick lookup of group membership
    var productGroupMap = {};
    groupedProducts.forEach(group => {
        group.forEach(productId => {
            productGroupMap[productId] = group;
        });
    });

    // Function to enable/disable buttons based on group logic
    function updateButtonStates() {
        for (var group of groupedProducts) {
            var defaultProduct = group[0];
            var otherProducts = group.slice(1);
            var defaultButton = $('.add-to-cart[data-product-id="' + defaultProduct + '"]');
            var otherButtons = otherProducts.map(id => $('.add-to-cart[data-product-id="' + id + '"]'));

            otherButtons.forEach(button => {
                if (defaultButton.hasClass('added')) {
                    button.prop('disabled', true);
                } else {
                    button.prop('disabled', false);
                }
            });
        }
    }

    // Disable buttons for products already in the cart on page load
    function disableCartButtonsOnLoad() {
        var cartItems = ajax_object.cart_items;
        cartItems.forEach(function (productId) {
            var button = $('.add-to-cart[data-product-id="' + productId + '"]');
            button.prop('disabled', true).addClass('added');
        });
        updateButtonStates();
    }

    disableCartButtonsOnLoad();

    // Handle Select All functionality
    $('#select-all-top, .select-all-product').on('change', function () {
        $('.select-product').prop('checked', $(this).prop('checked'));
    });

    // Handle single product Add to Cart
    $(document).on('click', '.add-to-cart', function () {
        var button = $(this);
        var product_id = button.data('product-id');

        $.ajax({
            type: 'POST',
            url: ajax_object.ajax_url,
            data: {
                action: 'add_to_cart',
                product_id: product_id,
                nonce: ajax_object.nonce
            },
            success: function (response) {
                if (response.success) {
                    alert('Product added to cart successfully!');
                    button.prop('disabled', true).addClass('added');
                    updateButtonStates();
                } else {
                    alert('Failed to add product to cart.');
                }
            }
        });
    });

    // Handle Add to Cart for selected products
    $('.addToCartHolder').on('click', function () {
        var selectedProducts = [];
        var addedProducts = new Set();

        $('.select-product:checked').each(function () {
            var productId = $(this).data('product-id');
            var group = productGroupMap[productId];

            if (group) {
                // If part of a group, only add default product
                var defaultProduct = group[0];
                if (!addedProducts.has(defaultProduct)) {
                    selectedProducts.push(defaultProduct);
                    addedProducts.add(defaultProduct);
                }
            } else {
                // Single product
                if (!addedProducts.has(productId)) {
                    selectedProducts.push(productId);
                    addedProducts.add(productId);
                }
            }
        });

        if (selectedProducts.length > 0) {
            $.ajax({
                type: 'POST',
                url: ajax_object.ajax_url,
                data: {
                    action: 'bulk_add_to_cart',
                    product_ids: selectedProducts,
                    nonce: ajax_object.nonce
                },
                success: function (response) {
                    if (response.success) {
                        alert('Products added to cart successfully!');
                        selectedProducts.forEach(productId => {
                            $('.add-to-cart[data-product-id="' + productId + '"]').prop('disabled', true).addClass('added');
                        });
                        updateButtonStates();
                    } else {
                        alert('Failed to add products to cart.');
                    }
                }
            });
        } else {
            alert('No products selected.');
        }
    });

    // Function to re-enable buttons when items are removed from cart
    function reenableButton(productId) {
        var button = $('.add-to-cart[data-product-id="' + productId + '"]');
        button.prop('disabled', false).removeClass('added');
        updateButtonStates();
    }

    // AJAX handler for removing product from cart
    $(document).on('click', '.remove-from-cart', function () {
        var button = $(this);
        var product_id = button.data('product-id');

        $.ajax({
            type: 'POST',
            url: ajax_object.ajax_url,
            data: {
                action: 'remove_from_cart',
                product_id: product_id,
                nonce: ajax_object.nonce
            },
            success: function (response) {
                if (response.success) {
                    alert('Product removed from cart successfully!');
                    reenableButton(product_id);
                } else {
                    alert('Failed to remove product from cart.');
                }
            }
        });
    });
});

