<?php

namespace Tests\Feature\Campaign;

use App\Http\Livewire\Campaign\Create;
use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\Country;
use App\Models\User;
use App\Services\Campaign\TemporaryCampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Traits\WithCreateCampaignTestDataProvider;
use Tests\TestCase;

class SecondStepFormTest extends TestCase
{
    use RefreshDatabase;
    use WithCreateCampaignTestDataProvider;

    /**
     * @dataProvider firstStepValidFormDataProvider
     **/
    public function test_second_step_form_can_be_rendered_after_first(string $country, string $address, string $category, string $title)
    {
        Livewire::actingAs($user = User::factory()->create());
        $newCountry = Country::factory()->create();
        $newCategory = CampaignCategory::factory()->create();

        $component = Livewire::test(Create::class)
            ->set('title', 'foo title')
            ->assertSet('title', 'foo title')
            ->set('address', 'foo address')
            ->assertSet('address', 'foo address')
            ->set('country', $newCountry->code)
            ->assertSet('country', $newCountry->code)
            ->set('category', $newCategory->name)
            ->assertSet('category', $newCategory->name)
            ->call('firstSubmit');
        $component->assertHasNoErrors(['category', 'title', 'country', 'address']);

        $temporaryCampaignService = new TemporaryCampaignService;
        $cachedData = $temporaryCampaignService->getCachedData();
        $temporaryCampaign = $temporaryCampaignService->get();
        $step = $temporaryCampaignService->getStep();
        $cachedCountry = $temporaryCampaignService->getCountry();
        $cachedCategory = $temporaryCampaignService->getCategory();

        $this->assertTrue($temporaryCampaign instanceof Campaign);
        $this->assertTrue($temporaryCampaign->title === 'foo title');
        $this->assertTrue($temporaryCampaign->address === 'foo address');
        $this->assertTrue($temporaryCampaign->country_id === $newCountry->id);
        $this->assertTrue($temporaryCampaign->campaign_category_id === $newCategory->id);

        $this->assertTrue($cachedCountry->is($newCountry));
        $this->assertTrue($cachedCategory->is($newCategory));
        $this->assertTrue($step === 2);

        // check if first step component is not rendered
        $component->assertDontSee(__('General informations'));
        // check if second step component is rendered
        $component->assertSee(__('Set the goal of your fundraiser'));
        $component->assertDontSee(__('Add a presentation photo'));
        $component->assertDontSee(__('Why are you fundraising?'));

        $component->set('amount', 2000);
    }

    /**
     * @dataProvider firstStepInvalidFormDataProvider
     **/
    public function test_second_step_form_can_not_be_validated_with_invalid_data(string $country, string $address, string $category, string $title)
    {
        Livewire::actingAs($user = User::factory()->create());

        $component = Livewire::test(Create::class)
            ->set('title', $title)
            ->assertSet('title', $title)
            ->set('address', $address)
            ->assertSet('address', $address)
            ->set('country', $country)
            ->assertSet('country', $country)
            ->set('category', $category)
            ->assertSet('category', $category)
            ->call('firstSubmit')
            ->assertHasErrors(['category', 'title', 'country', 'address']);

        $this->markTestIncomplete('This test has not been finished yet.');
    }
}
