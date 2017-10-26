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
<!--Это код таблицы с круглыми краями-->
<div id="v1">
	<div><b></b></div><div><b><i><q></q></i></b></div>
	<div><b><i></i></b></div><div><b></b></div><div><b></b></div>
	<div>
		<!--Это таблица где выводятся данные из базы-->
		<table>
			<!--Первый цикл который выведет нам данные-->
			<?php foreach ($this->rows as $row )
			    {
				// Проверка опубликован раздел или нет
				//echo '<tr> <td>'.$row->filial.'</td> <td>'.$row->ticket.'</td> </tr>';
                    print_r($row);
                    echo '</br></br>';
            }?>
		</table>
	</div>
	<div><b></b></div><div><b></b></div><div><b><i></i></b></div>
	<div><b><i><q></q></i></b></div><div><b></b></div>
</div>