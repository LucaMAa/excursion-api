<?php

declare(strict_types=1);

namespace App\Controller;

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

        if ([] !== $data && array_is_list($data)) {
            throw new BadRequestHttpException('The request body must be a JSON object.');
        }

        return $this->mapDto($dtoClass, $data);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $dtoClass
     *
     * @return list<T>
     */
    protected function bodyDtoList(Request $request, string $dtoClass): array
    {
        $data = $request->toArray();
        if (!array_is_list($data) || [] === $data) {
            throw new BadRequestHttpException('The request body must be a non-empty JSON array.');
        }

        $dtos = [];
        foreach ($data as $item) {
            if (!is_array($item)) {
                throw new BadRequestHttpException('Each item in the request body must be a JSON object.');
            }

            $dtos[] = $this->mapDto($dtoClass, $item);
        }

        return $dtos;
    }

    /**
     * @param object|array<array-key, mixed> $dto
     * @param array<string, string>          $headers
     */
    protected function dtoResponse(
        object|array $dto,
        int $status = JsonResponse::HTTP_OK,
        array $headers = [],
    ): JsonResponse {
        return new JsonResponse($this->normalizeResponseData($dto), $status, $headers);
    }

    /**
     * @param \SplObjectStorage<object, null>|null $seen
     */
    private function normalizeResponseData(mixed $data, ?\SplObjectStorage $seen = null): mixed
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->normalizeResponseData($value, $seen);
            }

            return $data;
        }

        if (!is_object($data)) {
            return $data;
        }

        if ($data instanceof \DateTimeInterface) {
            return $data->format(\DateTimeInterface::ATOM);
        }

        if ($data instanceof \JsonSerializable) {
            return $this->normalizeResponseData($data->jsonSerialize(), $seen);
        }

        $seen ??= new \SplObjectStorage();
        if ($seen->contains($data)) {
            throw new \LogicException(sprintf('A cyclic object graph cannot be serialized in a response (%s).', $data::class));
        }

        $seen->attach($data);

        try {
            $normalized = [];
            $reflection = new \ReflectionClass($data);

            foreach ($reflection->getProperties() as $property) {
                if ($property->isStatic() || !$property->isInitialized($data)) {
                    continue;
                }

                $normalized[$property->getName()] = $this->normalizeResponseData(
                    $property->getValue($data),
                    $seen,
                );
            }

            return $normalized;
        } finally {
            $seen->detach($data);
        }
    }

    /**
     * @template T of object
     *
     * @param class-string<T>      $dtoClass
     * @param array<string, mixed> $data
     *
     * @return T
     */
    private function mapDto(string $dtoClass, array $data): object
    {
        $reflection = new \ReflectionClass($dtoClass);
        $constructor = $reflection->getConstructor();

        if (!$reflection->isInstantiable() || null === $constructor) {
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

    private function convertValue(mixed $value, \ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();
        if (!$type instanceof \ReflectionNamedType) {
            throw new \LogicException(sprintf('DTO field "%s" must use a named type.', $parameter->getName()));
        }

        if (null === $value) {
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
            default => throw new \LogicException(sprintf('DTO field "%s" uses unsupported type "%s".', $parameter->getName(), $type->getName())),
        };
    }

    private function stringValue(mixed $value, \ReflectionParameter $parameter): string
    {
        if (!is_string($value) && !is_int($value) && !is_float($value) && !is_bool($value)) {
            throw $this->invalidField($parameter);
        }

        return (string) $value;
    }

    private function integerValue(mixed $value, \ReflectionParameter $parameter): int
    {
        $converted = filter_var($value, FILTER_VALIDATE_INT);
        if (false === $converted) {
            throw $this->invalidField($parameter);
        }

        return $converted;
    }

    private function floatValue(mixed $value, \ReflectionParameter $parameter): float
    {
        if (!is_numeric($value)) {
            throw $this->invalidField($parameter);
        }

        return (float) $value;
    }

    private function booleanValue(mixed $value, \ReflectionParameter $parameter): bool
    {
        $converted = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if (null === $converted) {
            throw $this->invalidField($parameter);
        }

        return $converted;
    }

    private function invalidField(\ReflectionParameter $parameter): BadRequestHttpException
    {
        return new BadRequestHttpException(sprintf('Field "%s" has an invalid value.', $parameter->getName()));
    }
}
