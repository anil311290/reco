<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\State;
use App\Models\City;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $india = Country::firstOrCreate(
            ['iso2' => 'IN'],
            [
                'name' => 'India',
                'iso3' => 'IND',
                'phone_code' => '+91',
                'currency' => 'INR',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        $statesData = $this->getStatesAndCities();
        $stateModels = [];

        foreach ($statesData as $index => $data) {
            $state = State::firstOrCreate(
                ['country_id' => $india->id, 'code' => $data['code']],
                [
                    'name' => $data['name'],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
            $stateModels[$data['code']] = $state;

            if (!empty($data['cities'])) {
                foreach ($data['cities'] as $ci => $cityName) {
                    City::firstOrCreate(
                        ['state_id' => $state->id, 'name' => $cityName],
                        [
                            'country_id' => $india->id,
                            'is_active' => true,
                            'sort_order' => $ci + 1,
                        ]
                    );
                }
            }
        }

        // Other countries
        $otherCountries = [
            ['name' => 'United States', 'iso2' => 'US', 'iso3' => 'USA', 'phone_code' => '+1', 'currency' => 'USD'],
            ['name' => 'United Kingdom', 'iso2' => 'GB', 'iso3' => 'GBR', 'phone_code' => '+44', 'currency' => 'GBP'],
            ['name' => 'United Arab Emirates', 'iso2' => 'AE', 'iso3' => 'ARE', 'phone_code' => '+971', 'currency' => 'AED'],
            ['name' => 'Singapore', 'iso2' => 'SG', 'iso3' => 'SGP', 'phone_code' => '+65', 'currency' => 'SGD'],
            ['name' => 'Australia', 'iso2' => 'AU', 'iso3' => 'AUS', 'phone_code' => '+61', 'currency' => 'AUD'],
            ['name' => 'Canada', 'iso2' => 'CA', 'iso3' => 'CAN', 'phone_code' => '+1', 'currency' => 'CAD'],
            ['name' => 'Germany', 'iso2' => 'DE', 'iso3' => 'DEU', 'phone_code' => '+49', 'currency' => 'EUR'],
            ['name' => 'Japan', 'iso2' => 'JP', 'iso3' => 'JPN', 'phone_code' => '+81', 'currency' => 'JPY'],
        ];

        foreach ($otherCountries as $i => $c) {
            Country::firstOrCreate(['iso2' => $c['iso2']], array_merge($c, ['is_active' => true, 'sort_order' => $i + 2]));
        }

    }

    private function getStatesAndCities(): array
    {
        return [
            // ── States ──────────────────────────────────────────
            [
                'name' => 'Andhra Pradesh', 'code' => 'AP',
                'cities' => ['Amaravati', 'Visakhapatnam', 'Vijayawada', 'Guntur', 'Nellore', 'Kurnool', 'Rajahmundry', 'Tirupati', 'Kadapa', 'Anantapur', 'Kakinada', 'Eluru', 'Ongole', 'Machilipatnam', 'Srikakulam', 'Vizianagaram', 'Chittoor', 'Bhimavaram', 'Tadepalligudem', 'Hindupur'],
            ],
            [
                'name' => 'Arunachal Pradesh', 'code' => 'AR',
                'cities' => ['Itanagar', 'Naharlagun', 'Pasighat', 'Tawang', 'Ziro', 'Bomdila', 'Tezu', 'Along', 'Changlang', 'Daporijo', 'Khonsa', 'Roing', 'Seppa', 'Yingkiong'],
            ],
            [
                'name' => 'Assam', 'code' => 'AS',
                'cities' => ['Guwahati', 'Silchar', 'Dibrugarh', 'Jorhat', 'Nagaon', 'Tinsukia', 'Tezpur', 'Bongaigaon', 'Karimganj', 'Diphu', 'Goalpara', 'Barpeta', 'Dhubri', 'Haflong', 'Hailakandi', 'Kokrajhar', 'Lanka', 'Mangaldoi', 'Morigaon', 'North Lakhimpur'],
            ],
            [
                'name' => 'Bihar', 'code' => 'BR',
                'cities' => ['Patna', 'Gaya', 'Muzaffarpur', 'Bhagalpur', 'Darbhanga', 'Purnia', 'Arrah', 'Begusarai', 'Bettiah', 'Bihar Sharif', 'Chhapra', 'Dehri', 'Hajipur', 'Jamalpur', 'Katihar', 'Kishanganj', 'Motihari', 'Munger', 'Saharsa', 'Samastipur', 'Sasaram', 'Siwan'],
            ],
            [
                'name' => 'Chhattisgarh', 'code' => 'CG',
                'cities' => ['Raipur', 'Bhilai', 'Bilaspur', 'Korba', 'Durg', 'Rajnandgaon', 'Jagdalpur', 'Raigarh', 'Ambikapur', 'Dhamtari', 'Janjgir', 'Kawardha', 'Kondagaon', 'Mahasamund', 'Naila Janjgir'],
            ],
            [
                'name' => 'Goa', 'code' => 'GA',
                'cities' => ['Panaji', 'Margao', 'Vasco da Gama', 'Mapusa', 'Ponda', 'Mormugao', 'Bicholim', 'Curchorem', 'Cuncolim', 'Valpoi', 'Quepem', 'Sanguem', 'Canacona', 'Pernem'],
            ],
            [
                'name' => 'Gujarat', 'code' => 'GJ',
                'cities' => ['Ahmedabad', 'Surat', 'Vadodara', 'Rajkot', 'Gandhinagar', 'Bhavnagar', 'Jamnagar', 'Junagadh', 'Anand', 'Bharuch', 'Bhuj', 'Dahod', 'Godhra', 'Himatnagar', 'Idar', 'Kheda', 'Mehsana', 'Navsari', 'Palanpur', 'Patan', 'Porbandar', 'Surendranagar', 'Valsad', 'Veraval', 'Vapi'],
            ],
            [
                'name' => 'Haryana', 'code' => 'HR',
                'cities' => ['Gurugram', 'Faridabad', 'Panipat', 'Ambala', 'Yamunanagar', 'Rohtak', 'Hisar', 'Karnal', 'Sonipat', 'Panchkula', 'Bhiwani', 'Sirsa', 'Bahadurgarh', 'Jind', 'Thanesar', 'Kaithal', 'Rewari', 'Narnaul', 'Mahendragarh'],
            ],
            [
                'name' => 'Himachal Pradesh', 'code' => 'HP',
                'cities' => ['Shimla', 'Manali', 'Dharamshala', 'Mandi', 'Solan', 'Kullu', 'Kangra', 'Bilaspur', 'Chamba', 'Hamirpur', 'Kinnaur', 'Kullu', 'Lahaul and Spiti', 'Nahan', 'Palampur', 'Sirmaur', 'Sundernagar', 'Una'],
            ],
            [
                'name' => 'Jharkhand', 'code' => 'JH',
                'cities' => ['Ranchi', 'Jamshedpur', 'Dhanbad', 'Bokaro', 'Deoghar', 'Hazaribagh', 'Giridih', 'Ramgarh', 'Medininagar', 'Chatra', 'Chaibasa', 'Dumka', 'Godda', 'Gumla', 'Lohardaga', 'Pakur', 'Sahebganj', 'Simdega'],
            ],
            [
                'name' => 'Karnataka', 'code' => 'KA',
                'cities' => ['Bengaluru', 'Mysuru', 'Mangaluru', 'Hubballi-Dharwad', 'Belagavi', 'Kalaburagi', 'Davanagere', 'Ballari', 'Vijayapura', 'Shivamogga', 'Tumakuru', 'Raichur', 'Hassan', 'Mandya', 'Chikkamagaluru', 'Udupi', 'Karwar', 'Kolar', 'Ramanagara', 'Chitradurga', 'Koppal', 'Gadag', 'Haveri', 'Bidar', 'Yadgir', 'Bagalkot', 'Chamarajanagar', 'Kodagu', 'Dakshina Kannada', 'Uttara Kannada'],
            ],
            [
                'name' => 'Kerala', 'code' => 'KL',
                'cities' => ['Thiruvananthapuram', 'Kochi', 'Kozhikode', 'Thrissur', 'Kollam', 'Palakkad', 'Alappuzha', 'Kottayam', 'Kannur', 'Kasaragod', 'Malappuram', 'Pathanamthitta', 'Idukki', 'Ernakulam', 'Wayanad', 'Ponnani', 'Thalassery', 'Kayamkulam', 'Cherthala', 'Attingal'],
            ],
            [
                'name' => 'Madhya Pradesh', 'code' => 'MP',
                'cities' => ['Bhopal', 'Indore', 'Jabalpur', 'Gwalior', 'Ujjain', 'Sagar', 'Dewas', 'Satna', 'Ratlam', 'Rewa', 'Singrauli', 'Katni', 'Khandwa', 'Chhindwara', 'Balaghat', 'Betul', 'Bhind', 'Chhatarpur', 'Damoh', 'Datia', 'Dhar', 'Dindori', 'Harda', 'Hoshangabad', 'Mandla', 'Mandsaur', 'Morena', 'Narsinghpur', 'Neemuch', 'Panna', 'Raisen', 'Rajgarh', 'Sehore', 'Seoni', 'Shahdol', 'Shajapur', 'Shivpuri', 'Sidhi', 'Tikamgarh', 'Vidisha'],
            ],
            [
                'name' => 'Maharashtra', 'code' => 'MH',
                'cities' => ['Mumbai', 'Pune', 'Nagpur', 'Thane', 'Nashik', 'Aurangabad', 'Solapur', 'Kolhapur', 'Amravati', 'Nanded', 'Sangli', 'Jalgaon', 'Akola', 'Latur', 'Dhule', 'Ahmednagar', 'Chandrapur', 'Parbhani', 'Ichalkaranji', 'Jalna', 'Bhusawal', 'Panvel', 'Satara', 'Beed', 'Yavatmal', 'Kamptee', 'Gondia', 'Barshi', 'Wardha', 'Washim', 'Baramati', 'Alibaug', 'Karad', 'Ratnagiri', 'Malegaon', 'Osmanabad', 'Hinganghat', 'Pusad'],
            ],
            [
                'name' => 'Manipur', 'code' => 'MN',
                'cities' => ['Imphal', 'Thoubal', 'Bishnupur', 'Churachandpur', 'Chandel', 'Senapati', 'Ukhrul', 'Tamenglong', 'Jiribam', 'Kakching', 'Moreh', 'Moirang'],
            ],
            [
                'name' => 'Meghalaya', 'code' => 'ML',
                'cities' => ['Shillong', 'Tura', 'Jowai', 'Nongstoin', 'Williamnagar', 'Baghmara', 'Resubelpara', 'Nongpoh'],
            ],
            [
                'name' => 'Mizoram', 'code' => 'MZ',
                'cities' => ['Aizawl', 'Lunglei', 'Saiha', 'Champhai', 'Kolasib', 'Serchhip', 'Lawngtlai', 'Mamit'],
            ],
            [
                'name' => 'Nagaland', 'code' => 'NL',
                'cities' => ['Kohima', 'Dimapur', 'Mokokchung', 'Tuensang', 'Wokha', 'Zunheboto', 'Mon', 'Phek', 'Kiphire', 'Longleng', 'Peren'],
            ],
            [
                'name' => 'Odisha', 'code' => 'OD',
                'cities' => ['Bhubaneswar', 'Cuttack', 'Rourkela', 'Berhampur', 'Sambalpur', 'Puri', 'Balasore', 'Bhadrak', 'Baripada', 'Jharsuguda', 'Angul', 'Dhenkanal', 'Jeypore', 'Koraput', 'Rayagada', 'Sundargarh', 'Keonjhar', 'Jajpur', 'Kendrapara', 'Paradip'],
            ],
            [
                'name' => 'Punjab', 'code' => 'PB',
                'cities' => ['Ludhiana', 'Amritsar', 'Jalandhar', 'Patiala', 'Bathinda', 'Mohali', 'Hoshiarpur', 'Batala', 'Pathankot', 'Moga', 'Abohar', 'Malerkotla', 'Khanna', 'Phagwara', 'Muktsar', 'Barnala', 'Rajpura', 'Firozpur', 'Kapurthala', 'Faridkot', 'Gurdaspur', 'Sangrur', 'Nawanshahr', 'Ropar', 'Tarn Taran'],
            ],
            [
                'name' => 'Rajasthan', 'code' => 'RJ',
                'cities' => ['Jaipur', 'Jodhpur', 'Udaipur', 'Kota', 'Ajmer', 'Bikaner', 'Alwar', 'Bharatpur', 'Bhilwara', 'Sri Ganganagar', 'Sikar', 'Pali', 'Tonk', 'Kishangarh', 'Beawar', 'Hanumangarh', 'Dholpur', 'Gangapur City', 'Churu', 'Jhunjhunu', 'Baran', 'Dausa', 'Rajsamand', 'Bundi', 'Jaisalmer', 'Barmer', 'Nagaur', 'Pratapgarh'],
            ],
            [
                'name' => 'Sikkim', 'code' => 'SK',
                'cities' => ['Gangtok', 'Namchi', 'Gyalshing', 'Mangan', 'Rangpo', 'Singtam', 'Jorethang'],
            ],
            [
                'name' => 'Tamil Nadu', 'code' => 'TN',
                'cities' => ['Chennai', 'Coimbatore', 'Madurai', 'Tiruchirappalli', 'Salem', 'Tirunelveli', 'Erode', 'Vellore', 'Thoothukudi', 'Thanjavur', 'Dindigul', 'Kancheepuram', 'Karur', 'Nagercoil', 'Kumbakonam', 'Sivakasi', 'Tiruppur', 'Namakkal', 'Krishnagiri', 'Cuddalore', 'Kanchipuram', 'Nagapattinam', 'Ramanathapuram', 'Sivaganga', 'Tiruvannamalai', 'Villupuram', 'Virudhunagar', 'Ariyalur', 'Perambalur', 'Tiruvarur', 'Theni', 'Nilgiris', 'Pudukkottai'],
            ],
            [
                'name' => 'Telangana', 'code' => 'TS',
                'cities' => ['Hyderabad', 'Warangal', 'Nizamabad', 'Karimnagar', 'Ramagundam', 'Khammam', 'Mahbubnagar', 'Nalgonda', 'Adilabad', 'Suryapet', 'Siddipet', 'Miryalaguda', 'Jagtial', 'Mancherial', 'Nirmal', 'Kamareddy', 'Bhadrachalam', 'Zaheerabad', 'Vikarabad'],
            ],
            [
                'name' => 'Tripura', 'code' => 'TR',
                'cities' => ['Agartala', 'Udaipur', 'Dharmanagar', 'Kailasahar', 'Belonia', 'Ambassa', 'Khowai', 'Teliamura', 'Sabroom', 'Sonamura'],
            ],
            [
                'name' => 'Uttar Pradesh', 'code' => 'UP',
                'cities' => ['Lucknow', 'Noida', 'Kanpur', 'Agra', 'Varanasi', 'Meerut', 'Prayagraj', 'Ghaziabad', 'Bareilly', 'Aligarh', 'Moradabad', 'Saharanpur', 'Gorakhpur', 'Firozabad', 'Jhansi', 'Muzaffarnagar', 'Mathura', 'Ayodhya', 'Shahjahanpur', 'Rampur', 'Farrukhabad', 'Mau', 'Hapur', 'Etawah', 'Mirzapur', 'Bulandshahr', 'Sambhal', 'Amroha', 'Hardoi', 'Fatehpur', 'Raebareli', 'Orai', 'Sitapur', 'Bahraich', 'Modinagar', 'Unnao', 'Jaunpur', 'Lakhimpur', 'Hathras', 'Banda', 'Pilibhit', 'Barabanki', 'Khurja', 'Gonda', 'Mainpuri', 'Lalitpur', 'Etah', 'Deoria', 'Ghazipur', 'Sultanpur'],
            ],
            [
                'name' => 'Uttarakhand', 'code' => 'UK',
                'cities' => ['Dehradun', 'Haridwar', 'Haldwani', 'Roorkee', 'Rudrapur', 'Kashipur', 'Rishikesh', 'Kotdwar', 'Mussoorie', 'Nainital', 'Almora', 'Pithoragarh', 'Srinagar', 'Tehri', 'Chamoli', 'Bageshwar', 'Champawat', 'Udham Singh Nagar', 'Pauri', 'Rudraprayag'],
            ],
            [
                'name' => 'West Bengal', 'code' => 'WB',
                'cities' => ['Kolkata', 'Howrah', 'Durgapur', 'Asansol', 'Siliguri', 'Bardhaman', 'Malda', 'Baharampur', 'Habra', 'Kharagpur', 'Shantiniketan', 'Krishnanagar', 'Nabadwip', 'Raiganj', 'Balurghat', 'Cooch Behar', 'Alipurduar', 'Jalpaiguri', 'Purulia', 'Bankura', 'Bishnupur', 'Darjeeling', 'Kalimpong', 'Medinipur', 'Tamluk', 'Diamond Harbour', 'Barrackpore', 'Barasat', 'Basirhat', 'Chandannagar', 'Serampore', 'Rishra', 'Hooghly'],
            ],

            // ── Union Territories ───────────────────────────────
            [
                'name' => 'Delhi', 'code' => 'DL',
                'cities' => ['New Delhi', 'Delhi', 'Noida', 'Gurgaon', 'Faridabad', 'Ghaziabad'],
            ],
            [
                'name' => 'Jammu and Kashmir', 'code' => 'JK',
                'cities' => ['Srinagar', 'Jammu', 'Anantnag', 'Baramulla', 'Sopore', 'Kathua', 'Udhampur', 'Punch', 'Rajouri', 'Kupwara', 'Budgam', 'Pulwama', 'Kulgam', 'Shopian', 'Bandipora', 'Ganderbal', 'Doda', 'Kishtwar', 'Ramban', 'Reasi', 'Samba'],
            ],
            [
                'name' => 'Ladakh', 'code' => 'LA',
                'cities' => ['Leh', 'Kargil'],
            ],
            [
                'name' => 'Chandigarh', 'code' => 'CH',
                'cities' => ['Chandigarh'],
            ],
            [
                'name' => 'Puducherry', 'code' => 'PY',
                'cities' => ['Puducherry', 'Karaikal', 'Mahe', 'Yanam'],
            ],
            [
                'name' => 'Andaman and Nicobar Islands', 'code' => 'AN',
                'cities' => ['Port Blair'],
            ],
            [
                'name' => 'Dadra and Nagar Haveli and Daman and Diu', 'code' => 'DD',
                'cities' => ['Daman', 'Diu', 'Silvassa'],
            ],
            [
                'name' => 'Lakshadweep', 'code' => 'LD',
                'cities' => ['Kavaratti'],
            ],
        ];
    }
}
