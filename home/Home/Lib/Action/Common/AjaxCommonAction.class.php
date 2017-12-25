<?php
/**
 * 公共接口方法 action
 *
 * @author : John zeng<zengc@imageco.com.cn> Date: 2016/05/30
 */
class AjaxCommonAction extends Action {
    private $returnInfo = [];
    //析构函数
    public function _initialize() {
        $this->returnInfo = [
            'status' => 0,      //失败
            'msg' => '',      //失败信息
            'data' => ''      //返回数据信息1
        ];
        
    }
    
    public function getAdressList(){ 
        $phoneNum = I('phone_num');
        if(empty($phoneNum)){
            $this->returnInfo['msg'] = '请输入正确的手机号码';
            echo json_encode($this->returnInfo);
            exit();
        }
        if (! check_str($phoneNum, array('null' => false, 'strtype' => 'mobile'), $error)) {
            $this->returnInfo['msg'] = '手机号码不正确';
            echo json_encode($this->returnInfo);
            exit();
        }

        //获取用户地址信息’
        $this->returnInfo['data'] = D('CityExpressShipping')->getUserAdressList($phoneNum);
        if(false === $returnInfo['data']){
            $this->returnInfo['msg'] = '获取地址信息失败';
            echo json_encode($returnInfo);
        }else{
            $this->returnInfo['status'] = 1;
            $this->returnInfo['msg'] = '成功获取信息';
            echo json_encode($this->returnInfo);
        }
    }
    
    //获取用户购物车信息
    public function getCartList(){ 
        $phoneNum = I('phone_num');
        $nodeId = I('node_id');
        $goodsInfoM = D('GoodsInfo');
        if(empty($nodeId) || empty($phoneNum)){
            $this->returnInfo['msg'] = '登录失败，无法查看购物车';
            echo json_encode($this->returnInfo);
        }
        //赋值
        $goodsInfoM->phoneNum = $phoneNum;
        // 购物车SESSION名
        $goodsInfoM->session_cart_name = 'session_cart_products_' . $nodeId . '_' . $phoneNum;
        // 商品收货地址SESISON名
        $goodsInfoM->session_ship_name = 'session_ship_products_' . $nodeId . '_' . $phoneNum;
        $cartsInfo = $goodsInfoM->_getCart();
        //获取用户地址信息
        $this->returnInfo['data'] = $cartsInfo;
        if(false === $this->returnInfo['data']){
            $this->returnInfo['msg'] = '登录失败，无法查看购物车';
            echo json_encode($this->returnInfo);
        }else{
            $this->returnInfo['status'] = 1;
            $this->returnInfo['msg'] = '成功获取信息';
            echo json_encode($this->returnInfo);
        }
    }
    //获取购物车商品信息
    public function getGoodsDetail(){
        $id = I('id');
        $skuInfo = I('skuInfo');
        if(empty($id)){
            $this->returnInfo['msg'] = '商品信息有误';
            echo json_encode($this->returnInfo);
            exit();
        }
        $goodsInfo = M('tbatch_info as b')
                ->join('tgoods_info as g on g.goods_id = b.goods_id')
                ->field('b.*, g.is_sku')
                ->where(['b.id'=>$id])
                ->find();
        //存储初始规格信息
        $goodsInfo['skuInfo'] = $skuInfo;
        if(!$goodsInfo){
            $this->returnInfo['msg'] = '商品获取信息失败';
            echo json_encode($this->returnInfo);
        }else{
            //判断是否SKU商品
            if('1' === $goodsInfo['is_sku']){
                // 是否sku商品
                $skuObj = D('Sku', 'Service');
                $skuObj->nodeId = $goodsInfo['node_id'];
                $goodsInfo = $skuObj->getSkuListInfo($goodsInfo);
                $goodsInfo['skuType'] = '[' . $goodsInfo['skuType'] . ']';
                $goodsInfo['skuDetail'] = '[' . $goodsInfo['skuDetail'] . ']';
            }
            $this->returnInfo['data'] = $goodsInfo;
            $this->returnInfo['status'] = 1;
            $this->returnInfo['msg'] = '获取商品信息成功';
            echo json_encode($this->returnInfo);
        }
    }
}