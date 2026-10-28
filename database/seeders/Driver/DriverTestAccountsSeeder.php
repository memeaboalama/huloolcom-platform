<?php

namespace Database\Seeders\Driver;

use App\Application\Marketplace\Enums\MerchantOrderStatuses;
use App\Domain\MarketPlace\Models\MerchantOrder;
use App\Domain\Provider\Models\Company;
use App\Support\Enum\StatusEnum;
use Domain\Driver\Models\Driver;
use App\Domain\Provider\Models\DriversRegisters;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use MatanYadaev\EloquentSpatial\Objects\Point;

/**
 * DriverTestAccountsSeeder
 * ------------------------
 * NON-DESTRUCTIVE, IDEMPOTENT local test data for the driver module.
 * - Never truncates, never deletes unrelated rows.
 * - Safe to run repeatedly (firstOrNew / updateOrCreate style).
 *
 * Accounts created / refreshed:
 *   driver.approved@test.local  / Password123!  (approved, is_complete=1, network online)
 *   driver.pending@test.local   / Password123!  (pending, is_complete=0, offline)
 *   register.pending@test.local / Password123!  (row in drivers_registers, not a driver yet)
 *   demo@test.com               / Password123!  (existing company id=1, password was EMPTY — now set)
 *   admin@gmail.com             / 123456789     (existing admin id=1, password refreshed)
 *
 * Orders created for driver.approved@test.local:
 *   DRV-TEST-ACCEPT-001  status=confirmed   (ready to accept → started-work)
 *   DRV-TEST-ACCEPT-002  status=ready       (second accept candidate)
 *   DRV-TEST-ONWAY-001   status=on-way      (to be completed)
 *   DRV-TEST-COMPLETED-01 status=completed  (history/stats)
 *   DRV-TEST-CANCELLED-01 status=cancelled  (history/stats)
 *   DRV-TEST-AUTO-001    status=confirmed, driver_id=NULL + branch near driver (auto-assign)
 *
 * Run with:
 *   php artisan db:seed --class=Database\\Seeders\\Driver\\DriverTestAccountsSeeder
 */
class DriverTestAccountsSeeder extends Seeder
{
    private const PASSWORD = 'Password123!';
    private const ADMIN_PASSWORD = '123456789';

    private const APPROVED_EMAIL = 'driver.approved@test.local';
    private const PENDING_EMAIL = 'driver.pending@test.local';
    private const REGISTER_EMAIL = 'register.pending@test.local';

    private const RIYADH = [24.7136, 46.6753];

    public function run(): void
    {
        $this->seedAdmin();
        $company = $this->seedCompany();
        $approved = $this->seedApprovedDriver($company);
        $this->seedPendingDriver();
        $this->seedPendingRegister();
        $this->seedOrders($approved);
        $this->ensureDriverSearchCapacity();

        $this->command?->info('DriverTestAccountsSeeder finished (idempotent, no truncation).');
    }

    private function seedAdmin(): void
    {
        $admin = DB::table('admins')->where('email', 'admin@gmail.com')->first();
        if ($admin) {
            DB::table('admins')->where('id', $admin->id)->update([
                'password' => Hash::make(self::ADMIN_PASSWORD),
                'status' => StatusEnum::ACTIVE->value,
            ]);
            $this->command?->info("Admin #{$admin->id} admin@gmail.com password refreshed.");
        }
    }

    private function seedCompany(): Company
    {
        $company = Company::firstOrNew(['email' => 'demo@test.com']);
        $company->fill([
            'company_name' => 'demo',
            'name' => 'demo',
            'phone' => '512345678',
            'password' => Hash::make(self::PASSWORD),
            'phone_verified_at' => now(),
            'email_verified_at' => now(),
            'country_id' => $company->country_id ?? 1,
            'status' => StatusEnum::ACTIVE->value,
        ])->save();
        $this->command?->info("Company #{$company->id} demo@test.com ready.");
        return $company;
    }

    private function seedApprovedDriver(Company $company): Driver
    {
        $driver = Driver::firstOrNew(['email' => self::APPROVED_EMAIL]);
        $isNew = !$driver->exists;
        $driver->fill([
            'name' => 'سائق اختبار معتمد',
            'phone' => '591000201',
            'dialcode' => '966',
            'country_code' => 'sa',
            'country_name' => 'Saudi Arabia',
            'password' => Hash::make(self::PASSWORD),
            'location' => new Point(self::RIYADH[0], self::RIYADH[1], 4326),
            'network_status' => 1,
            'status' => StatusEnum::ACTIVE,
            'is_complete' => 1,
            'taken_commission' => 15,
            'company_id' => $company->id,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ])->save();

        $required = ['license_copy', 'car_copy', 'residence_permit_copy', 'iban_copy', 'car_registration_copy'];
        $existing = DB::table('driver_files')
            ->where('fileable_type', Driver::class)
            ->where('fileable_id', $driver->id)
            ->pluck('collection_name')
            ->all();

        foreach ($required as $collection) {
            if (!in_array($collection, $existing, true)) {
                DB::table('driver_files')->insert([
                    'file_name' => $collection . '.pdf',
                    'original_name' => $collection . '.pdf',
                    'mime_type' => 'application/pdf',
                    'collection_name' => $collection,
                    'type' => null,
                    'storage' => 'public',
                    'url' => '/storage/driver-test/' . $collection . '.pdf',
                    'path' => 'driver-test/' . $collection . '.pdf',
                    'order_column' => array_search($collection, $required) + 1,
                    'size' => 1024,
                    'fileable_type' => Driver::class,
                    'fileable_id' => $driver->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command?->info(
            ($isNew ? 'Created' : 'Refreshed') .
            " approved driver #{$driver->id} {$driver->email} / " . self::PASSWORD
        );
        return $driver;
    }

    private function seedPendingDriver(): void
    {
        $driver = Driver::firstOrNew(['email' => self::PENDING_EMAIL]);
        $isNew = !$driver->exists;
        $driver->fill([
            'name' => 'سائق اختبار معلق',
            'phone' => '591000202',
            'dialcode' => '966',
            'password' => Hash::make(self::PASSWORD),
            'location' => new Point(self::RIYADH[0] + 0.05, self::RIYADH[1] + 0.05, 4326),
            'network_status' => 0,
            'status' => StatusEnum::INACTIVE,
            'is_complete' => 0,
            'taken_commission' => 15,
            'company_id' => null,
        ])->save();

        $this->command?->info(
            ($isNew ? 'Created' : 'Refreshed') .
            " pending driver #{$driver->id} {$driver->email} / " . self::PASSWORD
        );
    }

    private function seedPendingRegister(): void
    {
        $row = DriversRegisters::firstOrNew(['email' => self::REGISTER_EMAIL]);
        $isNew = !$row->exists;
        $row->fill([
            'name' => 'طلب تسجيل معلق',
            'phone' => '591000203',
            'dialcode' => '966',
            'password' => Hash::make(self::PASSWORD),
            'country_id' => 1,
            'status' => StatusEnum::INACTIVE,
            'network_status' => 0,
        ])->save();

        $this->command?->info(
            ($isNew ? 'Created' : 'Refreshed') .
            " pending register #{$row->id} {$row->email} / " . self::PASSWORD
        );
    }

    private function seedOrders(Driver $driver): void
    {
        $userId = DB::table('users')->orderBy('id')->value('id');
        $merchant = DB::table('merchants')->where('id', 1)->first()
            ?? DB::table('merchants')->orderBy('id')->first();
        $branch = DB::table('merchant_branches')
            ->where('merchant_id', $merchant->id)
            ->orderBy('id')->first();
        $addressId = DB::table('user_addresses')->orderBy('id')->value('id');

        $specs = [
            // code, status, driver_id, note
            ['DRV-TEST-ACCEPT-001', MerchantOrderStatuses::UPCOMING->value, $driver->id, 'to-accept'],
            ['DRV-TEST-ACCEPT-002', MerchantOrderStatuses::READY->value, $driver->id, 'to-accept'],
            ['DRV-TEST-ONWAY-001', MerchantOrderStatuses::ON_WAY->value, $driver->id, 'to-complete'],
            ['DRV-TEST-COMPLETED-01', MerchantOrderStatuses::PREVIOUS->value, $driver->id, 'history'],
            ['DRV-TEST-CANCELLED-01', MerchantOrderStatuses::CANCELLED->value, $driver->id, 'history'],
            ['DRV-TEST-AUTO-001', MerchantOrderStatuses::UPCOMING->value, null, 'auto-assign candidate'],
        ];

        foreach ($specs as [$code, $status, $driverId, $note]) {
            $exists = MerchantOrder::where('code', $code)->first();
            $payload = [
                'user_id' => $userId,
                'merchant_id' => $merchant->id,
                'merchant_branch_id' => $branch->id ?? null,
                'address_id' => $addressId,
                'addressInOrder' => 'الرياض - حي النرجس - شارع الاختبار',
                'order_type' => $branch ? 'fixed' : 'mobile',
                'channel' => 'mobile',
                'dialcode' => '966',
                'client_name' => 'عميل اختبار',
                'client_phone' => '591111111',
                'client_lat' => (string) self::RIYADH[0],
                'client_long' => (string) self::RIYADH[1],
                'merchant_name' => $merchant->company_name ?? $merchant->name ?? 'demo',
                'payment_method' => 'cash',
                'payment_type' => 'Cash',
                'subtotal' => 100,
                'vat' => 15,
                'total_after_vat' => 115,
                'total_addon' => 0,
                'total_service' => 0,
                'discount' => 0,
                'total_after_discount' => 115,
                'delivery_fee' => 15,
                'delivery_fee_discount' => 0,
                'delivery_distance' => 3.5,
                'driver_id' => $driverId,
                'has_assign_driver' => $driverId ? 1 : 0,
                'assign_driver_id' => $driverId,
                'commission_from_driver' => $driverId ? 15 : 0,
                'service_type_id' => 1,
                'mark' => 'white',
                'status' => $status,
                'note' => $note,
            ];

            if ($exists) {
                $exists->update($payload);
                // Re-assert code (some model events can blank it)
                DB::table('merchant_orders')->where('id', $exists->id)->update(['code' => $code]);
                $this->command?->info("Order {$code} (#{$exists->id}) refreshed → {$status}");
            } else {
                $created = MerchantOrder::create($payload);
                DB::table('merchant_orders')->where('id', $created->id)->update(['code' => $code]);
                $this->command?->info("Order {$code} (#{$created->id}) created → {$status}");
            }
        }
    }

    private function ensureDriverSearchCapacity(): void
    {
        $settings = DB::table('general_settings')->orderBy('id')->first();
        if ($settings && empty($settings->driver_search_capacity)) {
            DB::table('general_settings')->where('id', $settings->id)->update([
                'driver_search_capacity' => 50,
            ]);
            $this->command?->info('general_settings.driver_search_capacity set to 50 (was empty).');
        }
    }
}
