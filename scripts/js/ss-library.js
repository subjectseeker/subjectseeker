/* ScienceSeeker functions */

$(document).ready(function() {
	
	var loadingGif = '<img src="/images/icons/loading.gif" alt="Loading" title="Loading" />'
	
	function updateComments (element) {
		var parent = $(element).parents('.data-carrier');
		var id = $(parent).attr("id");
		var persona = $(parent).attr('data-personaId');
		var dataString = 'id='+ id + '&persona='+ persona + '&step=showComments';
		var insert = $(parent).find('.comments-list-wrapper');
		
		$(insert).html(loadingGif).fadeIn('slow');
			$.ajax({
				type: "POST",
				url: "/subjectseeker/comment.php",
				data: dataString,
				cache: false,
				
				success: function(html) {
					insert.html(html);
				} 
		});
		
	}
	
	function toggleSlider (button) {
		$(button).next('.ss-slide-wrapper').slideToggle('slow', "swing", function() {
			updateCommentsButton($(this).parents('.data-carrier').find('.comment-button'));
		});
		$(button).find(".arrow-down,.arrow-up").toggleClass("arrow-up arrow-down");
	}
	
	function updateCommentsButton (element) {
		var parent = $(element).parents('.data-carrier');
		var button = $(parent).find('.comment-button');
		var slider = $(parent).find('#post-info.ss-slide-wrapper');
		var number = $(button).attr('data-number');
		var commentsContent = $(parent).find('.comments-list-wrapper').html();
		
		if (commentsContent != "") {
			if((slider).is(':visible')){
				$(button).html('Hide Info');
			}
			else {
				$(button).html(number + ' Note');
				if (number != 1) {
					$(button).append('s');
				}
			}
		}
	}
	
	function updateCoords(c) {
		$('#x').val(c.x);
		$('#y').val(c.y);
		$('#w').val(c.w);
		$('#h').val(c.h);
	};

	function checkCoords() {
		if (parseInt($('#w').val())) return true;
		alert('Please select a crop region then press submit.');
		return false;
	};
	
	$("#pikame").PikaChoose({speed:10000, transition:[2,3,5]});
	
	$('.ssSlideShow').fadeIn();
	
	$('#jcrop-target').Jcrop({
		minSize: [ 580, 200 ],
		setSelect: [ 0, 0, 580, 200 ],
		aspectRatio: 59/20,
		onSelect: updateCoords
	});
	
	$('.ss-slide-wrapper, #loading-message').hide();
	
	$('.recommend').each(function() {
		if($(this).attr('id') == 'recommend') {
			$(this).parents('.data-carrier').find('.rec-comment').hide();
		}
	});
	
	$('.toggleHidenOption').not(":checked").closest('form').find('.ss-hidden-option').hide();
	
	$('.toggleHidenOption').change(function() {  
		if($(this).attr('checked')) {
			$(this).closest('form').find('.ss-hidden-option').slideDown();
		}
		else {
			$(this).closest('form').find('.ss-hidden-option').slideUp();
		}
  });
	
	$('.ss-div-button').mouseover(function() {
		$(this).find('.ss-hidden-text').show();
	});
	
	$('.ss-div-button').mouseleave(function() {
		$(this).find('.ss-hidden-text').hide();
	}); 
	
  $('.ss-div-button').click(function() {
    toggleSlider(this);
  });
	
	$('.toggle-button').click(function() {  
    $(this).next().slideToggle();
		$(this).toggleClass("toggle-button toggle-button-pressed");
  });
	
	$('.checkall').click(function () {
		$(this).parents('form:eq(0)').find('.checkbox').attr('checked', this.checked);
	});
	
	$('.close-parent').click(function () {
		$(this).parents('.closeable-parent, .comments-wrapper, #notification-area').slideUp();
	});
	
	$('div').on('click', '#remove-parent', function() {
		$(this).parents('.removable-parent').remove();
		if ($('.removable-parent').length >= 10) {
			$('#add-author').removeAttr('disabled');
			return;
		}
	});
	
	$('.data-carrier').on('click', '.submit-comment', function() {
		var parent = $(this).parents('.data-carrier');
		var id = $(parent).attr("id");
		var persona = $(parent).attr("data-personaId");
		var step = $(this).attr("data-step");
		var comment = $(parent).find('textarea').val();
		var commentButton = $(parent).find('.comment-button');
		var dataString = 'id='+ id + '&persona='+ persona + '&comment=' + comment + '&step=' + step;
		var insert = $(parent).find('.comments-list-wrapper');
		var commentTextArea = $(parent).find('.text-area');
		var commentNotification = commentTextArea.next('.comment-notification');
		
		$(insert).html(loadingGif).fadeIn('slow');
		$.ajax({
			type: "POST",
			enctype: "multipart/form-data",
			url: "/subjectseeker/comment.php",
			data: dataString,
			dataType: "html",
			cache: false,
			
			success: function(data) {
				commentTextArea.slideUp();
				commentNotification.html('<p><span class="ss-bold">Your note has been submitted and linked to your recommendation.</span></p>').show();
				setTimeout(function(){
					if(commentNotification.is(':visible')){
						commentNotification.slideUp();
					}
					else{
						commentNotification.hide();
					}
				},6000);
				insert.html(data);
				updateComments(this);
				updateCommentsButton(commentButton);
				$(parent).find('.comment-button').attr('data-number', $(data).filter('div').attr('data-count'));
			}
		});	
	});
	
	$('.comment-button').click(function() {
		var button = $(this);
		var number = $(button).attr('data-number');
		var parent = $(button).parents('.data-carrier');
		var slider = $(parent).find('#post-info.ss-slide-wrapper');
		var sliderButton = $(parent).find('.ss-div-button');
		var commentsContent = $(parent).find('.comments-list-wrapper').html();
		updateComments(this);
		if($(slider).is(':visible')){
			if (commentsContent == "") {
				updateCommentsButton (this);
				return;
			}
		}
		toggleSlider(sliderButton);
	});
	
	$('.recommendation-wrapper').on('click', '.recommend', function() {
		var parent = $(this).parents('.data-carrier');
		var id = $(parent).attr("id");
		var step = $(this).attr("id");
		var persona = $(parent).attr("data-personaId");
		var dataString = 'id='+ id + '&persona='+ persona + '&step=' + step;
		var recWrapper = $(this).closest('.recommendation-wrapper');
		var commentTextArea = $(parent).find('.rec-comment');
		
		if (persona == '') {
			$('#notification-area').slideDown();
			$('#notification-content').html('<p>You must register to be able to recommend posts.</p><a class="ss-button" href="/wp-login.php">Log In</a> <a class="ss-button" href="/wp-signup.php">Register</a>');
		}
		else {
			$(recWrapper).html(loadingGif).fadeIn('slow');
			$.ajax({
				type: "POST",
				url: "/subjectseeker/recommend.php",
				data: dataString,
				cache: false,
				
				success: function(html) {
					recWrapper.html(html);
				} 
			});
			if ($(this).attr("id") == 'recommend') {
				commentTextArea.slideDown();
				commentTextArea.find('.text-area').show();
			}
			else {
				if(!(commentTextArea).is(":visible")){
					$(commentTextArea).hide();
				}else{
					$(commentTextArea).slideUp();
				}
			}
			updateComments(recWrapper);
			updateCommentsButton($(parent).find('.comment-button'));
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
		$('#topic:checked').each(function () {;
			var value = $(this).attr('value');
			var extra = ('&filter' + i + '=topic&value' + i + '=');
			dataString += extra + value;
			i++;
		});
		$('#modifier:checked').each(function () {;
			var value = $(this).attr('value');
			var extra = ('&filter' + i + '=modifier&value' + i + '=');
			dataString += extra + value;
			i++;
		});
		$(wrapper).children('.custom-rss').attr('href', serializer + '?type=post' + encodeURI(dataString));
		$(wrapper).children('.ss-button').attr('href', feed + '?type=post' + encodeURI(dataString));
	});
	
	$('#add-author').click(function() {
		if ($('.removable-parent').length >= 10) {
			$(this).attr("disabled", true);
			$('#notification-content').html('Only 10 authors allowed for citations.');
			$('#notification-area').slideDown();
			return;
		}
		$('#journal').before('<div class="removable-parent"><div class="ss-div-2"><h4>Author <span id="remove-parent" class="alignright">X</span></h4><span class="subtle-text">First Name:</span> <textarea name="firstName[]" rows="1" cols="56"></textarea><br /><br /><span class="subtle-text">Last Name:</span> <textarea name="lastName[]" rows="1" cols="56"></textarea></div></div>');
	});
	
});