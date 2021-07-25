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

class CreateCampaignTest extends TestCase
{
    use RefreshDatabase;
    use WithCreateCampaignTestDataProvider;

    public function test_can_not_create_campaign_without_login()
    {
        $url = route('campaigns.create');
        $this->get($url)->assertRedirect('/login');
    }

    public function test_create_campaign_page_can_be_rendered()
    {
        Livewire::actingAs($user = User::factory()->create());

        $response = Livewire::test(Create::class);
        $response->assertStatus(200);
    }

    public function test_first_step_form_can_be_rendered_at_the_beginning()
    {
        Livewire::actingAs($user = User::factory()->create());
        $url = route('campaigns.create');
        $this->get($url)->assertSeeLivewire(Create::class);
        $response = Livewire::test(Create::class);
        $response->assertSeeHtml( __('General informations'));
        $response->assertDontSeeHtml(__('Set the goal of your fundraiser'));
        $response->assertDontSeeHtml(__('Add a presentation photo'));
        $response->assertDontSeeHtml(__('Why are you fundraising?'));
    }

    /**
     * @dataProvider createCampaignValidFormDataProvider
     **/
    public function test_can_create_campaign(string $country, string $address, string $category, string $title, int $amount)
    {
        Livewire::actingAs($user = User::factory()->create());
        $newCountry = Country::factory()->create();
        $newCategory = CampaignCategory::factory()->create();

        $component = Livewire::test(Create::class)
            ->set('title', $title)
            ->assertSet('title', $title)
            ->set('address', $address)
            ->assertSet('address', $address)
            ->set('country', $newCountry->code)
            ->assertSet('country', $newCountry->code)
            ->set('category', $newCategory->name)
            ->assertSet('category', $newCategory->name)
            ->call('firstSubmit');
        $component->assertHasNoErrors(['category', 'title', 'country', 'address']);

        $temporaryCampaignService = new TemporaryCampaignService;
        // $cachedData = $temporaryCampaignService->getCachedData();
        $temporaryCampaign = $temporaryCampaignService->get();
        $step = $temporaryCampaignService->getStep();
        $cachedCountry = $temporaryCampaignService->getCountry();
        $cachedCategory = $temporaryCampaignService->getCategory();

        $this->assertTrue($temporaryCampaign instanceof Campaign);
        $this->assertTrue($temporaryCampaign->title === $title);
        $this->assertTrue($temporaryCampaign->address === $address);
        $this->assertTrue($temporaryCampaign->country_id === $newCountry->id);
        $this->assertTrue($temporaryCampaign->campaign_category_id === $newCategory->id);

        $this->assertTrue($cachedCountry->is($newCountry));
        $this->assertTrue($cachedCategory->is($newCategory));
        $this->assertTrue($step === 2);

        $component->assertDontSee(__('General informations'));
        $component->assertSee(__('Set the goal of your fundraiser'));
        $component->assertDontSee(__('Add a presentation photo'));
        $component->assertDontSee(__('Why are you fundraising?'));

        // second step
        $component->set('amount', $amount)
        ->assertSet('amount', $amount)
            ->call('secondSubmit');
        $component->assertHasNoErrors(['amount']);

        $temporaryCampaign = $temporaryCampaignService->get();
        $step = $temporaryCampaignService->getStep();

        $this->assertTrue($temporaryCampaign->amount_to_reach === $amount);

        $this->assertTrue($step === 3);
        $component->assertDontSee(__('General informations'));
        $component->assertDontSee(__('Set the goal of your fundraiser'));
        $component->assertSee(__('Add a presentation photo'));
        $component->assertDontSee(__('Why are you fundraising?'));

        // $this->markTestIncomplete('This test has not been finished yet.');
    }
}
