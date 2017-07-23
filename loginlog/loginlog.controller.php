<?php
/**
 * @class loginlogController
 * @author XEPublic
 * @brief loginlog 모듈의 controller class
 **/

class loginlogController extends loginlog
{
	/**
	 * @brief 초기화
	 */
	public function init()
	{
	}

	/**
	 * @brief 기간 내의 로그인 기록 삭제 (Cron 호출용)
	 */
	public function deleteLogsByCron($type = 'ALL', $period = 1)
	{
		$args = new stdClass;

		switch($type)
		{
			/**
			 * 모든 기록 삭제 시 별도의 매개변수 전달이 필요하지 않음
			 */
			case 'ALL':
				break;
			case 'DAILY':
				$str = '-'. $period. ' day';
				$args->expire_date = date('Ymd000000', strtotime($str)); // -1 day
				break;
			case 'WEEKLY':
				$str = '-'. $period. ' week';
				$args->expire_date = date('Ymd000000', strtotime($str)); // -1 week
				break;
			case 'MONTHLY':
				$str = '-'. $period. ' month';
				$args->expire_date = date('Ymd000000', strtotime($str)); // -1 month
				break;
			case 'YEARLY':
				$str = '-'. $period. ' year';
				$args->expire_date = date('Y00000000', strtotime($str)); // -1 year
				break;
		}

		executeQuery('loginlog.initLoginlogs', $args);
	}

	/**
	 * @brief 주어진 기간 내의 로그인 기록 삭제
	 */
	public function deleteLogsByCronUsingDate($start_date, $end_date)
	{
		$args = new stdClass;
		$args->start_date = $start_date;
		$args->expire_date = $end_date;

		executeQuery('loginlog.initLoginlogs', $args);
	}

	/**
	 * @brief 로그인 전에 실행되는 트리거
	 */
	public function triggerBeforeLogin(&$obj)
	{
		// 넘어온 아이디가 없다면 실행 중단
		if(!$obj->user_id)
		{
			return new Object();
		}

		/**
		 * 입력한 비밀번호가 없다면 실행 중단
		 * 로그인 유지를 선택하고 로그인을 한 경우 비밀번호 입력 없이 로그인 과정을 거치게 됨
		 */
		if(!$obj->password)
		{
			return new Object();
		}

		// 대상 회원의 비밀번호와 회원 번호를 구함
		$output = executeQuery('loginlog.getMemberPassword', $obj);

		// 존재하지 않는 회원이라면 기록하지 않음
		if(!$output->data)
		{
			return new Object();
		}

		$member_srl = $output->data->member_srl;

		// 대상 회원의 비밀번호
		$password = $output->data->password;

		// memberModel 객체 생성
		$oMemberModel = getModel('member');

		// 비밀번호가 맞다면 기록하지 않음
		if($oMemberModel->isValidPassword($password, $obj->password))
		{
			return new Object();
		}

		// loginlogModel 객체 생성
		$oLoginlogModel = getModel('loginlog');

		// 로그인 기록 모듈의 설정을 가져옵니다
		$config = $oLoginlogModel->getModuleConfig();

		// 로그인 기록 대상 그룹이 설정되어 있다면...
		if(is_array($config->target_group) && count($config->target_group) > 0)
		{
			$isTargetGroup  = FALSE;

			// 소속된 그룹을 구합니다
			$group_list = $oMemberModel->getMemberGroups($member_srl);

			// loop를 돌면서 해당 그룹에 소속되어 있는 지 확인합니다
			foreach($group_list as $group_srl => &$group_title)
			{
				if(in_array($group_srl, $config->target_group))
				{
					$isTargetGroup = TRUE;
					break;
				}
			}

			if(!$isTargetGroup)
			{
				return new Object();
			}
		}

		require _XE_PATH_ . 'modules/loginlog/libs/Browser.php';

		$browser = new Browser();
		$browserName = $browser->getBrowser();
		$browserVersion = $browser->getVersion();
		$platform = $browser->getPlatform();

		$user_id = $output->data->user_id;
		$email_address = $output->data->email_address;

		// 로그인 기록을 남깁니다
		$log_info = new stdClass;
		$log_info->member_srl = $member_srl;
		$log_info->platform = $platform;
		$log_info->browser = $browserName . ' ' . $browserVersion;
		$log_info->user_id = $user_id;
		$log_info->email_address = $email_address;
		$this->insertLoginlog($log_info, false);

		return new Object();
	}

	/**
	 * @brief 로그인 성공 후 실행되는 트리거
	 */
	public function triggerAfterLogin(&$member_info)
	{
		if(!$member_info->member_srl)
		{
			return new Object();
		}

		// 로그인 기록 모듈의 설정값을 구함
		$oLoginlogModel = getModel('loginlog');
		$config = $oLoginlogModel->getModuleConfig();

		// 최고관리자는 기록하지 않는다면 패스~
		if($config->admin_user_log != 'Y' && $member_info->is_admin == 'Y') return new Object();

		// 로그인 기록 대상 그룹이 설정되어 있다면...
		if(is_array($config->target_group) && count($config->target_group) > 0)
		{
			$isTargetGroup  = FALSE;

			// memberModel 객체 생성
			$oMemberModel = getModel('member');

			// 소속된 그룹을 구합니다
			$group_list = $oMemberModel->getMemberGroups($member_info->member_srl);

			// loop를 돌면서 해당 그룹에 소속되어 있는 지 확인합니다
			foreach($group_list as $group_srl => &$group_title)
			{
				if(in_array($group_srl, $config->target_group))
				{
					$isTargetGroup = TRUE;
					break;
				}
			}

			if(!$isTargetGroup)
			{
				return new Object();
			}
		}

		require _XE_PATH_ . 'modules/loginlog/libs/Browser.php';

		$browser = new Browser();
		$browserName = $browser->getBrowser();
		$browserVersion = $browser->getVersion();
		$platform = $browser->getPlatform();

		// 로그인 기록을 남깁니다
		$log_info = new stdClass;
		$log_info->member_srl = $member_info->member_srl;
		$log_info->platform = $platform;
		$log_info->browser = $browserName . ' ' . $browserVersion;
		$log_info->user_id = $member_info->user_id;
		$log_info->email_address = $member_info->email_address;
		$this->insertLoginlog($log_info);

		return new Object();
	}

	/**
	 * @brief 회원 탈퇴 시 로그인 기록 삭제
	 */
	public function triggerDeleteMember(&$obj)
	{
		if(!$obj->member_srl)
		{
			return new Object();
		}

		$oModel = getModel('loginlog');
		$config = $oModel->getModuleConfig();

		if($config->delete_logs != 'Y')
		{
			return new Object();
		}

		executeQuery('loginlog.deleteMemberLoginlogs', $obj);

		return new Object();
	}

	public function triggerBeforeModuleInit(&$obj)
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info)
		{
			return new Object();
		}

		/**
	 	* 로그인 기록 메뉴 추가
	 	*/
		$oMemberController = getController('member');
		$oMemberController->addMemberMenu('dispLoginlogHistories', 'cmd_view_loginlog');
	}

	public function triggerBeforeModuleProc()
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info)
		{
			return new Object();
		}

		/**
		 * 관리자로 로그인 한 경우 회원 메뉴에 로그인 기록 추적 메뉴 추가
		 */
		if($this->act == 'getMemberMenu' && $logged_info->is_admin == 'Y')
		{
			$oMemberController = getController('member');

			$member_srl = Context::get('target_srl');
			$url = getUrl('', 'module', 'admin', 'act', 'dispLoginlogAdminList', 'search_target', 'member_srl', 'search_keyword', $member_srl);

			$oMemberController->addMemberPopupMenu($url, Context::getLang('cmd_trace_loginlog'), '', '_blank');
		}
	}

	public function insertLoginlog($log_info, $isSucceed = true)
	{
		$args = new stdClass;
		$args->log_srl = getNextSequence();
		$args->member_srl = &$log_info->member_srl;
		$args->is_succeed = $isSucceed ? 'Y' : 'N';
		$args->regdate = date('YmdHis');
		$args->platform = &$log_info->platform;
		$args->browser = &$log_info->browser;
		$args->user_id = &$log_info->user_id;
		$args->email_address = &$log_info->email_address;

		// 클라우드플레어 사용 시 실제 사용자 IP를 기록하도록 한다
		if (isset($_SERVER['HTTP_CF_CONNECTING_IP']))
		{
			$args->ipaddress = $_SERVER['HTTP_CF_CONNECTING_IP'];
		}
		return executeQuery('loginlog.insertLoginlog', $args);
	}
}

/* End of file : loginlog.controller.php */
/* Location : ./modules/loginlog/loginlog.controller.php */
