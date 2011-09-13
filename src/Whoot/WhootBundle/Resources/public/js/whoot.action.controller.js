// Wait for Document
$(function(){

    // Perform an action. .ac for POST actions, .acg for GET actions.
    $('.ac, .acg').live('click',function(event){
        event.preventDefault();

        $currentTarget = $(this);

        startAction(
            $(this),
            ($(this).hasClass('ac') ? 'POST' : 'GET'),
            ($(this).attr('href') ? $(this).attr('href') : $(this).data('url')
        ));

        return false;
    });

}); // end onDomLoad