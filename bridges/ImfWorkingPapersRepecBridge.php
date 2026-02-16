<?php

declare(strict_types=1);

class ImfWorkingPapersRepecBridge extends XPathAbstract {

    const NAME = 'IMF Working Papers - RePEc';
    const URI = 'https://ideas.repec.org/s/imf/imfwpa.html';
    const DESCRIPTION = 'Working Papers du FMI via RePEc/IDEAS';
    const MAINTAINER = 'Surmarxisme';

    const PARAMETERS = [
        [
            'year' => [
                'name' => 'Année',
                'type' => 'text',
                'exampleValue' => '2026',
                'title' => 'Filtrer par année (ex: 2026). Laisser vide pour 2025+2026',
            ],
            'limit' => [
                'name' => 'Limite',
                'type' => 'number',
                'exampleValue' => 50,
                'defaultValue' => 50,
            ],
        ]
    ];

    // Source HTML
    const FEED_SOURCE_URL = 'https://ideas.repec.org/s/imf/imfwpa.html';

    // Items = <p> après les h3 2025/2026
    const XPATH_EXPRESSION_ITEM = "//h3[contains(., '2026') or contains(., '2025')]/following-sibling::p[position() <= 50]";

    // Titre (numéro + titre)
    const XPATH_EXPRESSION_ITEM_TITLE = ".//strong/text()";

    // Auteurs
    const XPATH_EXPRESSION_ITEM_AUTHOR = ".//em/text()";

    // Lien
    const XPATH_EXPRESSION_ITEM_URI = "./preceding-sibling::h3[1]/a/@href";

    // Date (année, on la transformera dans formatItemTimestamp)
    const XPATH_EXPRESSION_ITEM_TIMESTAMP = "./preceding-sibling::h3[1]/text()";

    // Description: on réutilise le titre + auteurs
    const XPATH_EXPRESSION_ITEM_CONTENT = ".//strong/text()";

    public function formatItemUri($uri) {
        $uri = trim($uri);
        if ($uri === '') {
            return '';
        }
        if (str_starts_with($uri, '/')) {
            return 'https://ideas.repec.org' . $uri;
        }
        if (!preg_match('~^https?://~', $uri)) {
            return 'https://ideas.repec.org' . $uri;
        }
        return $uri;
    }

    public function formatItemAuthor($author) {
        $author = trim($author);
        return preg_replace('~^by\s+~i', '', $author);
    }

    public function formatItemTimestamp($text) {
        $text = is_array($text) ? implode(' ', $text) : (string)$text;
        if (preg_match('~(20[0-9]{2})~', $text, $m)) {
            $year = $m[1];
        } else {
            $year = date('Y');
        }
        return strtotime($year . '-01-01');
    }

    public function formatItemContent($contentValues, $itemValues) {
        $title = is_array($contentValues) ? implode(' ', $contentValues) : (string)$contentValues;
        $author = $itemValues['author'] ?? '';
        $author = $this->formatItemAuthor($author);
        if ($author !== '') {
            return $title . ' — ' . $author;
        }
        return $title;
    }
}
