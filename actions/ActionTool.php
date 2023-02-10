<?php
namespace buddysoft\api\actions;
use yii\base\Model;

class ActionTool {

    /**
     * 根据当前 $modelClass 名字，生成返回集合数据的存储键名字
     * 
     * 对于类似 index 这样的查询，如果在 ActiveController 的派生类中定义了 prepareDataProvider()时，需要将返回的结果数组放到 
     * 当前对象名字的键名中存储，如下所示：
     * [
     *      'code' => 0,
     *      'data' => [
     *          'modelName' => [
     *              [],[],[],
     *          ]
     *      ]
     * ]
     * 
     * 定义成静态接口，是为了供 UpdateAction 和  CreateAction 使用。
     * 
     * @param string $modelClass 如：'common\models\User'
     * @param bool $pluralize 是否需要复数形式
     * @return string 'User'
     */
    public static function collectionNameForModel(string $modelClass, bool $pluralize = false){
        // 获得对象类名字（不带路径）
        $arr = explode('\\', $modelClass);
        $classLastName = array_pop($arr);
        $classLastName = strtolower($classLastName);

        if ($pluralize){
            return \yii\helpers\Inflector::pluralize($classLastName);
        }else{
            return $classLastName;
        }
    }

    public static function makeErrorSummary(Model $model)
    {
        $errors = $model->getFirstErrors();
        return reset($errors);
    }
}
?>