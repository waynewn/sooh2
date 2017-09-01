<?php 
namespace  Sooh2\DB\Redis;
class CmdStr{
	const selectDB = 'select';
	const setExpireTo='expireAt';
	const search_keys='keys';// use *
	const key_type = 'type';//string: set: list: zset: hash: other:
	
	const get = 'get';
	const set = 'set';
	const set_expire = 'setex';
	const set_not_exists = 'setnx';
	const delete = 'delete';
	const exists = 'exists';
	const increase = 'incr';//needs set first
	const decrease = 'decr';
	const getMultiple = 'getMultiple';
	const array_unshift = 'lpush';
	const array_push = 'rpush';
	const array_shift = 'lpop';
	const array_size = 'llen';
	//const array_size = 'lsize';
	const set_add = 'sadd';
	const set_length = 'ssize';
	const set_exists = 'sContains';
	const set_remove = 'sRemove';
	
	const sortset_add = 'zAdd';  // key, worth, value
	const sortset_size = 'zSize';
	const sortset_count = 'zCount';// key,worth-start,worth-end
	const sortset_remore = 'zRem';
	const sortset_range = 'zRange';// get keys from start to end
	
	const hash_field_set = 'hSet';
	const hast_field_get = 'hGet';
	const hash_fields_count = 'hLen';
	const hash_fieldinc_step='hIncrBy';
	const hast_fields_all = 'hGetAll';
}