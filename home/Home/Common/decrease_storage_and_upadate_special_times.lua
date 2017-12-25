--- debug start
local key_str1 = cjson.encode(KEYS)
redis.call("set", "lua_key:decrease_storage_and_upadate_special_times", key_str1)
--- debug end

---  parse key to human style start
local redis_key_preifx = KEYS[1] --- redis key前缀
local drawlottery_main_key = KEYS[2] --- 抽奖main key
local marketing_info_id = KEYS[3]  --- tmarketing_info id
local person_id = KEYS[4] --- 用户ID
local cj_batch_id = KEYS[5]  --- tcj_batch id
local date_str = KEYS[6]  --- 当前日期字符串
local expire = KEYS[7]  --- expire 时间
local storage_num = ARGV[1] --- 库存数
---  parse key to human style end

local final_key = redis_key_preifx .. drawlottery_main_key --- 所有 RedisHelper evalSha 和 evaluate 方法参数列表第一个参数都是 KEY前缀 lua table第一个下标为1
local day_dynamic_final_key = final_key .. ":" .. date_str --- 个人数据日fianl key
local static_person_day_win_times_key = "static:person_day_win_times:" .. marketing_info_id --- 个人日中奖次数 key
local static_person_total_win_times_key = "static:person_total_win_times:" .. marketing_info_id --- 个人总中奖次数 key

local static_person_day_win_times = tonumber_with_default(redis.call("hget", final_key, static_person_day_win_times_key)) --- 个人日中奖次数
local static_person_total_win_times = tonumber_with_default(redis.call("hget", final_key, static_person_total_win_times_key)) --- 个人总中奖次数

local dynamic_person_day_win_times_key = "dynamic:person_day_win_times:" .. marketing_info_id .. ":" .. person_id --- 个人日中奖次数 key
local dynamic_person_total_win_times_key = "dynamic:person_total_win_times:" .. marketing_info_id .. ":" .. person_id --- 个人总中奖次数 key

local dynamic_person_day_win_times = tonumber_with_default(redis.call("hget", day_dynamic_final_key, dynamic_person_day_win_times_key)) --- 个人日中奖次数
local dynamic_person_total_win_times = tonumber_with_default(redis.call("hget", final_key, dynamic_person_total_win_times_key)) --- 个人总中奖次数

if static_person_day_win_times > 0 and dynamic_person_day_win_times >= static_person_day_win_times then --- 当日中奖次数达到上限
    return 2
end
if static_person_total_win_times > 0 and dynamic_person_total_win_times > static_person_total_win_times then --- 总中奖次数达到上限
    return 3
end

local static_lottery_day_win_times = tonumber_with_default(redis.call("hget", final_key, "static:day_win_times:" .. cj_batch_id)) --- 奖品日中奖次数（静态）
local static_lottery_total_win_times = tonumber_with_default(redis.call("hget", final_key, "static:total_win_times:" .. cj_batch_id)) --- 奖品总中奖次数（静态）
local static_lottery_storage_num = tonumber_with_default(redis.call("hget", final_key, "static:storage_num:" .. cj_batch_id)) --- 奖品库存数（静态）


local dynamic_lottery_day_win_times_key = "dynamic:day_win_times:" .. cj_batch_id --- 奖品日中奖次数
local dynamic_lottery_total_win_times_key = "dynamic:total_win_times:" .. cj_batch_id --- 奖品总中奖次数
local dynamic_lottery_storage_num_key = "dynamic:storage_num:" .. cj_batch_id --- 奖品库存数

local dynamic_lottery_day_win_times = tonumber_with_default(redis.call("hget", day_dynamic_final_key, dynamic_lottery_day_win_times_key)) --- 奖品日中奖次数（动态）
local dynamic_lottery_total_win_times = tonumber_with_default(redis.call("hget", final_key, dynamic_lottery_total_win_times_key)) --- 奖品总中奖次数（动态）
local dynamic_lottery_storage_num = tonumber_with_default(redis.call("hget", final_key, dynamic_lottery_storage_num_key)) --- 奖品库存数（动态）

if dynamic_lottery_day_win_times >= static_lottery_day_win_times then --- 奖品日中奖次数达到上限
    return 4
elseif dynamic_lottery_total_win_times >= static_lottery_total_win_times then --- 奖品总中奖次数达到上限
    return 5
elseif static_lottery_storage_num > 0 and dynamic_lottery_storage_num <= 0 then --- 奖品库存不足
    return 6
else
    local command_str
    if dynamic_person_day_win_times > 0 then
        command_str = "hincrby"
    else
        command_str = "hset"
    end
    redis.call(command_str, day_dynamic_final_key, dynamic_person_day_win_times_key, 1) --- 设置 个人日中奖次数

    if dynamic_person_total_win_times > 0 then
        command_str = "hincrby"
    else
        command_str = "hset"
    end

    redis.call(command_str, final_key, dynamic_person_total_win_times_key, 1) --- 设置 个人总中奖次数

    redis.call("hincrby", day_dynamic_final_key, dynamic_lottery_day_win_times_key, 1) --- 设置奖品日中奖次数（动态）
    redis.call("hincrby", final_key, dynamic_lottery_total_win_times_key, 1) --- 设置奖品总中奖次数（动态）

    if static_lottery_storage_num > 0 then --- 库存有限 扣库存
        redis.call("hincrby", final_key, dynamic_lottery_storage_num_key, storage_num) --- 扣除奖品库存（动态）
    end

    redis.call("expire", day_dynamic_final_key, expire)

    return 1
end