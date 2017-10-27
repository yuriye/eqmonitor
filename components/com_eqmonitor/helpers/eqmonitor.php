<?php
/**
 * @package    eqmonitor
 *
 * @author     eliseevya <your@email.com>
 * @copyright  A copyright
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       http://your.url.com
 */

defined('_JEXEC') or die;

/**
 * Eqmonitor Controller.
 *
 * @package  eqmonitor
 * @since    1.0
 */
class EqmonitorSiteHelper
{

	function copyDataFromEQWS()
	{
		$db = JFactory::getDbo();
		$delquery = 'truncate #__eqm_queue_item;';
		$db->setQuery($delquery)->execute();
		$query = 'select * from #__eqm_filial order by filial';
		$rows = $db->setQuery($query)->loadObjectList();

		foreach ($rows as $row) {
//			$json = $row->uuid
		}


	}


}
