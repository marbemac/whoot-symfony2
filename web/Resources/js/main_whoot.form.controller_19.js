// Wait for Document
$(function(){

    $('form:not(.noajax)').live('submit',function(event){
        // Ajaxify this form

        event.preventDefault();
        $currentTarget = $(this);
        formSubmit($(this), null, null);
        
        return false;
    });

    /*
     * Submit and handle a form..
     */
    var formSubmit = function(form, success, error) {

        console.log('Form submit');

        $.ajax({
            type: 'POST',
            url: form.attr('action'),
            data: form.serializeArray(),
            dataType: 'json',
            success: function(data)
            {
                if(appUpdate(data))
                {
                    if (data.result == 'error')
                    {
                        form.replaceWith(data.form);
                    }
                }


                if (success)
                {
                    success();
                }
            },
            error: function()
            {
                if (error)
                {
                    error();
                }
            }
        });

    }; // end onStateChange

}); // end onDomLoad