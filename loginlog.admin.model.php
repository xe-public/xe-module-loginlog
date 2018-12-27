<?php
/**
 * @class loginlogAdminModel
 * @brief loginlog 모듈의 admin model class
 * @author XEPublic
 */
class loginlogAdminModel extends loginlog
{
	public function getLoginlogAdminColorset()
	{
		$skin = Context::get('skin');
		if($skin)
		{
			$oModuleModel = getModel('module');
			$skin_info = $oModuleModel->loadSkinInfo($this->module_path, $skin);
			Context::set('skin_info', $skin_info);

			$config = $oModuleModel->getModuleConfig('loginlog');
			if(!$config->colorset) $config->colorset = 'white';
			Context::set('config', $config);

			$oTemplate = TemplateHandler::getInstance();
			$tpl = $oTemplate->compile($this->module_path.'tpl', 'colorset_list');
		}
		else
		{
			$tpl = '';
		}

		$this->add('tpl', $tpl);
	}
}