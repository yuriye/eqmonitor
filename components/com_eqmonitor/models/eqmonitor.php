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
 * Eqmonitor model.
 *
 * @package  eqmonitor
 * @since    1.0
 */
class EqmonitorModelEqmonitor extends JModelLegacy
{
	function getItem()
	{
		// Initialize variables.
		$db = $this->getDbo();
//		$db = JFactory::getDbo();
		/*$query = $db->getQuery(true);

		// Create the base select statement.
		$query->select($db->quoteName(array('id', 'uuid', 'filial')))
			->from($db->quoteName('#__eqm_queue_item'))
			->order($db->escape('filial, queued_at') . ' asc');*/
		$query = 'select * from #__eqm_queue_item order by filial, queued_at';
		$db->setQuery($query);
		$row = $db->loadObjectList();
		return $row;
	}
}
