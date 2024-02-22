<?php

namespace SV\StandardLib;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;
use XF\Mvc\Entity\Structure;
use XF\Service\AbstractService;
use function class_exists;
use function str_replace;
use function strpos;

class Helper
{
    /**
     * Private constructor, use statically.
     */
    private function __construct() { }

    public static function repo(): \SV\StandardLib\Repository\Helper
    {
        /** @var \SV\StandardLib\Repository\Helper $repo */
        $repo = \XF::repository('SV\StandardLib:Helper');

        return $repo;
    }

    public static function perms(): \SV\StandardLib\Repository\Permissions
    {
        /** @var \SV\StandardLib\Repository\Permissions $repo */
        $repo = \XF::repository('SV\StandardLib:Permissions');

        return $repo;
    }

    /**
     * @param string     $addonId
     * @param string|int $targetVersion
     * @param string     $operator
     * @return bool
     */
    public static function isAddOnActive(string $addonId, $targetVersion = null, string $operator = '>='): bool
    {
        return self::repo()->hasDesiredAddOnVersion($addonId, $targetVersion, $operator);
    }

    /**
     * @template T
     * @param class-string<T> $classname
     * @param        ...$args
     * @return T
     */
    public static function newExtendedClass(string $classname, ...$args)
    {
        $classname = \XF::extendClass($classname);
        /** @var T $obj */
        $obj = new $classname(...$args);
        return $obj;
    }

    /**
     * @param string|null $entityName
     * @return Structure|null
     * @noinspection PhpMissingParamTypeInspection
     */
    public static function getEntityStructure($entityName)
    {
        if ($entityName === null || $entityName === '')
        {
            return null;
        }

        $class = \XF::stringToClass($entityName, '%s\Entity\%s');
        // detect invalid content type+entity configuration; Vault Wiki appears a major offender
        // note; SV/TitleEditHistory can also trigger this
        try
        {
            if (@!class_exists($class))
            {
                return null;
            }
            return \XF::em()->getEntityStructure($entityName);
        }
        catch(\Throwable $e)
        {
            return null;
        }
    }

    /**
     * @template T of Entity
     * @param class-string<T> $identifier
     * @return T
     */
    public static function createEntity(string $identifier)
    {
        /** @var T $e */
        $e = \XF::em()->create($identifier);
        return $e;
    }

    /**
     * @template T of Finder
     * @param class-string<T> $identifier
     * @return T
     */
    public static function finder(string $identifier)
    {
        // XF2.2 entity cache key is on the short name, not the class name. So map to the expected thing
        if (\XF::$versionId < 2030000 && strpos($identifier, ':') === false)
        {
            $identifier = str_replace('\\Finder\\', ':', $identifier);
        }

        /** @var T $finder */
        $finder = \XF::app()->finder($identifier);

        return $finder;
    }


    /**
     * @template T of Repository
     * @param class-string<T> $identifier
     * @return T
     */
    public static function repository(string $identifier)
    {
        // XF2.2 repository cache key is on the short name, not the class name. So map to the expected thing
        if (\XF::$versionId < 2030000 && strpos($identifier, ':') === false)
        {
            $identifier = str_replace('\\Repository\\', ':', $identifier);
        }

        /** @var T $repo */
        $repo = \XF::repository($identifier);

        return $repo;
    }

    /**
     * @template T of Entity
     * @param class-string<T> $identifier
     * @param int|string      $id
     * @param array           $with
     * @return T|null
     */
    public static function find(string $identifier, $id, array $with = [])
    {
        // XF2.2 entity cache key is on the short name, not the class name. So map to the expected thing
        if (\XF::$versionId < 2030000 && strpos($identifier, ':') === false)
        {
            $identifier = str_replace('\\Entity\\', ':', $identifier);
        }

        /** @var T|null $entity */
        $entity = \XF::app()->find($identifier, $id, $with);

        return $entity;
    }

    /**
     * @template T of Entity
     * @param class-string<T> $identifier
     * @param int             $id
     * @return T|null
     */
    public static function findCached(string $identifier, int $id)
    {
        // XF2.2 entity cache key is on the short name, not the class name. So map to the expected thing
        if (\XF::$versionId < 2030000 && strpos($identifier, ':') === false)
        {
            $identifier = str_replace('\\Entity\\', ':', $identifier);
        }

        $entity = \XF::em()->findCached($identifier, $id);
        if (!$entity)
        {
            return null;
        }

        /** @var T $entity */
        return $entity;
    }

    /**
     * @template T of AbstractService
     * @param class-string<T> $identifier
     * @return T
     */
    public static function service(string $identifier, ...$arguments)
    {
        /** @var T $service */
        $service = \XF::service($identifier, ...$arguments);

        return $service;
    }
}