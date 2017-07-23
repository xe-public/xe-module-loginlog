<?php
/**
 * @class loginlogMobile
 * @brief loginlog 모듈의 mobile class
 * @author XEPublic
 */
require_once(_XE_PATH_.'modules/loginlog/loginlog.view.php');

class loginlogMobile extends loginlogView
{
	/**
	 * Support method are 
	 * dispMemberInfo, dispMemberSignUpForm, dispMemberFindAccount, dispMemberGetTempPassword, dispMemberModifyInfo, dispMemberModifyInfoBefore
	 */

	function init()
	{
		// Get the member configuration
		$oLoginlogModel = getModel('loginlog');
		$loginlog_config = $oLoginlogModel->getModuleConfig();
		Context::set('loginlog_config', $loginlog_config);

		$mskin = $loginlog_config->design->mskin;
		// Set the template path
		$template_path = sprintf('%sm.skins/%s',$this->module_path, $mskin);
		if(!is_dir($template_path)||!$mskin)
		{
			$mskin = 'default';
			$template_path = sprintf('%sm.skins/%s', $this->module_path, $mskin);
		}
		else
		{
			$template_path = sprintf('%sm.skins/%s', $this->module_path, $mskin);
		}

		$this->setTemplatePath($template_path);
	}

	function dispLoginlogHistories()
	{
		parent::dispLoginlogHistories();
	}
}
/* End of file member.mobile.php */
/* Location: ./modules/member/member.mobile.php */
