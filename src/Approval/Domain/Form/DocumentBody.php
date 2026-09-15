<?php

declare(strict_types=1);

namespace Nexia\Approval\Domain\Form;

use Stringable;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * A sanitized rich-text approval document body.
 */
final readonly class DocumentBody implements Stringable
{
    private function __construct(private string $html) {}

    public static function fromHtml(string $html): self
    {
        return new self(self::sanitizer()->sanitize($html));
    }

    public function html(): string
    {
        return $this->html;
    }

    public function assertNotEmpty(): self
    {
        $text = trim(html_entity_decode(strip_tags($this->html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $text = trim(str_replace("\xC2\xA0", ' ', $text));

        if ($text === '') {
            throw FormSchemaException::emptyBody();
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->html;
    }

    private static function sanitizer(): HtmlSanitizer
    {
        $config = (new HtmlSanitizerConfig)
            ->allowElement('p', ['class', 'style'])
            ->allowElement('br')
            ->allowElement('span', ['class', 'style'])
            ->allowElement('strong', ['class'])
            ->allowElement('b', ['class'])
            ->allowElement('em', ['class'])
            ->allowElement('i', ['class'])
            ->allowElement('u', ['class'])
            ->allowElement('s', ['class'])
            ->allowElement('h1', ['class', 'style'])
            ->allowElement('h2', ['class', 'style'])
            ->allowElement('h3', ['class', 'style'])
            ->allowElement('h4', ['class', 'style'])
            ->allowElement('ul', ['class'])
            ->allowElement('ol', ['class'])
            ->allowElement('li', ['class'])
            ->allowElement('blockquote', ['class'])
            ->allowElement('a', ['href', 'target', 'rel', 'class'])
            ->allowElement('img', ['src', 'alt', 'width', 'height', 'class'])
            ->allowElement('table', ['style', 'class'])
            ->allowElement('thead', ['class'])
            ->allowElement('tbody', ['class'])
            ->allowElement('tfoot', ['class'])
            ->allowElement('tr', ['class'])
            ->allowElement('th', ['style', 'colspan', 'rowspan', 'class'])
            ->allowElement('td', ['style', 'colspan', 'rowspan', 'class'])
            ->allowElement('colgroup', ['class'])
            ->allowElement('col', ['style', 'class'])
            ->allowLinkSchemes(['https', 'http', 'mailto'])
            ->allowMediaSchemes(['https', 'http', 'data']);

        return new HtmlSanitizer($config);
    }
}
