<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

global $APPLICATION, $USER;
$APPLICATION->SetTitle(GetMessage("BITRIX_PLANNER_PLANIROVANIE_OTPUSKO"));


/* ADDITIONAL JS ADDED *************** */
$APPLICATION->AddHeadScript('/bitrix/components/custom/holiday.list/templates/.default/jquery-3.1.0.min.js');
$APPLICATION->AddHeadScript('/bitrix/components/custom/holiday.list/templates/.default/jquery-ui-1.12.1/jquery-ui.min.js');
$APPLICATION->SetAdditionalCSS('/bitrix/components/custom/holiday.list/templates/.default/jquery-ui-1.12.1/jquery-ui.theme.min.css');
$APPLICATION->SetAdditionalCSS('/bitrix/components/custom/holiday.list/templates/.default/jquery-ui-1.12.1/jquery-ui.structure.min.css');


if (!CModule::IncludeModule('iblock') || !CModule::IncludeModule('intranet') || !CModule::IncludeModule('im'))
	die(GetMessage("BITRIX_PLANNER_NE_USTANOVLENY_TREBU"));

$IBLOCK_ID = intval($arParams['IBLOCK_ID']);
$UF_DAYS = 'UF_DAYS';

$arResult = array();
$arResult['ERROR'] = '';
$arResult['PERIOD'] = array();
$arResult['MARKER'] = array();
$arResult['USERS'] = array();
$arResult['SUMMARY'] = array();

$arResult['COUNT_DAYS'] = $arParams['COUNT_DAYS'] == 'Y';
$arResult['HR'] = $USER->IsAdmin() || $arParams['HR_GROUP_ID'] && in_array($arParams['HR_GROUP_ID'], $USER->GetUserGroupArray());

$arResult['TYPES'] = array(
	'VACATION' => GetMessage("BITRIX_PLANNER_OTPUSK"),
);
$rs = CIBlockPropertyEnum::GetList($arOrder = array("SORT" => "ASC", "VALUE" => "ASC"), $arFilter = array('IBLOCK_ID' => $IBLOCK_ID, 'PROPERTY_ID' => 'ABSENCE_TYPE'));
while($f = $rs->Fetch())
{
	$arResult['TYPES'][$f['XML_ID']] = $f['VALUE'];
	$arResult['ABSENCE_TYPES'][$f['XML_ID']] = $f['ID'];
	$arResult['ABSENCE_TYPES'][$f['ID']] = $f['XML_ID'];
}

$arResult['MONTH'] = intval($_REQUEST['month']);
$arResult['YEAR'] = intval($_REQUEST['year']);
if (!$arResult['MONTH'] || $arResult['MONTH'] > 12)
	$arResult['MONTH'] = intval(date('m'));
if (!$arResult['YEAR'])
	$arResult['YEAR'] = date('Y');

$arResult['PREV_YEAR'] = $arResult['NEXT_YEAR'] = $arResult['YEAR'];
$arResult['NEXT_MONTH'] = $arResult['MONTH'] + 1;
$arResult['PREV_MONTH'] = $arResult['MONTH'] - 1;

if ($arResult['NEXT_MONTH'] > 12)
{
	$arResult['NEXT_MONTH'] = 1;
	$arResult['NEXT_YEAR'] = $arResult['YEAR'] + 1;
}

if ($arResult['PREV_MONTH'] < 1)
{
	$arResult['PREV_MONTH'] = 12;
	$arResult['PREV_YEAR'] = $arResult['YEAR'] - 1;
}

$arResult['RECURSIVE'] = $_REQUEST['recursive'] ? 1 : 0;
$arResult['LAST_DAY'] = date('t',mktime(1,1,1,$arResult['MONTH'] ,1,$arResult['YEAR']));

$arResult['USER_ID'] = $USER->GetId();
$arResult['ADMIN'] = false;

$arResult['IBLOCK_ID'] = COption::GetOptionInt('intranet', 'iblock_structure');
$arResult['DEPARTMENT_LIST'] = array();
$arResult['DEPARTMENT_ID'] = intval($_REQUEST['department']);

$arFilter = array('IBLOCK_ID' => $arResult['IBLOCK_ID'], 'ACTIVE' => 'Y');
if ($arResult['HR'])
{
	$arResult['ADMIN'] = true;
	$arResult['DEPARTMENT_ID'] = intval($_REQUEST['department']);
}
elseif (count($ar = CIntranetUtils::GetSubordinateDepartments($USER->GetId(), true)))
{
	$arResult['ADMIN'] = true;
	if (in_array($_REQUEST['department'], $ar))
	{
		$arResult['DEPARTMENT_ID'] = intval($_REQUEST['department']);
		$arFilter['ID'] = $ar;
	}
	else
		$arResult['DEPARTMENT_ID'] = $ar[0];
}
else
{
	$dbRes = CUser::GetList($by='ID', $order='ASC', array('ID' => $USER->GetId()), array('SELECT' => array('UF_DEPARTMENT')));
	if (($arRes = $dbRes->Fetch()) && is_array($arRes['UF_DEPARTMENT']) && count($arRes['UF_DEPARTMENT']) > 0)
		$arResult['DEPARTMENT_ID'] = $arRes['UF_DEPARTMENT'][0];
	else
		return; // не работает для пользователей вне структуры
	$arFilter['ID'] = $arResult['DEPARTMENT_ID'];
}

$arResult['ALLOW_DAYS_ADD'] = $arResult['HR'] || $arResult['ADMIN'] && $arParams['MANAGER_ADD_DAYS'] != 'N';

CModule::IncludeModule('iblock');
$rs = CIBlockSection::GetList($arOrder = array('left_margin' => 'asc'), $arFilter);
while($f = $rs->Fetch())
{
	$f['DEPTH_NAME'] = str_repeat('. ', ($f['DEPTH_LEVEL'] - 1)).$f['NAME'];
	$arResult['DEPARTMENT_LIST'][$f['ID']] = $f;
	if (!$arResult['DEPARTMENT_ID'])
		$arResult['DEPARTMENT_ID'] = $f['ID'];
}

$arResult['BASE_URL'] = '?year='.$arResult['YEAR'].'&month='.$arResult['MONTH'].'&set_user_id='.$arResult['USER_ID'].'&department='.$arResult['DEPARTMENT_ID'].'&recursive='.$arResult['RECURSIVE']; 

$set_user_id = intval($_REQUEST['set_user_id']);
$rs = CIntranetUtils::GetDepartmentEmployees($arResult['DEPARTMENT_ID'], $arResult['RECURSIVE'], $bSkipSelf = false);
while($f = $rs->Fetch())
{
	if ($arResult['ADMIN'] && $set_user_id)
	{
		if ($f['ID'] == $set_user_id)
		{
			$arResult['USER_ID'] = $set_user_id;

			if ($arResult['ALLOW_DAYS_ADD'] && $d = intval($_REQUEST['add_days']))
			{
				$rs0 = CUser::GetList($by = 'id', $order = 'asc', $arFilter = array('ID' => $set_user_id), $arParams = array('SELECT' => array($UF_DAYS)));
				$f = $rs0->Fetch();

				if (!array_key_exists($UF_DAYS, $f))
				{
					$obUserField  = new CUserTypeEntity;
					$obUserField->Add(array(
							'ENTITY_ID' => 'USER',
							'FIELD_NAME' => $UF_DAYS,
							'USER_TYPE_ID' => 'double',
							'SORT' => 100,
							'SHOW_FILTER' => 'N',
							'SHOW_IN_LIST' => 'N',
							'EDIT_IN_LIST' => 'N',
							'EDIT_FORM_LABEL' => array('ru' => GetMessage("BITRIX_PLANNER_CISLO_DNEY_OTPUSKA")),
							'LIST_COLUMN_LABEL' => array('ru' => GetMessage("BITRIX_PLANNER_CISLO_DNEY_OTPUSKA")),
							'LIST_FILTER_LABEL' => array('ru' => GetMessage("BITRIX_PLANNER_CISLO_DNEY_OTPUSKA")),
						)
					);
				}

				$ob = new CUser();
				if ($ob->Update($set_user_id, array($UF_DAYS => intval($f[$UF_DAYS]) + $d)))
					LocalRedirect($APPLICATION->GetCurPage().$arResult['BASE_URL']);
				else
					$arResult['ERROR'] = GetMessage("BITRIX_PLANNER_NE_UDALOSQ_IZMENITQ");
			}
			$set_user_id = 0;
		}
	}
	$f['day_left'] = intval($f[$UF_DAYS]);
	$arResult['USERS'][$f['ID']] = $f;

	if ($f['ID'] == $arResult['USER_ID'])
		$APPLICATION->SetTitle(' ['.$f['NAME'].' '.$f['LAST_NAME'].']');
}

$map_from = mktime(0,0,0,$arResult['MONTH'] ,1,$arResult['YEAR']);
$map_to = 86400 + mktime(0,0,0,$arResult['MONTH'] ,$arResult['LAST_DAY'],$arResult['YEAR']);
$arFilter = array(
	'IBLOCK_ID' => $IBLOCK_ID, 
	'PROPERTY_USER' => array_keys($arResult['USERS'])
);

$rs = CIBlockElement::GetList($by = array('ACTIVE_FROM' => 'ASC'), $arFilter,false,false,array('*', 'PROPERTY_USER', 'PROPERTY_ABSENCE_TYPE'));
while($f = $rs->GetNext())
{

//print_r($f); 

	$uid = intval($f['PROPERTY_USER_VALUE']);
	//if (!$uid || !$arResult['USERS'][$uid])
	//	continue;

	//if (!$f['ACTIVE_FROM'] || !$f['ACTIVE_TO'])
	//	continue;

//print_r($arResult['USERS']);

//echo "<hr>";
//echo "User: ".$uid.' '. $arResult['USERS'][$uid]['NAME'].' '.$arResult['USERS'][$uid]['LAST_NAME'];
//echo "<br>";

	$from = MakeTimeStamp($f['ACTIVE_FROM']);
	$to = MakeTimeStamp($f['ACTIVE_TO']);

//echo "from:".$f['ACTIVE_FROM'].'<br>';
//echo "to:".$f['ACTIVE_TO'].'<br>';


	$f['CODE'] = $arResult['ABSENCE_TYPES'][$f['PROPERTY_ABSENCE_TYPE_ENUM_ID']];
	if (!$arResult['TYPES'][$f['CODE']])
		$f['CODE'] = 'VACATION';

	if ($f['CODE'] == 'VACATION')
	{
		$from = TimestampRemoveTime($from);
		$to = TimestampRemoveTime($to);
	}

	if ($to < $from)
		continue;

	if ($_REQUEST['action'] == 'delete' && $_REQUEST['id'] == $f['ID'])
		continue;

	$to_fixed = $to;
	if (date('His',$to) == 0) // отсутствие было указано без учета времени, добавляем сутки для корректного учета длительности периода
		$to_fixed += 86400;

	$from_visible = max(array($from, $map_from));
	$to_visible = min(array($to_fixed, $map_to));
	$period = $to_fixed - $from;
	$visible_period = $to_visible > $from_visible ? $to_visible - $from_visible : 0; // иначе отсутствие за пределами видимости

	$f['PERIOD'] = $period;
	$f['VISIBLE_PERIOD'] = $visible_period;

	if ($arResult['COUNT_DAYS'] && $f['CODE'] == 'VACATION')
		$arResult['USERS'][$uid]['day_left'] -= floor($period / 86400);

	$f['HUMAN_TIME'] = MakeHumanTime($period);
	$f['PARTIAL'] = $period < 86400 && (date('His', $from) > 0 || date('His', $to) > 0);

	$f['TITLE'] = $arResult['TYPES'][$f['CODE']].' '.$f['ACTIVE_FROM'].' - '.$f['ACTIVE_TO'].' ('.$f['HUMAN_TIME'].')'.($f['ACTIVE'] != 'Y' ? ' - '.GetMessage("BITRIX_PLANNER_NE_PODTVERJDENO") : '' ).($f['PREVIEW_TEXT'] ? ' ['.htmlspecialcharsbx($f['~PREVIEW_TEXT']).']' : '');

	$arResult['PERIOD'][] = $f;
	$from0 = $from_visible;
	$last_date = date('Ymd', $to_visible - 1); // 23:59:59
	
/* MARKER HERE *************************************************************** */

	while ($visible_period)
	{
		if (date('Ymd', $from0) > $last_date)
			break;
		$d = date('j', $from0);
		$arResult['MARKER'][$uid][$d] = $f;
		$from0 += 86400;
	}
}

/* ADD REQUEST ********************** */

if ($_REQUEST['action'] == 'add')
{
	$t0 = MakeTimeStamp($_REQUEST['day_to']);
	$t1 = MakeTimeStamp($_REQUEST['day_from']);

	$period = 1 + ceil(($t0 - $t1) / 86400);
	if (!$arResult['TYPES'][$type = $_REQUEST['event_type']])
		$type = 'VACATION';
	$name = $arResult['TYPES'][$type];

	/*
	echo "-".$t0.'-'.$t1.'-'.$name;
	echo "<br> pass user id:".$_REQUEST['userid'];
	echo "<br> user : ".$arResult['USER_ID'];
	echo "<br>";
	print_r($arResult['USERS']);
	//print_r($arResult);
	//die();
	*/

	// chagne this to the other user we pass here
	//$user_id = $arResult['USER_ID'];
	$user_id = $_REQUEST['userid'];

	if ($t0 >= $t1 && (!$arResult['COUNT_DAYS'] || $type != 'VACATION' || $arResult['USERS'][$user_id]['day_left'] >= $period))
	{
		$el = new CIBlockElement;
		if ($el->Add(array(
				'IBLOCK_ID' => $IBLOCK_ID,
				'NAME' => $name,
				'CODE' => $type,
				'ACTIVE' => 'N',//$arResult['ADMIN']?'Y':'N'
				'ACTIVE_FROM' => $_REQUEST['day_from'],
				'ACTIVE_TO' => $_REQUEST['day_to'],
				'PREVIEW_TEXT' => $_REQUEST['PREVIEW_TEXT'],
				'PROPERTY_VALUES' => array(
					'USER' => $user_id,
					'ABSENCE_TYPE' => $arResult['ABSENCE_TYPES'][$type]
				)
			)
		))
		{
			$comment = $_REQUEST['PREVIEW_TEXT'] ? ' ['.$_REQUEST['PREVIEW_TEXT'].']' : '';
			$ar = CIntranetUtils::GetUserDepartments($user_id);
			if ($id = CIntranetUtils::GetDepartmentManagerID($ar[0]))
				ImNotify($USER->GetId(), $id, GetMessage("BITRIX_PLANNER_DOBAVLENO").$name, GetMessage("BITRIX_PLANNER_DOBAVLNO").$name.' ['.$arResult['USERS'][$user_id]['NAME'].' '.$arResult['USERS'][$user_id]['LAST_NAME'].'] '.$_REQUEST['day_from'].' - '.$_REQUEST['day_to'].$comment);
			LocalRedirect($APPLICATION->GetCurPage().$arResult['BASE_URL']);
		}
		else
		{
			$arResult['ERROR'] = $el->LAST_ERROR;
		}
	}
	else
		$arResult['ERROR'] = GetMessage("BITRIX_PLANNER_VY_NE_MOJETE_VZATQ_O");
}

/* DELETE OR EDIT REQUEST ****************** */

elseif ($_REQUEST['action'] == 'delete' || $_REQUEST['action'] == 'edit')
{

	$rs = CIBlockElement::GetList($by = array('ACTIVE_FROM' => 'ASC'), $arFilter = array('IBLOCK_ID' => $IBLOCK_ID, 'ACTIVE' => 'N', 'ID' => $_REQUEST['id']),false,false,array('*', 'PROPERTY_USER', 'PROPERTY_ABSENCE_TYPE'));
	if ($f = $rs->Fetch())
	{

		$uid = intval($f['PROPERTY_USER_VALUE']);
		if ($uid == $arResult['USER_ID'] || $arResult['ADMIN'])
		{
			if ($_REQUEST['action'] == 'delete')
			{

				CIBlockElement::Delete($f['ID']);
				ImNotify($USER->GetId(), $uid, GetMessage("BITRIX_PLANNER_ZAPISQ_UDALENA"), GetMessage("BITRIX_PLANNER_ZAPISQ_UDALENA1").$arResult['TYPES'][$f['CODE']].' ['.$f['ACTIVE_FROM'].' - '.$f['ACTIVE_TO'].']');
			}
			else // edit
			{
				if (!$arResult['TYPES'][$type = $_REQUEST['event_type']])
					$type = 'VACATION';
				$name = $arResult['TYPES'][$type];

				$period = 1 + ceil((MakeTimeStamp($_REQUEST['day_to']) - MakeTimeStamp($_REQUEST['day_from'])) / 86400) - GetPeriod($f) / 86400;
				if (!$arResult['COUNT_DAYS'] || $type != 'VACATION' || $arResult['USERS'][$arResult['USER_ID']]['day_left'] >= $period)
				{

					$el = new CIBlockElement;
					if ($el->Update($_REQUEST['id'],
						array(
							'NAME' => $name,
							'CODE' => $type,
							'ACTIVE_FROM' => $_REQUEST['day_from'],
							'ACTIVE_TO' => $_REQUEST['day_to'],
							'PREVIEW_TEXT' => $_REQUEST['PREVIEW_TEXT'],
							'PROPERTY_VALUES' => array(
								'USER' => $uid,
								'ABSENCE_TYPE' => $arResult['ABSENCE_TYPES'][$type]
							)
						)
					))
					{
						$comment = $_REQUEST['PREVIEW_TEXT'] ? ' ['.$_REQUEST['PREVIEW_TEXT'].']' : '';
						ImNotify($USER->GetId(), $uid, GetMessage("BITRIX_PLANNER_ZAPISQ_IZMENENA"), GetMessage("BITRIX_PLANNER_ZAPISQ_IZMENENA1").$arResult['TYPES'][$f['CODE']].' '.$f['ACTIVE_FROM'].' - '.$f['ACTIVE_TO'].$comment);
						LocalRedirect($APPLICATION->GetCurPage().$arResult['BASE_URL']);
					}
					else {
						$arResult['ERROR'] = GetMessage("BITRIX_PLANNER_NELQZA_IZMENITQ_ZAPI");
					}
				}
				else {
					$arResult['ERROR'] = GetMessage("BITRIX_PLANNER_NELQZA_IZMENITQ_ZAPI");
				}
			}
		}
		else
			$arResult['ERROR'] = GetMessage("BITRIX_PLANNER_NELQZA_IZMENITQ_ZAPI1");
	}
	else
		$arResult['ERROR'] = GetMessage("BITRIX_PLANNER_ZAPISQ_NE_NAYDENA");


}
elseif ($_REQUEST['action'] == 'approve' || $_REQUEST['action'] == 'unapprove')
{

	$rs = CIBlockElement::GetList($by = array('ACTIVE_FROM' => 'ASC'), $arFilter = array('IBLOCK_ID' => $IBLOCK_ID, 'ID' => $_REQUEST['id']),false,false,array('*', 'PROPERTY_USER', 'PROPERTY_ABSENCE_TYPE'));
	if ($f = $rs->Fetch())
	{
		$uid = intval($f['PROPERTY_USER_VALUE']);

		// IF USER IS ADMIN OR ITS OWN EVENT
		if ($arResult['ADMIN'] || ($f['PROPERTY_USER_VALUE'] == $uid))
		{			
			$el = new CIBlockElement;
			$el->Update($f['ID'], array('ACTIVE' => $_REQUEST['action'] == 'approve' ? 'Y' : 'N'));

			if ($_REQUEST['action'] == 'approve')
				ImNotify($USER->GetId(), $uid, GetMessage("BITRIX_PLANNER_PODTVERJDENO").$name, GetMessage("BITRIX_PLANNER_PODTVERJDNO").$name.' ['.$f['ACTIVE_FROM'].' - '.$f['ACTIVE_TO'].']');
			LocalRedirect($APPLICATION->GetCurPage().$arResult['BASE_URL']);
		}
		else
			$arResult['ERROR'] = GetMessage("BITRIX_PLANNER_NET_PRAV_NA_OPERACIU");
	}
	else
		$arResult['ERROR'] = GetMessage("BITRIX_PLANNER_ZAPISQ_NE_NAYDENA");
}

foreach($arResult['PERIOD'] as $f)
	if ($f['VISIBLE_PERIOD'] && $f['ACTIVE'] == 'Y')
		$arResult['SUMMARY'][$f['PROPERTY_USER_VALUE']][$f['PROPERTY_ABSENCE_TYPE_ENUM_ID']] += $f['VISIBLE_PERIOD'];

CUtil::InitJSCore(array("tooltip"));
$this->IncludeComponentTemplate();

function MakeHumanTime($t)
{
	$w = floor($t / 86400 / 7);
	$d = floor($t%(86400 * 7) / 86400);
	$h = floor($t%86400 / 3600);
	$m = floor($t%3600 / 60);

	$res = '';
	if ($w)
		$res .= $w.' '.GetMessage("BITRIX_PLANNER_NED");
	if ($d)
		$res .= $d.' '.GetMessage("BITRIX_PLANNER_DN1");
	if ($h)
		$res .= $h.' '.GetMessage("BITRIX_PLANNER_C");
	if ($m)
		$res .= $m.' '.GetMessage("BITRIX_PLANNER_MIN");
	return trim($res);
}

function ImNotify($from, $to, $subject, $body)
{
	if ($from != $to)
		return CIMMessenger::Add(array(
			'TITLE' => $subject,
			'MESSAGE' => $body,
			'TO_USER_ID' => $to,
			'FROM_USER_ID' => $from,
			'MESSAGE_TYPE' => 'S', # P - private chat, G - group chat, S - notification
			'NOTIFY_MODULE' => 'intranet',
			'NOTIFY_TYPE' => 2,  # 1 - confirm, 2 - notify single from, 4 - notify single
		));
}

function GetPeriod($f)
{
	$diff = MakeTimeStamp($f['ACTIVE_TO']) - MakeTimeStamp($f['ACTIVE_FROM']);
	if ($diff%86400 == 0)
		$diff += 86400;
	return $diff;
}

function TimestampRemoveTime($time)
{
	return $time - date('H', $time) * 3600 - date('i', $time) * 60 - date('s', $time);
}
