<?php
// 子查询
$where .= $goodsInfo->getAdapter()->quoteInto(' and id not in (?)', 
        new Zend_Db_Expr(
                $goodsInfo->getAdapter()
                    ->quoteInto(
                        'select goodsid from t_genre_cus_goods where shopid = ?', 
                        $shopid, Zend_Db::PARAM_INT)));
$countRes = $goodsInfo->getCount($where);