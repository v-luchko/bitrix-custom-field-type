<?

use Bitrix\Main\Localization\Loc;

\Bitrix\Main\Loader::includeModule('iblock');

$eventManager = \Bitrix\Main\EventManager::getInstance();

$eventManager->addEventHandler('iblock',
    'OnIBlockPropertyBuildList', ['MyProperty', 'GetUserTypeDescription']);

include($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/highloadblock/classes/general/prop_directory.php');

class MyProperty extends CIBlockPropertyDirectory
{
    const USER_TYPE = 'myType';

    public static function GetUserTypeDescription()
    {
        return array(
            'PROPERTY_TYPE' => 'S',
            'USER_TYPE' => self::USER_TYPE,
            'DESCRIPTION' => 'Мой тип свойства',
            'GetSettingsHTML' => array(__CLASS__, 'GetSettingsHTML'),
            'GetPropertyFieldHtml' => array(__CLASS__, 'GetPropertyFieldHtml'),
            'GetPropertyFieldHtmlMulty' => array(
                __CLASS__,
                'GetPropertyFieldHtmlMulty'
            ),
            'PrepareSettings' => array(__CLASS__, 'PrepareSettings'),
            'GetAdminListViewHTML' => array(__CLASS__, 'GetAdminListViewHTML'),
            'GetPublicViewHTML' => array(__CLASS__, 'GetPublicViewHTML'),
            'GetPublicEditHTML' => array(__CLASS__, 'GetPublicEditHTML'),
            'GetPublicEditHTMLMulty' => array(
                __CLASS__,
                'GetPublicEditHTMLMulty'
            ),
            'GetAdminFilterHTML' => array(__CLASS__, 'GetAdminFilterHTML'),
            'GetExtendedValue' => array(__CLASS__, 'GetExtendedValue'),
            'GetSearchContent' => array(__CLASS__, 'GetSearchContent'),
            'AddFilterFields' => array(__CLASS__, 'AddFilterFields'),
            'GetUIFilterProperty' => array(__CLASS__, 'GetUIFilterProperty'),
            'ConvertToDB' => array(__CLASS__, 'ConvertToDB'),
            'ConvertFromDB' => array(__CLASS__, 'ConvertFromDB'),
        );
    }

    //Поле редактирование в элементе
    public static function GetPropertyFieldHtml(
        $arProperty,
        $value,
        $strHTMLControlName
    ) {
        $settings = CIBlockPropertyDirectory::PrepareSettings($arProperty);
        $size = ($settings["size"] > 1 ? ' size="' . $settings["size"] . '"' : '');
        $width = ($settings["width"] > 0 ? ' style="width:' . $settings["width"] . 'px"' : '');

        $options = CIBlockPropertyDirectory::GetOptionsHtml($arProperty,
            array($value['VALUE']["TABLE_ID"]));
        $select = 'Справочник: <select name="' . $strHTMLControlName['VALUE'] . '[TABLE_ID]"' . $size . $width . '>';
        $select .= $options;
        $select .= '</select>';

        $coordX = '<br>Координата X: <input size="30" style="margin-top: 10px" type="number" name="' . $strHTMLControlName['VALUE'] . '[COORD_X]" value="' . $value['VALUE']['COORD_X'] . '">';
        $coordY = '<br>Координата Y: <input size="30" style="margin-top: 10px" type="number" name="' . $strHTMLControlName['VALUE'] . '[COORD_Y]" value="' . $value['VALUE']['COORD_Y'] . '">';

        return $select . $coordX . $coordY;
    }


    //Поле редактирование в элементе для множественного свойства
    public static function GetPropertyFieldHtmlMulty(
        $arProperty,
        $value,
        $strHTMLControlName
    ) {
        $max_n = 0;
        $values = array();
        if (is_array($value)) {
            $match = array();
            foreach ($value as $property_value_id => $arValue) {
                $values[$property_value_id] = $arValue["VALUE"];
                if (preg_match("/^n(\\d+)$/", $property_value_id, $match)) {
                    if ($match[1] > $max_n) {
                        $max_n = intval($match[1]);
                    }
                }
            }
        }

        $settings = CIBlockPropertyDirectory::PrepareSettings($arProperty);
        $size = ($settings["size"] > 1 ? ' size="' . $settings["size"] . '"' : '');
        $width = ($settings["width"] > 0 ? ' style="width:' . $settings["width"] . 'px"' : ' style="margin-bottom:3px"');


        if (end($values) != "" || substr(key($values), 0, 1) != "n") {
            $values["n" . ($max_n + 1)] = "";
        }

        $name = $strHTMLControlName["VALUE"] . "VALUE";

        $html = '<table cellpadding="0" cellspacing="0" border="0" class="nopadding" width="100%" id="tb' . md5($name) . '">';
        foreach ($values as $property_value_id => $value) {
            $html .= '<tr><td>';

            $options = CIBlockPropertyDirectory::GetOptionsHtml($arProperty,
                array($value['TABLE_ID']));

            $html .= '<select name="' . $strHTMLControlName["VALUE"] . '[' . $property_value_id . '][TABLE_ID]"' . $size . $width . '>';
            $html .= $options;
            $html .= '</select>';

            $coordX = '<br>Координата X: <input size="30" style="margin-top: 10px" type="number" name="' . $strHTMLControlName['VALUE'] . '[' . $property_value_id . '][COORD_X]" value="' . $value['COORD_X'] . '">';
            $coordY = '<br>Координата Y: <input size="30" style="margin-top: 10px" type="number" name="' . $strHTMLControlName['VALUE'] . '[' . $property_value_id . '][COORD_Y]" value="' . $value['COORD_Y'] . '">';
            $html .= $coordX . $coordY;
            $html .= '</td></tr>';
        }
        $html .= '</table>';

        $html .= '<input type="button" value="' . Loc::getMessage("HIBLOCK_PROP_DIRECTORY_MORE") . '" onclick="if(window.addNewRow){addNewRow(\'tb' . md5($name) . '\', -1)}else{addNewTableRow(\'tb' . md5($name) . '\', 1, /\[(n)([0-9]*)\]/g, 2)}">';
        return $html;
    }

    //Сохранение в БД
    public static function ConvertToDB($arProperty, $value)
    {
        if ($arProperty['MULTIPLE'] != 'Y') {
            return ['VALUE' => serialize($value['VALUE'])];
        }

        //Отсеиваем пустые значения множественных полей
        if ($value['VALUE']['TABLE_ID'] != "" || $value['VALUE']['COORD_X'] != "" || $value['VALUE']['COORD_Y'] != "") {
            return ['VALUE' => serialize($value['VALUE'])];
        }
    }

    //Получение из БД
    public static function ConvertFromDB($arProperty, $value)
    {
        return [
            'VALUE' => unserialize(str_replace('&quot;', '"', $value['VALUE']))
        ];
    }
}

?>