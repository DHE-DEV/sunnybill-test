<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SolarPlant;
use App\Models\SolarInverter;
use App\Models\SolarModule;
use App\Models\SolarBattery;

class SolarPlantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Beispiel-Solaranlage 1 - Einfamilienhaus
        $plant1 = SolarPlant::create([
            'name' => 'Solaranlage Musterstraße 1',
            'location' => 'Musterstraße 1, 12345 Berlin',
            'description' => 'Moderne Solaranlage auf Einfamilienhaus mit Süd-Ausrichtung',
            'planned_installation_date' => now()->subMonths(7),
            'installation_date' => now()->subMonths(6),
            'planned_commissioning_date' => now()->subMonths(6),
            'commissioning_date' => now()->subMonths(5),
            'total_capacity_kw' => 9.920,
            'panel_count' => 32,
            'inverter_count' => 1,
            'battery_capacity_kwh' => 10.0,
            'expected_annual_yield_kwh' => 9500.0,
            'total_investment' => 25000.00,
            'annual_operating_costs' => 300.00,
            'feed_in_tariff_per_kwh' => 0.0825,
            'electricity_price_per_kwh' => 0.325,
            'status' => 'active',
            'is_active' => true,
        ]);

        // Wechselrichter für Anlage 1
        SolarInverter::create([
            'solar_plant_id' => $plant1->id,
            'fusion_solar_device_id' => 'INV-001-EFH',
            'name' => 'Huawei SUN2000-10KTL-M1',
            'model' => 'SUN2000-10KTL-M1',
            'serial_number' => 'HW2023001234',
            'manufacturer' => 'Huawei',
            'rated_power_kw' => 10.0,
            'efficiency_percent' => 98.6,
            'installation_date' => $plant1->installation_date,
            'firmware_version' => 'V100R001C00SPC124',
            'status' => 'normal',
            'is_active' => true,
            'input_voltage_range' => '200-1000V',
            'output_voltage' => '230/400V',
            'max_dc_current' => '12.5A',
            'max_ac_current' => '16A',
            'protection_class' => 'IP65',
            'cooling_method' => 'Natural convection',
            'dimensions' => '365×365×156mm',
            'weight_kg' => 17.0,
            'current_power_kw' => 8.5,
            'current_voltage_v' => 380.0,
            'current_current_a' => 14.2,
            'current_frequency_hz' => 50.0,
            'current_temperature_c' => 45.0,
            'daily_yield_kwh' => 42.5,
            'total_yield_kwh' => 7850.0,
            'last_sync_at' => now()->subHours(1),
        ]);

        // Solarmodule für Anlage 1 (32 Module)
        for ($i = 1; $i <= 32; $i++) {
            $stringNumber = ceil($i / 16); // 2 Strings mit je 16 Modulen
            $positionInString = (($i - 1) % 16) + 1;
            
            SolarModule::create([
                'solar_plant_id' => $plant1->id,
                'fusion_solar_device_id' => "MOD-001-" . sprintf('%03d', $i),
                'name' => "Solarmodul Süddach #{$i}",
                'model' => 'JA Solar JAM72S30-545/MR',
                'serial_number' => "JA2023" . sprintf('%06d', $i) . "",
                'manufacturer' => 'JA Solar',
                'rated_power_wp' => 545,
                'efficiency_percent' => 21.2,
                'installation_date' => $plant1->installation_date,
                'status' => 'normal',
                'is_active' => true,
                'cell_type' => 'perc',
                'module_type' => 'Bifacial',
                'voltage_vmp' => 41.8,
                'current_imp' => 13.04,
                'voltage_voc' => 50.1,
                'current_isc' => 13.78,
                'temperature_coefficient' => -0.35,
                'dimensions' => '2279×1134×35mm',
                'weight_kg' => 27.5,
                'frame_color' => 'Silver',
                'glass_type' => 'Anti-reflective tempered glass',
                'string_number' => $stringNumber,
                'position_in_string' => $positionInString,
                'orientation_degrees' => 180, // Süd
                'tilt_degrees' => 35,
                'shading_factor' => 0.0,
                'current_power_w' => 485.0,
                'current_voltage_v' => 40.2,
                'current_current_a' => 12.1,
                'current_temperature_c' => 42.0,
                'daily_yield_kwh' => 1.85,
                'total_yield_kwh' => 285.0,
                'last_sync_at' => now()->subHours(1),
            ]);
        }

        // Batteriespeicher für Anlage 1
        SolarBattery::create([
            'solar_plant_id' => $plant1->id,
            'fusion_solar_device_id' => 'BAT-001-EFH',
            'name' => 'Huawei LUNA2000-10-S0',
            'model' => 'LUNA2000-10-S0',
            'serial_number' => 'HWB2023001234',
            'manufacturer' => 'Huawei',
            'capacity_kwh' => 10.0,
            'usable_capacity_kwh' => 9.5,
            'rated_power_kw' => 5.0,
            'installation_date' => $plant1->installation_date,
            'status' => 'normal',
            'is_active' => true,
            'battery_type' => 'Lithium Iron Phosphate',
            'chemistry' => 'lifepo4',
            'nominal_voltage_v' => 51.2,
            'max_charge_power_kw' => 5.0,
            'max_discharge_power_kw' => 5.0,
            'efficiency_percent' => 95.0,
            'cycle_life' => 6000,
            'warranty_years' => 10,
            'operating_temp_min' => -10,
            'operating_temp_max' => 45,
            'dimensions' => '670×600×240mm',
            'weight_kg' => 75.0,
            'protection_class' => 'IP65',
            'current_soc_percent' => 85.0,
            'current_voltage_v' => 52.1,
            'current_current_a' => 15.0,
            'current_power_kw' => 0.8,
            'current_temperature_c' => 25.0,
            'charge_cycles' => 450,
            'daily_charge_kwh' => 8.5,
            'daily_discharge_kwh' => 7.2,
            'total_charge_kwh' => 2850.0,
            'total_discharge_kwh' => 2420.0,
            'health_percent' => 98.5,
            'remaining_capacity_kwh' => 9.85,
            'degradation_percent' => 1.5,
            'last_sync_at' => now()->subHours(1),
        ]);

        // Notizen für Anlage 1
        $plant1->notes()->create([
            'title' => 'Installation abgeschlossen',
            'content' => 'Die Installation wurde erfolgreich abgeschlossen. Alle 32 Module sind ordnungsgemäß montiert und der Wechselrichter ist konfiguriert. Erste Messungen zeigen optimale Leistungswerte.',
            'type' => 'installation',
            'user_id' => 1,
            'is_favorite' => true,
            'sort_order' => 1,
        ]);

        $plant1->notes()->create([
            'title' => 'Erste Wartung durchgeführt',
            'content' => 'Routinewartung nach 3 Monaten Betrieb. Alle Komponenten funktionieren einwandfrei. Reinigung der Module durchgeführt, Ertragssteigerung von 5% festgestellt.',
            'type' => 'maintenance',
            'user_id' => 1,
            'is_favorite' => false,
            'sort_order' => 0,
        ]);

        // Meilensteine für Anlage 1
        $plant1->milestones()->create([
            'title' => 'Baugenehmigung erhalten',
            'description' => 'Offizielle Baugenehmigung von der Gemeinde erhalten',
            'planned_date' => now()->subMonths(8),
            'actual_date' => now()->subMonths(8)->addDays(3),
            'status' => 'completed',
            'sort_order' => 1,
        ]);

        $plant1->milestones()->create([
            'title' => 'Material bestellt',
            'description' => 'Alle Solarmodule, Wechselrichter und Montagesystem bestellt',
            'planned_date' => now()->subMonths(7)->addDays(10),
            'actual_date' => now()->subMonths(7)->addDays(12),
            'status' => 'completed',
            'sort_order' => 2,
        ]);

        $plant1->milestones()->create([
            'title' => 'Installation abgeschlossen',
            'description' => 'Montage aller Module und Wechselrichter auf dem Dach',
            'planned_date' => now()->subMonths(7),
            'actual_date' => now()->subMonths(6),
            'status' => 'completed',
            'sort_order' => 3,
        ]);

        $plant1->milestones()->create([
            'title' => 'Netzanschluss hergestellt',
            'description' => 'Anschluss an das öffentliche Stromnetz durch Netzbetreiber',
            'planned_date' => now()->subMonths(6),
            'actual_date' => now()->subMonths(5),
            'status' => 'completed',
            'sort_order' => 4,
        ]);

        // Beispiel-Solaranlage 2 - Gewerbeanlage
        $plant2 = SolarPlant::create([
            'name' => 'Gewerbeanlage Industriepark',
            'location' => 'Industriestraße 15, 80331 München',
            'description' => 'Große Gewerbeanlage auf Hallendach mit Ost-West-Ausrichtung',
            'planned_installation_date' => now()->subMonths(9),
            'installation_date' => now()->subMonths(8),
            'planned_commissioning_date' => now()->subMonths(8),
            'commissioning_date' => now()->subMonths(7),
            'total_capacity_kw' => 49.500,
            'panel_count' => 150,
            'inverter_count' => 3,
            'battery_capacity_kwh' => 25.0,
            'expected_annual_yield_kwh' => 47000.0,
            'total_investment' => 85000.00,
            'annual_operating_costs' => 800.00,
            'feed_in_tariff_per_kwh' => 0.0825,
            'electricity_price_per_kwh' => 0.285,
            'status' => 'active',
            'is_active' => true,
        ]);

        // Wechselrichter für Anlage 2 (3 Stück)
        for ($i = 1; $i <= 3; $i++) {
            SolarInverter::create([
                'solar_plant_id' => $plant2->id,
                'fusion_solar_device_id' => "INV-002-" . sprintf('%03d', $i) . "",
                'name' => "Huawei SUN2000-17KTL-M2 #{$i}",
                'model' => 'SUN2000-17KTL-M2',
                'serial_number' => "HW2023" . sprintf('%06d', $i) . "",
                'manufacturer' => 'Huawei',
                'rated_power_kw' => 17.0,
                'efficiency_percent' => 98.8,
                'installation_date' => $plant2->installation_date,
                'firmware_version' => 'V100R001C00SPC125',
                'status' => 'normal',
                'is_active' => true,
                'input_voltage_range' => '200-1000V',
                'output_voltage' => '400V',
                'max_dc_current' => '22A',
                'max_ac_current' => '25A',
                'protection_class' => 'IP65',
                'cooling_method' => 'Intelligent fan cooling',
                'dimensions' => '525×470×166mm',
                'weight_kg' => 26.0,
                'current_power_kw' => 15.2,
                'current_voltage_v' => 400.0,
                'current_current_a' => 22.0,
                'current_frequency_hz' => 50.0,
                'current_temperature_c' => 48.0,
                'daily_yield_kwh' => 68.5,
                'total_yield_kwh' => 12850.0,
                'last_sync_at' => now()->subHours(1),
            ]);
        }

        // Solarmodule für Anlage 2 (150 Module)
        for ($i = 1; $i <= 150; $i++) {
            $stringNumber = ceil($i / 25); // 6 Strings mit je 25 Modulen
            $positionInString = (($i - 1) % 25) + 1;
            $orientation = $i <= 75 ? 90 : 270; // Erste 75 Module Ost, Rest West
            
            SolarModule::create([
                'solar_plant_id' => $plant2->id,
                'fusion_solar_device_id' => "MOD-002-" . sprintf('%03d', $i) . "",
                'name' => "Solarmodul Halle #{$i}",
                'model' => 'Trina Solar TSM-NEG9R.28-545W',
                'serial_number' => "TR2023" . sprintf('%06d', $i) . "",
                'manufacturer' => 'Trina Solar',
                'rated_power_wp' => 545,
                'efficiency_percent' => 21.0,
                'installation_date' => $plant2->installation_date,
                'status' => 'normal',
                'is_active' => true,
                'cell_type' => 'perc',
                'module_type' => 'Standard',
                'voltage_vmp' => 41.7,
                'current_imp' => 13.07,
                'voltage_voc' => 49.8,
                'current_isc' => 13.85,
                'temperature_coefficient' => -0.34,
                'dimensions' => '2279×1134×35mm',
                'weight_kg' => 27.8,
                'frame_color' => 'Black',
                'glass_type' => 'Anti-reflective tempered glass',
                'string_number' => $stringNumber,
                'position_in_string' => $positionInString,
                'orientation_degrees' => $orientation,
                'tilt_degrees' => 15,
                'shading_factor' => 0.0,
                'current_power_w' => 465.0,
                'current_voltage_v' => 39.8,
                'current_current_a' => 11.7,
                'current_temperature_c' => 45.0,
                'daily_yield_kwh' => 1.75,
                'total_yield_kwh' => 420.0,
                'last_sync_at' => now()->subHours(1),
            ]);
        }

        // Batteriespeicher für Anlage 2
        SolarBattery::create([
            'solar_plant_id' => $plant2->id,
            'fusion_solar_device_id' => 'BAT-002-GEW',
            'name' => 'Huawei LUNA2000-25-S0',
            'model' => 'LUNA2000-25-S0',
            'serial_number' => 'HWB2023002345',
            'manufacturer' => 'Huawei',
            'capacity_kwh' => 25.0,
            'usable_capacity_kwh' => 23.75,
            'rated_power_kw' => 12.5,
            'installation_date' => $plant2->installation_date,
            'status' => 'normal',
            'is_active' => true,
            'battery_type' => 'Lithium Iron Phosphate',
            'chemistry' => 'lifepo4',
            'nominal_voltage_v' => 51.2,
            'max_charge_power_kw' => 12.5,
            'max_discharge_power_kw' => 12.5,
            'efficiency_percent' => 95.0,
            'cycle_life' => 6000,
            'warranty_years' => 10,
            'operating_temp_min' => -10,
            'operating_temp_max' => 45,
            'dimensions' => '670×600×600mm',
            'weight_kg' => 187.5,
            'protection_class' => 'IP65',
            'current_soc_percent' => 72.0,
            'current_voltage_v' => 51.8,
            'current_current_a' => 35.0,
            'current_power_kw' => 1.8,
            'current_temperature_c' => 28.0,
            'charge_cycles' => 680,
            'daily_charge_kwh' => 18.5,
            'daily_discharge_kwh' => 16.2,
            'total_charge_kwh' => 8950.0,
            'total_discharge_kwh' => 7820.0,
            'health_percent' => 97.8,
            'remaining_capacity_kwh' => 24.45,
            'degradation_percent' => 2.2,
            'last_sync_at' => now()->subHours(1),
        ]);

        // Meilensteine für Anlage 2
        $plant2->milestones()->create([
            'title' => 'Dachstatik geprüft',
            'description' => 'Statische Prüfung der Hallendächer für zusätzliche Lasten',
            'planned_date' => now()->subMonths(10),
            'actual_date' => now()->subMonths(10)->addDays(2),
            'status' => 'completed',
            'sort_order' => 1,
        ]);

        $plant2->milestones()->create([
            'title' => 'Finanzierung gesichert',
            'description' => 'Kreditvertrag mit Bank abgeschlossen',
            'planned_date' => now()->subMonths(9),
            'actual_date' => now()->subMonths(9)->addDays(5),
            'status' => 'completed',
            'sort_order' => 2,
        ]);

        $plant2->milestones()->create([
            'title' => 'Gerüst aufgebaut',
            'description' => 'Arbeitsgerüst für sichere Montage errichtet',
            'planned_date' => now()->subMonths(8)->addWeeks(2),
            'actual_date' => now()->subMonths(8)->addWeeks(2)->addDays(1),
            'status' => 'completed',
            'sort_order' => 3,
        ]);

        $plant2->milestones()->create([
            'title' => 'Ost-Module installiert',
            'description' => 'Installation der 75 Module auf der Ostseite',
            'planned_date' => now()->subMonths(8)->addWeeks(3),
            'actual_date' => now()->subMonths(8)->addWeeks(3),
            'status' => 'completed',
            'sort_order' => 4,
        ]);

        $plant2->milestones()->create([
            'title' => 'West-Module installiert',
            'description' => 'Installation der 75 Module auf der Westseite',
            'planned_date' => now()->subMonths(8)->addWeeks(4),
            'actual_date' => now()->subMonths(8)->addWeeks(4)->subDays(2),
            'status' => 'completed',
            'sort_order' => 5,
        ]);

        $plant2->milestones()->create([
            'title' => 'Verkabelung abgeschlossen',
            'description' => 'DC- und AC-Verkabelung aller Komponenten',
            'planned_date' => now()->subMonths(7)->addWeeks(1),
            'actual_date' => now()->subMonths(7)->addWeeks(1)->addDays(3),
            'status' => 'completed',
            'sort_order' => 6,
        ]);

        $plant2->milestones()->create([
            'title' => 'Batteriespeicher installiert',
            'description' => 'Installation und Konfiguration des 25 kWh Speichers',
            'planned_date' => now()->subMonths(7)->addWeeks(2),
            'actual_date' => now()->subMonths(7)->addWeeks(2),
            'status' => 'completed',
            'sort_order' => 7,
        ]);

        // Notizen für Anlage 2
        $plant2->notes()->create([
            'title' => 'Großprojekt erfolgreich realisiert',
            'content' => 'Die Gewerbeanlage wurde in Rekordzeit installiert. Besondere Herausforderung war die Ost-West-Ausrichtung, die durch optimierte Modulanordnung gelöst wurde.',
            'type' => 'installation',
            'user_id' => 1,
            'is_favorite' => true,
            'sort_order' => 1,
        ]);

        $plant2->notes()->create([
            'title' => 'Monitoring-System installiert',
            'content' => 'Umfassendes Monitoring-System für Echtzeitüberwachung installiert. Ermöglicht frühzeitige Erkennung von Leistungsabweichungen und optimiert Wartungsintervalle.',
            'type' => 'monitoring',
            'user_id' => 1,
            'is_favorite' => true,
            'sort_order' => 2,
        ]);

        // Beispiel-Solaranlage 3 - Mehrfamilienhaus
        $plant3 = SolarPlant::create([
            'name' => 'Mehrfamilienhaus Sonnenallee',
            'location' => 'Sonnenallee 42, 20459 Hamburg',
            'description' => 'Solaranlage auf Mehrfamilienhaus mit Mieterstrom-Konzept',
            'planned_installation_date' => now()->subMonths(5),
            'installation_date' => now()->subMonths(4),
            'planned_commissioning_date' => now()->subMonths(4),
            'commissioning_date' => now()->subMonths(3),
            'total_capacity_kw' => 19.840,
            'panel_count' => 64,
            'inverter_count' => 2,
            'battery_capacity_kwh' => 15.0,
            'expected_annual_yield_kwh' => 18500.0,
            'total_investment' => 42000.00,
            'annual_operating_costs' => 450.00,
            'feed_in_tariff_per_kwh' => 0.0825,
            'electricity_price_per_kwh' => 0.315,
            'status' => 'active',
            'is_active' => true,
        ]);

        // Wechselrichter für Anlage 3 (2 Stück)
        for ($i = 1; $i <= 2; $i++) {
            SolarInverter::create([
                'solar_plant_id' => $plant3->id,
                'fusion_solar_device_id' => "INV-003-" . sprintf('%03d', $i) . "",
                'name' => "Huawei SUN2000-12KTL-M2 #{$i}",
                'model' => 'SUN2000-12KTL-M2',
                'serial_number' => "HW2023" . sprintf('%06d', $i) . "",
                'manufacturer' => 'Huawei',
                'rated_power_kw' => 12.0,
                'efficiency_percent' => 98.7,
                'installation_date' => $plant3->installation_date,
                'firmware_version' => 'V100R001C00SPC124',
                'status' => 'normal',
                'is_active' => true,
                'input_voltage_range' => '200-1000V',
                'output_voltage' => '230/400V',
                'max_dc_current' => '15A',
                'max_ac_current' => '18A',
                'protection_class' => 'IP65',
                'cooling_method' => 'Natural convection',
                'dimensions' => '365×365×156mm',
                'weight_kg' => 17.5,
                'current_power_kw' => 9.8,
                'current_voltage_v' => 385.0,
                'current_current_a' => 15.2,
                'current_frequency_hz' => 50.0,
                'current_temperature_c' => 46.0,
                'daily_yield_kwh' => 45.2,
                'total_yield_kwh' => 4850.0,
                'last_sync_at' => now()->subHours(1),
            ]);
        }

        // Solarmodule für Anlage 3 (64 Module)
        for ($i = 1; $i <= 64; $i++) {
            $stringNumber = ceil($i / 16); // 4 Strings mit je 16 Modulen
            $positionInString = (($i - 1) % 16) + 1;
            
            SolarModule::create([
                'solar_plant_id' => $plant3->id,
                'fusion_solar_device_id' => "MOD-003-" . sprintf('%03d', $i) . "",
                'name' => "Solarmodul MFH #{$i}",
                'model' => 'Canadian Solar CS3W-545MS',
                'serial_number' => "CS2023" . sprintf('%06d', $i) . "",
                'manufacturer' => 'Canadian Solar',
                'rated_power_wp' => 545,
                'efficiency_percent' => 21.1,
                'installation_date' => $plant3->installation_date,
                'status' => 'normal',
                'is_active' => true,
                'cell_type' => 'perc',
                'module_type' => 'Standard',
                'voltage_vmp' => 41.9,
                'current_imp' => 13.01,
                'voltage_voc' => 50.2,
                'current_isc' => 13.72,
                'temperature_coefficient' => -0.36,
                'dimensions' => '2279×1134×35mm',
                'weight_kg' => 27.2,
                'frame_color' => 'Silver',
                'glass_type' => 'Anti-reflective tempered glass',
                'string_number' => $stringNumber,
                'position_in_string' => $positionInString,
                'orientation_degrees' => 180, // Süd
                'tilt_degrees' => 30,
                'shading_factor' => 0.05, // Leichte Verschattung durch Nachbargebäude
                'current_power_w' => 475.0,
                'current_voltage_v' => 40.5,
                'current_current_a' => 11.7,
                'current_temperature_c' => 43.0,
                'daily_yield_kwh' => 1.72,
                'total_yield_kwh' => 195.0,
                'last_sync_at' => now()->subHours(1),
            ]);
        }

        // Batteriespeicher für Anlage 3
        SolarBattery::create([
            'solar_plant_id' => $plant3->id,
            'fusion_solar_device_id' => 'BAT-003-MFH',
            'name' => 'Huawei LUNA2000-15-S0',
            'model' => 'LUNA2000-15-S0',
            'serial_number' => 'HWB2023003456',
            'manufacturer' => 'Huawei',
            'capacity_kwh' => 15.0,
            'usable_capacity_kwh' => 14.25,
            'rated_power_kw' => 7.5,
            'installation_date' => $plant3->installation_date,
            'status' => 'normal',
            'is_active' => true,
            'battery_type' => 'Lithium Iron Phosphate',
            'chemistry' => 'lifepo4',
            'nominal_voltage_v' => 51.2,
            'max_charge_power_kw' => 7.5,
            'max_discharge_power_kw' => 7.5,
            'efficiency_percent' => 95.0,
            'cycle_life' => 6000,
            'warranty_years' => 10,
            'operating_temp_min' => -10,
            'operating_temp_max' => 45,
            'dimensions' => '670×600×360mm',
            'weight_kg' => 112.5,
            'protection_class' => 'IP65',
            'current_soc_percent' => 68.0,
            'current_voltage_v' => 51.5,
            'current_current_a' => 22.0,
            'current_power_kw' => 1.1,
            'current_temperature_c' => 26.0,
            'charge_cycles' => 285,
            'daily_charge_kwh' => 12.5,
            'daily_discharge_kwh' => 11.2,
            'total_charge_kwh' => 3850.0,
            'total_discharge_kwh' => 3420.0,
            'health_percent' => 99.2,
            'remaining_capacity_kwh' => 14.88,
            'degradation_percent' => 0.8,
            'last_sync_at' => now()->subHours(1),
        ]);

        // Notizen für Anlage 3
        $plant3->notes()->create([
            'title' => 'Mieterstrom-Konzept implementiert',
            'content' => 'Innovative Mieterstrom-Lösung erfolgreich umgesetzt. Direkte Stromversorgung der Mieter mit 20% Ersparnis gegenüber Netzstrom. Sehr positive Resonanz.',
            'type' => 'commissioning',
            'user_id' => 1,
            'is_favorite' => true,
            'sort_order' => 1,
        ]);

        $plant3->notes()->create([
            'title' => 'Batteriespeicher optimiert',
            'content' => 'Batteriespeicher-Einstellungen für optimale Eigenverbrauchsquote angepasst. Speicherkapazität wird nun zu 95% ausgenutzt, Eigenverbrauch um 15% gesteigert.',
            'type' => 'improvement',
            'user_id' => 1,
            'is_favorite' => true,
            'sort_order' => 2,
        ]);

        $plant3->notes()->create([
            'title' => 'Kleinere Reparatur durchgeführt',
            'content' => 'Defekter Optimierer an Modul 23 ausgetauscht. Ursache war Feuchtigkeit durch undichte Dachrinne. Dichtung erneuert, Problem behoben.',
            'type' => 'issue',
            'user_id' => 1,
            'is_favorite' => false,
            'sort_order' => 0,
        ]);

        // Meilensteine für Anlage 3
        $plant3->milestones()->create([
            'title' => 'Mieterstrom-Konzept entwickelt',
            'description' => 'Rechtliche und technische Planung für Mieterstrom-Modell',
            'planned_date' => now()->subMonths(6),
            'actual_date' => now()->subMonths(6)->addDays(7),
            'status' => 'completed',
            'sort_order' => 1,
        ]);

        $plant3->milestones()->create([
            'title' => 'Mieter informiert',
            'description' => 'Information aller Mieter über das Mieterstrom-Projekt',
            'planned_date' => now()->subMonths(5)->addWeeks(2),
            'actual_date' => now()->subMonths(5)->addWeeks(2),
            'status' => 'completed',
            'sort_order' => 2,
        ]);

        $plant3->milestones()->create([
            'title' => 'Zählerschrank erweitert',
            'description' => 'Installation zusätzlicher Zähler für Mieterstrom',
            'planned_date' => now()->subMonths(4)->addWeeks(2),
            'actual_date' => now()->subMonths(4)->addWeeks(2)->addDays(1),
            'status' => 'completed',
            'sort_order' => 3,
        ]);

        $plant3->milestones()->create([
            'title' => 'Module auf Süddach',
            'description' => 'Installation von 64 Modulen auf dem Süddach',
            'planned_date' => now()->subMonths(4)->addWeeks(3),
            'actual_date' => now()->subMonths(4)->addWeeks(3),
            'status' => 'completed',
            'sort_order' => 4,
        ]);

        $plant3->milestones()->create([
            'title' => 'Smart Meter installiert',
            'description' => 'Installation intelligenter Stromzähler für alle Wohnungen',
            'planned_date' => now()->subMonths(3)->addWeeks(2),
            'actual_date' => now()->subMonths(3)->addWeeks(2)->addDays(3),
            'status' => 'completed',
            'sort_order' => 5,
        ]);

        $plant3->milestones()->create([
            'title' => 'Mieterstrom-Portal aktiviert',
            'description' => 'Online-Portal für Mieter zur Verbrauchsübersicht',
            'planned_date' => now()->subMonths(2),
            'actual_date' => now()->subMonths(2)->addDays(5),
            'status' => 'completed',
            'sort_order' => 6,
        ]);

        $plant3->milestones()->create([
            'title' => 'Erste Wartung',
            'description' => 'Planmäßige Wartung nach 3 Monaten Betrieb',
            'planned_date' => now()->addWeeks(2),
            'actual_date' => null,
            'status' => 'planned',
            'sort_order' => 7,
        ]);

        // Große Solaranlage 4 - Sonnenkraft Nord
        $plant4 = SolarPlant::create([
            'name' => 'Sonnenkraft Nord',
            'location' => 'Industriegebiet Nord, 30159 Hannover',
            'description' => 'Große Freiflächenanlage mit 200 Solarmodulen und optimaler Süd-Ausrichtung',
            'planned_installation_date' => now()->addMonths(1),
            'installation_date' => null,
            'planned_commissioning_date' => now()->addMonths(2),
            'commissioning_date' => null,
            'total_capacity_kw' => 2000.000,
            'panel_count' => 400,
            'inverter_count' => 8,
            'battery_capacity_kwh' => 0.0,
            'expected_annual_yield_kwh' => 115000.0,
            'total_investment' => 180000.00,
            'annual_operating_costs' => 2500.00,
            'feed_in_tariff_per_kwh' => 0.0825,
            'electricity_price_per_kwh' => 0.275,
            'status' => 'under_construction',
            'is_active' => true,
        ]);

        // Wechselrichter für Anlage 4 (8 Stück für 2 MW)
        for ($i = 1; $i <= 8; $i++) {
            SolarInverter::create([
                'solar_plant_id' => $plant4->id,
                'fusion_solar_device_id' => "INV-004-" . sprintf('%03d', $i) . "",
                'name' => "Huawei SUN2000-50KTL-M3 #{$i}",
                'model' => 'SUN2000-50KTL-M3',
                'serial_number' => "HW2024" . sprintf('%06d', $i) . "",
                'manufacturer' => 'Huawei',
                'rated_power_kw' => 50.0,
                'efficiency_percent' => 98.8,
                'installation_date' => $plant4->installation_date,
                'firmware_version' => 'V100R001C00SPC126',
                'status' => 'normal',
                'is_active' => true,
                'input_voltage_range' => '200-1000V',
                'output_voltage' => '400V',
                'max_dc_current' => '20A',
                'max_ac_current' => '22A',
                'protection_class' => 'IP65',
                'cooling_method' => 'Intelligent fan cooling',
                'dimensions' => '525×470×166mm',
                'weight_kg' => 26.5,
                'current_power_kw' => 13.8,
                'current_voltage_v' => 400.0,
                'current_current_a' => 20.0,
                'current_frequency_hz' => 50.0,
                'current_temperature_c' => 42.0,
                'daily_yield_kwh' => 72.5,
                'total_yield_kwh' => 18500.0,
                'last_sync_at' => now()->subHours(1),
            ]);
        }

        // Solarmodule für Anlage 4 (400 Module für 2 MW - Testdaten)
        for ($i = 1; $i <= 400; $i++) {
            $stringNumber = ceil($i / 25); // Strings mit je 25 Modulen
            $positionInString = (($i - 1) % 25) + 1;
            
            SolarModule::create([
                'solar_plant_id' => $plant4->id,
                'fusion_solar_device_id' => "MOD-004-" . sprintf('%03d', $i) . "",
                'name' => "Solarmodul Nord #{$i}",
                'model' => 'Huawei SUN545-22MBB',
                'serial_number' => "HW2024" . sprintf('%06d', $i) . "",
                'manufacturer' => 'Huawei',
                'rated_power_wp' => 545,
                'efficiency_percent' => 22.3,
                'installation_date' => $plant4->installation_date,
                'status' => 'normal',
                'is_active' => true,
                'cell_type' => 'mono',
                'module_type' => 'Bifacial',
                'voltage_vmp' => 41.8,
                'current_imp' => 13.04,
                'voltage_voc' => 50.1,
                'current_isc' => 13.78,
                'temperature_coefficient' => -0.29,
                'dimensions' => '2279×1134×30mm',
                'weight_kg' => 28.1,
                'frame_color' => 'Silver',
                'glass_type' => 'Anti-reflective tempered glass',
                'string_number' => $stringNumber,
                'position_in_string' => $positionInString,
                'orientation_degrees' => 180, // Süd
                'tilt_degrees' => 25,
                'shading_factor' => 0.0,
                'current_power_w' => 520.0,
                'current_voltage_v' => 40.8,
                'current_current_a' => 12.7,
                'current_temperature_c' => 38.0,
                'daily_yield_kwh' => 2.15,
                'total_yield_kwh' => 485.0,
                'last_sync_at' => now()->subHours(1),
            ]);
        }

        // Meilensteine für Anlage 4 (Im Bau)
        $plant4->milestones()->create([
            'title' => 'Baugenehmigung erhalten',
            'description' => 'Genehmigung für 2 MW Freiflächenanlage von Behörden erhalten',
            'planned_date' => now()->subMonths(2),
            'actual_date' => now()->subMonths(2)->addDays(5),
            'status' => 'completed',
            'sort_order' => 1,
        ]);

        $plant4->milestones()->create([
            'title' => 'Fundamente gegossen',
            'description' => 'Betonfundamente für Montagegestelle erstellt',
            'planned_date' => now()->subWeeks(3),
            'actual_date' => now()->subWeeks(2),
            'status' => 'completed',
            'sort_order' => 2,
        ]);

        $plant4->milestones()->create([
            'title' => 'Montagegestelle installiert',
            'description' => 'Aufbau der Unterkonstruktion für 2 MW Solaranlage',
            'planned_date' => now()->subWeeks(1),
            'actual_date' => null,
            'status' => 'in_progress',
            'sort_order' => 3,
        ]);

        $plant4->milestones()->create([
            'title' => 'Module montieren',
            'description' => 'Installation aller 400 Huawei Solarmodule (2 MW)',
            'planned_date' => now()->addWeeks(2),
            'actual_date' => null,
            'status' => 'planned',
            'sort_order' => 4,
        ]);

        $plant4->milestones()->create([
            'title' => 'Wechselrichter anschließen',
            'description' => 'Installation und Verkabelung der 8 Huawei Wechselrichter',
            'planned_date' => now()->addWeeks(3),
            'actual_date' => null,
            'status' => 'planned',
            'sort_order' => 5,
        ]);

        $plant4->milestones()->create([
            'title' => 'Netzanschluss',
            'description' => 'Anschluss an das Mittelspannungsnetz für 2 MW Einspeisung',
            'planned_date' => now()->addMonths(1),
            'actual_date' => null,
            'status' => 'planned',
            'sort_order' => 6,
        ]);

        // Notizen für Anlage 4
        $plant4->notes()->create([
            'title' => 'Großanlage erfolgreich in Betrieb',
            'content' => 'Die Freiflächenanlage Sonnenkraft Nord wurde erfolgreich installiert und in Betrieb genommen. Mit 200 Modulen und 7 Wechselrichtern eine der größten Anlagen im Portfolio.',
            'type' => 'commissioning',
            'user_id' => 1,
            'is_favorite' => true,
            'sort_order' => 1,
        ]);

        // Große Solaranlage 5 - Sonnenkraft Süd
        $plant5 = SolarPlant::create([
            'name' => 'Sonnenkraft Süd',
            'location' => 'Gewerbepark Süd, 70173 Stuttgart',
            'description' => 'Große Gewerbeanlage mit 200 Hochleistungsmodulen auf mehreren Hallendächern',
            'planned_installation_date' => now()->addMonths(4),
            'installation_date' => null,
            'planned_commissioning_date' => now()->addMonths(5),
            'commissioning_date' => null,
            'total_capacity_kw' => 2000.000,
            'panel_count' => 400,
            'inverter_count' => 8,
            'battery_capacity_kwh' => 0.0,
            'expected_annual_yield_kwh' => 118000.0,
            'total_investment' => 185000.00,
            'annual_operating_costs' => 2800.00,
            'feed_in_tariff_per_kwh' => 0.0825,
            'electricity_price_per_kwh' => 0.285,
            'status' => 'in_planning',
            'is_active' => true,
        ]);

        // Wechselrichter für Anlage 5 (8 Stück für 2 MW)
        for ($i = 1; $i <= 8; $i++) {
            SolarInverter::create([
                'solar_plant_id' => $plant5->id,
                'fusion_solar_device_id' => "INV-005-" . sprintf('%03d', $i) . "",
                'name' => "Huawei SUN2000-50KTL-M3 #{$i}",
                'model' => 'SUN2000-50KTL-M3',
                'serial_number' => "HW2024S" . sprintf('%05d', $i) . "",
                'manufacturer' => 'Huawei',
                'rated_power_kw' => 50.0,
                'efficiency_percent' => 98.8,
                'installation_date' => $plant5->installation_date,
                'firmware_version' => 'V100R001C00SPC126',
                'status' => 'normal',
                'is_active' => true,
                'input_voltage_range' => '200-1000V',
                'output_voltage' => '400V',
                'max_dc_current' => '20A',
                'max_ac_current' => '22A',
                'protection_class' => 'IP65',
                'cooling_method' => 'Intelligent fan cooling',
                'dimensions' => '525×470×166mm',
                'weight_kg' => 26.5,
                'current_power_kw' => 14.2,
                'current_voltage_v' => 400.0,
                'current_current_a' => 20.5,
                'current_frequency_hz' => 50.0,
                'current_temperature_c' => 44.0,
                'daily_yield_kwh' => 75.8,
                'total_yield_kwh' => 19200.0,
                'last_sync_at' => now()->subHours(1),
            ]);
        }

        // Solarmodule für Anlage 5 (400 Module für 2 MW - Testdaten)
        for ($i = 1; $i <= 400; $i++) {
            $stringNumber = ceil($i / 25); // Strings mit je 25 Modulen
            $positionInString = (($i - 1) % 25) + 1;
            
            SolarModule::create([
                'solar_plant_id' => $plant5->id,
                'fusion_solar_device_id' => "MOD-005-" . sprintf('%03d', $i) . "",
                'name' => "Solarmodul Süd #{$i}",
                'model' => 'Huawei SUN545-22MBB',
                'serial_number' => "HW2024S" . sprintf('%06d', $i) . "",
                'manufacturer' => 'Huawei',
                'rated_power_wp' => 545,
                'efficiency_percent' => 22.5,
                'installation_date' => $plant5->installation_date,
                'status' => 'normal',
                'is_active' => true,
                'cell_type' => 'mono',
                'module_type' => 'Bifacial',
                'voltage_vmp' => 41.9,
                'current_imp' => 13.01,
                'voltage_voc' => 50.3,
                'current_isc' => 13.75,
                'temperature_coefficient' => -0.28,
                'dimensions' => '2279×1134×30mm',
                'weight_kg' => 28.5,
                'frame_color' => 'Black',
                'glass_type' => 'Anti-reflective tempered glass',
                'string_number' => $stringNumber,
                'position_in_string' => $positionInString,
                'orientation_degrees' => 180, // Süd
                'tilt_degrees' => 20,
                'shading_factor' => 0.02, // Minimale Verschattung durch Lüftungsanlagen
                'current_power_w' => 525.0,
                'current_voltage_v' => 41.2,
                'current_current_a' => 12.8,
                'current_temperature_c' => 40.0,
                'daily_yield_kwh' => 2.25,
                'total_yield_kwh' => 510.0,
                'last_sync_at' => now()->subHours(1),
            ]);
        }

        // Meilensteine für Anlage 5 (In Planung)
        $plant5->milestones()->create([
            'title' => 'Statikprüfung Hallendächer',
            'description' => 'Prüfung der Tragfähigkeit der Hallendächer für 2 MW Solaranlage',
            'planned_date' => now()->addWeeks(2),
            'actual_date' => null,
            'status' => 'planned',
            'sort_order' => 1,
        ]);

        $plant5->milestones()->create([
            'title' => 'Baugenehmigung beantragen',
            'description' => 'Einreichung der Bauunterlagen für 2 MW Gewerbeanlage',
            'planned_date' => now()->addMonths(1),
            'actual_date' => null,
            'status' => 'planned',
            'sort_order' => 2,
        ]);

        $plant5->milestones()->create([
            'title' => 'Material bestellen',
            'description' => 'Bestellung von 3670 Huawei Modulen und 40 Wechselrichtern',
            'planned_date' => now()->addMonths(2),
            'actual_date' => null,
            'status' => 'planned',
            'sort_order' => 3,
        ]);

        $plant5->milestones()->create([
            'title' => 'Installation beginnen',
            'description' => 'Start der Montagearbeiten für 2 MW Anlage auf Hallendächern',
            'planned_date' => now()->addMonths(4),
            'actual_date' => null,
            'status' => 'planned',
            'sort_order' => 4,
        ]);

        // Notizen für Anlage 5
        $plant5->notes()->create([
            'title' => 'Gewerbeanlage Süd in Betrieb',
            'content' => 'Die Sonnenkraft Süd Anlage wurde erfolgreich auf mehreren Hallendächern installiert. Optimale Südausrichtung sorgt für maximale Erträge.',
            'type' => 'planning',
            'user_id' => 1,
            'is_favorite' => true,
            'sort_order' => 1,
        ]);

        // Große Solaranlage 6 - Solarpark
        $plant6 = SolarPlant::create([
            'name' => 'Solarpark',
            'location' => 'Freifläche Solarpark, 01099 Dresden',
            'description' => 'Großer Solarpark mit 200 Premium-Modulen auf optimierter Freiflächenanlage',
            'planned_installation_date' => now()->subWeeks(2),
            'installation_date' => now()->subWeeks(1),
            'planned_commissioning_date' => now()->addWeeks(1),
            'commissioning_date' => null,
            'total_capacity_kw' => 109.000,
            'panel_count' => 200,
            'inverter_count' => 7,
            'battery_capacity_kwh' => 0.0,
            'expected_annual_yield_kwh' => 120000.0,
            'total_investment' => 190000.00,
            'annual_operating_costs' => 3000.00,
            'feed_in_tariff_per_kwh' => 0.0825,
            'electricity_price_per_kwh' => 0.275,
            'status' => 'awaiting_commissioning',
            'is_active' => true,
        ]);

        // Wechselrichter für Anlage 6 (7 Stück)
        for ($i = 1; $i <= 7; $i++) {
            SolarInverter::create([
                'solar_plant_id' => $plant6->id,
                'fusion_solar_device_id' => "INV-006-" . sprintf('%03d', $i) . "",
                'name' => "Huawei SUN2000-15KTL-M2 #{$i}",
                'model' => 'SUN2000-15KTL-M2',
                'serial_number' => "HW2024P" . sprintf('%05d', $i) . "",
                'manufacturer' => 'Huawei',
                'rated_power_kw' => 15.0,
                'efficiency_percent' => 98.8,
                'installation_date' => $plant6->installation_date,
                'firmware_version' => 'V100R001C00SPC126',
                'status' => 'normal',
                'is_active' => true,
                'input_voltage_range' => '200-1000V',
                'output_voltage' => '400V',
                'max_dc_current' => '20A',
                'max_ac_current' => '22A',
                'protection_class' => 'IP65',
                'cooling_method' => 'Intelligent fan cooling',
                'dimensions' => '525×470×166mm',
                'weight_kg' => 26.5,
                'current_power_kw' => 14.5,
                'current_voltage_v' => 400.0,
                'current_current_a' => 21.0,
                'current_frequency_hz' => 50.0,
                'current_temperature_c' => 40.0,
                'daily_yield_kwh' => 78.2,
                'total_yield_kwh' => 22500.0,
                'last_sync_at' => now()->subHours(1),
            ]);
        }

        // Solarmodule für Anlage 6 (200 Module)
        for ($i = 1; $i <= 200; $i++) {
            $stringNumber = ceil($i / 28); // 8 Strings mit je 25-29 Modulen
            $positionInString = (($i - 1) % 28) + 1;
            
            SolarModule::create([
                'solar_plant_id' => $plant6->id,
                'fusion_solar_device_id' => "MOD-006-" . sprintf('%03d', $i) . "",
                'name' => "Solarmodul Park #{$i}",
                'model' => 'Trina Solar Vertex S+ TSM-NEG9R.28 545W',
                'serial_number' => "TR2024" . sprintf('%06d', $i) . "",
                'manufacturer' => 'Trina Solar',
                'rated_power_wp' => 545,
                'efficiency_percent' => 22.1,
                'installation_date' => $plant6->installation_date,
                'status' => 'normal',
                'is_active' => true,
                'cell_type' => 'mono',
                'module_type' => 'Bifacial',
                'voltage_vmp' => 41.7,
                'current_imp' => 13.07,
                'voltage_voc' => 49.8,
                'current_isc' => 13.85,
                'temperature_coefficient' => -0.30,
                'dimensions' => '2279×1134×30mm',
                'weight_kg' => 28.0,
                'frame_color' => 'Silver',
                'glass_type' => 'Anti-reflective tempered glass',
                'string_number' => $stringNumber,
                'position_in_string' => $positionInString,
                'orientation_degrees' => 180, // Süd
                'tilt_degrees' => 30,
                'shading_factor' => 0.0,
                'current_power_w' => 530.0,
                'current_voltage_v' => 41.0,
                'current_current_a' => 12.9,
                'current_temperature_c' => 36.0,
                'daily_yield_kwh' => 2.35,
                'total_yield_kwh' => 580.0,
                'last_sync_at' => now()->subHours(1),
            ]);
        }

        // Notizen für Anlage 6
        $plant6->notes()->create([
            'title' => 'Solarpark erfolgreich realisiert',
            'content' => 'Der große Solarpark wurde als Leuchtturmprojekt erfolgreich umgesetzt. Mit 200 Premium-Modulen und optimaler Freiflächennutzung ein Vorzeigeprojekt.',
            'type' => 'commissioning',
            'user_id' => 1,
            'is_favorite' => true,
            'sort_order' => 1,
        ]);

        $plant6->notes()->create([
            'title' => 'Monitoring-System erweitert',
            'content' => 'Umfassendes Monitoring-System für die Großanlage installiert. Ermöglicht detaillierte Überwachung aller 200 Module und 7 Wechselrichter in Echtzeit.',
            'type' => 'monitoring',
            'user_id' => 1,
            'is_favorite' => false,
            'sort_order' => 0,
        ]);

        // Meilensteine für Anlage 6 (Warte auf Inbetriebnahme)
        $plant6->milestones()->create([
            'title' => 'Installation abgeschlossen',
            'description' => 'Alle 200 Module und 7 Wechselrichter erfolgreich installiert',
            'planned_date' => now()->subWeeks(2),
            'actual_date' => now()->subWeeks(1),
            'status' => 'completed',
            'sort_order' => 1,
        ]);

        $plant6->milestones()->create([
            'title' => 'Technische Abnahme',
            'description' => 'Technische Prüfung durch Sachverständigen',
            'planned_date' => now()->subDays(3),
            'actual_date' => now()->subDays(2),
            'status' => 'completed',
            'sort_order' => 2,
        ]);

        $plant6->milestones()->create([
            'title' => 'Netzanschluss-Prüfung',
            'description' => 'Abnahme des Netzanschlusses durch Netzbetreiber',
            'planned_date' => now()->addDays(2),
            'actual_date' => null,
            'status' => 'planned',
            'sort_order' => 3,
        ]);

        $plant6->milestones()->create([
            'title' => 'Inbetriebnahme',
            'description' => 'Offizielle Inbetriebnahme und Freischaltung',
            'planned_date' => now()->addWeeks(1),
            'actual_date' => null,
            'status' => 'planned',
            'sort_order' => 4,
        ]);

        $plant6->milestones()->create([
            'title' => 'Monitoring aktivieren',
            'description' => 'Aktivierung des Überwachungssystems',
            'planned_date' => now()->addWeeks(1)->addDays(1),
            'actual_date' => null,
            'status' => 'planned',
            'sort_order' => 5,
        ]);
    }
}