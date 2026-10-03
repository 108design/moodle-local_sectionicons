<?php
namespace local_sectionicons\local;

use invalid_parameter_exception;

/** Strict sanitizer for small decorative SVG images. */
final class svg_sanitizer {
    private const ELEMENTS = [
        'svg', 'g', 'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon', 'title', 'desc',
        'defs', 'lineargradient', 'radialgradient', 'stop', 'clippath', 'mask', 'use',
    ];

    private const ATTRIBUTES = [
        'xmlns', 'viewbox', 'preserveaspectratio', 'fill', 'fill-opacity', 'fill-rule', 'stroke',
        'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'stroke-opacity', 'stroke-dasharray',
        'opacity', 'transform', 'd', 'x', 'y', 'x1', 'x2', 'y1', 'y2', 'cx', 'cy', 'r', 'rx', 'ry',
        'width', 'height', 'points', 'offset', 'stop-color', 'stop-opacity', 'gradientunits',
        'gradienttransform', 'spreadmethod', 'id', 'clip-path', 'mask', 'href', 'xlink:href',
    ];

    public static function sanitise(string $content): string {
        if ($content === '' || strlen($content) > image_validator::MAX_BYTES
                || preg_match('/<!DOCTYPE|<!ENTITY|<\?xml-stylesheet/i', $content)) {
            throw new invalid_parameter_exception(get_string('invalidimage', 'local_sectionicons'));
        }
        $previous = libxml_use_internal_errors(true);
        $document = new \DOMDocument();
        $loaded = $document->loadXML($content, LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $root = $document->documentElement;
        if (!$loaded || !$root || strtolower($root->localName) !== 'svg' || $document->doctype) {
            throw new invalid_parameter_exception(get_string('invalidimage', 'local_sectionicons'));
        }
        self::clean_node($root);
        $root->setAttribute('xmlns', 'http://www.w3.org/2000/svg');
        $root->setAttribute('aria-hidden', 'true');
        $root->setAttribute('focusable', 'false');
        $root->removeAttribute('width');
        $root->removeAttribute('height');
        if (!$root->hasAttribute('viewBox') && !$root->hasAttribute('viewbox')) {
            throw new invalid_parameter_exception(get_string('invalidimage', 'local_sectionicons'));
        }
        $result = $document->saveXML($root);
        if ($result === false) {
            throw new invalid_parameter_exception(get_string('invalidimage', 'local_sectionicons'));
        }
        return $result;
    }

    private static function clean_node(\DOMElement $node): void {
        if (!in_array(strtolower($node->localName), self::ELEMENTS, true)
                || ($node->namespaceURI !== null && $node->namespaceURI !== 'http://www.w3.org/2000/svg')) {
            throw new invalid_parameter_exception(get_string('invalidimage', 'local_sectionicons'));
        }
        foreach (iterator_to_array($node->attributes ?? []) as $attribute) {
            $name = strtolower($attribute->nodeName);
            $value = trim($attribute->nodeValue);
            if (str_starts_with($name, 'on') || !in_array($name, self::ATTRIBUTES, true)
                    || !self::safe_value($name, $value)) {
                $node->removeAttributeNode($attribute);
            }
        }
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof \DOMElement) {
                self::clean_node($child);
            } else if (!($child instanceof \DOMText)) {
                $node->removeChild($child);
            }
        }
    }

    private static function safe_value(string $name, string $value): bool {
        if ($name === 'xmlns') {
            return $value === 'http://www.w3.org/2000/svg';
        }
        if ($name === 'href' || $name === 'xlink:href') {
            return preg_match('/^#[A-Za-z_][A-Za-z0-9_.:-]{0,100}$/', $value) === 1;
        }
        if (in_array($name, ['fill', 'stroke', 'clip-path', 'mask'], true) && str_contains($value, 'url(')) {
            return preg_match('/^url\(#[A-Za-z_][A-Za-z0-9_.:-]{0,100}\)$/', $value) === 1;
        }
        return !preg_match('/(?:javascript:|data:|https?:|@import|expression\s*\()/i', $value);
    }
}
