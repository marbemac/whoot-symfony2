$(function() {

    /*
     * LOGIN/REGISTRATION
     */

    $('#login,#register').colorbox({title:"Woops, you need to login to do that!", transition: "none", opacity: .5, inline: true, href: "#auth_box"});

    /*
     * CONTRIBUTE
     */

    $('#contribute').colorbox({title:"Give us some shit!", transition: "none", opacity: .5, href: function() {
        return $(this).attr('href') + '.xml'
    }});

    /*
     * SPLASH PAGE
     */

    // show the hidden login form on ctrl+L
    $(window).keypress(function(e) {
        var $code = e.which ? e.which : e.keyCode;
        if (e.ctrlKey && $code == 108)
        {
            $('#auth-login').fadeToggle(300);
        }

    })

    /**
     * USERS
     */

    // Enabling dragging of user tags.
    $(".user-link").livequery(function() {
        $(this).draggable({
            opacity: 0.7,
            helper: "clone",
            handle: ".drag-handleC",
            appendTo: "body"
        });
    });
    // Enabling dropping of user tags.
    $('.my-lists .teaser').livequery(function() {
        $(this).droppable({
            accept:      ".user-link",
            activeClass: "dragging",
            hoverClass:  "dropping",
            drop: function(event, ui) {

                // Get the base follow_add url and add the target users ID to the end.
                var $url = $(this).data('d').url;
                var $payload = {};
                $payload['userId'] = ui.draggable.data('d').id;
                $payload['feedReload'] = 'no';

                doAction({'url': $url, 'requestType': 'POST', 'payload': $payload}, null, null);
            }
        })
    });

    $('.user-link').livequery(function() {
        $(this).each(function() {
            var $self = $(this);
            $self.qtip({
                content: {
                    text: 'Loading...',
                    ajax: {
                        once: true,
                        url: $self.data("d").tab,
                        type: 'get',
                        success: function(data) {
                            $('.user-'+$self.data('d').id).qtip('option', {
                                'content.text': data,
                                'content.ajax': false
                            });
                        }
                    }
                },
                style: {classes: 'ui-tooltip-shadow ui-tooltip-light', tip: true},
                position: {
                    my: 'bottom center',
                    at: 'top center',
                    viewport: $(window)
                },
                show: {delay: 400},
                hide: {delay: 300, fixed: true}
            })
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

    // Show confirm button for cancel open invite post
    $('#cancel-post').livequery(function() {
        $(this).colorbox({title:"Are you sure you want to cancel your open invite?", transition: "none", opacity: .5, inline: true, href: "#invite-cancel-confirm"});
    })

    // Toggle collapse right undecided bar
    $('.undecidedC .side-toggle, #feed-filters .undecided').live('click', function() {
        $('.undecidedC').toggleClass('collapsed');
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

    // Toggle new post options
    $('#post-box .type').live('click', function() {
        $(this).addClass('on').siblings('.type').removeClass('on');
        $('#whoot_post_form_type').val($(this).data('val'));
    })

    // Scroll to my post
    $('#my-post').live('click', function(ev) {
        if ($(ev.target).attr('id') == 'change-post' || $(ev.target).attr('id') == 'cancel-post')
            return;

        var $self = $(this);
        $.scrollTo($self.data('target'), {
            duration: 500,
            onAfter: function() {
                $($self.data('target')).click();
            }
        })
    })

    // Submit a new post
    var postSubmit = false;
    $('.post_create .submit').live('click', function() {
        if (postSubmit)
            return;

        postSubmit = true;
        var $self = $(this);
        var $error_flag = false;

        $('#post-box .status').removeClass('error');
        $self.text('Working...');

        var $payload = {};
        $payload[$('#whoot_post_form_type').attr('name')] = $('#whoot_post_form_type').val();
        $wordCount = 0;
        $.each($('input.word'), function() {
            if ($.trim($(this).val()).length > 0)
            {
                $wordCount++;
            }
            $payload[$(this).attr('name')] = $(this).val();
        })

        if ($.inArray($payload[$('#whoot_post_form_type').attr('name')], ['working', 'low_in', 'low_out', 'big_out']) == -1) {
            $error_flag = true;
            $('#post-box .status').addClass('error');
        }

        if ($wordCount == 0)
        {
            $error_flag = true;
            $('.post_create .errors').text('You must input 1 - 5 words! Be sure to press enter after inputting them.');
        }

//        if ($('#post-box .open-invite-toggle').hasClass('on')) {
//            $payload['venue'] = $('#post-venue').val();
//            $payload['address'] = $('#post-address').val();
//            $payload['address_lat'] = $('#post-address-lat').val();
//            $payload['address_lon'] = $('#post-address-lon').val();
//            $payload['time'] = $('#post-time').val();
//
//            $('.invite-req, #post-address').each(function(index) {
//                $(this).prev().css('color', '#000');
//                if (!$(this).val() || $(this).val() == 'Enter a location') {
//                    $error_flag = true;
//                    $(this).prev().css('color', 'red');
//                }
//            })
//        }

        if ($error_flag) {
            $self.text('Submit Post');
            postSubmit = false;
            return false;
        }

        $.post($('.post_create').attr('action'), $payload, function(data) {
            appUpdate(data);
            $self.text('Submit Post');
            postSubmit = false;
        }, 'json');
    })

    // Cancel a change post
    $('#post-box .cancel').live('click', function() {
        $('#post-box').fadeOut(300);
    })

    // Cancel a open invite cancellation
    $('#invite-cancel-confirm .cancel').live('click', function() {
        $('#invite-cancel-confirm').colorbox.close();
    })

    // Toggle the activity of a post
    var postActivityToggle = false;
    $('.teaser.post').live('click', function(ev) {
        if ($(ev.target).is('a') || $(ev.target).hasClass('word') || postActivityToggle)
            return;

        postActivityToggle = true;
        var $self = $(this);
        if ($self.next().hasClass('post-details')) {
            $self.toggleClass('on').next().toggle();
            postActivityToggle = false;

            return;
        }

        $.get($self.data('details'), {}, function(data) {
                    $self.after(data.details).toggleClass('on');
                    postActivityToggle = false;
                }, 'json')
    })

    // Toggle the + Open Invite in the post box
    $('.open-invite-toggle').live('click', function() {
        var $self = $(this);
        if ($self.hasClass('on')) {
            $self.removeClass('on').text('+ Open Invite').siblings('textarea').prev().text('Optional Note');
        }
        else {
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
            $('#post-address-lat').val(place.geometry.location.Ha);
            $('#post-address-lon').val(place.geometry.location.Ia);
        })
    })

    // Draw on post maps
    $('.post-map').livequery(function() {
        var $self = $(this);

        var latlng = new google.maps.LatLng($self.data('lat'), $self.data('lon'));

        var myOptions = {
            zoom: 16,
            center: latlng,
            disableDefaultUI: true,
            scaleControls: true,
            mapTypeId: google.maps.MapTypeId.ROADMAP
        };

        var map = new google.maps.Map(document.getElementById($self.attr('id')),
                myOptions);

        var marker = new google.maps.Marker({
            position: latlng,
            map: map,
            title: $self.data('name')
        });
    })

    /*
     * COMMENTS
     */
    $('.comment_new .content').live('keypress', function(e) {
        if (e.keyCode == 13) {
            e.preventDefault();
            $(this).siblings('input[type="submit"]').click();
        }
    })

    /*
     * WORDS
     */

    // Handle post words
    // How many words are we allowed?
    var $wordMax = 5;
    // How many characters per word (avg)
    var $characterMax = 10;
    $('.post_create .words').live('keypress', function(e) {
        var $code = e.which ? e.which : e.keyCode;
        $(this).siblings('.errors').text('');

        // Get the number of words the user is trying to add
        $wordNum = $.trim($(this).val()).split(' ').length;

        // 13 enter keypress
        if ($code == 13)
        {
            // Calculate how many words the user has already added
            $currentWords = '';
            $.each($('input.word'), function() {
                if ($.trim($(this).val()).length > 0)
                {
                    $currentWords += $(this).val() + ' ';
                }
            })

            if ($currentWords.replace(' ', '').length + $(this).val().replace(' ', '').length > $wordMax*$characterMax)
            {
                $(this).siblings('.error').text('Stop using such big words. You may use a maximum of '+$wordMax*$characterMax+' characters for your 5 words.');
            }
            else if ($.trim($currentWords).split(' ').length + $wordNum > $wordMax)
            {
                $(this).siblings('.errors').text('You cannot use more than '+$wordMax+' words total.');
            }
            // Add the word
            else
            {
                var target = $(this).siblings('.wordsC .word:not(.on):first');
                target.addClass('on').prepend('<div>'+$(this).val()+'</div>');
                $(target.data('target')).val($(this).val());
                $(this).val('');
            }
            e.preventDefault();
        }
        else
        {
            // We only allow phrases of 3 or less words...
            if (($wordNum > 3 || ($wordNum == 3 && $code == 32)) && $code != 8 && $code != 46)
            {
                e.preventDefault();
                $(this).siblings('.errors').text('You cannot use more than three words in a single phrase');
            }
        }
    })

    // Used to delete an already added word (on the submit post form)
    $('.post_create .word span').live('click', function() {
        $(this).siblings().remove();
        $($(this).parent().removeClass('on').data('target')).val('');
    })

    // Filter posts by word
    $('.word').live('click', function() {
        var $self = $(this);

        if ($('.word-filters .w-'+$(this).data('id')).length == 0)
        {
            $('.word-filters').show();
            
            $('.word-filters').append($(this).clone().append('<span>x</span>'));
            $('.teaser').each(function() {
                if ($(this).find('.w-'+$self.data('id')).length == 0)
                {
                    $(this).hide();
                }
            })
        }
    })

    // Remove a filtered word
    $('.word-filters .word').live('click', function() {
        var $word = $(this);

        var $filteredFound = false;
        var $filteredWords = '';
        $.each($('.word-filters .word'), function() {
            if ($(this).data('id') != $word.data('id'))
            {
                $filteredFound = true;
                $filteredWords += '.w-'+$(this).data('id')+', ';
            }
        })

        if (!$filteredFound)
        {
            $('.teaser').fadeIn(150);
        }
        else
        {
            console.log($filteredWords);
            $('.teaser').each(function() {
                if ($(this).find($filteredWords).length != 0)
                {
                    $(this).fadeIn(150);
                }
            })
        }
        $(this).remove();

        if ($('.word-filters .word').length == 0)
        {
            $('.word-filters').hide();
        }
    })

    /*
     * LISTS
     */
    $("#list-add-user").autocomplete($('#list-add-user').data('url'), {
        minChars: 3,
        width: 143,
        matchContains: true,
        autoFill: false,
        searchKey: 'name',
        formatItem: function(row, i, max) {
            return row.name;
        },
        formatMatch: function(row, i, max) {
            return row.name;
        },
        formatResult: function(row) {
            return row.name;
        }
    });
    $("#list-add-user").result(function(event, data, formatted) {
        $.post($('#list-add-user').data('url-add'), {'userId': data.id}, function(data) {
                    appUpdate(data);
                    $('#list-add-user').val($('#list-add-user').data('default'));

                    if (data.result != 'error') {
                        $('.list-left ul').prepend('<li>' + data.user + '</li>');
                        $('.list-left ul .none').remove();
                    }
                }, 'json');
    });

    /*
     * PINGS
     */
    $('.ping-countdown').livequery(function() {
        var $self = $(this);
        $self.countdown({
            layout: '{sn}',
            until: '+' + $self.data('until') + 's',
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

    /*
     * SEARCH
     */

    $(".search input").autocomplete($('.search input').data('url'), {
        minChars: 3,
        width: 245,
        matchContains: true,
        autoFill: false,
        searchKey: 'name',
        formatItem: function(row, i, max) {
            return row.formattedItem;
        },
        formatMatch: function(row, i, max) {
            return row.name;
        },
        formatResult: function(row) {
            return row.name;
        }
    });
    $(".search input").result(function(event, data, formatted) {
        $(".search input").val($(".search input").data('default'));
        if (data.postId && $('#post-' + data.postId).length > 0) {
            $.scrollTo('#post-' + data.postId, {
                duration: 500,
                onAfter: function() {
                    $('#post-' + data.postId).click();

                    $(this).oneTime(1000, "show-search-tip", function() {
                        $('.user-' + data.id).qtip({
                            content: 'Found!',
                            style: {
                                classes: 'ui-tooltip-red ui-tooltip-shadow ui-tooltip-rounded my-pings-tip'
                            },
                            position: {
                                my: 'bottom center',
                                at: 'top center'
                            }
                        });
                        $('.user-' + data.id).qtip('show');


                        $(this).oneTime(3000, "hide-search-tip", function() {
                            $('.user-' + data.id).qtip('destroy');
                        })
                    });
                }
            })
        }
        else if ($('.user-' + data.id).length > 0) {
            $.scrollTo('.user-' + data.id, {
                duration: 500,
                onAfter: function() {
                    $(this).oneTime(1000, "show-search-tip", function() {
                        $('.user-' + data.id).qtip({
                            content: 'Found!',
                            style: {
                                classes: 'ui-tooltip-red ui-tooltip-shadow ui-tooltip-rounded my-pings-tip'
                            },
                            position: {
                                my: 'bottom center',
                                at: 'top center'
                            }
                        });
                        $('.user-' + data.id).qtip('show');


                        $(this).oneTime(3000, "hide-search-tip", function() {
                            $('.user-' + data.id).qtip('destroy');
                        })
                    });
                }
            })
        }
        else {
            window.location = '/' + data.username + '/following';
        }
    });
})