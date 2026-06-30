<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\City;

class IndianStatesAndCitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Indian States and their major cities
        $statesData = [
            ['name' => 'Andhra Pradesh', 'code' => 'AP', 'cities' => ['Visakhapatnam', 'Vijayawada', 'Tirupati', 'Nellore']],
            ['name' => 'Arunachal Pradesh', 'code' => 'AR', 'cities' => ['Itanagar', 'Naharlagun', 'Tawang']],
            ['name' => 'Assam', 'code' => 'AS', 'cities' => ['Guwahati', 'Silchar', 'Dibrugarh', 'Nagaon']],
            ['name' => 'Bihar', 'code' => 'BR', 'cities' => ['Patna', 'Gaya', 'Bhagalpur', 'Madhubani']],
            ['name' => 'Chhattisgarh', 'code' => 'CT', 'cities' => ['Raipur', 'Bhilai', 'Durg', 'Bilaspur']],
            ['name' => 'Goa', 'code' => 'GA', 'cities' => ['Panaji', 'Margao', 'Vasco da Gama']],
            ['name' => 'Gujarat', 'code' => 'GJ', 'cities' => ['Ahmedabad', 'Surat', 'Vadodara', 'Rajkot', 'Gandhinagar']],
            ['name' => 'Haryana', 'code' => 'HR', 'cities' => ['Faridabad', 'Gurgaon', 'Hisar', 'Rohtak', 'Panipat']],
            ['name' => 'Himachal Pradesh', 'code' => 'HP', 'cities' => ['Shimla', 'Solan', 'Mandi', 'Kangra']],
            ['name' => 'Jharkhand', 'code' => 'JH', 'cities' => ['Ranchi', 'Dhanbad', 'Jamshedpur', 'Giridih']],
            ['name' => 'Karnataka', 'code' => 'KA', 'cities' => ['Bangalore', 'Mysore', 'Mangalore', 'Belgaum', 'Hubli']],
            ['name' => 'Kerala', 'code' => 'KL', 'cities' => ['Kochi', 'Thiruvananthapuram', 'Kozhikode', 'Thrissur']],
            ['name' => 'Madhya Pradesh', 'code' => 'MP', 'cities' => ['Indore', 'Bhopal', 'Jabalpur', 'Ujjain', 'Gwalior']],
            ['name' => 'Maharashtra', 'code' => 'MH', 'cities' => ['Mumbai', 'Pune', 'Nagpur', 'Aurangabad', 'Nashik', 'Surat']],
            ['name' => 'Manipur', 'code' => 'MN', 'cities' => ['Imphal', 'Bishnupur', 'Thoubal']],
            ['name' => 'Meghalaya', 'code' => 'ML', 'cities' => ['Shillong', 'Tura', 'Nongpoh']],
            ['name' => 'Mizoram', 'code' => 'MZ', 'cities' => ['Aizawl', 'Lunglei', 'Saiha']],
            ['name' => 'Nagaland', 'code' => 'NL', 'cities' => ['Kohima', 'Dimapur', 'Mokokchung']],
            ['name' => 'Odisha', 'code' => 'OR', 'cities' => ['Bhubaneswar', 'Rourkela', 'Cuttack', 'Sambalpur']],
            ['name' => 'Punjab', 'code' => 'PB', 'cities' => ['Chandigarh', 'Ludhiana', 'Amritsar', 'Patiala', 'Jalandhar']],
            ['name' => 'Rajasthan', 'code' => 'RJ', 'cities' => ['Jaipur', 'Jodhpur', 'Kota', 'Ajmer', 'Udaipur', 'Bikaner']],
            ['name' => 'Sikkim', 'code' => 'SK', 'cities' => ['Gangtok', 'Namchi', 'Pelling']],
            ['name' => 'Tamil Nadu', 'code' => 'TN', 'cities' => ['Chennai', 'Coimbatore', 'Madurai', 'Salem', 'Tiruchirappalli']],
            ['name' => 'Telangana', 'code' => 'TG', 'cities' => ['Hyderabad', 'Secunderabad', 'Warangal', 'Karimnagar']],
            ['name' => 'Tripura', 'code' => 'TR', 'cities' => ['Agartala', 'Udaipur', 'Dharmanagar']],
            ['name' => 'Uttar Pradesh', 'code' => 'UP', 'cities' => ['Lucknow', 'Kanpur', 'Ghaziabad', 'Agra', 'Varanasi', 'Noida']],
            ['name' => 'Uttarakhand', 'code' => 'UK', 'cities' => ['Dehradun', 'Haridwar', 'Nainital', 'Almora']],
            ['name' => 'West Bengal', 'code' => 'WB', 'cities' => ['Kolkata', 'Howrah', 'Durgapur', 'Siliguri', 'Asansol']],
            ['name' => 'Andaman and Nicobar Islands', 'code' => 'AN', 'cities' => ['Port Blair', 'Rangat', 'Car Nicobar']],
            ['name' => 'Chandigarh', 'code' => 'CH', 'cities' => ['Chandigarh']],
            ['name' => 'Dadra and Nagar Haveli', 'code' => 'DN', 'cities' => ['Silvassa', 'Daman', 'Diu']],
            ['name' => 'Daman and Diu', 'code' => 'DD', 'cities' => ['Daman', 'Diu']],
            ['name' => 'Lakshadweep', 'code' => 'LD', 'cities' => ['Kavaratti', 'Kalpeni', 'Minicoy']],
            ['name' => 'Delhi', 'code' => 'DL', 'cities' => ['New Delhi', 'Delhi']],
            ['name' => 'Puducherry', 'code' => 'PY', 'cities' => ['Puducherry', 'Yanam', 'Mahe', 'Karaikal']],
        ];

        foreach ($statesData as $stateData) {
            $state = State::create([
                'country_id' => 1, // India
                'name' => $stateData['name'],
                'code' => $stateData['code'],
            ]);

            // Create cities for each state
            foreach ($stateData['cities'] as $cityName) {
                City::create([
                    'country_id' => 1, // India
                    'state_id' => $state->id,
                    'name' => $cityName,
                ]);
            }
        }
    }
}
