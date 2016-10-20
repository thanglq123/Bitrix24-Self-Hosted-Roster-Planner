<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?

$arMonths = array('', GetMessage("BITRIX_PLANNER_ANVARQ"), GetMessage("BITRIX_PLANNER_FEVRALQ"), GetMessage("BITRIX_PLANNER_MART"), GetMessage("BITRIX_PLANNER_APRELQ"), GetMessage("BITRIX_PLANNER_MAY"), GetMessage("BITRIX_PLANNER_IUNQ"), GetMessage("BITRIX_PLANNER_IULQ"), GetMessage("BITRIX_PLANNER_AVGUST"), GetMessage("BITRIX_PLANNER_SENTABRQ"), GetMessage("BITRIX_PLANNER_OKTABRQ"), GetMessage("BITRIX_PLANNER_NOABRQ"), GetMessage("BITRIX_PLANNER_DEKABRQ"));
 
//echo '<pre>';print_r(array_keys($arResult));echo '</pre>';
//print_r($arResult['ABSENCE_TYPES']);
?>



<style>
<?
/* SET colors for the absence types */
$colorsArray = [
"AA0078",
"005578",
"B3B3D7",
"D1CAB0",
"ff9999",
"ff0000",
"CCCCFF",
"7D9C9F",
"BDD8DA",
"DFEFF0",
"ECECEC",
"B1B1B1",
"ffd11a",
"ff9933",
"ff6600",
"ff5050",
"ff3399",
"ffff33",
"b8b894",
];
$colorWorking = "FFFFFF";
$colorDayoff = "ffff00";
$colorVacation = "00ffff";
$colorTraining = "8cff1a";

$x = 0;
foreach($arResult['ABSENCE_TYPES'] as $i => $k)
{
	if (is_numeric($i)) {
		if($k == "working"){
			echo ".mark-".$k."{
				background-color:#".$colorWorking.";
			}";
		} else if($k == 'dayoff'){
			echo ".mark-".$k."{
				background-color:#".$colorDayoff.";
			}";
		} elseif ($k == 'vacation') {
			echo ".mark-".$k."{
				background-color:#".$colorVacation.";
			}";
		} elseif ($k == 'training') {
			echo ".mark-".$k."{
				background-color:#".$colorTraining.";
			}";
		} 
		else {
			echo ".mark-".$k."{
				background-color:#".$colorsArray[$x].";
			}";
			$x++;
		}
    }	
}

?>
</style>
<?
if ($_REQUEST['export'] == 'report')
{
	$APPLICATION->RestartBuffer();
	Header('Content-Type: application/vnd.ms-excel');
	Header('Content-Disposition: attachment;filename='.sprintf('absence_report_%d.%02d.xls', $arResult['YEAR'], $arResult['MONTH']));
	echo '<html>
		<head>
		<meta http-equiv="Content-Type" content="text/html; charset='.LANG_CHARSET.'">
		</head>
		<body>';
	echo '<table>';
		
		echo '<tr style="border-bottom:solid 1px #000">';
		echo '<td>Name</td>';
		echo '<td>Absence Type</td>';
		echo '<td>From</td>';
		echo '<td>To</td>';
		echo '<td>Duration</td>';
		echo '<td>Reason</td>';
		echo '<td>Confirmation</td>';
		echo '</tr>';

	foreach($arResult['PERIOD'] as $f)
	{

		// IF WORKING OR TRAINING PASS *******************
		if(strtolower($f['CODE']) == 'working' || strtolower($f['CODE']) == 'training')
			continue;


		$uid = intval($f['PROPERTY_USER_VALUE']);
		if ($uid != $arResult['USER_ID'] && !$arResult['ADMIN'] && !$arResult['HR'])
			continue;

		if ($f['ACTIVE'] == 'Y' && date('Y', MakeTimeStamp($f['ACTIVE_TO'])) != $arResult['YEAR'])
			continue;
		
		echo '<tr>';
		echo '<td>'.htmlspecialcharsbx($arResult['USERS'][$uid]['NAME'].' '.$arResult['USERS'][$uid]['LAST_NAME']).'</td>';
		echo '<td>'.htmlspecialcharsbx($arResult['TYPES'][$f['CODE']]).'</td>';
		echo '<td>'.$f['ACTIVE_FROM'].'</td>';
		echo '<td>'.$f['ACTIVE_TO'].'</td>';
		echo '<td>'.$f['HUMAN_TIME'].'</td>';
		echo '<td>'.$f['PREVIEW_TEXT'].'</td>';
		echo '<td>'.($f['ACTIVE'] == 'Y' ? GetMessage("BITRIX_PLANNER_PODTVERJDEN") : GetMessage("BITRIX_PLANNER_NE_PODTVERJDEN")).'</td>';
		echo '</tr>';
	}
	echo '</table></body></html>';
	die;
}
elseif ($_REQUEST['export'] == 'summary')
{
	$APPLICATION->RestartBuffer();
	Header('Content-Type: application/vnd.ms-excel');
	Header('Content-Disposition: attachment;filename='.sprintf('absence_summary_%d.%02d.xls', $arResult['YEAR'], $arResult['MONTH']));

	echo '<html>
		<head>
		<meta http-equiv="Content-Type" content="text/html; charset='.LANG_CHARSET.'">
		</head>
		<body>';
	echo '<table>'.
		'<tr><td colspan='.(count($arResult['TYPES']) + 1).' align=center><b>'.$arMonths[$arResult['MONTH']].' '.$arResult['YEAR'].'</b></td></tr>'.
		'<tr><th>'.GetMessage("BITRIX_PLANNER_SOTRUDNIK").'</th>';
	foreach($arResult['TYPES'] as $type)
	{
		echo '<th>'.htmlspecialcharsbx($type).'</th>';
	}
	echo '</tr>'."\n";

	foreach($arResult['USERS'] as $f)
	{
		echo "\n".'<tr><td>'.htmlspecialcharsbx($f['NAME'].' '.$f['LAST_NAME']).'</td>';
		foreach($arResult['TYPES'] as $type_id => $type)
		{
			$time = $arResult['SUMMARY'][$f['ID']][$arResult['ABSENCE_TYPES'][$type_id]];
			echo '<td>'.MakeHumanTime($time).'</td>';
		}
	}
	echo '</table></body></html>';
	die;
}
?>
<div id="holidays">
<?
if ($arResult['ERROR'])
	echo '<div style="border:2px solid red;color:red;padding:10px;margin:10px;display:inline-block;border-radius:10px">'.$arResult['ERROR'].'</div>';

?>


<?
$arrayusers = array();
foreach($arResult['USERS'] as $f)
{
//echo "<br> id : ".$f['ID'].' name: '.$f['NAME'].' '.$f['LAST_NAME'];
$arrayusers[$f['ID']] = $f['NAME'].' '.$f['LAST_NAME'];
}
?>
<script src="//api.bitrix24.com/api/v1/"></script>


<script>

	var day_from = 0;
	var day_to = 0;
	var month = '<?=sprintf('%02d',$arResult['MONTH'] )?>';
	var year = <?=$arResult['YEAR']?>;
	var last_day = <?=$arResult['LAST_DAY']?>;
	var day_left = <?=intval($arResult['USERS'][$arResult['USER_ID']]['day_left'])?>;
	var base_url = '<?=$arResult['BASE_URL']?>';


	try {
		var user_obj_json = '<?=json_encode($arrayusers)?>';
		var user_obj = JSON.parse(user_obj_json);
		console.log(user_obj);
	} catch (e){

	}

	function GetDay(ob)
	{
		if($(ob).hasClass('day_number')){
			var str = $(ob).attr('id');
			var partsOfStr = str.split('_');
			return partsOfStr[1];
		} 
	}

	function GetRowID(ob)
	{
		if($(ob).hasClass('day_number')){
			var str = $(ob).attr('id');
			var partsOfStr = str.split('_');
			return partsOfStr[2];
		} 	
	}

	function StartSelect(ob)
	{

		console.log('StartSelect');
		
		day_from = GetDay(ob);
		console.log('From : '+day_from);

		Mark(ob);
	}

	function EndSelect(ob)
	{

		console.log('EndSelect');
		//console.log(ob);

		ShowEditForm('<?=GetMessageJS("BITRIX_PLANNER_DOBAVLENIE_ZAPISI")?>');
		document.forms.add_form.action.value = 'add';

		// add use id to form
		document.forms.add_form.userid.value = GetRowID(ob);

		v = Math.min(day_from, day_to);
		if (v < 10)
			v = '0' + v;
		document.forms.add_form.day_from.value = v + '/' + month + '/' +  year;
		v = Math.max(day_from, day_to);
		if (v < 10)
			v = '0' + v;
		document.forms.add_form.day_to.value = v + '/' + month + '/' +  year;

		type  = '<?=!$arResult['COUNT_DAYS'] || $arResult['USERS'][$arResult['USER_ID']]['day_left'] > 0 ? 'VACATION' : ''?>'
		//console.log(type);
		SetType(type);
		day_from = 0;
	}

	function EditVacation(id, from, to, type, PREVIEW_TEXT, action)
	{
		if (action)
		{
			ShowEditForm('<?=GetMessageJS("BITRIX_PLANNER_DOBAVLENIE_ZAPISI")?>');
			document.forms.add_form.action.value = 'add';
		}
		else
		{
			ShowEditForm('<?=GetMessageJS("BITRIX_PLANNER_IZMENENIE_ZAPISI")?>');
			document.forms.add_form.action.value = 'edit';
		}
		document.forms.add_form.id.value = id;
		document.forms.add_form.day_from.value = from;
		document.forms.add_form.day_to.value = to;
		document.forms.add_form.PREVIEW_TEXT.value = PREVIEW_TEXT;
		SetType(type);
	}

	function SetType(type)
	{
		var sel = document.forms.add_form.event_type;
		for(i=0; i < sel.options.length; i++)
			sel.options[i].selected = sel.options[i].value == type;
	}

	function SetApprvEditDeleteFormType(type)
	{
		var sel = document.forms.edit_apprv_delete_form.event_type;
		for(i=0; i < sel.options.length; i++)
			sel.options[i].selected = sel.options[i].value == type;
	}

	function ShowEditForm(text)
	{
		frm = BX('date_edit_form');

		t = (document.documentElement.scrollTop||document.body.scrollTop) + (window.innerHeight - 400) / 2;
		frm.style.top = (t < 0 ? 0 : t) + 'px';
		l = (window.innerWidth - 600) / 2;
		frm.style.left = (l < 0 ? 0 : l) + 'px';
		frm.style.display = '';

		BX('date_edit_title').innerHTML = '<b>' + text + '</b>';

		document.onkeydown = function (e) 
		{ 
			e = e || window.event;
			if (e.keyCode == 27)
			{
				BX('date_edit_form').style.display='none';
			}
		}
	}

	function ShowApprvEditDeleteForm(text)
	{
		frm = BX('date_edit_apprv_delete_form');

		t = (document.documentElement.scrollTop||document.body.scrollTop) + (window.innerHeight - 400) / 2;
		frm.style.top = (t < 0 ? 0 : t) + 'px';
		l = (window.innerWidth - 600) / 2;
		frm.style.left = (l < 0 ? 0 : l) + 'px';
		frm.style.display = '';

		//BX('date_edit_apprv_delete_title').innerHTML = '<b>Event - Approve / Edit / Delete</b>';

		$('form[name="edit_apprv_delete_form"] .date_edit_apprv_delete_title').html('<b>Event - Approve / Edit / Delete</b>');

		document.onkeydown = function (e) 
		{ 
			e = e || window.event;
			if (e.keyCode == 27)
			{
				BX('date_edit_apprv_delete_form').style.display='none';
			}
		}
	}


	function Mark(ob)
	{

		//console.log('Mark');
		//console.log(ob);
		//console.log(GetRowID(ob));

		var row_id = GetRowID(ob);

		if (day_from > 0)
		{
			day_to_tmp = GetDay(ob);

			//console.log(day_to_tmp);

			min = Math.min(day_from, day_to_tmp);
			max = Math.max(day_from, day_to_tmp);
			cnt = max - min + 1;

			day_to = day_to_tmp;
			for(i=1;i<=last_day;i++)
			{
				color = i >= min && i <= max ? 'rgb(136, 168, 226)' : '';
				//ob = document.getElementById('day_' + i);
				//ob = document.getElementById('day_' + i + '_' + GetRowID(ob));
				//ob.style.background = color;

				var ob = $('#day_' + i + '_' + row_id);
				$(ob).css('background-color',color);

				ob.title = i == day_to ? '<?=GetMessageJS("BITRIX_PLANNER_PRODOLJITELQNOSTQ")?>' + cnt + ' <?=GetMessageJS("BITRIX_PLANNER_DN")?>' : '';
			}
		}
	}

	function DeleteVacation(id)
	{
		if (confirm('<?=GetMessageJS("BITRIX_PLANNER_UDALITQ_ZAPISQ")?>'))
			document.location = '<?=$arResult['BASE_URL']?>&action=delete&id=' + id;
	}

	function ApproveVacation(id, from, to, type, PREVIEW_TEXT)
	{
		if (confirm('<?=GetMessageJS("BITRIX_PLANNER_PODTVERDITQ_ZAPISQ")?>'))
			document.location = '<?=$arResult['BASE_URL']?>&action=approve&id=' + id;

	}

	function UnApproveVacation(id)
	{
		if (confirm('<?=GetMessageJS("BITRIX_PLANNER_VERNUTQ_STATUS_NEPOD")?>'))
			document.location = '<?=$arResult['BASE_URL']?>&action=unapprove&id=' + id;
	}

	/* ******************* */

	function ApproveEditDeleteVacation(id, uid, from, to, type, PREVIEW_TEXT)
	{

		console.log('Approve');
		console.log(uid);
		console.log(id);
		console.log(from);
		console.log(to);
		console.log(type);
		console.log(PREVIEW_TEXT);


		$('form[name="edit_apprv_delete_form"] #edit-event-section').css('display','none');
		$('form[name="edit_apprv_delete_form"] #approve-form').css('display','block');

		ShowApprvEditDeleteForm('<?=GetMessageJS("BITRIX_PLANNER_IZMENENIE_ZAPISI")?>');
		
		document.forms.edit_apprv_delete_form.action.value = 'edit';	
		document.forms.edit_apprv_delete_form.id.value = id;
		document.forms.edit_apprv_delete_form.day_from.value = from;
		document.forms.edit_apprv_delete_form.day_to.value = to;
		document.forms.edit_apprv_delete_form.PREVIEW_TEXT.value = PREVIEW_TEXT;
		$('form[name="edit_apprv_delete_form"] #user_name').text(user_obj[uid]);

		$('form[name="edit_apprv_delete_form"] #display_day_from').text(from);
		$('form[name="edit_apprv_delete_form"] #display_day_to').text(to);
		$('form[name="edit_apprv_delete_form"] #display_user_name').text(user_obj[uid]);

		$('form[name="edit_apprv_delete_form"] #btn-edit').click(function(e) {
			e.preventDefault();
			
			$('form[name="edit_apprv_delete_form"] #edit-event-section').css('display','block');
			$('form[name="edit_apprv_delete_form"] #approve-form').css('display','none');

		});
		


		$('form[name="edit_apprv_delete_form"] #btn-approve').click(function(e) {
			e.preventDefault();
			document.location = base_url + '&action=approve&id=' + id;   
		});
		
		$('form[name="edit_apprv_delete_form"] #btn-delete').click(function(e) {
			e.preventDefault();
			document.location = base_url + '&action=delete&id=' + id;   
		});

		SetApprvEditDeleteFormType(type);

	}

	/* ***************** */


	function AddDaysLeft(id)
	{
		inp = BX('days_left_' + id);
		inp.value = 28;
		inp.style.display = '';
		inp.focus();
		inp.onkeypress = 
			function(event) 
			{
				if (event.keyCode==13)
				{
					inp = BX('days_left_' + id);
					document.location = '<?=$arResult['BASE_URL']?>&set_user_id=' + id + '&add_days=' + encodeURIComponent(inp.value);
				}
			}
	}

	function RefreshList(department)
	{
		document.location = '<?=$arResult['BASE_URL']?>&department=' + department;
	}

	function showEdit_section()
	{
		
		$('form[name="edit_apprv_delete_form"] #btn-edit').click(function(e) {
			e.preventDefault();
			console.log('here..../');

			
		});
		
	}
</script>

<div id="dialog-form" title="Create new user" style="display:none;">
  <p class="validateTips">All form fields are required.</p>
 
  <form>
    <fieldset>
      <label for="name">Name</label>
      <input type="text" name="name" id="name" value="Jane Smith" class="text ui-widget-content ui-corner-all">
      <label for="email">Email</label>
      <input type="text" name="email" id="email" value="jane@smith.com" class="text ui-widget-content ui-corner-all">
      <label for="password">Password</label>
      <input type="password" name="password" id="password" value="xxxxxxx" class="text ui-widget-content ui-corner-all">
 
      <!-- Allow form submission with keyboard without duplicating the dialog button -->
      <input type="submit" tabindex="-1" style="position:absolute; top:-1000px">
    </fieldset>
  </form>
</div>

<div style="display:table;padding:10px;text-align:center">
<div style="font-size:140%">
<?
for($i = date('Y') - 1;$i <= date('Y') + 1; $i++)
{
	echo $i == $arResult['YEAR'] ? '<b>'.$i.'</b>' : '<a href="'.$arResult['BASE_URL'].'&year='.$i.'">'.$i.'</a>';
	echo ' &nbsp;  &nbsp; ';
}
?>
</div>
<?

for ($i=1; $i<=12; $i++)
{
	if ($i == $arResult['MONTH'] )
		echo '<b>'.$arMonths[$arResult['MONTH'] ].'</b>';
	else
		echo '<a href="'.$arResult['BASE_URL'].'&month='.$i.'">'.$arMonths[$i].'</a>';
	echo ' &nbsp; ';
}

?>
<br>
<br>


<h1>
	<a href="<?=$arResult['BASE_URL']?>&year=<?=$arResult['PREV_YEAR']?>&month=<?=$arResult['PREV_MONTH']?>">&#8592;</a>
	<?=$arMonths[$arResult['MONTH'] ].' '.$arResult['YEAR']?>
	<a href="<?=$arResult['BASE_URL']?>&year=<?=$arResult['NEXT_YEAR']?>&month=<?=$arResult['NEXT_MONTH']?>">&#8594;</a>
</h1>

<table id="main-table">
	<tr>
		<th colspan="<?=$arResult['LAST_DAY']+1?>" style="background-color:#CCC" align=left>
	<? 
	if ($arResult['ADMIN'])
	{
	?>
		<?=GetMessage("BITRIX_PLANNER_PODRAZDELENIE")?><select onchange="RefreshList(this.value)"><?
		foreach($arResult['DEPARTMENT_LIST'] as $f)
		{
			echo '<option value='.$f['ID'].($arResult['DEPARTMENT_ID'] == $f['ID'] ? ' selected' : '').'>'.htmlspecialcharsbx($f['DEPTH_NAME']).'</option>';
		}
		?></select>
		<label><input type="checkbox" <?=$arResult['RECURSIVE'] ? 'checked' : ''?> onchange="document.location='<?=$arResult['BASE_URL']?>&recursive=' + (this.checked ? 1 : 0)"> <?=GetMessage("BITRIX_PLANNER_VKLUCAA_PODOTDELY")?></label>
	<?
	}

	if ($arResult['USERS'][$arResult['USER_ID']])
	{ 
	?>
		<div class="plus-btn" onclick="EditVacation(0, '', '', '', '', 'add')">+</div>
	<? 
	} 
	?>
		</th>
	</tr>
<tr><th style="min-width:300px"><? // htmlspecialcharsbx($arResult['DEPARTMENT_LIST'][$arResult['DEPARTMENT_ID']]['NAME'])?></th>
<?
for($i=1; $i<=$arResult['LAST_DAY']; $i++)
{
	echo '<td class="day-title'.(date('N',mktime(1,1,1,$arResult['MONTH'] ,$i,$arResult['YEAR'])) > 5 ? '-holiday' : '').(sprintf('%4d%02d%02d',$arResult['YEAR'],$arResult['MONTH'] ,$i) <= date('Ymd') ? '-past' : '').'">'.$i.'</td>';
}

foreach($arResult['USERS'] as $f)
{

	$d = intval($f['day_left']);
	echo '<tr '.($f['ID'] == $arResult['USER_ID'] ? 'class="current-user"' : 'class="any-user"').' >
		<td style="text-align:left" id="system_person_'.$f['ID'].'">'. 
		//'ID('.$f['ID'].') '.
		($arResult['ADMIN'] ? '<a href="'.$arResult['BASE_URL'].'&set_user_id='.$f['ID'].'">' : '').htmlspecialcharsbx($f['NAME'].' '.$f['LAST_NAME']).'</a>'.
		($arResult['COUNT_DAYS'] ? ' ('.GetMessage("BITRIX_PLANNER_DNEY").'<b>'.$d.'</b>)'.($arResult['ALLOW_DAYS_ADD'] ? ' <a href="javascript:AddDaysLeft('.$f['ID'].')">+</a><input size=3 id="days_left_'.$f['ID'].'" style="display:none">' : '') : '').
		'<script>BX.tooltip('.$f["ID"].', "system_person_'.$f['ID'].'", "");</script>'.
		'</td>';

	for($i=1; $i<=$arResult['LAST_DAY']; $i++)
	{

		$id = $f['ID'] == $arResult['USER_ID'] ? ' id="day_'.$i.'"' : '';

		$m = $arResult['MARKER'][$f['ID']][$i];
		$class = 'mark-'.strtolower($m['CODE']);
		$class_past = sprintf('%4d%02d%02d',$arResult['YEAR'],$arResult['MONTH'] ,$i) <= date('Ymd') ? 'day-past' : '';

		if ($m['ACTIVE'] == 'N') {
			
			/* if user is its own record should allow approval as well */
			/* conditions  
			Admin 
			&&
			User

			can approve 
			*/

			$onclick = ( $arResult['ADMIN']? 
				' onclick="ApproveEditDeleteVacation('.$m['ID'].', \''.$f['ID'].'\', \''.$m['ACTIVE_FROM'].'\', \''.$m['ACTIVE_TO'].'\', \''.$m['CODE'].'\', \''.CUtil::JSEscape(str_replace('"', '&quot;', $m['~PREVIEW_TEXT'])).'\')"' 
				: 
				'');
			
			echo '<td '.
				$onclick.
				' class="day-saved '.$class_past.'"'.
				' title="'.$m['TITLE'].'" '.$id.'><div style="border-radius:10px" class="'.$class.($m['PARTIAL'] ? ' day-partial' : '').'"'.
				($arResult['ADMIN'] != true && $arResult['USER_ID'] == $f['ID'] ? 
					' onclick="EditVacation('.$m['ID'].', \''.$m['ACTIVE_FROM'].'\', \''.$m['ACTIVE_TO'].'\', \''.$m['CODE'].'\', \''.CUtil::JSEscape(str_replace('"', '&quot;', $m['~PREVIEW_TEXT'])).'\')"' 
					: 
					'')
				.'>&nbsp;</div></td>';

		
		} elseif ($m['ACTIVE'] == 'Y') {

			/* conditions  
			Admin 
			&&
			User

			can approve
			*/

			$onclick = ($arResult['ADMIN'] ? ' ondblclick="UnApproveVacation('.$m['ID'].')" ' : '');
			
			if($class == 'mark-working'){
				echo '<td '.
				'style="padding:0px" class="'.$class_past.'"'.
				$onclick.
				' title="'.$m['TITLE'].'"'.$id.'><div class="active-day '.$class.($m['PARTIAL'] ? ' day-partial' : '').'"><span class="np-td-text" style="color:#8cff1a;">W</span></div></td>';
			} elseif ($class == 'mark-vacation') {
				echo '<td '.
				'style="padding:0px" class="'.$class_past.'"'.
				$onclick.
				' title="'.$m['TITLE'].'"'.$id.'><div class="active-day '.$class.($m['PARTIAL'] ? ' day-partial' : '').'"><span class="np-td-text">AL</sApan></div></td>';
			} elseif ($class == 'mark-training') {
				echo '<td '.
				'style="padding:0px" class="'.$class_past.'"'.
				$onclick.
				' title="'.$m['TITLE'].'"'.$id.'><div class="active-day '.$class.($m['PARTIAL'] ? ' day-partial' : '').'"><span class="np-td-text">T</sApan></div></td>';
			} else {
				echo '<td '.
				'style="padding:0px" class="'.$class_past.'"'.
				$onclick.
				' title="'.$m['TITLE'].'"'.$id.'><div class="active-day '.$class.($m['PARTIAL'] ? ' day-partial' : '').'">&nbsp;</div></td>';
			}
		} elseif ($f['ID'] == $arResult['USER_ID'] || $arResult['ADMIN']) { // $id
		
			echo '<td onmouseover="Mark(this)" onmousedown="StartSelect(this)" onmouseup="EndSelect(this)" class="'.$class_past.' day_number" id="day_'.$i.'_'.$f['ID'].'"> </td>';
			
		} else {
			echo '<td class="'.$class_past.'"> </td>';
		}



		/*
		$id = $f['ID'] == $arResult['USER_ID'] ? ' id="day_'.$i.'"' : '';
		$m = $arResult['MARKER'][$f['ID']][$i];
		$class = 'mark-'.strtolower($m['CODE']);
		$class_past = sprintf('%4d%02d%02d',$arResult['YEAR'],$arResult['MONTH'] ,$i) <= date('Ymd') ? 'day-past' : '';
		if ($m['ACTIVE'] == 'N') {
			$onclick = ($arResult['ADMIN'] ? ' ondblclick="ApproveVacation('.$m['ID'].')" ' : '');
			
			echo '<td '.
				$onclick.
				' class="day-saved '.$class_past.'"'.
				' title="'.$m['TITLE'].'" '.$id.'><div class="'.$class.($m['PARTIAL'] ? ' day-partial' : '').'"'.
				($arResult['USER_ID'] == $f['ID'] ? ' style="cursor:pointer;border-radius:10px" onclick="EditVacation('.$m['ID'].', \''.$m['ACTIVE_FROM'].'\', \''.$m['ACTIVE_TO'].'\', \''.$m['CODE'].'\', \''.CUtil::JSEscape(str_replace('"', '&quot;', $m['~PREVIEW_TEXT'])).'\')"' : ' style="border-radius:10px"').
				'>&nbsp;</div></td>';
		
		} elseif ($m['ACTIVE'] == 'Y') {

			$onclick = ($arResult['ADMIN'] ? ' ondblclick="UnApproveVacation('.$m['ID'].')" ' : '');
			
			if($class == 'mark-working'){
				echo '<td '.
				'style="padding:0px" class="'.$class_past.'"'.
				$onclick.
				' title="'.$m['TITLE'].'"'.$id.'><div class="active-day '.$class.($m['PARTIAL'] ? ' day-partial' : '').'"><span class="np-td-text" style="color:#8cff1a;">W</span></div></td>';
			} elseif ($class == 'mark-vacation') {
				echo '<td '.
				'style="padding:0px" class="'.$class_past.'"'.
				$onclick.
				' title="'.$m['TITLE'].'"'.$id.'><div class="active-day '.$class.($m['PARTIAL'] ? ' day-partial' : '').'"><span class="np-td-text">AL</sApan></div></td>';
			} elseif ($class == 'mark-training') {
				echo '<td '.
				'style="padding:0px" class="'.$class_past.'"'.
				$onclick.
				' title="'.$m['TITLE'].'"'.$id.'><div class="active-day '.$class.($m['PARTIAL'] ? ' day-partial' : '').'"><span class="np-td-text">T</sApan></div></td>';
			} else {
				echo '<td '.
				'style="padding:0px" class="'.$class_past.'"'.
				$onclick.
				' title="'.$m['TITLE'].'"'.$id.'><div class="active-day '.$class.($m['PARTIAL'] ? ' day-partial' : '').'">&nbsp;</div></td>';
			}
		} elseif ($id) {
			echo '<td onmouseover="Mark(this)" onmousedown="StartSelect(this)" onmouseup="EndSelect(this)"'.$id.'> </td>';
		} else
			echo '<td class="'.$class_past.'"> </td>';
			*/

	}
}
?>
</tr>
</table>
</div>

<?

/* PLANNED LEAVES REPORT */

echo '<div style="float:left;padding-right:10px;padding-left:10px"><table style="border-collapse:collapse">';
echo '<tr><td colspan=7 style="background-color:#CCC;text-align:center"><b>'.GetMessage("BITRIX_PLANNER_ZAPLANIROVANNYE_OTSU").'</b>&nbsp;<div style="float:right"><a href="'.$arResult['BASE_URL'].'&export=report">'.GetMessage("BITRIX_PLANNER_EKSPORT_V").'</a></div></td></tr>';
// $tt = strtotime($arResult['YEAR'].'-'.$arResult['MONTH'].'-01');
foreach($arResult['PERIOD'] as $f)
{

	$uid = intval($f['PROPERTY_USER_VALUE']);
	if ($uid != $arResult['USER_ID'] && !$arResult['ADMIN'] && !$arResult['HR'])
		continue;

	if ($f['ACTIVE'] == 'Y' && date('Y', MakeTimeStamp($f['ACTIVE_TO'])) != $arResult['YEAR'])
		continue;

	// ********
	// IF WORKING OR TRAINING PASS ****************
	if(strtolower($f['CODE']) == 'working' || strtolower($f['CODE']) == 'training')
		continue;


	$arActions = array();
	if ($f['ACTIVE'] == 'Y')
	{
		$class = 'mark-'.strtolower($f['CODE']);
		if ($arResult['ADMIN'])
			$arActions[] = '<a href="javascript:UnApproveVacation('.$f['ID'].')" class="link-disapprove">'.GetMessage("BITRIX_PLANNER_SNATQ_PODTVERJDENIE").'</a>';
	}
	else
	{
		$class = 'mark-'.strtolower($f['CODE']);
		if ($arResult['ADMIN'])
			$arActions[] = '<a href="javascript:ApproveVacation('.$f['ID'].')" class="link-approve">'.GetMessage("BITRIX_PLANNER_PODTVERDITQ").'</a>';
		if ($uid == $arResult['USER_ID'] || $arResult['ADMIN'])
		{
			$arActions[] = '<a href="javascript:EditVacation('.$f['ID'].', \''.$f['ACTIVE_FROM'].'\', \''.$f['ACTIVE_TO'].'\', \''.$f['CODE'].'\', \''.CUtil::JSEscape(str_replace('"', '&quot;', $f['~PREVIEW_TEXT'])).'\')" class="link-edit">'.GetMessage("BITRIX_PLANNER_IZMENITQ").'</a>';
			$arActions[] = '<a href="javascript:DeleteVacation('.$f['ID'].')" class="link-remove">'.GetMessage("BITRIX_PLANNER_UDALITQ").'</a>';
		}
	}

	echo '<tr'.($arResult['ADMIN'] && $uid == $arResult['USER_ID'] ? ' class="current-user"' : '').'>';
	echo '<td><a href="'.$arResult['BASE_URL'].'&set_user_id='.$uid.'">'.htmlspecialcharsbx($arResult['USERS'][$uid]['NAME'].' '.$arResult['USERS'][$uid]['LAST_NAME']).'</a></td>';
	echo '<td class="'.($f['ACTIVE'] == 'Y' ? $class : 'day-saved').'">'.htmlspecialcharsbx($arResult['TYPES'][$f['CODE']]).'</td>';
	echo '<td><a href="'.$arResult['BASE_URL'].'&year='.date('Y',$t = MakeTimeStamp($f['ACTIVE_FROM'])).'&month='.date('n',$t).'&set_user_id='.$uid.'">'.$f['ACTIVE_FROM'].'</a></td>';
	echo '<td><a href="'.$arResult['BASE_URL'].'&year='.date('Y',$t = MakeTimeStamp($f['ACTIVE_TO'])).'&month='.date('n',$t).'&set_user_id='.$uid.'">'.$f['ACTIVE_TO'].'</a></td>';
	echo '<td>'.$f['HUMAN_TIME'].'</td>';
	echo '<td>'.$f['PREVIEW_TEXT'].'</td>';
	echo '<td>'.implode(' / ',$arActions).'</td>';
	echo '</tr>';
}
echo '</table></div>';

/* SUMMARY REPORT */


if ($arResult['SUMMARY'])
{
	$colspan = count($arResult['TYPES']) + 1;
	echo '<div style="float:left;padding:10px;padding-left:10px"><table style="border-collapse:collapse">';
	echo '<tr><td colspan='.$colspan.' style="background-color:#CCC;text-align:center"><b>'.GetMessage("BITRIX_PLANNER_ITOGO_ZA").$arMonths[$arResult['MONTH']].' '.$arResult['YEAR'].'</b>&nbsp;<div style="float:right"><a href="'.$arResult['BASE_URL'].'&export=summary">'.GetMessage("BITRIX_PLANNER_EKSPORT_V").'</a></div></td></tr>';
	echo '<tr><th></th>';
	foreach($arResult['TYPES'] as $type)
	{
		echo '<th>'.htmlspecialcharsbx($type).'</th>';
	}
	echo '</tr>'."\n";

	foreach($arResult['USERS'] as $f)
	{
		$uid = intval($f['ID']);
		if (!$arResult['SUMMARY'][$uid])
			continue;
		echo '<tr'.($arResult['ADMIN'] && $uid == $arResult['USER_ID'] ? ' class="current-user"' : '').'>';
		echo '<td><a href="'.$arResult['BASE_URL'].'&set_user_id='.$uid.'">'.htmlspecialcharsbx($arResult['USERS'][$uid]['NAME'].' '.$arResult['USERS'][$uid]['LAST_NAME']).'</a></td>';
		foreach($arResult['TYPES'] as $type_id => $type)
		{
			$time = $arResult['SUMMARY'][$uid][$arResult['ABSENCE_TYPES'][$type_id]];
			echo '<td>'.MakeHumanTime($time).'</td>';
		}
	}
	echo '</table></div>';
}

include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/interface/admin_lib.php');
$uid = $arResult['USER_ID'];
?>



<!-- +++++++++++++++ EDIT FORM ++++++++++++++++++++++  -->



<div style="position:absolute;top:0px;left:0px;display:none;background-color:#eee;padding:6px;border-radius:4px" id=date_edit_form>
<form method=post name=add_form>
<input type=hidden name=action>
<input type=hidden name=id>
<input type=hidden name=userid>
	<table style="border-collapse:collapse;background-color:#FFF">
		<tr><td colspan=2 style="background-color:#CCC;text-align:center" id="date_edit_title">ddd</td></tr>
		<tr><td><?=GetMessage("BITRIX_PLANNER_SOTRUDNIK")?></td><td><?=htmlspecialcharsbx($arResult['USERS'][$uid]['NAME'].' '.$arResult['USERS'][$uid]['LAST_NAME'])?></td></tr>
		<tr><td><?=GetMessage("BITRIX_PLANNER_DATA_NACALA")?></td><td><input name=day_from size=16 autocomplete="off"> 
		<?$APPLICATION->IncludeComponent("bitrix:main.calendar","",Array(
			"SHOW_INPUT" => "N",
			"FORM_NAME" => "add_form",
			"INPUT_NAME" => "day_from",
			"INPUT_NAME_FINISH" => "",
			"INPUT_VALUE" => "",
			"INPUT_VALUE_FINISH" => "", 
			"SHOW_TIME" => $arParams['SHOW_TIME'],
			"HIDE_TIMEBAR" => "N"
			)
		);?>	
		
		</td></tr>
		<tr><td><?=GetMessage("BITRIX_PLANNER_DATA_KONCA")?></td><td><input name=day_to size=16 autocomplete="off">
		<?$APPLICATION->IncludeComponent("bitrix:main.calendar","",Array(
			"SHOW_INPUT" => "N",
			"FORM_NAME" => "add_form",
			"INPUT_NAME" => "day_to",
			"INPUT_NAME_FINISH" => "",
			"INPUT_VALUE" => "",
			"INPUT_VALUE_FINISH" => "", 
			"SHOW_TIME" => $arParams['SHOW_TIME'],
			"HIDE_TIMEBAR" => "N"
			)
		);?>	
		
		</td></tr>
		<tr><td><?=GetMessage("BITRIX_PLANNER_TIP_ZAPISI")?></td><td>
			<select name=event_type size=<?=count($arResult['TYPES'])?>>
			<?
				foreach($arResult['TYPES'] as $k => $v)
					echo '<option value="'.htmlspecialcharsbx($k).'">'.htmlspecialcharsbx($v).'</option>';
			?>
			</select>
		</td></tr>
		<tr><td><?=GetMessage("BITRIX_PLANNER_PRIMECANIE")?></td><td><input name=PREVIEW_TEXT style="width:100%"></td></tr>
		<tr><td colspan=2><input type=submit value="<?=GetMessage("BITRIX_PLANNER_SOHRANITQ")?>"> <input type=button value="<?=GetMessage("BITRIX_PLANNER_OTMENA")?>" onclick="BX('date_edit_form').style.display='none'"></td></tr>
	</table>
</form>
</div>


<!-- +++++++++++++++ EDIT / APPROVE / DELETE FORM ++++++++++++++++++++++  -->

<div style="position:absolute;top:0px;left:0px;display:none;background-color:#eee;padding:6px;border-radius:4px" id=date_edit_apprv_delete_form>
<form method=post name=edit_apprv_delete_form>  
<input type=hidden name=action>
<input type=hidden name=id>
<input type=hidden name=userid>
	<table style="border-collapse:collapse;background-color:#FFF;display: none;" id="edit-event-section" >
		<tr><td colspan=2 style="background-color:#CCC;text-align:center" class="date_edit_apprv_delete_title"></td></tr>
		<tr>
		<td><?=GetMessage("BITRIX_PLANNER_SOTRUDNIK")?></td>
		<td><span id="user_name"></span></td>
		</tr>
		<tr><td><?=GetMessage("BITRIX_PLANNER_DATA_NACALA")?></td><td><input name=day_from size=16 autocomplete="off"> 
		<?$APPLICATION->IncludeComponent("bitrix:main.calendar","",Array(
			"SHOW_INPUT" => "N",
			"FORM_NAME" => "edit_apprv_delete_form",
			"INPUT_NAME" => "day_from",
			"INPUT_NAME_FINISH" => "",
			"INPUT_VALUE" => "",
			"INPUT_VALUE_FINISH" => "", 
			"SHOW_TIME" => $arParams['SHOW_TIME'],
			"HIDE_TIMEBAR" => "N"
			)
		);?>	
		
		</td></tr>
		<tr><td><?=GetMessage("BITRIX_PLANNER_DATA_KONCA")?></td><td><input name=day_to size=16 autocomplete="off">
		<?$APPLICATION->IncludeComponent("bitrix:main.calendar","",Array(
			"SHOW_INPUT" => "N",
			"FORM_NAME" => "edit_apprv_delete_form",
			"INPUT_NAME" => "day_to",
			"INPUT_NAME_FINISH" => "",
			"INPUT_VALUE" => "",
			"INPUT_VALUE_FINISH" => "", 
			"SHOW_TIME" => $arParams['SHOW_TIME'],
			"HIDE_TIMEBAR" => "N"
			)
		);?>	
		
		</td></tr>
		<tr><td><?=GetMessage("BITRIX_PLANNER_TIP_ZAPISI")?></td><td>
			<select name=event_type size=<?=count($arResult['TYPES'])?>>
			<?
				foreach($arResult['TYPES'] as $k => $v)
					echo '<option value="'.htmlspecialcharsbx($k).'">'.htmlspecialcharsbx($v).'</option>';
			?>
			</select>
		</td></tr>
		<tr><td><?=GetMessage("BITRIX_PLANNER_PRIMECANIE")?></td><td><input name=PREVIEW_TEXT style="width:100%"></td></tr>
		<tr>
		<td colspan=2>
		<input type=submit value="<?=GetMessage("BITRIX_PLANNER_SOHRANITQ")?>" style="float:left; background-color:#fff;"> 
		<input type=button value="<?=GetMessage("BITRIX_PLANNER_OTMENA")?>" onclick="BX('date_edit_apprv_delete_form').style.display='none'" style="float:left; background-color:#fff;">
		</td>
		</tr>
	</table>
	<table id="approve-form">
	<tr>
		<td colspan=2 style="background-color:#CCC;text-align:center" class="date_edit_apprv_delete_title"></td>
	</tr>
	<tr>
		<td><?=GetMessage("BITRIX_PLANNER_SOTRUDNIK")?></td>
		<td><span id="display_user_name"></span></td>
	</tr>
	<tr>
		<td><?=GetMessage("BITRIX_PLANNER_DATA_NACALA")?></td>
		<td id="display_day_from"> </td>
		</tr>
	<tr>
		<td><?=GetMessage("BITRIX_PLANNER_DATA_KONCA")?></td>
		<td id="display_day_to"></td>
	</tr>
	<tr>
	<td colspan="2">
		<button id="btn-approve" style="float:right; background-color:#5cd65c;">Approve</button>
		<button id="btn-delete" style="float:right;background-color:#ff3333;">Delete</button>
		<button id="btn-edit" style="float:left; background-color:#33adff;">Edit Event</button>
	</td>
	</tr>
	</table>


</form>
</div>





<div style="clear:both;padding:10px">
<table style="border-collapse:collapse">
	<tr><td style="background-color:rgb(136, 168, 226)" width=20></td> <td><?=GetMessage("BITRIX_PLANNER_OTMECENO_NE_SOHRANE")?></td></tr>
	<tr><td class="day-saved" width=20></td> <td><?=GetMessage("BITRIX_PLANNER_SOHRANENO_NO_NE_POD")?></td></tr>
	<?
		foreach($arResult['TYPES'] as $k => $v)
			echo '<tr><td class="mark-'.strtolower($k).'">'.
		(strtolower($k) == 'working'?'<span class="np-td-text" style="color:#8cff1a;">W</span>':'').
		(strtolower($k) == 'vacation'?'<span class="np-td-text">AL</span>':'').
		(strtolower($k) == 'training'?'<span class="np-td-text">T</span>':'')
		.'</td> <td>'.htmlspecialcharsbx($v).'</td></tr>';
	?>
</table>
</div>

</div>
