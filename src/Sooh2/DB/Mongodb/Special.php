<?php

/*

db.tb_user_0.insert({"_id":"abasdfhi3rabasdfhi3rabasdfhi3r123456","nickname":"张三","rowVersion":1});
WriteResult({ "nInserted" : 1 })
WriteResult({
    "nInserted" : 0,
    "writeError" : {
        "code" : 11000,
        "errmsg" : "E11000 duplicate key error collection: test.tb_user_0 index: _id_ dup key: { : \"abasdfhi3rabasdfhi3rabasdfhi3r123456\" }"
    }
})


 */

namespace Sooh2\DB\Mongodb;

/**
 * Description of Special
 *
 * @author wangning
 */
class Special {
    public function findAndModify();
    public function addIndex();
}
