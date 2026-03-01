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
	<div style="width:100%; background:#fafafa; border:solid 1px #ddd; padding:15px; margin-bottom:20px;">
		<h3>Easy Stats</h3>
		<b>This plugin shows statistics by counting only unique IP addresses on a website.</b>
	</div>

	<div class="bg-light border p-2"><h2>Stats</h2></div>
	';

	echo '
	<table class="table">
		<tr><td>Unique users all time: ' . (int)$uniqueAllVisitors	. '</td></tr>
		<tr><td>Unique users last 30 days: ' . (int)$uniqueVisitors30Days  . '</td></tr>
		<tr><td>Unique users last 7 days: '  . (int)$uniqueVisitors7Days   . '</td></tr>
		<tr><td>Unique users last 24 hours: '. (int)$uniqueVisitors24Hours . '</td></tr>
		<tr><td>Unique users last 5 minutes: '. (int)$uniqueVisitors5Minutes . '</td></tr>
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

	// Sort pages by visit count descending
	uasort($pages, function ($a, $b) {
		return $b['visits'] <=> $a['visits'];
	});

	echo '
	<div class="col-md-12 bg-light border p-2"><h2>Most popular views:</h2></div>
	<table class="table">
	';
	foreach ($pages as $url => $pageData) {
		$safeUrl	 = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
		$uniqueCount = (int) count($pageData['unique_visitors']);
		echo '
		<tr>
			<td><b>' . $safeUrl . '</b> – unique views: ' . $uniqueCount . '</td>
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
		label: 'Unique visitors',
		data: [
		  " . (int)$uniqueAllVisitors	 . ",
		  " . (int)$uniqueVisitors30Days  . ",
		  " . (int)$uniqueVisitors7Days   . ",
		  " . (int)$uniqueVisitors24Hours . ",
		  " . (int)$uniqueVisitors5Minutes . "
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

	// Skip tracking search result pages
	if (strpos($currentUrl, '?search=') !== false) {
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
