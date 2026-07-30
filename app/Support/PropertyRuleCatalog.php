<?php

namespace App\Support;

class PropertyRuleCatalog
{
    public static function categories(): array
    {
        return [
            'must_read' => 'Must-read rules',
            'guest_profile' => 'Guest profile',
            'id_proof' => 'ID proof',
            'smoking_alcohol' => 'Smoking & alcohol',
            'food' => 'Food & dining',
            'visitors' => 'Visitors',
            'children_pets' => 'Children & pets',
            'property_use' => 'Property use & conduct',
            'accessibility' => 'Accessibility',
        ];
    }

    public static function rules(): array
    {
        return [
            'minimum_age' => ['category'=>'must_read','label'=>'Primary guest minimum age','options'=>['18'=>'Minimum 18 years','21'=>'Minimum 21 years'],'messages'=>['18'=>'The primary guest must be at least 18 years old.','21'=>'The primary guest must be at least 21 years old.'],'must'=>true],
            'local_id' => ['category'=>'id_proof','label'=>'Local ID','options'=>['allowed'=>'Allowed','not_allowed'=>'Not allowed'],'messages'=>['allowed'=>'Local government-issued ID is accepted.','not_allowed'=>'Local ID is not accepted at this property.'],'must'=>true],
            'accepted_id' => ['category'=>'id_proof','label'=>'Accepted ID documents','options'=>['government'=>'Government photo ID','passport_foreign'=>'Government ID; passport required for foreign guests'],'messages'=>['government'=>'A valid government-issued photo ID is required at check-in.','passport_foreign'=>'A valid government photo ID is required; foreign guests must present a passport and valid visa.'],'must'=>true],
            'unmarried_couples' => ['category'=>'guest_profile','label'=>'Unmarried couples','options'=>['allowed'=>'Allowed','not_allowed'=>'Not allowed'],'messages'=>['allowed'=>'Unmarried couples are welcome.','not_allowed'=>'Unmarried couples are not permitted.'],'must'=>true],
            'male_groups' => ['category'=>'guest_profile','label'=>'All-male groups','options'=>['allowed'=>'Allowed','not_allowed'=>'Not allowed','approval'=>'Prior approval required'],'messages'=>['allowed'=>'All-male groups are welcome.','not_allowed'=>'Groups consisting only of male guests are not permitted.','approval'=>'All-male group stays require prior property approval.'],'must'=>false],
            'smoking' => ['category'=>'smoking_alcohol','label'=>'Smoking','options'=>['designated'=>'Designated areas only','not_allowed'=>'Not allowed','allowed'=>'Allowed'],'messages'=>['designated'=>'Smoking is permitted only in designated areas.','not_allowed'=>'Smoking is not permitted on the property.','allowed'=>'Smoking is permitted subject to the property’s posted conditions.'],'must'=>false],
            'alcohol' => ['category'=>'smoking_alcohol','label'=>'Alcohol','options'=>['allowed'=>'Allowed','not_allowed'=>'Not allowed','licensed_only'=>'Only in licensed areas'],'messages'=>['allowed'=>'Alcohol consumption is allowed subject to applicable law.','not_allowed'=>'Alcohol consumption is not permitted.','licensed_only'=>'Alcohol may be consumed only in licensed or designated areas.'],'must'=>false],
            'outside_food' => ['category'=>'food','label'=>'Outside food','options'=>['allowed'=>'Allowed','not_allowed'=>'Not allowed'],'messages'=>['allowed'=>'Outside food is allowed.','not_allowed'=>'Outside food is not permitted.'],'must'=>false],
            'non_veg_food' => ['category'=>'food','label'=>'Non-vegetarian food','options'=>['allowed'=>'Allowed','not_allowed'=>'Not allowed'],'messages'=>['allowed'=>'Non-vegetarian food is allowed.','not_allowed'=>'Non-vegetarian food is not permitted.'],'must'=>false],
            'food_delivery' => ['category'=>'food','label'=>'Food delivery services','options'=>['allowed'=>'Allowed','reception'=>'Collect from reception','not_allowed'=>'Not allowed'],'messages'=>['allowed'=>'Food delivery services are allowed.','reception'=>'Food deliveries must be collected from reception.','not_allowed'=>'Food delivery services are not permitted at the property.'],'must'=>false],
            'in_room_dining' => ['category'=>'food','label'=>'In-room dining','options'=>['available'=>'Available','limited_hours'=>'Available during limited hours','not_available'=>'Not available'],'messages'=>['available'=>'In-room dining is available.','limited_hours'=>'In-room dining is available during the property’s service hours.','not_available'=>'In-room dining is not available.'],'must'=>false],
            'visitors' => ['category'=>'visitors','label'=>'Visitors','options'=>['allowed'=>'Allowed','registered_only'=>'Registered visitors only','not_allowed'=>'Not allowed'],'messages'=>['allowed'=>'Visitors are allowed subject to front-desk registration.','registered_only'=>'Visitors must register at reception and leave within permitted hours.','not_allowed'=>'Visitors are not permitted in guest rooms.'],'must'=>false],
            'pets' => ['category'=>'children_pets','label'=>'Pets','options'=>['allowed'=>'Allowed','approval'=>'Prior approval required','not_allowed'=>'Not allowed'],'messages'=>['allowed'=>'Pets are allowed subject to the property’s pet policy.','approval'=>'Pets require prior property approval.','not_allowed'=>'Pets are not permitted.'],'must'=>false],
            'resident_pets' => ['category'=>'children_pets','label'=>'Pets living at property','options'=>['present'=>'Yes','not_present'=>'No'],'messages'=>['present'=>'Pets live on the property.','not_present'=>'There are no resident pets at the property.'],'must'=>false],
            'children' => ['category'=>'children_pets','label'=>'Children','options'=>['allowed'=>'Allowed','not_allowed'=>'Not allowed'],'messages'=>['allowed'=>'Children are welcome; occupancy limits apply.','not_allowed'=>'Children are not accommodated at this property.'],'must'=>false],
            'parties_events' => ['category'=>'property_use','label'=>'Private parties or events','options'=>['allowed'=>'Allowed','approval'=>'Prior approval required','not_allowed'=>'Not allowed'],'messages'=>['allowed'=>'Private parties or events are allowed subject to property rules.','approval'=>'Private parties or events require prior property approval.','not_allowed'=>'Private parties or events are not permitted.'],'must'=>false],
            'quiet_hours' => ['category'=>'property_use','label'=>'Quiet hours','options'=>['22_07'=>'10 PM to 7 AM','23_07'=>'11 PM to 7 AM','none'=>'No fixed quiet hours'],'messages'=>['22_07'=>'Quiet hours are from 10 PM to 7 AM.','23_07'=>'Quiet hours are from 11 PM to 7 AM.','none'=>'The property has no fixed quiet hours; guests must avoid disturbing others.'],'must'=>false],
            'wheelchair' => ['category'=>'accessibility','label'=>'Wheelchair accessibility','options'=>['accessible'=>'Accessible','limited'=>'Limited; contact property','not_accessible'=>'Not accessible'],'messages'=>['accessible'=>'The property provides wheelchair-accessible facilities.','limited'=>'Accessibility is limited; please contact the property before booking.','not_accessible'=>'The property is not wheelchair accessible.'],'must'=>false],
        ];
    }
}
