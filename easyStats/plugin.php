<?php
class easyStats extends Plugin
{



    public function adminView()
    {

        $html = '';

        $visitorsFile =  PATH_CONTENT . 'easyStats/visitors.xml';

        // Pobranie adresu IP odwiedzającego
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $ipAddress = hash('sha256', $ipAddress);

        // Aktualna data i czas
        $currentTimestamp = time();

        //Read current visitors from the temporary file
        $currentVisitors = [];




        //Read existing visitors from an XML file
        $allVisitors = [];
        $visitors7Days = [];
        $visitors30Days = [];
        $visitors24Hours = [];
        $visitors5Minutes = [];

        if (file_exists($visitorsFile)) {
            $xml = simplexml_load_file($visitorsFile);
            foreach ($xml->visitor as $visitor) {
                $visitorIp = (string) $visitor->ip;
                $visitorTimestamp = (int) $visitor->timestamp;
                $allVisitors[] = $visitorIp;



                if ($currentTimestamp - $visitorTimestamp <= 7 * 24 * 60 * 60) {
                    $visitors7Days[] = $visitorIp;
                }

                if ($currentTimestamp - $visitorTimestamp <= 30 * 24 * 60 * 60) {
                    $visitors30Days[] = $visitorIp;
                }

                if ($currentTimestamp - $visitorTimestamp <= 24 * 60 * 60) {
                    $visitors24Hours[] = $visitorIp;
                }

                if ($currentTimestamp - $visitorTimestamp <= 5 * 60) {
                    $visitors5Minutes[] = $visitorIp;
                }
            }
        }

        // Get the number of unique visitors
        $uniqueAllVisitors = count(array_unique($allVisitors));
        $uniqueVisitors7Days = count(array_unique($visitors7Days));
        $uniqueVisitors30Days = count(array_unique($visitors30Days));
        $uniqueVisitors24Hours = count(array_unique($visitors24Hours));
        $uniqueVisitors5Minutes = count(array_unique($visitors5Minutes));

        // Get the number of current visitors
        $currentVisitorsCount = count($currentVisitors);

        // Display information

        $html .=  "

    <div style='width:100%;background:#fafafa;border:solid 1px #ddd; padding:15px;margin-bottom:20px;'>
    <h3>Easy Stats</h3>
    <b>this plugin shows statistics by counting only unique IP addresses on a website</b>
    </div>

    <div class='bg-light border p-2'><h2>Newest Stats</h2></div>
    ";
        $html .=  '<table class="table">';

        $html .=  "<tr><td>Unique user from all the time: $uniqueAllVisitors</tr></td>";
        $html .=  "<tr><td>Unique user from last 30 days: $uniqueVisitors30Days</tr></td>";
        $html .=  "<tr><td>Unique user from last 7 days: $uniqueVisitors7Days</tr></td>";
        $html .=  "<tr><td>Unique user from last 24hours: $uniqueVisitors24Hours </tr></td>";
        $html .= "<tr><td>Unique user from last 5 minutes:" . $uniqueVisitors5Minutes . "</tr></td>";
        $html .=  '</table>';




        $html .=  ' 
<canvas style="margin:20px;0" id="statisticsChart" width="400" height="200"></canvas>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

';




        $pagesFile = PATH_CONTENT . 'easyStats/pagesCount.xml';

        // Get the current URL
        $currentUrl = $_SERVER['REQUEST_URI'];

        // Get the visitor's IP address
        $ipAddress = $_SERVER['REMOTE_ADDR'];


        // Date 30 days back
        $thirtyDaysAgo = strtotime('-30 days');

        //Read existing visited pages from an XML file
        $pages = [];
        if (file_exists($pagesFile)) {
            $xml = simplexml_load_file($pagesFile);
            foreach ($xml->page as $page) {
                $pageUrl = (string) $page->url;
                $pageVisits = (int) $page->visits;
                $pageUniqueVisitors = explode(',', (string) $page->unique_visitors);
                $pageTimestamps = explode(',', (string) $page->timestamps);
                $pages[$pageUrl] = [
                    'visits' => $pageVisits,
                    'unique_visitors' => $pageUniqueVisitors,
                    'timestamps' => $pageTimestamps
                ];
            }
        }


        // Sort pages by number of visits in reverse order
        uasort($pages, function ($a, $b) {
            return $b['visits'] <=> $a['visits'];
        });

        // Download the list of the 100 most visited websites from the last 30 days
        $top100Pages = $pages;




        // Display a list of the 100 most frequently visited pages
        $html .=  "<div class='col-md-12 bg-light border p-2'><h4> Most popular views: </h4></div>";

        $html .=  '<table class="table">';
        foreach ($top100Pages as $url => $pageData) {
            $html .= "<tr><td><b>" . $url . "</b>- unique Views: " . count($pageData['unique_visitors']) . "</td></tr>";
        }

        $html .= "</table>

    


<!-- online script -->

<script>
  const ctx = document.getElementById('statisticsChart');

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['Unique user All the time', 'Unique user last 30 days:', 'Unique user last 7 days:','Unique user last 24hours','Unique user last 5 minutes'],
      datasets: [{
        label: 'Views on website',
        data: [$uniqueAllVisitors,$uniqueVisitors30Days,$uniqueVisitors7Days,$uniqueVisitors24Hours,$uniqueVisitors5Minutes],
        borderWidth: 1
      }]
    },
    options: {
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });
</script>


";

        $html .= '<div id="paypal" style="margin-top:10px; background: #fafafa; border:solid 1px #ddd; padding: 10px;box-sizing: border-box; text-align: center;">
<p style="margin-bottom:10px;">If you want to see new plugins, buy me a ☕ :) </p>
<a href="https://www.paypal.com/donate/?hosted_button_id=TW6PXVCTM5A72"><img alt="" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0"></a>
</div>';

        return $html;
    }

    public function adminSidebar()
    {
        $pluginName = Text::lowercase(__CLASS__);
        $url = HTML_PATH_ADMIN_ROOT . 'plugin/' . $pluginName;
        $html = '<a id="current-version" class="nav-link" href="' . $url . '">EasyStats 📊</a>';
        return $html;
    }



    public function pageEnd()
    {

        $currentTimestamp = time();
        $http_response_code = http_response_code();

        if ($http_response_code !== 404) {

            $visitorsFile = PATH_CONTENT . 'easyStats/visitors.xml';

            if (!is_dir(PATH_CONTENT . 'easyStats/')) {
                mkdir(PATH_CONTENT . 'easyStats/', 0755);
                file_put_contents(PATH_CONTENT . 'easyStats/.htaccess', 'Deny from All');
                file_put_contents(PATH_CONTENT . 'easyStats/visitors.xml', '<?xml version="1.0"?>
                <visitors></visitors>
                ');
            };


            // Get the visitor's IP address
            $ipAddress = $_SERVER['REMOTE_ADDR'];
            $ipAddress = hash('sha256', $ipAddress);

            // Current date and time
            $currentTimestamp = time();

            // Read current visitors from a temporary file
            $currentVisitors = [];

            // Check if the visitor exists in the list of current visitors and remove them
            if (array_key_exists($ipAddress, $currentVisitors)) {
                unset($currentVisitors[$ipAddress]);
            }

            // Add or update information about the current visitor
            $currentVisitors[$ipAddress] = $currentTimestamp;

            // Read existing visitors from an XML file
            $allVisitors = [];
            $visitors7Days = [];
            $visitors30Days = [];
            $visitors24Hours = [];

            if (file_exists($visitorsFile)) {
                $xml = simplexml_load_file($visitorsFile);
                foreach ($xml->visitor as $visitor) {
                    $visitorIp = (string) $visitor->ip;
                    $visitorTimestamp = (int) $visitor->timestamp;
                    $allVisitors[] = $visitorIp;

                    if ($currentTimestamp - $visitorTimestamp <= 7 * 24 * 60 * 60) {
                        $visitors7Days[] = $visitorIp;
                    }

                    if ($currentTimestamp - $visitorTimestamp <= 30 * 24 * 60 * 60) {
                        $visitors30Days[] = $visitorIp;
                    }

                    if ($currentTimestamp - $visitorTimestamp <= 24 * 60 * 60) {
                        $visitors24Hours[] = $visitorIp;
                    }
                }
            }

            // Add a new visitor to the XML file
            if (!in_array($ipAddress, $allVisitors)) {
                $xml = simplexml_load_file($visitorsFile);
                $newVisitor = $xml->addChild('visitor');
                $newVisitor->addChild('ip', $ipAddress);
                $newVisitor->addChild('timestamp', $currentTimestamp);
                $xml->asXML($visitorsFile);
            }

            // Get the number of unique visitors
            $uniqueAllVisitors = count(array_unique($allVisitors));
            $uniqueVisitors7Days = count(array_unique($visitors7Days));
            $uniqueVisitors30Days = count(array_unique($visitors30Days));
            $uniqueVisitors24Hours = count(array_unique($visitors24Hours));

            // Get the current number of visitors
            $currentVisitorsCount = count($currentVisitors);

            $pagesFile = PATH_CONTENT . 'easyStats/pagesCount.xml';

            // Get the current URL
            $currentUrl = $_SERVER['REQUEST_URI'];

            // Get the visitor's IP address
            $ipAddress = $_SERVER['REMOTE_ADDR'];
            $ipAddress = hash('sha256', $ipAddress);

            // Current date and time
            $currentTimestamp = time();

            // Date 30 days ago
            $thirtyDaysAgo = strtotime('-30 days');

            // Read existing visited pages from an XML file
            $pages = [];
            if (file_exists($pagesFile)) {
                $xml = simplexml_load_file($pagesFile);
                foreach ($xml->page as $page) {
                    $pageUrl = (string) $page->url;
                    $pageVisits = (int) $page->visits;
                    $pageUniqueVisitors = explode(',', (string) $page->unique_visitors);
                    $pageTimestamps = explode(',', (string) $page->timestamps);
                    $pages[$pageUrl] = [
                        'visits' => $pageVisits,
                        'unique_visitors' => $pageUniqueVisitors,
                        'timestamps' => $pageTimestamps
                    ];
                }
            }

            // Check if the current URL contains "?search="
            $isSearchPage = strpos($currentUrl, '?search=') !== false;

            // Increase the visit and unique visitor count for the current page (if it's not a search page)
            if (!$isSearchPage) {
                if (array_key_exists($currentUrl, $pages)) {
                    $pages[$currentUrl]['visits']++;

                    if (!in_array($ipAddress, $pages[$currentUrl]['unique_visitors'])) {
                        $pages[$currentUrl]['unique_visitors'][] = $ipAddress;
                    }

                    $pages[$currentUrl]['timestamps'][] = $currentTimestamp;
                } else {
                    $pages[$currentUrl] = [
                        'visits' => 1,
                        'unique_visitors' => [$ipAddress],
                        'timestamps' => [$currentTimestamp]
                    ];
                }
            }

            // Update the XML file with information about visited pages
            $xml = new SimpleXMLElement('<pages></pages>');
            foreach ($pages as $pageUrl => $pageData) {
                $page = $xml->addChild('page');
                $page->addChild('url', $pageUrl);
                $page->addChild('visits', $pageData['visits']);
                $page->addChild('unique_visitors', implode(',', $pageData['unique_visitors']));
                $page->addChild('timestamps', implode(',', $pageData['timestamps']));
            }
            $xml->asXML($pagesFile);

            // Sort pages by visit count in descending order
            uasort($pages, function ($a, $b) {
                return $b['visits'] <=> $a['visits'];
            });

            // Get a list of the top 100 most visited pages in the last 30 days
            $top100Pages = $pages;
        }
    }
}
