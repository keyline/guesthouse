<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPropertyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_property_list(): void
    {
        $this->get('/admin/properties')->assertRedirect('/admin/login');
    }

    public function test_customer_cannot_access_property_list(): void
    {
        $customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);

        $this->actingAs($customer)->get('/admin/properties')->assertForbidden();
    }

    public function test_admin_can_view_property_list(): void
    {
        $admin = $this->adminUser();

        Property::query()->create($this->propertyPayload([
            'name' => 'EENNRA Riverside Guest House',
            'city' => 'Kolkata',
        ]));

        $this->actingAs($admin)
            ->get(route('admin.properties.index'))
            ->assertOk()
            ->assertSee('Properties')
            ->assertSee('EENNRA Riverside Guest House')
            ->assertSee('Kolkata');
    }

    public function test_admin_can_create_property_with_amenities(): void
    {
        $admin = $this->adminUser();
        $amenityIds = Amenity::query()
            ->whereIn('name', ['Wi-Fi', 'Parking', 'Air Conditioning'])
            ->pluck('id')
            ->all();

        $this->actingAs($admin)
            ->post(route('admin.properties.store'), $this->propertyPayload([
                'name' => 'Lake View Guest House',
                'amenities' => $amenityIds,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('properties', [
            'name' => 'Lake View Guest House',
            'slug' => 'lake-view-guest-house',
            'city' => 'Kolkata',
        ]);

        $this->assertDatabaseHas('amenities', ['name' => 'Wi-Fi']);
        $this->assertDatabaseHas('amenities', ['name' => 'Parking']);

        $property = Property::query()->where('slug', 'lake-view-guest-house')->firstOrFail();

        $this->assertSame(3, $property->amenities()->count());
    }

    public function test_admin_can_update_property(): void
    {
        $admin = $this->adminUser();
        $property = Property::query()->create($this->propertyPayload(['name' => 'Old Property Name']));

        $banquetHall = Amenity::query()->where('name', 'Banquet Hall')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.properties.update', $property), $this->propertyPayload([
                'name' => 'New Property Name',
                'city' => 'Durgapur',
                'amenities' => [$banquetHall->id],
            ]))
            ->assertRedirect(route('admin.properties.edit', $property));

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'name' => 'New Property Name',
            'slug' => 'new-property-name',
            'city' => 'Durgapur',
        ]);

        $this->assertSame(['Banquet Hall'], $property->fresh()->amenities()->pluck('name')->all());
    }

    public function test_blank_amenity_inputs_do_not_block_property_update(): void
    {
        $admin = $this->adminUser();
        $property = Property::query()->create($this->propertyPayload(['name' => 'Browser Test Property']));

        $this->actingAs($admin)
            ->put(route('admin.properties.update', $property), $this->propertyPayload([
                'name' => 'Browser Test Property 2',
                'amenities' => [null, null, null, null, null, null],
            ]))
            ->assertRedirect(route('admin.properties.edit', $property));

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'name' => 'Browser Test Property 2',
        ]);
    }

    public function test_admin_can_toggle_property_between_draft_and_active(): void
    {
        $admin = $this->adminUser();
        $property = Property::query()->create($this->propertyPayload([
            'name' => 'Toggle Status Property',
            'status' => Property::STATUS_DRAFT,
        ]));

        $this->actingAs($admin)
            ->post(route('admin.properties.toggle-status', $property))
            ->assertRedirect();

        $this->assertSame(Property::STATUS_ACTIVE, $property->fresh()->status);
        $this->assertNotNull($property->fresh()->published_at);

        $this->actingAs($admin)
            ->post(route('admin.properties.toggle-status', $property))
            ->assertRedirect();

        $this->assertSame(Property::STATUS_DRAFT, $property->fresh()->status);
        $this->assertNull($property->fresh()->published_at);
    }

    public function test_admin_can_toggle_property_home_page_visibility(): void
    {
        $admin = $this->adminUser();
        $property = Property::query()->create($this->propertyPayload([
            'name' => 'Home Toggle Property',
            'show_on_home' => false,
        ]));

        $this->actingAs($admin)
            ->post(route('admin.properties.toggle-home', $property))
            ->assertRedirect();

        $this->assertTrue($property->fresh()->show_on_home);

        $this->actingAs($admin)
            ->post(route('admin.properties.toggle-home', $property))
            ->assertRedirect();

        $this->assertFalse($property->fresh()->show_on_home);
    }

    public function test_home_page_featured_cards_use_marked_properties(): void
    {
        Property::query()->create($this->propertyPayload([
            'name' => 'Featured Guest House',
            'property_type' => Property::TYPE_GUEST_HOUSE,
            'show_on_home' => true,
        ]));

        Property::query()->create($this->propertyPayload([
            'name' => 'Dropdown Only Guest House',
            'property_type' => Property::TYPE_GUEST_HOUSE,
            'show_on_home' => false,
        ]));

        $this->get('/')
            ->assertOk()
            ->assertViewHas('guestHouseProperties', fn ($properties) => $properties->pluck('name')->all() === [
                'Dropdown Only Guest House',
                'Featured Guest House',
            ])
            ->assertViewHas('guestHouseFeaturedProperties', fn ($properties) => $properties->pluck('name')->all() === [
                'Featured Guest House',
            ]);
    }

    public function test_super_admin_can_manage_amenity_master(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->post(route('admin.amenities.store'), [
                'name' => 'Conference Wi-Fi',
                'icon' => 'wifi',
                'category' => 'connectivity',
                'scope' => 'room_category',
                'sort_order' => 5,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.amenities.index'));

        $this->assertDatabaseHas('amenities', [
            'name' => 'Conference Wi-Fi',
            'icon' => 'wifi',
            'category' => 'connectivity',
            'scope' => 'room_category',
            'is_active' => true,
        ]);
    }

    public function test_property_manager_cannot_manage_amenity_master(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_PROPERTY_MANAGER,
            'is_active' => true,
        ]);

        $this->actingAs($manager)
            ->get(route('admin.amenities.index'))
            ->assertForbidden();
    }

    public function test_admin_can_upload_property_image(): void
    {
        Storage::fake('public');

        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->post(route('admin.properties.store'), $this->propertyPayload([
                'name' => 'Image Ready Guest House',
                'images' => [
                    UploadedFile::fake()->image('front.jpg', 1200, 800),
                ],
            ]))
            ->assertRedirect();

        $property = Property::query()->where('slug', 'image-ready-guest-house')->firstOrFail();
        $image = PropertyImage::query()->where('property_id', $property->id)->firstOrFail();

        Storage::disk('public')->assertExists($image->path);
        $this->assertTrue($image->is_primary);
    }

    public function test_admin_can_delete_property_image(): void
    {
        Storage::fake('public');

        $admin = $this->adminUser();
        $property = Property::query()->create($this->propertyPayload(['name' => 'Image Delete Guest House']));
        Storage::disk('public')->put('properties/'.$property->id.'/front.jpg', 'image-data');

        $image = $property->images()->create([
            'path' => 'properties/'.$property->id.'/front.jpg',
            'alt_text' => 'Front view',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $this->actingAs($admin)
            ->deleteJson(route('admin.properties.images.destroy', [$property, $image]))
            ->assertNoContent();

        Storage::disk('public')->assertMissing($image->path);
        $this->assertDatabaseMissing('property_images', ['id' => $image->id]);
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function propertyPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Central Guest House',
            'property_type' => Property::TYPE_GUEST_HOUSE,
            'status' => Property::STATUS_ACTIVE,
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'country' => 'India',
            'postal_code' => '700001',
            'location' => 'Golpark',
            'address' => '12 Guest Road',
            'phone' => '+91 90000 00000',
            'email' => 'property@example.com',
            'manager_name' => 'Front Office Manager',
            'check_in_time' => '12:00',
            'check_out_time' => '11:00',
            'base_price' => '2500.00',
            'currency' => 'INR',
            'sort_order' => 0,
            'description' => 'Designed for quick booking, reliable operations, and guest comfort.',
            'amenities' => [],
        ], $overrides);
    }
}
