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

    $('#change-post').live('click', function() {
        $('#post-box').fadeIn(500);
    })

    // Use canvas to draw the post timers
    var $postColors = {'working': '#009966', 'low_in': '#996699', 'low_out': '#FF9900', 'big_out': '#CC3300'};
    $('.post.teaser .timer').livequery(function() {
        var $oldestPost = $('#oldestPost').data('time');
        var canvas = this;

        // Make sure we don't execute when canvas isn't supported
        if (canvas.getContext)
        {
            var $postType = $(canvas).data('type');
            // Get this posts time
            var $postTime = $(canvas).data('time');
            // Calculate it's time relative to all other posts
            var $ratio,
                $degrees;

            if ($postTime == $oldestPost)
            {
                $ratio = 1;
                $degrees = 270;
            }
            else
            {
                // Get the current time in seconds since the Unix epoch
                var $time = new Date;
                $time = $time.getTime();
                $time = parseInt($time / 1000);
                $ratio = ($time - $postTime) / ($time-$oldestPost);
                $degrees = ($ratio*270-90);
            }

            ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            ctx.beginPath();
            ctx.strokeStyle = $postColors[$postType];
            ctx.arc(canvas.width/2, canvas.height/2, 9, 0, (360*(Math.PI/180)), true);
            ctx.stroke();

            // Draw the timer fill
            if ($ratio > 0)
            {
                ctx.beginPath();
                ctx.fillStyle = $postColors[$postType];
                ctx.moveTo(canvas.width/2, canvas.height/2);
                ctx.arc(canvas.width/2, canvas.height/2, 9, -Math.PI/2, $degrees*(Math.PI/180), false);
                ctx.lineTo(canvas.width/2, canvas.height/2);
                ctx.fill();
            }
        }
        else
        {

        }
    })

    // Add qTips to the post timers
    $('.post.teaser .timer').livequery(function() {
        var $self = $(this);
        $self.qtip({
            content: {
                attr: 'title'
            },
            style: {
                classes: 'ui-tooltip-blue ui-tooltip-shadow ui-tooltip-rounded'
            }
        })
    })

    // Add qTips to the feed post stats
    $('#feed-stats div').qtip({
        content: {
            attr: 'title'
        },
        style: {
            classes: 'ui-tooltip-blue ui-tooltip-shadow ui-tooltip-rounded my-pings-tip'
        }
    })

    // Add qTips to the post timers
    $('#my-pings').qtip({
        content: {
            attr: 'title'
        },
        style: {
            classes: 'ui-tooltip-blue ui-tooltip-shadow ui-tooltip-rounded my-pings-tip'
        },
        position: {
            my: 'right center',
            at: 'left center'
        }
    })

    // Toggle new post options
    $('#post-box .type').live('click', function() {
        $(this).addClass('on').siblings().removeClass('on');
    })

    // Submit a new post
    $('#post-box .submit').live('click', function() {
        var $payload = {};
        $payload['type'] = $('#post-box .type.on').data('val');
        $payload['note'] = $('#post-box .note').val();

        if (!$payload['type'])
        {
            $('#post-box .status').css('color', 'red').text('Status - You must pick a status!');

            return false;
        }

        $.post($(this).data('url'), $payload, function(data) {
            appUpdate(data);
        }, 'json');
    })

    // Toggle the activity of a post
    $('#post-feed li').live('click', function(ev) {
        if ($(ev.target).is('a'))
            return;

        var $self = $(this);
        $self.find('.teaser.post').toggleClass('on');
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
                $self.prev().replaceWith('<span class="pinged">Pinged</span>');
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