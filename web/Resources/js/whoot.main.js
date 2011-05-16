$(function() {

    /*
     * LOGIN/REGISTRATION
     */

    $('#login,#register').colorbox({title:"Woops, you need to login to do that!", transition: "none", opacity: .5, inline: true, href: "#auth_box"});

    /*
     * CONTRIBUTE
     */

    $('#contribute').colorbox({title:"Give us some shit!", transition: "none", opacity: .5, href: function() {return $(this).attr('href') + '.xml'}});

    /*
     * SPLASH PAGE
     */
    $('#splash .button').live('click', function() {
        var $self = $(this);
        $self.parents('.panel').fadeOut(300, function() {
            $($self.data('target')).fadeIn(300);
        })
    })
    
    /*
     * POSTS
     */
    $('#declare-post').livequery(function() {
        $('#post-box').fadeIn(500);
    })
    $('#my-post').live('click', function() {
        $('#post-box').fadeIn(500);
    })

    /*
     * PINGS
     */
    $('.ping-countdown').livequery(function() {
        var $self = $(this);
        $self.countdown({
            layout: '{sn}',
            until: '+'+$self.data('until')+'s',
            onExpiry: function() {
                console.log('test');
                $self.before().remove();
                $self.remove();
            }
        });
    })

    /*
     * MENUS
     */

    // Single choice menu.
    $('.sc-menu').live({
        mouseenter: function() {
            $(this).find('li').show();
        },
        mouseleave: function() {
            $(this).find('li:not(.on)').hide();
        }
    })
    $('.sc-menu a').live('click', function() {
        $(this).parent().addClass('on').siblings().removeClass('on');
    })

    // Multiple choice menu
    $('.mc-menu a').live('click', function() {
        $(this).parent().toggleClass('on');
    })
})