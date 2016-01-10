<?php

namespace digioz\thread_like\migrations;

class basic_schema extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'likes');
	}

	static public function depends_on()
	{
		return array(
			'\phpbb\db\migration\data\v310\rc4',
		);
	}

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'likes' => array(
					'COLUMNS' => array(
						'like_id' => array('UINT', null, 'auto_increment'),
						'forum_id' => array('UINT', null),
						'topic_id' => array('UINT', null),
						'user_id' => array('UINT', null),
						'username' => array('VCHAR_UNI:255', ''),
					),
					'PRIMARY_KEY' => array('like_id'),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'likes',
			),
		);
	}
}

