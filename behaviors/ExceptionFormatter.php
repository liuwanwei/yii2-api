<?php
/**
 * 使用 behavior 对运行过程中发生的异常进行处理，隐藏异常信息，返回符合通信协议要求的数据格式
 * 
 * 在 @app/config/main.php 中进行配置：
 
 'response' => [
    'class' => 'yii\web\Response',
    'format' => yii\web\Response::FORMAT_JSON,
    'charset' => 'UTF-8',
    // 格式化异常返回
    'as exceptionFormatter' => [
        'class' => ExceptionFormatter::class,
    ],
],        
 */
namespace buddysoft\api\behaviors;

use Yii;
use yii\web\Response;
use buddysoft\api\controllers\ApiController;
use yii\base\Event;

class ExceptionFormatter extends \yii\base\Behavior{

    public function events()
    {
        return [
            Response::EVENT_BEFORE_SEND => 'beforeSendResponse'
        ];
    }

    public function beforeSendResponse(Event $event){
        $response = $event->sender;

        if ($response->statusCode == 200) {
            return;
        }
        
        /**
         * 必须在配置文件 @app/config/main.php 中配置 'response' 组件的 'format' 属性为 JSON，否则 $data 就是字符串
         */
        $data = & $response->data;
        if (isset($data['status'])){
            /**
             * 如果发生异常，转换成标准协议格式后返回
             */
            $this->_formatExceptionData($data);
        }
    }

    private function _formatExceptionData(array & $data){
        $code = null;

        // 将 Yii2 框架生成的异常信息，转换成符合自身协议格式的信息
        if($data['status'] == 400){
            $code = ApiController::CODE_INVALID_PARAM;
            $msg = $data['message'];
        }else if ($data['status'] == 401) {
            $code = ApiController::CODE_UNAUTHORIZED;
            $msg = '请求包认证信息错误';
        }else if($data['status'] == 404){
            $code = ApiController::CODE_NOT_EXIST;
            $msg = '请求的对象不存在';
        }else if($data['status'] == 403){
            $code = ApiController::CODE_UNAUTHORIZED;
            $msg = $data['message'];
        }

        if ($code != null) {
            // 将调整过的异常反馈当作正常数据，请求端(app或小程序)只通过 status 区分
            Yii::$app->response->statusCode = 200;

            // 格式化异常错误反馈
            $data = [
                ApiController::$sCode => $code,
                ApiController::$sMsg => $msg,
            ];
        }

        return $data;
    }
}
?>