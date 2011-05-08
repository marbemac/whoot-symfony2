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
    $('#my-post').live('click', function() {
        $('#post-box').fadeIn(500);
    })
})