<?php
defined('_SECURE_') or die('Forbidden');
if(!isadmin()){forcenoaccess();};

switch ($op) {
	case "all_inbox":
		$search_category = array(_('User') => 'username', _('Time') => 'in_datetime', _('From') => 'in_sender', _('Message') => 'in_msg');
		$base_url = 'index.php?app=menu&inc=all_inbox&op=all_inbox';
		$search = themes_search($search_category, $base_url);
		$conditions = array('in_hidden' => 0);
		$keywords = $search['dba_keywords'];
		$join = 'INNER JOIN '._DB_PREF_.'_tblUser AS B ON in_uid=B.uid';
		$count = dba_count(_DB_PREF_.'_tblUserInbox', $conditions, $keywords, '', $join);
		$nav = themes_nav($count, $search['url']);
		$extras = array('ORDER BY' => 'in_id DESC', 'LIMIT' => $nav['limit'], 'OFFSET' => $nav['offset']);
		$list = dba_search(_DB_PREF_.'_tblUserInbox', '*', $conditions, $keywords, $extras, $join);

		$actions_box = "
			<div id=actions_box>
			<div id=actions_box_left><input type=submit name=go value=\""._('Export')."\" class=button /></div>
			<div id=actions_box_center>".$nav['form']."</div>
			<div id=actions_box_right><input type=submit name=go value=\""._('Delete')."\" class=button /></div>
			</div>";

		$content = "
			<h2>"._('All inbox')."</h2>
			<p>".$search['form']."</p>
			<form name=\"fm_inbox\" action=\"index.php?app=menu&inc=all_inbox&op=actions\" method=post onSubmit=\"return SureConfirm()\">
			".$actions_box."
			<div class=table-responsive><table class=playsms-table-list>
			<thead>
			<tr>
				<th width=20%>"._('User')."</th>
				<th width=25%>"._('From')."</th>
				<th width=50%>"._('Message')."</th>
				<th width=5% class=\"sorttable_nosort\"><input type=checkbox onclick=CheckUncheckAll(document.fm_inbox)></td>
			</tr>
			</thead>
			<tbody>";

		$i = $nav['top'];
		$j = 0;
		for ($j=0;$j<count($list);$j++) {
			$list[$j] = core_display_data($list[$j]);
			$in_username = $list[$j]['username'];
			$in_id = $list[$j]['in_id'];
			$in_sender = $list[$j]['in_sender'];
			$p_desc = phonebook_number2name($in_sender);
			$current_sender = $in_sender;
			if ($p_desc) {
				$current_sender = "$in_sender<br />$p_desc";
			}
			$in_datetime = core_display_datetime($list[$j]['in_datetime']);
			$msg = $list[$j]['in_msg'];
			$in_msg = core_display_text($msg);
			$reply = '';
			$forward = '';
			if ($msg && $in_sender) {
				$reply = _a('index.php?app=menu&inc=send_sms&op=sendsmstopv&do=reply&message='.urlencode($msg).'&to='.urlencode($in_sender), $core_config['icon']['reply']);
				$forward = _a('index.php?app=menu&inc=send_sms&op=sendsmstopv&do=forward&message='.urlencode($msg), $core_config['icon']['forward']);
			}
			$c_message = "<div id=\"all_inbox_msg\">".$in_msg."</div><div id=\"msg_label\">".$in_datetime."</div><div id=\"msg_option\">".$reply.$forward."</div>";
			$i--;
			$tr_class = ($i % 2) ? "row_odd" : "row_even";
			$content .= "
				<tr class=$tr_class>
					<td>$in_username</td>
					<td>$current_sender</td>
					<td>$c_message</td>
					<td>
						<input type=hidden name=itemid".$j." value=\"$in_id\">
						<input type=checkbox name=checkid".$j.">
					</td>
				</tr>";
		}

		$content .= "
			</tbody>
			</table></div>
			".$actions_box."
			</form>";

		if ($err = $_SESSION['error_string']) {
			echo "<div class=error_string>$err</div>";
		}
		echo $content;
		break;
	case "actions":
		$nav = themes_nav_session();
		$search = themes_search_session();
		$go = $_REQUEST['go'];
		switch ($go) {
			case _('Export'):
				$conditions = array('in_hidden' => 0);
				$join = 'INNER JOIN '._DB_PREF_.'_tblUser AS B ON in_uid=B.uid';
				$list = dba_search(_DB_PREF_.'_tblUserInbox', '*', $conditions, $search['dba_keywords'], '', $join);
				$data[0] = array(_('User'), _('Time'), _('From'), _('Message'));
				for ($i=0;$i<count($list);$i++) {
					$j = $i + 1;
					$data[$j] = array(
						$list[$i]['username'],
						core_display_datetime($list[$i]['in_datetime']),
						$list[$i]['in_sender'],
						$list[$i]['in_msg']);
				}
				$content = csv_format($data);
				$fn = 'all_inbox-'.$core_config['datetime']['now_stamp'].'.csv';
				download($content, $fn, 'text/csv');
				break;
			case _('Delete'):
				for ($i=0;$i<$nav['limit'];$i++) {
					$checkid = $_POST['checkid'.$i];
					$itemid = $_POST['itemid'.$i];
					if(($checkid=="on") && $itemid) {
						$up = array('c_timestamp' => mktime(), 'in_hidden' => '1');
						dba_update(_DB_PREF_.'_tblUserInbox', $up, array('in_id' => $itemid));
					}
				}
				$ref = $nav['url'].'&search_keyword='.$search['keyword'].'&page='.$nav['page'].'&nav='.$nav['nav'];
				$_SESSION['error_string'] = _('Selected incoming SMS has been deleted');
				header("Location: ".$ref);
		}
		break;
}

?>