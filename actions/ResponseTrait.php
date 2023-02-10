<?php
namespace buddysoft\api\actions;

use buddysoft\api\controllers\ApiController;
use yii\base\Model;

trait ResponseTrait{

    /**
     * 参数错误返回信息封装
     *
     * @param string $context 发生错误时的现场描述
     */
    public function failedWithWrongParam($context = null){
        return $this->exit(ApiController::CODE_INVALID_PARAM, $context);
    }

    /**
     * 保存 Model 对象到数据库失败时，返回对应错误信息给客户端
     *
     * @param Model  $model   所要保存的对象
     * @param string $context 保存时上下文环境信息
     *
     * @return array response array matches protocol
     */
    public function failedWhenSaveModel(Model $model, $context = null){
        $class = $model::class;
        $error = $this->_mergeMessage("保存 {$class} 对象", $context ? $context : '保存失败：');

        // 返回保存错误信息总结
        $error .= ActionTool::makeErrorSummary($model);

        return $this->exit(ApiController::CODE_INTERNAL_ERROR, $error);
    }

    /**
     * 删除对象失败返回错误信息
     *
     * @param Model $model
     * @param string $context
     * @return array
     */
    public function failedWhenDeleteModel($model, $context = null){
        $class = $model::class;
        $error = $this->_mergeMessage("删除 {$class} 对象", $context ? $context : '删除失败：');

        // 返回保存错误信息总结
        $error .= ActionTool::makeErrorSummary($model);

        return $this->exit(ApiController::CODE_INTERNAL_ERROR, $error);
    }

    /**
     * 超过阈值时的反馈消息
     */
    public function failedWithExceedLimit($context = null){
        return $this->exit(ApiController::CODE_EXCESS_LIMIT, $this->_mergeMessage('超过限制', $context));
    }

    /*
     * 直接给出错误原因并反馈给客户端
     *
     * @param string $context 错误原因
     */
    public function failedWithReason($reason, $status = ApiController::CODE_INTERNAL_ERROR){
        return $this->exit($status, $reason);
    }

    /*
     * 只返回成功状态信息的反馈消息
     */
    public function success($context = null){
        return $this->exit(ApiController::CODE_SUCCESS, $context);
    }

    /*
     * 格式化执行成功时返回对象信息反馈消息
     * 
     * @param \yii\db\ActiveRecord $object
     * @param string $context
     */
    public function successWithObject($object, $context = null){
        return $this->exit(ApiController::CODE_SUCCESS, null, ['object' => $object]);
    }

    /**
     * 最底层封装返回数据格式，所有返回数据最终都要调用这里
     *
     * @param integer $code
     * @param string $message
     * @param array $data
     * @return array
     */
    public function exit(int $code, string $message = null, array $data = null){
        $result = [
            ApiController::$sCode => $code,
            ApiController::$sMsg => $message ? $message : '成功',			
        ];

        if ($data) {
            $result[ApiController::$sData] = $data;
        }

        return $result;
    }

    /**
     * 使用统一的封装返回 API 数据
     * 
     * v3.0.0 引入
     *
     * @param array $data
     * @param string|null $context
     * @return array
     */
    public function successWithData(array $data, string $context = null)
    {
        return $this->exit(ApiController::CODE_SUCCESS, $context, $data);
    }

    /**
     * 没有权限时返回错误信息
     *
     * @return array
     */
    public function failedWithPrivilege(string $context = null){
        if ($context == null) $context = '没有权限';
        return $this->exit(ApiController::CODE_UNAUTHORIZED, $context);
    }

    public function failedWithNotExist(){
        return $this->exit(ApiController::CODE_NOT_EXIST, '对象不存在');
    }

    /**
     * 合并错误信息和上下文信息到一个字符串
     * 
     * @param string $category 错误分类，如 “参数错误“， ”超过限制“ 等
     * @param string $context 错误现场信息，一般由用户自定义
     * 
     * @return string 合并后的错误描述信息
     */
    private function _mergeMessage($category, $context){
        if (empty($category)) {
            return $context;
        } else if (empty($context)) {
            return $category;
        }else {
            return $category . ' -> ' . $context;
        }
    }
}

?>