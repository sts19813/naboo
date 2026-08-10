<?php

namespace Database\Seeders;

use App\Models\Charge;
use App\Models\ChargePayment;
use App\Models\DossierDeletedFile;
use App\Models\DossierDocumentRequirement;
use App\Models\Expense;
use App\Models\ExpenseFile;
use App\Models\ExpenseNotificationSetting;
use App\Models\InventoryCheck;
use App\Models\MaintenanceProvider;
use App\Models\MaintenanceTicket;
use App\Models\MaintenanceTicketAssignment;
use App\Models\MaintenanceTicketCost;
use App\Models\MaintenanceTicketMessage;
use App\Models\MaintenanceTicketNotification;
use App\Models\MaintenanceTicketStatusHistory;
use App\Models\Owner;
use App\Models\OwnerDocument;
use App\Models\Property;
use App\Models\PropertyChangeLog;
use App\Models\PropertyDocument;
use App\Models\PropertyInventoryArea;
use App\Models\PropertyInventoryItem;
use App\Models\PropertyInventoryPhoto;
use App\Models\PropertyType;
use App\Models\RecurringExpenseItem;
use App\Models\StorageItem;
use App\Models\StorageItemLog;
use App\Models\StorageWarehouse;
use App\Models\StorageZone;
use App\Models\SystemSetting;
use App\Models\Tenant;
use App\Models\TenantDocument;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    private User $admin;
    private User $advisor;
    private User $advisorSecondary;
    private User $technician;
    private User $tenantUser;

    /**
     * Seed a realistic local demo dataset for all business modules.
     */
    public function run(): void
    {
        $this->admin = $this->user('Admin Local', 'admin@naboo.local', 'administrador');
        $this->advisor = $this->user('Andrea Campos', 'andrea.campos@naboo.local', 'asesores');
        $this->advisorSecondary = $this->user('Marco Balam', 'marco.balam@naboo.local', 'asesores');
        $this->technician = $this->user('Rafael Pacheco', 'rafael.pacheco@naboo.local', 'tecnico');
        $this->tenantUser = $this->user('Laura Pech', 'laura.pech@naboo.local', 'inquilino');

        $this->seedSettings();
        $this->seedDossierRequirements();

        $owners = $this->seedOwners();
        $tenants = $this->seedTenants();
        $properties = $this->seedProperties($owners, $tenants);

        $this->seedInventory($properties);
        $this->seedDossiers($properties, $owners, $tenants);
        $this->seedCharges($properties);
        $expenses = $this->seedExpenses($properties);
        $providers = $this->seedMaintenanceProviders();
        $this->seedMaintenance($properties, $providers, $expenses);
        $this->seedStorage();
    }

    private function seedSettings(): void
    {
        ExpenseNotificationSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'days_before' => 5,
                'emails' => ['administracion@naboo.local', 'finanzas@naboo.local'],
                'phones' => ['9991223344', '9994556677'],
            ]
        );

        foreach ([
            'dossiers.storage_limit_gb' => '250',
            'dossiers.max_file_size_mb' => '50',
            'dossiers.storage_warning_percent' => '82',
        ] as $key => $value) {
            SystemSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    private function seedDossierRequirements(): void
    {
        $requirements = [
            'property' => [
                'escritura' => 'Escritura o contrato de compraventa',
                'predial' => 'Predial vigente',
                'servicios' => 'Recibo de luz o agua',
                'contrato_administracion' => 'Contrato de administracion',
            ],
            'owner' => [
                'ine' => 'Identificacion oficial',
                'constancia_fiscal' => 'Constancia de situacion fiscal',
                'estado_cuenta' => 'Estado de cuenta bancario',
            ],
            'tenant' => [
                'ine' => 'Identificacion oficial',
                'comprobante_ingresos' => 'Comprobante de ingresos',
                'referencias' => 'Referencias personales',
                'contrato_arrendamiento' => 'Contrato de arrendamiento',
            ],
        ];

        foreach ($requirements as $entityType => $items) {
            $order = 10;
            foreach ($items as $documentType => $label) {
                DossierDocumentRequirement::query()->updateOrCreate(
                    ['entity_type' => $entityType, 'document_type' => $documentType],
                    ['label' => $label, 'is_active' => true, 'sort_order' => $order]
                );
                $order += 10;
            }
        }
    }

    /**
     * @return array<string, Owner>
     */
    private function seedOwners(): array
    {
        $rows = [
            'sofia' => [
                'name' => 'Sofia Manzanilla Ruiz',
                'phone' => '9991457821',
                'email' => 'sofia.manzanilla@example.com',
                'rfc' => 'MARS820414QJ2',
                'curp' => 'MARS820414MYNNZF08',
                'owner_type' => Owner::OWNER_INDIVIDUAL,
                'bank_name' => 'BBVA',
                'clabe' => '012180001234567890',
                'account_holder' => 'Sofia Manzanilla Ruiz',
                'payment_method' => Owner::PAYMENT_METHOD_TRANSFER,
                'address' => 'Calle 41 #215, Col. Campestre, Merida, Yucatan',
                'notes' => 'Prefiere reportes mensuales por correo y pagos los dias 3.',
            ],
            'hacienda' => [
                'name' => 'Inmobiliaria Hacienda Norte SAPI de CV',
                'phone' => '9992384401',
                'email' => 'admin@haciendanorte.example.com',
                'rfc' => 'IHN190225K72',
                'curp' => null,
                'owner_type' => Owner::OWNER_COMPANY,
                'bank_name' => 'Santander',
                'clabe' => '014180556677889900',
                'account_holder' => 'Inmobiliaria Hacienda Norte SAPI de CV',
                'payment_method' => Owner::PAYMENT_METHOD_TRANSFER,
                'address' => 'Av. Andres Garcia Lavin 298, Piso 4, Merida, Yucatan',
                'notes' => 'Enviar facturas a administracion antes de cerrar cada mes.',
            ],
            'emilio' => [
                'name' => 'Emilio Cervera Medina',
                'phone' => '9993189022',
                'email' => 'emilio.cervera@example.com',
                'rfc' => 'CEME760901T44',
                'curp' => 'CEME760901HYNDRM05',
                'owner_type' => Owner::OWNER_INDIVIDUAL,
                'bank_name' => 'Banorte',
                'clabe' => '072180003456789012',
                'account_holder' => 'Emilio Cervera Medina',
                'payment_method' => Owner::PAYMENT_METHOD_TRANSFER,
                'address' => 'Privada Xcanatun lote 18, Merida, Yucatan',
                'notes' => 'Autoriza mantenimientos menores a $2,500 sin llamada previa.',
            ],
            'luma' => [
                'name' => 'Luma Desarrollos Urbanos SA de CV',
                'phone' => '9995067710',
                'email' => 'contacto@lumadesarrollos.example.com',
                'rfc' => 'LDU210620G18',
                'curp' => null,
                'owner_type' => Owner::OWNER_COMPANY,
                'bank_name' => 'HSBC',
                'clabe' => '021180009876543210',
                'account_holder' => 'Luma Desarrollos Urbanos SA de CV',
                'payment_method' => Owner::PAYMENT_METHOD_TRANSFER,
                'address' => 'Calle 60 #391, Centro, Merida, Yucatan',
                'notes' => 'Cuenta corporativa con revision trimestral de rentas.',
            ],
        ];

        return collect($rows)
            ->map(function (array $row): Owner {
                return Owner::query()->updateOrCreate(
                    ['email' => $row['email']],
                    $row + ['is_active' => true]
                );
            })
            ->all();
    }

    /**
     * @return array<string, Tenant>
     */
    private function seedTenants(): array
    {
        $rows = [
            'laura' => [
                'full_name' => 'Laura Pech Dominguez',
                'phone_primary' => '9993112200',
                'phone_secondary' => '9997441108',
                'email' => 'laura.pech@example.com',
                'rfc' => 'PEDL910708AJ5',
                'curp' => 'PEDL910708MYNCHL02',
                'employer' => 'Clinica Merida Norte',
                'occupation' => 'Coordinadora administrativa',
                'monthly_income' => 48500,
                'employment_years' => 5,
                'personal_reference_name' => 'Martha Pech',
                'personal_reference_phone' => '9991012233',
                'work_reference_name' => 'Dr. Ivan Fuentes',
                'work_reference_phone' => '9992023344',
                'emergency_contact_name' => 'Daniel Pech',
                'emergency_contact_phone' => '9993034455',
                'previous_address' => 'Calle 22 #145, Montes de Ame, Merida',
                'current_address' => 'Privada Ceiba 12, Temozon Norte',
                'dossier_status' => Tenant::DOSSIER_COMPLETE,
                'notes' => 'Pago puntual por transferencia; requiere factura mensual.',
            ],
            'julio' => [
                'full_name' => 'Julio Santamaria Vega',
                'phone_primary' => '9994106712',
                'phone_secondary' => null,
                'email' => 'julio.santamaria@example.com',
                'rfc' => 'SAVJ880112P19',
                'curp' => 'SAVJ880112HYNNTL09',
                'employer' => 'Mayab Analytics',
                'occupation' => 'Data engineer',
                'monthly_income' => 62500,
                'employment_years' => 3,
                'personal_reference_name' => 'Carolina Vega',
                'personal_reference_phone' => '9997653344',
                'work_reference_name' => 'Patricia Aranda',
                'work_reference_phone' => '9998123344',
                'emergency_contact_name' => 'Natalia Santamaria',
                'emergency_contact_phone' => '9999123344',
                'previous_address' => 'Av. Camara de Comercio 210, Merida',
                'current_address' => 'Torre Altabrisa, Departamento 804',
                'dossier_status' => Tenant::DOSSIER_IN_REVIEW,
                'notes' => 'Contrato en firma; pendiente aval solidario.',
            ],
            'paola' => [
                'full_name' => 'Paola Carrillo Medina',
                'phone_primary' => '9995880933',
                'phone_secondary' => '9997720031',
                'email' => 'paola.carrillo@example.com',
                'rfc' => 'CAMP940311L65',
                'curp' => 'CAMP940311MYNRDL06',
                'employer' => 'Estudio Punto Arquitectura',
                'occupation' => 'Arquitecta',
                'monthly_income' => 39000,
                'employment_years' => 6,
                'personal_reference_name' => 'Renata Medina',
                'personal_reference_phone' => '9996001122',
                'work_reference_name' => 'Arq. Tomas Abreu',
                'work_reference_phone' => '9996013344',
                'emergency_contact_name' => 'Carlos Carrillo',
                'emergency_contact_phone' => '9996025566',
                'previous_address' => 'Calle 29 #307, Garcia Gineres',
                'current_address' => 'Local 3, Plaza Itzimna',
                'dossier_status' => Tenant::DOSSIER_INCOMPLETE,
                'notes' => 'Falta comprobante de domicilio reciente.',
            ],
            'nicolas' => [
                'full_name' => 'Nicolas Arceo Ibarra',
                'phone_primary' => '9997002244',
                'phone_secondary' => null,
                'email' => 'nicolas.arceo@example.com',
                'rfc' => 'AEIN830506GR8',
                'curp' => 'AEIN830506HYNBRL04',
                'employer' => 'Consultor independiente',
                'occupation' => 'Consultor turistico',
                'monthly_income' => 55500,
                'employment_years' => 9,
                'personal_reference_name' => 'Luis Arceo',
                'personal_reference_phone' => '9997112233',
                'work_reference_name' => 'Mariela Canto',
                'work_reference_phone' => '9997223344',
                'emergency_contact_name' => 'Rosa Ibarra',
                'emergency_contact_phone' => '9997334455',
                'previous_address' => 'Progreso centro, Yucatan',
                'current_address' => 'Villa Palma 4, Chicxulub Puerto',
                'dossier_status' => Tenant::DOSSIER_COMPLETE,
                'notes' => 'Renta de temporada larga con opcion de renovacion.',
            ],
        ];

        return collect($rows)
            ->map(function (array $row): Tenant {
                return Tenant::query()->updateOrCreate(
                    ['email' => $row['email']],
                    $row + ['is_active' => true]
                );
            })
            ->all();
    }

    /**
     * @param array<string, Owner> $owners
     * @param array<string, Tenant> $tenants
     * @return array<string, Property>
     */
    private function seedProperties(array $owners, array $tenants): array
    {
        $types = PropertyType::query()->pluck('id', 'slug');
        $zones = collect(['Temozon', 'Montebello', 'Francisco Montejo', 'Playa', 'Cholul', 'Centro', 'Altabrisa'])
            ->mapWithKeys(function (string $zone): array {
                $model = Zone::query()->firstOrCreate(
                    ['slug' => Str::slug($zone)],
                    ['name' => $zone, 'is_active' => true]
                );

                return [$zone => $model->id];
            });

        $rows = [
            'NB-TEM-012' => [
                'key' => 'ceiba',
                'internal_name' => 'Casa Ceiba Temozon',
                'property_type_id' => $types['casa'] ?? PropertyType::query()->first()->id,
                'zone_id' => $zones['Temozon'],
                'zone_text' => 'Temozon Norte',
                'full_address' => 'Privada Ceiba lote 12, Temozon Norte, Merida, Yucatan',
                'map_url' => 'https://maps.google.com/?q=Temozon+Norte+Merida',
                'complex_name' => 'Privada Ceiba',
                'official_number' => '12',
                'unit_number' => null,
                'monthly_rent_price' => 28500,
                'charge_day' => 5,
                'charge_tolerance_days' => 3,
                'rent_charge_plan' => [
                    ['concept' => 'Renta mensual', 'amount' => 28500],
                    ['concept' => 'Mantenimiento privada', 'amount' => 1850],
                ],
                'details' => 'Casa de dos plantas con 3 recamaras, piscina y cuarto de servicio.',
                'description' => 'Propiedad familiar en privada con vigilancia 24/7 y acceso rapido a carretera Progreso.',
                'rental_requirements' => 'Mes de renta, deposito, aval con propiedad en Yucatan o doble deposito.',
                'amenities' => 'Piscina, paneles solares, cochera techada, minisplits inverter.',
                'facade_photo_path' => $this->demoSvg('fachadas/casa-ceiba.svg', 'Casa Ceiba Temozon', '#4f8f72'),
                'status' => Property::STATUS_OCCUPIED,
                'tenant_id' => $tenants['laura']->id,
                'current_tenant_name' => $tenants['laura']->full_name,
                'contract_starts_at' => now()->subMonths(7)->toDateString(),
                'contract_expires_at' => now()->addMonths(5)->toDateString(),
                'advisor_user_id' => $this->advisor->id,
                'owner_keys' => ['sofia'],
            ],
            'NB-MON-804' => [
                'key' => 'altavista',
                'internal_name' => 'Departamento Altavista 804',
                'property_type_id' => $types['departamento'] ?? PropertyType::query()->first()->id,
                'zone_id' => $zones['Altabrisa'],
                'zone_text' => 'Altabrisa',
                'full_address' => 'Av. Republica de Corea 345, Torre Altavista, Depto 804, Merida',
                'map_url' => 'https://maps.google.com/?q=Altabrisa+Merida',
                'complex_name' => 'Torre Altavista',
                'official_number' => '345',
                'unit_number' => '804',
                'monthly_rent_price' => 19600,
                'charge_day' => 1,
                'charge_tolerance_days' => 5,
                'rent_charge_plan' => [
                    ['concept' => 'Renta mensual', 'amount' => 19600],
                    ['concept' => 'Cuota condominio', 'amount' => 2300],
                ],
                'details' => 'Departamento amueblado de 2 recamaras con vista norte.',
                'description' => 'Ideal para ejecutivo; incluye dos cajones de estacionamiento y bodega.',
                'rental_requirements' => 'Contrato minimo 12 meses, comprobacion de ingresos 3 a 1.',
                'amenities' => 'Gimnasio, cowork, rooftop, seguridad y elevadores.',
                'facade_photo_path' => $this->demoSvg('fachadas/departamento-altavista.svg', 'Altavista 804', '#476b9f'),
                'status' => Property::STATUS_RENTED,
                'tenant_id' => $tenants['julio']->id,
                'current_tenant_name' => $tenants['julio']->full_name,
                'contract_starts_at' => now()->subMonths(2)->toDateString(),
                'contract_expires_at' => now()->addMonths(10)->toDateString(),
                'advisor_user_id' => $this->advisorSecondary->id,
                'owner_keys' => ['hacienda'],
            ],
            'NB-CHO-018' => [
                'key' => 'cholul',
                'internal_name' => 'Townhouse Nativa 18',
                'property_type_id' => $types['townhouse'] ?? PropertyType::query()->first()->id,
                'zone_id' => $zones['Cholul'],
                'zone_text' => 'Cholul',
                'full_address' => 'Calle 21 diagonal, Nativa Residencial TH 18, Cholul, Merida',
                'map_url' => 'https://maps.google.com/?q=Cholul+Merida',
                'complex_name' => 'Nativa Residencial',
                'official_number' => '18',
                'unit_number' => null,
                'monthly_rent_price' => 16800,
                'charge_day' => 10,
                'charge_tolerance_days' => 2,
                'rent_charge_plan' => [['concept' => 'Renta mensual', 'amount' => 16800]],
                'details' => 'Townhouse nuevo con roof garden y dos recamaras.',
                'description' => 'Disponible para entrega inmediata. Incluye mantenimiento de areas comunes.',
                'rental_requirements' => 'Mes adelantado, deposito y convenio transaccional.',
                'amenities' => 'Roof garden, parrilla, porton electrico, calentador electrico.',
                'facade_photo_path' => $this->demoSvg('fachadas/townhouse-nativa.svg', 'Townhouse Nativa', '#9f7a47'),
                'status' => Property::STATUS_AVAILABLE,
                'tenant_id' => null,
                'current_tenant_name' => null,
                'contract_starts_at' => null,
                'contract_expires_at' => null,
                'advisor_user_id' => $this->advisor->id,
                'owner_keys' => ['emilio'],
            ],
            'NB-CEN-003' => [
                'key' => 'itzimna',
                'internal_name' => 'Local Itzimna 3',
                'property_type_id' => $types['local'] ?? PropertyType::query()->first()->id,
                'zone_id' => $zones['Centro'],
                'zone_text' => 'Itzimna / Centro',
                'full_address' => 'Calle 20 #102-B Local 3, Itzimna, Merida',
                'map_url' => 'https://maps.google.com/?q=Itzimna+Merida',
                'complex_name' => 'Plaza Itzimna',
                'official_number' => '102-B',
                'unit_number' => 'Local 3',
                'monthly_rent_price' => 12400,
                'charge_day' => 3,
                'charge_tolerance_days' => 3,
                'rent_charge_plan' => [['concept' => 'Renta local comercial', 'amount' => 12400]],
                'details' => 'Local a pie de calle con medio bano y cortina metalica.',
                'description' => 'En proceso de firma para giro de despacho creativo.',
                'rental_requirements' => 'Deposito, poliza juridica y giro autorizado por administracion.',
                'amenities' => 'Area de recepcion, minisplit, preparacion para internet.',
                'facade_photo_path' => $this->demoSvg('fachadas/local-itzimna.svg', 'Local Itzimna 3', '#7b6aa8'),
                'status' => Property::STATUS_IN_PROCESS,
                'tenant_id' => $tenants['paola']->id,
                'current_tenant_name' => $tenants['paola']->full_name,
                'contract_starts_at' => now()->addDays(8)->toDateString(),
                'contract_expires_at' => now()->addYear()->addDays(8)->toDateString(),
                'advisor_user_id' => $this->advisorSecondary->id,
                'owner_keys' => ['luma'],
            ],
            'NB-PLY-004' => [
                'key' => 'palma',
                'internal_name' => 'Villa Palma Chicxulub 4',
                'property_type_id' => $types['casa'] ?? PropertyType::query()->first()->id,
                'zone_id' => $zones['Playa'],
                'zone_text' => 'Chicxulub Puerto',
                'full_address' => 'Km 11 carretera Progreso-Chicxulub, Villa Palma 4, Yucatan',
                'map_url' => 'https://maps.google.com/?q=Chicxulub+Puerto',
                'complex_name' => 'Villas Palma',
                'official_number' => '4',
                'unit_number' => null,
                'monthly_rent_price' => 34200,
                'charge_day' => 7,
                'charge_tolerance_days' => 4,
                'rent_charge_plan' => [
                    ['concept' => 'Renta mensual', 'amount' => 34200],
                    ['concept' => 'Servicio piscina y jardin', 'amount' => 3200],
                ],
                'details' => 'Villa de playa con 4 recamaras, alberca y terraza.',
                'description' => 'Contrato de temporada larga con mantenimiento semanal incluido.',
                'rental_requirements' => 'Deposito equivalente a dos meses por mobiliario y equipo.',
                'amenities' => 'Alberca, terraza, internet satelital, cuarto de lavado.',
                'facade_photo_path' => $this->demoSvg('fachadas/villa-palma.svg', 'Villa Palma', '#4296a3'),
                'status' => Property::STATUS_OCCUPIED,
                'tenant_id' => $tenants['nicolas']->id,
                'current_tenant_name' => $tenants['nicolas']->full_name,
                'contract_starts_at' => now()->subMonth()->toDateString(),
                'contract_expires_at' => now()->addMonths(11)->toDateString(),
                'advisor_user_id' => $this->advisor->id,
                'owner_keys' => ['sofia', 'emilio'],
            ],
        ];

        $properties = [];

        foreach ($rows as $reference => $row) {
            $ownerKeys = $row['owner_keys'];
            unset($row['owner_keys'], $row['key']);

            $property = Property::query()->updateOrCreate(
                ['internal_reference' => $reference],
                $row + [
                    'created_by' => $this->admin->id,
                    'onboarding_step' => 5,
                    'use_global_expense_notifications' => true,
                ]
            );

            $property->owners()->sync(collect($ownerKeys)->map(fn (string $key) => $owners[$key]->id)->all());
            $property->advisors()->syncWithoutDetaching([$property->advisor_user_id]);

            PropertyChangeLog::query()->updateOrCreate(
                ['property_id' => $property->id, 'changed_at' => now()->subDays(12)->startOfHour()],
                [
                    'user_id' => $this->admin->id,
                    'change_set' => [
                        'status' => ['old' => 'draft', 'new' => $property->status],
                        'monthly_rent_price' => ['old' => null, 'new' => (float) $property->monthly_rent_price],
                    ],
                ]
            );

            $properties[$reference] = $property;
        }

        return $properties;
    }

    /**
     * @param array<string, Property> $properties
     */
    private function seedInventory(array $properties): void
    {
        foreach ($properties as $property) {
            $areas = [
                'Sala comedor' => [
                    ['Sofa modular gris', 'bueno', 'Tapiceria sin manchas; cojines completos.'],
                    ['Mesa de centro madera parota', 'bueno', 'Cristal templado sin fisuras.'],
                    ['Cortinas blackout', 'regular', 'Requieren limpieza profunda.'],
                ],
                'Cocina' => [
                    ['Refrigerador inverter', 'bueno', 'Serie inventariada y funcionando.'],
                    ['Parrilla electrica', 'bueno', 'Sin golpes visibles.'],
                    ['Campana extractora', 'regular', 'Filtro con desgaste normal.'],
                ],
                'Recamara principal' => [
                    ['Base king size', 'bueno', 'Estructura firme.'],
                    ['Minisplit 18,000 BTU', 'bueno', 'Servicio preventivo realizado.'],
                    ['Closet vestido', 'bueno', 'Herrajes completos.'],
                ],
            ];

            foreach ($areas as $areaName => $items) {
                $area = PropertyInventoryArea::query()->updateOrCreate(
                    ['property_id' => $property->id, 'name' => $areaName],
                    ['notes' => 'Revision demo cargada para control de inventario.']
                );

                PropertyInventoryPhoto::query()->updateOrCreate(
                    ['property_inventory_area_id' => $area->id, 'display_order' => 1],
                    ['file_path' => $this->demoSvg('inventario/area-'.$area->id.'.svg', $areaName, '#63788f')]
                );

                foreach ($items as [$name, $condition, $notes]) {
                    $item = PropertyInventoryItem::query()->updateOrCreate(
                        ['property_inventory_area_id' => $area->id, 'name' => $name],
                        [
                            'condition' => $condition,
                            'notes' => $notes,
                            'entry_checklist' => 'Fotografiado; funcionamiento validado; sin adeudos reportados.',
                            'exit_checklist' => 'Validar limpieza, funcionamiento y accesorios originales.',
                        ]
                    );

                    $item->photos()->updateOrCreate(
                        ['name' => 'Foto de referencia'],
                        [
                            'status' => 'active',
                            'expires_at' => now()->addYear()->toDateString(),
                        ]
                    );
                }
            }

            if ($property->tenant_id) {
                $check = InventoryCheck::query()->updateOrCreate(
                    ['property_id' => $property->id, 'type' => 'entry'],
                    [
                        'status' => 'completed',
                        'tenant_id' => $property->tenant_id,
                        'notes' => 'Inventario de entrega firmado sin observaciones mayores.',
                        'created_by' => $this->admin->id,
                        'completed_at' => now()->subWeeks(3),
                    ]
                );

                $property->inventoryAreas()
                    ->with('items')
                    ->get()
                    ->flatMap(fn (PropertyInventoryArea $area) => $area->items)
                    ->take(7)
                    ->each(function (PropertyInventoryItem $item) use ($check): void {
                        $check->items()->updateOrCreate(
                            ['property_inventory_item_id' => $item->id],
                            [
                                'item_name' => $item->name,
                                'status' => $item->condition === 'regular' ? 'damaged' : 'ok',
                                'notes' => $item->condition === 'regular'
                                    ? 'Desgaste estetico aceptado por ambas partes.'
                                    : 'Entregado en buen estado.',
                                'photo_path' => $this->demoSvg('inventario/check-'.$check->id.'-'.$item->id.'.svg', $item->name, '#83915a'),
                            ]
                        );
                    });
            }
        }
    }

    /**
     * @param array<string, Property> $properties
     */
    private function seedCharges(array $properties): void
    {
        foreach ($properties as $property) {
            if (! $property->tenant_id) {
                continue;
            }

            $rent = (float) $property->monthly_rent_price;
            $baseDate = now()->startOfMonth();
            $rows = [
                [
                    'type' => Charge::TYPE_RENT,
                    'due_date' => $baseDate->copy()->subMonth()->day(min((int) $property->charge_day, 28)),
                    'amount' => $rent,
                    'status' => Charge::STATUS_PAID,
                    'paid_amount' => $rent,
                    'concept' => 'Renta '.ucfirst($baseDate->copy()->subMonth()->locale('es')->monthName).' '.$baseDate->copy()->subMonth()->year,
                    'paid_at' => now()->subDays(24),
                ],
                [
                    'type' => Charge::TYPE_RENT,
                    'due_date' => $baseDate->copy()->day(min((int) $property->charge_day, 28)),
                    'amount' => $rent,
                    'status' => $property->internal_reference === 'NB-MON-804' ? Charge::STATUS_PARTIAL : Charge::STATUS_PENDING,
                    'paid_amount' => $property->internal_reference === 'NB-MON-804' ? round($rent * 0.45, 2) : 0,
                    'concept' => 'Renta '.ucfirst($baseDate->copy()->locale('es')->monthName).' '.$baseDate->year,
                    'paid_at' => null,
                ],
                [
                    'type' => Charge::TYPE_MAINTENANCE,
                    'due_date' => $baseDate->copy()->addDays(12),
                    'amount' => match ($property->internal_reference) {
                        'NB-TEM-012' => 1850,
                        'NB-MON-804' => 2300,
                        'NB-PLY-004' => 3200,
                        default => 1200,
                    },
                    'status' => $property->internal_reference === 'NB-PLY-004' ? Charge::STATUS_IN_VALIDATION : Charge::STATUS_PENDING,
                    'paid_amount' => 0,
                    'concept' => 'Cuota de mantenimiento '.$baseDate->format('m/Y'),
                    'paid_at' => null,
                ],
            ];

            foreach ($rows as $row) {
                $charge = Charge::query()->updateOrCreate(
                    [
                        'property_id' => $property->id,
                        'tenant_id' => $property->tenant_id,
                        'concept' => $row['concept'],
                    ],
                    [
                        'type' => $row['type'],
                        'due_date' => $row['due_date']->toDateString(),
                        'amount' => $row['amount'],
                        'paid_amount' => $row['paid_amount'],
                        'period_month' => (int) $row['due_date']->month,
                        'period_year' => (int) $row['due_date']->year,
                        'notes' => 'Cargo generado para demo local.',
                        'status' => $row['status'],
                        'paid_at' => $row['paid_at'],
                        'created_by' => $this->admin->id,
                    ]
                );

                if ($row['status'] === Charge::STATUS_PAID) {
                    $this->payment($charge, $row['amount'], ChargePayment::STATUS_SUCCEEDED, ChargePayment::METHOD_SPEI, now()->subDays(24));
                } elseif ($row['status'] === Charge::STATUS_PARTIAL) {
                    $this->payment($charge, $row['paid_amount'], ChargePayment::STATUS_SUCCEEDED, ChargePayment::METHOD_TRANSFER, now()->subDays(2));
                } elseif ($row['status'] === Charge::STATUS_IN_VALIDATION) {
                    $this->payment($charge, $row['amount'], ChargePayment::STATUS_PENDING_VALIDATION, ChargePayment::METHOD_TRANSFER, now()->subDay(), 'Comprobante pendiente de validar por administracion.');
                }
            }
        }
    }

    /**
     * @param array<string, Property> $properties
     * @return array<int, Expense>
     */
    private function seedExpenses(array $properties): array
    {
        $expenses = [];

        foreach ($properties as $property) {
            $recurring = RecurringExpenseItem::query()->updateOrCreate(
                ['property_id' => $property->id, 'concept' => 'Servicio mensual de jardineria'],
                [
                    'amount' => match ($property->internal_reference) {
                        'NB-PLY-004' => 2800,
                        'NB-TEM-012' => 1650,
                        default => 950,
                    },
                    'frequency' => RecurringExpenseItem::FREQUENCY_MONTHLY,
                    'starts_on' => now()->startOfYear()->toDateString(),
                    'occurrences_count' => 12,
                    'description' => 'Servicio recurrente de areas verdes y retiro de residuos.',
                    'is_active' => true,
                    'created_by' => $this->admin->id,
                ]
            );

            $rows = [
                [
                    'concept' => 'Servicio mensual de jardineria',
                    'amount' => (float) $recurring->amount,
                    'due_date' => now()->subDays(8),
                    'paid_at' => now()->subDays(7),
                    'description' => 'Mantenimiento preventivo de jardin y poda menor.',
                    'recurring_expense_item_id' => $recurring->id,
                ],
                [
                    'concept' => 'Recibo CFE '.$property->internal_reference,
                    'amount' => match ($property->internal_reference) {
                        'NB-TEM-012' => 3420,
                        'NB-PLY-004' => 4890,
                        default => 1780,
                    },
                    'due_date' => now()->addDays(6),
                    'paid_at' => null,
                    'description' => 'Consumo bimestral pendiente de programar.',
                    'recurring_expense_item_id' => null,
                ],
                [
                    'concept' => 'Revision preventiva de aire acondicionado',
                    'amount' => 1450,
                    'due_date' => now()->subDays(3),
                    'paid_at' => null,
                    'description' => 'Servicio de limpieza y gas refrigerante si aplica.',
                    'recurring_expense_item_id' => null,
                ],
            ];

            foreach ($rows as $row) {
                $expense = Expense::query()->updateOrCreate(
                    ['property_id' => $property->id, 'concept' => $row['concept'], 'due_date' => $row['due_date']->toDateString()],
                    [
                        'recurring_expense_item_id' => $row['recurring_expense_item_id'],
                        'amount' => $row['amount'],
                        'excluded_from_totals' => false,
                        'recurrence_date' => $row['recurring_expense_item_id'] ? $row['due_date']->toDateString() : null,
                        'description' => $row['description'],
                        'paid_at' => $row['paid_at'],
                        'upcoming_notified_at' => $row['paid_at'] ? null : now()->subDay(),
                        'overdue_notified_at' => $row['paid_at'] || $row['due_date']->isFuture() ? null : now()->subHours(8),
                        'created_by' => $this->admin->id,
                    ]
                );

                ExpenseFile::query()->updateOrCreate(
                    ['expense_id' => $expense->id, 'original_name' => Str::slug($row['concept']).'.pdf'],
                    [
                        'path' => $this->demoPdf('gastos/'.$expense->id.'.pdf', $row['concept']),
                        'type' => 'receipt',
                        'mime_type' => 'application/pdf',
                        'size' => 98000,
                    ]
                );

                $expenses[] = $expense;
            }
        }

        return $expenses;
    }

    /**
     * @return array<string, MaintenanceProvider>
     */
    private function seedMaintenanceProviders(): array
    {
        $rows = [
            'clima' => [
                'type' => 'empresa_externa',
                'name' => 'Climas del Mayab',
                'email' => 'servicio@climasmayab.example.com',
                'phone' => '9994108822',
                'specialty' => 'Aire acondicionado y refrigeracion',
                'average_cost' => 1350,
                'rating' => 4.7,
                'availability' => 'Lunes a sabado, atencion 24h para urgencias.',
            ],
            'plomeria' => [
                'type' => 'empresa_externa',
                'name' => 'Hidrosoluciones Peninsular',
                'email' => 'agenda@hidrosoluciones.example.com',
                'phone' => '9995332244',
                'specialty' => 'Plomeria, bombas y tinacos',
                'average_cost' => 950,
                'rating' => 4.5,
                'availability' => 'Citas de 9:00 a 18:00 con guardia nocturna.',
            ],
            'interno' => [
                'type' => 'tecnico_interno',
                'name' => 'Rafael Pacheco',
                'email' => 'rafael.pacheco@naboo.local',
                'phone' => '9995012299',
                'specialty' => 'Mantenimiento general',
                'average_cost' => 800,
                'rating' => 4.8,
                'availability' => 'Tecnico interno de lunes a viernes.',
                'user_id' => $this->technician->id,
            ],
        ];

        return collect($rows)
            ->map(fn (array $row): MaintenanceProvider => MaintenanceProvider::query()->updateOrCreate(
                ['email' => $row['email']],
                $row + ['is_active' => true]
            ))
            ->all();
    }

    /**
     * @param array<string, Property> $properties
     * @param array<string, MaintenanceProvider> $providers
     * @param array<int, Expense> $expenses
     */
    private function seedMaintenance(array $properties, array $providers, array $expenses): void
    {
        $rows = [
            [
                'property' => 'NB-TEM-012',
                'provider' => 'plomeria',
                'category' => 'plomeria',
                'priority' => 'alta',
                'status' => 'programado',
                'title' => 'Baja presion en regadera principal',
                'exact_location' => 'Bano recamara principal',
                'description' => 'La inquilina reporta baja presion desde hace tres dias; se revisara mezcladora y bomba presurizadora.',
                'reported_at' => now()->subDays(4)->setTime(9, 20),
                'scheduled_visit_at' => now()->addDay()->setTime(10, 30),
                'payer' => 'administracion',
                'payment_rule' => 'preventivo',
                'labor_cost' => 850,
                'material_cost' => 420,
                'final_cost' => 1270,
            ],
            [
                'property' => 'NB-MON-804',
                'provider' => 'clima',
                'category' => 'aire_acondicionado',
                'priority' => 'media',
                'status' => 'en_proceso',
                'title' => 'Minisplit sala no enfria correctamente',
                'exact_location' => 'Sala comedor',
                'description' => 'Equipo prende pero no baja de 27 grados. Se sospecha falta de mantenimiento y gas.',
                'reported_at' => now()->subDays(7)->setTime(18, 10),
                'scheduled_visit_at' => now()->subDay()->setTime(16, 0),
                'payer' => 'administracion',
                'payment_rule' => 'preventivo',
                'labor_cost' => 1100,
                'material_cost' => 650,
                'final_cost' => 1750,
            ],
            [
                'property' => 'NB-PLY-004',
                'provider' => 'interno',
                'category' => 'limpieza',
                'priority' => 'baja',
                'status' => 'completado',
                'title' => 'Limpieza profunda posterior a tormenta',
                'exact_location' => 'Terraza y area de piscina',
                'description' => 'Retiro de hojas, arena y lavado de terraza. Trabajo concluido con evidencia fotografica.',
                'reported_at' => now()->subDays(18)->setTime(8, 40),
                'scheduled_visit_at' => now()->subDays(16)->setTime(9, 0),
                'payer' => 'administracion',
                'payment_rule' => 'preventivo',
                'labor_cost' => 900,
                'material_cost' => 280,
                'final_cost' => 1180,
                'completed_at' => now()->subDays(15)->setTime(13, 15),
            ],
            [
                'property' => 'NB-CHO-018',
                'provider' => 'interno',
                'category' => 'seguridad',
                'priority' => 'urgente',
                'status' => 'pendiente',
                'title' => 'Control de acceso requiere alta de tags',
                'exact_location' => 'Caseta de privada',
                'description' => 'Antes de publicar la propiedad se deben habilitar tags y validar camaras comunes.',
                'reported_at' => now()->subHours(10),
                'scheduled_visit_at' => null,
                'payer' => 'administracion',
                'payment_rule' => 'otro',
                'labor_cost' => 0,
                'material_cost' => 0,
                'final_cost' => 0,
            ],
        ];

        foreach ($rows as $index => $row) {
            $property = $properties[$row['property']];
            $provider = $providers[$row['provider']];

            $ticket = MaintenanceTicket::query()->updateOrCreate(
                ['property_id' => $property->id, 'title' => $row['title']],
                [
                    'reported_by_user_id' => $row['property'] === 'NB-TEM-012' ? $this->tenantUser->id : $this->admin->id,
                    'current_provider_id' => $provider->id,
                    'reported_by_role' => $row['property'] === 'NB-TEM-012' ? 'inquilino' : 'administrador',
                    'reported_by_name' => $row['property'] === 'NB-TEM-012' ? 'Laura Pech Dominguez' : 'Admin Local',
                    'category' => $row['category'],
                    'priority' => $row['priority'],
                    'status' => $row['status'],
                    'reference' => 'MTTO-2026-'.str_pad((string) ($index + 21), 4, '0', STR_PAD_LEFT),
                    'exact_location' => $row['exact_location'],
                    'description' => $row['description'],
                    'additional_notes' => 'Caso demo cargado para revisar flujo de mantenimiento.',
                    'reported_at' => $row['reported_at'],
                    'scheduled_visit_at' => $row['scheduled_visit_at'],
                    'payer' => $row['payer'],
                    'payment_rule' => $row['payment_rule'],
                    'payment_rule_notes' => 'Costo absorbido segun politica demo y revision administrativa.',
                    'assigned_at' => in_array($row['status'], ['asignado', 'programado', 'en_proceso', 'completado'], true) ? $row['reported_at']->copy()->addHours(4) : null,
                    'started_at' => in_array($row['status'], ['en_proceso', 'completado'], true) ? $row['reported_at']->copy()->addDays(2) : null,
                    'completed_at' => $row['completed_at'] ?? null,
                ]
            );

            MaintenanceTicketAssignment::query()->updateOrCreate(
                ['ticket_id' => $ticket->id, 'provider_id' => $provider->id],
                [
                    'assigned_by_user_id' => $this->admin->id,
                    'notes' => 'Asignado por disponibilidad y especialidad.',
                    'assigned_at' => $ticket->assigned_at ?? now(),
                    'is_current' => true,
                ]
            );

            $expense = null;
            if ((float) $row['final_cost'] > 0) {
                $expense = Expense::query()->updateOrCreate(
                    ['property_id' => $property->id, 'concept' => 'Mantenimiento '.$ticket->reference.': '.$ticket->title],
                    [
                        'amount' => $row['final_cost'],
                        'excluded_from_totals' => $row['payer'] === 'inquilino',
                        'due_date' => now()->addDays(7)->toDateString(),
                        'description' => $row['description'],
                        'paid_at' => $ticket->status === 'completado' ? now()->subDays(14) : null,
                        'created_by' => $this->admin->id,
                    ]
                );
            }

            MaintenanceTicketCost::query()->updateOrCreate(
                ['ticket_id' => $ticket->id],
                [
                    'expense_id' => $expense?->id,
                    'labor_cost' => $row['labor_cost'],
                    'material_cost' => $row['material_cost'],
                    'advance_cost' => $row['status'] === 'en_proceso' ? 500 : 0,
                    'final_cost' => $row['final_cost'],
                    'currency' => 'MXN',
                    'payer' => $row['payer'],
                    'payment_rule' => $row['payment_rule'],
                    'notes' => 'Costeo demo con mano de obra y materiales separados.',
                ]
            );

            foreach (['pendiente', 'revisado', $row['status']] as $statusIndex => $status) {
                MaintenanceTicketStatusHistory::query()->updateOrCreate(
                    ['ticket_id' => $ticket->id, 'to_status' => $status],
                    [
                        'changed_by_user_id' => $this->admin->id,
                        'from_status' => $statusIndex === 0 ? null : 'pendiente',
                        'notes' => 'Actualizacion de estado demo.',
                        'changed_at' => $row['reported_at']->copy()->addHours($statusIndex * 5),
                    ]
                );
            }

            MaintenanceTicketMessage::query()->updateOrCreate(
                ['ticket_id' => $ticket->id, 'message' => 'Se confirma recepcion del reporte y se dara seguimiento por este medio.'],
                [
                    'sender_user_id' => $this->admin->id,
                    'recipient_user_id' => $ticket->reported_by_user_id,
                    'channel' => 'interno',
                ]
            );

            MaintenanceTicketNotification::query()->updateOrCreate(
                ['ticket_id' => $ticket->id, 'event' => 'ticket_status_changed'],
                [
                    'channel' => 'email',
                    'recipient' => $property->tenant?->email ?? 'administracion@naboo.local',
                    'was_sent' => true,
                    'notified_at' => now()->subDays(1),
                    'meta' => ['status' => $ticket->status, 'reference' => $ticket->reference],
                ]
            );
        }
    }

    private function seedStorage(): void
    {
        $warehouse = StorageWarehouse::query()->updateOrCreate(
            ['name' => 'Bodega Central Naboo'],
            [
                'location' => 'Calle 21 #88, Parque Industrial Yucatan, Merida',
                'maps_url' => 'https://maps.google.com/?q=Parque+Industrial+Yucatan',
                'is_default' => true,
            ]
        );

        $beachWarehouse = StorageWarehouse::query()->updateOrCreate(
            ['name' => 'Bodega Playa Progreso'],
            [
                'location' => 'Calle 31 #144, Progreso, Yucatan',
                'maps_url' => 'https://maps.google.com/?q=Progreso+Yucatan',
                'is_default' => false,
            ]
        );

        $zones = [
            'central-a' => StorageZone::query()->updateOrCreate(['storage_warehouse_id' => $warehouse->id, 'name' => 'Rack A - Herramientas'], ['is_default' => true]),
            'central-b' => StorageZone::query()->updateOrCreate(['storage_warehouse_id' => $warehouse->id, 'name' => 'Rack B - Consumibles'], ['is_default' => false]),
            'playa' => StorageZone::query()->updateOrCreate(['storage_warehouse_id' => $beachWarehouse->id, 'name' => 'Cuarto seco'], ['is_default' => true]),
        ];

        $items = [
            ['Herramienta', 'Rotomartillo SDS Plus', 'Bosch', 'bueno', 2, 'central-a', 'Equipo para perforacion en concreto con maletin.'],
            ['Consumible', 'Filtros minisplit 12k/18k', 'Mirage', 'bueno', 18, 'central-b', 'Filtros de reemplazo para servicios preventivos.'],
            ['Refaccion', 'Bomba presurizadora 1/2 HP', 'Evans', 'regular', 1, 'central-a', 'Probada; conservar como respaldo temporal.'],
            ['Mobiliario', 'Sillas plegables blancas', 'Lifetime', 'bueno', 12, 'playa', 'Uso en entregas y eventos de villas.'],
            ['Limpieza', 'Kit piscina cloro y red', 'AquaPool', 'bueno', 5, 'playa', 'Consumible para mantenimiento semanal de alberca.'],
            ['Electronico', 'Router LTE desbloqueado', 'Huawei', 'malo', 1, 'central-b', 'Equipo con falla intermitente; pendiente baja definitiva.'],
        ];

        foreach ($items as [$productType, $name, $brand, $condition, $quantity, $zoneKey, $description]) {
            $zone = $zones[$zoneKey];
            $item = StorageItem::query()->updateOrCreate(
                ['name' => $name, 'brand' => $brand],
                [
                    'product_type' => $productType,
                    'storage_warehouse_id' => $zone->storage_warehouse_id,
                    'storage_zone_id' => $zone->id,
                    'description' => $description,
                    'condition' => $condition,
                    'quantity' => $quantity,
                    'photo' => $this->demoSvg('almacen/'.Str::slug($name).'.svg', $name, '#706f63'),
                ]
            );

            StorageItemLog::query()->updateOrCreate(
                ['storage_item_id' => $item->id, 'action' => 'entrada inicial'],
                [
                    'user_id' => $this->admin->id,
                    'note' => 'Alta de inventario fisico demo.',
                    'changes' => ['quantity' => $quantity, 'condition' => $condition],
                ]
            );
        }
    }

    /**
     * @param array<string, Property> $properties
     * @param array<string, Owner> $owners
     * @param array<string, Tenant> $tenants
     */
    private function seedDossiers(array $properties, array $owners, array $tenants): void
    {
        foreach ($properties as $property) {
            $this->document($property->documents(), 'escritura', 'Escritura o contrato de compraventa', PropertyDocument::STATUS_APPROVED, now()->addYears(5));
            $this->document($property->documents(), 'predial', 'Predial vigente', PropertyDocument::STATUS_UPLOADED, now()->addMonths(7));
            $this->document($property->documents(), 'contrato_administracion', 'Contrato de administracion', PropertyDocument::STATUS_APPROVED, now()->addYear());
        }

        foreach ($owners as $owner) {
            $this->document($owner->documents(), 'ine', 'Identificacion oficial', OwnerDocument::STATUS_APPROVED, now()->addYears(3));
            $this->document($owner->documents(), 'constancia_fiscal', 'Constancia de situacion fiscal', OwnerDocument::STATUS_UPLOADED, null);
            $this->document($owner->documents(), 'estado_cuenta', 'Estado de cuenta bancario', OwnerDocument::STATUS_APPROVED, now()->addMonths(4));
        }

        foreach ($tenants as $tenant) {
            $this->document($tenant->documents(), 'ine', 'Identificacion oficial', TenantDocument::STATUS_APPROVED, now()->addYears(2));
            $this->document($tenant->documents(), 'comprobante_ingresos', 'Comprobante de ingresos', TenantDocument::STATUS_UPLOADED, now()->addMonths(2));
            $this->document($tenant->documents(), 'contrato_arrendamiento', 'Contrato de arrendamiento', $tenant->dossier_status === Tenant::DOSSIER_INCOMPLETE ? TenantDocument::STATUS_PENDING : TenantDocument::STATUS_APPROVED, now()->addYear());
        }

        $sampleDoc = PropertyDocument::query()->first();
        if ($sampleDoc) {
            DossierDeletedFile::query()->updateOrCreate(
                ['entity_type' => 'property', 'document_id' => $sampleDoc->id, 'version_number' => 0],
                [
                    'entity_id' => $sampleDoc->property_id,
                    'entity_name' => $sampleDoc->property?->internal_name,
                    'document_group' => 'property',
                    'document_type' => $sampleDoc->document_type,
                    'document_label' => $sampleDoc->label,
                    'version_id' => null,
                    'original_name' => 'predial-2024-obsoleto.pdf',
                    'file_path' => 'demo/expedientes/obsoletos/predial-2024.pdf',
                    'mime_type' => 'application/pdf',
                    'file_size' => 87000,
                    'file_deleted' => true,
                    'delete_reason' => 'Archivo reemplazado por version vigente.',
                    'deleted_by_user_id' => $this->admin->id,
                    'deleted_at' => now()->subDays(9),
                ]
            );
        }
    }

    private function document($relation, string $type, string $label, string $status, ?Carbon $expiresAt): void
    {
        $path = $status === 'pending' ? null : $this->demoPdf('expedientes/'.$type.'-'.Str::random(8).'.pdf', $label);
        $document = $relation->updateOrCreate(
            ['document_type' => $type],
            [
                'label' => $label,
                'file_path' => $path,
                'status' => $status,
                'uploaded_at' => $path ? now()->subDays(rand(2, 50)) : null,
                'expires_at' => $expiresAt?->toDateString(),
            ]
        );

        if ($path && $document->versions()->count() === 0) {
            $document->versions()->create([
                'version_number' => 1,
                'file_path' => $path,
                'original_name' => Str::slug($label).'.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 124000,
                'uploaded_by' => $this->admin->id,
                'uploaded_at' => $document->uploaded_at ?? now(),
            ]);
        }
    }

    private function payment(Charge $charge, float $amount, string $status, string $method, Carbon $date, ?string $notes = null): void
    {
        ChargePayment::query()->updateOrCreate(
            ['charge_id' => $charge->id, 'reference' => 'NABOO-'.$charge->id.'-'.$status],
            [
                'amount' => $amount,
                'currency' => 'mxn',
                'status' => $status,
                'source' => $status === ChargePayment::STATUS_PENDING_VALIDATION
                    ? ChargePayment::SOURCE_PUBLIC_TRANSFER
                    : ChargePayment::SOURCE_ADMIN,
                'payment_method' => $method,
                'receipt_path' => $this->demoPdf('pagos/comprobante-'.$charge->id.'-'.$status.'.pdf', 'Comprobante '.$charge->concept),
                'paid_at' => $date,
                'payment_date' => $date->toDateString(),
                'registered_by' => $this->admin->id,
                'validated_by' => $status === ChargePayment::STATUS_SUCCEEDED ? $this->admin->id : null,
                'validation_notes' => $status === ChargePayment::STATUS_SUCCEEDED ? 'Validado contra estado de cuenta demo.' : null,
                'payload' => ['demo' => true, 'bank' => 'BBVA', 'tracking_key' => 'DEM'.str_pad((string) $charge->id, 8, '0', STR_PAD_LEFT)],
                'notes' => $notes,
            ]
        );
    }

    private function user(string $name, string $email, string $role): User
    {
        $user = User::query()->firstOrNew(['email' => $email]);
        $user->fill([
            'name' => $name,
            'password' => Hash::make('NabooLocal123!'),
            'is_active' => true,
        ]);
        $user->forceFill(['email_verified_at' => now()]);
        $user->save();
        $user->assignRole($role);

        return $user;
    }

    private function demoPdf(string $path, string $title): string
    {
        $content = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n"
            ."2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
            ."3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]/Contents 4 0 R/Resources<</Font<</F1 5 0 R>>>>>>endobj\n"
            ."4 0 obj<</Length 96>>stream\nBT /F1 18 Tf 72 720 Td (Documento demo Naboo) Tj 0 -32 Td (".substr($title, 0, 48).") Tj ET\nendstream endobj\n"
            ."5 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj\nxref\n0 6\n0000000000 65535 f \ntrailer<</Root 1 0 R/Size 6>>\nstartxref\n412\n%%EOF";

        Storage::disk('public')->put('demo/'.$path, $content);

        return 'demo/'.$path;
    }

    private function demoSvg(string $path, string $title, string $color): string
    {
        $safeTitle = e($title);
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="960" height="540" viewBox="0 0 960 540">
  <rect width="960" height="540" fill="{$color}"/>
  <rect x="60" y="60" width="840" height="420" rx="18" fill="rgba(255,255,255,0.18)" stroke="rgba(255,255,255,0.45)" stroke-width="3"/>
  <text x="90" y="275" fill="#fff" font-family="Arial, sans-serif" font-size="42" font-weight="700">{$safeTitle}</text>
  <text x="90" y="330" fill="rgba(255,255,255,0.82)" font-family="Arial, sans-serif" font-size="24">Imagen demo local Naboo</text>
</svg>
SVG;

        Storage::disk('public')->put('demo/'.$path, $svg);

        return 'demo/'.$path;
    }
}
