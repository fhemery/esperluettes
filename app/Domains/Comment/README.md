# Comment Module

This module provides comment creation/listing services and a pluggable policy mechanism to enforce domain-specific rules (per commentable entity type).

## Technical details

- Public API: `App\Domains\Comment\PublicApi\CommentPublicApi`
  - `create(CommentToCreateDto $comment): int` creates a comment and returns its id.
  - `getFor(string $entityType, int $entityId, int $page = 1, int $perPage = 20): CommentListDto` lists comments.
  - `getComment(int $commentId): CommentDto` fetches a single comment.
- Sanitization: comment bodies are sanitized via HTML Purifier (strict profile) and stored as plain text.
- Policies: per-entity posting rules via `CommentPolicyRegistry` and `CommentPolicy`.
  - Registry is a container singleton registered by `CommentServiceProvider`.
  - Policies validate a `CommentToCreateDto` and can throw `UnauthorizedException` or `ValidationException`.

## Policy mechanism

- Interface: `App\\Domains\\Comment\\Contracts\\CommentPolicy`
  - Methods:
    - `validateCreate(CommentToCreateDto $dto): void`
    - `canCreateRoot(string $entityType, int $entityId, int $userId): bool`
    - `canReply(CommentDto $parentComment, int $userId): bool`
    - `canEditOwn(CommentDto $comment, int $userId): bool`
    - `validateEdit(CommentDto $comment, int $userId, string $newBody): void`
    - `getMinBodyLength(): ?int`
    - `getMaxBodyLength(): ?int` (null means no limit)
- DTO: `App\Domains\Comment\Contracts\CommentToCreateDto`
  - Fields: `entityType`, `entityId`, `body`, `parentCommentId`.
- Registry: `App\\Domains\\Comment\\Services\\CommentPolicyRegistry`
- The Public API calls `CommentPolicyRegistry::validateCreate()` before persisting.

## Registering a policy from your module

Create a class implementing `CommentPolicy` (e.g., inside your domain) and register it for the targeted `entityType` in your service provider's `boot()`/`register()` method.

```php
<?php
namespace App\Domains\YourDomain\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domains\Comment\PublicApi\CommentPolicyRegistry;
use App\Domains\Comment\Contracts\CommentPolicy;
use App\Domains\Comment\Contracts\CommentToCreateDto;
use App\Domains\Auth\PublicApi\AuthPublicApi;
use App\Domains\Auth\PublicApi\Roles;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;

class YourDomainServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $registry = app(CommentPolicyRegistry::class);

        // Example: only confirmed users can comment on chapters
        $registry->register('chapter', new class implements CommentPolicy {
            public function validateCreate(CommentToCreateDto $dto): void
            {
                $auth = app(AuthPublicApi::class);
                if (!$auth->hasAnyRole([Roles::USER_CONFIRMED])) {
                    throw new UnauthorizedException('Only confirmed users may comment');
                }

                // Example: 140-char limit on plain text content
                $len = mb_strlen(trim(strip_tags($dto->body)));
                if ($len > 140) {
                    throw ValidationException::withMessages(['body' => ['Comment too long']]);
                }
            }
            public function canCreateRoot(string $entityType, int $entityId, int $userId): bool { return true; }
            public function canReply(\App\\Domains\\Comment\\Contracts\\CommentDto $parentComment, int $userId): bool { return true; }
            public function canEditOwn(\App\\Domains\\Comment\\Contracts\\CommentDto $comment, int $userId): bool { return true; }
            public function validateEdit(\App\\Domains\\Comment\\Contracts\\CommentDto $comment, int $userId, string $newBody): void {}
            public function getMinBodyLength(): ?int { return 1; }
            public function getMaxBodyLength(): ?int { return 140; }
        });
    }
}
```

Notes:
- `entityType` is a free-form string you choose per commentable type (e.g., `chapter`, `story`).
- You can register multiple policies for different entity types by calling `register()` with different keys.
- Policies should be deterministic and side-effect free (validation only).

## File locations

- Contracts: `app/Domains/Comment/Contracts/`
- Services (registry, service): `app/Domains/Comment/Services/`
- Public API: `app/Domains/Comment/PublicApi/`
- Provider: `app/Domains/Comment/PublicApi/Providers/CommentServiceProvider.php`
