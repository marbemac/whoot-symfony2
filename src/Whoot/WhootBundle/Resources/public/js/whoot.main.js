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

    /**
     * USERS
     */

    
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
        },
        position: {
            my: 'top center',
            at: 'bottom center'
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
        $(this).addClass('on').siblings('.type').removeClass('on');
    })

    // Scroll to my post
    $('#my-post').live('click', function(ev) {
        if ($(ev.target).attr('id') == 'change-post')
            return;
        
        location.href = $(this).data('target');
        $($(this).data('target')).click();
    })

    // Submit a new post
    $('#post-box .submit').live('click', function() {
        var $payload = {};
        $payload['type'] = $('#post-box .type.on').data('val');
        $payload['note'] = $('#post-description').val();

        var $error_flag = false;

        if (!$payload['type'])
        {
            $error_flag = true;
            $('#post-box .status').css('color', 'red');
        }

        if ($('#post-box .open-invite-toggle').hasClass('on'))
        {
            $payload['venue'] = $('#post-venue').val();
            $payload['address'] = $('#post-address').val();
            $payload['address_lat'] = $('#post-address-lat').val();
            $payload['address_lon'] = $('#post-address-lon').val();
            $payload['time'] = $('#post-time').val();

            $('.invite-req, #post-address').each(function(index) {
                $(this).prev().css('color', '#000');
                if (!$(this).val() || $(this).val() == 'Enter a location')
                {
                    $error_flag = true;
                    $(this).prev().css('color', 'red');
                }
            })
        }

        if ($error_flag)
            return false;

        $.post($(this).data('url'), $payload, function(data) {
            appUpdate(data);
        }, 'json');
    })

    // Cancel a change post
    $('#post-box .cancel').live('click', function() {
        $('#post-box').fadeOut(300);
    })

    // Toggle the activity of a post
    $('#post-feed li').live('click', function(ev) {
        if ($(ev.target).is('a'))
            return;

        var $self = $(this);
        $self.find('.teaser.post').toggleClass('on');
    })

    // Toggle the + Open Invite in the post box
    $('.open-invite-toggle').live('click', function() {
        var $self = $(this);
        if ($self.hasClass('on'))
        {
            $self.removeClass('on').text('+ Open Invite').siblings('textarea').prev().text('Optional Note');
        }
        else
        {
            $self.addClass('on').text('- Open Invite').siblings('textarea').prev().text('Invite Description');
        }

        $('.open-invite-C').toggle();
    })

    // Show the post-where places autocomplete
    $('#post-address').livequery(function() {
        var $self = $(this);
        var bounds = new google.maps.LatLngBounds(
              new google.maps.LatLng($self.data('lat'), $self.data('lon')),
              new google.maps.LatLng($self.data('lat'), $self.data('lon')));
        var $auto = new google.maps.places.Autocomplete(document.getElementById('post-address'), {bounds: bounds});

        // Handle a place choice
        google.maps.event.addListener($auto, 'place_changed', function() {
            var place = $auto.getPlace();
            $('#post-address-name').val(place.formatted_address);
            $('#post-address-lat').val(place.geometry.location.Ia);
            $('#post-address-lon').val(place.geometry.location.Ja);
        })
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