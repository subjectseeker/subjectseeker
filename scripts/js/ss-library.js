$(document).ready(function() {
	$('.toggleHidenOption').not(":checked").closest('form').find('.ss-hidden-option').hide();
  $('.ss-slide-wrapper').hide();
	$('.toggleHidenOption').change(function() {  
		if($(this).attr('checked')) {
			$(this).closest('form').find('.ss-hidden-option').slideDown("fast");
		}
		else {
			$(this).closest('form').find('.ss-hidden-option').slideUp("fast");
		}
  });
	$('.ss-div-button').mouseover(function() {
		$(this).find('.ss-hidden-text').show();
	});
	$('.ss-div-button').mouseleave(function() {
		$(this).find('.ss-hidden-text').hide();
	}); 
  $('.ss-div-button').click(function() {  
    $(this).closest('.ss-entry-wrapper').find('.ss-slide-wrapper').slideToggle("fast");
		$(this).find(".arrow-down,.arrow-up").toggleClass("arrow-up arrow-down");
  });
	$('.checkall').click(function () {
		$(this).parents('form:eq(0)').find('.checkbox').attr('checked', this.checked);
	});
});