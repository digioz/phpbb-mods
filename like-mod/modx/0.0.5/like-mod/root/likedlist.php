    <?php
    define('IN_PHPBB', true);
    $phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
    $phpEx = substr(strrchr(__FILE__, '.'), 1);
    include($phpbb_root_path . 'common.' . $phpEx);
    include($phpbb_root_path . 'includes/functions_display.' . $phpEx);
    include($phpbb_root_path . 'includes/bbcode.' . $phpEx);

    $icons = $cache->obtain_icons();

    // Start session management
    $user->session_begin();
    $auth->acl($user->data);

    $user->setup();
    //$user->setup('viewtopic'); // for specific template

    // If Page access limited to logged in users also add this
    if ($user->data['user_id'] == ANONYMOUS)
    {
        login_box('', $user->lang['LOGIN']);
    }

    // Run Query to get list ----------------------------------------------------

    $start = request_var('start', 0);
    $result_set = get_liked_list($start);

    foreach ($result_set as $row)
                    {
                        $topic_id = $row['topic_id'];
                        $forum_id = $row['forum_id'];

                        // This will allow the style designer to output a different header
                        // or even separate the list of announcements from sticky and normal topics
                        $s_type_switch_test = ($row['topic_type'] == POST_ANNOUNCE || $row['topic_type'] == POST_GLOBAL) ? 1 : 0;

                        // Replies
                        $replies = ($auth->acl_get('m_approve', $forum_id)) ? $row['topic_replies_real'] : $row['topic_replies'];

                        if ($row['topic_status'] == ITEM_MOVED)
                        {
                            $topic_id = $row['topic_moved_id'];
                            $unread_topic = false;
                        }
                        else
                        {
                            $unread_topic = (isset($topic_tracking_info[$topic_id]) && $row['topic_last_post_time'] > $topic_tracking_info[$topic_id]) ? true : false;
                        }

                        // Get folder img, topic status/type related information
                        $folder_img = $folder_alt = $topic_type = '';
                        topic_status($row, $replies, $unread_topic, $folder_img, $folder_alt, $topic_type);

                        // Generate all the URIs ...
                        $view_topic_url = append_sid("{$phpbb_root_path}viewtopic.$phpEx", 'f=' . (($row['forum_id']) ? $row['forum_id'] : $forum_id) . '&amp;t=' . $topic_id);

                        $topic_unapproved = (!$row['topic_approved'] && $auth->acl_get('m_approve', $forum_id)) ? true : false;
                        $posts_unapproved = ($row['topic_approved'] && $row['topic_replies'] < $row['topic_replies_real'] && $auth->acl_get('m_approve', $forum_id)) ? true : false;
                        $u_mcp_queue = ($topic_unapproved || $posts_unapproved) ? append_sid("{$phpbb_root_path}mcp.$phpEx", 'i=queue&amp;mode=' . (($topic_unapproved) ? 'approve_details' : 'unapproved_posts') . "&amp;t=$topic_id", true, $user->session_id) : '';



                        $template->assign_block_vars('topicrow', array(
                            'FORUM_ID'                    => $forum_id,
                            'TOPIC_ID'                    => $topic_id,
                            'TOPIC_AUTHOR'                => get_username_string('username', $row['topic_poster'], $row['topic_first_poster_name']),
                            'TOPIC_AUTHOR_COLOUR'        => get_username_string('colour', $row['topic_poster'], $row['topic_first_poster_name']),
                            'TOPIC_AUTHOR_FULL'            => get_username_string('full', $row['topic_poster'], $row['topic_first_poster_name']),
                            'FIRST_POST_TIME'            => $user->format_date($row['topic_time']),
                            'LAST_POST_SUBJECT'            => censor_text($row['topic_last_post_subject']),
                            'LAST_POST_TIME'            => $user->format_date($row['topic_last_post_time']),
                            'LAST_VIEW_TIME'            => $user->format_date($row['topic_last_view_time']),
                            'LAST_POST_AUTHOR'            => get_username_string('username', $row['topic_last_poster_id'], $row['topic_last_poster_name'], $row['topic_last_poster_colour']),
                            'LAST_POST_AUTHOR_COLOUR'    => get_username_string('colour', $row['topic_last_poster_id'], $row['topic_last_poster_name'], $row['topic_last_poster_colour']),
                            'LAST_POST_AUTHOR_FULL'        => get_username_string('full', $row['topic_last_poster_id'], $row['topic_last_poster_name'], $row['topic_last_poster_colour']),
                            'LIKE_COUNT'                =>  $row['count'],

                            'PAGINATION'        => topic_generate_pagination($replies, $view_topic_url),
                            'REPLIES'            => $replies,
                            'VIEWS'                => $row['topic_views'],
                            'TOPIC_TITLE'        => censor_text($row['topic_title']),
                            'TOPIC_TYPE'        => $topic_type,

                            'TOPIC_FOLDER_IMG'        => $user->img($folder_img, $folder_alt),
                            'TOPIC_FOLDER_IMG_SRC'    => $user->img($folder_img, $folder_alt, false, '', 'src'),
                            'TOPIC_FOLDER_IMG_ALT'    => $user->lang[$folder_alt],
                            'TOPIC_ICON_IMG'        => (!empty($icons[$row['icon_id']])) ? $icons[$row['icon_id']]['img'] : '',
                            'TOPIC_ICON_IMG_WIDTH'    => (!empty($icons[$row['icon_id']])) ? $icons[$row['icon_id']]['width'] : '',
                            'TOPIC_ICON_IMG_HEIGHT'    => (!empty($icons[$row['icon_id']])) ? $icons[$row['icon_id']]['height'] : '',
                            'ATTACH_ICON_IMG'        => ($auth->acl_get('u_download') && $auth->acl_get('f_download', $forum_id) && $row['topic_attachment']) ? $user->img('icon_topic_attach', $user->lang['TOTAL_ATTACHMENTS']) : '',
                            'UNAPPROVED_IMG'        => ($topic_unapproved || $posts_unapproved) ? $user->img('icon_topic_unapproved', ($topic_unapproved) ? 'TOPIC_UNAPPROVED' : 'POSTS_UNAPPROVED') : '',

                           

                            'S_TOPIC_TYPE'            => $row['topic_type'],
                            'S_USER_POSTED'            => (isset($row['topic_posted']) && $row['topic_posted']) ? true : false,
                            'S_UNREAD_TOPIC'        => $unread_topic,
                            'S_TOPIC_REPORTED'        => (!empty($row['topic_reported']) && $auth->acl_get('m_report', $forum_id)) ? true : false,
                            'S_TOPIC_UNAPPROVED'    => $topic_unapproved,
                            'S_POSTS_UNAPPROVED'    => $posts_unapproved,
                            'S_HAS_POLL'            => (isset($row['poll_start']) && $row['poll_start'])  ? true : false,
                            'S_POST_ANNOUNCE'        => ($row['topic_type'] == POST_ANNOUNCE) ? true : false,
                            'S_POST_GLOBAL'            => ($row['topic_type'] == POST_GLOBAL) ? true : false,
                            'S_POST_STICKY'            => ($row['topic_type'] == POST_STICKY) ? true : false,
                            'S_TOPIC_LOCKED'        => ($row['topic_status'] == ITEM_LOCKED) ? true : false,
                            'S_TOPIC_MOVED'            => ($row['topic_status'] == ITEM_MOVED) ? true : false,

                            'U_NEWEST_POST'            => $view_topic_url . '&amp;view=unread#unread',
                            'U_LAST_POST'            => $view_topic_url . '&amp;p=' . $row['topic_last_post_id'] . '#p' . $row['topic_last_post_id'],
                            'U_LAST_POST_AUTHOR'    => get_username_string('profile', $row['topic_last_poster_id'], $row['topic_last_poster_name'], $row['topic_last_poster_colour']),
                            'U_TOPIC_AUTHOR'        => get_username_string('profile', $row['topic_poster'], $row['topic_first_poster_name']),
                            'U_VIEW_TOPIC'            => append_sid("{$phpbb_root_path}viewtopic.$phpEx", 'f=' . $forum_id . '&t=' . $topic_id),
                            'U_MCP_REPORT'            => append_sid("{$phpbb_root_path}mcp.$phpEx", 'i=reports&amp;mode=reports&amp;f=' . $forum_id . '&amp;t=' . $topic_id, true, $user->session_id),
                            'U_MCP_QUEUE'            => $u_mcp_queue,
                            'S_TOPIC_TYPE_SWITCH'    => ($s_type_switch == $s_type_switch_test) ? -1 : $s_type_switch_test)
                        );

                        $s_type_switch = ($row['topic_type'] == POST_ANNOUNCE || $row['topic_type'] == POST_GLOBAL) ? 1 : 0;
                    }
                    $template->assign_vars(array(
                        'PAGINATION'    => generate_pagination(append_sid("{$phpbb_root_path}likedlist.$phpEx"), $topics_count, $config['topics_per_page'], $start),
                        'PAGE_NUMBER'    => on_page($topics_count, $config['topics_per_page'], $start),
                        'TOTAL_TOPICS'    => (true) ? false : (($topics_count == 1) ? $user->lang['PTT_NUM_TOPIC'] : sprintf($user->lang['PTT_NUM_TOPICS'], $topics_count)),
                        'S_DISPLAY_SEARCHBOX'        => true,
                        )
                    );
               
    // END Query to get list -----------------------------------------------------

    page_header('Liked List');

    $template->set_filenames(array(
        'body' => 'likedlist_body.html',
    ));

    make_jumpbox(append_sid("{$phpbb_root_path}viewforum.$phpEx"));
    page_footer();
    ?>
