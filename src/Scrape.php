<?php

namespace App;
use Symfony\Component\DomCrawler\Crawler;

require '..\vendor\autoload.php';
require 'ScrapeHelper.php';


class Scrape
{
    private  $products = array();

    public function run(): void
    {
        $document = ScrapeHelper::fetchDocument('https://www.magpiehq.com/developer-challenge/smartphones');
        $listProducts = $document->filter('div.product'); 
         
        foreach ($listProducts as $currentProduct)
        { 
            $element = new Crawler($currentProduct);
            // echo var_dump($element);
            // echo "<br/>";
            $capacity = $element->filter('span.product-capacity')->text();
            $availability = $element->filter('div.text-center.my-4')->text();
            $availabilityText = $element->filter('div.text-center.my-4')->first()->text();
            $shippingText = $element->filter('div.text-center.my-4')->last()->text();
            $product = [
                // "title"  => $element->filter('span.product-name')->text()." ".$element->filter('span.product-capacity')->text(),
                // "price"  => $element->filter('div.block')->text(),
                // "imageUrl"  => "https://www.magpiehq.com/developer-challenge" . substr($element->filter('div > img')->first()->attr('src'),2),
                // "capacityMB"  =>substr($capacity, -2) == "GB" ? 1000 * intval(rtrim($capacity,"GB")) : intval(rtrim($capacity,"MB")),
                // "colour"  => $element->filter('div > span')->first()->attr('data-colour'),
                // "availabilityText"  => $availabilityText,
                // "isAvailable"  => strstr($availability, "In Stock") ? true : false,
                // "shippingText"  => $availabilityText == $shippingText ? "Non" : $shippingText,
                //  "shippingDate"  => strstr($shippingText, "Deliver") ? $shippingText : "",  
                "colour"  => $element->filter('div > span')->count(),           
            
            ];    
            array_push($this->products, $product);      
        }
        echo var_dump($this->products);

        //file_put_contents('output.json', json_encode($this->products));
    }
}

$scrape = new Scrape();
$scrape->run();
