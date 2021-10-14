Product.OptionsPrice.prototype.formatPrice = function(price) {
    let adPayment = jQuery('anyday-price-tag');
    if (adPayment.length) {
        if (parseFloat(adPayment.attr('total-price')) != parseFloat(price)) {
            adPayment.attr('total-price', price);
        }
    }
    return formatCurrency(price, this.priceFormat);
}
