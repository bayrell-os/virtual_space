local cjson = require "cjson"
local jwt = require "resty.jwt"

-- If redirect is need ?
local is_login_redirect = 0

-- Check no redirect pages
local is_no_redirect_page = 0
if string.sub(ngx.var.request_uri, 1, 6) == "/login" then
	is_no_redirect_page = 1
end
if string.sub(ngx.var.request_uri, 1, 7) == "/logout" then
	is_no_redirect_page = 1
end
if string.sub(ngx.var.request_uri, 1, 5) == "/api/" then
	is_no_redirect_page = 1
end

-- Get redirect url
local redirect_url = ""
if ngx.var.http_x_route_prefix ~= nil and ngx.var.http_x_route_prefix ~= "" then
	redirect_url = redirect_url .. ngx.var.http_x_route_prefix
end
redirect_url = redirect_url .. ngx.var.request_uri

-- Read JWT Cookie
local jwt_str = ngx.var.cookie_cloud_jwt

-- Check JWT Cookie
if jwt_str == nil or jwt_str == '' then
	is_login_redirect = 1
else
	
	-- Check Token Sign
	local jwt_public_key = os.getenv("JWT_PUBLIC_KEY")
	local jwt_obj = jwt:verify(jwt_public_key, jwt_str)
	
	-- ngx.log(ngx.STDERR, jwt_public_key)
	-- ngx.log(ngx.STDERR, cjson.encode(jwt_obj))
	
	if jwt_obj.valid == true and jwt_obj.verified == true then
		-- ngx.log(ngx.STDERR, "JWT is OK !!!")
	else
		is_login_redirect = 1
	end
	
end

-- Check if no redirect page
if is_no_redirect_page == 1 then
	is_login_redirect = 0
end

-- Redirect to login page
if is_login_redirect == 1 then
	return ngx.redirect("/login?r=" .. redirect_url);
end

-- ngx.log(ngx.STDERR, is_no_redirect_page)