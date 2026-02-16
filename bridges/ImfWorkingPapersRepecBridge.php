<?php

declare(strict_types=1);

class ImfWorkingPapersRepecBridge extends BridgeAbstract {

    const NAME = 'IMF Working Papers - RePEc';
    const URI = 'https://ideas.repec.org/s/imf/imfwpa.html';
    const DESCRIPTION = 'Working Papers du FMI via RePEc/IDEAS';
    const MAINTAINER = 'Surmarxisme';

    const PARAMETERS = [
        [
            'limit' => [
                'name' => 'Limite',
                'type' => 'number',
                'exampleValue' => 50,
                'defaultValue' => 50,
            ],
        ]
    ];

    public function collectData() {
        $html = getSimpleHTMLDOM(self::URI);
        
        // Recherche des titres h3 contenant 2026 ou 2025
        $years = $html->find('h3');
        $count = 0;
        $limit = $this->getInput('limit') ?? 50;
        
        foreach ($years as $yearH3) {
            $yearText = $yearH3->plaintext;
            
            // Vérifier si c'est 2026 ou 2025
            if (!preg_match('/(2026|2025)/', $yearText, $match)) {
                continue;
            }
            
            $year = $match[1];
            
            // Récupérer tous les <p> qui suivent ce h3
            $nextSibling = $yearH3->next_sibling();
            
            while ($nextSibling && $nextSibling->tag === 'p' && $count < $limit) {
                $item = [];
                
                // Titre et numéro (dans <strong>)
                $strong = $nextSibling->find('strong', 0);
                if ($strong) {
                    $item['title'] = trim($strong->plaintext);
                }
                
                // Auteurs (dans <em>)
                $em = $nextSibling->find('em', 0);
                if ($em) {
                    $author = trim($em->plaintext);
                    $author = preg_replace('/^by\s+/i', '', $author);
                    $item['author'] = $author;
                }
                
                // Lien (dans le h3 précédent)
                $link = $yearH3->find('a', 0);
                if ($link) {
                    $href = $link->href;
                    if (strpos($href, 'http') !== 0) {
                        $href = 'https://ideas.repec.org' . $href;
                    }
                    $item['uri'] = $href;
                }
                
                // Date (utiliser l'année)
                $item['timestamp'] = strtotime($year . '-01-01');
                
                // Contenu (titre + auteur)
                $content = $item['title'] ?? '';
                if (!empty($item['author'])) {
                    $content .= ' — ' . $item['author'];
                }
                $item['content'] = $content;
                
                // UID unique
                $item['uid'] = $item['uri'] ?? md5($content . $year);
                
                $this->items[] = $item;
                $count++;
                
                if ($count >= $limit) {
                    return;
                }
                
                $nextSibling = $nextSibling->next_sibling();
            }
        }
    }
}
