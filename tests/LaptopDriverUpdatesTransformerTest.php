<?php

$json = file_get_contents('laptop-driver-updates.json');
$data = json_decode($json, true);

$transformedPosts = [];

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

var_dump($transformedPosts);
