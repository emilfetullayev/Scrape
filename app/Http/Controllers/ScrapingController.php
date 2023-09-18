<?php

namespace App\Http\Controllers;

use Goutte\Client;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\HtmlParser;
use Illuminate\Http\Request;

class ScrapingController extends Controller
{
    public function trendyol()
    {
        try {
            $result = [];
            $httpClient = $this->initializeHttpClient();
            $response = $httpClient->get('https://www.trendyol.com/sr?wc=103946&sst=MOST_FAVOURITE');

            if (!$response->successful()) {
                return response()->json(["error" => "The operation failed."], 500);
            }

            $htmlContent = $response->body();
            $crawler = new Crawler($htmlContent);
            $widgetProducts = $crawler->filter('.p-card-wrppr');

            $widgetProducts->each(function ($widgetProduct) use (&$result, $httpClient) {
                $links = $widgetProduct->filter('a');
                $links->each(function ($link) use (&$result, $httpClient) {
                    $url = $link->attr('href');
                    $productUrl = 'https://www.trendyol.com/' . $url;
                    $productData = $this->scrapeProductData($httpClient, $productUrl);
                    if ($productData) {
                        $result[] = $productData;
                    }
                });
            });

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(["error" => $e->getMessage()], 500);
        }
    }

    public function defacto()
    {
        try {
            $result = [];
            $httpClient = $this->initializeHttpClient();
            $response = $httpClient->get('https://www.defacto.com.tr/erkek-giyim');

            if (!$response->successful()) {
                return response()->json(["error" => "The operation failed."], 500);
            }

            $htmlContent = $response->body();
            $crawler = new Crawler($htmlContent);
            $widgetProducts = $crawler->filter('.catalog-products__item a');

            $widgetProducts->each(function ($link) use (&$result, $httpClient) {
                if (count($result) >= 15) {
                    return false;
                }

                $url = $link->attr('href');
                $productUrl = 'https://www.defacto.com.tr' . $url;
                $productData = $this->scrapeProductData($httpClient, $productUrl);
                if ($productData) {
                    $result[] = $productData;
                }
            });

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(["error" => $e->getMessage()], 500);
        }
    }

    private function initializeHttpClient()
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36';
        return Http::withHeaders(['User-Agent' => $userAgent]);
    }

    private function scrapeProductData($httpClient, $productUrl)
    {
        $productResponse = $httpClient->get($productUrl);

        if ($productResponse->successful()) {
            $pageContent = $productResponse->body();
            $crawler = new Crawler($pageContent);
            $pageTitle = $crawler->filter('title')->text();
            $price = $crawler->filter('.prc-dsc, .product-card__price--new')->text();
            $images = $crawler->filter('img.swiper-lazy')->each(function ($node) {
                return $node->attr('src');
            });

            return [
                'pageTitle' => $pageTitle,
                'images' => $images,
                'price' => $price,
            ];
        }

        return null;
    }
}
