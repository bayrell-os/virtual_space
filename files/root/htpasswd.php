#!/usr/bin/php
<?php

define("CLOUD_OS_KEY", getenv("CLOUD_OS_KEY"));
define("CLOUD_OS_GATEWAY", getenv("CLOUD_OS_GATEWAY"));


/**
 * Returns curl
 */
function curl($url, $data)
{
	$time = time();
	$key = CLOUD_OS_KEY;
	$arr = array_keys($data); sort($arr);
	array_unshift($arr, $time);
	$text = implode("|", $arr);
	$sign = hash_hmac("SHA512", $text, $key);
	
	$curl = curl_init();
	$opt =
	[
		CURLOPT_URL => $url,
		CURLOPT_TIMEOUT => 10,
		CURLOPT_CONNECTTIMEOUT => 10,
		CURLOPT_FOLLOWLOCATION => 10,
		CURLOPT_SSL_VERIFYHOST => 0,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HTTPHEADER =>
		[
			"Content-Type: application/json",
		],
		CURLOPT_POSTFIELDS => json_encode
		(
			[
				"data" => $data,
				"time" => $time,
				"sign" => $sign,
			]
		),
	];
	curl_setopt_array($curl, $opt);
	return $curl;
}


/**
 * Send api request
 */
function send_api($url, $data)
{
	$curl = curl($url, $data);
	$out = curl_exec($curl);
	$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	curl_close($curl);
	$response = null;
	$code = (int)$code;
	
	//var_dump($out);
	//var_dump($url);
	//var_dump($data);
	//var_dump($code);
	
	if ($code == 200 || $code == 204)
	{
		$response = @json_decode($out, true);
	}
	else if ($code == 400 || $code == 404)
	{
		$response = @json_decode($out, true);
	}
	
	//var_dump($response);
	
	if (
		$response != null &&
		isset($response["error"]) &&
		isset($response["error"]["code"]) &&
		$response["error"]["code"] == 1
	)
	{
		return $response["result"];
	}
	
	return null;
}


/**
 * Update nginx file
 */
function update_nginx_file($file_name, $new_content)
{
	$file = "/etc/nginx" . $file_name;
	$old_content = "";
	
	if (file_exists($file))
	{
		$old_content = @file_get_contents($file);
	}
	
	$dir_name = dirname($file);
	if (!file_exists($dir_name))
	{
		mkdir($dir_name, 0775, true);
	}
	
	if ($old_content != $new_content || !file_exists($file) && $new_content == "")
	{
		file_put_contents($file, $new_content);
		echo "[router.php] Updated nginx file " . $file_name . "\n";
		return true;
	}
	
	return false;
}


/**
 * Reload nginx
 */
function nginx_reload()
{
	echo "[router.php] Nginx reload\n";
	$s = shell_exec("/usr/sbin/nginx -s reload");
	echo "[router.php] " . $s;
}


/**
 * Update htpasswd
 */
function update_htpasswd()
{
	$url = "http://" . CLOUD_OS_GATEWAY . "/api/bus/nginx/htpasswd/";
	$data = [];
	$api_res = send_api($url, $data);
    
    $content = "";
	if ($api_res && isset($api_res["content"]))
	{
		$content = $api_res["content"];
	}
    $res = update_nginx_file("/inc/htpasswd.inc", $content);
    return $res;
}


$res = false;

/* Update nginx files */
if (update_htpasswd()) $res = true;

/* Reload nginx */
if ($res)
{
	nginx_reload();
}