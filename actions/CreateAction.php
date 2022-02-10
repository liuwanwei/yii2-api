<?php

namespace buddysoft\widget\actions;

use Yii;
use yii\web\ServerErrorHttpException;


class CreateAction extends \yii\rest\CreateAction{

	use ActionTrait;
	
    /**
     * Deletes a model.
     * @param mixed $id id of the model to be deleted.
     * @throws ServerErrorHttpException on failure.
     *
     * @return array response array matches protocol
     */
    public function run()
    {
        $model = parent::run();

        if ($model->hasErrors()) {
            return $this->failedWhenSaveModel($model);
        }

        /**
         *
         * 查询新添加的记录，保证字段默认值也能返回给用户，保证用户传来的 string 
         * 格式的整型值被正确转换成整型
         *
         */
        
        $model = call_user_func([$this->modelClass, 'findOne'], $model->id);
	
        /*
         * 将调用成功时的 API status code 统一为 200
         *
         * Closed: if opened, 小程序 will send one more request like /schools/27 after creation.
         */
	    // $response = Yii::$app->getResponse();
	    // $response->setStatusCode(200);

        return $this->successWithObject($model);
    }
}
