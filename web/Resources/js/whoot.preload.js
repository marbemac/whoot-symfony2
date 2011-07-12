/*
 * Control the main content resizing
 */

var resizeLayout = function()
{
    console.log('test');
    var h = $(window).height() - $('#header').height();
    $('#sidebar').css('height', h);
    $('#page_content').css('height', h-parseInt($('#page_content').css('margin-top').replace("px", ""))-parseInt($('#page_content').css('margin-bottom').replace('px', '')));
}

// On page loads
$("#sidebar").livequery(function() {
    resizeLayout();
})

// on first load
resizeLayout();

// on window resize
$(window).resize(function(){
    resizeLayout();
});

/*
 * Fixes for development environment
 */

// Adjust the footer bar to be above the web profiler bar
$('.sf-toolbarreset').livequery(function() {
    $('#footer').css('bottom', $('.sf-toolbarreset').height());
})