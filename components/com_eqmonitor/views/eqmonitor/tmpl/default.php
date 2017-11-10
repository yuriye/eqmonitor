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

JHtml::_('script', 'com_eqmonitor/script.js', false, true);
JHtml::_('stylesheet', 'com_eqmonitor/style.css', array(), true);

/*
$layout = new JLayoutFile('eqmonitor.page');
$data = new stdClass;
$data->text = 'Hello Joomla!';
echo $layout->render($data);
*/


/*
Шаблон это вывод данных которые сформированы через модель и вид.
Здесь выводится через цикл. так должно быть echo $row->id;
а не echo $this->id; id это просто то значение которо нужно
вывести оно может быть любым в зависимости как называются ваши поля в таблице.
Ну и естественно свои классы в joomla для оформления страницы.
*/
//защита от прямого доступа
?>

<!--Подключаем css-->
<link rel="stylesheet" type="text/css" href="/components/com_eqmonitor/css/eqm.css">
<div class="item-page">
    <h3 class="mfch">
        Состояние электронной очереди в филиалах </h3>
	<?php foreach ($this->rows as $row): ?>
        <div class="filial">
			<?php
			echo "<b>$row->filial</b><br/>";
			//echo "Всего окон: $row->cabs, работает окон: {$row->state['ON']}<br/>";
			echo "Работает окон: {$row->state['ON']}<br/>";
			$serving = $row->state['ON'] < $row->clientsServing ? $row->state['ON'] : $row->clientsServing;
			if (isset($row->clientsServing)) echo "Сейчас обслуживается $serving чел.<br/>";
			if (isset($row->clientsWaiting)) echo "Ожидают обслуживания $row->clientsWaiting чел.<br/>";
			echo "Среднее время обслуживания $row->averageServiceTime мин.<br/>";
			//print_r($row);
			echo '<br/>'; ?>
        </div>
	<?php endforeach ?>
</div>