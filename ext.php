<?php
/**
*
* @package - Separate Users and Bots
*
* @copyright (c) 2020 RMcGirr83
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

namespace rmcgirr83\separateusersandbots;

/**
* Extension class for custom enable/disable/purge actions
*/
class ext extends \phpbb\extension\base
{
	/** @var string Require phpBB 3.2.0 */
	const PHPBB_MIN_VERSION = '3.2.0';
	/**
	 * Enable extension if phpBB version requirement is met
	 *
	 * @return bool
	 * @access public
	 */
	public function is_enableable()
	{
		$config = $this->container->get('config');

		$enableable = (phpbb_version_compare($config['version'], self::PHPBB_MIN_VERSION, '>='));
		if (!$enableable)
		{
			$language = $this->container->get('language');
			$language->add_lang('common', 'rmcgirr83/separateusersandbots');
			trigger_error($language->lang('EXTENSION_REQUIRES_32'), E_USER_WARNING);
		}

		return $enableable;
	}
}
