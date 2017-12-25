local tonumber_with_default = function (origin, ...)
    local change_len = select('#', ...)
    local final_result = nil
    local origin_type = type(origin)
    local base = 10
    local default_val = 0

    if change_len == 1 then
        default_val = select(1, ...)
    elseif change_len == 2 then
        default_val = select(1, ...)
        base =  select(2, ...)
    end

    base = tonumber(base) or 10
    default_val = tonumber(default_val, base) or 0
    if origin_type == "number" or origin_type == "string" then
        final_result = tonumber(origin, base) or default_val
    else
        final_result = default_val
    end

    return final_result
end