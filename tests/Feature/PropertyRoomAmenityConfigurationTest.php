<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\Booking;
use App\Models\Property;
use App\Models\PropertyRoomType;
use App\Models\RatePlan;
use App\Models\Room;
use App\Models\RoomAmenityOverride;
use App\Models\RoomType;
use App\Models\User;
use App\Services\Rooms\AmenityResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyRoomAmenityConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_global_category_has_independent_amenities_per_property(): void
    {
        $type = $this->type(); $one = $this->property('One'); $two = $this->property('Two');
        $wifi = Amenity::query()->where('name', 'Wi-Fi')->firstOrFail();
        $tv = Amenity::query()->where('name', 'TV')->firstOrFail();
        $first = PropertyRoomType::query()->create(['property_id'=>$one->id,'room_type_id'=>$type->id]);
        $second = PropertyRoomType::query()->create(['property_id'=>$two->id,'room_type_id'=>$type->id]);
        $first->amenities()->attach($wifi->id); $second->amenities()->attach($tv->id);

        $resolver = app(AmenityResolver::class);
        $this->assertSame(['Wi-Fi'], $resolver->forCategory($one->id, $type->id)->pluck('name')->all());
        $this->assertSame(['TV'], $resolver->forCategory($two->id, $type->id)->pluck('name')->all());
    }

    public function test_physical_room_overrides_add_and_remove_inherited_amenities(): void
    {
        $type=$this->type(); $property=$this->property('One');
        $wifi=Amenity::query()->where('name','Wi-Fi')->firstOrFail(); $tv=Amenity::query()->where('name','TV')->firstOrFail();
        $config=PropertyRoomType::query()->create(['property_id'=>$property->id,'room_type_id'=>$type->id]); $config->amenities()->attach($wifi->id);
        $room=Room::query()->create(['property_id'=>$property->id,'room_type_id'=>$type->id,'room_number'=>'101','status'=>'available']);
        $room->amenityOverrides()->create(['amenity_id'=>$wifi->id,'state'=>RoomAmenityOverride::MISSING]);
        $room->amenityOverrides()->create(['amenity_id'=>$tv->id,'state'=>RoomAmenityOverride::PRESENT]);
        $this->assertSame(['TV'], app(AmenityResolver::class)->forRoom($room)->pluck('name')->all());
    }

    public function test_admin_can_save_property_specific_category_configuration(): void
    {
        $admin=User::factory()->create(['role'=>User::ROLE_SUPER_ADMIN,'is_active'=>true]);
        $type=$this->type(); $property=$this->property('One'); $tv=Amenity::query()->where('name','TV')->firstOrFail();
        $this->actingAs($admin)->put(route('admin.room-types.properties.update',[$type,$property]),[
            'status'=>'active','max_adults'=>3,'max_children'=>1,'is_pet_friendly'=>'1','amenities'=>[$tv->id],
        ])->assertRedirect();
        $this->assertDatabaseHas('property_room_types',['property_id'=>$property->id,'room_type_id'=>$type->id,'max_adults'=>3,'is_pet_friendly'=>true]);
        $this->assertDatabaseHas('property_room_type_amenities',['amenity_id'=>$tv->id]);
    }

    public function test_category_and_physical_room_configuration_pages_render(): void
    {
        $admin=User::factory()->create(['role'=>User::ROLE_SUPER_ADMIN,'is_active'=>true]);
        $type=$this->type(); $property=$this->property('One');
        $room=Room::query()->create(['property_id'=>$property->id,'room_type_id'=>$type->id,'room_number'=>'101','status'=>'available']);
        $configured=Amenity::query()->where('name','TV')->firstOrFail();
        $notConfigured=Amenity::query()->create(['name'=>'Terrace Wi-Fi','code'=>'terrace-wifi','icon'=>'wifi','category'=>'connectivity','scope'=>'multi_scope','is_active'=>true]);
        PropertyRoomType::query()->where('property_id',$property->id)->where('room_type_id',$type->id)->firstOrFail()->amenities()->attach($configured->id);
        $this->actingAs($admin)->get(route('admin.room-types.show',$type))->assertOk()->assertSee('Configure category at One');
        $this->actingAs($admin)->withSession([\App\Support\AdminPropertyScope::SESSION_KEY=>$property->id])->get(route('admin.room-types.edit',$type))->assertOk()->assertSee('Configure Category Amenities')->assertSee('#property-config-'.$property->id, false);
        $this->actingAs($admin)->get(route('admin.rooms.edit',$room))->assertOk()->assertSee('Room amenity exceptions')->assertSee('TV')->assertDontSee($notConfigured->name);
    }

    public function test_room_page_shows_category_amenities_without_repeating_property_facilities(): void
    {
        $admin=User::factory()->create(['role'=>User::ROLE_SUPER_ADMIN,'is_active'=>true]);
        $type=$this->type(); $property=$this->property('One');
        $room=Room::query()->create(['property_id'=>$property->id,'room_type_id'=>$type->id,'room_number'=>'101','status'=>'available']);
        $parking=Amenity::query()->where('name','Parking')->firstOrFail();
        $restaurant=Amenity::query()->where('name','Restaurant')->firstOrFail();
        $tv=Amenity::query()->where('name','TV')->firstOrFail();
        $property->amenities()->attach($parking->id);
        PropertyRoomType::query()->where('property_id',$property->id)->where('room_type_id',$type->id)->firstOrFail()->amenities()->attach($tv->id);

        $this->actingAs($admin)->get(route('admin.rooms.edit',$room))
            ->assertOk()
            ->assertDontSee('aria-label="Parking status"', false)
            ->assertSee('TV')
            ->assertSee('Included by category')
            ->assertDontSee('aria-label="'.$restaurant->name.' status"', false);
    }

    private function type(): RoomType { return RoomType::query()->create(['name'=>'Deluxe','code'=>'deluxe','status'=>'active','max_adults'=>2,'max_children'=>0]); }
    private function property(string $name): Property { return Property::query()->create(['name'=>$name,'slug'=>strtolower($name),'property_type'=>'guest_house','status'=>'active','city'=>'Kolkata','address'=>'Test','country'=>'India','currency'=>'INR']); }
}
