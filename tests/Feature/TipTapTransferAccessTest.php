<?php

namespace Tests\Feature;

use App\Models\BranchStore;
use App\Models\User;
use Tests\TestCase;

class TipTapTransferAccessTest extends TestCase
{
    public function test_guest_is_redirected_from_transfer_routes(): void
    {
        $this->get(route('tip-tap-transfer.index'))->assertRedirect(route('login'));
        $this->post(route('tip-tap-transfer.export'))->assertRedirect(route('login'));
    }

    public function test_non_owner_cannot_open_or_export_tip_tap_transfer(): void
    {
        $user = new User(['role' => 'CS']);
        $user->id = 99;
        $this->actingAs($user);

        $this->get(route('tip-tap-transfer.index'))->assertForbidden();
        $this->post(route('tip-tap-transfer.export'))->assertForbidden();
    }

    public function test_owner_cannot_open_transfer_when_branch_setting_is_disabled(): void
    {
        $this->actingAs($this->ownerWithSetting(false));

        $this->get(route('tip-tap-transfer.index'))->assertNotFound();
        $this->post(route('tip-tap-transfer.export'))->assertNotFound();
    }

    public function test_owner_can_open_transfer_when_branch_setting_is_enabled(): void
    {
        $this->actingAs($this->ownerWithSetting(true));

        $this->get(route('tip-tap-transfer.index'))
            ->assertOk()
            ->assertSee('Export Data untuk Tip-Tap');
    }

    private function ownerWithSetting(bool $enabled): User
    {
        $branchStore = new BranchStore();
        $branchStore->tip_tap_transfer_enabled = $enabled;

        $user = new User(['role' => 'OWNER']);
        $user->id = 1;
        $user->setRelation('branchStore', $branchStore);

        return $user;
    }
}
