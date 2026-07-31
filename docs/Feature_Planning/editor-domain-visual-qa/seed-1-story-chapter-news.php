<?php

$content = <<<'HTML'
<p>QA-PLAIN Paragraphe normal, aligné à gauche par défaut.</p>
<p class="ql-align-center">QA-CENTER Ce paragraphe doit être centré.</p>
<p class="ql-align-right">QA-RIGHT Ce paragraphe doit être aligné à droite.</p>
<p class="ql-align-justify">QA-JUSTIFY Ce paragraphe doit être justifié, il faut donc suffisamment de texte pour que la justification soit visible sur plusieurs lignes de rendu à l'écran, ajoutons donc quelques mots supplémentaires afin d'atteindre la deuxième ligne sans difficulté.</p>
<p>QA-SPOILER Voici un <span class="ql-spoiler">secret caché derrière un spoiler</span> dans une phrase.</p>
<ul><li>QA-UL premier élément</li><li>QA-UL second élément</li></ul>
<ol><li>QA-OL premier élément</li><li>QA-OL second élément</li></ol>
<p>QA-LINK Un <a href="https://example.com" target="_blank" rel="noopener noreferrer">lien externe</a> dans le texte.</p>
<p>QA-EMOJI Un emoji personnalisé <span class="ql-custom-emoji ql-custom-emoji--esper-love"></span> inline.</p>
<h2>QA-H2 Un titre de niveau 2</h2>
<h3>QA-H3 Un titre de niveau 3</h3>
<blockquote>QA-QUOTE Une citation en bloc.</blockquote>
<p><strong>QA-BOLD gras</strong> <em>QA-ITALIC italique</em> <u>QA-UNDERLINE souligné</u> <s>QA-STRIKE barré</s></p>
HTML;

$note = <<<'HTML'
<p>QA-NOTE Note de l'auteur avec un <span class="ql-spoiler">spoiler dans la note</span>.</p>
HTML;

$indented = <<<'HTML'
<div class="ql-indent"><p>QA-INDENT Ce paragraphe doit être indenté de 2rem via .ql-indent.</p><p>QA-INDENT-2 Deuxième paragraphe indenté.</p></div>
HTML;

$full = $content . "\n" . $indented;

$existing = DB::table('story_chapters')->where('slug', 'qa-editor-domain')->first();
$data = [
    'story_id' => 1,
    'title' => 'QA Editor Domain',
    'slug' => 'qa-editor-domain',
    'author_note' => $note,
    'content' => $full,
    'sort_order' => 9999,
    'status' => 'published',
    'first_published_at' => now(),
    'publish_at' => now(),
    'last_edited_at' => now(),
    'reads_logged_count' => 0,
    'word_count' => 200,
    'character_count' => strlen(strip_tags($full)),
    'updated_at' => now(),
];

if ($existing) {
    DB::table('story_chapters')->where('id', $existing->id)->update($data);
    echo "updated chapter {$existing->id}\n";
} else {
    $data['created_at'] = now();
    $id = DB::table('story_chapters')->insertGetId($data);
    echo "created chapter {$id}\n";
}
echo "url: /stories/le-crepuscule-des-as-1/chapters/qa-editor-domain\n";
