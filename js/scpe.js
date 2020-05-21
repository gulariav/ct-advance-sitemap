jQuery(document).ready(function(){
    jQuery(".collapsible-faq > div > div ").hide();
    
    jQuery(".collapsible-faq > div > h2 ").click(function(){
        jQuery(this).toggleClass("toggle_active").next().slideToggle("fast");
        return false
    });

    
    jQuery(".faq-data").hide();
    
    jQuery(".faq-btn").click(function() {
        jQuery(this).toggleClass("active").next().slideToggle("fast");
        return false
    })
    
});
