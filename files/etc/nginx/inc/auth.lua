
local function check_password(password, hash1)
	
	local ffi = require("ffi")
	
	ffi.cdef[[
	char *crypt(const char *key, const char *salt);
	]]
	
	local res = ffi.C.crypt(password, hash1)
	local hash2 = ffi.string(res)
	
	--[[
	ngx.log(ngx.STDERR, "Password=" .. tostring(password))
	ngx.log(ngx.STDERR, "Hash=" .. tostring(hash))
	ngx.log(ngx.STDERR, "Res1=" .. tostring(res1))
	--]]
	
	if hash1 == hash2 then
		return 1
	end
	
	return 0
end


local function check_htpasswd_file(username, password)
	
	local file = io.open("/etc/nginx/inc/htpasswd.inc")
	if file == nil then
		return 0
	end
	
	for line in file:lines() do 
		
		local line_index = line:find(':')
		if line_index ~= nil then
			local line_user = line:sub(0, line_index - 1)
			local line_pass = line:sub(line_index + 1)
			
			if line_user == username then
				
				file:close()
				
				-- ngx.log(ngx.STDERR, "User=" .. username)
				-- ngx.log(ngx.STDERR, "Pass=" ..  password)
				
				local check = check_password(password, line_pass)
				
				
				-- ngx.log(ngx.STDERR, line_pass)
				-- ngx.log(ngx.STDERR, check)
				
				if (check == 1) then
					return 1
				end
				
				return 0
			end
			
		end
		
	end
	
	file:close()
	
	return 0
end


local function bus_call(url, data)
	
	local cjson = require "cjson"
	
	local cloud_os_gateway = os.getenv("CLOUD_OS_GATEWAY")
	local cloud_os_key = os.getenv("CLOUD_OS_KEY")
	local data_keys = {}
	local time = os.time()
	
	-- Add slash to url
	if string.sub(url, 1, 1) == "/" then url = string.sub(url, 2) end
	if string.sub(url, -1, -1) ~= "/" then url = url .. "/" end
	
	-- Build url
	url = "http://" .. cloud_os_gateway .. "/api/bus/" .. url
	
	-- Build data_keys
	for k, v in pairs(data) do
		table.insert(data_keys, k)
	end
	
	-- Sort data_keys
	table.sort(data_keys)
	
	-- Insert time and cloud os key to data_keys
	table.insert(data_keys, time)
	table.insert(data_keys, cloud_os_key)
	
	-- Get text from data_key
	local text = table.concat(data_keys, "|")
	
	-- Get sign
	local md5 = require 'md5'
	local sign = md5.sumhexa(text)
	
	-- Get post data
	local post_data = {}
	post_data.alg = "md5"
	post_data.data = data
	post_data.time = time
	post_data.sign = sign
	post_data = cjson.encode(post_data)
	
	-- Send request
	local cURL = require("cURL")
	local out = ""
	
	local c = cURL.easy_init()
	c = cURL.easy{
		url = url,
		post = true,
		httpheader = {
			"Content-Type: application/json";
		},
		postfields = post_data,
	}
	c:perform({ writefunction = function(str)
		out = out .. str
	end})
	
	local obj = cjson.decode(out)
	
	if obj.error.code == 1 then
		return obj.result.jwt
	end
	
	return ""
end


local function check_htpasswd_os(username, password)
	
	if username == "" then
		return 0
	end
	if password == "" then
		return 0
	end
	
	local space_uid = ngx.req.get_headers()['X-SPACE-UID']
	if space_uid == "" then
		return 0
	end
	
	local cache = ngx.shared.basic_auth_cache
	local cache_key = username .. ':' .. password
	local cjson = require "cjson"
	local jwt = require "resty.jwt"
	
	-- Read from cache
	local jwt_str = cache:get(cache_key)
	-- ngx.log(ngx.STDERR, "Cache " .. tostring(jwt_str))
	
	-- Call bus login
	if jwt_str == nil then
		
		-- Post data
		local data = {}
		data.login = username
		data.password = password
		data.space_uid = space_uid
		-- ngx.log(ngx.STDERR, cjson.encode(data))
		
		-- Call login api
		jwt_str = bus_call("/space/login", data)
	
	end
	
	-- ngx.log(ngx.STDERR, "JWT " .. tostring(jwt_str))
	
	-- Check JWT Token Sign
	local jwt_public_key = os.getenv("JWT_PUBLIC_KEY")
	local jwt_obj = jwt:verify(jwt_public_key, jwt_str)
	
	-- ngx.log(ngx.STDERR, jwt_public_key)
	-- ngx.log(ngx.STDERR, cjson.encode(jwt_obj))
	-- ngx.log(ngx.STDERR, jwt_obj.valid ~= true)
	-- ngx.log(ngx.STDERR, jwt_obj.verified ~= true)
	
	if jwt_obj.valid ~= true or jwt_obj.verified ~= true then
		jwt_str = ""
	end
	
	-- Save to cache
	cache:set(cache_key, jwt_str, 180)
	
	if jwt_str == "" then
		return 0
	end
	
	return 1
end


local function check_basic_auth()
	
	local auth_header = ngx.req.get_headers()['Authorization']
	
	if auth_header == nil then
		return 0
	end
	
	local index = auth_header:find('Basic ')
	if index == nil then
		return 0
	end
	
	auth_header = auth_header:sub(7)
	auth_header = ngx.decode_base64(auth_header)
	if auth_header == nil then
		return 0
	end
	
	local index = auth_header:find(':')
	if index == nil then
		return 0
	end
	local username = auth_header:sub(0, index - 1)
	local password = auth_header:sub(index + 1)
	
	if check_htpasswd_os(username, password) == 1 then
		ngx.req.set_header("JWT_AUTH_USER", username)
		return 1
	end
	
	return 0
end


local function check_jwt_auth()
	
	local cjson = require "cjson"
	local jwt = require "resty.jwt"
	
	-- Read JWT Cookie
	local jwt_cookie_key = os.getenv("JWT_COOKIE_KEY")
	-- ngx.log(ngx.STDERR, jwt_cookie_key)
	if jwt_cookie_key == nil then
		return 0
	end
	
	jwt_cookie_key = "cookie_" .. jwt_cookie_key
	local jwt_str = ngx.var[jwt_cookie_key]

	-- Check JWT Cookie
	if jwt_str == nil or jwt_str == '' then
		return 0
	else
		
		-- Check Token Sign
		local jwt_public_key = os.getenv("JWT_PUBLIC_KEY")
		local jwt_obj = jwt:verify(jwt_public_key, jwt_str)
		
		-- ngx.log(ngx.STDERR, jwt_public_key)
		-- ngx.log(ngx.STDERR, cjson.encode(jwt_obj))
		
		if jwt_obj.valid == true and jwt_obj.verified == true then
			
			ngx.req.set_header("JWT_AUTH_USER", jwt_obj.payload.l)
			
			return 1
		end
		
	end
	
	return 0
end


local function show_login_page()
	
	-- Check no redirect pages
	local is_show_login_page = 1
	if ngx.var.no_redirect_login == "1" or ngx.var.no_redirect_login == 1 then
		if string.sub(ngx.var.request_uri, 1, 6) == "/login" then
			is_show_login_page = 0
		end
		if string.sub(ngx.var.request_uri, 1, 7) == "/logout" then
			is_show_login_page = 0
		end
	end
	if ngx.var.no_redirect_api == "1" or ngx.var.no_redirect_api == 1 then
		if string.sub(ngx.var.request_uri, 1, 5) == "/api/" then
			is_show_login_page = 0
		end
	end
	
	-- Get redirect url
	local redirect_url = ""
	if ngx.var.http_x_route_prefix ~= nil and ngx.var.http_x_route_prefix ~= "" then
		redirect_url = redirect_url .. ngx.var.http_x_route_prefix
	end
	redirect_url = redirect_url .. ngx.var.request_uri
	
	if is_show_login_page == 1 then
		return ngx.redirect("/login?r=" .. redirect_url, ngx.HTTP_MOVED_TEMPORARILY);
	end
	
	-- ngx.log(ngx.STDERR, "Show login=" .. tostring(is_show_login_page))
	
end


local function show_basic_auth()
	ngx.header.content_type = 'text/plain'
	ngx.header.www_authenticate = 'Basic realm=""'
	ngx.status = ngx.HTTP_UNAUTHORIZED
	ngx.say('401 Access Denied')
	ngx.exit(ngx.HTTP_UNAUTHORIZED)
end


-- Set default user name
ngx.req.set_header("JWT_AUTH_USER", "")

-- Is auth
local is_jwt_auth = check_jwt_auth()
local is_basic_auth = check_basic_auth()

-- ngx.log(ngx.STDERR, "Enable auth basic=" .. tostring(ngx.var.enable_auth_basic))
-- ngx.log(ngx.STDERR, "Is JWT Auth=" .. tostring(is_jwt_auth))
-- ngx.log(ngx.STDERR, "Is Basic Auth=" .. tostring(is_basic_auth))

if ngx.var.enable_auth_basic == "1" or ngx.var.enable_auth_basic == 1 then
	if is_basic_auth == 0 and is_jwt_auth == 0 then
		show_basic_auth()
	end
else
	if is_jwt_auth == 0 then
		show_login_page()
	end
end
