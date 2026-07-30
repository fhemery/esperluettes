<?php

$desc = <<<'HTML'
<p>QA-PROF Bio de test pour la vérification visuelle.</p>
<p class="ql-align-center">QA-PROF-CENTER Ligne centrée.</p>
<ul><li>QA-PROF-UL élément de liste</li></ul>
<p>QA-PROF-LINK <a href="https://example.com" target="_blank" rel="noopener noreferrer">un lien</a> et un emoji <span class="ql-custom-emoji ql-custom-emoji-esper-heart"></span>.</p>
<h2>QA-PROF-H2 Titre</h2>
HTML;

DB::table('profile_profiles')->where('user_id', 12)->update(['description' => $desc]);
echo "profile 12 description set\n";

// Report the real custom-emoji class names shipped in the CSS, so the flow can
// assert against one that actually has a background-image.
echo "done\n";
