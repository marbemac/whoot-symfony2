$(function() {

    $('.growfield').livequery(function() {
        $(this).growfield();
    });

    var inputClear = function(target)
    {
        if (target.hasClass('cleared') && !$.trim(target.val()))
        {
            target.val(target.data('default')).data('default', '').removeClass('cleared');
        }
        else if (!target.data('default'))
        {
            target.data('default', target.val()).val('').addClass('cleared');
        }
    }
    $('.iclear').live('click', function() {
        inputClear($(this));
    })
    $('.iclear').live('blur', function() {
        inputClear($(this));
    })

})