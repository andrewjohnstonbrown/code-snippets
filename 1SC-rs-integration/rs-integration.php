<script>

    jQuery(document).ready(function() {

        var __rslinks = jQuery("[href='" + <?php echo "\"{$link}\""; ?> + "']");
        console.log("the __rslinks", __rslinks);

        __rslinks.on("click", function(e) {

            e.preventDefault();

            // andrew edited on Tue, June 21, 2016 START

            var _self = jQuery(this);
            console.log("target", _self);

            var _redirect = _self.attr('href');
            console.log("redirect to", _redirect); 

            /* START get current SKU and price from the link */
            __urlParts = _redirect.split('&');

            var curSKU = __urlParts.filter(function(v,i) {

                  if ( v.indexOf('sku=') != -1 ) {
                    console.log(v);
                    return i;
                  }

            })[0];

            curSKU = curSKU.substring("sku=".length)

            var curPrice = __urlParts.filter(function(v,i) {

                  if ( v.indexOf('prodPrice=') != -1 ) {
                    console.log(v);
                    return i;
                  }

            })[0];

            var curPrice = curPrice.substring("prodPrice=".length);
            /* STOP get current SKU and price from the link */

            _rsq.push(['_setAction', 'shopping_cart']); 
            _rsq.push(['_addItem', {'id': curSKU , 'name': curSKU , 'price': curPrice }]);
            _rsq.push(['_track']);

            
            // andrew edited on Tue, June 21, 2016 STOP

            setTimeout(function() {  
                location.assign(_redirect); 
            }, 500);

        });

    });

</script>

