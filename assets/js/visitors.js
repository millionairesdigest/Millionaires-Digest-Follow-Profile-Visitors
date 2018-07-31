jQuery(document).ready(function(){

    var jq = jQuery;

    if ( typeof jq.fn.lightSlider !== 'undefined' ) {
        jq('.most-visited-users-slide').each(function(){
            var $this = jq(this);
            $this.lightSlider();
        });
    }
})
