--- debug start
local key_str2 = cjson.encode(KEYS)
redis.call("set", "lua_key:verify_person_drawLottery_rule_and_update", key_str2)
---  debug end
---  parse key to human style start
local redis_key_prefix = KEYS[1]    --- redis key前缀
local draw_lottery_main_key = KEYS[2] --- 抽奖main key
local marketing_info_id = KEYS[3] --- tmarketing_info id
local person_id = KEYS[4]  --- 用户ID
local date_str = KEYS[5]  --- 当前日期字符串
local expire = KEYS[6]  --- expire 时间

---  parse key to human style end

local final_key = redis_key_prefix .. draw_lottery_main_key
local day_dynamic_final_key = final_key .. ":" .. date_str   --- 单日动态数据 fianl key
local static_person_day_part_times_key   = "static:person_day_part_times:" .. marketing_info_id    --- 个人日参与次数key
local static_person_total_part_times_key = "static:person_total_part_times:" .. marketing_info_id    --- 个人总参与次数key
local static_person_day_win_times_key    = "static:person_day_win_times:" .. marketing_info_id    --- 个人日中奖次数 key
local static_person_total_win_times_key  = "static:person_total_win_times:" .. marketing_info_id    --- 个人总中奖次数 key


local static_person_day_part_times   = tonumber_with_default(redis.call("hget", final_key, static_person_day_part_times_key))    --- 个人日参与次数
local static_person_total_part_times = tonumber_with_default(redis.call("hget", final_key, static_person_total_part_times_key))    --- 个人总参与次数
local static_person_day_win_times    = tonumber_with_default(redis.call("hget", final_key, static_person_day_win_times_key))    --- 个人日中奖次数
local static_person_total_win_times  = tonumber_with_default(redis.call("hget", final_key, static_person_total_win_times_key))    --- 个人总中奖次数

local dynamic_person_day_part_times_key   = "dynamic:person_day_part_times:" .. marketing_info_id .. ":" .. person_id    --- 个人日参与次数 key
local dynamic_person_total_part_times_key = "dynamic:person_total_part_times:" .. marketing_info_id .. ":" .. person_id    --- 个人总参与次数 key
local dynamic_person_day_win_times_key    = "dynamic:person_day_win_times:" .. marketing_info_id .. ":" .. person_id    --- 个人日中奖次数 key
local dynamic_person_total_win_times_key  = "dynamic:person_total_win_times:" .. marketing_info_id .. ":" .. person_id    --- 个人总中奖次数 key

local dynamic_person_day_part_times   = tonumber_with_default(redis.call("hget", day_dynamic_final_key, dynamic_person_day_part_times_key))    --- 个人日参与次数
local dynamic_person_total_part_times = tonumber_with_default(redis.call("hget", final_key, dynamic_person_total_part_times_key))    --- 个人总参与次数
local dynamic_person_day_win_times    = tonumber_with_default(redis.call("hget", day_dynamic_final_key, dynamic_person_day_win_times_key))    --- 个人日中奖次数
local dynamic_person_total_win_times  = tonumber_with_default(redis.call("hget", final_key, dynamic_person_total_win_times_key))    --- 个人总中奖次数

if static_person_day_part_times > 0 and dynamic_person_day_part_times >= static_person_day_part_times then    --- 当日参与次数达到上限
    return 2
elseif static_person_total_part_times > 0 and dynamic_person_total_part_times >= static_person_total_part_times then    --- 参与次数达到上限
    return 3
elseif static_person_day_win_times > 0 and dynamic_person_day_win_times >= static_person_day_win_times then    --- 当日中奖次数达到上限
    return 4
elseif static_person_total_win_times > 0 and dynamic_person_total_win_times >= static_person_total_win_times then    --- 总中奖次数达到上限
    return 5
end

if static_person_day_part_times > 0 then    --- 需要设置当日参与次数
    local command_str
    if dynamic_person_day_part_times > 0 then
        command_str = "hincrby"
    else
        command_str = "hset"
    end
    redis.call(command_str, day_dynamic_final_key, dynamic_person_day_part_times_key, 1)
end

if static_person_total_part_times > 0 then    --- 需要设置总参与次数
    local command_str
    if dynamic_person_total_part_times > 0 then
        command_str = "hincrby"
    else
        command_str = "hset"
    end
    redis.call(command_str, final_key, dynamic_person_total_part_times_key, 1)
end

if static_person_day_part_times > 0 or static_person_total_part_times > 0 then
    redis.call("expire", day_dynamic_final_key, expire)
end

return 1