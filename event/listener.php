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
use phpbb\auth\auth;
use phpbb\config\config;
use phpbb\language\language;
use phpbb\template\template;

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

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \rmcgirr83\hidebots\event\listener */
	private $hidebots;

	/**
	* Constructor
	*
	* @param \phpbb\auth\auth					$auth			Auth object
	* @param \phpbb\config\config               $config         Config object
	* @param \phpbb\language\language			$language		Language object
	* @param \phpbb\template\template           $template       Template object
	* @param \rmcgirr83\hidebots\event\listener	$hidebots
	* @access public
	*/
	public function __construct(
		\phpbb\auth\auth $auth,
		\phpbb\config\config $config,
		\phpbb\language\language $language,
		\phpbb\template\template $template,
		\rmcgirr83\hidebots\event\listener $hidebots = null)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->language = $language;
		$this->template = $template;
		$this->hidebots = $hidebots;

		//variable we'll need later in the script
		$this->bots_online = '';
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
			'core.page_header_after'						=>	'page_header_after',
			'core.obtain_users_online_string_before_modify'	=>	'obtain_users_online_string_before_modify',
			'core.obtain_users_online_string_modify'		=>	'obtain_users_online_string_modify',
		];
	}

	/**
	* Change stats display on index page
	*
	* @param object $event The event object
	* @return void
	* @access public
	*/
	public function obtain_users_online_string_before_modify ($event)
	{
		if (!$this->adjust_whois_online($event['item_id']))
		{
			return;
		}

			$this->language->add_lang('common', 'rmcgirr83/separateusersandbots');

		$online_users = $event['online_users'];
		$user_online_link = $event['user_online_link'];
		$rowset = $event['rowset'];

			$bot_online_link = [];
			$bot_count = 0;
		foreach ($rowset as $row)
			{
			if ($row['user_type'] == USER_IGNORE && $row['user_id'] != ANONYMOUS)
					{
						++$bot_count;
				$bot_online_link[$row['username']] = $user_online_link[$row['user_id']];

				//adjust the event entries
				--$online_users['visible_online'];
				unset($user_online_link[$row['user_id']]);
				}
			}

		$online_botlist = '';

		// sort the bots by name
			ksort($bot_online_link);
			$online_botlist = implode(', ', $bot_online_link);

			if (!$online_botlist)
			{
				$online_botlist = $this->language->lang('NO_ONLINE_BOTS');
			}

			$online_botlist = $this->language->lang('BOTS_ONLINE') . ' ' . $online_botlist;

		$this->bots_online = $this->language->lang('ONLINE_BOT_COUNT', (int) $bot_count);

		$this->template->assign_vars(
			[
				'LOGGED_IN_BOT_LIST'	=> $online_botlist,]
		);

		$event['online_users'] = $online_users;
		$event['user_online_link'] = $user_online_link;
	}

	/**
	* Change language string
	*
	* @param object $event The event object
	* @return void
	* @access public
	*/
	public function obtain_users_online_string_modify ($event)
	{
		if (!$this->adjust_whois_online($event['item_id']))
		{
			return;
		}

		$l_online_users = $event['l_online_users'];
		$online_users = $event['online_users'];

			$visible_online = $this->language->lang('REG_USERS_TOTAL', (int) $online_users['visible_online']);
			$hidden_online = $this->language->lang('HIDDEN_USERS_TOTAL', (int) $online_users['hidden_online']);

			if ($this->config['load_online_guests'])
			{
				$guests_online = $this->language->lang('GUEST_USERS_TOTAL', (int) $online_users['guests_online']);
			$l_online_users = $this->language->lang('SUB_ONLINE_USERS_TOTAL_GUESTS', (int) $online_users['total_online'], $visible_online, $this->bots_online, $hidden_online, $guests_online);
			}
			else
			{
			$l_online_users = $this->language->lang('SUB_ONLINE_USERS_TOTAL', (int) $online_users['total_online'], $visible_online, $this->bots_online, $hidden_online);
			}

		$event['l_online_users'] = $l_online_users;
	}

	/**
	* Adjust template vars
	*
	* @param object 	$event 	The event object
	* @return void
	* @access public
	*/
	public function page_header_after ($event)
	{
		if (!$this->adjust_whois_online($event['item_id']))
		{
			return;
		}

		$this->template->assign_vars(
			[
				'S_DISPLAY_SEPARATEUSERSANDBOTS' => true,
				'S_DISPLAY_ONLINE_LIST' => false,]
		);
	}

	/**
	* should we adjust the who is online stats
	*
	* @param	int		item_id		event item_id
	* @return	bool
	* @access	private
	*/
	private function adjust_whois_online($item_id = 0)
	{
		//if the hide bots extension is installed or we aren't on the index page, do nothing
		$hidebots = (!$this->auth->acl_get('a_') && $this->hidebots !== null) ? true : false;
		if ($hidebots || $item_id != 0)
		{
			return false;
		}

		return true;
	}
}
