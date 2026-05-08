<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Moderation\Private\Models\ModerationReason;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('ModerationReason Admin Controller', function () {

    describe('index', function () {
        it('displays the list for admins', function () {
            createReason('story', 'Contenu illégal');

            $this->actingAs(admin($this))
                ->get(route('moderation.admin.moderation-reasons.index'))
                ->assertOk()
                ->assertSee('Contenu illégal');
        });

        it('displays the list for moderators', function () {
            createReason('story', 'Harcèlement');

            $this->actingAs(moderator($this))
                ->get(route('moderation.admin.moderation-reasons.index'))
                ->assertOk()
                ->assertSee('Harcèlement');
        });

        it('denies access to non-admins', function () {
            $user = alice($this, [], true, [Roles::USER_CONFIRMED]);

            $this->actingAs($user)
                ->get(route('moderation.admin.moderation-reasons.index'))
                ->assertRedirect();
        });

        it('redirects unauthenticated users to login', function () {
            $this->get(route('moderation.admin.moderation-reasons.index'))
                ->assertRedirect(route('login'));
        });

        it('filters by topic_key', function () {
            createReason('story', 'Raison story');
            createReason('chapter', 'Raison chapitre');

            $this->actingAs(admin($this))
                ->get(route('moderation.admin.moderation-reasons.index', ['topic_key' => 'story']))
                ->assertOk()
                ->assertSee('Raison story')
                ->assertDontSee('Raison chapitre');
        });

        it('filters by is_active', function () {
            createReason('story', 'Active', null, true);
            createReason('story', 'Inactive', null, false);

            $this->actingAs(admin($this))
                ->get(route('moderation.admin.moderation-reasons.index', ['is_active' => '1']))
                ->assertOk()
                ->assertSee('Active')
                ->assertDontSee('Inactive');
        });

        it('shows all reasons when filter is submitted with empty is_active', function () {
            createReason('story', 'Active reason', null, true);
            createReason('story', 'Another active', null, true);

            $this->actingAs(admin($this))
                ->get(route('moderation.admin.moderation-reasons.index') . '?topic_key=&is_active=')
                ->assertOk()
                ->assertSee('Active reason')
                ->assertSee('Another active');
        });
    });

    describe('create', function () {
        it('displays the create form for admins', function () {
            $this->actingAs(admin($this))
                ->get(route('moderation.admin.moderation-reasons.create'))
                ->assertOk();
        });

        it('displays the create form for moderators', function () {
            $this->actingAs(moderator($this))
                ->get(route('moderation.admin.moderation-reasons.create'))
                ->assertOk();
        });
    });

    describe('store', function () {
        it('creates a reason', function () {
            $this->actingAs(admin($this))
                ->post(route('moderation.admin.moderation-reasons.store'), [
                    'topic_key' => 'story',
                    'label' => 'Nouvelle raison',
                    'is_active' => '1',
                ])
                ->assertRedirect(route('moderation.admin.moderation-reasons.index'));

            $this->assertDatabaseHas('moderation_reasons', [
                'topic_key' => 'story',
                'label' => 'Nouvelle raison',
                'is_active' => true,
            ]);
        });

        it('validates required fields', function () {
            $this->actingAs(admin($this))
                ->post(route('moderation.admin.moderation-reasons.store'), [])
                ->assertSessionHasErrors(['topic_key', 'label']);
        });
    });

    describe('edit', function () {
        it('displays the edit form', function () {
            $reason = createReason('story', 'Raison existante');

            $this->actingAs(admin($this))
                ->get(route('moderation.admin.moderation-reasons.edit', $reason))
                ->assertOk()
                ->assertSee('Raison existante');
        });
    });

    describe('update', function () {
        it('updates a reason', function () {
            $reason = createReason('story', 'Ancienne raison');

            $this->actingAs(admin($this))
                ->put(route('moderation.admin.moderation-reasons.update', $reason), [
                    'topic_key' => 'story',
                    'label' => 'Nouvelle raison',
                    'is_active' => '1',
                ])
                ->assertRedirect(route('moderation.admin.moderation-reasons.index'));

            $this->assertDatabaseHas('moderation_reasons', [
                'id' => $reason->id,
                'label' => 'Nouvelle raison',
            ]);
        });
    });

    describe('destroy', function () {
        it('deletes a reason not used in reports', function () {
            $reason = createReason('story', 'A supprimer');

            $this->actingAs(admin($this))
                ->delete(route('moderation.admin.moderation-reasons.destroy', $reason))
                ->assertRedirect(route('moderation.admin.moderation-reasons.index'));

            $this->assertDatabaseMissing('moderation_reasons', ['id' => $reason->id]);
        });

        it('blocks deletion when reason is used in a report', function () {
            $user = admin($this);
            $reason = createReason('story', 'Raison utilisée');
            createReportForUser($user, 'pending');

            // Link the report to this reason
            \Illuminate\Support\Facades\DB::table('moderation_reports')
                ->where('reason_id', '!=', $reason->id)
                ->update(['reason_id' => $reason->id]);

            $this->actingAs($user)
                ->delete(route('moderation.admin.moderation-reasons.destroy', $reason))
                ->assertRedirect(route('moderation.admin.moderation-reasons.index'));

            $this->assertDatabaseHas('moderation_reasons', ['id' => $reason->id]);
        });
    });

    describe('reorder', function () {
        it('reorders reasons', function () {
            $r1 = createReason('story', 'Premiere', 0);
            $r2 = createReason('story', 'Deuxieme', 1);
            $r3 = createReason('story', 'Troisieme', 2);

            $this->actingAs(admin($this))
                ->putJson(route('moderation.admin.moderation-reasons.reorder'), [
                    'ordered_ids' => [$r3->id, $r1->id, $r2->id],
                ])
                ->assertOk()
                ->assertJson(['success' => true]);

            $this->assertDatabaseHas('moderation_reasons', ['id' => $r3->id, 'sort_order' => 0]);
            $this->assertDatabaseHas('moderation_reasons', ['id' => $r1->id, 'sort_order' => 1]);
            $this->assertDatabaseHas('moderation_reasons', ['id' => $r2->id, 'sort_order' => 2]);
        });
    });
});
