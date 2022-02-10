<?php
/**
 * Created by PhpStorm.
 * User: sungeo
 * Date: 2018/1/16
 * Time: 09:01
 */

namespace buddysoft\api\actions;

use Yii;
use yii\helpers\ArrayHelper;

// 返回数据错误类型
define('STATUS_SUCCESS', 0);
define('STATUS_INVALID_PARAM', 		-1);    	// 参数错误

define('STATUS_CAN_NOT_SAVE', 		-12);    	// 无法保存，跟 ApiController 冲突，- 10
define('STATUS_EXCEED_LIMIT', 		-13);    	// 越界，跟 ApiController 冲突，- 10

define('STATUS_NOT_EXIST', 				-4);			// 不存在

define('STATUS_NOT_MATCH', 				-15);    	// 不匹配，跟 ApiController 冲突，- 10
define('STATUS_ALREADY_EXIST', 		-16);    	// 已存在，跟 ApiController 冲突，- 10
define('STATUS_FAILED_FOR_REASON',-10);			// 其它错误，原因在 msg 中给出


trait ActionTrait
{
	/**
	 * 通过私有成员存储错误信息的方式，配合 _setReturns() 和 _makeReturns() 使用
	 */
	private $_status = 0;
	private $_msg = '';
	private $_object = null;
	private $_items = null;

	private function _setObject($object){
		$this->_object = $object;
	}

	private function _setItems($items){
		$this->_items = $items;
	}

	private function _setReturns(int $status, string $msg): void
	{
		$this->_status = $status;
		$this->_msg = $msg;
	}

	private function _makeReturns(array $content = null): array
	{
		if (is_array($content) && count($content) > 1) {
			$this->_setReturns($content[0], $content[1]);
		}

		$returns = ['status' => $this->_status, 'msg' => $this->_msg];
		if ($this->_object) {
			$returns['object'] = $this->_object;
		}
		if (is_array($this->_items)) {
			$returns['items'] = $this->_items;
		}

		return $returns;
	}


	/*
	 * 通过对象的 id（或 sid）属性，查询对象
	 *
	 * @param string $objectId   对象的 id 或 sid 属性，通过 is_numeric() 区分
	 * @param string $param      查询请求中传递过来的属性名字，只用于回显错误信息
	 * @param string $modelClass 对象的类名字，通过 Model::className() 获取
	 *
	 * @return mix   返回 object 类型数据时表示查询成功，否则返回 array 类型的错误信息，返回 null 表示参数不存在
	 */
	private function _objectWithId($objectId, $param, $modelClass){
		if ($objectId === null){
//			return $this->failedWithWrongParam("缺少 {$param} 参数");
			return null;
		}
		
		if (is_numeric($objectId)){
			$object = $modelClass::findOne($objectId);
		}else{
			$object = $modelClass::findOne(['sid' => $objectId]);
		}
		if ($object === null){
			return $this->failedWithWrongParam("{$param} 目标对象不存在");
		}
		
		return $object;
	}
	
	/*
	 * 从 $_GET 中获取 $modelClass 类型对象的 id ，并通过查询返回对象
	 *
	 * @param string $param      存有对象 id 或 sid 属性的字段名字
	 * @param string $modelClass 目标对象类名字，通过 Model::className() 获得
	 *
	 * @return mix   返回 object 类型数据时表示查询成功，否则返回 array 类型的错误信息
	 *
	 */
	public function objectWithGetParam($param, $modelClass){
		$objectId = Yii::$app->request->get($param);
		
		return $this->_objectWithId($objectId, $param, $modelClass);
	}
	
	/*
	 * 从 $_POST 中获取 $modelClass 类型对象的 id ，并通过查询返回对象
	 *
	 * @param string $param      参数在 POST 数组中的键值
	 * @param string $modelClass 对象的类原型，可以通过 class 方法取得
	 *
	 * @return mix   返回 object 类型数据时表示查询成功，否则返回 array 类型的错误信息，返回 null 表示参数不存在
	 *
	 * 注意：调用者需使用 objectOk() 对返回值进行判断，
	 */
	public function objectWithPostParam($param, $modelClass){
		$objectId = Yii::$app->request->post($param);
		
		return $this->_objectWithId($objectId, $param, $modelClass);
	}
	
	/*
	 * 判断上一步返回的对象是否查询成功
	 *
	 * @param mix $object 查询到的结果，ActiveRecord 对象或 array 数组
	 *
	 * @return true 查询成功，false 失败
	 */
	public function objectOk($result){
		if ($result === null || is_array($result)){
			return false;
		}else{
			return true;
		}
	}

	/**
	 * 统一入口，根据 id 或 sid 参数查询某种类型的对象是否存在
	 *
	 * @param string $param 			id 或 sid 所在请求参数中的键名字，如 'childSid'
	 * @param string $modelClass	id 或 sid 对应对象的的类名字，带名字空间
	 * @return mixed							null 代表请求参数不存在或对象不存在，否则返回对象的实例
	 */
	public function objectWithParam($param, $modelClass)
	{
		$request = Yii::$app->request;

		if ($request->isPost) {
			$objectId = $request->post($param);
		}else if($request->isPut || $request->isPatch){
			// PUT 和 PATCH 用相同的处理方法，参考：https://www.yiiframework.com/doc/guide/2.0/en/runtime-requests
			$objectId = $request->getBodyParam($param);
		}else{
			$objectId = $request->get($param);
		}

		if ($objectId === null){
			return null;
		}
		
		/**
		 * 通过判断 objectId 是不是纯数字，来确定通过 id 字段还是 sid 字段来查询 
		 */
		if (is_numeric($objectId)){
			$object = $modelClass::findOne($objectId);
		}else{
			$object = $modelClass::findOne(['sid' => $objectId]);
		}
		
		return $object;
	}


	/**
	 * 经典使用场景：通过传入的类名字，生成期望参数名字，返回对象
	 * 
	 * 例如传入 'common\models\Clerk' 字符串，意味着调用者试图查找名为 clerkSid 的参数，并通过 Clerk::findOne(['sid' => $clerkSid])，
	 * 查找 common\models\Clerk 对象。
	 *
	 * @param string $class class name with namespace
	 * @return Object 成功时返回 $class 类型对象，否则返回 null
	 */
	public function classicObjectWithParam($class){
		$name = $this->shortClassName($class);
    $sidName = $name . 'Sid';

    return $this->objectWithParam($sidName, $class);
	}


	/**
	 * 返回完整类名字中的除去名字空间后的名字部分，并小写首字母
	 * 如：'frontend/models/PayType' 会返回 payType
	 *
	 * @param string $modelClass 完整类名字
	 * @return string 小写首字母的类名字
	 */
	public function shortClassName($modelClass){
		$name = (new \ReflectionClass($modelClass))->getShortName();
		return lcfirst($name);
	}

	/**
   * 检查名字类似 xxxSid 的输入参数，假定 xxx 是对象类名字，xxxId 是需要保存的属性，
   * 所以本函数要做的是先找到 xxx 对象，然后获取它的 xxx->id，更新到当前对象的 xxxId 属性中。
   *
   * @param string $class 目标对象的全名（带 namespace）
   * @return bool 成功时返回 true，标明属性已更新；否则返回 false
   */
  public function updateObjectParam($class){
		$name = $this->shortClassName($class);

    $sidName = $name . 'Sid';
    $idName = $name . 'Id';

    $object = $this->objectWithParam($sidName, $class);
    if (! $object) {
      return false;
    }

    $this->updateParams([$idName => $object->id]);
    return true;
  }
	

	/*
	 * 这对 objectOk() 判断为 false 的情况，返回统一的错误信息
	 *
	 * @param mixed  $result 对应 objectWithPostParam() 和 objectWithGetParam() 的结果
	 * @param string $param  查询对象时提供的键值
	 *
	 * @return array 符合标准协议错误格式的数组
	 */
	public function failedWithResult($result, $param){
		if ($result == null){
			return $this->failedWithWrongParam("缺少 {$param} 参数");
		}else{
			return $result;
		}
	}
	
	/*
	 * 合并错误信息和上下文信息到一个字符串
	 */
	private function _mergeMessage($category, $context){
		if (empty($category)) {
			return $context;
		} else if (empty($context)) {
			return $category;
		}else {
			return $category . ': ' . $context;
		}
	}
	
	/*
	 * 更新 GET 请求中的查询参数
	 */
	public function updateQueryParam($params){
		$request = Yii::$app->request;
		
		$oldParams = $request->queryParams;
		$newParams = ArrayHelper::merge($oldParams, $params);
		
		$request->queryParams = $newParams;
	}
	
	/*
	 * 向 Rest 请求的 body 参数中增加经过中间处理的参数
	 *
	 * @param array $params 中间处理过新加入的参数
	 */
	public function updateRequestBody($params){
		$request = Yii::$app->request;
		
		$oldParams = $request->getBodyParams();
		$newParams = ArrayHelper::merge($oldParams, $params);
		
		$request->setBodyParams($newParams);
	}

	/**
	 * 统一设置请求参数接口，会自动判断 GET 还是 POST、PUT、PATCH 请求
	 *
	 * @param array $params	需要设置的参数数组
	 * @return void
	 */
	public function updateParams(array $params)
	{
		$request = Yii::$app->request;
		if ($request->isPost || $request->isPatch || $request->isPut) {
			$oldParams = $request->getBodyParams();
			$newParams = ArrayHelper::merge($oldParams, $params);
			$request->setBodyParams($newParams);
		}else{
			$oldParams = $request->queryParams;
			$newParams = ArrayHelper::merge($oldParams, $params);			
			$request->queryParams = $newParams;				
		}
	}
	
	/*
	 * 检查 action 执行结果是否成功
	 *
	 * @param array $result 执行结果，至少包括 status 和 msg 元素
	 *
	 * @return boolean
	 */
	public function isSuccess($result){
		if (isset($result['status']) && $result['status'] === 0){
			return true;
		}else{
			return false;
		}
	}
	
	/*
	 * 参数错误返回信息封装
	 *
	 * @param string $context 发生错误时的现场描述
	 */
	public function failedWithWrongParam($context = null){
		return [
			'status' => STATUS_INVALID_PARAM,
			'msg' => $this->_mergeMessage('参数错误', $context),
		];
	}
	
	/*
	 * 保存 Model 对象到数据库失败时，返回对应错误信息给客户端
	 *
	 * @param Model  $model   所要保存的对象
	 * @param string $context 保存时上下文环境信息
	 *
	 * @return array response array matches protocol
	 */
	public function failedWhenSaveModel($model, $context = null){
		$error = $this->_mergeMessage('', $context ? $context : '保存失败：');

		$model->getFirstError();

		$error .= ErrorFormatter::fromModel($model);
		return [
			'status' => STATUS_CAN_NOT_SAVE,
			'msg' => $error,
		];
	}
	
	public function failedWhenDeleteModel($model, $context = null){
		return $this->failedWhenSaveModel($model, $context ? $context : '删除失败：');
	}
	
	/*
	 * 超过阈值时的反馈消息
	 */
	public function failedWithExceedLimit($context = null){
		return [
			'status' => STATUS_EXCEED_LIMIT,
			'msg' => $this->_mergeMessage('超过限制', $context),
		];
	}
	
	/*
	 * 直接给出错误原因并反馈给客户端
	 *
	 * @param string $context 错误原因
	 */
	public function failedWithReason($reason, $status = STATUS_FAILED_FOR_REASON){
		return [
			'status' => $status,
			'msg' => $reason,
		];
	}
	
	/*
	 * 只返回成功状态信息的反馈消息
	 */
	public function success($context = null){
		return [
			'status' => STATUS_SUCCESS,
			'msg' => $context ? $context : '成功'
		];
	}
	
	/*
	 * 格式化执行成功时返回对象信息反馈消息
	 */
	public function successWithObject($object, $context = null){
		return [
			'status' => STATUS_SUCCESS,
			'msg' => $context ? $context : '成功',
			'object' => $object,
		];
	}
}