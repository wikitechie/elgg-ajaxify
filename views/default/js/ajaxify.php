elgg.provide('elgg.ajaxify');

elgg.ajaxify.init = function() {
	elgg.ajaxify.ajaxLoader = $('<div class=elgg-ajax-loader></div>');
	$('.elgg-menu-item-likes').live('click', function(event) {
		elgg.ajaxify.likes.action(this);
		event.preventDefault();
	});
	$('.elgg-menu-item-delete').live('click', function(event) {
		elgg.ajaxify.delete_entity(elgg.ajaxify.getGUIDFromMenuItem(this));
		event.preventDefault();
	});
	$('input[name=address]').live('blur', function(event) {
		elgg.ajaxify.bookmarks(this);
	});
	//Default actions that have to be invoked after a successful AJAX request
	$(document).ajaxSuccess(function(event, xhr, options) {
		//Check for any system messages
		try {
			var response = jQuery.parseJSON(xhr.responseText);
		} catch(JSONException) {
			console.log('Not a JSON response');
		}
		if (response && response.system_messages) {
			elgg.register_error(response.system_messages.error);
			elgg.system_message(response.system_messages.success);
		}
	});
};

/**
 * Fetch a view via AJAX
 *
 * @example Usage:
 * Use it to fetch a view using /ajax/view
 * can also be used to refresh a view
 * elgg.view('likes/display', {data: {guid: GUID}, target: targetDOM})
 * @param {string} name Viewname
 * @param {Object} options Parameters to the view along with jQuery options {@see jQuery#ajax}
 * @return {void}
 */

elgg.view = function(name, options) {
	elgg.assertTypeOf('string', name);
	//Check to see if its already a normalized url
	if (new RegExp("^(https?://)", "i").test(name)) {
		name = name.split(elgg.config.wwwroot)[1];
	}
	var url = elgg.normalize_url('ajax/view/'+name);
	if (elgg.isNullOrUndefined(options.success)) {
		options.manipulationMethod = options.manipulationMethod || 'html';
		options.success = function(data) {
			$(options.target)[options.manipulationMethod](data);
		}
	}
	elgg.get(url, options);
};

/**
 * Delete an entity
 *
 * @param guid The guid of the entity we want to delete
 * @return {XMLHttpRequest}
 */

elgg.ajaxify.delete_entity = function(guid) {
	guid = parseInt(guid);
	if (guid < 1) {
		return false;
	}
	$('#elgg-object-'+guid).slideUp();
	return elgg.action('entity/delete', {guid: guid});
};

/**
 * Get URL from ElggMenuItem 
 *
 * @param item {Object} List item 
 * @return URL {String}
 */

elgg.ajaxify.getURLFromMenuItem = function(item) {
	var actionURL = $(item).find('a').attr('href');
	return actionURL;
};

/**
 * Parse guid from ElggMenuItem 
 *
 * @param item {Object} List item 
 * @return guid {String}
 */

elgg.ajaxify.getGUIDFromMenuItem = function(item) {
	return elgg.ajaxify.getURLFromMenuItem(item).match(/guid=(\d+)/)[1];
};

/**
 * Parse view name from the current URL of the page 
 *
 * @param value {String} Value to return if no name is available
 * @return viewname {String}
 */

elgg.ajaxify.getViewFromURL = function(value) {
	elgg.assertTypeOf('string', value);
	var viewname = '';
	//Parse the URL to get the viewname
	try {
		viewname = new RegExp(elgg.config.wwwroot+'(.+)').exec(window.location.toString())[1];
		//Strip off any parameters
		viewname = viewname.split('?')[0];
	} catch(exception) {
		viewname = value;
	}
	return viewname;
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
 *
 * The action can be relative to the current site ('name/of/action') or
 * the full URL of the action ('http://elgg.org/action/name/of/action').
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
 * @note If you intend to use the second field in the "verbose" way, you must
 * specify a callback method or the data parameter.  If you do not, elgg.action
 * will think you mean to send the second parameter as data.
 *
 * @note You do not have to add security tokens to this request.  Elgg does that
 * for you automatically.
 *
 * @see jQuery.ajax
 *
 * @param {String} action The action to call.
 * @param {Object} options
 * @return {XMLHttpRequest}
 */
elgg.action = function(action, options) {
	elgg.assertTypeOf('string', action);

	// support shortcut and full URLs
	// this will mangle URLs that aren't elgg actions.
	// Use post, get, or ajax for those.
	if (action.indexOf('action/') < 0) {
		action = 'action/' + action;
	}

	options = elgg.ajax.handleOptions(action, options);

	options.data = elgg.security.addToken(options.data);
	options.dataType = 'json';

	//Always display system messages after actions
	var custom_success = options.success || elgg.nullFunction;
	options.success = function(json, two, three, four) {
		if (json && json.system_messages) {
			//elgg.register_error(json.system_messages.error);
			//elgg.system_message(json.system_messages.success);
		}

		custom_success(json, two, three, four);
	};

	return elgg.post(options);
};

elgg.register_hook_handler('init', 'system', elgg.ajaxify.init);
