<?php

namespace buddysoft\api\actions;

use Yii;
use yii\web\ServerErrorHttpException;


class CreateAction extends \yii\rest\CreateAction{

	use ActionTrait;

    /** @var \yii\db\ActiveRecord 新创建出来对象 */
    public $newObject;
	
    /**
     * Deletes a model.
     * @param mixed $id id of the model to be deleted.
     * @throws ServerErrorHttpException on failure.
     *
     * @return array response array matches protocol
     */
    public function run()
    {        
        /** @var \yii\base\Model $model */
        $model = parent::run();
        if ($model->hasErrors()){
            return $this->failedWithWrongParam(ActionTool::makeErrorSummary($model));
        }

        /**
         * 查询新添加的记录，保证返回数据库中真实存在的值
         * 1. 保证字段默认值也能返回给用户；2. 保证用户传来的 string 格式的整型值被正确转换成整型
         */        
        $model = call_user_func([$this->modelClass, 'findOne'], $model->id);        

        $this->newObject = $model;

        // 获得对象类名字（不带路径）
        $key = ActionTool::collectionNameForModel($this->modelClass);

        return $this->successWithData([$key => $model]);
    }
}
