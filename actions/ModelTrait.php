<?php
/**
 * Created by PhpStorm.
 * User: sungeo
 * Date: 2018/1/16
 * Time: 21:27
 */

namespace buddysoft\widget\actions;


trait ModelTrait
{
	/*
	 * 判断 $id 是字符串类型还是 int 型，并查找对应 Model 对象
	 *
	 * @param mix $id 对象 id，可能是 int 型，对应 primary key；可能是 string 型，对应 sid 属性
	 *
	 * @return mix 没有找到对象返回 null，否则返回对象
	 */
	public function getModelById($id){
		$modelClass = $this->modelClass;
		if (is_numeric($id)){
			$model = $modelClass::findOne($id);
		}else{
			$model = $modelClass::findOne(['sid' => $id]);
		}
		
		// 缓存查询到的对象
		if (property_exists($this, '_model')){
			$this->_model = $model;
		}
		
		return $model;
	}
}