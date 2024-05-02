<?php
/**
 * @noinspection PhpMissingParamTypeInspection
 * @noinspection PhpMissingReturnTypeInspection
 * @noinspection PhpIllegalPsrClassPathInspection
 * @noinspection PhpMultipleClassDeclarationsInspection
 */
namespace XF\Entity;

interface LinkableInterface
{
    /**
     * @param bool  $canonical
     * @param array $extraParams
     * @param string|null $hash
     * @return string
     */
    public function getContentUrl(bool $canonical = false, array $extraParams = [], $hash = null);

    /**
     * @return string|null
     */
    public function getContentPublicRoute();

    /**
     * @param string $context
     * @return string|\XF\Phrase
     */
    public function getContentTitle(string $context = '');
}