/* ScienceSeeker functions */

$(document).ready(function() {
	
	$("#pikame").PikaChoose({speed:10000, transition:[2,3,5]});
	
	$('.ss-slide-wrapper, .rec-comment, .comments-list-wrapper').hide();
	
	$('.toggleHidenOption').not(":checked").closest('form').find('.ss-hidden-option').hide();
	
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
	
	$('.filter-button').click(function() {  
    $(this).next().slideToggle("fast");
		$(this).toggleClass("filter-button filter-button-pressed");
  });
	
	$('.checkall').click(function () {
		$(this).parents('form:eq(0)').find('.checkbox').attr('checked', this.checked);
	});
	
	$('#close-notification').click(function () {
		$('#notification-area').slideUp('fast');
	});
	
	$('.rec-comment').on('click', '.ss-button', function(){
		var id = $(this).parents('.ss-entry-wrapper').attr("id");
		var persona = $(this).parents('.ss-entry-wrapper').attr("data-personaId");
		var comment = $(this).parents('.ss-entry-wrapper').find('textarea').val();
		var image = $(this).parents('.ss-entry-wrapper').find('#image').val();
		var dataString = 'id='+ id + '&persona='+ persona + '&comment=' + comment + '&image=' + image + '&step=store';
		var insert = $(this).parents('.ss-entry-wrapper').find('.comments-list-wrapper');
		
		$(insert).html('<img src="http://scienceseeker.org/images/icons/loading.gif" alt="Loading" title="Loading" />').fadeIn('slow');
		$.ajax({
			type: "POST",
			enctype: "multipart/form-data",
			url: "http://dev.scienceseeker.org/subjectseeker/comment.php",
			data: dataString,
			dataType: "html",
			cache: false,
			
			success: function(data) {
				insert.html(data);
			}
		});
	});
	
	$('.recommendation-wrapper').on('click', '.recommend', function() {
		var id = $(this).parents('.ss-entry-wrapper').attr("id");
		var step = $(this).attr("id");
		var persona = $(this).parents('.ss-entry-wrapper').attr("data-personaId");
		var dataString = 'id='+ id + '&persona='+ persona + '&step=' + step;
		var parent = $(this).closest('.recommendation-wrapper');
		var commentWrapper = $(this).closest('.ss-entry-wrapper').find('.rec-comment');
		
		if (persona == '') {
			$('#notification-area').slideDown('fast');
			$('#notification-content').html('You need to register to be able to recommend posts');
		}
		
		else {
			$(parent).html('<img src="http://scienceseeker.org/images/icons/loading.gif" alt="Loading" title="Loading" />').fadeIn('slow');
			$.ajax({
				type: "POST",
				url: "http://dev.scienceseeker.org/subjectseeker/recommend.php",
				data: dataString,
				cache: false,
				
				success: function(html) {
					parent.html(html);
				} 
			});
			if ($(this).attr('id') == 'recommend') {
				$(commentWrapper).slideDown('fast');
			}
			else {
				$(commentWrapper).slideUp('fast');
			}
		}
		return false;
	});
	
	$('.textArea').keyup(function() {
		var area = $(this);
		var count = area.val().length
		if(count > 120) {
			area.val(area.val().substr(0, 120));
		}
		area.nextAll('.alignright').children('.charsLeft').html(120 - area.val().length);
	});
	
	$('.comment-button').click(function() {
		var id = $(this).parents('.ss-entry-wrapper').attr("id");
		var persona = $(this).parents('.ss-entry-wrapper').attr("data-personaId");
		var dataString = 'id='+ id + '&persona='+ persona + '&step=showComments';
		var insert = $(this).parents('.ss-entry-wrapper').find('.comments-list-wrapper');
		var wrapper = $(this).parents('.ss-entry-wrapper').find('.comments-wrapper');
		if ($(insert).html() == "") {
		$(insert).html('<img src="http://scienceseeker.org/images/icons/loading.gif" alt="Loading" title="Loading" />').fadeIn('slow');
			$.ajax({
				type: "POST",
				url: "http://dev.scienceseeker.org/subjectseeker/comment.php",
				data: dataString,
				cache: false,
				
				success: function(html) {
					insert.html(html);
				} 
		});
		}
		else {
			$(wrapper).slideToggle("fast");
		}
	});
	
	$('.categories').click(function() {
		var wrapper = $(this).parents('.categories-wrapper');
		var feed = $(wrapper).attr('data-feed');
		var serializer = $(wrapper).attr('data-serializer');
		
		var dataString = '';
		var i = 0;
		$('#category:checked').each(function () {;
			var value = $(this).attr('value');
			var extra = ('&filter' + i + '=topic&value' + i + '=')
			dataString += extra + value;
			i++;
		});
		$('#filter:checked').each(function () {;
			var value = $(this).attr('value');
			var attribute = ('&' + value + '=1')
			dataString += attribute;
			i++;
		});
		$(wrapper).children('.custom-rss').attr('href', serializer + '?type=blog' + encodeURI(dataString));
		$(wrapper).children('.ss-button').attr('href', feed + '?type=blog' + encodeURI(dataString));
	});
	
	$('#add-author').click(function() {
		$('#journal').before('<div class=\"ss-div-2\"><h4>Author<span id=\"remove-parent\" class=\"alignright\">X</span></h4><textarea name=\"authors[]\" rows=\"2\" cols=\"65\"></textarea></div>');
	});
	
	$('form').on('click', '#remove-parent', function() {
		$(this).parents('.ss-div-2').remove();
	});
	
});