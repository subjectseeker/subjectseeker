/* SubjectSeeker functions */

/*function getUrlVars() {
	var vars = [], hash;
	var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
	var out = hashes;
	for(var i = 0; i < hashes.length; i++) {
		hash = hashes[i].split('=');
		vars.push(hash[0]);
		vars[hash[0]] = hash[1];
		
		//out += hash;
	}
	//return vars;
	return out;
}*/

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
	var commentsContent = $(parent).find('.comments').html();
	
	if (commentsContent != "") {
		$(count).html(number);
	}
}

function isLoggedIn () {
	var userId = $('#user-box').data('user');
	
	return userId;
}

function popup(text) {
	$('#popup-box').slideDown();
	$('#popup-content').html(text);
}

function notification(text) {
	$('#notification-area').slideDown();
	$('#notification-content').html(text);
}

$(document).ready(function() {
	
	var loadingGif = '<div class="center-text"><img src="/images/icons/loading.gif" height="12px" alt="Loading" title="Loading" /></div>'
	
	/*function updateComments (element) {
		var parent = $(element).parents('.data-carrier');
		var id = $(parent).data('id');
		var dataString = 'postId='+ id + '&step=showComments';
		var insert = $(parent).find('.comments');
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
		
	}*/
	
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
		$(this).parent().slideUp();
	});
	
	$('div').on('click', '.remove-parent', function() {
		$(this).parents('.removable-parent').remove();
		if ($('.removable-parent').length >= 10) {
			$('#add-author').removeAttr('disabled');
			return;
		}
	});
	
	/*$(window).scroll( function(){
		//$('.infinite-scroll').each(function () {
			var wrapper = $('.infinite-scroll:last');
			var childName = $(wrapper).data('child');
			var lastItem = $(wrapper).find('.'+childName+':last-child');
			var enabled = $(wrapper).data('enabled');
			var name = $(wrapper).data('name');
			var bottom_of_object = lastItem.position();
			var bottom_of_window = $(window).scrollTop() + $(window).height();
			var query = getUrlVars();
			alert(bottom_of_object);
			if(bottom_of_window > bottom_of_object && $(wrapper).attr('data-enabled') == 'true') {
				//alert('window:' + bottom_of_window + ' object:' + bottom_of_object + ' enabled:' + $(wrapper).attr('data-enabled'));
				$(wrapper).attr('data-enabled', 'false');
				$.ajax({
					type: "POST",
					enctype: "multipart/form-data",
					url: "/scripts/ajax/"+name+".php",
					data: 'query=' + query,
					cache: false,
					
					success: function(data) {
						wrapper.after(data);
					}
				});
			}
		//});
	});*/
	
	$('.submit-comment').on('click', function() {
		var parent = $(this).parents('.data-carrier');
		var postId = $(parent).data('id');
		var type = $(parent).data('type');
		var step = $(this).attr("data-step");
		var comment = $(parent).find('.comment-area').val();
		var commentButton = $(parent).find('.comment-button');
		var comments = $(parent).find('.comments');
		var commentTextArea = $(parent).find('.text-area');
		var tweetPreview = $(parent).find('.tweet-preview').text();
		var commentNotification = commentTextArea.next('.comment-notification');
		var dataString = 'id='+ postId + '&comment=' + comment + '&tweet=' + tweetNote + '&tweetContent=' + tweetPreview + '&step=' + step + '&type=' + type;
		
		if (parent.find('.tweet-note').is(":checked")) {
			var tweetNote = 'true';
		}
		
		//insert.html(loadingGif).fadeIn('slow');
		$.ajax({
			type: "POST",
			enctype: "multipart/form-data",
			url: "/scripts/ajax/comment.php",
			data: dataString,
			cache: false,
			
			success: function(data) {
				comments.append(data);
			}
		});
	});
	
	$('.comments').on('click', '.comment-delete', function() {
		var comment = $(this).parents('.comment');
		var commentId = $(comment).data('comment-id');
		var dataString = 'commentId='+ commentId;
		
		//insert.html(loadingGif).fadeIn('slow');
		$.ajax({
			type: "POST",
			enctype: "multipart/form-data",
			url: "/scripts/ajax/comment-delete.php",
			data: dataString,
			cache: false,
			
			success: function(data) {
				comment.remove();
			}
		});
	});
	
	$('.rec-box').on('click', '.recommended,.recommend', function() {
		var parent = $(this).parents('.rec-box');
		var id = $(parent).data('id');
		var type = $(parent).data('type');
		var step = $(this).attr("class");
		var dataString = 'id='+ id + '&type=' + type + '&step=' + step;
		
		if (!isLoggedIn()) {
			notification('<p>You must register to be able to recommend posts.</p><a class="ss-button" href="/login">Log In</a> <a class="ss-button" href="/register">Register</a>');
			
			return false;
		}
		
		parent.html(loadingGif).fadeIn('slow');
		$.ajax({
			type: 'POST',
			url: '/scripts/ajax/recommend.php',
			data: dataString,
			cache: false,
			
			success: function(data) {
				parent.html(data);
			} 
		});
		
	});
	
	$('.rec-count').on('click', function() {
		var parent = $(this).parents('.rec-box');
		var id = $(parent).data('id');
		var type = $(parent).data('type');
		var dataString = 'id='+ id + '&type=' + type;
		
		$.ajax({
			type: 'POST',
			url: '/scripts/ajax/show-recs.php',
			data: dataString,
			cache: false,
			
			success: function(data) {
				popup(data);
			} 
		});
		
		return false;
	});
	
	$('.comment-count').on('click', function() {
		var parent = $(this).parents('.comment-box');
		var id = $(parent).data('id');
		var type = $(parent).data('type');
		var dataString = 'id='+ id + '&type=' + type;
		
		$.ajax({
			type: 'POST',
			url: '/scripts/ajax/show-comments.php',
			data: dataString,
			cache: false,
			
			success: function(data) {
				popup(data);
			} 
		});
		
		return false;
	});
	
	$('.follow-status').on('click', '.follow-button,.unfollow-button', function() {
		var wrapper = $(this).parents('.follow-status');
		var id = $(wrapper).data('id');
		var type = $(wrapper).data('type');
		var dataString = 'id='+ id + '&type=' + type;
		
		if (!isLoggedIn()) {
			notification('<p>You must register to be able to recommend posts.</p><a class="ss-button" href="/login">Log In</a> <a class="ss-button" href="/register">Register</a>');
			
			return false;
		}
		
		$(wrapper).html(loadingGif).fadeIn('slow');
		$.ajax({
			type: 'POST',
			url: '/scripts/ajax/follow.php',
			data: dataString,
			cache: false,
			success: function(data) {
				wrapper.html(data);
			} 
		});
		
		return false;
	});
	
	$('.tweet-note').click(function() {
		var tweet = $(this);
		var parent = $(this).parents('.data-carrier');
		var comment = $(parent).find('.note-area').val();
		var postId = $(parent).data('id');
		if (tweet.is(':checked')) {
			$.ajax({
				type: 'POST',
				url: '/scripts/ajax/tweet-data.php',
				data: 'postId=' + postId,
				cache: false,
				
				success: function(data) {
					parent.find('.tweet-extras').html(data);
					parent.find('.tweet-preview-area').fadeIn();
					$('.comment-area').keyup();
				} 
			});
		} else {
			parent.find('.tweet-preview-area').fadeOut();
			parent.find('.tweet-extras').html('');
			$('.comment-area').keyup();
		}
	});
	
	$('.comment-area').keyup(function() {
		var area = $(this);
		var parent = $(this).parents('.data-carrier');
		var comment = $(parent).find('.comment-area').val();
		var counter = $(parent).find('.char-count');
		var limit = $(counter).data('limit');
		
		parent.find('.tweet-message').html(comment);
		var count = parent.find('.tweet-preview').text().length;
		if (count > limit) {
			$(parent).find('.submit-comment').attr('disabled','disabled');
		} else {
			$(parent).find('.submit-comment').removeAttr('disabled');
		}
		counter.html(limit - count);
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