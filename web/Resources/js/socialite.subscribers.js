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
     * POSTS
     */

    amplify.subscribe("post_created", function( data ) {
        $('#post-box').fadeOut(500);
        
        $('#my-post').remove();
        $('#post-feed').prepend(data.myPost);
    });

});