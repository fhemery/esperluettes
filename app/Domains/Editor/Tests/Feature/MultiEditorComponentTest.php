<?php

use App\Domains\Editor\Private\Blocks\ImageBlockType;
use App\Domains\Editor\Private\Blocks\TextBlockType;
use App\Domains\Editor\Public\Blocks\EditorBlockRegistry;
use App\Domains\Editor\Public\Blocks\EditorBlockType;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class);

/**
 * Bind a fresh registry holding the built-ins plus a fake `fake` plugin type
 * whose editor view is the test fixture, so the fake never leaks into other tests.
 */
function bindMultiEditorFakeRegistry(): void
{
    view()->addNamespace('editortest', app_path('Domains/Editor/Tests/Fixtures/views'));

    $registry = new EditorBlockRegistry();
    $registry->register(new TextBlockType());
    $registry->register(new ImageBlockType());
    $registry->register(new class implements EditorBlockType {
        public function key(): string
        {
            return 'fake';
        }

        public function labelKey(): string
        {
            return 'Fake block label';
        }

        public function icon(): string
        {
            return 'star';
        }

        public function editorView(): string
        {
            return 'editortest::fake-block';
        }

        public function render(array $block, array $context): string
        {
            return '';
        }
    });
    app()->instance(EditorBlockRegistry::class, $registry);
}

describe('<x-editor::multi> block types', function () {
    it('offers only text and image by default, in the palette and in the insert menu', function () {
        bindMultiEditorFakeRegistry();

        $html = (string) $this->blade(
            '<x-editor::multi name="blocks" scope="news" :blocks="$blocks" />',
            ['blocks' => [['type' => 'text', 'html' => '<p>x</p>']]]
        );

        expect($html)->toContain("appendBlock('text')")
            ->and($html)->toContain("appendBlock('image')")
            ->and($html)->toContain("insertAfter(\$el, 'text')")
            ->and($html)->toContain("insertAfter(\$el, 'image')")
            ->and($html)->not->toContain("appendBlock('fake')")
            ->and($html)->not->toContain("insertAfter(\$el, 'fake')");
    });

    it('hides the image entry from the insert menu when blockTypes is [\'text\']', function () {
        $html = (string) $this->blade(
            '<x-editor::multi name="blocks" scope="news" :blocks="$blocks" :blockTypes="[\'text\']" />',
            ['blocks' => [['type' => 'text', 'html' => '<p>x</p>']]]
        );

        expect($html)->toContain("insertAfter(\$el, 'text')")
            ->and($html)->toContain("appendBlock('text')")
            ->and($html)->not->toContain("insertAfter(\$el, 'image')")
            ->and($html)->not->toContain("appendBlock('image')");
    });

    it('offers a registered plugin type only when blockTypes enables it', function () {
        bindMultiEditorFakeRegistry();

        $html = (string) $this->blade(
            '<x-editor::multi name="blocks" scope="news" :blocks="$blocks" :blockTypes="[\'text\', \'image\', \'fake\']" />',
            ['blocks' => [['type' => 'text', 'html' => '<p>x</p>']]]
        );

        expect($html)->toContain("appendBlock('fake')")
            ->and($html)->toContain("insertAfter(\$el, 'fake')")
            ->and($html)->toContain('Fake block label')
            ->and($html)->toContain('>star<');
    });

    it('ignores a blockTypes entry that is not registered', function () {
        $html = (string) $this->blade(
            '<x-editor::multi name="blocks" scope="news" :blocks="$blocks" :blockTypes="[\'text\', \'nope\']" />',
            ['blocks' => [['type' => 'text', 'html' => '<p>x</p>']]]
        );

        expect($html)->toContain("appendBlock('text')")
            ->and($html)->not->toContain("appendBlock('nope')")
            ->and($html)->not->toContain("insertAfter(\$el, 'nope')")
            ->and($html)->not->toContain('data-block-template="nope"');
    });

    it('renders a stored plugin block through its editor view with the block context', function () {
        bindMultiEditorFakeRegistry();

        $html = (string) $this->blade(
            '<x-editor::multi name="blocks" scope="news" :blocks="$blocks" :blockTypes="[\'text\', \'fake\']" :blockContext="$ctx" />',
            [
                'blocks' => [
                    ['type' => 'text', 'html' => '<p>x</p>'],
                    ['type' => 'fake', 'value' => 'stored-42'],
                ],
                'ctx' => ['marker' => 'ctx-marker-7'],
            ]
        );

        $stored = Str::between($html, 'data-uid="b1"', 'data-block-template');

        // Chrome from <x-editor::multi.block>: type input, controls, insert menu.
        expect($html)->toContain('data-type="fake" data-uid="b1"')
            ->and($stored)->toContain('name="blocks[b1][type]" value="fake"')
            ->and($stored)->toContain('fake-value:stored-42')
            ->and($stored)->toContain('ctx-marker-7')
            ->and($stored)->toContain('moveUp($el)')
            ->and($stored)->toContain('removeBlock($el)')
            ->and($stored)->toContain("insertAfter(\$el, 'fake')");
    });

    it('renders one hidden template per enabled type, keyed by type', function () {
        bindMultiEditorFakeRegistry();

        $html = (string) $this->blade(
            '<x-editor::multi name="blocks" scope="news" :blockTypes="[\'text\', \'fake\']" :blockContext="$ctx" />',
            ['ctx' => ['marker' => 'tpl-marker']]
        );

        $fakeTpl = Str::between($html, '<template data-block-template="fake">', '</template>');

        expect(substr_count($html, 'data-block-template="text"'))->toBe(1)
            ->and(substr_count($html, 'data-block-template="fake"'))->toBe(1)
            ->and($html)->not->toContain('data-block-template="image"')
            ->and($fakeTpl)->toContain('data-uid="__UID__"')
            ->and($fakeTpl)->toContain('fake-value:')
            ->and($fakeTpl)->toContain('tpl-marker');
    });

    it('skips a stored block whose type is not enabled instead of rendering it as text', function () {
        bindMultiEditorFakeRegistry();

        $html = (string) $this->blade(
            '<x-editor::multi name="blocks" scope="news" :blocks="$blocks" />',
            ['blocks' => [
                ['type' => 'text', 'html' => '<p>x</p>'],
                ['type' => 'fake', 'value' => 'stored-42'],
                ['type' => 'unknown', 'html' => '<p>ghost</p>'],
            ]]
        );

        expect($html)->toContain('name="blocks[b0][html]"')
            ->and($html)->not->toContain('data-uid="b1"')
            ->and($html)->not->toContain('data-uid="b2"')
            ->and($html)->not->toContain('ghost');
    });
});

describe('<x-editor::multi>', function () {
    it('renders simple pane by default with the content field', function () {
        $html = $this->blade(
            '<x-editor::multi name="blocks" content-name="content" scope="news" />'
        );

        $html->assertSee('name="mode"', false);
        $html->assertSee('name="blocks_order"', false);
        // Simple editor hidden textarea for the content field.
        $html->assertSee('name="content"', false);
        // Mode toggle wired.
        $html->assertSee('goSimple()', false);
        $html->assertSee('goAdvanced()', false);
    });

    it('renders initial advanced blocks and serializes by uid', function () {
        $html = $this->blade(
            '<x-editor::multi name="blocks" scope="news" :blocks="$blocks" />',
            ['blocks' => [
                ['type' => 'text', 'html' => '<p>Intro</p>'],
                ['type' => 'image', 'path' => 'news/a.jpg', 'alt' => 'A'],
            ]]
        );

        // Text block: type + html textarea for uid b0.
        $html->assertSee('name="blocks[b0][type]"', false);
        $html->assertSee('name="blocks[b0][html]"', false);
        // Image block: type + media image-field path/file for uid b1.
        $html->assertSee('name="blocks[b1][type]"', false);
        $html->assertSee('name="blocks[b1][path]"', false);
        $html->assertSee('name="blocks[b1][file]"', false);
    });

    it('exposes text and image palette buttons', function () {
        $this->blade('<x-editor::multi name="blocks" scope="news" />')
            ->assertSee("appendBlock('text')", false)
            ->assertSee("appendBlock('image')", false);
    });

    it('offers both block types in the insert affordance popover', function () {
        // The insert "+" lets the author choose the type to insert at that spot.
        $this->blade(
            '<x-editor::multi name="blocks" scope="news" :blocks="$blocks" />',
            ['blocks' => [['type' => 'text', 'html' => '<p>x</p>']]]
        )
            ->assertSee("insertAfter(\$el, 'text')", false)
            ->assertSee("insertAfter(\$el, 'image')", false);
    });

    it('defaults text blocks to five lines and no indent', function () {
        // Regression guard: consumers that pass neither prop (News) must keep
        // rendering exactly what they rendered before the props existed.
        $html = (string) $this->blade(
            '<x-editor::multi name="blocks" scope="news" :blocks="$blocks" />',
            ['blocks' => [['type' => 'text', 'html' => '<p>Intro</p>']]]
        );

        expect($html)->toContain('data-nb-lines="5"')
            ->and($html)->not->toContain('data-nb-lines="15"')
            ->and($html)->not->toContain('ql-indent')
            ->and(substr_count($html, 'class="surface-read text-on-surface w-full"'))->toBe(2);
    });

    it('applies nbLines to every text block', function () {
        $html = (string) $this->blade(
            '<x-editor::multi name="blocks" scope="news" :blocks="$blocks" :nbLines="15" />',
            ['blocks' => [
                ['type' => 'text', 'html' => '<p>One</p>'],
                ['type' => 'text', 'html' => '<p>Two</p>'],
            ]]
        );

        // Two server-rendered blocks, the <template> new blocks are cloned from,
        // and the simple pane — the same writing surface in the same form.
        expect(substr_count($html, 'data-nb-lines="15"'))->toBe(4)
            ->and($html)->not->toContain('data-nb-lines="5"');
    });

    it('applies indentParagraphs to every text block', function () {
        $html = (string) $this->blade(
            '<x-editor::multi name="blocks" scope="news" :blocks="$blocks" :indentParagraphs="true" />',
            ['blocks' => [
                ['type' => 'text', 'html' => '<p>One</p>'],
                ['type' => 'text', 'html' => '<p>Two</p>'],
            ]]
        );

        // Same breakdown as above; `ql-indent` is the class <x-editor::rich-text>
        // emits for this prop.
        expect(substr_count($html, 'ql-indent'))->toBe(4);
    });

    it('gives dynamically added text blocks the same writing surface', function () {
        // New blocks are cloned from the Blade <template data-block-template="text">,
        // so the props only have to reach that template for JS-inserted blocks to match.
        $html = (string) $this->blade(
            '<x-editor::multi name="blocks" scope="news" :nbLines="15" :indentParagraphs="true" />'
        );

        $tpl = Str::between($html, '<template data-block-template="text">', '</template>');

        expect($tpl)->toContain('data-nb-lines="15"')
            ->and($tpl)->toContain('ql-indent');
    });

    it('forwards needsPropertyConfirm to image blocks and the image template', function () {
        $with = (string) $this->blade(
            '<x-editor::multi name="blocks" scope="chapters/1" :blocks="$blocks" :needs-property-confirm="true" />',
            ['blocks' => [['type' => 'image', 'path' => 'chapters/1/a.jpg', 'alt' => '']]]
        );
        $without = (string) $this->blade(
            '<x-editor::multi name="blocks" scope="news" :blocks="$blocks" />',
            ['blocks' => [['type' => 'image', 'path' => 'news/a.jpg', 'alt' => '']]]
        );

        $confirm = __('media::image-field.property_confirm');
        expect($with)->toContain($confirm)
            ->and(Str::between($with, '<template data-block-template="image">', '</template>'))->toContain($confirm)
            ->and($without)->not->toContain($confirm);
    });
});
