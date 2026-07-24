<?php

use App\Domains\FAQ\Private\Models\FaqCategory;
use App\Domains\FAQ\Private\Models\FaqQuestion;
use App\Domains\FAQ\Private\Support\FaqMediaUsageProvider;
use App\Domains\Media\Private\Services\MediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

function faqCategoryForImage(): FaqCategory
{
    return FaqCategory::create([
        'name' => 'Cat', 'slug' => 'cat-' . uniqid(), 'is_active' => true,
        'sort_order' => 1, 'created_by_user_id' => 1, 'updated_by_user_id' => 1,
    ]);
}

function faqQuestionWithImage(FaqCategory $category, string $imagePath): FaqQuestion
{
    return FaqQuestion::create([
        'faq_category_id' => $category->id,
        'question' => 'Q ' . uniqid() . ' ?',
        'slug' => 'q-' . uniqid(),
        'answer' => '<p>a</p>',
        'image_path' => $imagePath,
        'image_alt_text' => 'Alt',
        'is_active' => true, 'sort_order' => 1,
        'created_by_user_id' => 1, 'updated_by_user_id' => 1,
    ]);
}

describe('FAQ image via Media', function () {
    it('stores an uploaded image through Media and records its path', function () {
        $category = faqCategoryForImage();

        $this->actingAs(admin($this))
            ->post(route('faq.admin.faq-questions.store'), [
                'faq_category_id' => $category->id,
                'question' => 'Avec image ?',
                'slug' => 'avec-image',
                'answer' => '<p>oui</p>',
                'is_active' => '1',
                'image' => ['file' => UploadedFile::fake()->image('pic.jpg', 800, 600), 'alt' => 'Une image'],
            ])
            ->assertRedirect(route('faq.admin.faq-questions.index'));

        $q = FaqQuestion::where('slug', 'avec-image')->firstOrFail();
        expect($q->image_path)->toStartWith('faq/');
        expect($q->image_alt_text)->toBe('Une image');
        Storage::disk('public')->assertExists($q->image_path);
    });

    it('reuses an existing path without re-uploading', function () {
        $category = faqCategoryForImage();
        Storage::disk('public')->put('faq/existing.jpg', 'x');

        $this->actingAs(admin($this))
            ->post(route('faq.admin.faq-questions.store'), [
                'faq_category_id' => $category->id,
                'question' => 'Reuse ?',
                'slug' => 'reuse',
                'answer' => '<p>oui</p>',
                'is_active' => '1',
                'image' => ['path' => 'faq/existing.jpg', 'alt' => 'Reused'],
            ])
            ->assertRedirect();

        $q = FaqQuestion::where('slug', 'reuse')->firstOrFail();
        expect($q->image_path)->toBe('faq/existing.jpg');
    });

    it('clears the image when path is emptied and no file is sent', function () {
        $category = faqCategoryForImage();
        $question = faqQuestionWithImage($category, 'faq/old.jpg');

        $this->actingAs(admin($this))
            ->put(route('faq.admin.faq-questions.update', $question), [
                'faq_category_id' => $category->id,
                'question' => $question->question,
                'slug' => $question->slug,
                'answer' => '<p>a</p>',
                'is_active' => '1',
                'image' => ['path' => '', 'alt' => ''],
            ])
            ->assertRedirect();

        $question->refresh();
        expect($question->image_path)->toBeNull();
        expect($question->image_alt_text)->toBeNull();
    });

    it('does not delete the image file on question delete (left to GC)', function () {
        $category = faqCategoryForImage();
        Storage::disk('public')->put('faq/keep.jpg', 'x');
        $question = faqQuestionWithImage($category, 'faq/keep.jpg');

        $this->actingAs(admin($this))
            ->delete(route('faq.admin.faq-questions.destroy', $question))
            ->assertRedirect();

        Storage::disk('public')->assertExists('faq/keep.jpg');
    });
});

describe('FaqMediaUsageProvider', function () {
    it('yields every question image path', function () {
        $category = faqCategoryForImage();
        faqQuestionWithImage($category, 'faq/a.jpg');
        faqQuestionWithImage($category, 'faq/b.jpg');
        // A question without an image contributes nothing.
        FaqQuestion::create([
            'faq_category_id' => $category->id, 'question' => 'No image ?', 'slug' => 'no-img-' . uniqid(),
            'answer' => '<p>a</p>', 'is_active' => true, 'sort_order' => 1,
            'created_by_user_id' => 1, 'updated_by_user_id' => 1,
        ]);

        $paths = iterator_to_array((function () {
            yield from (new FaqMediaUsageProvider())->usedPaths();
        })());

        expect($paths)->toContain('faq/a.jpg');
        expect($paths)->toContain('faq/b.jpg');
        expect($paths)->toHaveCount(2);
    });

    it('protects a referenced FAQ image from GC while collecting an unclaimed one', function () {
        $category = faqCategoryForImage();
        Storage::disk('public')->put('faq/used.jpg', 'x');
        Storage::disk('public')->put('faq/orphan.jpg', 'x');
        faqQuestionWithImage($category, 'faq/used.jpg');

        // FAQ provider is registered at boot, so the faq scope is "claimed".
        app(MediaService::class)->gc(-1);

        Storage::disk('public')->assertExists('faq/used.jpg');
        Storage::disk('public')->assertMissing('faq/orphan.jpg');
    });
});
