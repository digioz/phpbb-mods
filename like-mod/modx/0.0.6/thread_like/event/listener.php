<?php
/**
*
*
*/

namespace digioz\thread_like\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class listener implements EventSubscriberInterface
{
	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	protected $request;
	protected $auth;
	protected $db;

	protected $root_path;
	protected $php_ext;

	protected $like_functions;

	public function __construct(
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\request\request $request,
		\phpbb\auth\auth $auth,
		\phpbb\db\driver\driver_interface $db,
		$root_path,
		$php_ext,
		\digioz\thread_like\core\thread_like $like_functions)
	{
		$this->template = $template;
		$this->user = $user;
		$this->request = $request;
		$this->auth = $auth;
		$this->db = $db;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
		$this->like_functions = $like_functions;
	}
	
	/**
	* Assign functions defined in this class to event listeners in the core
	*
	* @return array
	* @static
	* @access public
	*/
	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup' 				=> 'load_language',
			'core.viewtopic_assign_template_vars_before'	=> 'viewtopic_assign_vars',
			'core.viewtopic_get_post_data'			=> 'viewtopic_get_like_data',
			
		);
	}
	
	public function load_language($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'digioz/thread_like',
			'lang_set' => 'thread_like',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}
	
	public function viewtopic_assign_vars($event)
	{
		$this->template->assign_vars(array(
			'U_LIKE_TOPIC'		=> $this->user->data['is_registered'] ? append_sid($this->root_path . 'viewtopic.' . $this->php_ext, array('t' => $event['topic_id'], 'like' => 1, 'hash' => generate_link_hash("topic_" . $event['topic_id']))) : '',
			'U_LIKE_USERS_LIST'	=> $this->like_functions->get_like_list($event['forum_id'], $event['topic_id']),
		));
	}

	public function viewtopic_get_like_data($event)
	{
		$like_topic = (int)$this->request->variable('like', 0);
		if($like_topic && $this->user->data['is_registered'])
		{
			if(check_link_hash($this->request->variable('hash', ''), "topic_" . $event['topic_id']))
			{
				$this->like_functions->submit_like($event['forum_id'], $event['topic_id'], $this->user->data['user_id'], $this->user->data['username']);

				redirect(append_sid($this->root_path . 'viewtopic.' . $this->php_ext, array('f' => $event['forum_id'], 't' => $event['topic_id'])));
			}
		}
	}
}
