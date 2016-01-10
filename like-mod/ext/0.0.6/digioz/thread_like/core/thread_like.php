<?php

/**
*
* @package Like Topic Mod
* @author DigiOz Multimedia, Inc.
* @copyright (c) 2016
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

namespace digioz\thread_like\core;

class thread_like
{
	protected $db;
	protected $likes_table;
	protected $root_path;
	protected $php_ext;

	public function __construct(
		\phpbb\db\driver\driver_interface $db,
		$likes_table,
		$root_path,
		$php_ext)
	{
		$this->db = $db;
		$this->likes_table = $likes_table;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
	}

	public function get_like_exists($forum_id, $topic_id, $user_id)
	{
		$sql = "SELECT COUNT(like_id) AS like_id_count
			FROM " . $this->likes_table . "
			WHERE forum_id = $forum_id
			AND topic_id = $topic_id
			AND user_id = $user_id";

		$result = $this->db->sql_query($sql);
		$like_count = (int)$this->db->sql_fetchfield('like_id_count', false, $result);
		
		return $like_count > 0;
	}

	public function submit_like($forum_id, $topic_id, $user_id, $username)
	{
		if(!$this->get_like_exists($forum_id, $topic_id, $user_id))
		{
			$data = array(
				'forum_id'	=> $forum_id,
				'topic_id'	=> $topic_id,
				'user_id'	=> $user_id,
				'username'	=> $username,
			);

			$sql = "INSERT INTO " . $this->likes_table .
				$this->db->sql_build_array('INSERT', $data);

			$this->db->sql_query($sql);
		}
	}

	public function get_like_list($forum_id, $topic_id)
	{
		$like_list = '';

		$sql = "SELECT A.user_id, A.username, B.user_colour 
			FROM " . $this->likes_table . " 
			AS A, " . USERS_TABLE . " AS B 
			WHERE forum_id = $forum_id
			AND topic_id = $topic_id
			AND A.user_id = B.user_id
			ORDER BY username ASC";

		$result = $this->db->sql_query($sql);
		$result_set = $this->db->sql_fetchrowset($result);
		
		for($i = 0; $i < sizeof($result_set); $i++)
		{
			$like_list .= '<a href="' . append_sid($this->root_path . 'memberlist.' . $this->php_ext, array('mode' => 'viewprofile', 'u' => $result_set[$i]['user_id'])) . '"><span style="color: #' . $result_set[$i]['user_colour'] . '">' . $result_set[$i]['username'] . '</span></a>';

			if($i < sizeof($result_set) - 1)
			{
				$like_list .= ", ";
			}
		}

		$this->db->sql_freeresult($result);
		return $like_list;
	}
}


