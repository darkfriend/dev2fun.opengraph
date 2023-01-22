<?php
/**
 * @author darkfriend <hi@darkfriend.ru>
 * @version 1.4.2
 */
$MESS["SEC_MAIN_TAB"] = "Настройки OpenGraph";
$MESS["SEC_MAIN_TAB_TITLE"] = "Настройки OpenGraph";
$MESS['SEC_BACKUP_TITLE'] = "Модуль бекапа файлов";
$MESS['LABEL_TITLE_OG_FIELDS'] = "Указание активных OpenGraph свойств";
$MESS['LABEL_ADD'] = "Добавить";
$MESS['SEC_FILTER_NOTE'] = "Модуль разработан командой <b>Dev2Fun</b>, для помощи в работе владельцам продукта 1С-Битрикс.<br>Модуль будет улучшаться и добавляться новый функционал.<br>Мы будем очень рады за вашу поддержку: как на добром слове, так и финансовой.<br>Также мы открыты для ваших идей и предложений.<br>Спасибо вам и приятного использования.";
$MESS['LABEL_TITLE_SETTINGS_FIELDS'] = "Настройки";
$MESS['LABEL_CACHE_TIME_FIELDS'] = "Время жизни кэша (сек)";
$MESS['SUCCESS_SAVE_MESSAGE'] = "Настройки успешно сохранены";
$MESS['LABEL_TITLE_OG_PAGE_EXCLUDED'] = "Страницы исключений";
$MESS['LABEL_SETTING_ADD_REMOVE_INDEX'] = "Удалить index.php";

$MESS['LABEL_TITLE_OG_SETTINGS_ADMIN'] = "Настройки в админке";
$MESS['LABEL_SETTING_ADD_TAB_ELEMENT'] = "Добавить таб в элементах (админка)";
$MESS['LABEL_SETTING_ADD_TAB_SECTION'] = "Добавить таб в разделах (админка)";
$MESS['LABEL_SETTING_ADD_TITLE'] = "Авто-заполнение поля og:title (админка)";
$MESS['LABEL_SETTING_ADD_DESCRIPTION'] = "Авто-заполнение поля og:description (админка)";
$MESS['LABEL_SETTING_ADD_IMAGE'] = "Авто-заполнение поля og:image (админка)";

$MESS['LABEL_TITLE_OG_SETTINGS_SHOW'] = "Настройки вывода Open Graph полей";
$MESS['LABEL_SETTING_SHOW_ELEMENTS'] = "Выводить OG в элементах";
$MESS['LABEL_SETTING_SHOW_SECTIONS'] = "Выводить OG в разделах";
$MESS['LABEL_SETTING_SHOW_PAGES'] = "Выводить OG на страницах";

$MESS['LABEL_SETTING_ADD_DEFAULT_IMAGE'] = "Картинка по умолчанию";
$MESS['LABEL_SETTING_OG_RESIZE_ENABLE'] = "Включить авторесайз";
$MESS['LABEL_SETTING_OG_RESIZE_WIDTH'] = "Ширина изображения";
$MESS['LABEL_SETTING_OG_RESIZE_HEIGHT'] = "Высота изображения";
$MESS['LABEL_SETTING_OG_RESIZE_TYPE'] = "Вид ресайза";
$MESS['LABEL_SETTING_OG_RESIZE_HEADING'] = "Автоматический ресайз og:image";
$MESS['LABEL_SETTING_OG_SORTABLE'] = "Порядок присваивания значений";


$MESS['LABEL_SETTING_OG_CACHE_HEADING'] = "Управление кэшированием";
$MESS["D2F_OG_CACHE_CLEARED"] = "Кэш успешно очищен";

$MESS['LABEL_SETTING_OG_ADDITIONAL_PLACEHOLDER'] = "OpenGraph доп. ключ";
//$MESS['LABEL_SETTING_OG_ADDITIONAL_PLACEHOLDER_VALUE'] = "OpenGraph значение";

$MESS['LABEL_SETTING_OG_BX_RESIZE_IMAGE_EXACT'] = "масштабирует в прямоугольник указаной шириной и высотой c сохранением пропорций, обрезая лишнее";
$MESS['LABEL_SETTING_OG_BX_RESIZE_IMAGE_PROPORTIONAL'] = "масштабирует с сохранением пропорций, размер ограничивается указаной шириной и высотой";
$MESS['LABEL_SETTING_OG_BX_RESIZE_IMAGE_PROPORTIONAL_ALT'] = "масштабирует с сохранением пропорций за ширину при этом принимается максимальное значение из высоты/ширины, размер ограничивается указаной шириной и высотой, улучшенная обработка вертикальных картинок";

$MESS['LABEL_SETTING_OG_SORTABLE_PROP_OG'] = "Данные из полей OpenGraph элементов/разделов";
$MESS['LABEL_SETTING_OG_SORTABLE_PROP_IBLOCK'] = "Данные из полей элементов/разделов";
$MESS['LABEL_SETTING_OG_SORTABLE_PROP_FIELDS'] = "Данные из SetPageProperty/SetDirProperty";
$MESS['LABEL_SETTING_OG_SORTABLE_PROP_DEFAULT'] = "Данные из алгоритма \"по умолчанию\"";
$MESS['LABEL_SETTING_OG_SORTABLE_ATTENTION'] = "Внимание это настройка для технических специалистов.<br>Перед использованием ознакомьтесь с документацией.";


$MESS['LABEL_TITLE_OG_FIELDS_TEXT'] = "<i>Больше OpenGraph-ключей указано на сайте <a href=\"http://ogp.me/\" target=\"_blank\">ogp.me</a></i>";
$MESS['LABEL_TITLE_OG_PAGE_EXCLUDED_TEXT'] = <<<EO
	<i>Вы можете указывать как точный путь, так и регулярное выражение.</i>
	<ul>
		<li>
			Пример точного пути: <code>/catalog/</code><br>
			В этом случае модуль не выведет на странице <code>/catalog/index.php</code>
		</li>
		<li>
			Пример регулярного выражения: <code>#\/catalog\/*#i</code><br>
			В этом случае модуль не выведет на странице и всех подстраницах раздела catalog.
		</li>
	</ul>
EO;
$MESS['LABEL_TITLE_HELP_BEGIN'] = 'Вы используете бесплатный модуль разработанный командой <a href="//dev2fun.com" class="c-badge c-badge--rounded c-badge--ghost c-badge--brand" target="_blank">Dev2Fun</a>';
$MESS['LABEL_TITLE_HELP_BEGIN_TEXT'] = <<<EOT
	<p class="c-paragraph">На этой странице вы можете:</p>
	<ul class="c-list">
		<li class="c-list__item">поддержать выпуск обновлений модуля</li>
		<li class="c-list__item">поддержать выпуск новых <b>бесплатных</b> модулей</li>
		<li class="c-list__item">поддержать команду <b>Dev2fun</b></li>
	</ul>
	<p class="c-paragraph"><b>Ваша поддержка нам крайне важна. Спасибо вам!</b></p>
EOT;
$MESS['LABEL_TITLE_HELP_DONATE_TEXT'] = 'Поддержать разработчиков';
$MESS['LABEL_TITLE_HELP_DONATE_ALL_TEXT'] = 'Все способы поддержки';
$MESS['LABEL_TITLE_HELP_DONATE_OTHER_TEXT'] = 'Другие способы поддержки';
$MESS['LABEL_TITLE_HELP_DONATE_OTHER_TEXT_S'] = <<<EOT
	<p class="c-paragraph"><b>Хорошие отзывы</b> - нас также мотивируют ваши хорошие отзывы. Спасибо за них!</p>
	<p class="c-paragraph"><b>Ваши идеи и предложения</b> - нам всегда интересны любые идеи по улучшнию модуля. Не стесняйтесь ими делиться!</p>
	<p class="c-paragraph">Мы стараемся радовать вас удобными и полезными решениями. Приятной работы!</p>
EOT;
$MESS['LABEL_TITLE_HELP_DONATE_FOLLOW'] = 'Следите за новостями';
$MESS['SEC_DONATE_TAB'] = 'Поблагодарить';
$MESS['SEC_DONATE_TAB_TITLE'] = 'Поблагодарить за модуль';