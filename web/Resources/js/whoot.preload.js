/*
 * Control the main content resizing
 */

var resizeLayout = function(target)
{
    var h = $(window).height() - $('#header').height();
    target.css('height', h);

    $('#page_content').css('min-height', h-$('#footer').height())
}

// On page loads
$("#sidebar").livequery(function() {
    resizeLayout($(this));
})

// on first load
resizeLayout($("#sidebar"));

// on window resize
$(window).resize(function(){
    resizeLayout($("#sidebar"));
});

/*
 * Fixes for development environment
 */

// Adjust the footer bar to be above the web profiler bar
$('.sf-toolbarreset').livequery(function() {
    $('#footer').css('bottom', $('.sf-toolbarreset').height());
})