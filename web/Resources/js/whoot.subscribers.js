// Wait for Document
$(function(){

    var actionCommon = function(target, data) {
        if (data.newText)
        {
            target.text(data.newText);
        }

        if (data.newUrl)
        {
            target.attr('href', data.newUrl);
        }
    }

    var deleteCommon = function (data) {
        $('.o_'+data.objectId).remove();
    }

    /*
     * USERS
     */

    amplify.subscribe("follow_toggle", function( data ) {
        actionCommon($('.fol_'+data.userId), data);
    });

    amplify.subscribe("ping_toggle", function( data ) {
        actionCommon($('.ping-'+data.userId), data);

        if (data.status == 'new')
        {
            $('.ping-'+data.userId).after('<span class="ping-countdown ping-countdown-'+data.userId+'" data-until="59"></span>');
        }
        else if (data.status == 'deleted')
        {
            $('.ping-countdown-'+data.userId).remove();
        }
    });

    /*
     * POSTS
     */

    amplify.subscribe("post_created", function( data ) {
        $('#post-box').fadeOut(500);

        $('#my-post').replaceWith(data.myPost);
    });

});