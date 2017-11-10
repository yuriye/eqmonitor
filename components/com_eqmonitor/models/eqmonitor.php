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

		$objectList = array();
		$query      = 'SELECT * FROM #__eqm_filial_cabs ORDER BY filial';
		$this->db->setQuery($query);
		$sourceObjectList = $this->db->loadObjectList();

		//collecting statistic in $sourceObjectList for every filial
		foreach ($sourceObjectList as $sourceObject)
		{
			$obj = null;
			if ($sourceObject->dayoff) continue; //Если сегодня окно не работает - пропуск
			if (!isset($objectList[$sourceObject->filial]))
			{
				$obj                               = new stdClass();
				$obj->filial                       = $sourceObject->filial;
				$objectList[$sourceObject->filial] = $obj;
				$obj->cabs                         = 0;
				$obj->status                       = array();
				$obj->state                        = array();
				$obj->countOfServed                = 0;
				$obj->totalServiceTime             = 0;

			}
			else
			{
				$obj = $objectList[$sourceObject->filial];
			}
			$obj->cabs++;
			if (key_exists($sourceObject->status, $obj->status))
			{
				$obj->status[$sourceObject->status]++;
			}
			else
			{
				$obj->status[$sourceObject->status] = 0;
			}

			if (key_exists($sourceObject->state, $obj->state))
			{
				$obj->state[$sourceObject->state]++;
			}
			else
			{
				$obj->state[$sourceObject->state] = 0;
			}

			$obj->countOfServed    += $sourceObject->count_of_served;
			$obj->totalServiceTime += $sourceObject->average_service_time * $sourceObject->count_of_served;
		};
		foreach ($objectList as $obj)
		{
			if ($obj->countOfServed == 0)
			{
				$obj->averageServiceTime = 0;
				continue;
			}
			$obj->averageServiceTime = $obj->totalServiceTime / $obj->countOfServed;
			$obj->averageServiceTime = round($obj->averageServiceTime / 60000);
		}

		$query = 'SELECT * FROM #__eqm_queue_item ORDER BY filial';
		$this->db->setQuery($query);
		$sourceObjectList = $this->db->loadObjectList();

		foreach ($sourceObjectList as $sourceObject)
		{
			$obj = null;
			if (!isset($objectList[$sourceObject->filial])) continue;
			$obj = $objectList[$sourceObject->filial];
			if (!isset($obj->clientsWaiting)) {
				$obj->clientsWaiting = 0;
			};
			if (!isset($obj->clientsServing)) {
				$obj->clientsServing = 0;
			};
			if (!isset($obj->numberOfCases)) {
				$obj->numberOfCases = 0;
			};

			if ('WAIT' == $sourceObject->status)
			{
				if (!$sourceObject->remote_reg)
				{
					$obj->clientsWaiting++;
				}
			} else {
				$obj->clientsServing++;
			};

			$obj->numberOfCases += $sourceObject->number_of_cases;
		}


		return $objectList;
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
				$queueItem->queued_at       = JFactory::getDate($dtQueued->getTimestamp())->toSql(true);
				$queueItem->waiting_time    = 0;
				$queueItem->remote_reg       = $ticket->remoteReg;
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
