<?php

namespace EMS\CommonBundle\Common;

use EMS\CommonBundle\Elasticsearch\Document\EMSSource;

class EMSLink
{
    public const EMSLINK_ASSET_PREFIX = 'ems://asset:';
    /**
     * object|asset.
     *
     * @var string
     */
    private $linkType = 'object';

    /** @var string */
    private $contentType;

    /** @var string */
    private $ouuid;

    /** @var string|null */
    private $query = null;

    /**
     * Regex for searching ems links in content
     * content_type and query can be empty/optional.
     *
     * Example: <a href="ems://object:page:AV44kX4b1tfmVMOaE61u">example</a>
     * link_type => object, content_type => page, ouuid => AV44kX4b1tfmVMOaE61u
     */
    public const PATTERN = '/((?P<src>src="))?ems:\/\/(?P<link_type>.*?):(?:(?P<content_type>([[:alnum:]]|_)*?):)?(?P<ouuid>([[:alnum:]]|-|_)*)(?:\?(?P<query>(?:[^"|\']*)))?/';
    public const SIMPLE_PATTERN = '/(?:(?P<content_type>.*?):)?(?P<ouuid>([[:alnum:]]|-|_)*)/';

    private function __construct()
    {
    }

    public static function fromContentTypeOuuid(string $contentType, string $ouuid): EMSLink
    {
        $link = new self();
        $link->ouuid = $ouuid;
        $link->contentType = $contentType;

        return $link;
    }

    public function isValid(): bool
    {
        return null !== $this->contentType && null !== $this->ouuid;
    }

    public static function fromText(string $text): EMSLink
    {
        $pattern = 'ems://' === \substr($text, 0, 6) ? self::PATTERN : self::SIMPLE_PATTERN;
        \preg_match($pattern, $text, $match);

        return self::fromMatch($match);
    }

    /**
     * @param array{ouuid?: string, link_type?: string, content_type?: string, query?: string} $match
     */
    public static function fromMatch(array $match): EMSLink
    {
        $link = new self();

        if (!isset($match['ouuid'])) {
            throw new \InvalidArgumentException(\sprintf('ouuid is required! (%s)', \implode(',', $match)));
        }

        $link->ouuid = $match['ouuid'];
        $link->linkType = $match['link_type'] ?? 'object';

        if (isset($match['content_type']) && !empty($match['content_type'])) {
            $link->contentType = $match['content_type'];
        } elseif (isset($match['link_type']) && !empty($match['link_type'])) {
            $link->contentType = $match['link_type'];
        }

        if (isset($match['query']) && !empty($match['query'])) {
            $link->query = \html_entity_decode($match['query']);
        }

        return $link;
    }

    /**
     * @param array{_id: string, _type?: string, _source: array} $document
     */
    public static function fromDocument(array $document): EMSLink
    {
        $link = new self();
        $link->ouuid = $document['_id'];

        $contentType = $document['_source'][EMSSource::FIELD_CONTENT_TYPE] ?? null;
        if (null == $contentType) {
            $contentType = $document['_type'] ?? null;
            @\trigger_error(\sprintf('The field %s is missing in the document %s', EMSSource::FIELD_CONTENT_TYPE, $link->getEmsId()), E_USER_DEPRECATED);
        }
        if (null == $contentType) {
            throw new \RuntimeException(\sprintf('Unable to determine the content type for document %s', $link->ouuid));
        }
        $link->contentType = $contentType;

        return $link;
    }

    public function __toString(): string
    {
        return \vsprintf('ems://%s:%s%s%s', [
            $this->linkType,
            $this->contentType ? $this->contentType.':' : '',
            $this->ouuid,
            $this->query ? '?'.$this->query : '',
        ]);
    }

    public function getLinkType(): string
    {
        return $this->linkType;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getOuuid(): string
    {
        return $this->ouuid;
    }

    /**
     * @return array<string|array>
     */
    public function getQuery(): array
    {
        if (null == $this->query) {
            return [];
        }
        \parse_str($this->query, $output);

        return $output;
    }

    public function hasContentType(): bool
    {
        return null !== $this->contentType;
    }

    public function getEmsId(): string
    {
        return \sprintf('%s:%s', $this->contentType, $this->ouuid);
    }
}
