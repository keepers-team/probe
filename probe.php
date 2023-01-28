<?php

Header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
mb_internal_encoding("UTF-8");

$proxies = [
    null,
    ['gateway.keeps.cyou', 2081],
    ['gateway.keepers.tech', 60789]
];

$forum = [
    'rutracker.org',
    'rutracker.net',
    'rutracker.nl'
];

$api = [
    'api.rutracker.cc'
];

$connectivity = array();
foreach ($forum as $addr){
    $connectivity[$addr] = array();
}

function getWebTloVersion(){
    $version_json_path = dirname(__FILE__) . '/version.json';
    if (!file_exists($version_json_path)) {
        return "version file not found";
    }
    $version_json = (object) json_decode(file_get_contents($version_json_path), true);
    return $version_json->version;
}

function getConfig(){
    $config_path = dirname(__FILE__) . '/data/config.ini';
    if (!file_exists($config_path)) {
        return array();
    }
    $config = parse_ini_file($config_path);
    return $config;
}

function getNullSafeProxy($proxy, $short = false)
{
    $replaceTemplate = $short ? "gateway." : "";
    return $proxy==null ? "no proxy" : str_replace($replaceTemplate, "", $proxy[0]);
}

function getUrl($url, $proxy)
{
    try {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 2,
            CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36",
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_URL => $url,
        ]);
        if (null !== $proxy) {
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5_HOSTNAME);
            curl_setopt($ch, CURLOPT_PROXY, sprintf("%s:%d", $proxy[0], $proxy[1]));
        }
        curl_exec($ch);
        return curl_getinfo($ch, CURLINFO_HTTP_CODE);
    } catch (Exception $e) {
        return null;
    }
}

function checkAccess($proxies, $hostnames, $tpl)
{
    foreach ($hostnames as $hostname) {
        $url = sprintf($tpl, $hostname);
        foreach ($proxies as $proxy) {
            $code = getUrl($url, $proxy);
            $nullsafe = getNullSafeProxy($proxy);
            $GLOBALS['connectivity'][$hostname][$nullsafe] = $code;
        }
    }
}

checkAccess($proxies, $forum, "https://%s/forum/info.php?show=copyright_holders");
checkAccess($proxies, $api, "https://%s/v1/get_client_ip");

$config = getConfig();

$probe = new stdClass();

$probe->version = getWebTloVersion();

$probe->config = new stdClass();
$probe->config->forum_url = $config['forum_url'] == 'custom' ? $config['forum_url_custom'] : $config['forum_url'];
$probe->config->forum_ssl = $config['forum_ssl'];
$probe->config->api_url = $config['api_url'] == 'custom' ? $config['api_url_custom'] : $config['api_url'];
$probe->config->api_ssl = $config['api_ssl'];

$probe->config->proxy = new stdClass();
if($config['activate_forum'] == 1 || $config['activate_api'] == 1){
    $probe->config->proxy->url = $config['hostname'].":".$config['port'];
    $probe->config->proxy->type = $config['type'];
}
$probe->config->proxy->activate_forum = $config['activate_forum'];
$probe->config->proxy->activate_api = $config['activate_api'];

$probe->server = $_SERVER['SERVER_SOFTWARE'];

$probe->php = new stdClass();
$probe->php->version = phpversion();
$probe->php->memory_limit = ini_get('memory_limit');
$probe->php->max_execution_time = ini_get('max_execution_time');
$probe->php->max_input_time = ini_get('max_input_time');
$probe->php->max_input_vars = ini_get('max_input_vars');
?>
<!DOCTYPE html>
<html class="ui-widget-content" lang="en">
<head>
    <meta charset="utf-8"/>
    <title>webTLO configuration checker</title>
    <link rel="stylesheet" href="https://cdn.simplecss.org/simple.min.css">
    <script>
        const binUrl = "https://bin.keepers.tech/upload";
		async function sendData(url, data) {
			const formData  = new FormData();
			for(const name in data) {
				formData.append(name, data[name]);
			}
			return await fetch(url, {method: 'POST', body: formData});
		}

		async function upload() {
			let results = document.getElementById("result").textContent;
			let payload = {"expiration": "1week", "syntax-highlight": "json", "content": results};
			let resp = await sendData(binUrl, payload);

			const p1 = document.createElement("p");
            const p1text = document.createTextNode("Configuration shared");
			p1.appendChild(p1text);
			const p2 = document.createElement("p");
			const p2url = document.createElement("a");
			p2url.attributes['href'] = resp.url;
			const p2text = document.createTextNode(resp.url);
			p2url.appendChild(p2text);
            p2.appendChild(p2url);
            document.getElementById("shares").appendChild(p1);
            document.getElementById("shares").appendChild(p2);
        }
		window.addEventListener("load", () => {
			document.getElementById("send").addEventListener("click", upload);
        })
    </script>
</head>
<body>
<header>
    <h1>webTLO configuration checker</h1>
    <button id="send">Share my config</button>
    <div id="shares"></div>
</header>
<main>
    <h3>Configuration</h3>
<pre id="result">
<?php 

$line1 = "|                   |";
$line2 = "| ----------------- |";
foreach ($proxies as $proxy){
    $nullsafe = getNullSafeProxy($proxy, true);
    $line1 = $line1." ".str_pad($nullsafe, 12)." |";
    $line2 = $line2." ".str_pad("-", 12)." |";
}
echo $line1."\r\n";
echo $line2."\r\n";

foreach (array_merge($forum, $api) as $url){
    $line = "| ".str_pad($url, 17)." | ";
    foreach ($proxies as $proxy){
        $nullsafe = getNullSafeProxy($proxy);
        $code = $connectivity[$url][$nullsafe];
        $emoji = ($code < 300 && $code > 0) ? "✅" : "❌";
        $line = $line.str_pad($emoji." ".$code, 12)." | ";
    }
    echo $line."\r\n";
}

echo "\r\n".json_encode($probe, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\r\n";
?>
</pre>
</main>
<footer>
    <p>made with ☕</p>
</footer>
</body>
</html>
