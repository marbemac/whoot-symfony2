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

        if (data.status == 'new')
        {
            $('#my-following').text(parseInt($('#my-following').text()) + 1);
        }
        else
        {
            $('#my-following').text(parseInt($('#my-following').text()) - 1);
        }
    });

    amplify.subscribe("ping_toggle", function( data ) {
        actionCommon($('.ping-'+data.userId), data);

        if (data.status == 'new')
        {
            $('.ping-'+data.userId).after('<span class="ping-countdown ping-countdown-'+data.userId+'" data-until="10"></span>');
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
        $('#post-box').fadeOut(300);

        $('#my-post').replaceWith(data.myPost);
    });

    amplify.subscribe("post_cancelled", function( data ) {
        $('#cancel-post').colorbox.remove();

        $('#my-post').replaceWith(data.myPost);
    });

    amplify.subscribe("jive_toggle", function( data ) {
        $('#my-post').replaceWith(data.myPost);

        if (data.oldPostId)
        {
            $('#post-'+data.oldPostId).parent().fadeOut(500, function() { $(this).remove() });
        }

        $('#post-'+data.postId).parent().fadeTo(250, .01, function() {
            $(this).html(data.post).fadeTo(250, 1);
        })
    });

    /*
     * COMMENTS
     */
    amplify.subscribe("comment_created", function( data ) {
        $('.comment_new .content').val($('.comment_new .content').data('default'));
        $('#post-'+data.postId).next().find('.activity-list').append(data.comment);
    });

});