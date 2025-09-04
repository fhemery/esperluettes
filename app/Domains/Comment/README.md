# Comment Module

This module provides comment creation/listing services and a pluggable policy mechanism to enforce domain-specific rules (per commentable entity type).

## Technical details

Because this module is meant to be used by other modules, it does not provide complete views.

Instead, the [CommentList](./View/Components/CommentList.php) component is provided as a Blade component. It is in charge of dealing with lazy or eager loading of comments, and provide the div with the comments list.

`CommentList` uses the [CommentPublicApi](./PublicApi/CommentPublicApi.php) to fetch comments and the operations that can be performed on them. To enable control from the other modules (Story, News, etc...), we provide a [PolicyRegistry](./Services/CommentPolicyRegistry.php) that can be used to check permissions and define some thresholds (comment min and max length, etc...).
The `CommentPublicApi` enriches data with the policies, and provide the first few comments with the children comments.

Then, an [Intersection observer](https://developer.mozilla.org/en-US/docs/Web/API/Intersection_Observer_API) is used to fetch the next page of comments when the user scrolls to the bottom of the list. This will call the [CommentController](./Http/Controllers/CommentController.php) to fetch the next page of comments as a fragment that is integrated by the list.

Why Blade ? Because although there are some client interaction, most code can be tested through Feature Tests, which increases drastically the robustness of the app.

You can find the full flow in the image below:

![Comment retrieval flow](./Docs/Diagrams/Comment%20Retrieval%20Sequence.png)



## Policy mechanism

- To register a policy, you need a class that implements [CommentPolicy](./Contracts/CommentPolicy.php)

Then register it simply in your module provider :
Create a class implementing `CommentPolicy` (e.g., inside your domain) and register it for the targeted `entityType` in your service provider's `boot()`/`register()` method.

```php
<?php
namespace App\Domains\YourDomain\Providers;

class YourDomainServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $registry = app(CommentPolicyRegistry::class);

        // Example: only confirmed users can comment on chapters
        $chapterPolicy = app(ChapterPolicy::class); // to enable DI
        $registry->register('chapter', $chapterPolicy);
    }
}
```

Example: [ChapterCommentPolicy](../Story/Services/ChapterCommentPolicy.php), registered in [StoryServiceProvider](../Story/Providers/StoryServiceProvider.php)
