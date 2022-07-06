<?php

namespace App;
use Symfony\Component\DomCrawler\Crawler; // Imports the Symfony DomCrawler class.

require '.\vendor\autoload.php'; // Imports the autoload file.
require 'ScrapeHelper.php'; // Imports the ScrapeHelper class


class Scrape
{
  /**
   * @var array|null
   */
  private  $products = array(); 
  /**    
   * The main function
   */
  public function run(): void 
  {
    $pages = [1,2,3]; // Initialises an array for storing the webpage/pagination numbers

    foreach($pages as $page) // Iterates through page numbers
    {
      $url = 'https://www.magpiehq.com/developer-challenge/smartphones/?page=' . $page; // Sets the URL to fetch.
      $document = ScrapeHelper::fetchDocument($url); // Fetches the DOMElement objects.
      $listProducts = $document->filter('div.product'); // Filters all the div elements with a CSS class 
                                                        // product. They  contain the targeted products.
      $this->pageProducts($listProducts); // Passes the filtered div elements to the pageProducts  
                                          // function,which fetches the required product data, and  
                                          // stores it in the $products array.
    }
    
    // Converts the $products array to Json using json_encode function, formats the presentation with JSON_PRETTY_PRINT, and unescapes the slashes of the the image URLS.
    file_put_contents('output.json', json_encode($this->products, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
  }

   /**
   * @param object  $listProducts
   *   
   * Fetches the required product data into the $products array
   */
  public function pageProducts($listProducts) // .
  {                                            
    foreach ($listProducts as $currentProduct) // Loops through the product div elements 
    { 
      $element = new Crawler($currentProduct); // Sets a Crawler instance on each product div elements.
      $colour_varients = $element->filter('div > span')->count(); // Counts the coulor varients.
      for ($i = 0; $i < $colour_varients; $i++) {  // Loops through the coulor varients. 
        $capacity = $element->filter('span.product-capacity')->text(); // Gets the product capacity.
        $availability = $element->filter('div.text-center.my-4')->text(); // Gets the availability status.
        $availabilityText = $element->filter('div.text-center.my-4')->first()->text(); // Availability.
        $shippingText = $element->filter('div.text-center.my-4')->last()->text(); // Shipping text

        $product = [ // Create a procuct array and populate it.

           // Uses the title function to get the title of the product.
          "title"  => $this->title($element, $capacity),
          "price"  => ltrim($element->filter('div.block')->text(), '£'), // Filters the price and 
                                                                         // remove the '£' sign.
          "imageUrl"  => $this->imageUrl($element), // Gets the image URL from the imageUrl function. 
          "capacityMB"  => $this->capacity($capacity), // Gets the capacity from the capacity function.

          // Gets the coulor by filtering the data-coulor attribute of the current coulor varient.
          "colour"  => $element->filter('div > span')->eq($i)->attr('data-colour'),
          "availabilityText"  => $availabilityText,

          // Sets the availability status by ascertaining if the product is in stock.
          "isAvailable"  => strstr($availability, "In Stock") ? true : false,   
          // Get the shipping text if it exists.       
          "shippingText"  => $availabilityText == $shippingText ? " " : $shippingText, 
          // Get the shipping date if it exists.
           "shippingDate"  => $this->shipingDate($shippingText),  
        ];  

         // Checks if the product varient exists in the $products array, using the checkExists function.
        $exists = $this->checkExists($this->title($element, $capacity), $element->filter('div > span')->eq($i)->attr('data-colour'));
        if(!$exists) {
          array_push($this->products, $product); // Push the $product array into the $products array if not already existing. 
        }
      }     
    }
  }

  /**
   * Extracts the Date substring, using regular expressions.
   * 
   * @param string $shippingText
   * 
   * @return The shiping Date in YYYY-MM-DD format.
   */
  public function shipingDate($shippingText)
  {
    $find = array("th","st", "rd", "nd");    // Remove ordinal numbers if existing
    $text = str_replace($find, '', $shippingText);
    if(strstr($text, "Deliver")) 
    {
      //Search for the date in YYYY-MM-DD format 
      preg_match('/(19|20)\d\d[\-\/.](0[1-9]|1[012])[\-\/.](0[1-9]|[12][0-9]|3[01])/', $text, $matches);
        if(!empty( $matches )) {
          return $matches[0];
        } else{
          
          preg_match('/(\d{1,2}) (\w+) (\d{4})/', $text, $matches); // Search for the short date.
          if(!empty( $matches )) {
            $day = $matches[1] < 10 ? 0 . $matches[1] : $matches[1]; // Format the day value
            $year = $matches[3];      
            
            $month_names = array( // Array of short names for months.
              "Jan",
              "Feb",
              "Mar",
              "Apr",
              "May",
              "Jun",
              "Jul",
              "Aug",
              "Sep",
              "Oct",
              "Nov",
              "Dec"
            );     

          $month = array_search($matches[2], $month_names) + 1; // Convert the month name to number 
          $month = strlen($month) < 2 ? '0'.$month : $month;  // Prepend a 0 if month > 10.
          $results = $year . '-' . $month . '-' . $day;        
          return $results;
        }
      }
    } 
  }

   /**
   * @param string $title
   * @param string  $colour
   * 
   * @return The existance status.
   * 
   * Checks the existance of a product varient by comparing title and the colour of the products
   */
  public function checkExists($title, $colour) 
  {                                                 
    $exist = false; // Initialises the existance status.
    foreach($this->products as $product) // Loops thrugh the items in the products array.
    {

      // Compares the title and the colour of the current product with 
      // that of the items in the $products array.
      if(($product['title'] == $title) && $product['colour'] == $colour)  
      {
        $exist = true; // Changes the existance status, if a match is found.    
      }
    }

    return $exist; 
  }

  /**
   * @param string $capacity
   * @param object  $element
   * 
   * @return The title of the product.
   */
  public function title($element, $capacity) 
  {
    $name = $element->filter('span.product-name')->text(); // Filters the name of the product.
    return $name . " " . $capacity; // Concartinates the name and the capacity of the product.
  }

   /**
   * @param string $capacity
   * 
   * @return The capacity in MB.
   */
  public function capacity($capacity) 
  {
    // Checks if the unit is 'GB'.
    if(substr($capacity, -2) == "GB")
    {
      $return = 1000 * intval(rtrim($capacity,"GB")); // Removes unit and converts to the value to MB 
    } else {                                          // if needed.
      $return = intval(rtrim($capacity,"MB"));
    }
    return $return; 
  }

  /**
   * @param object $element
   * 
   * @return The FQDN path of the product image.
   */
  public function imageUrl($element)  
  {
    // Filters absolute URL and remove '..'.
    $url = substr($element->filter('div > img')->first()->attr('src'),2); 
    return "https://www.magpiehq.com/developer-challenge" . $url;
  }
}

$scrape = new Scrape();
$scrape->run();
