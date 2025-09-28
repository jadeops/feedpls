<?php

require __DIR__ . '/vendor/autoload.php';

use DiDom\Document;
use FeedWriter\RSS2;
use Symfony\Component\HttpClient\HttpClient;

date_default_timezone_set('UTC');

define('PATH_LOG_FILE', __DIR__ . '/logs/cron.log');

// Truncate old logs on start
file_put_contents(PATH_LOG_FILE, '');

function msg(string $message): void
{
    error_log($message . PHP_EOL, 3, PATH_LOG_FILE);
}

/**
 * Format $posts[]
 *     'date',
 *     'title',
 *     'desc',
 *     'link',
 */
function createRssFeed(
    string $title,
    string $desc,
    string $link,
    array $posts,
    string $fileName
): bool {
    $rssFeed = new RSS2();
    $rssFeed->setTitle($title);
    $rssFeed->setDescription($desc);
    $rssFeed->setLink($link);
    if (!empty($posts)) {
        foreach ($posts as $post) {
            $item = $rssFeed->createNewItem();
            $item->setDate($post['date']);
            $item->setTitle($post['title']);
            $item->setDescription($post['desc']);
            $item->setLink($post['link']);
            $rssFeed->addItem($item);
        }
        file_put_contents(__DIR__ . "/feeds/$fileName.rss", $rssFeed->generateFeed());
        msg("[INFO] Success $fileName.rss");
        return true;
    }
    msg("[ERROR] Failure $fileName.rss - no posts found.");
    return false;
}

function updateIncomeTaxNews(): void
{
    $transformedPosts = [];
    $link = "https://www.incometax.gov.in/iec/foportal/latest-news";
    $html = '';
    $client = HttpClient::create();
    try {
        $response = $client->request('GET', $link);
        if ($response->getStatusCode() === 200) {
            $html = $response->getContent();
            $document = new Document($html);
            $posts = $document->find('div.latestnewssection div.views-row');
            foreach ($posts as $post) {
                $date = trim($post->find('div.up-date')[0]->text());
                $content = trim($post->find('div.gry-ft')[0]->text());
                $transformedPosts[] = [
                    'date' => $date,
                    'title' => substr($content, 0, 500),
                    'desc' => $content,
                    'link' => $link,
                ];
            }
        }
    } catch (\Exception $e) {
    }

    // var_dump($transformedPosts);exit;

    createRssFeed(
        title: 'Income Tax Department - News',
        desc: 'Income Tax Department - News',
        link: 'https://www.incometax.gov.in/iec/foportal/latest-news',
        posts: $transformedPosts,
        fileName: 'itd-news'
    );
}

// inspiration: https://github.com/kskarthik/gstfeed/blob/master/main.ts
function updateGstNews(): void
{
    $transformedPosts = [];
    $json = file_get_contents("https://www.gst.gov.in/fomessage/newsupdates");
    $data = json_decode($json, true);
    // var_dump($data);exit;
    if (!empty($data['data'])) {
        foreach ($data['data'] as $post) {
            $post['id'] = $post['id'] ?? 0;
            if (!empty($post['module'])) {
                $post['title'] = "[{$post['module']}] {$post['title']}";
            }
            // Change format "11/04/2025" -> "2025-04-11"
            $date = DateTime::createFromFormat('d/m/Y', $post['date']);
            $post['date'] =  $date->format('Y-m-d');
            $transformedPosts[] = [
                'date' => $post['date'],
                'title' => $post['title'],
                'desc' => $post['content'],
                'link' => "https://services.gst.gov.in/services/advisoryandreleases/read/{$post['id']}",
            ];
        }
    }

    createRssFeed(
        title: 'GST News & Updates',
        desc: 'News and updates from the GST Portal',
        link: 'https://www.gst.gov.in/',
        posts: $transformedPosts,
        fileName: 'gst-news'
    );
}

// BIOS
function updateSelfLaptopDriverUpdates(): void
{
    $transformedPosts = [];
    $json = shell_exec("curl -s 'https://pcsupport.lenovo.com/in/en/api/v4/downloads/drivers?productId=laptops-and-netbooks%2F5-series%2Fideapad-pro-5-14imh9'   --compressed   -H 'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:140.0) Gecko/20100101 Firefox/140.0'   -H 'Accept: application/json, text/plain, */*'   -H 'Accept-Language: en-US,en;q=0.5'   -H 'Accept-Encoding: gzip, deflate, br, zstd'   -H 'Referer: https://pcsupport.lenovo.com/in/en/products/laptops-and-netbooks/5-series/ideapad-pro-5-14imh9/downloads/driver-list/component?name=BIOS%2FUEFI^&id=5AC6A815-321D-440E-8833-B07A93E0428C'   -H 'x-requested-with: XMLHttpRequest'   -H 'x-requested-timezone: Asia/Kolkata'   -H 'DNT: 1'   -H 'Sec-GPC: 1'   -H 'Sec-Fetch-Dest: empty'   -H 'Sec-Fetch-Mode: cors'   -H 'Sec-Fetch-Site: same-origin'   -H 'Connection: keep-alive'");
    $data = json_decode($json, true);
    if (!empty($data['message']) && ($data['message'] === 'succeed')) {
        foreach ($data['body']['DownloadItems'] as $item) {
            if (str_contains($item['Title'], 'BIOS Update')) {
                $updatedAtEpoch = $item['Updated']['Unix'] ?? 0;
                $timestamp = $updatedAtEpoch / 1000;
                $date = date("Y-m-d", $timestamp);
                $transformedPosts[] = [
                    'date' => $date,
                    'title' => $item['Title'],
                    'desc' => json_encode($item, JSON_PRETTY_PRINT),
                    'link' => 'https://pcsupport.lenovo.com/in/en/api/v4/downloads/drivers?productId=laptops-and-netbooks%2F5-series%2Fideapad-pro-5-14imh9',
                ];
            }
        }
    }

    createRssFeed(
        title: 'IdeaPad Pro 5 14IMH9 - Updates',
        desc: 'IdeaPad Pro 5 14IMH9 - Updates',
        link: 'https://pcsupport.lenovo.com/in/en/products/laptops-and-netbooks/5-series/ideapad-pro-5-14imh9/83d2/83d2001gin/downloads/driver-list',
        posts: $transformedPosts,
        fileName: 'lenovo-laptop-driver-updates'
    );
}

function updateVpsBenchmarks(): void
{
    $transformedPosts = [];
    $html = file_get_contents('https://www.vpsbenchmarks.com/announcements');
    $doc = new Document($html);
    $news = $doc->find('div.announcement-container');
    foreach ($news as $article) {
        $transformedPosts[] = [
            'date' => $article->find('em')[0]->text(),
            'title' => $article->find('span.announcement-title')[0]->text(),
            'desc' => $article->find('.announcement-body')[0]->text(),
            'link' => 'https://www.vpsbenchmarks.com' . $article->find('span.announcement-title a')[0]->attr('href'),
        ];
    }

    createRssFeed(
        title: 'vpsbenchmarks.com Announcements',
        desc: 'vpsbenchmarks.com Announcements',
        link: 'https://www.vpsbenchmarks.com/announcements',
        posts: $transformedPosts,
        fileName: 'vpsbenchmarks-com-announcements'
    );
}

updateIncomeTaxNews();
updateGstNews();
updateSelfLaptopDriverUpdates();
updateVpsBenchmarks();
