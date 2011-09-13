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

        $.ajax({
            type: 'POST',
            url: form.attr('action'),
            data: form.serializeArray(),
            dataType: 'json',
            beforeSend: function()
            {
                console.log('Form submit');
                form.find('input, textarea').attr('disabled', true);
                $('#form-submitting').fadeIn(300);
            },
            success: function(data)
            {
                $('#form-submitting').fadeOut(300);
                form.find('input, textarea').removeAttr('disabled');
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
                $('#form-submitting').fadeOut(300);
                form.find('input, textarea').removeAttr('disabled');
                if (error)
                {
                    error();
                }
            }
        });

    }; // end onStateChange

}); // end onDomLoad