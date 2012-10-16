/* SubjectSeeker functions */

$(document).ready(function() {
	
	var loadingGif = '<div class="center-text"><img src="/images/icons/loading.gif" height="12px" alt="Loading" title="Loading" /></div>'
	
	function updateComments (element) {
		var parent = $(element).parents('.data-carrier');
		var id = $(parent).data('id');
		var dataString = 'postId='+ id + '&step=showComments';
		var insert = $(parent).find('.comments-list-wrapper');
		$(insert).html(loadingGif).fadeIn('slow');
			$.ajax({
				type: "POST",
				url: "/scripts/ajax/comment.php",
				data: dataString,
				cache: false,
				
				success: function(data) {
					insert.html(data);
					insert.height('auto');
					parent.find('.note-button').attr('data-number', $(data).filter('div').attr('data-count'));
					updateCommentsButton(insert);
					if(insert.is(':visible')){
						insert.css({ 'height' : ($(insert).height())});
					}
				} 
		});
		
	}
	
	function toggleSlider (slider) {
		slider.slideToggle(400, "swing", function() {
			updateCommentsButton(this);
		});
	}
	
	function updateCommentsButton (element) {
		var parent = $(element).parents('.data-carrier');
		var button = $(parent).find('.note-button');
		var count = $(parent).find('.note-count');
		var number = $(button).attr('data-number');
		var commentsContent = $(parent).find('.comments-list-wrapper').html();
		
		if (commentsContent != "") {
			$(count).html(number);
		}
	}
	
	function isLoggedIn (element) {
		var userId = $(element).parents('body').find('#user-box').attr("data-user");
		
		return userId;
	}
	
	$('#ss-slideshow, .filter-buttons').fadeIn();
	
	$('#loading-message').hide();
	
	$('.tabs').on('click', '.tab-button', function() {
		var eq = $(this).index();
		var parent = $(this).parents('.tabs');
		var tab = $(parent).find('.tab-item').eq(eq);
		
		(parent).find('.tab-button-pressed').attr('class', 'tab-button');
		$(this).attr('class', 'tab-button-pressed');
		
		$(parent).find('.tab-item').fadeOut(200);
		$(tab).fadeIn(200);
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
		function(){$(this).find('.badges').children('.ss-slide-wrapper').delay(400).slideDown(300); },
		function(){$(this).find('.badges').children('.ss-slide-wrapper').stop(true, true).slideUp(300); }
	);
	
  $('.ss-div-button').click(function() {
    toggleSlider(this.next());
  });
	
	$('.ss-entry-wrapper').click(function(event) {
		if(! $( event.target).parents('.ss-slide-wrapper, .recs').length && ! $( event.target).is('a, #recommend')) {
			var slider = $(this).find('.ss-slide-wrapper:eq(0)');
			var indicator = $(this).find('.entry-indicator');
			slider.slideToggle(300, "swing", function() {
				updateCommentsButton(this);
				if(slider.is(":visible")) {
					indicator.html('-');
				} else {
					indicator.html('+');
				}
			});
		}
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
	
	$('div').on('click', '.remove-parent', function() {
		$(this).parents('.removable-parent').remove();
		if ($('.removable-parent').length >= 10) {
			$('#add-author').removeAttr('disabled');
			return;
		}
	});
	
	$('.data-carrier').on('click', '.submit-comment', function() {
		var parent = $(this).parents('.data-carrier');
		var postId = $(parent).data('id');
		var step = $(this).attr("data-step");
		var comment = $(parent).find('.note-area').val();
		var commentButton = $(parent).find('.note-button');
		var insert = $(parent).find('.comments-list-wrapper');
		var commentTextArea = $(parent).find('.text-area');
		var tweetPreview = $(parent).find('.tweet-preview').text();
		var commentNotification = commentTextArea.next('.comment-notification');
		
		if (parent.find('.tweet-note').is(":checked")) {
			var tweetNote = 'true';
		}
		
		var dataString = 'postId='+ postId + '&comment=' + comment + '&tweet=' + tweetNote + '&tweetContent=' + tweetPreview + '&step=' + step;
		
		insert.html(loadingGif).fadeIn('slow');
		$.ajax({
			type: "POST",
			enctype: "multipart/form-data",
			url: "/scripts/ajax/comment.php",
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
	
	$('.recommendation-wrapper').on('click', '.recommended,.recommend', function() {
		var parent = $(this).parents('.data-carrier');
		var id = $(parent).data('id');
		var step = $(this).attr("class");
		var dataString = 'postId='+ id +'&step=' + step;
		var recWrapper = $(this).closest('.recommendation-wrapper');
		var commentTextArea = $(parent).find('.rec-comment');
		
		if (!isLoggedIn(parent)) {
			$('#notification-area').slideDown();
			$('#notification-content').html('<p>You must register to be able to recommend posts.</p><a class="ss-button" href="/login">Log In</a> <a class="ss-button" href="/register">Register</a>');
		}
		else {
			$(recWrapper).html(loadingGif).fadeIn('slow');
			$.ajax({
				type: 'POST',
				url: '/scripts/ajax/recommend.php',
				data: dataString,
				cache: false,
				
				success: function(data) {
					updateComments(commentTextArea);
					recWrapper.html(data);
				} 
			});
			if (step == 'recommend') {
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
		var comment = $(parent).find('.note-area').val();
		var postId = $(parent).data('id');
		$.ajax({
			type: 'POST',
			url: '/scripts/ajax/tweet-data.php',
			data: 'postId=' + postId,
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
	
	$('.note-area').keyup(function() {
		var area = $(this);
		var parent = $(this).parents('.data-carrier');
		var comment = $(parent).find('.note-area').val();
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
		var serializer = $(wrapper).attr('data-feed');
		var text = $(wrapper).find('.filters-text').val();
		
		var postsString = '';
		var blogsString = '';
		var i = 0;
		$('.categories:checked').each(function () {;
			var value = $(this).attr('value');
			postsString += '&filter' + i + '=blog&modifier' + i + '=topic&value' + i + '=' + value;
			blogsString += '&filter' + i + '=topic&value' + i + '=' + value;
			i++;
		});
		$('.filters:checked').each(function () {;
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
		if (text) {
			postsString += '&filter' + i + '=title&' + 'value' + i + '=' + text;
			blogsString += '&filter' + i + '=title&' + 'value' + i + '=' + text;
			i++;
		}
		wrapper.find('.button-small-yellow[data-button="filter-feed"]').attr('href', serializer + '?type=post' + encodeURI(postsString));
		wrapper.find('.button-small-red[data-button="filter-posts"]').attr('href', posts + '?type=post' + encodeURI(postsString));
		wrapper.find('.button-small-red[data-button="filter-widget"]').attr('href', widget + '?type=post' + encodeURI(postsString));
		wrapper.find('.button-small-red[data-button="filter-blogs"]').attr('href', blogs + '?type=blog' + encodeURI(blogsString));
	});
	
	$('#add-author').click(function() {
		if ($('.removable-parent').length >= 10) {
			$(this).attr("disabled", true);
			$('#notification-content').html('Only 10 authors allowed for citations.');
			$('#notification-area').slideDown();
			return;
		}
		$('#journal').before('<div class="removable-parent"><div class="margin-bottom"><h4>Author <span class="remove-parent">X</span></h4><span class="subtle-text">First Name</span><br /><textarea class="small-text-area" name="fName[]"></textarea><br /><br /><span class="subtle-text">Last Name</span><br /><textarea class="small-text-area" name="lName[]"></textarea></div></div>');
	});
	
});