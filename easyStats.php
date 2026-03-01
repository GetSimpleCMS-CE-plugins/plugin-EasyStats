<?php

# get correct id for plugin
$thisfile = basename(__FILE__, ".php");

# register plugin
register_plugin(
	$thisfile, //Plugin id
	'easyStats 📊',	//Plugin name
	'2.0',			//Plugin version
	'CE Team',		//Plugin author
	'https://getsimple-ce.ovh/donate', //author website
	'This plugin shows statistics by counting only unique IP addresses on a website ', //Plugin description
	'pages',		//page type - on which admin tab to display
	'easyStatsView'	//main function (administration)
);

# activate filter
add_action('theme-footer', 'makeEasyStats');

# add a link in the admin tab 'theme'
add_action('pages-sidebar', 'createSideMenu', array($thisfile, 'Stats 📊'));

function easyStatsView() {
	$visitorsFile = GSDATAOTHERPATH . 'easyStats/visitors.xml';

	$currentTimestamp = time();

	$allVisitors		= [];
	$visitors7Days		= [];
	$visitors30Days		= [];
	$visitors24Hours	= [];
	$visitors5Minutes	= [];

	if (file_exists($visitorsFile)) {
		$xml = simplexml_load_file($visitorsFile);
		foreach ($xml->visitor as $visitor) {
			$visitorIp		= (string) $visitor->ip;
			$visitorTimestamp = (int)	$visitor->timestamp;
			$diff			 = $currentTimestamp - $visitorTimestamp;

			$allVisitors[] = $visitorIp;

			if ($diff <= 30 * 24 * 60 * 60) { $visitors30Days[]   = $visitorIp; }
			if ($diff <=  7 * 24 * 60 * 60) { $visitors7Days[]	= $visitorIp; }
			if ($diff <=	   24 * 60 * 60) { $visitors24Hours[]  = $visitorIp; }
			if ($diff <=			5 * 60)  { $visitors5Minutes[] = $visitorIp; }
		}
	}

	$uniqueAllVisitors	= count(array_unique($allVisitors));
	$uniqueVisitors30Days = count(array_unique($visitors30Days));
	$uniqueVisitors7Days  = count(array_unique($visitors7Days));
	$uniqueVisitors24Hours= count(array_unique($visitors24Hours));
	$uniqueVisitors5Minutes = count(array_unique($visitors5Minutes));

	echo '
	<style>
	h2{font-weight:600;}
	</style>
	';
	
	echo '
	<div style="width:100%; background:#fafafa; border:solid 1px #ddd; padding:15px; margin-bottom:30px;">
		<h3>📊 Easy Stats</h3>
		<p>This plugin shows statistics by counting only unique IP addresses on a website.</p>
	</div>

	<div class="bg-light border p-2"><h2><svg xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;" width="1.5em" height="1.2em" viewBox="0 0 640 512"><rect width="640" height="512" fill="none"/><path fill="#707070" d="M96 224c35.3 0 64-28.7 64-64s-28.7-64-64-64s-64 28.7-64 64s28.7 64 64 64m448 0c35.3 0 64-28.7 64-64s-28.7-64-64-64s-64 28.7-64 64s28.7 64 64 64m32 32h-64c-17.6 0-33.5 7.1-45.1 18.6c40.3 22.1 68.9 62 75.1 109.4h66c17.7 0 32-14.3 32-32v-32c0-35.3-28.7-64-64-64m-256 0c61.9 0 112-50.1 112-112S381.9 32 320 32S208 82.1 208 144s50.1 112 112 112m76.8 32h-8.3c-20.8 10-43.9 16-68.5 16s-47.6-6-68.5-16h-8.3C179.6 288 128 339.6 128 403.2V432c0 26.5 21.5 48 48 48h288c26.5 0 48-21.5 48-48v-28.8c0-63.6-51.6-115.2-115.2-115.2m-223.7-13.4C161.5 263.1 145.6 256 128 256H64c-35.3 0-64 28.7-64 64v32c0 17.7 14.3 32 32 32h65.9c6.3-47.4 34.9-87.3 75.2-109.4"/></svg> Visitors Online</h2></div>
	';

	echo '
	<table class="table">
		<tr><td>Unique visitors all time: <b style="background-color:#A3A4FF;padding:0 3px;border-radius:3px;">' . (int)$uniqueAllVisitors	. '</b></td></tr>
		<tr><td>Unique visitors last 30 days: <b style="background-color:#79E6A0;padding:0 3px;border-radius:3px;">' . (int)$uniqueVisitors30Days  . '</b></td></tr>
		<tr><td>Unique visitors last 7 days: <b style="background-color:#FFDB82;padding:0 3px;border-radius:3px;">'  . (int)$uniqueVisitors7Days   . '</b></td></tr>
		<tr><td>Unique visitors last 24 hours: <b style="background-color:#F9B27D;padding:0 3px;border-radius:3px;">'. (int)$uniqueVisitors24Hours . '</b></td></tr>
		<tr><td>Unique visitors last 5 minutes: <b style="background-color:#FF90C8;padding:0 3px;border-radius:3px;">'. (int)$uniqueVisitors5Minutes . '</b></td></tr>
	</table>
	';

	// Chart.js bar chart
	echo '
	<canvas style="margin:20px 0" id="statisticsChart" width="400" height="200"></canvas>
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	';

	// Pages stats
	$pagesFile = GSDATAOTHERPATH . 'easyStats/pagesCount.xml';

	$pages = [];
	if (file_exists($pagesFile)) {
		$xml = simplexml_load_file($pagesFile);
		foreach ($xml->page as $page) {
			$pageUrl			= (string) $page->url;
			$pageVisits		 = (int)	$page->visits;
			$pageUniqueVisitors = explode(',', (string) $page->unique_visitors);
			$pageTimestamps	 = explode(',', (string) $page->timestamps);
			$pages[$pageUrl] = [
				'visits'		  => $pageVisits,
				'unique_visitors' => $pageUniqueVisitors,
				'timestamps'	  => $pageTimestamps,
			];
		}
	}

	// Sort pages by visit count descending and limit to top 20
	uasort($pages, function ($a, $b) {
		return $b['visits'] <=> $a['visits'];
	});
	$pages = array_slice($pages, 0, 20, true);

	echo '
	<hr style="margin-bottom:30px;">
	<div class="col-md-12 bg-light border p-2"><h2><svg xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;"  width="1.2em" height="1.2em" viewBox="0 0 24 24"><rect width="24" height="24" fill="none"/><path fill="#707070" d="M18 20.5a.5.5 0 0 0 .5-.5V10H14a2 2 0 0 1-2-2V3.5H6a.5.5 0 0 0-.5.5v5.25a.75.75 0 0 1-1.5 0V4a2 2 0 0 1 2-2h6.172c.515 0 1.047.22 1.413.586l5.829 5.828A2 2 0 0 1 20 9.828V20a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-3.75a.75.75 0 0 1 1.5 0V20a.5.5 0 0 0 .5.5zm-.622-12L13.5 4.621V8a.5.5 0 0 0 .5.5zM2.75 13h2.505l1.557-3.551a.75.75 0 0 1 1.327-.091l.006.01l.05.103l2.413 6.027l1.427-2.1a.75.75 0 0 1 .545-.389l.01-.002l.107-.007h2a.75.75 0 0 1 .113 1.492l-.01.001l-.103.007h-1.554l-1.979 3.094a.75.75 0 0 1-1.306.04l-.005-.009l-.05-.101l-2.336-5.835l-1.035 2.362a.75.75 0 0 1-.564.439l-.012.002l-.11.008H2.75a.75.75 0 0 1-.113-1.492l.01-.001z"/></svg> Most Popular Pages:</h2></div>
	<table class="table">
	';
	foreach ($pages as $url => $pageData) {
		$safeUrl	 = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
		$uniqueCount = (int) count($pageData['unique_visitors']);
		echo '
		<tr>
			<td><svg xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;" width="1.2em" height="1.2em" viewBox="0 0 24 24"><rect width="24" height="24" fill="none"/><path fill="currentColor" d="M4 21V9l8-6l8 6v12h-6v-7h-4v7z"/></svg> <b>' . $safeUrl . '</b> – unique views: <b style="background-color:#8CDAF8;padding:0 3px;border-radius:3px;">' . $uniqueCount . '</b></td>
		</tr>
		';
	}
	echo '
	</table>
	';

	// Inline chart script
	echo "
<script>
  const ctx = document.getElementById('statisticsChart');
  new Chart(ctx, {
	type: 'bar',
	data: {
	  labels: [
		'All time',
		'Last 30 days',
		'Last 7 days',
		'Last 24 hours',
		'Last 5 minutes'
	  ],
	  datasets: [{
		label: 'Unique Visitors Online',
		data: [
		  " . (int)$uniqueAllVisitors	 . ",
		  " . (int)$uniqueVisitors30Days  . ",
		  " . (int)$uniqueVisitors7Days   . ",
		  " . (int)$uniqueVisitors24Hours . ",
		  " . (int)$uniqueVisitors5Minutes . "
		],
		backgroundColor: [
		  'rgba(99, 102, 241, 0.7)',
		  'rgba(34, 197, 94, 0.7)',
		  'rgba(251, 191, 36, 0.7)',
		  'rgba(249, 115, 22, 0.7)',
		  'rgba(236, 72, 153, 0.7)'
		],
		borderColor: [
		  'rgba(99, 102, 241, 1)',
		  'rgba(34, 197, 94, 1)',
		  'rgba(251, 191, 36, 1)',
		  'rgba(249, 115, 22, 1)',
		  'rgba(236, 72, 153, 1)'
		],
		borderWidth: 1
	  }]
	},
	options: {
	  scales: { y: { beginAtZero: true } }
	}
  });
</script>
";

	global $USR;
	echo '
	<style>
	.donateButton{box-shadow:inset 0px 1px 0px 0px #fce2c1;background:linear-gradient(to bottom, #ffc477 5%, #fb9e25 100%);background-color:#ffc477;border-radius:15px;border:1px solid #eeb44f;display:inline-block; cursor:pointer;color:#ffffff!important;font-family:Arial;font-size:15px;font-weight:bold;padding:5px 10px;text-decoration:none!important;text-shadow:0px 1px 0px #cc9f52}.donateButton:hover{background:linear-gradient(to bottom, #fb9e25 5%, #ffc477 100%);background-color:#fb9e25}.donateButton:active{position:relative;top:1px} hr{border:0;height:0;border-top:1px solid rgba(0, 0, 0, 0.1);border-bottom:1px solid rgba(255, 255, 255, 0.3)}
	</style>
	<footer id="paypal">
		<hr>
		<p style="margin:20px 0 0">Made with <span class="credit-icon">❤️</span> especially for "<b>' . $USR  . '</b>". Is this plugin useful to you?
		<a href="https://getsimple-ce.ovh/donate" target="_blank" class="donateButton"><b>Buy Us A Coffee </b><svg xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" fill-opacity="0" d="M17 14v4c0 1.66 -1.34 3 -3 3h-6c-1.66 0 -3 -1.34 -3 -3v-4Z"><animate fill="freeze" attributeName="fill-opacity" begin="0.8s" dur="0.5s" values="0;1"/></path><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path stroke-dasharray="48" stroke-dashoffset="48" d="M17 9v9c0 1.66 -1.34 3 -3 3h-6c-1.66 0 -3 -1.34 -3 -3v-9Z"><animate fill="freeze" attributeName="stroke-dashoffset" dur="0.6s" values="48;0"/></path><path stroke-dasharray="14" stroke-dashoffset="14" d="M17 9h3c0.55 0 1 0.45 1 1v3c0 0.55 -0.45 1 -1 1h-3"><animate fill="freeze" attributeName="stroke-dashoffset" begin="0.6s" dur="0.2s" values="14;0"/></path><mask id="lineMdCoffeeHalfEmptyFilledLoop0"><path stroke="#fff" d="M8 0c0 2-2 2-2 4s2 2 2 4-2 2-2 4 2 2 2 4M12 0c0 2-2 2-2 4s2 2 2 4-2 2-2 4 2 2 2 4M16 0c0 2-2 2-2 4s2 2 2 4-2 2-2 4 2 2 2 4"><animateMotion calcMode="linear" dur="3s" path="M0 0v-8" repeatCount="indefinite"/></path></mask><rect width="24" height="0" y="7" fill="currentColor" mask="url(#lineMdCoffeeHalfEmptyFilledLoop0)"><animate fill="freeze" attributeName="y" begin="0.8s" dur="0.6s" values="7;2"/><animate fill="freeze" attributeName="height" begin="0.8s" dur="0.6s" values="0;5"/></rect></g></svg></a></p>
	</footer>
	';
}

function makeEasyStats() {
	$http_response_code = http_response_code();
	if ($http_response_code === 404) {
		return;
	}

	$easyStatsDir = GSDATAOTHERPATH . 'easyStats/';
	$visitorsFile = $easyStatsDir . 'visitors.xml';
	$pagesFile	= $easyStatsDir . 'pagesCount.xml';

	if (!is_dir($easyStatsDir)) {
		mkdir($easyStatsDir, 0755, true);
		file_put_contents($easyStatsDir . '.htaccess', 'Deny from All');
	}

	if (!file_exists($visitorsFile)) {
		file_put_contents($visitorsFile, '<?xml version="1.0"?>' . "\n<visitors></visitors>\n");
	}

	if (!file_exists($pagesFile)) {
		file_put_contents($pagesFile, '<?xml version="1.0"?>' . "\n<pages></pages>\n");
	}

	// Hash IP immediately – never store raw IPs
	$ipAddress		= hash('sha256', $_SERVER['REMOTE_ADDR']);
	$currentTimestamp = time();
	$thirtyDaysAgo	= $currentTimestamp - (30 * 24 * 60 * 60);

	// visitors.xml – track unique IPs with file locking
	$fp = fopen($visitorsFile, 'r+');
	if ($fp && flock($fp, LOCK_EX)) {
		$xmlContent = stream_get_contents($fp);
		$xml		= simplexml_load_string($xmlContent);

		$allVisitors = [];
		// Keep only visitors seen within the last 30 days (prune old entries)
		$filteredVisitors = [];
		foreach ($xml->visitor as $visitor) {
			$visitorIp		= (string) $visitor->ip;
			$visitorTimestamp = (int)	$visitor->timestamp;

			if ($visitorTimestamp >= $thirtyDaysAgo) {
				$filteredVisitors[] = ['ip' => $visitorIp, 'timestamp' => $visitorTimestamp];
				$allVisitors[]	  = $visitorIp;
			}
			// Entries older than 30 days are silently dropped (pruned)
		}

		// Add new visitor if not already recorded at all
		if (!in_array($ipAddress, $allVisitors, true)) {
			$filteredVisitors[] = ['ip' => $ipAddress, 'timestamp' => $currentTimestamp];
		}

		// Rebuild XML
		$newXml = new SimpleXMLElement('<?xml version="1.0"?><visitors></visitors>');
		foreach ($filteredVisitors as $v) {
			$node = $newXml->addChild('visitor');
			$node->addChild('ip',		$v['ip']);
			$node->addChild('timestamp', $v['timestamp']);
		}

		// Write back
		rewind($fp);
		ftruncate($fp, 0);
		fwrite($fp, $newXml->asXML());
		flock($fp, LOCK_UN);
	}
	if ($fp) {
		fclose($fp);
	}

	// pagesCount.xml – track per-page unique visitors with file locking
	$currentUrl = $_SERVER['REQUEST_URI'];

    // Skip tracking internal GetSimple pages (search results, admin session tokens)
    if (strpos($currentUrl, '?search=') !== false || strpos($currentUrl, '?is=') !== false) {
        return;
    }

	// Sanitise the URL key – store only a safe version
	$currentUrl = filter_var($currentUrl, FILTER_SANITIZE_URL);

	$fp = fopen($pagesFile, 'r+');
	if ($fp && flock($fp, LOCK_EX)) {
		$xmlContent = stream_get_contents($fp);
		$xml		= simplexml_load_string($xmlContent);

		$pages = [];
		foreach ($xml->page as $page) {
			$pageUrl = (string) $page->url;
			$pages[$pageUrl] = [
				'visits'		  => (int) $page->visits,
				'unique_visitors' => array_filter(explode(',', (string) $page->unique_visitors), 'strlen'),
				'timestamps'	  => array_filter(explode(',', (string) $page->timestamps),	  'strlen'),
			];
		}

		// Update current page
		if (array_key_exists($currentUrl, $pages)) {
			$pages[$currentUrl]['visits']++;
			if (!in_array($ipAddress, $pages[$currentUrl]['unique_visitors'], true)) {
				$pages[$currentUrl]['unique_visitors'][] = $ipAddress;
			}
			$pages[$currentUrl]['timestamps'][] = $currentTimestamp;
		} else {
			$pages[$currentUrl] = [
				'visits'		  => 1,
				'unique_visitors' => [$ipAddress],
				'timestamps'	  => [$currentTimestamp],
			];
		}

		// Rebuild XML
		$newXml = new SimpleXMLElement('<?xml version="1.0"?><pages></pages>');
		foreach ($pages as $pageUrl => $pageData) {
			$node = $newXml->addChild('page');
			$node->addChild('url',			 $pageUrl);
			$node->addChild('visits',		  $pageData['visits']);
			$node->addChild('unique_visitors', implode(',', $pageData['unique_visitors']));
			$node->addChild('timestamps',	  implode(',', $pageData['timestamps']));
		}

		// Write back
		rewind($fp);
		ftruncate($fp, 0);
		fwrite($fp, $newXml->asXML());
		flock($fp, LOCK_UN);
	}
	if ($fp) {
		fclose($fp);
	}
}
