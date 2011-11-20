$(document).ready(function() {
  $('.ss-slide-wrapper').hide();
	$('.ss-div-button').mouseover(function() {
		$(this).find('.ss-hidden-text').show();
	});
	$('.ss-div-button').mouseleave(function() {
		$(this).find('.ss-hidden-text').hide();
	}); 
  $('.ss-div-button').click(function() {  
    $(this).next('.ss-slide-wrapper').slideToggle("fast");
		$(this).find(".arrow-down,.arrow-up").toggleClass("arrow-up arrow-down");
  });
	$('.checkall').click(function () {
		$(this).parents('form:eq(0)').find(':checkbox').attr('checked', this.checked);
	});
});