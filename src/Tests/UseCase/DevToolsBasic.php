<?php

/**
 * UseCase: Developer Tools (Basic Flow)
 *
 * Acceptance test for the Developer admin panel.
 * Tests the primary admin flow through the developer tools.
 */

use Core\Tenant\Models\User;
use Core\Tenant\Models\Workspace;

describe('Developer Tools', function () {
    beforeEach(function () {
        // Create user with workspace
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->workspace = Workspace::factory()->create();
        $this->workspace->users()->attach($this->user->id, [
            'role' => 'owner',
            'is_default' => true,
        ]);
    });

    it('can view the logs page with all sections', function () {
        $this->actingAs($this->user);

        $response = $this->get(route('hub.dev.logs'));

        $response->assertOk();

        // Verify page title
        $response->assertSee(__('developer::developer.logs.title'));

        // Verify action buttons
        $response->assertSee(__('developer::developer.logs.actions.refresh'));
        $response->assertSee(__('developer::developer.logs.actions.clear'));

        // Verify level filters
        $response->assertSee(__('developer::developer.logs.levels.error'));
        $response->assertSee(__('developer::developer.logs.levels.warning'));
        $response->assertSee(__('developer::developer.logs.levels.info'));
        $response->assertSee(__('developer::developer.logs.levels.debug'));
    });

    it('can view the routes page', function () {
        $this->actingAs($this->user);

        $response = $this->get(route('hub.dev.routes'));

        $response->assertOk();

        // Verify page title
        $response->assertSee(__('developer::developer.routes.title'));

        // Verify table headers
        $response->assertSee(__('developer::developer.routes.table.method'));
        $response->assertSee(__('developer::developer.routes.table.uri'));
        $response->assertSee(__('developer::developer.routes.table.name'));
        $response->assertSee(__('developer::developer.routes.table.action'));
    });

    it('can view the cache page', function () {
        $this->actingAs($this->user);

        $response = $this->get(route('hub.dev.cache'));

        $response->assertOk();

        // Verify page title
        $response->assertSee(__('developer::developer.cache.title'));

        // Verify cache actions
        $response->assertSee(__('developer::developer.cache.cards.application.title'));
        $response->assertSee(__('developer::developer.cache.cards.config.title'));
        $response->assertSee(__('developer::developer.cache.cards.view.title'));
        $response->assertSee(__('developer::developer.cache.cards.route.title'));
    });
});
