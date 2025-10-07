<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use DOMDocument;
use DOMXPath;
use Exception;

class LinkPreviewController extends Controller
{
    public function getPreview(Request $request)
    {
        $url = $request->query('url');
        
        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            return response()->json([
                'error' => 'URL invalide ou manquante'
            ], 400);
        }

        try {
            $client = new Client([
                'timeout' => 10,
                'verify' => false 
            ]);
            
            $response = $client->get($url);
            $html = (string) $response->getBody();
            
            libxml_use_internal_errors(true); 
            $doc = new DOMDocument();
            $doc->loadHTML($html);
            libxml_clear_errors();
            
            $xpath = new DOMXPath($doc);
            
            $data = [
                'title' => $this->getMetaContent($xpath, 'og:title') ?? 
                          $this->getMetaContent($xpath, 'twitter:title') ??
                          $this->getNodeContent($xpath, '//title') ??
                          $this->getMetaContent($xpath, 'title') ?? '',
                          
                'description' => $this->getMetaContent($xpath, 'og:description') ??
                                $this->getMetaContent($xpath, 'twitter:description') ??
                                $this->getMetaContent($xpath, 'description') ?? '',
                                
                'image' => $this->getMetaContent($xpath, 'og:image') ??
                          $this->getMetaContent($xpath, 'twitter:image') ?? null
            ];
            
            $data = array_map(function($value) {
                return $value ? trim(strip_tags($value)) : null;
            }, $data);
            
            return response()->json($data);
            
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Impossible de récupérer les informations du lien',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    private function getMetaContent(DOMXPath $xpath, string $property)
    {
        $meta = $xpath->query("//meta[@property='$property']")->item(0) ??
                $xpath->query("//meta[@name='$property']")->item(0);
                
        return $meta ? $meta->getAttribute('content') : null;
    }
    
    private function getNodeContent(DOMXPath $xpath, string $query)
    {
        $node = $xpath->query($query)->item(0);
        return $node ? $node->nodeValue : null;
    }
}