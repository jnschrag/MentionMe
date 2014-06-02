<?php
/*
 * Plugin Name: MentionMe for MyBB 1.6.x
 * Copyright 2014 WildcardSearch
 * http://www.rantcentralforums.com
 *
 * this is the main plugin file
 */

// disallow direct access to this file for security reasons.
if(!defined('IN_MYBB'))
{
    die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');
}

// checked by other plugin files
define('IN_MENTIONME', true);

// add hooks
mentionme_initialize();

/*
 * mention_run()
 *
 * use a regex to either match a double-quoted mention (@"user name")
 * or just grab the @ symbol and everything after it that qualifies as a
 * word and is within the name length range
 *
 * @param - $message is the contents of the post
 * @return: (string) the message
 */
$plugins->add_hook('parse_message', 'mention_run');
function mention_run($message)
{
	global $mybb;

	// emails addresses cause issues, strip them before matching
	$email_regex = "#\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b#i";
	preg_match_all($email_regex, $message, $emails, PREG_SET_ORDER);
	$message = preg_replace($email_regex, "<mybb-email>\n", $message);

	// use function mention_filter_callback to repeatedly process mentions in the current post
	$message = preg_replace_callback('/@([\'|"|`])([^<]+?)\1|@([\w .]{' . (int) $mybb->settings['minnamelength'] . ',' . (int) $mybb->settings['maxnamelength'] . '})/u', 'mention_filter_callback', $message);

	// now restore the email addresses
	foreach($emails as $email)
	{
		$message = preg_replace("#\<mybb-email>\n?#", $email[0], $message, 1);
	}
	return $message;
}

/*
 * mention_filter_callback()
 *
 * matches any mentions of existing users in the post
 *
 * advanced search routines rely on
 * $mybb->settings['mention_advanced_matching'], if set to true
 * mention will match user names with spaces in them without
 * necessitating the use of double quotes.
 *
 * @param - $match is an array generated by preg_replace_callback()
 * @return: (string) the mention HTML
 */
function mention_filter_callback($match)
{
	global $db, $mybb;
	static $name_cache, $mycache;
	$name_parts = array();
	$shift_count = 0;

	$cache_changed = false;

	// cache names to reduce queries
	if($mycache instanceof MentionMeCache == false)
	{
		$mycache = MentionMeCache::get_instance();
	}

	if(!isset($name_cache))
	{
		$name_cache = $mycache->read('namecache');
	}

	// if the user entered the mention in quotes then it will be returned in
	// $match[2], if not it will be returned in $match[3]
	if(strlen(trim($match[2])) >= $mybb->settings['minnamelength'])
	{
		$orig_name = html_entity_decode($match[2]);
		$shift_count = 1;
	}
	elseif(strlen(trim($match[3])) >= $mybb->settings['minnamelength'])
	{
		$orig_name = html_entity_decode($match[3]);
	}
	else
	{
		return $match[0];
	}

	$match[0] = trim(strtolower($orig_name));

	// if the name is already in the cache . . .
	if(isset($name_cache[$match[0]]))
	{
		$left_over = substr($orig_name, strlen($match[0]));
		return mention_build($name_cache[$match[0]]) . $left_over;
	}

	// if the array was shifted then no quotes were used
	if($shift_count)
	{
		// no padding necessary
		$shift_pad = 0;

		// split the string into an array of words
		$name_parts = explode(' ', $match[0]);

		// add the first part
		$username_lower = $name_parts[0];

		// if the name part we have is shorter than the minimum user name length (set in ACP) we need to loop through all the name parts and keep adding them until we at least reach the minimum length
		while(strlen($username_lower) < $mybb->settings['minnamelength'] && !empty($name_parts))
		{
			// discard the first part (we have it stored)
			array_shift($name_parts);
			if(strlen($name_parts[0]) == 0)
			{
				// no more parts?
				break;
			}

			// if there is another part add it
			$username_lower .= ' ' . $name_parts[0];
		}

		if(strlen($username_lower) < $mybb->settings['minnamelength'])
		{
			return $orig_name;
		}
	}
	else
	{
		// @ and two double quotes
		$shift_pad = 3;

		// grab the entire match
		$username_lower = $match[0];
	}

	// if the name is already in the cache . . .
	if(isset($name_cache[$username_lower]))
	{
		// . . . simply return it and save the query
		//  restore any surrounding characters from the original match
		return mention_build($name_cache[$username_lower]) . substr($orig_name, strlen($username_lower) + $shift_pad);
	}

	// lookup the user name
	$user = mention_try_name($username_lower);

	// if the user name exists . . .
	if($user['uid'] != 0)
	{
		$cache_changed = true;

		// preserve any surrounding chars
		$left_over = substr($orig_name, strlen($user['username']) + $shift_pad);
	}
	// if no match and advanced matching is enabled . . .
	elseif($mybb->settings['mention_advanced_matching'])
	{
		// we've already checked the first part, discard it
		array_shift($name_parts);

		// if there are more parts and quotes weren't used
		if(empty($name_parts) || $shift_pad == 3 || strlen($name_parts[0]) <= 0)
		{
			// nothing else to try
			return "@{$orig_name}";
		}

		// start with the first part . . .
		$try_this = $username_lower;

		$all_good = false;

		// . . . loop through each part and try them in serial
		foreach($name_parts as $val)
		{
			// add the next part
			$try_this .= ' ' . $val;

			// check the cache for a match to save a query
			if(isset($name_cache[$try_this]))
			{
				// preserve any surrounding chars from the original match
				$left_over = substr($orig_name, strlen($try_this) + $shift_pad);
				return mention_build($name_cache[$try_this]) . $left_over;
			}

			// check the db
			$user = mention_try_name($try_this);

			// if there is a match . . .
			if((int) $user['uid'] == 0)
			{
				continue;
			}

			// cache the user name HTML
			$username_lower = strtolower($user['username']);

			// preserve any surrounding chars from the original match
			$left_over = substr($orig_name, strlen($user['username']) + $shift_pad);

			// and gtfo
			$all_good = true;
			$cache_changed = true;
			break;
		}

		if(!$all_good)
		{
			// still no matches?
			return "@{$orig_name}";
		}
	}
	else
	{
		// no match found and advanced matching is disabled
		return "@{$orig_name}";
	}

	// store the mention
	$name_cache[$username_lower] = $user;

	// if we had to query for this user's info then update the cache
	if($cache_changed)
	{
		$mycache->update('namecache', $name_cache);
	}

	// and return the mention
	return mention_build($user) . $left_over;
}

/*
 * mention_build()
 *
 * build  mention from user info
 *
 * @param - $user - (array) an associative array of user info
 * @return: (string) the mention HTML
 */
function mention_build($user)
{
	if(!is_array($user) || empty($user) || strlen($user['username']) == 0)
	{
		return false;
	}

	// set up the user name link so that it displays correctly for the display group of the user
	$username = format_name(htmlspecialchars_uni($user['username']), $user['usergroup'], $user['displaygroup']);
	$url = get_profile_link($user['uid']);

	// the HTML id property is used to store the uid of the mentioned user for MyAlerts (if installed)
	return <<<EOF
@<a id="mention_{$user['uid']}" href="{$url}">{$username}</a>
EOF;
}

/*
 * mention_try_name()
 *
 * searches the db for a user by name
 *
 * return an array containing user id, user name, user group and display group upon success
 * return false on failure
 *
 * @param - $username is a string containing the user name to try
 * @return: (array) the user data or (bool) false on no match
 */
function mention_try_name($username = '')
{
	/**
	 * create another name cache here to save queries if names
	 * with spaces are used more than once in the same post
	 */
	static $name_list;

	if(!is_array($name_list))
	{
		$name_list = array();
	}

	$username = strtolower($username);

	// no user name supplied
	if(!$username)
	{
		return false;
	}

	// if the name is in this cache (has been searched for before)
	if($name_list[$username])
	{
		// . . . just return the data and save the query
		return $name_list[$username];
	}

	global $db;

	// query the db
	$query = $db->simple_select('users', 'uid, username, usergroup, displaygroup, additionalgroups', "LOWER(username)='{$db->escape_string($username)}'", array('limit' => 1));

	// result?
	if($db->num_rows($query) !== 1)
	{
		// no matches
		return false;
	}

	// cache the name
	$name_list[$username] = $db->fetch_array($query);

	// and return it
	return $name_list[$username];
}

/*
 * mention_mycode_add_codebuttons()
 *
 * add our code button's hover text language and insert our script (we don't
 * have to check settings because the hook will not be added if the setting for
 * adding a code button is set to no)
 *
 * @param - $edit_lang - (array) an unindexed array of language variable
 * names for the editor
 * @return: (array) the array of button language
 */
function mention_mycode_add_codebuttons($edit_lang)
{
	global $lang, $mybb;

	if($mybb->settings['mention_minify_js'])
	{
		$min = '.min';
	}
	$lang->mentionme_codebutton = <<<EOF
<script type="text/javascript" src="jscripts/MentionMe/mention_codebutton{$min}.js"></script>

EOF;

	$edit_lang[] = 'editor_mention';
	return $edit_lang;
}

/*
 * mention_misc_start()
 *
 * currently only here to display the mention popup for the code button
 *
 * @return: n/a
 */
function mention_misc_start()
{
	global $mybb;

	if($mybb->input['action'] != 'mentionme')
	{
		// not our time
		return;
	}

	if($mybb->input['mode'] == 'popup')
	{
		// if we have any input
		if(trim($mybb->input['username']))
		{
			// just insert it with the 'safe' syntax, close the window and get out
			die(<<<EOF
<script type="text/javascript">
<!--
	opener.clickableEditor.performInsert('@"{$mybb->input['username']}" ');
	window.close();
// -->
</script>
EOF
			);
		}

		// show the popup
		global $templates, $lang, $headerinclude;
		eval("\$page = \"" . $templates->get('mentionme_popup') . "\";");
		output_page($page);
		exit;
	}
}

/*
 * mentionme_initialize()
 *
 * add hooks and include functions only when appropriate
 *
 * @return: n/a
 */
function mentionme_initialize()
{
	global $mybb, $plugins, $lang;

	if(!class_exists('MentionMeCache'))
	{
		require_once MYBB_ROOT . 'inc/plugins/MentionMe/classes/MentionMeCache.php';
	}

	// load install routines and force enable script only if in ACP
	if(defined('IN_ADMINCP'))
	{
		switch($mybb->input['module'])
		{
			case 'config-plugins':
				require_once MYBB_ROOT . 'inc/plugins/MentionMe/mention_install.php';
				$plugins->add_hook('admin_load', 'mention_admin_load');
				break;
		}
		return;
	}

	if(!$lang->mention)
	{
		$lang->load('mention');
	}

	// load the alerts functions only if MyAlerts and mention alerts are enabled
	if($mybb->settings['myalerts_enabled'] && $mybb->settings['myalerts_alert_mention'])
	{
		require_once MYBB_ROOT . 'inc/plugins/MentionMe/mention_alerts.php';
	}

	// only add the code button if the setting is on and we are viewing a page that use an editor
	if($mybb->settings['mention_add_codebutton'] && in_array(THIS_SCRIPT, array('newthread.php', 'newreply.php', 'editpost.php', 'private.php', 'usercp.php', 'modcp.php', 'calendar.php')))
	{
		$add_hook = true;
		switch(THIS_SCRIPT) {
		case 'usercp.php':
			$add_hook = ($mybb->input['action'] == 'editsig');
			break;
		case 'private.php':
			$add_hook = ($mybb->input['action'] == 'send');
			break;
		case 'modcp.php':
			$add_hook = (in_array($mybb->input['action'], array('edit_announcement', 'new_announcement', 'editprofile')));
			break;
		case 'calendar.php':
			$add_hook = (in_array($mybb->input['action'], array('addevent', 'editevent')));
			break;
		}

		if($add_hook)
		{
			$plugins->add_hook('mycode_add_codebuttons', 'mention_mycode_add_codebuttons');
		}
	}

	if($mybb->settings['mention_auto_complete'] && in_array(THIS_SCRIPT, array('newthread.php', 'newreply.php', 'editpost.php', 'private.php', 'usercp.php', 'modcp.php', 'calendar.php', 'showthread.php')))
	{
		$add_js = true;
		switch(THIS_SCRIPT) {
		case 'usercp.php':
			$add_js = ($mybb->input['action'] == 'editsig');
			break;
		case 'private.php':
			$add_js = ($mybb->input['action'] == 'send');
			break;
		case 'modcp.php':
			$add_js = (in_array($mybb->input['action'], array('edit_announcement', 'new_announcement', 'editprofile')));
			break;
		case 'calendar.php':
			$add_js = (in_array($mybb->input['action'], array('addevent', 'editevent')));
			break;
		}

		if($mybb->settings['mention_minify_js'])
		{
			$min = '.min';
		}

		if(file_exists(MYBB_ROOT . 'jscripts/MentionMe/mention_autocomplete.debug.js'))
		{
			$debug_script = <<<EOF

<script type="text/javascript" src="jscripts/MentionMe/mention_autocomplete.debug.js"></script>
EOF;
		}

		if($add_js)
		{
			global $mention_autocomplete;
			$mention_autocomplete = <<<EOF
<!-- MentionMe Autocomplete Scripts -->
<script type="text/javascript" src="jscripts/js_cursor_position/selection_range.js"></script>
<script type="text/javascript" src="jscripts/js_cursor_position/string_splitter.js"></script>
<script type="text/javascript" src="jscripts/js_cursor_position/cursor_position.js"></script>
<script type="text/javascript" src="jscripts/MentionMe/mention_autocomplete{$min}.js"></script>{$debug_script}
<script type="text/javascript">
<!--
	MentionMe.autoComplete.setup({
		lang: {
			loading: '{$lang->mention_autocomplete_loading}',
			instructions: '{$lang->mention_autocomplete_instructions}',
		},
		minLength: {$mybb->settings['minnamelength']},
		maxLength: {$mybb->settings['maxnamelength']},
	});
// -->
</script>
EOF;
		}
	}

	// only add the misc hook if we are viewing the popup (or POSTing)
	if(THIS_SCRIPT == 'misc.php' && $mybb->input['action'] == 'mentionme')
	{
		$plugins->add_hook('misc_start', 'mention_misc_start');
	}

	// only add the showthread hook if we are there and we are adding a postbit multi-mention button
	if(THIS_SCRIPT == 'showthread.php' && $mybb->settings['mention_add_postbit_button'])
	{
		$plugins->add_hook('showthread_start', 'mention_showthread_start');
		$plugins->add_hook('postbit', 'mention_postbit');
	}

	// only add the xmlhttp hook if required and we are adding a postbit multi-mention button or autocomplete is on
	if(THIS_SCRIPT == 'xmlhttp.php' && $mybb->input['action'] == 'mentionme')
	{
		$plugins->add_hook('xmlhttp', 'mention_xmlhttp');
	}
}

/*
 * mention_postbit()
 *
 * build the multi-mention postbit button
 *
 * @param - $post - (array) passed from pluginSystem::run_hooks,
 * an array of the post data
 * @return: n/a
 */
function mention_postbit(&$post)
{
	global $mybb, $theme, $lang, $templates, $forumpermissions,
	$fid, $post_type, $thread, $forum;

	if($mybb->settings['quickreply'] == 0 ||
	   $mybb->user['suspendposting'] == 1 ||
	   $forumpermissions['canpostreplys'] == 0 ||
	   ($thread['closed'] == 1 && !is_moderator($fid)) ||
	   $forum['open'] == 0 ||
	   $post_type ||
	   $mybb->user['uid'] == $post['uid']) {
		return;
	}

	// tailor JS to postbit setting
	$js = "javascript:MentionMe.insert('{$post['username']}');";
	if($mybb->settings['mention_multiple'])
	{
		$js = "javascript:MentionMe.multi.mention({$post['pid']});";
	}

	if($mybb->settings['mention_css_buttons'])
	{
		eval("\$post['button_mention'] = \"" . $templates->get('mentionme_postbit_button_css') . "\";");
	}
	else
	{
		eval("\$post['button_mention'] = \"" . $templates->get('mentionme_postbit_button') . "\";");
	}
}

/*
 * mention_xmlhttp()
 *
 * handles AJAX for MentionMe
 *
 * @return: n/a
 */
function mention_xmlhttp()
{
	global $mybb;

	$ajax_function = "mention_xmlhttp_{$mybb->input['mode']}";
	if($mybb->input['action'] != 'mentionme' || !function_exists($ajax_function))
	{
		return;
	}

	$ajax_function();
	return;
}

/*
 * mention_xmlhttp_name_search()
 *
 * search for usernames beginning with search text and echo JSON
 *
 * @return: n/a
 */
function mention_xmlhttp_name_search()
{
	global $mybb, $db, $cache;

	if(!$mybb->input['search'])
	{
		exit;
	}

	$name = $db->escape_string(trim($mybb->input['search']));
	$name = strtr($name, array('%' => '=%', '=' => '==', '_' => '=_'));
	$query = $db->simple_select('users', 'username', "username LIKE '{$name}%' ESCAPE '='");

	if($db->num_rows($query) == 0)
	{
		exit;
	}

	$json = array();
	while($username = $db->fetch_field($query, 'username'))
	{
		$json[strtolower($username)] = $username;
	}

	// send our headers.
	header('Content-type: application/json');
	echo(json_encode($json));
	exit;
}

/*
 * mention_xmlhttp_get_name_cache()
 *
 * retrieve the name cache and echo JSON
 *
 * @return: n/a
 */
function mention_xmlhttp_get_name_cache()
{
	$name_cache = MentionMeCache::get_instance()->read('namecache');

	$json = array();
	foreach($name_cache as $key => $data)
	{
		$json[$key] = $data['username'];
	}

	// send our headers.
	header('Content-type: application/json');
	echo(json_encode($json));
	exit;
}

/*
 * mention_xmlhttp_get_multi_mentioned()
 *
 * retrieve the mentioned user names and echo HTML
 *
 * @return: n/a
 */
function mention_xmlhttp_get_multi_mentioned()
{
	global $mybb, $db, $charset;

	// if the cookie does not exist, exit
	if(!array_key_exists('multi_mention', $mybb->cookies))
	{
		exit;
	}
	// divide up the cookie using our delimiter
	$multi_mentioned = explode('|', $mybb->cookies['multi_mention']);

	// no values - exit
	if(!is_array($multi_mentioned))
	{
		exit;
	}

	// loop through each post ID and sanitize it before querying
	foreach($multi_mentioned as $post)
	{
		$mentioned_posts[$post] = (int) $post;
	}

	// join the post IDs back together
	$mentioned_posts = implode(',', $mentioned_posts);

	// fetch unviewable forums
	$unviewable_forums = get_unviewable_forums();
	if($unviewable_forums)
	{
		$unviewable_forums = " AND fid NOT IN ({$unviewable_forums})";
	}
	$message = '';

	// are we loading all mentioned posts or only those not in the current thread?
	$from_tid = '';
	if(!$mybb->input['load_all'])
	{
		$tid = (int) $mybb->input['tid'];
		$from_tid = "tid != '{$tid}' AND ";
	}

	// query for any posts in the list which are not within the specified thread
	$mentioned = array();
	$query = $db->simple_select('posts', 'username, fid, visible', "{$from_tid}pid IN ({$mentioned_posts}){$unviewable_forums}", array("order_by" => 'dateline'));
	while($mentioned_post = $db->fetch_array($query))
	{
		if(!is_moderator($mentioned_post['fid']) && $mentioned_post['visible'] == 0)
		{
			continue;
		}

		if($mentioned[$mentioned_post['username']] != true)
		{
			$message .= "@\"{$mentioned_post['username']}\" ";
			$mentioned[$mentioned_post['username']] = true;
		}
	}

	// send our headers.
	header("Content-type: text/plain; charset={$charset}");
	echo $message;
	exit;
}

/*
 * mention_showthread_start()
 *
 * add the script, the Quick Reply notification <div> and the hidden input
 *
 * @return: n/a
 */
function mention_showthread_start()
{
	global $mybb, $mention_script, $mention_quickreply,
	$mentioned_ids, $lang, $tid, $templates;

	// we only need the extra JS and Quick Reply additions if we are allowing multiple mentions
	if($mybb->settings['mention_multiple'])
	{
		$multi = '_multi';
		eval("\$mention_quickreply = \"" . $templates->get('mentionme_quickreply_notice') . "\";");

		$mentioned_ids = <<<EOF

	<input type="hidden" name="mentioned_ids" value="" id="mentioned_ids" />
EOF;
	}

	if($mybb->settings['mention_minify_js'])
	{
		$min = '.min';
	}

	$mention_script = <<<EOF
<script type="text/javascript" src="jscripts/MentionMe/mention_thread{$multi}{$min}.js"></script>

EOF;
}

?>
