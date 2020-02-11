<?

\Bitrix\Main\Loader::includeModule('iblock');

$eventManager = \Bitrix\Main\EventManager::getInstance();

$eventManager->addEventHandler('main',
    'OnUserTypeBuildList', ['MyUserField', 'GetUserTypeDescription']);

include($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/highloadblock/classes/general/cusertypehlblock.php');

class MyUserField extends CUserTypeHlblock
{

    function GetUserTypeDescription()
    {
        return array(
            "USER_TYPE_ID" => "myTypeId",
            "CLASS_NAME" => "MyUserField",
            "DESCRIPTION" => 'Моё пользовательское поле',
            "BASE_TYPE" => "string",
        );
    }

    function GetDBColumnType($arUserField)
    {
        global $DB;
        switch (strtolower($DB->type)) {
            case "mysql":
                return "text";
            case "oracle":
                return "varchar2(2000 char)";
            case "mssql":
                return "varchar(2000)";
        }
    }

    function GetEditFormHTML($arUserField, $arHtmlControl)
    {
        if (($arUserField["ENTITY_VALUE_ID"] < 1) && strlen($arUserField["SETTINGS"]["DEFAULT_VALUE"]) > 0) {
            $arHtmlControl["VALUE"] = intval($arUserField["SETTINGS"]["DEFAULT_VALUE"]);
        }

        $arHtmlControl['VALUE'] = unserialize(str_replace('&quot;', '"',
            $arHtmlControl['VALUE']));

        $rsEnum = call_user_func_array(
            array($arUserField["USER_TYPE"]["CLASS_NAME"], "getlist"),
            array($arUserField)
        );
        if (!$rsEnum) {
            return '';
        }

        $bWasSelect = false;
        $result2 = '';
        while ($arEnum = $rsEnum->GetNext()) {
            $bSelected = (
                ($arHtmlControl["VALUE"]['TABLE_ID'] == $arEnum["ID"]) ||
                ($arUserField["ENTITY_VALUE_ID"] <= 0 && $arEnum["DEF"] == "Y")
            );
            $bWasSelect = $bWasSelect || $bSelected;
            $result2 .= '<option value="' . $arEnum["ID"] . '"' . ($bSelected ? ' selected' : '') . '>' . $arEnum["VALUE"] . '</option>';
        }

        if ($arUserField["SETTINGS"]["LIST_HEIGHT"] > 1) {
            $size = ' size="' . $arUserField["SETTINGS"]["LIST_HEIGHT"] . '"';
        } else {
            $arHtmlControl["VALIGN"] = "middle";
            $size = '';
        }

        $result = '<select name="' . $arHtmlControl["NAME"] . '[TABLE_ID]"' . $size . ($arUserField["EDIT_IN_LIST"] != "Y" ? ' disabled="disabled" ' : '') . '>';
        if ($arUserField["MANDATORY"] != "Y") {
            $result .= '<option value=""' . (!$bWasSelect ? ' selected' : '') . '>' . htmlspecialcharsbx(strlen($arUserField["SETTINGS"]["CAPTION_NO_VALUE"]) > 0 ? $arUserField["SETTINGS"]["CAPTION_NO_VALUE"] : GetMessage('MAIN_NO')) . '</option>';
        }
        $result .= $result2;
        $result .= '</select>';
        $coordX = '<br>Координата X: <input size="30" style="margin-top: 10px" type="number" name="' . $arHtmlControl['NAME'] . '[COORD_X]" value="' . $arHtmlControl['VALUE']['COORD_X'] . '">';
        $coordY = '<br>Координата Y: <input size="30" style="margin-top: 10px" type="number" name="' . $arHtmlControl['NAME'] . '[COORD_Y]" value="' . $arHtmlControl['VALUE']['COORD_Y'] . '">';

        $result .= $coordX . $coordY;

        return $result;
    }

    function GetEditFormHTMLMulty($arUserField, $arHtmlControl)
    {
        $arHtmlControl['NAME'] = substr($arHtmlControl['NAME'],
            0, strlen($arHtmlControl['NAME']) - 2);
        $name = $arHtmlControl["NAME"] . "VALUE";

        $result = '<table cellpadding="0" cellspacing="0" border="0" class="nopadding" width="100%" id="tb' . md5($name) . '">';
        $result2 = '';
        $rsEnum = call_user_func_array(
            array($arUserField["USER_TYPE"]["CLASS_NAME"], "getlist"),
            array($arUserField)
        );
        if (!$rsEnum) {
            return '';
        }

        $enumList = [];
        while ($arEnum = $rsEnum->GetNext()) {
            $enumList[$arEnum['ID']] = $arEnum['VALUE'];
        }
        $arHtmlControl['VALUE'][] = "{}";

        if ($arUserField["SETTINGS"]["LIST_HEIGHT"] > 1) {
            $size = ' size="' . $arUserField["SETTINGS"]["LIST_HEIGHT"] . '"';
        } else {
            $arHtmlControl["VALIGN"] = "middle";
            $size = '';
        }

        foreach ($arHtmlControl['VALUE'] as $key => $value) {
            $result2 .= '<tr><td>';
            $value = unserialize(str_replace('&quot;', '"', $value));
            $result2 .= '<select name="' . $arHtmlControl["NAME"] . '[' . $key . ']' . '[TABLE_ID]"' . $size . ($arUserField["EDIT_IN_LIST"] != "Y" ? ' disabled="disabled" ' : '') . '>';
            $result2 .= '<option value="">(Нет)</option>';
            foreach ($enumList as $enumKey => $enumItem) {
                $result2 .= '<option value="' . $enumKey . '"' . ($value['TABLE_ID'] == $enumKey ? ' selected' : '') . '>' . $enumItem . '</option>';
            }
            $result2 .= '</select>';
            $coordX = '<br>Координата X: <input size="30" style="margin-top: 10px" type="number" name="' . $arHtmlControl['NAME'] . '[' . $key . ']' . '[COORD_X]" value="' . $value['COORD_X'] . '">';
            $coordY = '<br>Координата Y: <input size="30" style="margin-top: 10px" type="number" name="' . $arHtmlControl['NAME'] . '[' . $key . ']' . '[COORD_Y]" value="' . $value['COORD_Y'] . '">';
            $result2 .= $coordX . $coordY . '</td></tr>';
        }
        $result .= $result2;

        $result .= '</td></tr><tr><td></td></tr>';

        $buttonAdd = '<input type="button" value="Ещё" onclick="addNewRow(\'tb' . md5($name) . '\', \'' . $arHtmlControl['NAME'] . '\');">';
        return $result . '</table>' . $buttonAdd;
    }

    function OnBeforeSave($arUserField, $value)
    {
        if ($value['TABLE_ID'] != "" || $value['COORD_X'] != "" || $value['COORD_Y'] != "") {
            return serialize($value);
        }
    }
}

?>