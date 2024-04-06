<?php

namespace SV\StandardLib;

use SV\StandardLib\Repository\Permissions as PermissionsRepo;
use XF\ControllerPlugin\AbstractPlugin;
use XF\Mvc\Controller;
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
        return self::repository(\SV\StandardLib\Repository\Helper::class);
    }

    public static function perms(): PermissionsRepo
    {
        return self::repository(PermissionsRepo::class);
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

    public static function getEntityStructure(?string $entityName): ?Structure
    {
        if ($entityName === null || $entityName === '')
        {
            return null;
        }

        // XF2.2 entity cache key is on the short name, not the class name. So map to the expected thing
        if (\XF::$versionId < 2030000 && strpos($entityName, ':') === false)
        {
            $entityName = str_replace('\\Entity\\', ':', $entityName);
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
        // XF2.2 entity cache key is on the short name, not the class name. So map to the expected thing
        if (\XF::$versionId < 2030000 && strpos($identifier, ':') === false)
        {
            $identifier = str_replace('\\Entity\\', ':', $identifier);
        }

        /** @var T $e */
        $e = \XF::em()->create($identifier);
        return $e;
    }

    /**
     * @template T of Entity
     * @param class-string<T> $identifier
     * @param array $values Values for the columns in the entity, in source encoded form
     * @param array $relations
     * @param int $options Bit field of the \XF\Mvc\Entity\Manager::INSTANTIATE_* options
     * @return T
     */
    public static function instantiateEntity(string $identifier, array $values = [], array $relations = [], int $options = 0)
    {
        // XF2.2 entity cache key is on the short name, not the class name. So map to the expected thing
        if (\XF::$versionId < 2030000 && strpos($identifier, ':') === false)
        {
            $identifier = str_replace('\\Entity\\', ':', $identifier);
        }

        /** @var T $e */
        $e = \XF::em()->instantiateEntity($identifier, $values, $relations, $options);
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
     * @param int|string|array<int|string>      $id
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
     * @param int|string|array<int|string> $id
     * @return T|null
     */
    public static function findCached(string $identifier, $id)
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

    /**
     * @template T of AbstractPlugin
     * @param Controller|AbstractPlugin $controller
     * @param class-string<T> $class
     * @return T
     */
    public static function plugin($controller, string $class)
    {
        /** @var AbstractPlugin $plugin */
        $plugin = $controller->plugin($class);
        return $plugin;
    }
}