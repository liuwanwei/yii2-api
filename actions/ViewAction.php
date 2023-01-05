<?php
/**
 * Created by PhpStorm.
 * User: sungeo
 * Date: 2018/1/30
 * Update:
 * 	- 2023/1/5 11:15 返回数据时将 model name 作为键值封装 model 对象
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

		$key = ActionTool::collectionNameForModel($this->modelClass);
		
		return $this->successWithData([$key => $model]);
	}
}