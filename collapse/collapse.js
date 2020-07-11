/* For collapsing sections of documents */

$(document).ready(function() {
  $('.collapsible').click(function() {
    $(this).toggleClass('expanded');
    $(this).next().slideToggle('fast');
  })
});

