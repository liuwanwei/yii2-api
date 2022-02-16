<?php

namespace buddysoft\api\actions;

use Yii;
use yii\web\ServerErrorHttpException;


class UpdateAction extends \yii\rest\UpdateAction{

	use ActionTrait;
	use ModelTrait;
	
	public function run($id){
		$model = $this->getModelById($id);
		if ($model === null){
			return $this->failedWithWrongParam("sid 错误");
		}else{
			return $this->runWithModel($model);
		}
	}
	
	
    /**
     * Update a model.
     * @param mixed $id id of the model to be deleted.
     * @throws ServerErrorHttpException on failure.
     *
     * @return array response array matches protocol
     */
    public function runWithModel($model)
    {
	    if ($this->checkAccess) {
		    call_user_func($this->checkAccess, $this->id, $model);
	    }
	
	    $model->scenario = $this->scenario;
	    $model->load(Yii::$app->getRequest()->getBodyParams(), '');
	    if ($model->save() === false && !$model->hasErrors()) {
		    throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
	    }

        if ($model->hasErrors()) {
            return $this->failedWhenSaveModel($model);
        }

        // 重新查询，保证用户传来的 string 格式的整型值被正确转换成 integer
	    $modelClass = $this->modelClass;
	    $model = $modelClass::findOne($model->id);

		// 获得对象类名字（不带路径）
		$key = ActionTool::collectionNameForModel($this->modelClass);
	    
        return $this->successWithData([$key => $model]);
    }
}