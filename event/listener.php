<?php
/**
*
* @package - Separate Users and Bots
*
* @copyright (c) 2020 RMcGirr83
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

namespace rmcgirr83\separateusersandbots\event;

/**
* @ignore
*/
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class listener implements EventSubscriberInterface
{
	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver */
	protected $db;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \rmcgirr83\hidebots\event\listener */
	private $hidebots;

	/**
	* Constructor
	*
	* @param \phpbb\auth\auth					$auth			Auth object
	* @param \phpbb\config\config               $config         Config object
	* @param \phpbb\db\driver\driver			$db				Database object
	* @param \phpbb\language\language			$language		Language object
	* @param \phpbb\template\template           $template       Template object
	* @param \phpbb\user                        $user           User object
	* @param \rmcgirr83\hidebots\event\listener	$hidebots
	* @access public
	*/
	public function __construct(
		\phpbb\auth\auth $auth,
		\phpbb\config\config $config,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\language\language $language,
		\phpbb\template\template $template,
		\phpbb\user $user,
		\rmcgirr83\hidebots\event\listener $hidebots = null)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->db = $db;
		$this->language = $language;
		$this->template = $template;
		$this->user = $user;
		$this->hidebots = $hidebots;

		//variables we'll need later in the script
		$this->display_online_list = false;
		//maybe we'll work on forum lists at a later date
		//need events in viewforum.html
		$this->item_id = 0;
		$this->item = '';
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
		return [
			'core.page_header'			=> 'page_header',
			'core.page_header_after'	=> 'display_bots',
		];
	}

	/**
	* Change stats display on index page
	*
	* @param object $event The event object
	* @return void
	* @access public
	*/
	public function display_bots ($event)
	{
		// we'll just run with the code that is already in core, mostly
		// only do this for the index page $this->item
		if ($this->config['load_online'] && $this->config['load_online_time'] && $this->display_online_list && $this->item_id === 0)
		{
			$this->language->add_lang('common', 'rmcgirr83/separateusersandbots');

			$l_online_users = $online_userlist = $online_botlist = '';

			$online_users = obtain_users_online($this->item_id, $this->item);

			$bot_user_ids = $this->get_bot_users();

			//a nub admin may have removed all bots so ensure we have an array with data
			if (count($bot_user_ids))
			{
				$bot_user_ids_keys = array_keys($bot_user_ids);
			}
			$bot_online_link = [];
			$bot_count = 0;
			if (isset($bot_user_ids_keys))
			{
				foreach ($online_users['online_users'] as $key => $value)
				{
					if (in_array($key, $bot_user_ids_keys))
					{
						++$bot_count;
						$bot_online_link[$bot_user_ids[$key]['username']] = get_username_string('no_profile', $bot_user_ids[$key]['user_id'], $bot_user_ids[$key]['username'], $bot_user_ids[$key]['user_colour']);
						unset($online_users['online_users'][$key]);
						$online_users['visible_online']--;
					}
				}
			}

			// sort the bots by key name
			ksort($bot_online_link);
			$online_botlist = implode(', ', $bot_online_link);

			if (!$online_botlist)
			{
				$online_botlist = $this->language->lang('NO_ONLINE_BOTS');
			}

			$online_botlist = $this->language->lang('BOTS_ONLINE') . ' ' . $online_botlist;

			$user_online_strings = obtain_users_online_string($online_users, $this->item_id);

			// Build online listing
			$visible_online = $this->language->lang('REG_USERS_TOTAL', (int) $online_users['visible_online']);
			$hidden_online = $this->language->lang('HIDDEN_USERS_TOTAL', (int) $online_users['hidden_online']);

			$bots_online = $this->language->lang('ONLINE_BOT_COUNT', (int) $bot_count);

			if ($this->config['load_online_guests'])
			{
				$guests_online = $this->language->lang('GUEST_USERS_TOTAL', (int) $online_users['guests_online']);
				$l_online_users = $this->language->lang('SUB_ONLINE_USERS_TOTAL_GUESTS', (int) $online_users['total_online'], $visible_online, $bots_online, $hidden_online, $guests_online);
			}
			else
			{
				$l_online_users = $this->language->lang('SUB_ONLINE_USERS_TOTAL', (int) $online_users['total_online'], $visible_online, $bots_online, $hidden_online);
			}

			$online_userlist = $user_online_strings['online_userlist'];
			$total_online_users = $online_users['total_online'];

			if ($total_online_users > $this->config['record_online_users'])
			{
				$this->config->set('record_online_users', $total_online_users, false);
				$this->config->set('record_online_date', time(), false);
			}

			$l_online_record = $this->language->lang('RECORD_ONLINE_USERS', (int) $this->config['record_online_users'], $this->user->format_date($this->config['record_online_date'], false, true));

			$l_online_time = $this->language->lang('VIEW_ONLINE_TIMES', (int) $this->config['load_online_time']);

			$this->template->assign_vars(
				[
					'TOTAL_USERS_ONLINE'	=> $l_online_users,
					'LOGGED_IN_USER_LIST'	=> $online_userlist,
					'LOGGED_IN_BOT_LIST'	=> $online_botlist,
					'L_ONLINE_EXPLAIN'		=> $l_online_time,
					'RECORD_USERS'			=> $l_online_record,

					'S_DISPLAY_SEPARATEUSERSANDBOTS' => true,
					'S_DISPLAY_ONLINE_LIST' => false,]
			);
		}
	}

	/**
	* retrieve display_online_list, item_id and item
	*
	* @param object 	$event 	The event object
	* @return void
	* @access public
	*/
	public function page_header ($event)
	{
		//only run this on the index page
		if ($event['display_online_list'] == true && $event['item_id'] === 0)
		{
			// if the hide bots extension is installed then do nothing as they won't show anyway
			$hidebots = (!$this->auth->acl_get('a_') && $this->hidebots !== null) ? true : false;
			if (!$hidebots)
			{
				$this->display_online_list = $event['display_online_list'];
				$this->item_id = $event['item_id'];
				$this->item = $event['item'];

				//set display_online_list to false, this reduces queries and peak memory usage
				$event['display_online_list'] = false;
			}
		}
	}

	/**
	* create an array of bots and their needed data
	*
	* @return array
	* @access public
	*/
	private function get_bot_users()
	{
		$sql = 'SELECT user_id, username, user_colour
			FROM ' . USERS_TABLE . '
			WHERE user_type = ' . USER_IGNORE . ' AND user_id <> ' . ANONYMOUS;
		$result = $this->db->sql_query($sql);

		$bot_user_ids = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$bot_user_ids[$row['user_id']] = ['user_id' => $row['user_id'], 'username' => $row['username'], 'user_colour' => $row['user_colour']];
		}
		$this->db->sql_freeresult($result);

		return $bot_user_ids;
	}
}
