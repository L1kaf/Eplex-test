<?php

require "vendor/autoload.php";

function fetchAndParseData($code) {
    // Инициализация запроса
    $ch = curl_init('https://www.autozap.ru/goods');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['code' => $code]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $html = curl_exec($ch);
    curl_close($ch);

    $docs = phpQuery::newDocument($html);

    $data = [];
    $items = [];
    $elements = $docs->find('table tr');

    if (strpos($docs->find('h3')->text(), 'Выберите производителя')) {
        foreach ($elements as $element) {
            // Отделение td от span
            $brand = pq($element)->find('td.producer')->contents()->text();
            preg_match('/^\s*(\w+)/', $brand, $matchesBrand);
            $items = [
                'brand' => $matchesBrand[0],
                'article' => pq($element)->find('td.code')->text(),
                'name' => pq($element)->find('.name')->text(),
                'note' => pq($element)->find('td.com_search')->text(),
                'prices' => 'https://www.autozap.ru'.pq($element)->find('a[id*="goodLnk"]')->attr('href'),
            ];

            // Удаление пустых массивов
            if (!empty($items['note']) || !empty($items['brand']) || !empty($items['article'])) {
                $data[] = $items;
            }
        }
    }
    else {
        // Добавление повторяющихся элементов
        $name = pq($elements)->find('.name .goodlnk')->text();
        $article = trim(pq($elements)->find('td.code')->text());
        $brandText = pq($elements)->find('td.producer')->contents()->text();
        preg_match('/^\s*(\w+)/', $brandText, $matchesBrand);

        foreach ($elements as $element) {
            $price = pq($element)->find('td.price > span')->text();
            preg_match('/(\d+\.\d+)/', $price, $matchesPrice);

            $time = pq($element)->find('td.article')->text();
            preg_match('/^\s*(\w+)/', $time, $matchesTime);


            $items = [
                'name' => pq($element)->find('.name .goodlnk')->text() ?: $name,
                'price' => $matchesPrice[0],
                'article' => pq($element)->find('td.code')->text() ?: $article,
                'brand' => $matchesBrand[0],
                'count' => trim(pq($element)->find('.storehouse-quantity')->text()),
                'time' => $matchesTime[0],
                'id' => pq($element)->find('input[id*="g"]')->attr('value'),
            ];

        if (!empty($items['price']) || !empty($items['count']) || !empty($items['time'])) {
            $data[] = $items;
        }
        }
    }

    $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    file_put_contents('data.json', $jsonData);

    return $data;
}
