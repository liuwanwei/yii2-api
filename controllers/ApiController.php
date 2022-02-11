<?php

namespace buddysoft\api\controllers;

use Yii;

class ApiController extends \yii\web\Controller{
	
	const CODE_SUCCESS 					= 0;
	const CODE_INVALID_PARAM 			= -1;		// 参数错误
	const CODE_UNAUTHORIZED				= -2;		// 未授权
	const CODE_ALREADY_REGISTERED 		= -3;		// 已注册
	const CODE_NOT_EXIST				= -4;		// 对象不存在
	const CODE_VALIDATION_FAILED		= -5;		// Model 对象验证错误
	const CODE_INTERNAL_ERROR			= -100; 	// 内部错误

	const CODE = "code";
	const MSG = "message";
	const DATA = "data";

	public static $sCode 	= self::CODE;
	public static $sMsg 	= self::MSG;
	public static $sData 	= self::DATA;

	public function makeDataMessage($code = 0, $msg = '', $data = null) {
		$result = ['status' => $code, 'msg' => $msg];
		
		if ($data) {
			$result['data'] = $data;
		}
		
		return $result;
	}

	/**
	 * generate output array
	 *
	 * @param integer $code
	 * @param string $msg
	 * @param mixed $data
	 * @return void
	 * 
	 * $data can be array, object or basic variables.
	 * Let's assume response is an array named $resp, generally, it has two elements:
	 * $resp['status']: status code.
	 * $resp['msg']:    description message.
	 *  
	 * if $data is array and all keys are string, it's elements are all put to $resp root array.
	 * if $data is array and at least one key isn't string, it is packed as a whole in $resp['items']
	 * if $data is an object, it is put to $resp['object'] element.
	 * otherwise, it is put to $resp['extra'] element.
	 */
	public function exitWithCode($code = 0, $msg = '', $data = null){
		/**
		 *
		 * 在前一个版本中，直接 echo 输出的内容，并调用 Yii::$app->end() 是可行的，
		 * 但在 Yii 升级到 2.0.14.1 之后，调用 echo 后再调用 Yii::$app->end()，
		 * 会触发异常： yii\web\HeadersAlreadySentException。
		 * 
		 * 以后都该保证用 Response 来管理输出到客户端的内容。
		 */

		// echo $this->makeDataMessage($code, $msg, $data);
		$data = $this->makeDataMessage($code, $msg, $data);
		Yii::$app->response->data = $data;
		Yii::$app->end();
	}

	public function exitWithInvalidParam($msg = null){
		$this->exitWithCode(self::CODE_INVALID_PARAM, empty($msg) ? '参数错误' : $msg);
	}

	public function exitWithSuccess($data = null){
		$this->exitWithCode(self::CODE_SUCCESS, '操作成功', $data);
	}

	/*
	 * 快速返回错误信息的接口，参数 $error 的值必须通过 MyDefine() 来定义
	 * @param string  $error 字符串
	 */
	public function exitWithError($error, $data = array()){
		$key = $error;
		$code = $key."_CODE";
		$msg = $key."_MSG";

		if (defined($code) && defined($msg)) {
			$code = constant($code);
			$msg = constant($msg);
			$this->exitWithData($code, $msg, $data);
		}else{
			if (is_string($error)) {
				$this->exitWithCode(self::CODE_INTERNAL_ERROR, $error);
			}else{
				$msg = print_r($error, true);
				$this->exitWithCode(self::CODE_INTERNAL_ERROR, $msg);
			}
		}
	}

	/**
     * 将更多错误信息封装在 'data' 属性里,用于传输第三方服务器返回的错误信息
     */
    public function exitWithErrorData($error, $data){
        $this->exitWithCode(self::CODE_INTERNAL_ERROR, $error, ['data' => $data]);
    }

	/**
	 *
	 * 当 Model 验证（validate）出错时，返回发现的第一个错误
	 *
	 */
	public function exitWithValidationError($model){
		$errors = array_values($model->getErrors());
        $error = empty($errors) ? null : $errors[0][0];
        $this->exitWithCode(self::CODE_VALIDATION_FAILED, $error);
	}

	/**
	 *
	 * 对于需要口令才能访问的API接口进行口令检查
	 * @param string $secret 口令
	 * @return false: 口令不正确或配置文件未设置口令；true：口令正确
	 */
	
	public function checkSecret($secret){
		if (isset(Yii::$app->params['accessSecret'])) {
			if (Yii::$app->params['accessSecret'] == $secret) {
				return true;
			}
		}

		return false;
	}
}