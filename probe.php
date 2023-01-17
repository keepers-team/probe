<?php

Header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
mb_internal_encoding("UTF-8");

include_once dirname(__FILE__) . '/php/common.php';
$cfg = get_settings();

?>

<!DOCTYPE html>
<html class="ui-widget-content">

<head>
    <meta charset="utf-8" />
    <title>web-TLO-<?php echo $webtlo->version ?></title>
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/i18n/jquery-ui-i18n.min.js"></script>
    <script src="scripts/jquery.mousewheel.min.js"></script>
    <script src="scripts/js.cookie.min.js"></script>
    <link rel="stylesheet" href="css/reset.css" /> <!-- сброс стилей -->
    <link rel="stylesheet" href="css/style.css" /> <!-- таблица стилей webtlo -->
    <link rel="stylesheet" href="css/font-awesome.min.css">
</head>

<body>
<?php
	$probe = new stdClass();
	
	$probe->configuration = new stdClass();
	$probe->configuration->version = $webtlo->version;
	
	$probe->connectivity = new stdClass();
	$probe->connectivity->forum_url = $cfg['forum_url'];
	$probe->connectivity->forum_ssl = $cfg['forum_ssl'];
	$probe->connectivity->api_url = $cfg['api_url'];
	$probe->connectivity->api_ssl = $cfg['api_ssl'];
		
	$probe->connectivity->forum_avaliability = new stdClass();
	$probe->connectivity->forum_avaliability->from_server = "<span id='forum.server'>waiting for responce</span>";
	$probe->connectivity->forum_avaliability->no_proxy = "<span id='forum.no'>waiting for responce</span>";
	$probe->connectivity->forum_avaliability->keepers_tech = "<span id='forum.gateway.keepers.tech'>waiting for responce</span>";
	$probe->connectivity->forum_avaliability->keeps_cyou = "<span id='forum.gateway.keeps.cyou'>waiting for responce</span>";
	
	$probe->connectivity->api_avaliability = new stdClass();
	$probe->connectivity->api_avaliability->from_server = "<span id='api.server'>waiting for responce</span>";
	$probe->connectivity->api_avaliability->no_proxy = "<span id='api.no'>waiting for responce</span>";
	$probe->connectivity->api_avaliability->keepers_tech = "<span id='api.gateway.keepers.tech'>waiting for responce</span>";
	$probe->connectivity->api_avaliability->keeps_cyou = "<span id='api.gateway.keeps.cyou'>waiting for responce</span>";

	$probe->server = new stdClass();
	$probe->server->gateway = $_SERVER['GATEWAY_INTERFACE'];
	$probe->server->software = $_SERVER['SERVER_SOFTWARE'];
	
	$probe->php = new stdClass();
	$probe->php->version = phpversion();
	$probe->php->memory_limit = ini_get('memory_limit');
	$probe->php->max_execution_time = ini_get('max_execution_time');
	$probe->php->max_input_time = ini_get('max_input_time ');
	$probe->php->max_input_vars = ini_get('max_input_vars ');
	$probe->php->extensions = get_loaded_extensions();
	

	echo "<pre style='font-size: 14px; font-family: monospace;'>".json_encode($probe, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."</pre>";

?>

    <script type="text/javascript">
        var proxy_config = [
            "proxy_type=socks5h&proxy_hostname=gateway.keeps.cyou&proxy_port=2081&proxy_activate_",
            "proxy_type=socks5h&proxy_hostname=gateway.keepers.tech&proxy_port=60789&proxy_activate_"
        ];
		
		async function test_connectivity_per_url(url, url_type) {
			await test_connectivity_per_config(url, url_type, "");
			await test_connectivity_per_config(url, url_type, proxy_config[0]+url_type);
			await test_connectivity_per_config(url, url_type, proxy_config[1]+url_type);
		}
		async function test_connectivity_per_config(url, url_type, cfg) {
			var proxy_hostname = cfg.split('&')?.find(i => i.includes("proxy_hostname"))?.split('=')[1] ?? "no";

			$.ajax({
				type: "POST",
				url: "php/actions/check_mirror_access.php",
				data: {
					cfg: cfg,
					url: url,
					url_custom: null,
					ssl: true,
					url_type: url_type
				},
				success: function (response) {
					var id = url_type+"."+proxy_hostname;
					document.getElementById(id).innerHTML = response ? true : false;
				}
			});
		}

		function get_endpoint(url, callback) {
			$.ajax({
				type: "POST",
				url: "php/actions/get_endpoint.php",
				data: {
					url: url
				},
				success: function (response) {
					callback(response);
				}
			});
		}
		function get_connectivity_from_server(name, url){
			get_endpoint(url, response => {
				var object = JSON.parse(response);
				var status = object.results[object.results.length - 1].conditionResults[0].success;
				document.getElementById(name).innerHTML = status;
			});
			
		}
		
		get_connectivity_from_server("forum.server", "https://status.keepers.tech/api/v1/endpoints/forum_main/statuses");
		get_connectivity_from_server("api.server", "https://status.keepers.tech/api/v1/endpoints/api_main/statuses");
		
		test_connectivity_per_url("<?php echo $cfg['forum_url']; ?>", "forum");
		test_connectivity_per_url("<?php echo $cfg['api_url']; ?>" , "api");
	</script>
</body>

</html>