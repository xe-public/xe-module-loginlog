<?php
/**
 * @class loginlogModel
 * @brief loginlog 모듈의 model class
 * @author XEPublic
 **/

class loginlogModel extends loginlog
{
	/**
	 * @brief 초기화
	 */
	public function init()
	{
	}

	/**
	 * @brief 모듈의 global 설정 구함
	 */
	public function getModuleConfig()
	{
		static $config;
		if(!isset($config))
		{
			$oModuleModel = getModel('module');
			$config = $oModuleModel->getModuleConfig('loginlog');

			// $config 변수 초기화
			if(!isset($config))
			{
				$config = new stdClass;
			}

			if(!$config->admin_user_log) $config->admin_user_log = 'N';

			// 로그인 기록 대상 그룹이 설정되어 있지 않은 경우 변수 초기화
			if(!isset($config->target_group))
			{
				$config->target_group = array();
			}

			// 표시 항목 설정값
			if(!is_array($config->listSetting))
			{
				if($config->listSetting) $config->listSetting = explode('|@|', $config->listSetting);
				else $config->listSetting = array();
			}

			// 엑셀 파일(XLS) 내보내기 설정값
			if(!isset($config->exportConfig)) $config->exportConfig = new stdClass;
			if(!$config->exportConfig->listCount) $config->exportConfig->listCount = 100;
			if(!$config->exportConfig->pageCount) $config->exportConfig->pageCount = 10;

			if(!$config->exportConfig->includeGroup || !is_array($config->exportConfig->includeGroup))
			{
				if($config->exportConfig->includeGroup) $config->exportConfig->includeGroup = explode('|@|', $config->exportConfig->includeGroup);
				else $config->exportConfig->includeGroup = array();
			}

			if(!is_array($config->exportConfig->excludeGroup))
			{
				if($config->exportConfig->excludeGroup) $config->exportConfig->excludeGroup = explode('|@|', $config->exportConfig->excludeGroup);
				else $config->exportConfig->excludeGroup = array();
			}

			if(!isset($config->design))
			{
				$config->design = new stdClass;
			}
		}

		return $config;
	}

	/**
	 * @brief 선택한 회원의 로그인 기록을 가져옵니다
	 */
	public function getLoginlogListByMemberSrl($memberSrl, $searchObj = NULL, $columnList = array())
	{
		$args = new stdClass;

		if($searchObj != NULL)
		{
			$args->daterange_start = $searchObj->daterange_start;
			$args->daterange_end = $searchObj->daterange_end;
			$args->s_browser = $searchObj->s_browser;
			$args->s_platform = $searchObj->s_platform;
		}

		$args->member_srl = $memberSrl;

		return executeQueryArray('loginlog.getLoginlogListByMemberSrl', $args, $columnList);
	}
}