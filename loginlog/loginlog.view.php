<?php
/**
 * @class loginlogView
 * @brief loginlog 모듈의 view class
 * @author XEPublic
 */
class loginlogView extends loginlog
{
	/**
	 * 초기화
	 */
	public function init()
	{
		// loginlogModel 객체 생성
		$oLoginlogModel = getModel('loginlog');
		// 로그인 기록 모듈의 설정을 가져옵니다
		$loginlog_config = $oLoginlogModel->getModuleConfig();

		// 템플릿에서 쓸 수 있도록 Context::set()
		Context::set('loginlog_config', $loginlog_config);

		$template_path = sprintf("%sskins/%s/",$this->module_path, $loginlog_config->design->skin);
		if(!is_dir($template_path)||!$loginlog_config->design->skin)
		{
			$loginlog_config->design->skin = 'default';
			$template_path = sprintf("%sskins/%s/",$this->module_path, $loginlog_config->design->skin);
		}
		$this->setTemplatePath($template_path);
	}

	/**
	 * 로그인 기록
	 */
	public function dispLoginlogHistories()
	{
		// 로그인 정보를 가져옵니다
		$logged_info = Context::get('logged_info');
		// 로그인 하지 않은 경우 권한이 없다고 에러 출력
		if(!$logged_info)
		{
			return $this->stop('msg_not_permitted');
		}

		// 목록을 구하기 위한 옵션
		$args = new stdClass;
		$args->page = Context::get('page'); ///< 페이지
		$args->list_count = 30; ///< 한페이지에 보여줄 기록 수
		$args->page_count = 10; ///< 페이지 네비게이션에 나타날 페이지의 수
		$args->sort_index = 'log_srl';
		$args->order_type = 'desc';
		$args->member_srl = $logged_info->member_srl;

		$search_keyword = Context::get('search_keyword');
		$search_target = trim(Context::get('search_target'));

		$output = executeQueryArray('loginlog.getLoginlogList', $args);

		// 템플릿에 쓰기 위해 Context::set
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('histories', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		$this->setTemplateFile('histories');
	}
}