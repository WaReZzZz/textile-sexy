/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$('.closeCookie').on('click', function () {
    $.post("/ajax/validateCookie/", function (data) {
        if (data.cookie === true) {
            $(".cookieValidation").hide();
        }
    });
});

$(document).on('click', '.removeItem', function () {
    addRemoveItem($(this).data('asin'), 0, null, null, null);
});

$(document).on('click', '.buy', function () {
    itemName = $(this).data('name');
    var price, image = '';
    if ($(this).parent('p').siblings('p.label-info.price').children('.amount').text().trim() !== '') {
        price = $(this).parent('p').siblings('p.label-info.price').children('.amount').text().trim();
    } else {
        price = $(this).next('p').find('.amount').text().trim();
    }
    if (typeof $(this).parent('p').parent('div').prev('img').data('small-image') !== 'undefined') {
        image = $(this).parent('p').parent('div').prev('img').data('small-image');
    } else {
        image = $('img[data-small-image]').data('small-image');
    }
    addRemoveItem($(this).data('asin'), 1, price, image, itemName);
});

function addRemoveItem(asin, quantity, amount, image, name) {
    $.ajax({
        url: '/createCart',
        data: {id: asin, quantity: quantity, price: amount, image: image, name: name},
        type: 'POST',
        success: function (data) {
            if (typeof data.amazonCart.Cart.PurchaseURL !== 'undefined' && data.amazonCart.Cart.PurchaseURL.length > 0) {
                $('.dropdown-toggle').parent().addClass('dropdown');
                $('.dropdown-toggle').attr("data-toggle", "dropdown");
                $('.toAmazonCart').attr("href", data.amazonCart.Cart.PurchaseURL);
                dropdownCart = $('.dropdown-cart');
                dropdownCart.html('');
                var countItem = 0, TotalPrice = 0;
                $.each(data.basket, function (key, item) {
                    dropdownCart.append('<li>' +
                        '<span class="item">' +
                        '<span class="item-left">' +
                        '<img src="' + item.image + '" alt="" />' +
                        '<span class="item-info">' +
                        '<span>' + item.name + '</span>' +
                        '<span>' + item.price / 100 + ' €</span>' +
                        '<span>X ' + item.quantity + '</span>' +
                        '</span>' +
                        '</span>' +
                        '<span class="item-right">' +
                        '<button class="btn btn-xs btn-danger pull-right removeItem" data-asin="' + key + '">x</button>' +
                        '</span>' +
                        '</span>' +
                        '</li>');
                    TotalPrice += (item.price / 100) * item.quantity;
                    countItem += parseInt(item.quantity);
                });
                $('.countArticles').html(countItem);
                if (countItem > 0) {
                    dropdownCart.append('<li class="total text-center bg-info text-info text-uppercase">Total: ' + TotalPrice.toFixed(2) + ' €</li>' +
                        '<li class="divider"></li>' +
                        '<li><a class="text-center toAmazonCart" href="' + data.amazonCart.Cart.PurchaseURL + '">Voir le panier</a></li>');
                } else {
                    dropdownCart.parent('li').children('a').removeAttr('data-toggle');
                }
                if (typeof itemName !== 'undefined') {
                    $(".glyphicon-shopping-cart").notify("+1 article à votre panier", {
                        style: 'purple'
                    });
                }
            } else if (typeof data.basket != 'undefined' && data.basket.length === 0) {
                dropdownCart = $('.dropdown-cart');
                dropdownCart.html('');
                dropdownCart.parent('li').children('a').removeAttr('data-toggle');
                $('.countArticles').html(0);
            } else {
                console.log('test');
                /*$('.submitContact').html('');
                 $('.submitContact').attr("disabled", true);
                 $('#addAsset').addClass('hidden');*/
            }
        }
    });
}


$(document).ready(function () {
    slideProductDetails();
    /*$('.variationList').on('change', function(){
     $(this).parent('div').find('.btn-primary').attr('href', '/produit/' + $(this).val() + '/');
     });*/

    $(".owl-carousel-list").owlCarousel({
        margin: 10,
        loop: true,
        autoplay: true,
        dots: true,
        items: 2
    });
    $("#owl-carousel-product").owlCarousel({
        loop: true,
        autoplay: true,
        dots: true,
        center: true,
        autoWidth: true,
        singleItem: true
    });
    $.notify.addStyle('purple', {
        html: "<div><span data-notify-text/></div>",
        classes: {
            base: {
                "white-space": "nowrap",
                "background-color": "#937bb8",
                "padding": "5px",
                "color": "white",
            }
        }
    });
    $.post("/ajax/isCookieValidated/", function (data) {
        if (data.cookie === true) {
            $(".cookieValidation").hide();
        }
    });
    $('#newsletter').modal('show');
    /* smooth scrolling for scroll to top */
    $('.scroll-top').click(function () {
        $('body,html').animate({scrollTop: 0}, 1000);
    });


    $('.registerNewsletter').on('submit', function (e) {
        e.preventDefault();
        var email = $(this).find('[name="email"]').val();
        $.post("/ajax/addNewsletter/", {email: email}, function (data) {
            if (data.success === true) {
                $(this).replaceWith('<p class="bg-success">Merci ! vous recevrez notre newsletter trés prochainement.</p>');
            } else {
                $(this).replaceWith('<p class="bg-danger">Veuiller rééssayer plus tard !</p>');
            }
        });
    });

    /* $('nav').affix({
            offset: {
                top: $('header').height()
            }
        });*/

});

function slideProductDetails() {
    $('.thumbnail').hover(
        function () {
            $(this).find('.caption').slideDown(250); //.fadeIn(250)
        },
        function () {
            $(this).find('.caption').slideUp(250); //.fadeOut(205)
        }
    );
}

function call() {
    slideProductDetails();
}

