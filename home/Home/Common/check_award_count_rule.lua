--- debug start
local key_str3 = cjson.encode(KEYS)
redis.call("set", "lua_key:check_award_count_rule", key_str3)
--- debug end
---  parse key to human style start
local redis_key_prefix = KEYS[1]    --- redis key前缀
local draw_lottery_main_key = KEYS[2] --- 抽奖main key
local cj_batch_id = KEYS[3] --- tcj_batch id
local date_str = KEYS[4]  --- 当前日期字符串
local final_key = redis_key_prefix .. draw_lottery_main_key  --- 最终key
local day_dynamic_final_key = final_key .. ":" .. date_str --- 单日动态数据 fianl key
---  parse key to human style start

local static_lottery_day_win_times = tonumber_with_default(redis.call("hget", final_key, "static:day_win_times:" .. cj_batch_id)) --- 奖品日中奖次数（静态）
local static_lottery_total_win_times = tonumber_with_default(redis.call("hget", final_key, "static:total_win_times:" .. cj_batch_id)) --- 奖品总中奖次数（静态）
local static_lottery_storage_num = tonumber_with_default(redis.call("hget", final_key, "static:storage_num:" .. cj_batch_id)) --- 奖品库存数（静态）

local dynamic_lottery_day_win_times = tonumber_with_default(redis.call("hget", day_dynamic_final_key, "dynamic:day_win_times:" .. cj_batch_id)) --- 奖品日中奖次数（动态）
local dynamic_lottery_total_win_times = tonumber_with_default(redis.call("hget", final_key, "dynamic:total_win_times:" .. cj_batch_id)) --- 奖品总中奖次数（动态）
local dynamic_lottery_storage_num = tonumber_with_default(redis.call("hget", final_key, "dynamic:storage_num:" .. cj_batch_id)) --- 奖品库存数（动态）

if dynamic_lottery_day_win_times >= static_lottery_day_win_times then --- 奖品日中奖次数达到上限
    return 2
elseif dynamic_lottery_total_win_times >= static_lottery_total_win_times then --- 奖品总中奖次数达到上限
    return 3
elseif static_lottery_storage_num > 0 and dynamic_lottery_storage_num <= 0 then --- 奖品库存不足
    return 4
else
    return 1
end