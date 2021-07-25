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

class FirstStepFormTest extends TestCase
{
    use RefreshDatabase;
    use WithCreateCampaignTestDataProvider;

    /**
     * @dataProvider firstStepInvalidFormDataProvider
     **/
    public function test_can_not_pass_first_step_form_validation_with_invalid_data(string $country, string $address, string $category, string $title)
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

        $temporaryCampaignService = new TemporaryCampaignService;
        $cachedData = $temporaryCampaignService->getCachedData();
        $temporaryCampaign = $temporaryCampaignService->get();
        $step = $temporaryCampaignService->getStep();
        $cachedCountry = $temporaryCampaignService->getCountry();
        $cachedCategory = $temporaryCampaignService->getCategory();

        $this->assertNull($cachedData);
        $this->assertNull($temporaryCampaign);
        $this->assertEquals($step, 1);
        $this->assertNull($cachedCountry);
        $this->assertNull($cachedCategory);
        $this->assertFalse($temporaryCampaign instanceof Campaign);
    }

    public function test_can_fill_first_step_and_pass_to_second_step()
    {
        Livewire::actingAs($user = User::factory()->create());
        $country = Country::factory()->create();
        $category = CampaignCategory::factory()->create();
        $component = Livewire::test(Create::class)
            ->set('title', 'foo title')
            ->assertSet('title', 'foo title')
            ->set('address', 'foo address')
            ->assertSet('address', 'foo address')
            ->set('country', $country->code)
            ->assertSet('country', $country->code)
            ->set('category', $category->name)
            ->assertSet('category', $category->name)
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
        $this->assertTrue($temporaryCampaign->country_id === $country->id);
        $this->assertTrue($temporaryCampaign->campaign_category_id === $category->id);

        $this->assertTrue($cachedCountry->is($country));
        $this->assertTrue($cachedCategory->is($category));
        $this->assertTrue($step === 2);
        $component->assertDontSee(__('General informations'));
        $component->assertSee(__('Set the goal of your fundraiser'));
    }

    public function test_first_step_form_data_can_be_updated_on_form_refilled()
    {
        Livewire::actingAs($user = User::factory()->create());
        $country = Country::factory()->create();
        $category = CampaignCategory::factory()->create();
        $component = Livewire::test(Create::class)
            ->set('title', 'foo title')
            ->assertSet('title', 'foo title')
            ->set('address', 'foo address')
            ->assertSet('address', 'foo address')
            ->set('country', $country->code)
            ->assertSet('country', $country->code)
            ->set('category', $category->name)
            ->assertSet('category', $category->name)
            ->call('firstSubmit');
        $component->assertHasNoErrors(['category', 'title', 'country', 'address']);

        $country2 = Country::factory()->create([
            'name' => 'Country 2',
            'code' => 'CDCXS',
        ]);

        $category2 = CampaignCategory::factory()->create([
            'name' => 'Category 2',
        ]);

        $component->set('step', 1); // returning to step 1 before continue
        $component
            ->set('title', 'test title')
            ->assertSet('title', 'test title')
            ->set('address', 'test address')
            ->assertSet('address', 'test address')
            ->set('country', $country2->code)
            ->assertSet('country', $country2->code)
            ->set('category', $category2->name)
            ->assertSet('category', $category2->name)
            ->call('firstSubmit');

        $component->assertHasNoErrors(['category', 'title', 'country', 'address']);
        $temporaryCampaignService = new TemporaryCampaignService;
        $cachedData = $temporaryCampaignService->getCachedData();
        $temporaryCampaign = $temporaryCampaignService->get();
        $step = $temporaryCampaignService->getStep();
        $cachedCountry = $temporaryCampaignService->getCountry();
        $cachedCategory = $temporaryCampaignService->getCategory();

        $this->assertTrue($temporaryCampaign instanceof Campaign);
        $this->assertTrue($temporaryCampaign->title === 'test title');
        $this->assertTrue($temporaryCampaign->address === 'test address');
        $this->assertTrue($temporaryCampaign->country_id === $country2->id);
        $this->assertTrue($temporaryCampaign->campaign_category_id === $category2->id);

        $this->assertTrue($cachedCountry->is($country2));
        $this->assertTrue($cachedCategory->is($category2));
        $this->assertTrue($step === 2);
    }
}
