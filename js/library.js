/* SubjectSeeker functions */

function toggleSlider (slider) {
	slider.slideToggle(400, "swing");
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

function closePopup() {
	$('#popup-box').slideUp();
}

function notification(text) {
	$('#notification-area').slideDown();
	$('#notification-content').html(text);
}

$(document).ready(function() {
	
	var loadingGif = '<div class="center-text"><img src="/images/icons/loading.gif" height="12px" alt="Loading" title="Loading" /></div>';
	
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
		} else {
			$(this).closest('form').find('.ss-hidden-option').slideUp();
		}
  });
	
	$('.ss-entry-wrapper').hover(
		function(){$(this).find('.badges-wrapper').delay(400).slideDown(300); },
		function(){$(this).find('.badges-wrapper').stop(true, true).slideUp(300); }
	);
	
  $('.ss-div-button').click(function() {
    toggleSlider(this.next());
  });
	
	$('.entries, .posts').on('click', '.ss-entry-wrapper', function(event) {
		if(! $( event.target).parents('.ss-slide-wrapper, .recs, .user-card-small, .tag').length && ! $( event.target).is('a, .recommend, input')) {
			var slider = $(this).find('.ss-slide-wrapper:eq(0)');
			var indicator = $(this).find('.entry-indicator');
			slider.slideToggle(300, "swing", function() {
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
	
	$('.trophy-box').on('click', '.nominated,.nominate', function() {
		var parent = $(this).parents('.trophy-box');
		var id = $(this).parents('.trophy-box').data('id');
		var type = $(this).parents('.trophy-box').data('type');
		
		if (!isLoggedIn()) {
			notification('<p>You must be logged in to nominate a post.</p><a class="ss-button" href="/login">Log In</a> <a class="ss-button" href="/register">Register</a>');
			return false;
		}
		
		parent.html(loadingGif);
		$.ajax({
			type: 'POST',
			url: '/scripts/ajax/nominate.php',
			data: 'id=' + id + '&type=' + type,
			cache: false,
			success: function(data) {
				parent.html(data);
			} 
		});
		
		return false;
	});
	
	/*$('#popup-box').on('click', '.contest-category', function() {
		var parent = $(this).parents('ul');
		var id = parent.data('id');
		var category = $(this).data('category');
		$.ajax({
			type: 'POST',
			url: '/scripts/ajax/nominate.php',
			data: 'id=' + id + '&category=' + category,
			cache: false
		});
		
		closePopup();
		
		return false;
	});*/
	
	$('.rec-box').on('click','.rec-count', function() {
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
	
	$('.comment-box').on('click','.comment-count', function() {
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
	
	$('.add-tag').click(function() {
		var parent = $(this).parents('.tags');
		var id = $(this).data('id');
		var type = $(this).data('type');
		
		if (!isLoggedIn()) {
			notification('<p>You must be logged in to add tags.</p><a class="ss-button" href="/login">Log In</a> <a class="ss-button" href="/register">Register</a>');
			
			return false;
		}
		
		if (type == 4) {
			var tagsNum = $(parent).children('.tag').length;
			
			if (tagsNum >= 10) {
				notification('<p>Maximum number of tags reached on this group.</a>');
				return false;
			}
		}
		
		popup('<h2>Add tag</h2><div class="tag-form" data-id="'+id+'" data-type="'+type+'"><p>Tag: <input type="name" name="name" /> <select><option value="0">Public</option><option value="1">Private</option></select></p><input class="tag-button ss-button" type="submit" value="Add Tag" /></div>');
		
	});
	
	$('#popup-box').on('click', '.tag-button', function() {
		var parent = $(this).parents('.tag-form');
		var id = parent.data('id');
		var type = parent.data('type');
		var topicName = parent.find('input[name="name"]').val();
		var privacy = parent.find('option:selected').val();
		
		var addButton = $('.add-tag[data-id="'+id+'"][data-type="'+type+'"]');
		if (type == 4) {
			var tagsNum = $(addButton).parents('tags').children('.tag').length;
			
			if (tagsNum >= 10) {
				notification('<p>Maximum number of tags reached on this group.</a>');
				return false;
			}
		}
		
		$.ajax({
			type: 'POST',
			url: '/scripts/ajax/add-tag.php',
			data: 'id=' + id + '&type=' + type + '&topicName=' + topicName + '&privacy=' + privacy,
			cache: false,
			
			success: function(data) {
				$('.add-tag').before(data);
			} 
		});
		
		closePopup();
	});
	
	$('.tags').on('click', '.tag-remove', function() {
		var parent = $(this).parents('.tag');
		var id = parent.data('id');
		$.ajax({
			type: 'POST',
			url: '/scripts/ajax/remove-tag.php',
			data: 'tagId=' + id,
			cache: false
		});
		parent.remove();
	});
	
	$('.user-card-small').on('click', '.user-remove', function() {
		var parent = $(this).parents('.user-card-small');
		var userId = parent.data('user-id');
		var groupId = parent.data('group-id');
		$.ajax({
			type: 'POST',
			url: '/scripts/ajax/remove-manager.php',
			data: 'userId=' + userId + '&groupId=' + groupId,
			cache: false
		});
		parent.remove();
		
		return false;
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
		$('#journal').before('<div class="removable-parent"><h4>Author <span class="remove-parent">X</span></h4><p><span class="subtle-text">First Name</span><br /><textarea class="small-text-area" name="fName[]"></textarea></p><p><span class="subtle-text">Last Name</span><br /><textarea class="small-text-area" name="lName[]"></textarea></p><br /></div>');
	});
	
});