<?php
/**
*
* @package - Separate Users and Bots
*
* @copyright (c) 2020 RMcGirr83
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine
// Some characters you may want to copy&paste:
// ’ » “ ” …

$lang = array_merge($lang, array(
	'ONLINE_BOT_COUNT'	=> array(
		1	=> '%d bot',
		2	=> '%d bots',
	),
	// "... :: w registered, x bots, y hidden and z guests"
	'SUB_ONLINE_USERS_TOTAL_GUESTS'	=> array(
		1	=> 'In total there is <strong>%1$d</strong> user online :: %2$s, %3$s, %4$s and %5$s',
		2	=> 'In total there are <strong>%1$d</strong> users online :: %2$s, %3$s, %4$s and %5$s',
	),
	'NO_ONLINE_BOTS' => 'No bots',
	'BOTS_ONLINE' => 'Bots: ',
	'EXTENSION_REQUIRES_32'	=> 'This extension requires phpBB version 3.2. You must update your version of phpBB to use this extension.',
));
