<?php

namespace App\Libraries;

/**
 * Allowlist HTML sanitizer for rich-text paragraph content.
 *
 * Paragraph bodies are authored by (authenticated, semi-trusted) form owners but
 * rendered to anonymous public visitors, so the stored HTML must be cleaned to a
 * safe subset — otherwise a malicious owner could plant stored XSS. Anything not
 * on the allowlist is dropped: disallowed *formatting* tags are unwrapped (their
 * text is kept), while dangerous tags (script/style/iframe/…) are removed whole.
 * All event handlers, styles, and unsafe URL schemes are stripped.
 */
class HtmlSanitizer
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'strike',
        'ol', 'ul', 'li', 'a', 'blockquote', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
    ];

    /** Removed entirely, contents included. */
    private const DROP_TAGS = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'link', 'meta', 'svg', 'math'];

    /** The only class values kept (Quill text-alignment). */
    private const ALLOWED_CLASSES = ['ql-align-center', 'ql-align-right', 'ql-align-justify'];

    public function clean(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        // Wrap so the fragment has a single root; NOIMPLIED/NODEFDTD keep DOM
        // from injecting <html>/<body>/doctype around it.
        $dom->loadHTML('<?xml encoding="UTF-8"><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $root = $dom->documentElement;
        if ($root === null) {
            return '';
        }

        $this->process($root);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $dom->saveHTML($child);
        }

        return trim($out);
    }

    /**
     * Stabilise a node's direct children (drop/unwrap/clean), then recurse.
     */
    private function process(\DOMNode $node): void
    {
        $changed = true;
        while ($changed) {
            $changed = false;

            foreach (iterator_to_array($node->childNodes) as $child) {
                if ($child instanceof \DOMText) {
                    continue;
                }
                if (! ($child instanceof \DOMElement)) {
                    $node->removeChild($child); // comments, PIs, etc.
                    $changed = true;
                    continue;
                }

                $tag = strtolower($child->tagName);

                if (in_array($tag, self::DROP_TAGS, true)) {
                    $node->removeChild($child);
                    $changed = true;
                    continue;
                }

                if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                    // Unwrap: keep the text/children, drop the tag itself.
                    while ($child->firstChild !== null) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                    $changed = true;
                    continue;
                }

                $this->cleanAttributes($child, $tag);
            }
        }

        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof \DOMElement) {
                $this->process($child);
            }
        }
    }

    private function cleanAttributes(\DOMElement $el, string $tag): void
    {
        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->name);

            if ($name === 'class') {
                $kept = array_values(array_intersect(preg_split('/\s+/', trim($attr->value)) ?: [], self::ALLOWED_CLASSES));
                if ($kept === []) {
                    $el->removeAttribute('class');
                } else {
                    $el->setAttribute('class', implode(' ', $kept));
                }
                continue;
            }

            if ($name === 'style') {
                // Keep only a text-align declaration, rebuilt from a fixed set so
                // nothing else in the style string can survive.
                $align = $this->extractTextAlign($attr->value);
                if ($align !== null) {
                    $el->setAttribute('style', 'text-align: ' . $align);
                } else {
                    $el->removeAttribute('style');
                }
                continue;
            }

            if ($tag === 'a' && in_array($name, ['href', 'target', 'rel'], true)) {
                continue; // validated below
            }

            $el->removeAttribute($attr->name);
        }

        if ($tag === 'a') {
            if (! $this->isSafeUrl($el->getAttribute('href'))) {
                $el->removeAttribute('href');
            }
            if ($el->getAttribute('target') !== '') {
                $el->setAttribute('rel', 'noopener noreferrer nofollow');
            }
        }
    }

    /**
     * The text-align keyword in a style string, or null. Used to rebuild a
     * minimal safe `style` attribute (nothing else is preserved).
     */
    private function extractTextAlign(string $style): ?string
    {
        if (preg_match('/text-align\s*:\s*(left|right|center|justify)\b/i', $style, $m) === 1) {
            return strtolower($m[1]);
        }

        return null;
    }

    /**
     * Allow http(s), mailto, and scheme-less (relative) URLs; block javascript:,
     * data:, and any other scheme.
     */
    private function isSafeUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }
        if (preg_match('#^(https?:|mailto:)#i', $url) === 1) {
            return true;
        }
        // Has some other scheme (e.g. javascript:, data:) → reject.
        if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $url) === 1) {
            return false;
        }

        return true; // relative / anchor
    }
}
