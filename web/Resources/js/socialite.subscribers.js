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

    /*
     * POSTS
     */

    amplify.subscribe("post_created", function( data ) {
        $('#post-box').fadeOut(500);

        $('#my-post').remove();
        $('#post-feed').prepend(data.myPost);
    });

    amplify.subscribe("invite_request_toggle", function( data ) {
        if (data.status == 'Check')
        {
            alert('You already have a pending invite request today. Cancel that one first. (This will be replace with an option to do that automatically).');
            exit();
        }
        actionCommon($('.ir_'+data.postId), data);
    });

});