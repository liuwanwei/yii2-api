<?php

namespace buddysoft\api\actions;

use yii\web\ServerErrorHttpException;


class DeleteAction extends \yii\rest\DeleteAction{

	use ActionTrait;
	use ModelTrait;

	/**
	 * Soft Delete 字段名称
	 *
	 * @var string
	 */
	public $softDeleteField = 'deleted';
	
	public function run($id){
		$model = $this->getModelById($id);
		if ($model === null){
			return $this->failedWithWrongParam("找不到对象");
		}else{
			return $this->runWithModel($model);
		}
	}
	
    /**
     * Deletes a model.
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

		$softDeleteField = $this->softDeleteField;
		
        if ($model->hasAttribute($softDeleteField)) {
			if ($model->$softDeleteField == 1) {
				return $this->failedWithWrongParam("对象已删除");
			}

        	// 对于有 deleted 字段的表，执行假删除
        	$model->$softDeleteField = 1;
        	$model->save();
        	
        	return $this->success('删除成功');
        }else{
	        // 常规删除
	        if ($model->delete() === false) {
		        return $this->failedWhenDeleteModel($model);
	        }else{
		        return $this->success('删除成功');
	        }
        }
    }
}