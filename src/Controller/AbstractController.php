<?php

declare(strict_types=1);

namespace App\Controller;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

abstract class AbstractController extends SymfonyAbstractController
{
    /**
     * @template T of object
     *
     * @param class-string<T> $dtoClass
     *
     * @return T
     */
    protected function queryDto(Request $request, string $dtoClass): object
    {
        return $this->mapDto($dtoClass, $request->query->all());
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $dtoClass
     *
     * @return T
     */
    protected function bodyDto(Request $request, string $dtoClass): object
    {
        $data = $request->toArray();

        if ($data !== [] && array_is_list($data)) {
            throw new BadRequestHttpException('The request body must be a JSON object.');
        }

        return $this->mapDto($dtoClass, $data);
    }

    protected function dtoResponse(
        object|array $dto,
        int $status = JsonResponse::HTTP_OK,
        array $headers = [],
    ): JsonResponse {
        return new JsonResponse($dto, $status, $headers);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $dtoClass
     * @param array<string, mixed> $data
     *
     * @return T
     */
    private function mapDto(string $dtoClass, array $data): object
    {
        $reflection = new ReflectionClass($dtoClass);
        $constructor = $reflection->getConstructor();

        if (!$reflection->isInstantiable() || $constructor === null) {
            throw new \LogicException(sprintf('DTO "%s" must be instantiable and have a constructor.', $dtoClass));
        }

        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();
            if (!array_key_exists($name, $data)) {
                if ($parameter->isDefaultValueAvailable()) {
                    continue;
                }

                throw new BadRequestHttpException(sprintf('Missing required field "%s".', $name));
            }

            $arguments[$name] = $this->convertValue($data[$name], $parameter);
        }

        return $reflection->newInstanceArgs($arguments);
    }

    private function convertValue(mixed $value, ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();
        if (!$type instanceof ReflectionNamedType) {
            throw new \LogicException(sprintf('DTO field "%s" must use a named type.', $parameter->getName()));
        }

        if ($value === null) {
            if ($type->allowsNull()) {
                return null;
            }

            throw new BadRequestHttpException(sprintf('Field "%s" cannot be null.', $parameter->getName()));
        }

        if (!$type->isBuiltin()) {
            throw new \LogicException(sprintf('DTO field "%s" must use a built-in type.', $parameter->getName()));
        }

        return match ($type->getName()) {
            'string' => $this->stringValue($value, $parameter),
            'int' => $this->integerValue($value, $parameter),
            'float' => $this->floatValue($value, $parameter),
            'bool' => $this->booleanValue($value, $parameter),
            'array' => is_array($value) ? $value : throw $this->invalidField($parameter),
            'mixed' => $value,
            default => throw new \LogicException(sprintf(
                'DTO field "%s" uses unsupported type "%s".',
                $parameter->getName(),
                $type->getName(),
            )),
        };
    }

    private function stringValue(mixed $value, ReflectionParameter $parameter): string
    {
        if (!is_string($value) && !is_int($value) && !is_float($value) && !is_bool($value)) {
            throw $this->invalidField($parameter);
        }

        return (string) $value;
    }

    private function integerValue(mixed $value, ReflectionParameter $parameter): int
    {
        $converted = filter_var($value, FILTER_VALIDATE_INT);
        if ($converted === false) {
            throw $this->invalidField($parameter);
        }

        return $converted;
    }

    private function floatValue(mixed $value, ReflectionParameter $parameter): float
    {
        if (!is_numeric($value)) {
            throw $this->invalidField($parameter);
        }

        return (float) $value;
    }

    private function booleanValue(mixed $value, ReflectionParameter $parameter): bool
    {
        $converted = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($converted === null) {
            throw $this->invalidField($parameter);
        }

        return $converted;
    }

    private function invalidField(ReflectionParameter $parameter): BadRequestHttpException
    {
        return new BadRequestHttpException(sprintf('Field "%s" has an invalid value.', $parameter->getName()));
    }
}
