<?php

namespace App\Domains\Comment\Contracts;

use App\Domains\Shared\Dto\ProfileDto;
use App\Domains\Auth\PublicApi\Dto\RoleDto;
use App\Domains\Comment\Models\Comment;

class CommentDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $body,
        public readonly int $authorId,
        public readonly ProfileDto $authorProfile,
        public readonly string $createdAt,
        public readonly ?string $updatedAt = null,
    ) {
    }

    public static function fromModel(Comment $model, ProfileDto $authorProfile): self
    {
        return new self(
            id: (int) $model->id,
            body: (string) $model->body,
            authorId: (int) $model->author_id,
            authorProfile: $authorProfile,
            createdAt: (string) $model->created_at,
            updatedAt: $model->updated_at?->toISOString(),
        );
    }

    public static function fromArray(array $data): self
    {
        $profile = new ProfileDto(
            user_id: (int) ($data['author_profile']['user_id'] ?? ($data['author_id'] ?? 0)),
            display_name: (string) ($data['author_profile']['display_name'] ?? ''),
            slug: (string) ($data['author_profile']['slug'] ?? ''),
            avatar_url: (string) ($data['author_profile']['avatar_url'] ?? ''),
        );

        $roles = array_map(function (array $r) {
            return new RoleDto(
                id: (int) $r['id'],
                name: (string) $r['name'],
                slug: (string) $r['slug'],
                description: $r['description'] ?? null,
            );
        }, $data['author_roles'] ?? []);

        return new self(
            id: (int) $data['id'],
            body: (string) $data['body'],
            authorId: (int) $data['author_id'],
            authorProfile: $profile,
            createdAt: (string) $data['created_at'],
            updatedAt: $data['updated_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'author_id' => $this->authorId,
            'author_profile' => [
                'user_id' => $this->authorProfile->user_id,
                'display_name' => $this->authorProfile->display_name,
                'slug' => $this->authorProfile->slug,
                'avatar_url' => $this->authorProfile->avatar_url,
            ],
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
