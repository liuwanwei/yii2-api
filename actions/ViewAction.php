<?php
/**
 * Created by PhpStorm.
 * User: sungeo
 * Date: 2018/1/30
 * Time: 10:15
 */

namespace buddysoft\api\actions;

class ViewAction extends \yii\rest\ViewAction
{
	use ActionTrait;
	use ModelTrait;
	
	public function run($id){
		$model = $this->getModelById($id);
		
		if ($model === null){
			return $this->failedWithWrongParam('对象不存在');
		}
		
		if ($this->checkAccess) {
			call_user_func($this->checkAccess, $this->id, $model);
		}
		
		return $this->successWithObject($model);
	}
}