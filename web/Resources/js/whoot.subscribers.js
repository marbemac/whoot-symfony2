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

    amplify.subscribe("location_updated", function( data ) {
        $('#collect-info-box').fadeOut(200, function() {
            $(this).remove();
        })
    });

    /*
     * POSTS
     */

    amplify.subscribe("post_created", function( data ) {
        $('#post-box').fadeOut(300);

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
     * VOTING
     */
    // Listens to votes being registered.
    amplify.subscribe("vote_toggle", function( data ) {
        // Turn the scorebox voted button on/off
        $('.sb-'+data.objectId+' .v').toggleClass('on');

        // Update the objects scores
        $('.s-'+data.objectId).text(data.objectNewScore);
    });

    /*
     * COMMENTS
     */
    amplify.subscribe("comment_created", function( data ) {
        $('.comment_new .content').val($('.comment_new .content').data('default'));
        $('.cf-'+data.rootId).append(data.comment);
    });

    /*
     * WORDS
     */
    amplify.subscribe("make_word_trendable", function( data ) {
        $('.w-'+data.wordId).prependTo($('#admin-trendable'));
    });
    amplify.subscribe("make_word_stopword", function( data ) {
        $('.w-'+data.wordId).prependTo($('#admin-stopword'));
    });

    /*
     * INVITES
     */
    amplify.subscribe("invite_cancelled", function( data ) {
        $('#cancel-post').colorbox.remove();

        $('#my-post').replaceWith(data.myPost);
    });
    
    amplify.subscribe("invite_created", function( data ) {
        $('#post-box').fadeOut(300);

        $('#my-post').replaceWith(data.myPost);
    });

    amplify.subscribe('attend_toggle', function ( data ) {
        actionCommon($('.attending_'+data.inviteId), data);
        $('#my-post').replaceWith(data.myPost);
    })

    /*
     * NOTIFICATIONS
     */
    amplify.subscribe('notifications_show', function ( data ) {
        $('#notificationsC').remove();
        $('#my-notification-count').prepend(data.notifications).find('span').removeClass('on').text('0');
    })

    /*
     * LISTS
     */

    // Listens for when the add list button is clicked
    amplify.subscribe("list_form", function( data ) {
        $.colorbox({title:"Create a List!", transition: "none", scrolling: false, opacity: .5, html: data.form });
    });

    // Listens for when a list is created
    amplify.subscribe("list_created", function( data ) {
        $.colorbox.remove();
        $('.my-lists .lists').append('<li>'+data.object+'</li>').find('.none').remove();
    });

    // Listens for when a list is deleted
    amplify.subscribe("list_deleted", function( data ) {
        $('.l_'+data.objectId).remove();
    });

    // Listens for when a list user is deleted
    amplify.subscribe("list_user_deleted", function( data ) {
        $('#uld-'+data.objectId).parent().remove();
    });


});