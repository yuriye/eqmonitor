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

	public function __construct()
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
		$delquery = 'truncate #__eqm_queue_item;';
		$this->db->setQuery($delquery)->execute();

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
				$prefix = '';
				echo "prefix ======$prefix\n";
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
				$dt = new DateTime();
				$dt->setTimezone($tz);
				$dt->setTimestamp($seconds);

				$insertSQL    = "INSERT IGNORE INTO `#__eqm_queue_item` 
									(filial,  ticket,  priority,  queued_at,  call_time,  waiting_time,  queue,  service_name,  status,  window_number,  number_of_cases,  created_on) VALUES 
									(:filial, :ticket, :priority, :queued_at, :call_time, :waiting_time, :queue, :service_name, :statuss, :window_number, :number_of_cases, :created_on);";
				$query = $this->db->prepare($insertSQL);
				$query->execute(array(
					':filial' => $row->filial,
					':ticket' => $prefix . $ticket->clientNum,
					':priority' => $ticket->priority,
					':queued_at' => DateTime::createFromFormat($fmt , $ticket->regTime, $tz),
					':call_time' => $dt,
					':service_name' => $ticket->serviceName,
					':status' => $ticket->status,
					':window_number' => $ticket->cabNum,
					':number_of_cases' => $ticket->number_of_cases,
					':created_on' => $ticket->created_on
				));
			}
		}
	}
}
