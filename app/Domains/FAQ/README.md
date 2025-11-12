# FAQ module

This module is in charge of handling the FAQ of the site.

## Technical detail

The module uses [Filament](https://filamentphp.com/) to provide an admin panel to create categories and spread questions into categories (see [FaqCategoryResource](../Admin/Filament/Resources/FAQ/FaqCategoryResource.php) and [FaqQuestionResource](../Admin/Filament/Resources/FAQ/FaqQuestionResource.php)).

Then provides a public view at `faq`, with possible category refinement.