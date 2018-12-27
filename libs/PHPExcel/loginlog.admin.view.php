<?php
/**
 * @class loginlogAdminView
 * @author 퍼니엑스이 (admin@funnyxe.com)
 * @brief loginlog 모듈의 controller class
 **/

class loginlogAdminView extends loginlog
{
	/**
	 * @brief 초기화
	 */
	public function init()
	{
		// 템플릿 폴더 지정
		$this->setTemplatePath($this->module_path . 'tpl');
	}

	/**
	 * @brief 로그인 기록 열람
	 */
	public function dispLoginlogAdminList()
	{
		$oLoginlogModel = getModel('loginlog');
		$config = $oLoginlogModel->getModuleConfig();

		if(!isset($config->listSetting) || !is_array($config->listSetting) || count($config->listSetting) < 1)
		{
			$config->listSetting = array(
				'member.nick_name',
				'member.user_id',
				'member.email_address',
				'loginlog.ipaddress',
				'loginlog.regdate',
				'loginlog.platform',
				'loginlog.browser'
			);
		}

		$columnList = $config->listSetting;
		$columnList[] = 'loginlog.is_succeed';
		$columnList[] = 'loginlog.log_srl';
		$columnList[] = 'loginlog.member_srl';
		$columnList[] = 'loginlog.platform';
		$columnList[] = 'loginlog.browser';

		Context::set('loginlog_config', $config);

		// 목록을 구하기 위한 옵션
		$args = new stdClass;
		$args->page = Context::get('page'); ///< 페이지
		$args->list_count = 30; ///< 한페이지에 보여줄 기록 수
		$args->page_count = 10; ///< 페이지 네비게이션에 나타날 페이지의 수
		$args->sort_index = 'loginlog.regdate';
		$args->order_type = 'desc';
		$args->daterange_start = (int)(str_replace('-', '', Context::get('daterange_start')) . '000000');
		$args->daterange_end = (int)(str_replace('-', '', Context::get('daterange_end')) . '235959');
		$args->isSucceed  = Context::get('isSucceed');

		$ynList = array('Y' => 1, 'N' => 1);
		if(!isset($ynList[$args->isSucceed]))
		{
			unset($args->isSucceed);
		}

		$search_keyword = Context::get('search_keyword');
		$search_target = trim(Context::get('search_target'));

		if($search_keyword)
		{
			switch($search_target)
			{
				case 'member_srl':
					$args->member_srl = (int)$search_keyword;
					break;
				case 'user_id':
					$args->s_user_id = $search_keyword;
					array_push($columnList, 'member.user_id');
					break;
				case 'user_name':
					$args->s_user_name = $search_keyword;
					array_push($columnList, 'member.user_name');
					break;
				case 'nick_name':
					$args->s_nick_name = $search_keyword;
					array_push($columnList, 'member.nick_name');
					break;
				case 'ipaddress':
					$args->s_ipaddress = $search_keyword;
					array_push($columnList, 'loginlog.ipaddress');
					break;
				case 'os':
					$args->s_os = $search_keyword;
					break;
				case 'browser':
					$args->s_browser = $search_keyword;
					break;
			}
		}

		$columnList = array_unique($columnList);

		$output = executeQueryArray('loginlog.getLoginlogListWithinMember', $args, $columnList);

		// 템플릿에 쓰기 위해 Context::set
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('log_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		// 템플릿 파일 지정
		$this->setTemplateFile('list');
	}

	public function dispLoginlogAdminSetting()
	{
		// 로그인 기록 모듈의 설정값을 가져옴
		$oLoginlogModel = getModel('loginlog');
		$config = $oLoginlogModel->getModuleConfig();

		// 생성된 그룹 목록을 가져옴
		$oMemberModel = getModel('member');
		$group_list = $oMemberModel->getGroups();

		Context::set('group_list', $group_list);
		Context::set('config', $config);

		$this->setTemplateFile('setting');
	}

	public function dispLoginlogAdminArrange()
	{
		$this->setTemplateFile('arrange');
	}

	public function dispLoginlogAdminDesign()
	{
		// loginlogModel 객체 생성 
		$oLoginlogModel = getModel('loginlog');
		$config = $oLoginlogModel->getModuleConfig();

		Context::set('config', $config);

		// moduleModel 객체 생성
		$oModuleModel = getModel('module');

		// 스킨 목록을 가져옴
		$skin_list = $oModuleModel->getSkins($this->module_path);
		Context::set('skin_list', $skin_list);

		// 모바일 스킨 목록을 가져옴
		$mskin_list = $oModuleModel->getSkins($this->module_path, 'm.skins');
		Context::set('mskin_list', $mskin_list);

		// 템플릿 파일 지정
		$this->setTemplateFile('design');
	}
}

/* End of file : loginlog.admin.view.php */
/* Location : ./modules/loginlog/loginlog.admin.view.php */