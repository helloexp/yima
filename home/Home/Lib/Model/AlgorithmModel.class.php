<?php

/**
 * Created by PhpStorm.
 * User: wangy
 * Date: 2016/3/31
 * Time: 13:49
 */
class AlgorithmModel extends Model
{
    // 客户标签
    protected $customerLabel = [];
    // 公告标签
    protected $noticeLabel = [];
    // 访问记录
    protected $visitTrace = [];

    public function Init($config = [])
    {
        if (empty($config) || !is_array($config)) {
            die('初始化参数有误！');
        }
        $this->customerLabel = $config['node_tag'];
        $this->noticeLabel   = $config['ym_tag'];
        $visitTrace          = $config['visit_trace'];
        if(!empty($visitTrace))
            foreach ($visitTrace as $k => $v) {
                $this->visitTrace[$v['recommend_id']] = $v;
            }
        
        if (!is_array($this->customerLabel)) {
            die('初始化商户标签有误！');
        }
        if (!is_array($this->noticeLabel)) {
            die('初始化公告标签有误！');
        }
    }
    /**
     * [getResult 获取num个结果]
     * @param  [type] $num [结果数]
     * @return [type]      [数组]
     */
    public function getResult($num = 0)
    {
        // 按照更新时间排序
        self::SortByUpdateTime();
        // 按照优先级排序
        self::SortByPriority();
        // 按照余弦值排序
        self::SortByDistance();
        // 清除已经点击过的公告，产品暂时决定去掉
        //self::InDisplayTime();
        // 把未置顶的放到最后
        self::CleanUnTop();
        // 过期公告直接推到尾部
        self::CleanUpOverTime();
        // 取排序靠前的num个公告
        if($num != 0)
        {
            $this->noticeLabel = array_slice($this->noticeLabel, 0, $num);
        }
        return $this->noticeLabel;
    }

    /**
     * 取客户标签与公告标签的公共标签，除以公告标签
     * 并按照其值进行倒序排列
     */
    private function SortByDistance()
    {
        // 客户标签值，转化为数组
        $ctmLabelArr = array_unique(explode(',',$this->customerLabel['tag_id_list']));
        foreach ($this->noticeLabel as $key => $ntc) {
            // 此项公告标签值，转化为数组
            $labelArr = array_filter(array_unique(explode(',',$ntc['tag_id_list'])));
            // 公告标签不得为空
            $labelCount = count($labelArr) or die('公告标签必须设置一个以上');
            // 取客户标签与公告标签的公共标签
            $intersectArr                        = array_intersect($labelArr, $ctmLabelArr);
            $intersectCount                      = count($intersectArr);
            $this->noticeLabel[$key]['distance'] = $intersectCount / $labelCount;
        }
        $this->sortProperty('distance');
    }

    /**
     * 按照公告预设的优先级进行倒序排列
     */
    private function SortByPriority()
    {
        $this->sortProperty('sort_num');
    }

    /**
     * 按照公告更新的时间进行倒序排列
     */
    private function SortByUpdateTime()
    {
        $this->sortProperty('add_time');
    }

    /**
     * 点击过的公告，直接扔到尾部
     */
    private function InDisplayTime()
    {
        if (empty($this->noticeLabel)) {
            return false;
        }
        foreach ($this->noticeLabel as $key => $val) {
            $firstVisitTrace = get_val($this->visitTrace,$val['id']);
            if ($firstVisitTrace && $firstVisitTrace['first_visit_time'] < date('YmdHis', strtotime('-' . $val['show_date'] . ' days'))) {
                //$this->noticeLabel[] = $val;
                unset($this->noticeLabel[$key]);
            }
            
        }
    }
    /**
     * 未置顶的公告直接放到最后
     */
    private function CleanUnTop()
    {
        if (empty($this->noticeLabel)) {
            return false;
        }
        foreach ($this->noticeLabel as $key => $val) {
            if ($val['top_flag'] == '0') {
                $this->noticeLabel[] = $val;
                unset($this->noticeLabel[$key]);
            }
        }
    }

    /**
     * 过期的公告直接放到最后
     */
    private function CleanUpOverTime()
    {
        if (empty($this->noticeLabel)) {
            return false;
        }
        foreach ($this->noticeLabel as $key => $val) {
            if ($val['end_time'] < date('YmdHis')) {
                $this->noticeLabel[] = $val;
                unset($this->noticeLabel[$key]);
            }
        }
    }

    /**
     * 冒泡排序属性，默认从大到小排序
     *
     * @param $property [属性]
     */
    private function sortProperty($property, $order = true)
    {
        $n = count($this->noticeLabel);
        for ($i = 0; $i < $n; ++$i) {
            for ($j = 0; $j < $n - $i - 1; ++$j) {
                if($order){
                    if ($this->noticeLabel[$j][$property] < $this->noticeLabel[$j + 1][$property]) {
                        $temp                      = $this->noticeLabel[$j];
                        $this->noticeLabel[$j]     = $this->noticeLabel[$j + 1];
                        $this->noticeLabel[$j + 1] = $temp;
                    }
                }else{
                    if ($this->noticeLabel[$j][$property] > $this->noticeLabel[$j + 1][$property]) {
                        $temp                      = $this->noticeLabel[$j];
                        $this->noticeLabel[$j]     = $this->noticeLabel[$j + 1];
                        $this->noticeLabel[$j + 1] = $temp;
                    }
                }
            }
        }
    }
}















