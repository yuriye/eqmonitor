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
	private $db = null;

	function __construct()
	{
		parent::__construct();
		$this->db = $this->getDbo();
	}

	function getItem()
	{
		if ($this->timeToRefillData(60))
		{
			$this->copyDataFromEQWS();
		}

		$query = 'SELECT * FROM #__eqm_queue_item ORDER BY filial, queued_at';
		$this->db->setQuery($query);
		$row = $this->db->loadObjectList();

		return $row;
	}

	/**
	 * true - yes
	 *
	 * $period: period between now and last records made in seconds
	 *
	 * @since 1.0
	 *
	 */
	function timeToRefillData($period = 60)
	{
		if (!is_numeric($period) || $period == 0)
		{
			return true;
		}

		$query = 'SELECT queued_at FROM #__eqm_queue_item ORDER BY filial, queued_at LIMIT 1';
		$this->db->setQuery($query);
		$rows = $this->db->loadObjectList();
		if (count($rows) == 0) return true;

		$fmt  = 'Y-m-d h:i:s';
		$tz   = new DateTimeZone('+12:00');
		$diff = 0;
		foreach ($rows as $row)
		{
			$dt   = DateTime::createFromFormat($fmt, $row->queued_at, $tz);
			$diff = (new DateTime())->getTimestamp() - $dt->getTimestamp();
			break;
		}
		if ($period < $diff)
		{
			return true;
		}

		return false;
	}

	function copyDataFromEQWS()
	{
		copyEQItems();
		copyCabStates();

	}

	function copyCabStates()
	{
//		$this->db->setQuery('DELETE FROM #__eqm_filial_cabs')->execute();
		$this->db->truncateTable('#__eqm_filial_cabs');

		$query = 'SELECT * FROM #__eqm_filial ORDER BY filial';
		$rows  = $this->db->setQuery($query)->loadObjectList();

		foreach ($rows as $row)
		{
			$contents    = file_get_contents("http://aismfc.mfc.local/infobox/getwindow/byqueue/$row->uuid?queueUuid=all");
			$contentObjs = json_decode($contents);

			foreach ($contentObjs as $win)
			{
				$winRecord = new stdClass();
				$winRecord->uuid = $row->uuid;
				$winRecord->filial = $row->filial;
				$winRecord->cab_num = $win->cabNum;
				$winRecord->status = $win->status;
				$winRecord->state = $win->state;
				$winRecord->count_of_served = $win->countOfServed;
				$winRecord->average_service_time = $win->averageServiceTime;
				$winRecord->pause_starttime = $win->pauseStartTime;
				$winRecord->pause_count = $win->pauseCount;
				$winRecord->dayoff = $win->dayoff;
				$this->db->insertObject('#__eqm_filial_cabs', $winRecord);
			}
		}
	}

	function copyEQItems()
	{
//		$this->db->setQuery('DELETE FROM #__eqm_queue_item')->execute();
		$this->db->truncateTable('#__eqm_queue_item');

		$query = 'SELECT * FROM #__eqm_filial ORDER BY filial';
		$rows  = $this->db->setQuery($query)->loadObjectList();

		$fmt = 'M d, Y h:i:s A';
		$tz  = new DateTimeZone('+12:00');

		foreach ($rows as $row)
		{
			$contents    = file_get_contents("http://aismfc.mfc.local/infobox/getqueue/$row->uuid?queueUuid=all");
			$contentObjs = json_decode($contents);

			foreach ($contentObjs as $ticket)
			{
				$queueItem = new stdClass();

				$prefix = '';
				switch ($ticket->prefix)
				{
					case('ru_a'):
						$prefix = 'A';
						break;
					case('ru_v'):
						$prefix = 'V';
						break;
					case('ru_p'):
						$prefix = 'P';
						break;
					case('ru_k'):
						$prefix = 'K';
						break;
				}

				$seconds = intval($ticket->startTime / 1000);
				$dt      = new DateTime();
				$dt->setTimezone($tz);
				$dt->setTimestamp($seconds);
				$queueItem->uuid     = $row->uuid;
				$queueItem->filial   = $row->filial;
				$queueItem->ticket   = $prefix . $ticket->clientNum;
				$queueItem->priority = $ticket->priority;
				$dtQueued            = DateTime::createFromFormat($fmt, $ticket->regTime, $tz);
				echo '$dtQueued=';
				print_r($dtQueued);
				echo "<br>";
				$queueItem->queued_at = JFactory::getDate($dtQueued->getTimestamp())->toSql(true);
				echo "<br>";
				echo '$queueItem->queued_at=';
				print_r($queueItem->queued_at);
				echo "<br>";
				$queueItem->waiting_time    = 0;
				$queueItem->queue           = '';
				$queueItem->call_time       = $dt;
				$queueItem->service_name    = $ticket->serviceName;
				$queueItem->status          = $ticket->status;
				$queueItem->window_number   = $ticket->cabNum;
				$queueItem->number_of_cases = $ticket->additionalInfo->countCases;
//				$queueItem->created_on      = JFactory::getDate((new DateTime())->getTimestamp(), $tz);
				$queueItem->created_on = (new JDate('now'))->setTimezone($tz)->toSQL();
				echo "<br>";
				echo '$queueItem->created_on=';
				print_r($queueItem->created_on);
				echo "<br>";

				$this->db->insertObject('#__eqm_queue_item', $queueItem);
			}
		}
	}


}
