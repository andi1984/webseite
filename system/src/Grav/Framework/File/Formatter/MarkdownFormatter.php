<?php
/**
 * @package    Grav\Framework\Formatter
 *
 * @copyright  Copyright (C) 2015 - 2018 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */

namespace Grav\Framework\Formatter;

/**
 * Class MarkdownFormatter
 * @package Grav\Framework\Formatter
 */
class MarkdownFormatter implements FormatterInterface
{
    /** @var array */
    private $config;
    /** @var FormatterInterface */
    private $headerFormatter;

    public function __construct(array $config = [], FormatterInterface $headerFormatter = null)
    {
        $this->config = $config + [
            'file_extension' => '.md',
            'header' => 'header',
            'body' => 'markdown',
            'raw' => 'frontmatter',
            'formatter' => ['inline' => 20]
        ];

        $this->headerFormatter = $headerFormatter ?: new YamlFormatter($this->config['formatter']);
    }

    /**
     * {@inheritdoc}
     */
    public function getFileExtension()
    {
        return $this->config['file_extension'];
    }

    /**
     * {@inheritdoc}
     */
    public function encode($data)
    {
        $headerVar = $this->config['header'];
        $bodyVar = $this->config['body'];

        $header = isset($data[$headerVar]) ? (array) $data[$headerVar] : [];
        $body = isset($data[$bodyVar]) ? (string) $data[$bodyVar] : '';

        // Create Markdown file with YAML header.
        $encoded = '';
        if ($header) {
            $encoded = "---\n" . trim($this->headerFormatter->encode($data['header'])) . "\n---\n\n";
        }
        $encoded .= $body;

        // Normalize line endings to Unix style.
        $encoded = preg_replace("/(\r\n|\r)/", "\n", $encoded);

        return $encoded;
    }

    /**
     * {@inheritdoc}
     */
    public function decode($data)
    {
        $headerVar = $this->config['header'];
        $bodyVar = $this->config['body'];
        $rawVar = $this->config['raw'];

        $content = [
            $headerVar => [],
            $bodyVar => ''
        ];

        $headerRegex = "/^---\n(.+?)\n---\n{0,}(.*)$/uis";

        // Normalize line endings to Unix style.
        $data = preg_replace("/(\r\n|\r)/", "\n", $data);

        // Parse header.
        preg_match($headerRegex, ltrim($data), $matches);
        if(empty($matches)) {
            $content[$bodyVar] = $data;
        } else {
            // Normalize frontmatter.
            $frontmatter = preg_replace("/\n\t/", "\n    ", $matches[1]);
            if ($rawVar) {
                $content[$rawVar] = $frontmatter;
            }
            $content[$headerVar] = $this->headerFormatter->decode($frontmatter);
            $content[$bodyVar] = $matches[2];
        }

        return $content;
    }
}
