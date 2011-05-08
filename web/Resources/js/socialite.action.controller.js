// Wait for Document
$(function(){

    $('a.ac').live('click',function(event){
        // Ajaxify this link
        var $this = $(this),
            url = $this.attr('href');

        $currentTarget = $(this);
        doAction({'url':url}, null, null);
        event.preventDefault();

        return false;
    });

    /*
     * Perform an action.
     */
    var doAction = function(params, success, error) {

        console.log('Action:' + params.url);

        amplify.request( "doAction", { 'url': params.url }, function ( data ) {
            appUpdate(data);
            if (success)
            {
                success({'url': params.url}, data);
            }
        })

    }; // end onStateChange

}); // end onDomLoad