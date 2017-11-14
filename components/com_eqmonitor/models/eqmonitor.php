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

		return $this->createRows();
	}

	function createRows()
	{
		$query = "SELECT * FROM #__eqm_filial ORDER BY filial";
		$this->db->setQuery($query);
		$dbFilials = $this->db->loadObjectList();
		$filialList = array();
		foreach ($dbFilials as $dbFilial) {
			$filial                               = new stdClass();
			$filial->filial                       = $dbFilial->filial;
			$filial->cabs                         = $dbFilial->cabs;
			$filial->status                       = array();
			$filial->state                        = array();
			$filial->countOfServed                = 0;
			$filial->totalServiceTime             = 0;
			$filial->totalWaitingTime             = 0;
			$filial->numberOfCases = 0;
			$filial->clientsServing = 0;
			$filial->clientsWaiting = 0;
			$filial->state['ON'] = 0;
			$filial->state['PAUSE'] = 0;
			$filial->state['OFF'] = 0;

			$filialList[$dbFilial->filial] = $filial;
		}

		$query      = 'SELECT * FROM #__eqm_filial_cabs ORDER BY filial';
		$this->db->setQuery($query);
		$dbCabList = $this->db->loadObjectList();

		//collecting statistics in $dbFilialList for the each filial
		foreach ($dbCabList as $dbCab)
		{
			$obj = null;
			if ($dbCab->dayoff === TRUE) continue; //Если сегодня окно не работает - пропуск

			$filial = $filialList[$dbCab->filial];

			if (!key_exists($dbCab->status, $filial->status))
				$filial->status[$dbCab->status] = 0;
			$filial->status[$dbCab->status]++;

			if (!key_exists($dbCab->state, $filial->state))
				$filial->state[$dbCab->state] = 0;
			$filial->state[$dbCab->state]++;

			//Do not process if the object '$filial' is in an inconsistent state.
			if ('OFF' === $filial->state[$dbCab->state] && 'FREE' !== $filial->status[$dbCab->status]) continue;

			$filial->countOfServed    += $dbCab->count_of_served;
			$filial->totalServiceTime += $dbCab->average_service_time * $dbCab->count_of_served;
		}

		$query = 'SELECT * FROM #__eqm_queue_item ORDER BY filial';
		$this->db->setQuery($query);
		$dbEqItemList = $this->db->loadObjectList();

		foreach ($dbEqItemList as $dbEqItem)
		{
			$eqItem = null;
			if (!isset($filialList[$dbEqItem->filial])) continue;
			$filial = $filialList[$dbEqItem->filial];

			if ('WAIT' == $dbEqItem->status)
			{
				if (!$dbEqItem->remote_reg)
				{
					$filial->clientsWaiting++;
				}
			} else {
				$filial->clientsServing++;
			}
			$filial->numberOfCases += $dbEqItem->number_of_cases;
			$filial->totalWaitingTime += $dbEqItem->waiting_time;

		}

		foreach ($filialList as $filial)
		{
			if ($filial->countOfServed == 0)
			{
				$filial->averageServiceTime = 0;
				continue;
			}
			$filial->averageServiceTime = $filial->totalServiceTime / $filial->countOfServed;
			$filial->averageServiceTime = round($filial->averageServiceTime / 60000);
			$filial->averageWaitingTime = round(($filial->totalWaitingTime / $filial->clientsServing) / 60);
		}

		return $filialList;
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

		$query = 'SELECT created_on FROM #__eqm_queue_item ORDER BY filial, queued_at LIMIT 1';
		$this->db->setQuery($query);
		$rows = $this->db->loadObjectList();
		if (count($rows) == 0) return true;

		$diff = 0;
		foreach ($rows as $row)
		{
			$now        = (new JDate('now'))->getTimestamp();
			$created_on = $row->created_on;
			$diff       = $now - $created_on;
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
		$this->copyEQItems();
		$this->copyCabStates();
	}

	function copyCabStates()
	{
//		$this->db->setQuery('DELETE FROM #__eqm_filial_cabs')->execute();
		$this->db->truncateTable('#__eqm_filial_cabs');

		$query = 'SELECT * FROM #__eqm_filial ORDER BY filial';
		$rows  = $this->db->setQuery($query)->loadObjectList();

		foreach ($rows as $row)
		{
			//$contents    = file_get_contents("http://aismfc.mfc.local/infobox/getwindow/byqueue/$row->uuid?queueUuid=all");
			$contents    = file_get_contents("http://10.200.202.11:8080/infobox/getwindow/byqueue/$row->uuid?queueUuid=all");
			$contentObjs = json_decode($contents);

			foreach ($contentObjs as $win)
			{
				$winRecord          = new stdClass();
				$winRecord->uuid    = $row->uuid;
				$winRecord->filial  = $row->filial;
				$winRecord->cab_num = $win->cabNum;
				switch ($win->status)
				{
					case('FREE'):
						$winRecord->status = 'FREE';
						break;
					case('SERVED'):
						$winRecord->status = 'SERVED';
						break;
					case('CALL'):
						$winRecord->status = 'SERVED';
						break;
					case('PAUSE'):
						$winRecord->status = 'PAUSE';
						break;
					default:
						$winRecord->status = $win->status;
				}
				switch ($win->state)
				{
					case('ON'):
						$winRecord->state = 'ON';
						break;
					case('OFF'):
						$winRecord->state = 'OFF';
						break;
					case('PAUSE'):
						$winRecord->state = 'PAUSE';
						break;

					default:
						$winRecord->state = 'OFF';
				}
				$winRecord->count_of_served      = $win->countOfServed;
				$winRecord->average_service_time = $win->averageServiceTime;
				$winRecord->pause_starttime      = $win->pauseStartTime;
				$winRecord->pause_count          = $win->pauseCount;
				$winRecord->dayoff               = $win->dayoff;
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
//		$tz  = new DateTimeZone('+12:00');
		$tz =  	new DateTimeZone('Asia/Anadyr');
		foreach ($rows as $row)
		{
			//$contents    = file_get_contents("http://aismfc.mfc.local/infobox/getqueue/$row->uuid?queueUuid=all");
			$contents    = file_get_contents("http://10.200.202.11:8080/infobox/getqueue/$row->uuid?queueUuid=all");
			if(!$contents) {
				echo "По филиалу \"$row->filial\" информация не доступна.<br/>";
			}
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
				$queueItem->uuid            = $row->uuid;
				$queueItem->filial          = $row->filial;
				$queueItem->ticket          = $prefix . $ticket->clientNum;
				$queueItem->priority        = $ticket->priority;
				$dtQueued                   = DateTime::createFromFormat($fmt, $ticket->regTime, $tz);
				//$queueItem->queued_at       = JFactory::getDate($dtQueued->getTimestamp())->toSql(true);
				$queueItem->queued_at       = $dtQueued->getTimestamp();
				$queueItem->start_time      = $ticket->startTime / 1000;
				$queueItem->waiting_time    = $ticket->startTime / 1000 - $dtQueued->getTimestamp();
				if ($queueItem->waiting_time < 0) $queueItem->waiting_time = 0;
				$queueItem->remote_reg      = $ticket->remoteReg;
				$queueItem->queue           = '';
				$queueItem->call_time       = $dt;
				$queueItem->service_name    = $ticket->serviceName;
				$queueItem->status          = $ticket->status;
				$queueItem->window_number   = $ticket->cabNum;
				$queueItem->number_of_cases = $ticket->additionalInfo->countCases;
				$queueItem->created_on      = (new DateTime())->getTimestamp();
				$this->db->insertObject('#__eqm_queue_item', $queueItem);
			}
		}
	}


}
