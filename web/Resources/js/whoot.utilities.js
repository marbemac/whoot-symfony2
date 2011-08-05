$(function() {

    $('.growfield').livequery(function() {
        $(this).growfield();
    });

    // Sets the cursor to a position in an input
    $.fn.selectRange = function(start, end) {
        return this.each(function() {
            if (this.setSelectionRange) {
                this.focus();
                this.setSelectionRange(start, end);
            } else if (this.createTextRange) {
                var range = this.createTextRange();
                range.collapse(true);
                range.moveEnd('character', end);
                range.moveStart('character', start);
                range.select();
            }
        });
    };

    $('.iclear').live('click', function() {
        if (!$(this).hasClass('cleared') && (!$(this).data('default') || $(this).val() == $(this).data('default')))
        {
            $(this).addClass('active').data('default', $(this).val()).selectRange(0, 0);
        }
    })
    $('.iclear').live('blur', function() {
        if (!$.trim($(this).val()) || $(this).val() == $(this).data('default'))
        {
            $(this).removeClass('active cleared').val($(this).data('default'));
        }
    })
    $('.iclear').live('keydown', function() {
        if ($(this).val() == $(this).data('default'))
        {
            $(this).removeClass('active').val('');
        }
    })
    $('.iclear').live('keyup', function() {
        if (!$.trim($(this).val()))
        {
            $(this).addClass('active').val($(this).data('default')).selectRange(0, 0);
        }
    })

    // Confirmation required. Prompt for confirmation before proceeding.
    $('.cr').live('click', function(e) {
        var $confirm = confirm("Are you sure?");

        if ($confirm)
        {
            return true;
        }

        return false;
    })

})