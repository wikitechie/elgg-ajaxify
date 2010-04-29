elgg.provide('elgg.ajax');

/**
 * @author Evan Winslow
 * Provides a bunch of useful shortcut functions for making ajax calls
 */

/**
 * Wrapper function for jQuery.ajax which ensures that the url being called
 * is relative to the elgg site root.
 * 
 * @param {string} url Optionally specify the url as the first argument
 * @param {Object} options Optional. {@see jQuery#ajax}
 * @return {XmlHttpRequest}
 */
elgg.ajax = function(url, options) {
	options = elgg.ajax.handleOptions(url, options);
	
	options.url = elgg.extendUrl(options.url);
	return $.ajax(options);
};

/**
 * Handle optional arguments and return the resulting options object
 * 
 * @param url
 * @param options
 * @return {Object}
 * @private
 */
elgg.ajax.handleOptions = function(url, options) {
	//elgg.ajax('example/file.php', {...});
	if(typeof url == 'string') {
		options = options || {};
	
	//elgg.ajax({...});
	} else {
		options = url || {};
		url = options.url;
	}
	
	var data_only = true;

	//elgg.ajax('example/file.php', {data:{...}});
	if(options.data) {
		data_only = false;
	} else {
		for (var member in options) {
			//elgg.ajax('example/file.php', {callback:function(){...}});
			if(typeof options[member] == 'function') {
				data_only = false;
			}
		}
	}

	//elgg.ajax('example/file.php', {notdata:notfunc});
	if (data_only) {
		var data = options;
		options = {data: data};
	}
	
	if (url) {
		options.url = url;
	}
	
	return options;
};

/**
 * Wrapper function for elgg.ajax which forces the request type to 'get.'
 * 
 * @param {string} url Optionally specify the url as the first argument
 * @param {Object} options {@see jQuery#ajax}
 * @return {XmlHttpRequest}
 */
elgg.get = function(url, options) {
	options = elgg.ajax.handleOptions(url, options);
	
	options.type = 'get';
	return elgg.ajax(options);
};

/**
 * Wrapper function for elgg.get which forces the dataType to 'json.'
 * 
 * @param {string} url Optionally specify the url as the first argument
 * @param {Object} options {@see jQuery#ajax}
 * @return {XmlHttpRequest}
 */
elgg.getJSON = function(url, options) {
	options = elgg.ajax.handleOptions(url, options);
	
	options.dataType = 'json';
	return elgg.get(options);
};

/**
 * Wrapper function for elgg.ajax which forces the request type to 'post.'
 * 
 * @param {string} url Optionally specify the url as the first argument
 * @param {Object} options {@see jQuery#ajax}
 * @return {XmlHttpRequest}
 */
elgg.post = function(url, options) {
	options = elgg.ajax.handleOptions(url, options);
	
	options.type = 'post';
	return elgg.ajax(options);
};

/**
 * Perform an action via ajax
 * 
 * @example Usage 1:
 * At its simplest, only the action name is required (and anything more than the
 * action name will be invalid).
 * <pre>
 * elgg.action('name/of/action');
 * </pre>
 * Note that it will *not* love you if you specify the full url as the action
 * (i.e. elgg.yoursite.com/action/name/of/action), but why would you want to do
 * that anyway, when you can just specify the action name?
 * 
 * @example Usage 2:
 * If you want to pass some data along with it, use the second parameter
 * <pre>
 * elgg.action('friend/add', { friend: some_guid });
 * </pre>
 * 
 * @example Usage 3:
 * Of course, you will have no control over what happens when the request
 * completes if you do it like that, so there's also the most verbose method
 * <pre>
 * elgg.action('friend/add', {
 *     data: {
 *         friend: some_guid
 *     },
 *     success: function(json) {
 *         //do something
 *     },
 * }
 * </pre>
 * You can pass any of your favorite $.ajax arguments into this second parameter.
 * 
 * Note: If you intend to use the second field in the "verbose" way, you must
 * specify a callback method or the data parameter.  If you do not, elgg.action
 * will think you mean to send the second parameter as data.
 * 
 * @param {String} action The action to call.
 * @param {Object} options {@see jQuery#ajax}
 * @return {XMLHttpRequest}
 */
elgg.action = function(action, options) {
	if(!action) {
		throw new TypeError("action must be specified");
	} else if (typeof action != 'string') {
		throw new TypeError("action must be a string");
	}
	
	options = elgg.ajax.handleOptions('action/' + action, options);
	
	options.data = elgg.security.addToken(options.data);
	options.dataType = 'json';
	
	return elgg.post(options);
};

/**
 * Make an API call
 * 
 * @example Usage:
 * <pre>
 * elgg.api('system.api.list', {
 *     success: function(data) {
 *         console.log(data);
 *     }
 * });
 * </pre>
 * 
 * @param {String} method The API method to be called
 * @param {Object} options {@see jQuery#ajax}
 * @return {XmlHttpRequest}
 */
elgg.api = function(method, options) {
	if (!method) {
		throw new TypeError("method must be specified");
	} else if (typeof method != 'string') {
		throw new TypeError("method must be a string");
	}
	
	var defaults = {
		dataType: 'json',
		data: {}
	};
	
	options = elgg.ajax.handleOptions(method, options);
	options = $.extend(defaults, options);
	
	options.url = 'services/api/rest/' + options.dataType + '/';
	options.data.method = method;
	
	return elgg.ajax(options);
};

/**
 * @param {string} selector a jQuery selector
 * @return {XMLHttpRequest}
 */
elgg.refresh = function(selector) {
	return $(selector).load(location.href + ' ' + selector + ' > *');
};

/**
 * @param {string} selector a jQuery selector (usually an #id)
 * @param {number} interval The refresh interval in seconds
 * @return {number} The interval identifier
 */
elgg.feed = function(selector, interval) {
	return setInterval(function() {
		elgg.refresh(selector);
	}, interval);
};