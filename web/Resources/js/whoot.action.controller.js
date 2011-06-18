// Wait for Document
$(function(){

    // Perform an action. .ac for POST actions, .acg for GET actions.
    $('.ac, .acg').live('click',function(event){
        // Ajaxify this link
        var $this = $(this),
            url = $this.attr('href') ? $this.attr('href') : $this.data('url'),
            requestType = $this.hasClass('ac') ? 'POST' : 'GET';

        doAction({'url': url, 'requestType': requestType}, null, null);
        event.preventDefault();

        return false;
    });

}); // end onDomLoad