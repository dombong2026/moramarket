<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;

final class SafeHtml
{
    private const TAGS = [
        'p','br','strong','b','em','i','u','h2','h3','h4','ul','ol','li',
        'blockquote','a','code','pre','hr',
    ];

    public function clean(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $old = libxml_use_internal_errors(true);
        try {
            $dom->loadHTML('<?xml encoding="utf-8"?><div id="mm-root">'.$html.'</div>',
                LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($old);
        }
        $root = $dom->getElementById('mm-root');
        if (! $root) {
            return '';
        }

        $this->filter($root);
        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $dom->saveHTML($child);
        }
        return $output;
    }

    private function filter(DOMNode $parent): void
    {
        for ($node = $parent->firstChild; $node;) {
            $next = $node->nextSibling;
            if ($node instanceof DOMElement) {
                $tag = strtolower($node->tagName);
                if (in_array($tag, [
                    'script','style','iframe','object','embed','svg','math','form',
                    'input','button','textarea','select','video','audio','img','template',
                    'link','meta','base','noscript',
                ], true)) {
                    $parent->removeChild($node);
                    $node = $next;
                    continue;
                }

                $href = $tag === 'a' ? trim($node->getAttribute('href')) : '';
                $this->filter($node);

                if (! in_array($tag, self::TAGS, true)) {
                    while ($node->firstChild) {
                        $parent->insertBefore($node->firstChild, $node);
                    }
                    $parent->removeChild($node);
                    $node = $next;
                    continue;
                }

                foreach (iterator_to_array($node->attributes) as $attribute) {
                    $node->removeAttributeNode($attribute);
                }

                if ($tag === 'a') {
                    if ($this->allowedHref($href)) {
                        $node->setAttribute('href', $href);
                        $node->setAttribute('rel', 'nofollow noopener noreferrer');
                    }
                }
            } elseif ($node->nodeType !== XML_TEXT_NODE) {
                $parent->removeChild($node);
            }

            $node = $next;
        }
    }

    private function allowedHref(string $href): bool
    {
        if ($href === '' || str_contains($href, '\\') || preg_match('/[\x00-\x20\x7f]/', $href)) {
            return false;
        }
        if (str_starts_with($href, '/') && ! str_starts_with($href, '//')) {
            return true;
        }
        if (! filter_var($href, FILTER_VALIDATE_URL)) {
            return false;
        }
        return in_array(strtolower((string) parse_url($href, PHP_URL_SCHEME)), ['https','http'], true);
    }
}
