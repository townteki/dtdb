if (typeof DTDB != "object")
	var DTDB = { data_loaded: jQuery.Callbacks() };

DTDB.user = {};
(function(user, $) {

	user.params = {};
	user.deferred = $.Deferred().always(function() {
		if(user.data) {
			user.update();
		} else {
			user.anonymous();
		}
	});
	
	user.query = function () {
		$.ajax(Routing.generate('user_info', user.params), {
			cache: false,
			dataType: 'json',
			success: function(data, textStatus, jqXHR) {
				user.data = data;
				user.deferred.resolve();
			},
			error: function(jqXHR, textStatus, errorThrown) {
				user.deferred.reject();
			}
		});
	}
	
	user.retrieve = function () {
		if(localStorage) {
			var timestamp = new Date(parseInt(localStorage.getItem('user_timestamp'),10));
			var now = new Date();
			if(now - timestamp < 3600000) {
				var storedData = localStorage.getItem('user');
				if(storedData) {
					user.data = JSON.parse(storedData);
					user.deferred.resolve();
					return;
				}
			}
		}
		user.query();
	}
	
	user.wipe = function () {
		localStorage.removeItem('user');
		localStorage.removeItem('user_timestamp');
	}
	
	user.store = function () {
		localStorage.setItem('user', JSON.stringify(user.data));
		localStorage.setItem('user_timestamp', new Date().getTime());
	}
	
	user.anonymous = function() {
		user.wipe();
		$('#login').append('<ul class="dropdown-menu"><li><a href="'+Routing.generate('fos_user_security_login')+'">Login or Register</a></li></ul>');
	}
	
	user.update = function() {
		user.store();
		$('#login').addClass('dropdown').append('<ul class="dropdown-menu"><li><a href="'
				+ Routing.generate('user_profile',{_locale:user.data.locale}) 
				+ '">Profile page</a></li><li><a href="'
				+ user.data.public_profile_url 
				+ '">Decklists</a></li><li><a href="'
				+ Routing.generate('user_comments',{_locale:user.data.locale})
				+ '">Comments</a></li><li><a href="'
				+ Routing.generate('fos_user_security_logout') 
				+ '" onclick="DTDB.user.wipe()">Jack out</a></li></ul>');
	}
	
	$(function() {
		if($.isEmptyObject(user.params)) {
			user.retrieve()
		} else {
			user.query();
		}
	});
	
})(DTDB.user, jQuery);

