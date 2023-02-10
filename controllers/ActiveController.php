<?php
namespace buddysoft\api\controllers;

use buddysoft\api\actions\ActionTool;
use buddysoft\api\actions\ResponseTrait;
use Yii;
use yii\filters\auth\HttpBasicAuth;
use yii\rest\Serializer;

class ActiveController extends \yii\rest\ActiveController{

    use ResponseTrait;

    const QUERY_ENVELOPE = 'items';

	// 设置认证方式：Http Baisc Auth
	public function behaviors(){
		$behaviors = parent::behaviors();
		$behaviors['authenticator'] = [
			'class' => HttpBasicAuth::class,
		];

        // 永远返回 JSON 格式数据
        $behaviors['contentNegotiator']['formats']['text/html'] = \yii\web\Response::FORMAT_JSON;

		return $behaviors;
	}

	// 将查询返回的数据增加一个信封：items
	public $serializer = [
        'class' => Serializer::class,
        'collectionEnvelope' => self::QUERY_ENVELOPE,
    ];

    /**
     * 配置 actions 的默认处理方式
     *
     * @return array
     */
    public function actions(){
    	$actions = parent::actions();
    	$actions['index']['prepareDataProvider'] = [$this, 'prepareDataProvider'];
    	
    	$customActions = [
            // 对于具备 deleted 字段的表，执行假删除
    		'delete' => [
    			'class' => 'buddysoft\api\actions\DeleteAction',
    			'modelClass' => $this->modelClass,                
                'checkAccess' => [$this, 'checkAccess'],                
    		],
            // 对于创建和更新，重新查询并返回数据，保证用户传来的 string 格式的整型值被正确转换成 integer
            'create' => [
                'class' => 'buddysoft\api\actions\CreateAction',
                'modelClass' => $this->modelClass,
                'checkAccess' => [$this, 'checkAccess'],
                'scenario' => $this->createScenario,
            ],
            'update' => [
                'class' => 'buddysoft\api\actions\UpdateAction',
                'modelClass' => $this->modelClass,
                'checkAccess' => [$this, 'checkAccess'],
                'scenario' => $this->updateScenario,
            ],
            'view' => [
                'class' => 'buddysoft\api\actions\ViewAction',
                'modelClass' => $this->modelClass,
                'checkAccess' => [$this, 'checkAccess'],
            ],
    	];

    	return array_merge($actions, $customActions);
    }

    /**
     * 当请求处理过程中抛出 WrongParamException 异常时，直接返回参数错误给请求者。
     * 从原理上说，想抛出异常，调用 [[ActionTrait::getGet()]] 或 [[]ActionTrait::getPost()] 时，$strict 参数传 true，如果没有找到该参数，就会抛出异常。
     * 这里捕获到异常，就直接结束处理并返回标准化的参数错误提示内容。
     */
    public function runAction($id, $params = [])
    {
        try{
            return parent::runAction($id, $params);
        }catch(\buddysoft\api\WrongParamException $e){
            return $this->failedWithWrongParam($e->getMessage());
        }
    }

    /**
     * 对反馈的数据进行格式化处理
     * 
     * 任何需要对标准返回内容进行额外处理的代码都可以放在这里。
     * 
     * 对运行中发生异常的处理放在了 \buddysoft\api\behaviors\ExceptionFormatter 中实现。
     */
    public function afterAction($action, $result)
    {
        $data = parent::afterAction($action, $result);
        
        if (isset($data[static::QUERY_ENVELOPE])){
            /**
             * 对以 prepareDataProvider() 方式提供的数据，封装到 'data' 字段里
             */
            $collectionKey = ActionTool::collectionNameForModel($this->modelClass, true);

            $data = [
                'code' => 0,
                'message' => '查询成功',
                'data' => [
                    'count' => count($data[static::QUERY_ENVELOPE]),
                    $collectionKey => $data[static::QUERY_ENVELOPE],
                ]
            ];
        }

        return $data;
    }

    /**
     *
     * 处理查询参数中关于分页的参数
     *
     */
    
    public function preparePagination($dataProvider){
        $paging = false;
        $pagination = $dataProvider->getPagination();

        $params = Yii::$app->request->queryParams;
        if (isset($params['pageSize']) && is_numeric($params['pageSize'])) {
            $pagination->pageSize = $params['pageSize'];
            $paging = true;
        }

        if (isset($params['page']) && is_numeric($params['page'])) {
            $pagination->page = $params['page'];
            $paging = true;
        }

        if(false == $paging){
            // 没有分页参数时，默认关闭分页
            $dataProvider->setPagination(false);
        }
    }

    /**
     *
     * 如果数据中带 kuserId 字段，对其验证，保证自己只能修改自己创建的数据
     *
     */ 
    public function checkAccess($action, $model = null, $params = []){
        $kuserId = Yii::$app->user->id;
        if ($action == 'update') {          
            if (isset($model->kuserId) && ($model->kuserId != $kuserId)) {
                throw new \yii\web\ForbiddenHttpException('禁止修改其它用户的数据');
            }
        }
    }

    /**
     *
     * 结束 RESTFul actions（一般是 index） 执行
     * 一般用在发生参数错误时，调用本函数结束执行，返回标准协议：
     * { 'status' : -1, 'msg' : $msg}
     *
     */
    
    protected function badRequest($msg){
        throw new \yii\web\BadRequestHttpException($msg, 0);
    }

    protected function forbiddenRequest($msg = null){
        if (empty($msg)) {
            $msg = '禁止访问不属于自己的数据';
        }
        throw new \yii\web\ForbiddenHttpException($msg);
    }
}
