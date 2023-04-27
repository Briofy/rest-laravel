<?php

namespace Briofy\RestLaravel\Http\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Symfony\Component\HttpFoundation\Response;

trait Respond
{
    protected array $metas = [];

    protected array $additional = [];

    protected ?string $responseMessage = null;

    protected bool $hasErrors = false;

    public function additional(): array
    {
        return $this->additional;
    }

    public function setAdditional(array $data): static
    {
        $this->additional = $data;

        return $this;
    }

    protected function metas(): array
    {
        return $this->metas;
    }

    protected function setMetas(array $metas): self
    {
        $this->metas = $metas;

        return $this;
    }

    protected function message(): string
    {
        return $this->responseMessage ?? __('rest.respond.successful_message');
    }

    protected function setMessage(string $message): self
    {
        $this->responseMessage = $message;

        return $this;
    }

    protected function setCreatedMessage(): self
    {
        $this->responseMessage = __('rest.respond.successful_created_message');

        return $this;
    }

    protected function setUpdatedMessage(): self
    {
        $this->responseMessage = __('rest.respond.successful_update_message');

        return $this;
    }

    protected function hasErrors(): bool
    {
        return $this->hasErrors;
    }

    protected function setHasErrors(bool $hasErrors): self
    {
        $this->hasErrors = $hasErrors;

        return $this;
    }

    protected function respond(
        ResourceCollection|JsonResource|array|string $data = [],
        int $statusCode = Response::HTTP_OK,
        array $headers = []
    ): JsonResponse {
        if ($data instanceof ResourceCollection && $data->resource instanceof LengthAwarePaginatorContract) {
            return $this->respondWithPagination($data, [], $headers);
        }

        return response()->json(
            array_merge([
                'succeed' => !$this->hasErrors(),
                'message' => $this->message(),
                'results' => $data,
                'metas'   => $this->metas(),
            ], $this->additional()),
            $statusCode,
            $headers
        );
    }

    protected function respondWithPagination(
        ResourceCollection $resource,
        array $metas = [],
        array $headers = [],
    ): JsonResponse {
        $data           = $resource->response()->getData(true);
        $metas['links'] = $data['links'];

        return $this->setMetas(
            array_merge($data['meta'], $metas, $this->metas())
        )->respond($data['data'], Response::HTTP_OK, $headers);
    }

    protected function respondWithError(
        ?\Exception $exception = null,
        ?string $message = null,
        array $constraints = [],
        int $status = Response::HTTP_INTERNAL_SERVER_ERROR
    ): JsonResponse {
        return $this->setHasErrors(true)
            ->setMessage($message ?? __('rest.respond.error'))
            ->respond($constraints, $status);
    }

    protected function respondEntityRemoved(int|string $id): JsonResponse
    {
        return $this->setMessage(__('rest.respond.entity_removed'))->respond(['id' => $id]);
    }

    protected function respondEntityNotFound(?\Exception $exception, ?string $message = null): JsonResponse
    {
        return $this->respondWithError(
            $exception ?? new \Exception( __('rest.respond.not_found')),
            $message ?? __('rest.respond.not_found'),
            status: Response::HTTP_NOT_FOUND
        );
    }

    protected function respondNotTheRightParameters(?string $message = null): JsonResponse
    {
        return $this->respondWithError(
            $message ?? __('rest.respond.wrong_parameter'),
            status: Response::HTTP_BAD_REQUEST
        );
    }

    protected function respondInvalidQuery(?string $message = null): JsonResponse
    {
        return $this->respondWithError(
            $message ?? __('rest.respond.invalid_query'),
            status: Response::HTTP_BAD_REQUEST
        );
    }

    protected function respondInvalidParameters(?string $message = null): JsonResponse
    {
        return $this->respondWithError(
            $message ?? __('rest.respond.invalid_parameters'),
            status: Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }

    protected function respondUnauthorized(?string $message = null): JsonResponse
    {
        return $this->respondWithError(
            $message ?? __('rest.respond.unauthorized'),
            status: Response::HTTP_UNAUTHORIZED
        );
    }

    protected function respondForbidden(?string $message = null): JsonResponse
    {
        return $this->respondWithError(
            $message ?? __('rest.respond.forbidden'),
            status: Response::HTTP_FORBIDDEN
        );
    }

    protected function respondNotAcceptable(?string $message = null): JsonResponse
    {
        return $this->respondWithError(
            $message ?? __('rest.respond.not_acceptable'),
            status: Response::HTTP_NOT_ACCEPTABLE
        );
    }
}
