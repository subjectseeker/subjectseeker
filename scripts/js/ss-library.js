/* ScienceSeeker functions */

$(document).ready(function() {
	
	pathArray = window.location.pathname.split( '/' );
	var baseUrl = pathArray[2];
	
	function updateComments (element) {
		var parent = $(element).parents('.ss-entry-wrapper');
		var id = $(parent).attr("id");
		var persona = $(parent).attr("data-personaId");
		var dataString = 'id='+ id + '&persona='+ persona + '&step=showComments';
		var insert = $(parent).find('.comments-list-wrapper');
		if ($(insert).html() == "") {
		$(insert).html('<img src="http://scienceseeker.org/images/icons/loading.gif" alt="Loading" title="Loading" />').fadeIn('slow');
			$.ajax({
				type: "POST",
				url: baseUrl + "/subjectseeker/comment.php",
				data: dataString,
				cache: false,
				
				success: function(html) {
					insert.html(html);
				} 
		});
		}
	}
	
	$("#pikame").PikaChoose({speed:10000, transition:[2,3,5]});
	
	$('.ss-slide-wrapper, .comments-wrapper').hide();
	
	$('.recommend').each(function() {
		if($(this).attr('id') == 'recommend') {
			$(this).parents('.ss-entry-wrapper').find('.rec-comment').hide();
		}
	});
	
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
	
	$('.close-parent').click(function () {
		$(this).parents('.closeable-parent, .comments-wrapper, #notification-area').slideUp('fast');
	});
	
	$('div').on('click', '#remove-parent', function() {
		$(this).parents('.removable-parent').remove();
	});
	
	$('.comments-wrapper').on('click', '.submit-comment', function() {
		var parent = $(this).parents('.ss-entry-wrapper');
		var id = $(parent).attr("id");
		var persona = $(parent).attr("data-personaId");
		var step = $(this).attr("data-step");
		var comment = $(parent).find('textarea').val();
		var dataString = 'id='+ id + '&persona='+ persona + '&comment=' + comment + '&step=' + step;
		var insert = $(parent).find('.comments-list-wrapper');
		
		$(insert).html('<img src="http://scienceseeker.org/images/icons/loading.gif" alt="Loading" title="Loading" />').fadeIn('slow');
		$.ajax({
			type: "POST",
			enctype: "multipart/form-data",
			url: baseUrl + "/subjectseeker/comment.php",
			data: dataString,
			dataType: "html",
			cache: false,
			
			success: function(data) {
				insert.html(data);
			}
		});
		
		updateComments(this);
	});
	
	$('.comment-button').click(function() {
		updateComments(this);
		$(this).parents('.ss-entry-wrapper').find('.comments-wrapper').slideToggle("fast");
	});
	
	$('.recommendation-wrapper').on('click', '.recommend', function() {
		var parent = $(this).parents('.ss-entry-wrapper');
		var id = $(parent).attr("id");
		var step = $(this).attr("id");
		var persona = $(parent).attr("data-personaId");
		var dataString = 'id='+ id + '&persona='+ persona + '&step=' + step;
		var recWrapper = $(this).closest('.recommendation-wrapper');
		var commentsWrapper = $(parent).find('.comments-wrapper');
		var commentTextArea = $(parent).find('.rec-comment');
		
		if (persona == '') {
			$('#notification-area').slideDown('fast');
			$('#notification-content').html('You must register to be able to recommend posts.<br /><br /><a class="ss-button" href="http://dev.scienceseeker.org/wp-login.php">Log In</a> <a class="ss-button" href="http://dev.scienceseeker.org/wp-signup.php">Register</a>');
		}
		else {
			$(recWrapper).html('<img src="http://scienceseeker.org/images/icons/loading.gif" alt="Loading" title="Loading" />').fadeIn('slow');
			$.ajax({
				type: "POST",
				url: baseUrl + "/subjectseeker/recommend.php",
				data: dataString,
				cache: false,
				
				success: function(html) {
					recWrapper.html(html);
				} 
			});
			if ($(this).attr("id") == 'recommend') {
				updateComments(commentsWrapper);
				$(commentTextArea).add(commentsWrapper).slideDown('fast');
			}
			else {
				$(commentTextArea).add(commentsWrapper).slideUp('fast');
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
		$('#journal').before('<div class="removable-parent"><div class="ss-div-2"><h4>Author <span id="remove-parent" class="alignright">X</span></h4><span class="subtle-text">First Name:</span> <textarea name="firstName[]" rows="1" cols="56"></textarea><br /><br /><span class="subtle-text">Last Name:</span> <textarea name="lastName[]" rows="1" cols="56"></textarea></div></div>');
	});
	
});