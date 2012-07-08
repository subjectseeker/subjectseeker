/* ScienceSeeker functions */

$(document).ready(function() {
	
	var loadingGif = '<div class="center-text"><img src="/images/icons/loading.gif" alt="Loading" title="Loading" /></div>'
	
	function updateComments (element) {
		var parent = $(element).parents('.data-carrier');
		var id = $(parent).attr("id");
		var dataString = 'id='+ id + '&step=showComments';
		var insert = $(parent).find('.comments-list-wrapper');
		$(insert).html(loadingGif).fadeIn('slow');
			$.ajax({
				type: "POST",
				url: "/subjectseeker/comment.php",
				data: dataString,
				cache: false,
				
				success: function(data) {
					insert.html(data);
					insert.height('auto');
					parent.find('.comment-button').attr('data-number', $(data).filter('div').attr('data-count'));
					updateCommentsButton(insert);
					if(insert.is(':visible')){
						insert.css({ 'height' : ($(insert).height())});
					}
				} 
		});
		
	}
	
	function toggleSlider (button) {
		var slider = $(button).next('.ss-slide-wrapper');
		slider.slideToggle(400, "swing", function() {
			updateCommentsButton(this);
			if(slider.is(':visible')){
				$(button).find(".arrow-down,.arrow-up").attr("class", "arrow-up");
			}
			else {
				$(button).find(".arrow-down,.arrow-up").attr("class", "arrow-down");
			}
		});
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
	
	$('.ssSlideShow, #filter-buttons').fadeIn();
	
	$('#jcrop-target').Jcrop({
		minSize: [ 580, 200 ],
		setSelect: [ 0, 0, 580, 200 ],
		aspectRatio: 59/20,
		onSelect: updateCoords
	});
	
	$('#loading-message').hide();
	
	$('.red-star,.grey-star').each(function() {
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
	
	$('.ss-entry-wrapper').hover(
		function(){$(this).find('#etiquettes.ss-slide-wrapper').delay(400).slideDown(300); },
		function(){$(this).find('#etiquettes.ss-slide-wrapper').stop(true, true).delay(400).slideUp(300); }
	);
	
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
		var step = $(this).attr("data-step");
		var comment = $(parent).find('textarea').val();
		var commentButton = $(parent).find('.comment-button');
		var insert = $(parent).find('.comments-list-wrapper');
		var commentTextArea = $(parent).find('.text-area');
		var tweetPreview = $(parent).find('.tweet-preview').text();
		var commentNotification = commentTextArea.next('.comment-notification');
		
		if (parent.find('.tweet-note').is(":checked")) {
			var tweetNote = 'true';
		}
		
		var dataString = 'id='+ id + '&comment=' + comment + '&tweet=' + tweetNote + '&tweetContent=' + tweetPreview + '&step=' + step;
		
		insert.html(loadingGif).fadeIn('slow');
		$.ajax({
			type: "POST",
			enctype: "multipart/form-data",
			url: "/subjectseeker/comment.php",
			data: dataString,
			dataType: "html",
			cache: false,
			
			success: function(data) {
				commentNotification.html(data);
				if (commentNotification.html() == '') {
					commentNotification.html('<p><span class="ss-bold">Your note has been submitted and linked to your recommendation.</span></p>').show();
					setTimeout(function(){
						if(commentNotification.is(':visible')){
							commentNotification.slideUp();
						}
						else{
							commentNotification.hide();
						}
					},6000);
				}
				updateComments(insert);
				commentTextArea.slideUp();
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
		
		if (commentsContent == "") updateComments(this);
		if($(slider).is(':visible')){
			if (commentsContent == "") {
				return;
			}
		}
		toggleSlider(sliderButton);
	});
	
	$('.recommendation-wrapper').on('click', '.red-star,.grey-star', function() {
		var parent = $(this).parents('.data-carrier');
		var user = $(parent).attr("data-user");
		var id = $(parent).attr("id");
		var step = $(this).attr("id");
		var dataString = 'id='+ id +'&step=' + step;
		var recWrapper = $(this).closest('.recommendation-wrapper');
		var commentTextArea = $(parent).find('.rec-comment');
		
		if (! user) {
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
				
				success: function(data) {
					updateComments(commentTextArea);
					recWrapper.html(data);
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
		}
		return false;
	});
	
	$('.tweet-note').click(function() {
		var tweet = $(this);
		var parent = $(this).parents('.data-carrier');
		var comment = $(parent).find('textarea').val();
		var id = $(parent).attr("id");
		$.ajax({
			type: 'POST',
			url: '/subjectseeker/getTweetData.php',
			data: 'id=' + id,
			cache: false,
			
			success: function(data) {
				parent.find('.tweet-extras').html(data);
			} 
		});
		if (tweet.is(':checked')) {
			parent.find('.tweet-preview-area').fadeIn();
		}
		else {
			parent.find('.tweet-preview-area').fadeOut();
		}
	});
	
	$('.textArea').keyup(function() {
		var area = $(this);
		var parent = $(this).parents('.data-carrier');
		var comment = $(parent).find('textarea').val();
		var tweetPreview = comment;
		var count = area.val().length
		if(count > 102) {
			area.val(area.val().substr(0, 102));
		}
		parent.find('.charsLeft').html(102 - area.val().length);
		parent.find('.tweet-message').html(comment);
	});
	
	$('.categories-wrapper').click(function() {
		var wrapper = $(this);
		var posts = $(wrapper).attr('data-posts');
		var widget = $(wrapper).attr('data-widget');
		var blogs = $(wrapper).attr('data-blogs');
		var serializer = $(wrapper).attr('data-rss');
		
		var postsString = '';
		var blogsString = '';
		var i = 0;
		$('#topic:checked').each(function () {;
			var value = $(this).attr('value');
			postsString += '&filter' + i + '=blog&modifier' + i + '=topic&value' + i + '=' + value;
			blogsString += '&filter' + i + '=topic&value' + i + '=' + value;
			i++;
		});
		$('#filter:checked').each(function () {;
			var filter = $(this).attr('value');
			if (filter == 'recommender-status') {
				postsString += '&filter' + i + '=' + filter + '&value' + i + '=editor';
			}
			if (filter == 'has-citation') {
				postsString += '&filter' + i + '=' + filter;
				blogsString += '&filter' + i + '=' + filter;
			}
			i++;
		});
		wrapper.find('#filter-rss').attr('href', serializer + '?type=post' + encodeURI(postsString));
		wrapper.find('#filter-posts').attr('href', posts + '?type=post' + encodeURI(postsString));
		wrapper.find('#filter-widget').attr('href', widget + '?type=post' + encodeURI(postsString));
		wrapper.find('#filter-blogs').attr('href', blogs + '?type=blog' + encodeURI(blogsString));
	});
	
	$('#add-author').click(function() {
		if ($('.removable-parent').length >= 10) {
			$(this).attr("disabled", true);
			$('#notification-content').html('Only 10 authors allowed for citations.');
			$('#notification-area').slideDown();
			return;
		}
		$('#journal').before('<div class="removable-parent"><div class="ss-div-2"><h4>Author <span id="remove-parent" class="alignright">X</span></h4><span class="subtle-text">First Name:</span> <textarea name="fName[]" rows="1" cols="56"></textarea><br /><br /><span class="subtle-text">Last Name:</span> <textarea name="lName[]" rows="1" cols="56"></textarea></div></div>');
	});
	
});